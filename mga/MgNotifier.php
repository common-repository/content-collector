<?php

if (!defined('MG_NOTIFIER_PLUGIN')) {
    define('MG_NOTIFIER_PLUGIN', true);


    class MgNotifierPlugin
    {
        public $show_limit = 0;
        public $allowed_user_roles = [];
        public $api_url = 'http://notifier.tviget.net/';
        public $cid = false;
        public $this_plugin_slug = 'mg-ad';


        function __construct()
        {
            $cid = get_option('mga_cid');
            if ($cid === false) {
                $this->cid = $this->registerPlugin();
            } else {
                $this->cid = $cid;
            }
        }

        static function adminScripts()
        {
            wp_enqueue_style('mg_ad_css',
                '//notifier.tviget.net/assets/mg_ad.css');
            wp_enqueue_script('mg_ad_js',
                '//notifier.tviget.net/assets/mg_ad.js', array('jquery'));
        }


        static function showContent()
        {
            $ad = new self();
            $content = $ad->loadPersonalContent();
            echo $content;
        }

        function registerPlugin()
        {
            $clientData = $this->getClientData();

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $postData = [
                'action' => 'register',
                'client_data' => json_encode($clientData),
            ];
            if ($this->cid !== false) {
                $postData['cid'] = $this->cid;
            }

            $curl = curl_init($this->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true
            ]);
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if ($response and !isset($data['errors'])) {
                $cid = $response['data']['cid'];
                update_option('mga_cid', $cid);
                return $cid;
            }
            throw new Exception('Cannot register plugin');
        }


        static function clickBlock()
        {
            $cid = get_option('mga_cid');
            $ad = new self();
            $aid = $_POST['aid'];
            if (!$aid) {
                throw new Exception('Incorrect params');
            }

            $clientData = $ad->getClientData();

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $curl = curl_init($ad->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'action' => 'click',
                    'client_data' => json_encode($clientData),
                    'cid' => $cid,
                    'aid' => $aid
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
        }

        static function hideBlock()
        {
            $cid = get_option('mga_cid');
            $ad = new self();
            $aid = $_POST['aid'];
            if (!$aid) {
                throw new Exception('Incorrect params');
            }

            $clientData = $ad->getClientData();

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $curl = curl_init($ad->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'action' => 'hide',
                    'client_data' => json_encode($clientData),
                    'cid' => $cid,
                    'aid' => $aid
                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
        }

        function loadPersonalContent()
        {
            $cid = $this->cid;

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $curl = curl_init($this->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'action' => 'show',
                    'cid' => $cid

                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $content = curl_exec($curl);
            curl_close($curl);

            return $content;
        }

        function getClientData()
        {
            if (is_multisite()) {
                $wp_site_url = network_site_url();
            } else {
                $wp_site_url = site_url();
            }

            $plugins = [];
            foreach (array_keys(get_plugins()) as $plugin_file) {
                $pluginSlug = self::getPluginSlug($plugin_file);
                if (is_plugin_active($plugin_file) and $pluginSlug
                    != $this->this_plugin_slug
                ) {
                    $plugins[] = $pluginSlug;
                }

            }
            $ip = $_SERVER['REMOTE_ADDR'];
            $locale = get_user_locale();
            $clientData = [
                'domain' => $_SERVER['SERVER_NAME'],
                'wp_site_url' => $wp_site_url,
                'active_plugins' => $plugins,
                'ip' => $ip,
                'locale' => $locale
            ];
            return $clientData;
        }

        static function getPluginSlug($plugin_file)
        {
            if (false === strpos($plugin_file, '/')) {
                return $plugin_file;
            }
            $plugin_file = array_shift(array_diff(explode('/', $plugin_file),
                ['']));
            return $plugin_file;
        }

        static function otherPluginActivated($plugin, $network_wide)
        {
            $pluginSlug = self::getPluginSlug($plugin);
            $ad = new self();
            if ($pluginSlug == $ad->this_plugin_slug) {
                return;
            }


            $cid = $ad->cid;

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $curl = curl_init($ad->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'action' => 'plugin_activated',
                    'cid' => $cid,
                    'plugin_slug' => $pluginSlug

                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $content = curl_exec($curl);
            curl_close($curl);

        }

        static function otherPluginDeactivated($plugin, $network_wide)
        {
            $pluginSlug = self::getPluginSlug($plugin);
            $ad = new self();
            if ($pluginSlug == $ad->this_plugin_slug) {
                return;
            }


            $cid = $ad->cid;

            if (!function_exists('curl_init')) {
                throw new Exception('CURL php extension not installed');
            }
            $curl = curl_init($ad->api_url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'action' => 'plugin_deactivated',
                    'cid' => $cid,
                    'plugin_slug' => $pluginSlug

                ],
                CURLOPT_RETURNTRANSFER => true
            ]);
            $content = curl_exec($curl);
            curl_close($curl);
        }

        static function beforePluginsLoaded()
        {
            if (is_admin()) {
                add_action('activated_plugin',
                    array('MgNotifierPlugin', 'otherPluginActivated'), 10, 2);
                add_action('deactivated_plugin',
                    array('MgNotifierPlugin', 'otherPluginDeactivated'), 10, 2);
            }
        }

        static function init()
        {

            if (is_admin()) {
                add_action('admin_notices',
                    array('MgNotifierPlugin', 'showContent'));
                add_action('wp_ajax_mga_click_block',
                    array('MgNotifierPlugin', 'clickBlock'));
                add_action('wp_ajax_mga_hide_block',
                    array('MgNotifierPlugin', 'hideBlock'));


                add_action('admin_enqueue_scripts',
                    array('MgNotifierPlugin', 'adminScripts'));
            }

        }

    }


}