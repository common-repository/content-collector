<?php
	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}
	
	class WSCR_IndexPage extends Wbcr_FactoryPages402_AdminPage {

		/**
		 * The id of the page in the admin menu.
		 *
		 * Mainly used to navigate between pages.
		 * @see FactoryPages402_AdminPage
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $id = "index";

		public $internal = true;

		private $meta_options;

		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		public function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			$this->menu_title = __('Процесс парсинга', 'wbcr-scrapes');
			//$this->menuIcon = "\f226";
			$this->menu_post_type = WSCR_SCRAPES_POST_TYPE;
			$this->capabilitiy = "read_wbcr-scrapes";

			parent::__construct($plugin);

			$this->plugin = $plugin;
		}

		public function assets($scripts, $styles)
		{
			$this->styles->add(WSCR_PLUGIN_URL . '/assets/css/feed.css');
			$this->scripts->add(WSCR_PLUGIN_URL . '/assets/js/feed.js');
			$this->scripts->add(WSCR_PLUGIN_URL . '/assets/js/tasks.js');
		}

		protected function getOption($task_id, $option_name, $default = null)
		{
			$task_id = (int)$task_id;
			$prefix = 'wbcr_scrapes_';

			if( empty($this->meta_options) ) {
				$meta_vals = get_post_meta($task_id, '', true);

				foreach($meta_vals as $name => $val) {
					$this->meta_options[$name] = $val[0];
				}
			}

			return isset($this->meta_options[$prefix . $option_name])
				? $this->meta_options[$prefix . $option_name]
				: $default;
		}

		protected function updateOption($task_id, $option_name, $option_value)
		{
			return update_post_meta($task_id, 'wbcr_scrapes_' . $option_name, $option_value);
		}

		protected function removeOption($task_id, $option_name)
		{
			return delete_post_meta($task_id, 'wbcr_scrapes_' . $option_name);
		}

		public function indexAction()
		{

			$task_id = $this->request->get('task_id');

            $source_channel = WSCR_Helper::getMetaOption($task_id, 'source_channel');

            $options = [];

            if( $source_channel == 'facebook_feed' ) {
                require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-facebook-posts.php';
                $scrape_posts = new WSC_ScrapeFacebookPosts($task_id);
            } else if( $source_channel == 'site_stream' ) {
                require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-site-stream-posts.php';
                $scrape_posts = new WSC_ScrapeSiteSreamPosts($task_id);
            } else if( $source_channel == 'default' ) {
                require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-posts-by-links.php';
                $scrape_posts = new WSC_ScrapePostsByLinks($task_id);
            } else {
                throw new Exception('Не известная ошибка!');
            }

            $options = $scrape_posts->getOptions();
            $options['parser_scenario'] = $source_channel;


            $ki = '';
            if(defined('WSC_KI')){
                $ki =  WSC_KI;
            }

            $fs = Freemius::get_instance_by_file('wp-parser/wpparser.php');
            $sites = $fs->get_site();

            $options['install_id'] = $sites->id;
            $options['install_pk'] = $sites->public_key;



            $upload_images_to_local_storage = WSCR_Helper::getMetaOption($task_id, 'upload_images_to_local_storage', true);
            $min_width = WSCR_Helper::getMetaOption($this->task_id, 'min_width_updaload_images', 300);
            $min_height = WSCR_Helper::getMetaOption($this->task_id, 'min_height_updaload_images', 100);

            $options['upload_images_to_local_storage'] = $upload_images_to_local_storage;
            $options['min_width'] = $min_width;
            $options['min_height'] = $min_height;


			?>
            <script type="text/template" id="wpp-result-tpl">
                <div class="wbcr-scrapes-feed-item">
                    <div class="wbcr-scrapes-meta"><a href="#" class="wbcr-scrapes-remove-feed-item" data-post-id="{post_id}"><?=__('Удалить', 'wbcr-scrapes');?></a></div>
                    <figure class="wbcr-scrapes-preview {show_image}"><img src="{image_src}" width="500"></figure>
                    <h3 class="wbcr-scrapes-title"><a href="{origin_url}">{title}</a></h3>
                    <div class="wbcr-scrapes-description">{description}</div>
                    <div class="wbcr-scrapes-more-buttons"><a href="{origin_url}" target="_blank"><?=__('Посмотреть источник', 'wbcr-scrapes');?></a> | <a href="{post_url}" target="_blank"><?=__('Перейти к записи', 'wbcr-scrapes');?></a></div>
                </div>
            </script>
            <script type="text/template" id="wpp-message-tpl">
               <div class="wpp-message">
                   <div class="wpp-message-title">{title}</div>
                   <div class="wpp-message-message">{message}</div>
               </div>
            </script>
            <script type="text/template" id="wpp-error-result-tpl">
                <div class="wbcr-scrapes-feed-item error">
                    <h3 class="wbcr-scrapes-title"><a href="{origin_url}">{title}</a></h3>
                    <div class="wbcr-scrapes-description">{description}</div>
                    <div class="wbcr-scrapes-more-buttons"><a href="{origin_url}" target="_blank"><?=__('Посмотреть источник', 'wbcr-scrapes');?></a></div>
                </div>
            </script>
            <script type="text/template" id="wpp-error-limit-tpl">
                <div class="wbcr-scrapes-limit-reached">
                    <div><?=__("Исчерпаны дневные лимиты", 'wbcr-scrapes');?></div>
                    <a href="/wp-admin/edit.php?post_type=wbcr-scrapes&page=wpparser-pricing"><?=__("Показать тарифы", 'wbcr-scrapes');?></a>
                </div>
            </script>
            <script type="text/template" id="wpp-error-auth-tpl">
                <div class="wbcr-scrapes-auth">
                    <p><?=__("Сбой авторизации<br>Попробуйте переактивировать плагин или нажать синхронизовать лицензию в личном кабинете", 'wbcr-scrapes')?></p>
                </div>
            </script>
            <script type="text/template" id="wpp-number-posts-tpl">
                <?=__('Загружено {posts_length} постов', 'wbcr-scrapes');?>
            </script>

            <div id="WBCR">
                <div class="factory-bootstrap-401 factory-fontawesome-321">
                    <div class="wbcr-scrapes-feed">
                        <div class="wpp-complete-message js-wpp-complete-header"><?=__('Готово!', 'wbcr-scrapes');?></div>
                        <div class="wpp-loading-message js-wpp-loading-header">
                            <?=__('Загрузка..', 'wbcr-scrapes');?>
                            <div class="wpp-loading-message-small"><?=__('Пожалуйста, подождите', 'wbcr-scrapes')?></div>
                        </div>
                        <div class="wpp-error-message js-wpp-error-header">
                            <?=__('Ошибка', 'wbcr-scrapes');?>
                        </div>
                        <div class="wpp-message-box js-wpp-message-box"></div>

                        <div class="wpp-progressbar js-wpp-progressbar">
                            <div class="wpp-progressbar-number js-wpp-progressbar-number"></div>
                            <div class="wpp-progressbar-fill js-wpp-progressbar-fill"></div>
                        </div>

                        <div class="wpp-results js-wpp-results">

                    </div>
                </div>
            </div>
            <script>
                window.taskOptions= {ki:"<?=$ki?>", task_id:<?=$task_id?>, task_options:<?=json_encode($options)?>, options:<?=json_encode([])?>};
            </script>
            <?


		}
	}

	