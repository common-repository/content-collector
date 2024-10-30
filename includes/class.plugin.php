<?php
	/**
	 * Webcraftic cloud scraper page core class
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 19.02.2018, Webcraftic
	 * @version 1.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( !class_exists('WSCR_Plugin') ) {

		class WSCR_Plugin extends Wbcr_Factory401_Plugin {

			/**
			 * @var Wbcr_Factory401_Plugin
			 */
			private static $app;

			/**
			 * @param string $plugin_path
			 * @param array $data
			 * @throws Exception
			 */
			public function __construct($plugin_path, $data)
			{
				parent::__construct($plugin_path, $data);

				self::$app = $this;

				$this->setTextDomain();
				$this->setModules();

				$this->globalScripts();

				if( is_admin() ) {
					$this->adminScripts();
				}
			}

			/**
			 * @return Wbcr_Factory401_Plugin
			 */
			public static function app()
			{
				return self::$app;
			}

			protected function setTextDomain()
			{

				load_plugin_textdomain('webcraftic-cloud-scraper', false, dirname(WSCR_PLUGIN_BASE) . '/languages/');
			}
			
			protected function setModules()
			{

				$this->load(array(
					// Bootstrap фреймворк, используется для работы с интерфейсом плагина
					array('libs/factory/bootstrap', 'factory_bootstrap_401', 'admin'),
					// Font awesome используется для работы с интерфейсом плагина
					array('libs/factory/font-awesome', 'factory_fontawesome_321', 'admin'),
					// Компонент форм, позволяет быстро создавать различные поля форм, табы, блоки,
					// без затрат времени на верстку интерфейса
					array('libs/factory/forms', 'factory_forms_401', 'admin'),
					// Компонент для создания страниц в панели администратора, по мимо удобного
					// создания страниц, этот компонент предоставлят большой спектр функций для работы
					// с действиями, перенаправлениями, уведомлениями страницы. Имеет готовые шаблоны страниц.
					array('libs/factory/pages', 'factory_pages_402', 'admin'),
					// Компонент для работы с типами записей
					array('libs/factory/types', 'factory_types_401'),
					// Компонент для работы с метабоксами
					array('libs/factory/metaboxes', 'factory_metaboxes_401', 'admin'),
					// Компонент для создания таблиц-списков
					array('libs/factory/viewtables', 'factory_viewtables_401', 'admin')
				));
			}

			private function registerPages()
			{
				$this->registerPage('WSCR_IndexPage', WSCR_PLUGIN_DIR . '/admin/pages/index.php');
				$this->registerPage('WSCR_ErrorLogsPage', WSCR_PLUGIN_DIR . '/admin/pages/logs.php');
				$this->registerPage('WSCR_ParserPermissionsSettings', WSCR_PLUGIN_DIR . '/admin/pages/permissions-settings.php');
                $this->registerPage('WSCR_SignInPage', WSCR_PLUGIN_DIR . '/admin/pages/signin.php');
				//$this->registerPage('WSCR_SettingsPage', WSCR_PLUGIN_DIR . '/admin/pages/settings.php');
                $this->registerPage('WSCR_HelpPage', WSCR_PLUGIN_DIR . '/admin/pages/help.php');
			}

			private function registerTypes()
			{
				$this->registerType('WSC_TasksItemType', WSCR_PLUGIN_DIR . '/admin/pages/index.php');
			}

			private function adminScripts()
			{

				require_once(WSCR_PLUGIN_DIR . '/admin/activation.php');
				$this->registerActivation('WSC_Activation');
				
				// Действия исполняемые при активации плагина
				//require_once(WSCR_PLUGIN_DIR . '/plugins/activation.php');
				// Класс таблицы-списка для заданий парсера, связан с типами записей wbcr-tasks
				require_once(WSCR_PLUGIN_DIR . '/admin/includes/class.tasks.viewtable.php');
				// Регистрация типа записей для заданий парсера
				require_once(WSCR_PLUGIN_DIR . '/admin/types/tasks-post-types.php');
				
				// Подлючаем страницы с обработчиками AJAX запросов
				// ----
				
				// Информация о ссылках, собранных со стены страницы в фейсбук
				require_once(WSCR_PLUGIN_DIR . '/admin/ajax/get-sites.php');
				// Получает контент страницы, для инстурмента по выборке контента
				require_once(WSCR_PLUGIN_DIR . '/admin/ajax/get-site-content.php');
				// Быстрое удаление записи в обход корзины
				require_once(WSCR_PLUGIN_DIR . '/admin/ajax/remove-post.php');
				//require_once(WSCR_PLUGIN_DIR . '/admin/ajax/get-fb-pages.php');


				
				require_once(WSCR_PLUGIN_DIR . '/admin/boot.php');

                require_once(WSCR_PLUGIN_DIR . '/admin/includes/class.extract-async-post.php');

				$this->registerTypes();
				$this->registerPages();
			}
			
			private function globalScripts()
			{
				//require_once(WSCR_PLUGIN_DIR . '/includes/classes/class.configurate-hide-login-page.php');
				//new WSCR_ConfigHideLoginPage(self::$app);
			}

			static function registrationHook($plugin, $network){
			    if($plugin == WSCR_PLUGIN_BASE and (!defined('WSC_'.'KI') or WSC_KI == false)){
                    wp_safe_redirect('/wp-admin/edit.php?post_type=wbcr-scrapes&page=signin-wbcr_scraper');
                    exit();
                }
            }
		}
	}