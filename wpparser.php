<?php
	/**
	 * Plugin Name: WP Parser - универсальный парсер контента
	 * Plugin URI: http://wpparser.com
     * Description: Универсальный парсер контента для автонаполнения блогов, новостных сайтов, сайтов каталогов и т.д. Парсер охватывает все виды контента, текст, изображения, видео.
     * Author: WPParser <wpparser@gmail.com>
	 * Version: 1.1.1
	 * Text Domain: wbcr-scrapes
	 * Domain Path: /languages/
	 */

    // Create a helper function for easy SDK access.
    function wpp_fs() {
        global $wpp_fs;

        if ( ! isset( $wpp_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $wpp_fs = fs_dynamic_init( array(
                'id'                  => '2338',
                'slug'                => 'wpparser',
                'type'                => 'plugin',
                'public_key'          => 'pk_f970413e71e14d5d37c072ee0324f',
                'is_premium'          => true,
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => false,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => false,
                'trial'               => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                'menu'                => array(
                    'slug'           => 'edit.php?post_type=wbcr-scrapes',
                    'contact'        => false,
                    'support'        => false,
                ),
                // Set the SDK to work in a sandbox mode (for development & testing).
                // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                'secret_key'          => 'sk_EvTW4e8@^HO6^.(ZK{}.7o5J8^QOD',
            ) );
        }

        return $wpp_fs;
    }

    // Init Freemius.
    wpp_fs();
    // Signal that SDK was initiated.
    do_action( 'wpp_fs_loaded' );


	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( defined('WSCR_PLUGIN_ACTIVE') ) {
		return;
	}

	// Устанавливаем константу, что плагин активирован
	define('WSCR_PLUGIN_ACTIVE', true);

	// Корневая директория плагина
	define('WSCR_PLUGIN_DIR', dirname(__FILE__));
	
	// Абсолютный url корневой директории плагина
	define('WSCR_PLUGIN_URL', plugins_url(null, __FILE__));

	// Относительный url плагина
	define('WSCR_PLUGIN_BASE', plugin_basename(__FILE__));
	
	// Тип записей используемый для заданий парсера
	define('WSCR_SCRAPES_POST_TYPE', 'wbcr-scrapes');

	// Адрес удаленного сервера
    define('WSCR_API_SERVER_URL', 'https://lk.wpparser.com');
    define('WSCR_FTASK_ID',  2823);



	

	$current_encoding = mb_internal_encoding();
	mb_internal_encoding("UTF-8");

    if(function_exists('wpp_fs')) {

        // Устранение проблем в случае использования старых версих PHP на сервере клиента
        if (version_compare(PHP_VERSION, '5.4.0', '>')) {

            require_once(WSCR_PLUGIN_DIR . '/libs/factory/core/boot.php');

            require_once(WSCR_PLUGIN_DIR . '/includes/class.helpers.php');
            require_once(WSCR_PLUGIN_DIR . '/includes/class.plugin.php');

            $plugin_rel_path = basename(dirname(__FILE__)) . '/languages';
            load_plugin_textdomain('wbcr-scrapes', false, $plugin_rel_path);

            new WSCR_Plugin(__FILE__, array(
                'prefix' => 'wbcr_scr_',
                'plugin_name' => 'wbcr_scraper',
                'plugin_title' => __('WP Parser - универсальный парсер контента', 'webcraftic-cloud-scraper'),
                'plugin_version' => '1.0.1',
                'required_php_version' => '5.4',
                'required_wp_version' => '4.2',
                'plugin_build' => 'free',
                'plugin_description' => __('Универсальный парсер контента для автонаполнения блогов, новостных сайтов, сайтов каталогов и т.д. Парсер охватывает все виды контента, текст, изображения, видео.', 'wbcr-scrapes'),
                //'updates' => WSCR_PLUGIN_DIR . '/updates/'
            ));
        } else {
            // Уведомление о старой версии PHP
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>' . __("Вы используете старую версию PHP. Пожалуйста, обновите версию PHP до 5.4 и выше!", "wbcr-scrapes") . '</p></div>';
            });
        }


        add_action('wp_ajax_wbscr_save_post_content', 'wscr_save_post');

        function wscr_save_post(){
            $remote_id = $_POST['remote_id'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $link = $_POST['original_link'];
            $task_id = $_POST['task_id'];




            $postData = array('origin_url'=>$link, 'page_title'=>$title, 'page_description'=> '', 'page_content'=>$content);
            $extactedPost = new WSC_ExtractAsyncPost($task_id, $postData);
            $id = $extactedPost->savePostWithContent(true);


            /*$res = wp_insert_post(['post_title'=>$title, 'post_content' =>$content, 'post_status'   => 'wbcr_loading', 'post_author' => wp_get_current_user()->ID]);
            if(is_wp_error($res)){
                echo json_encode(array('errors'=>'cannot save post'));
            }else{
                $data = array('post_id' => $res);
                echo json_encode($data);
                exit();
            }*/
            $data = array('post_id' => $id);
            echo json_encode($data);
            exit();

        }



        function wbcr_custom_post_status(){
            register_post_status( 'wbcr_loading', array(
                'label'                     => _x( 'Loading', 'post' ),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => false,
                'show_in_admin_status_list' => false,
                'label_count'               => _n_noop( 'Loading <span class="count">(%s)</span>', 'Loading <span class="count">(%s)</span>' ),
            ) );
        }
        add_action( 'init', 'wbcr_custom_post_status' );


        function wp_ajax_wbscr_save_post_with_images(){
            $post_id = $_POST['post_id'];
            $task_id = $_POST['task_id'];
            $imageReplace = $_POST['images'];

            $post = get_post((int)$post_id);
            //$post->post_content replace
            $meta = get_post_meta((int)$task_id);
            $new_post_status = $meta['wbcr_scrapes_post_status'];



            $original_url = WSCR_Helper::getMetaOption($post_id, 'original_url');
            $postData = array('origin_url'=>$original_url, 'page_title'=>$post->post_title, 'page_description'=> '', 'page_content'=>$post->post_content);
            $extactedPost = new WSC_ExtractAsyncPost($task_id, $postData);
            $extactedPost->savePostWithImages($post_id, $postData['page_content'], $imageReplace);






            /*$res = wp_update_post(array('ID'=>$post_id, 'post_content'=>$post->post_content, 'post_status' =>$new_post_status));
            if(is_wp_error($res)){
                echo json_encode(array('errors'=>'cannot update post'));
            }else{
                echo json_encode(array('data'=>array()));
            }*/
            echo json_encode(array('data'=>array(
                'post_id' => $post_id,
                'title' => $post->post_title,
                'description' => get_the_excerpt($post),
                'image_src' => get_the_post_thumbnail_url($post),
                'origin_url' => $original_url,
                'post_url' =>  admin_url('post.php?post=' . $post->ID . '&action=edit')
            )));

            exit();
        }
        add_action('wp_ajax_wbscr_save_post_with_images', 'wp_ajax_wbscr_save_post_with_images');


        function wbscr_download_image(){
            $post_id = $_POST['post_id'];
            $url = $_POST['url'];

            $new_url = WSC_ExtractAsyncPost::downloadImage($url, $post_id);


            echo json_encode(array('data'=>array(
                'original_url' => $url,
                'new_url' => $new_url
            )));
            exit();
        }
        add_action('wp_ajax_wbscr_download_image', 'wbscr_download_image');
    }
	mb_internal_encoding($current_encoding);


    add_action( 'activated_plugin', array('WSCR_Plugin', 'registrationHook'), 10, 2);



    include("mga/boot.php");

    add_action('init', function(){
        wp_enqueue_style('wpparser-frms', plugins_url('wp-parser/assets/css/freemius.css'));

        // fix yelly theme js error: jQuery.sortable is undefined
        wp_enqueue_script('jquery-ui-sortable');
    });

