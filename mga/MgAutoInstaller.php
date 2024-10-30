<?php
if (!defined('MG_AUTO_INSTALLER')) {
    define('MG_AUTO_INSTALLER', true);


    define('WP_API_CORE',
        'http://api.wordpress.org/core/version-check/1.7/?locale=');
    define('WPQI_CACHE_PATH', ABSPATH . 'wp-content/plugins/cache/');
    define('WPQI_CACHE_CORE_PATH', WPQI_CACHE_PATH . 'core/');
    define('WPQI_CACHE_PLUGINS_PATH', WPQI_CACHE_PATH . 'plugins/');


    class MgAutoInstaller
    {
        static $ver = 1;

        static function init()
        {
            if (!is_admin() or wp_doing_ajax()) {
                return;
            }
            //load get_plugins function
            if (!function_exists('get_plugins')) {
                // подключим файл с функцией get_plugins()
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $directory = ABSPATH;
            $plugins_dir = $directory . 'wp-content/plugins/';
            // get archived plugins
            $installDir = __DIR__ . '/install';
            $files = scandir($installDir);
            $plugins = array();
            // installed in past
            $installedPlugins = get_option('mg_auto_installer_past', array());

            foreach ($files as $file) {
                if ($file == '..' or $file == '.') {
                    continue;
                }
                $info = pathinfo($file);
                if ($info['extension'] != 'zip') {
                    continue;
                }
                $pluginSlug = $info['basename'];
                $pos = strripos($pluginSlug, '.zip');
                if ($pos !== false) {
                    $pluginSlug = substr($pluginSlug, 0, $pos);
                }
                if (in_array($pluginSlug, $installedPlugins)) {
                    continue;
                } else {
                    $installedPlugins[] = $pluginSlug;
                }
                $plugins[] = [
                    'slug' => $pluginSlug,
                    'archive' => $installDir . '/' . $file
                ];

            }
            update_option('mg_auto_installer_past', $installedPlugins);

            //skip exists plugins
            $exist_plugins = array_keys(get_plugins());
            $plugins = array_filter($plugins,
                function ($plugin) use ($exist_plugins) {
                    $allow = true;
                    foreach ($exist_plugins as $exist_plugin) {
                        if (strpos($exist_plugin, $plugin['slug'] . '/')
                            === 0
                        ) {
                            $allow = false;
                            break;
                        }
                    }
                    return $allow;
                });

            foreach ($plugins as $plugin) {
                // We unzip it
                $zip = new ZipArchive;
                if ($zip->open($plugin['archive']) === true) {
                    $zip->extractTo($plugins_dir);
                    $zip->close();
                }
            }

            // fix issue 'caching plugin data'
            wp_cache_flush();

            $plugins_info = get_plugins();
            $plugins_main_file = array_filter(array_keys(get_plugins()),
                function ($item) use ($plugins) {
                    $allow = false;
                    foreach ($plugins as $plugin) {
                        if (strpos($item, $plugin['slug'] . '/') === 0) {
                            $allow = true;
                            break;
                        }
                    }
                    return $allow;
                });

            $res = activate_plugins($plugins_main_file);
            if (is_wp_error($res)) {
                throw new Exception('Ошибка активации дополнительного плагина');
            }
        }


    }

}
