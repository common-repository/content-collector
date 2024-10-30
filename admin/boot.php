<?php
	/**
	 * Webcraftic подключаем ресурсы администратора
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright Alex Kovalev 25.05.2017
	 * @version 1.0
	 */
	/**
	 * Регистрируем метабоксы плагина
	 *
	 * @since 1.0.0
	 */
	function wbcr_scrapes_add_meta_boxes()
	{
		$plugin = WSCR_Plugin::app();

		// #wpbcr-scrapes post type
		require_once(WSCR_PLUGIN_DIR . '/admin/metaboxes/facebook-options.php');
		Wbcr_FactoryMetaboxes401::registerFor(new WSC_FacebookOptionsMetaBox($plugin), WSCR_SCRAPES_POST_TYPE, $plugin);

		require_once(WSCR_PLUGIN_DIR . '/admin/metaboxes/base-options.php');
		Wbcr_FactoryMetaboxes401::registerFor(new WSC_BaseOptionsMetaBox($plugin), WSCR_SCRAPES_POST_TYPE, $plugin);

		require_once(WSCR_PLUGIN_DIR . '/admin/metaboxes/images-options.php');
		Wbcr_FactoryMetaboxes401::registerFor(new WSC_ImagesOptionsMetaBox($plugin), WSCR_SCRAPES_POST_TYPE, $plugin);
		/*require_once(WSCR_PLUGIN_DIR . '/admin/metaboxes/shedule-options.php');
		Wbcr_FactoryMetaboxes401::registerFor(new WSC_SheduleOptionsMetaBox($plugin), WSCR_SCRAPES_POST_TYPE, $plugin);*/
	}

	add_action('init', 'wbcr_scrapes_add_meta_boxes');


	/* reset old ki */
    $old_version = get_option('wsc_default_ki', false);
    if($old_version === false){
        delete_option('wsc_ki');
    }

    $ki = get_option('wbcr_scr_ki', false);
    if(empty($ki)){
        $ki = get_option('wsc_default_ki', false);
    }
    /*if(($ki) === false){
        $domain = get_option('home');
        if(!$domain) $domain = 'http'.(($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'];
        $default_args = array(
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'method' => 'POST',
            'timeout' => 10,
            'sslverify' => false,
            'body' => array(
                'domain' => $domain
            )
        );

        $request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v1/parser/nc', $default_args);
        if( is_wp_error($request) ) {
            // throw new Exception($request->get_error_message());
        }else{
            $body = wp_remote_retrieve_body($request);
            $body = trim($body);

            if( empty($body) ) {
                // throw new Exception($request->get_error_message());
            }else{
                $new_ki = @json_decode($body, ARRAY_A);
                if( isset($new_ki['error']) ) {
                    //throw new Exception($posts['error']);
                }else{
                    $ki = $new_ki['ki'];
                    update_option('wsc_default_ki',$ki);
                }
            }
        }
    }*/
    define('WSC_'.'KI',$ki);



    $fs = Freemius::get_instance_by_file('wp-parser/wpparser.php');
    $sites = $fs->get_site();

    $oldval = get_option('wsc_tasks3', false);
    $now = time();
    if($tasks_allowed === false or intval($oldval['cache_time'])+(5*60) <= $now or $sites->plan_id != $oldval['plan_id']){
        $tasks_allowed = 3;
        $default_args = array(
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'method' => 'POST',
            'timeout' => 10,
            'sslverify' => false,
            'body' => array(
            )
        );



        $default_args['body']['install_id'] = $sites->id;
        $default_args['body']['install_pk'] = $sites->public_key;


        $request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v2/info/tasks', $default_args);
        if( !is_wp_error($request) and $body = trim(wp_remote_retrieve_body($request)) and !empty($body)) {
            $result = @json_decode($body, ARRAY_A);
            if($result and !isset($result['errors'])){
                $tasks_allowed = $result['data']['task_limit'];
                $new_val = array('value' => $tasks_allowed, 'cache_time'=>time(), 'plan_id'=>$sites->plan_id);
                update_option('wsc_tasks3', $new_val, false);
            }
        }

    }else{
        $tasks_allowed = $oldval['value'];
    }
    $tasks = get_posts(array(
        'post_type' => WSCR_SCRAPES_POST_TYPE,
        'post_status' => 'publish,private,draft,future',
        'numberposts' => 100,
        'suppress_filters' => true,

    ));
    if(count($tasks) >= $tasks_allowed){
        define('WBCR_DISABLE_ADD_TASKS', true);
    }


    function wbcr_after_save_handler($post_id){
        if ( wp_is_post_revision( $post_id ) )
            return;
        $post_type = get_post_type($post_id);
        if($post_type != WSCR_SCRAPES_POST_TYPE){
            return;
        }
        $tasks = get_posts(array(
            'numberposts' => -1,
            'post_status' => 'publish,draft,private,future,pending',
            'post_type' => WSCR_SCRAPES_POST_TYPE

        ));
        $default_args = array(
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'method' => 'POST',
            'sslverify' => false,
            'timeout' => 10,
            'body' => array(
                'ki' => WSC_KI,
                'tasks' => count($tasks)

            )
        );

        $request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v2/info/reg-tasks', $default_args);


    }

    add_action('save_post', 'wbcr_after_save_handler');
    add_action('deleted_post', 'wbcr_after_save_handler');



    function wbcr_scrapes_msg_by_code($code){
        ?>
        <h1><?=__("Ошибка", "wbcr-scrapes");?></h1>
        <p><?=__("Количество запросов на сегодня исчерпано. Лимиты сбрасываются каждые 24 часа", "wbcr-scrapes");?></p>
        <p><?=__("Хотите парсить больше? Перейдите на премиум тариф.", "wbcr-scrapes");?></p>
        <p><a class="button button-primary button-large" href="<?=WSCR_API_SERVER_URL?>/admin/" target="_blank"><?=__("Купить", "wbcr-scrapes");?></a></p>
        <?php
    }


	/**
	 * Добавляем поле короткое описание статьи, перед полем заголовка
	 * Только для записей
	 */
	// ВРЕМЕННО!
	//============================================
	function wbcr_scrapes_add_excerpt_field()
	{
		global $post, $wp_meta_boxes;
		if( !empty($post) && $post->post_type == 'post' ) {
			$post_excerpt = get_the_excerpt($post->ID);
			echo '<textarea rows="1" cols="40" name="excerpt" id="excerpt">' . $post_excerpt . '</textarea>';
		}
	}

	add_action('edit_form_after_title', 'wbcr_scrapes_add_excerpt_field');

	/**
	 * Удаляем wordpress метабокс короткого описания
	 */
	function wbcr_scrapes_remove_post_meta_boxes()
	{
		remove_meta_box('postexcerpt', 'post', 'normal');
	}

	add_action('admin_menu', 'wbcr_scrapes_remove_post_meta_boxes');


    add_action( 'wp_trash_post', 'disable_trash_for_scrapes' );
    function disable_trash_for_scrapes( $post_id ){
        if ( get_post_type($post_id) === WSCR_SCRAPES_POST_TYPE ) {
            wp_delete_post( $post_id, true );
        }
    }