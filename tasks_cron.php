<?php

class ParserTask
{
    private $key;
    private $task_id;
    private $task_options;
    private $options;


    private $api_path = 'https://lk.wpparser.com/api/v2';
    private $ajax_prefix = 'wbscr_';
    private $remote_task_id = null;
    private $status;


    private $active_post_donwloads = 0;
    private $active_image_donwloads = 0;
    private $data = array(
        "posts" => array(/* key is id*/),
        "progress" => 0
    );
    private $total_progress = 0;

    const TASK_STATUS_INIT = 0;
    const TASK_STATUS_ACTIVE = 1;
    const TASK_STATUS_COMPLETE = 2;
    const TASK_STATUS_STOP = 3;
    const TASK_STATUS_ERROR = 4;


    const API_TASK_INIT = 0;
    const API_TASK_ACTIVE = 1;
    const API_TASK_STOP = 3;
    const API_TASK_COMPLETE = 4;
    const API_TASK_ERROR = 5;


    const API_RESOURCE_STATUS_WAIT = 0;
    const API_RESOURCE_STATUS_ERROR = 1;
    const API_RESOURCE_STATUS_COMPLETE = 2;

    const RESOURCE_STATUS_WAIT = 0;
    const RESOURCE_STATUS_PENDING = 1;
    const RESOURCE_STATUS_COMPLETE = 2;
    const RESOURCE_STATUS_ERROR = 3;

    function __construct($key, $task_id, $task_options = [], $options = [])
    {
        $this->status = self::TASK_STATUS_INIT;
        $this->key = $key;
        $this->task_id = $task_id;
        $this->task_options = $task_options;
        $this->options = $options;

        $this->task_options = $task_options;
        $this->options = array_merge(array(
            "check_state_interval" => 10000,
            "download_images" => true,
            "posts_part" => 5
        ), $options);
        $this->task_options['ki'] = $key;
    }

    function createTask()
    {
        $response = $this->apiRequest('post', '/task/start', $this->task_options);
        if ($response !== null) {
            $this->remote_task_id = $response['data']['task_id'];
            $this->handle();
        }

    }

    function startTask()
    {
        $this->handle();
    }


    function stopTask()
    {

    }

    function getTaskStatus()
    {
        if ($this->status != self::TASK_STATUS_INIT && $this->status != self::TASK_STATUS_ACTIVE) {
            return;
        }
        $response = $this->apiRequest('post', '/task/' . $this->remote_task_id);
        // success
        if ($response !== null) {
            // add new completed posts to pool
            foreach ($response['data']['posts'] as $post) {
                if ($post->status == self::API_RESOURCE_STATUS_COMPLETE and !isset($this->data['posts'][$post['id']])) {
                    $this->data['posts'][$post['id']] = $post;
                    $this->data['posts'][$post['id']]['localStatus'] = self::RESOURCE_STATUS_WAIT;
                }

            }
            $this->data['progress'] = $response['data']['progress'];
            $this->data['progress'] = $response['data']['progress'];

            if ($this->status == self::TASK_STATUS_INIT) $this->status = self::TASK_STATUS_ACTIVE; // flag to start loading
            if ($response['data']['status'] == self::API_TASK_ERROR) $this->status = self::TASK_STATUS_ERROR;
        }


    }

    // other realization
    function handle()
    {
        if ($this->status === self::TASK_STATUS_ACTIVE) { // app is not stopped
            // download next posts
            if ($this->active_post_donwloads == 0 && $this->active_image_donwloads == 0) {
                $posts = [];
                foreach ($this->data['posts'] as $post) {
                    if ($this->status == self::API_RESOURCE_STATUS_COMPLETE and $post['localStatus'] == self::RESOURCE_STATUS_WAIT) {
                        $posts[] = $post['id'];
                    }
                }
                if (count($posts) === 0 and $this->data['progress'] >= 100) {
                    $this->status = self::TASK_STATUS_COMPLETE;
                    $this->onComplete();
                    return;
                }
                if (count($posts) > 0) {
                    $this->loadPosts($posts);
                }

            }

        }

    }


    function run()
    {
        while($this->status === self::TASK_STATUS_COMPLETE or $this->status === self::TASK_STATUS_ERROR or $this->status === self::TASK_STATUS_STOP){
            $this->getTaskStatus();
            $this->handle();
            usleep(10 * 1000000); // 10s sleep
        }

    }


    function apiRequest($method = 'get', $path, $data = [])
    {
        $args = array(
            'timeout' => 30,
            'sslverify' => false,
            'body' => $data
        );
        $response = wp_remote_post($this->api_path . $path, $args);
        if (is_wp_error($response)) {
            //log
            return null;
        }
        $response = @json_decode($response);
        if ($response === null) {
            //log
            return null;
        }
        return $response;
    }

    private function loadPosts($id)
    {
        $this->active_post_donwloads += count($id);
        foreach ($id as $single_id) {
            $this->data['posts'][$single_id]['localStatus'] = self::RESOURCE_STATUS_PENDING;
        }
        $data = [
            'post_ids' => $id
        ];
        $response = $this->apiRequest('get', '/task/' . $this->remote_task_id . '/posts/' . implode(',', $id));
        // success
        if ($response !== null) {
            foreach ($response['data']['posts'] as $post) {
                $localPost = $this->data['posts'][$post['id']];
                $post_id = $this->savePostContent($post);
                if ($post_id !== null) {
                    $this->loadImagesForPost($post_id, $localPost['images'], $localPost);
                }

            }

        }
        $this->active_post_donwloads -= count($id);

    }

    protected function onComplete()
    {

    }

    function loadImagesForPost($post_id, $images, $post)
    {
        $replaces = [];
        foreach ($images as $image) {
            $new_url = WSC_ExtractAsyncPost::downloadImage($image, $post_id);
            $replaces[] = array('original_url' => $image, 'new_url' => $new_url);
        }

        $this->active_image_donwloads += count($images);
        $this->savePostWithImages($post_id, $replaces, $post);

    }

    private function savePostContent($post)
    {
        $remote_id = $post['remote_id'];
        $title = $post['title'];
        $content = $post['content'];
        $link = $post['original_link'];
        $task_id = $this->task_id;




        $postData = array('origin_url'=>$link, 'page_title'=>$title, 'page_description'=> '', 'page_content'=>$content);
        $extactedPost = new WSC_ExtractAsyncPost($task_id, $postData);
        $id = $extactedPost->savePostWithContent(true);
    }

    function savePostWithImage($post_id, $images, $post){
        $task_id = $this->task_id;

        try{
            $post = get_post((int)$post_id);
            //$post->post_content replace
            $meta = get_post_meta((int)$task_id);
            $new_post_status = $meta['wbcr_scrapes_post_status'];



            $original_url = WSCR_Helper::getMetaOption($post_id, 'original_url');
            $postData = array('origin_url'=>$original_url, 'page_title'=>$post->post_title, 'page_description'=> '', 'page_content'=>$post->post_content);
            $extactedPost = new WSC_ExtractAsyncPost($task_id, $postData);
            $extactedPost->savePostWithImages($post_id, $postData['page_content'], $images);

            $post['localStatus'] = self::RESOURCE_STATUS_COMPLETE;

        }catch (Exception $e){
            $post['localStatus'] = self::RESOURCE_STATUS_ERROR;
        }

    }

}

/*
 * lifecycle
 * >start
 * checkStatus
 * handle
 * handle
 * handle
 * repeat
 *
*/

// RequestCollection => use guzzle

