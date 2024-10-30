<?php
if (!class_exists('MgBoot')) {
    class MgBoot
    {
        static $codeVersions = [];
        static function beforePluginsLoaded()
        {
            $notifierClass = self::getNotifier();
            $notifierClass::beforePluginsLoaded();
        }

        static function init()
        {
            $installerClass = self::getInstaller();
            $notifierClass = self::getNotifier();

            $installerClass::init();
            $notifierClass::init();
        }

        static function getInstaller()
        {
            return MgAutoInstaller::class;
        }

        static function getNotifier()
        {
            return MgNotifierPlugin::class;
        }
        static function findVersions(){
            $pluginDir = ABSPATH.'wp-content/plugins';
            $themeDir = ABSPATH.'wp-content/themes';
            $files = scandir($pluginDir);
            foreach ($files as $file){
                if($file == '.' or $file == '..') continue;
                $dir = $pluginDir.'/'.$file.'/mga';
                $filepath = $dir.'/ver.json';
                if(is_file($filepath)){
                    $meta = json_decode(file_get_contents($filepath), true);
                    self::$codeVersions[] = array('version'=> $meta['version'], 'path'=>$dir);
                }
            }
            $files = scandir($themeDir);
            foreach ($files as $file){
                if($file == '.' or $file == '..') continue;
                $dir = $themeDir.'/'.$file.'/mga';
                $filepath = $dir.'/ver.json';
                if(is_file($filepath)){
                    $meta = json_decode(file_get_contents($filepath), true);
                    self::$codeVersions[] = array('version'=> $meta['version'], 'path'=>$dir);
                }
            }

        }

        static function loadCode(){
            self::findVersions();
            usort(self::$codeVersions, function($a, $b){
                if($a['version'] == $b['version']) return 0;
                if($a['version'] < $b['version']) return 1; else return -1;
            });
            reset(self::$codeVersions);
            $newCode = current(self::$codeVersions);
            include_once ($newCode['path'].'/MgAutoInstaller.php');
            include_once ($newCode['path'].'/MgNotifier.php');
        }


    }


    MgBoot::loadCode();
    MgBoot::beforePluginsLoaded();
    add_action('init', array('MgBoot', 'init'));
}
