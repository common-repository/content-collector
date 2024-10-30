<?php
	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}
	
	class WSCR_ErrorLogsPage extends Wbcr_FactoryPages402_AdminPage {

		/**
		 * The id of the page in the admin menu.
		 *
		 * Mainly used to navigate between pages.
		 * @see FactoryPages402_AdminPage
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $id = "error-logs";

		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		public function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			$this->menu_post_type = WSCR_SCRAPES_POST_TYPE;
			$this->menu_title = __('Лог ошибок', 'wbcr-scrapes');
			$this->menu_icon = "\f226";
			$this->capabilitiy = "manage_options";

			parent::__construct($plugin);

			$this->plugin = $plugin;
		}

		public function assets($scripts, $styles)
		{
			//$this->styles->add(WSCR_PLUGIN_URL . '/admin/assets/css/feed.css');
			//$this->scripts->add(WSCR_PLUGIN_URL . '/admin/assets/js/feed.js');
		}

		public function indexAction()
		{
			?>
			<h2><?=__("Лог ошибок", "wbcr-scrapes");?></h2>
			<textarea style="margin-top:20px; width:100%; height:700px;"><?= file_get_contents(WSCR_PLUGIN_DIR . '/logs/logs.txt'); ?></textarea>
			<p><a href="<?= $this->getActionUrl('clear-log') ?>" class="button button-default"><?=__("Очистить лог", "wbcr-scrapes");?></a></p>
		<?php
		}

		public function clearLogAction()
		{
			$filepath = WBCR_SCRAPES_PLUGIN_DIR . '/logs/logs.txt';
			if( file_exists($filepath) ) {
				unlink($filepath);
			}
			WSCR_Helper::writeLog('Plugin activated...' . PHP_EOL . WSCR_Helper::systemInfo());

			$this->redirectToAction('index');
		}
	}
