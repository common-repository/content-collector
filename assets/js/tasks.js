jQuery(function($){

    String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.replace(new RegExp(search, 'g'), replacement);
    };



    function ParserTask(key, task_id, task_options, options) {
        var TASK_STATUS_INIT = 0;
        var TASK_STATUS_ACTIVE = 1;
        var TASK_STATUS_COMPLETE = 2;
        var TASK_STATUS_STOP = 3;
        var TASK_STATUS_ERROR = 4;


        var API_TASK_INIT = 0;
        var API_TASK_ACTIVE = 1;
        var API_TASK_STOP = 3;
        var API_TASK_COMPLETE = 4;
        var API_TASK_ERROR = 5;


        var API_RESOURCE_STATUS_WAIT = 0;
        var API_RESOURCE_STATUS_ERROR = 1;
        var API_RESOURCE_STATUS_COMPLETE = 2;

        var RESOURCE_STATUS_WAIT = 0;
        var RESOURCE_STATUS_PENDING = 1;
        var RESOURCE_STATUS_COMPLETE = 2;
        var RESOURCE_STATUS_ERROR = 3;

        this.api_path = 'https://lk.wpparser.com/api/v2';
        var API_LIMIT_REACHED = 403;
        var API_NEED_AUTH = 401;
        this.ajax_prefix = 'wbscr_';
        this.key = key;
        this.task_id = task_id;
        this.remote_task_id = null;
        this.options = {
            check_state_interval: 10000,
            download_images: true,
            posts_part: 5
        };
        this.task_options = task_options || {};
        this.task_options.ki = key;

        this.status = TASK_STATUS_INIT; // STOP / PROGRESS / COMPLETE

        this.active_post_donwloads = 0;
        this.active_image_donwloads = 0;
        this.data = {posts: {/* key is id*/}, progress: 0};
        this.total_progress = 0;

        this.el= {
            results: $('.js-wpp-results'),
            progressbar: $('.js-wpp-progressbar'),
            progressbar_fill: $('.js-wpp-progressbar-fill'),
            progressbar_number: $('.js-wpp-progressbar-number'),
            error_header: $('.js-wpp-error-header'),
            loading_header: $('.js-wpp-loading-header'),
            complete_header: $('.js-wpp-complete-header'),
            messages: $('.js-wpp-message-box')

        };

        this.checkErrors = function(response){
            if(response.errors !== undefined){
                this.status = TASK_STATUS_ERROR;
                return true;
            }
            return false;
        };

        this.createTask = function () {
            var inst = this;
            $.ajax({
                url: inst.api_path + '/task/start',
                data: inst.task_options,
                dataType: 'json',
                method: 'post',
                success: function (response) {
                    inst.remote_task_id = response.data.task_id;

                    inst.getTaskStatus();
                    inst.handle();
                    inst.el.progressbar_fill.addClass('animated');


                }
            });
        };

        this.startTask = function () {
            var inst = this;
            inst.getTaskStatus();
            inst.handle();
        };

        this.stopTask = function () {

        };

        this.getTaskStatus = function () {
            var inst = this;
            if(inst.status != TASK_STATUS_INIT && inst.status != TASK_STATUS_ACTIVE){
                return;
            }
            $.ajax({
                url: inst.api_path + '/task/'+inst.remote_task_id,
                method: 'post',
                data: {
                },
                dataType: 'json',
                success: function (response) {
                    // add new completed posts to pool
                    for (var i = 0; i < response.data.posts.length; i++) {
                        var item = response.data.posts[i];
                        if (item.status == API_RESOURCE_STATUS_COMPLETE && !inst.data.posts[item.id]) {
                            inst.data.posts[item.id] = item;
                            inst.data.posts[item.id].localStatus = RESOURCE_STATUS_WAIT;
                        }
                    }
                    inst.data.progress = response.data.progress;
                    inst.data.status = response.data.status;
                    inst.updateProgress();



                    if(inst.status == TASK_STATUS_INIT) inst.status = TASK_STATUS_ACTIVE; // flag to start loading
                    if(response.data.status  == API_TASK_ERROR){
                        inst.status = TASK_STATUS_ERROR;
                        inst.onError();
                        if(response.data.errors !== undefined){
                            for(var prop in response.data.errors){
                                response.data.errors.hasOwnProperty(prop);
                                var error = response.data.errors[prop];
                                this.showError(error.code, error.title);
                            }

                        }


                    }



                },
                complete: function () {
                    if((inst.status == TASK_STATUS_INIT || inst.status == TASK_STATUS_ACTIVE) && inst.data.progress < 100){
                        setTimeout(function(){
                            inst.getTaskStatus();
                        }, inst.options.check_state_interval);
                    }
                }
            });
        };

        this.handleMeta = function(response){
            // handle messages
            if(response.meta !== undefined && response.meta.messages !== undefined){
                for(var i=0; i<response.meta.messages; i++){
                    var message = response.meta.messages[i];
                    this.displayMessage(message.type, message.title, message.message, message.code);
                }
            }
        };



        this.handle = function () {
            var inst = this;
            if (this.status == TASK_STATUS_ACTIVE) { // app is not stopped
                // download next posts
                if (this.active_post_donwloads == 0 && this.active_image_donwloads == 0) {
                    var posts = [];
                    for (var prop in this.data.posts) {
                        if(!this.data.posts.hasOwnProperty(prop)) continue;
                        var item = this.data.posts[prop];
                        if (item.status == API_RESOURCE_STATUS_COMPLETE && item.localStatus == RESOURCE_STATUS_WAIT) { // parsed
                            posts.push(item.id);
                        }
                    }
                    if((posts.length === 0 &&  this.data.status == API_TASK_COMPLETE)){
                        this.status = TASK_STATUS_COMPLETE;
                        this.onComplete();
                        return;
                    }

                    if(posts.length > 0){
                        this.loadPosts(posts);
                    }



                }
            }
            if(this.status == TASK_STATUS_ACTIVE || this.status == TASK_STATUS_INIT){
                setTimeout(function(){
                    inst.handle();
                }, 1000);
            }

        };

        // id is array
        this.loadPosts = function (id) {
            //   /api/v1/task/{task_id}/15,45,84,20  - filter by id
            var inst = this;
            /*if (id instanceof Array) {
                id = id.join(',');
            }*/

            this.active_post_donwloads += id.length;

            for(var i=0;i<id.length;i++){
                var single_id = id[i];
                this.data.posts[single_id].localStatus = RESOURCE_STATUS_PENDING;
            }




            $.ajax({
                url: inst.api_path + '/task/'+inst.remote_task_id + '/posts/'+ id.join(','),
                method: 'get',
                dataType: 'json',
                data:{
                    'post_ids': id
                },
                success: function (data) {
                    for (var i2 = 0; i2 < data.data.posts.length; i2++) {
                        (function() {
                            var post = data.data.posts[i2];
                            var localPost = inst.data.posts[post.id];
                            inst.savePostContent(post, function (post_id) {
                                inst.loadImagesForPost(post_id, localPost.images, localPost);
                            });
                        })()


                    }
                },

            });

            /*data:{
                items:[
                    {id: 15, link: 'http://asdasdd.d', title: 'Hello', content: 'asdasdasdasdasd', }
                ]

            }*/

        };

        this.updateProgress = function(){
            var local_progress = 0;
            var complete_posts = 0;
            var total_posts = 0; // local
            for(var prop in this.data.posts){
                if(!this.data.posts.hasOwnProperty(prop)) continue;
                var item = this.data.posts[prop];
                total_posts++;
                if(item.localStatus == RESOURCE_STATUS_COMPLETE || item.localStatus == RESOURCE_STATUS_ERROR){
                    complete_posts++;
                }
            }
            if(this.data.progress ==0 || total_posts == 0){
                this.total_progress =  0;
            }else{
                local_progress = (complete_posts / total_posts);
                this.total_progress =  (local_progress * (this.data.progress/100))*100; // remote progress
            }


            this.el.progressbar_fill.css('width', this.total_progress+'%');
            this.el.progressbar_number.html(Math.floor(this.total_progress) + '%');
        };


        this.bindWidget = function () {

        };

        this.updateWidget = function () {

        };

        this.displayItem = function (post_id, title, description, image_src, origin_url, post_url ) {
            var show_image = '';
            if(image_src != false) show_image = 'show';
            var html = this.template('wpp-result-tpl', {
                post_id: post_id,
                title: title,
                description: description,
                image_src: image_src,
                origin_url: origin_url,
                post_url: post_url,
                show_image: show_image
            });
            this.el.results.append(html);
        };

        this.displayMessage = function (type, title, message, code) {
            if(type == undefined) type = 'notice';
            if(code == undefined) code = 0;

            var html = this.template('wpp-message-tpl', {
                type: type,
                title: title,
                message: message,
                code: code
            });
            this.el.messages.append(html);

        };

        this.displayErrorItem = function(remote_id, title, origin_url){
            var html = this.template('wpp-error-result-tpl', {
                remote_id: remote_id,
                title: title,
                origin_url: origin_url,
            });
            this.el.results.append(html);
        };

        this.showError = function (code, title) {
            var html;

            switch (code){
                case API_LIMIT_REACHED:
                    html = this.template('wpp-error-limit-tpl');
                    this.el.messages.append(html);
                    break;
                case API_NEED_AUTH:
                    html = this.template('wpp-error-auth-tpl');
                    this.el.messages.append(html);
                    break;

                default:
                    this.el.messages.append('<p class="wpp-message-box-error">'+title+'</p>');
            }
        };

        this.showInfo = function (code, title) {
            this.el.messages.append('<p class="wpp-message-box-info">'+title+'</p>');
        };

        this.template = function(template_id, data){
            var tpl = $('#'+template_id).html();
            for(var prop in data){
                if(!data.hasOwnProperty(prop)) continue;
                tpl = tpl.replaceAll('{'+prop+'}', data[prop]);
            }
            return tpl;
        };

        this.onComplete = function () {
            this.el.progressbar_fill.removeClass('animated');
            this.el.loading_header.hide();
            this.el.complete_header.show();
            this.el.progressbar.hide();
            var posts = [];
            for (var prop in this.data.posts) {
                if(!this.data.posts.hasOwnProperty(prop)) continue;
                var item = this.data.posts[prop];
                if (item.localStatus == RESOURCE_STATUS_COMPLETE) { // parsed
                    posts.push(item.id);
                }
            }
            var html = this.template('wpp-number-posts-tpl', {
                posts_length: posts.length
            });
            this.displayMessage('notice', html, '', 0);
        };

        this.onError = function() {
            this.el.complete_header.hide();
            this.el.loading_header.hide();
            this.el.error_header.show();
            this.el.progressbar.addClass('wpp-error');

        };





        this.loadImagesForPost = function (post_id, images, post) {
            var inst = this;
            var requestCollection = new RequestCollection();

            for (var i = 0; i < images.length; i++) {
                var image_url = images[i];
                requestCollection.add(ajaxurl, {action: this.ajax_prefix + 'download_image', url: image_url});
            }
            this.active_image_donwloads += images.length;

            requestCollection.onCompleteAll(function (requests) {
                inst.active_image_donwloads -= images.length;
                var success_images = [];
                for (var i = 0; i < requests.length; i++) {
                    var request = requests[i];
                    if (!request.error) {
                        success_images.push({
                            original_url: request.response.data.original_url,
                            new_url: request.response.data.new_url
                        });
                    }
                }
                inst.savePostWithImages(post_id, success_images, post);
            });
            requestCollection.run();

        };

        // save data as hidden post for get post_id
        this.savePostContent = function (post, successCallback) {
            var inst = this;
            post.action = inst.ajax_prefix + 'save_post_content';
            post.task_id = this.task_id;
            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: post,
                dataType: 'json',
                success: function (data) {
                    if(typeof successCallback == 'function'){
                        successCallback(data.post_id);

                    }

                }
            })

        };

        // images is [{original_url: '', new_url: ''}, ..]
        this.savePostWithImages = function (post_id, images, post) {
            var inst = this;
            $.ajax({
                url: ajaxurl,
                method: 'post',
                dataType: 'json',
                data: {
                    action: inst.ajax_prefix + 'save_post_with_images',
                    post_id: post_id,
                    images: images,
                    task_id: inst.task_id
                },
                success: function(data){
                    if(!data.errors){
                        post.localStatus = RESOURCE_STATUS_COMPLETE;
                        inst.displayItem(data.data.post_id, data.data.title, data.data.description, data.data.image_src, data.data.origin_url, data.data.post_url);
                    }else{
                        post.localStatus = RESOURCE_STATUS_ERROR;
                        inst.displayErrorItem(post.id, post.title, post.origin_url);
                    }
                    inst.updateProgress();

                },
                error: function(){
                    post.localStatus = RESOURCE_STATUS_ERROR;
                },
                complete: function(){
                    inst.active_post_donwloads -= 1;
                }
            })

        };
        this.Api = function(options){
            var user_success = options.success;
            var user_error = options.error;
            var user_complete = options.complete;
            var inst = this;

            options.error = function(){
                inst.showError(0, 'Connection error ' + options.url);
                if(typeof user_error === 'function'){
                    user_error();
                }
            };
            options.success = function(response, textStatus, jqXHR){
                if(response.errors !== undefined && response.errors.length){
                    for(var key in response.errors){
                        if(!response.errors.hasOwnProperty(key)) continue;
                        var error = response.errors[key];
                        inst.showError(error.code, error.title)
                    }
                    if(typeof user_error === 'function'){
                        user_error();
                    }

                }else{
                    if(typeof user_success === 'function'){
                        user_success(response, textStatus, jqXHR);
                    }
                }
            };

            $.ajax(options);

        };


    }


    function RequestCollection() {
        this.completed = 0;
        this.errors = 0;
        this.success = 0;
        this.save_response_data = true;
        this.requests = [];
        this.pause = 500; //ms
        this.data = [];
        this.callbacks = {onCompleteAll: []};

        this.add = function (url, postData, successCallback) {
            this.requests.push({
                url: url,
                postData: postData,
                successCallback: successCallback,
                error: false,
                response: null
            });
            return this.requests.length - 1; // return index of request
        };

        this.runOne = function (request, completeCallback) {
            var inst = this;
            $.ajax({
                url: request.url,
                method: 'post',
                data: request.postData,
                dataType: 'json',
                success: function (response) {
                    if (typeof request.successCallback == 'function') {
                        request.successCallback(response);
                    }
                    if (inst.save_response_data) {
                        request.response = response;
                    }
                    inst.success++;
                },
                complete: function () {
                    inst.completed++;
                    if (typeof completeCallback == 'function') {
                        completeCallback();
                    }
                },
                error: function () {
                    request.error = true;
                    inst.errors++;
                }
            });
        };

        this.run = function (i) {
            var inst = this;
            if (i === undefined) i = 0;
            if (i >= this.requests.length) {
                this._completeAll();
                return;
            }
            this.runOne(this.requests[i], function () {
                setTimeout(function () {
                    inst.run(++i);
                }, inst.pause);

            });
        };

        this._completeAll = function () {
            for (var i = 0; i < this.callbacks.onCompleteAll.length; i++) {
                var callback = this.callbacks.onCompleteAll[i];
                callback(this.requests);
            }
        };

        // add callback
        this.onCompleteAll = function (callback) {
            this.callbacks.onCompleteAll.push(callback);
        };
    }

    window.ParserTask = ParserTask;
    window.RequestCollection = RequestCollection;

    if(window.taskOptions){
        window.task = new ParserTask(taskOptions.ki, taskOptions.task_id, taskOptions.task_options, taskOptions.options);
        window.task.createTask();
    }else{
        console.warn('taskOptions are missing');
    }

});
