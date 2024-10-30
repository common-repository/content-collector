<?php
	
	class WSC_SheduleOptionsMetaBox extends Wbcr_FactoryMetaboxes401_FormMetabox {
		
		/**
		 * A visible title of the metabox.
		 *
		 * Inherited from the class FactoryMetabox.
		 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $title;
		
		
		/**
		 * A prefix that will be used for names of input fields in the form.
		 * Inherited from the class FactoryFormMetabox.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $scope = 'wbcr_scrapes';
		
		/**
		 * The priority within the context where the boxes should show ('high', 'core', 'default' or 'low').
		 *
		 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
		 * Inherited from the class FactoryMetabox.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $priority = 'core';
		
		public $cssClass = 'factory-bootstrap-401 factory-fontawesome-321';

		protected $errors = array();
		protected $source_channel;
		
		public function __construct($plugin)
		{
			parent::__construct($plugin);
			
			$this->title = __('Настройки запуска по расписанию', 'wbcr-scrapes');
		}

		public function html()
		{
			parent::html();
			foreach($this->errors as $error) {
				echo $error;
			}
		}

		public function save($post_id)
		{
			if( !$this->checkErrors() ) {
				parent::save($post_id);
			}
		}

		public function checkErrors()
		{
			global $post;

			$this->source_channel = WSCR_Helper::getMetaOption($post->ID, 'source_channel', 'facebook_feed');

			$is_error = false;
			if( !empty($post) ) {
				if( $this->source_channel == 'facebook_feed' ) {
					$facebook_group_id = WSCR_Helper::getMetaOption($post->ID, 'facebook_group_id');
					if( empty($facebook_group_id) ) {
						$is_error = true;
					}
				}
				if( $this->source_channel == 'site_stream' ) {
					$paginate_url = esc_url(WSCR_Helper::getMetaOption($post->ID, 'paginate_url'));
					if( empty($paginate_url) ) {
						$is_error = true;
					}
				}
				if( $this->source_channel == 'default' ) {
					$collected_links = esc_url(WSCR_Helper::getMetaOption($post->ID, 'collected_links'));
					if( empty($collected_links) ) {
						$is_error = true;
					}
				}
			}

			if( empty($post) || empty($this->source_channel) || $is_error ) {
				$this->errors[] = __('Пожалуйста, завершите настройки первого шага.', "wbcr-scrapes");
			}

			if( !empty($this->errors) ) {
				return true;
			}

			return false;
		}

		/**
		 * Configures a form that will be inside the metabox.
		 *
		 * @see Wbcr_FactoryMetaboxes401_FormMetabox
		 * @since 1.0.0
		 *
		 * @param FactoryForms401_Form $form A form object to configure.
		 * @return void
		 */
		public function form($form)
		{
			if( $this->checkErrors() ) {
				return;
			}
			/*$items[] = array(
				'type' => 'hidden',
				'name' => 'item',
				'default' => isset($_GET['wbcr_scrapes'])
					? $_GET['wbcr_scrapes']
					: null
			);*/

			/*$items[] = array(
				'type' => 'html',
				'html' => array($this, 'printScripts')
			);*/

			$items[] = array(
				'type' => 'checkbox',
				'way' => 'buttons',
				'name' => 'autoscrape',
				'title' => __('Запускать парсинг по расписанию?', 'wbcr-scrapes'),
				'hint' => __('Включить/выключить запуск парсинга автоматически по рассписаю.', 'wbcr-scrapes'),
				'eventsOn' => array(
					'show' => '#wbcr-scrapes-autoscrape-options'
				),
				'eventsOff' => array(
					'hide' => '#wbcr-scrapes-autoscrape-options'
				),
				'default' => false
			);

			$group_items[] = array(
				'type' => 'dropdown',
				'way' => 'buttons',
				'name' => 'shedule_time',
				'data' => array(
					array('hourly', __('Hourly', 'wbcr-scrapes')),
					array('daily', __('Daily', 'wbcr-scrapes')),
					array('weekly', __('Weekly', 'wbcr-scrapes')),
					array('monthly', __('Monthly', 'wbcr-scrapes'))
				),
				'default' => 'weekly',
				'events' => array(
					'hourly' => array(
						'show' => '.factory-control-shedule_hourly_time'
					),
					'daily' => array(
						'hide' => '.factory-control-shedule_hourly_time'
					),
					'weekly' => array(
						'hide' => '.factory-control-shedule_hourly_time'
					),
					'monthly' => array(
						'hide' => '.factory-control-shedule_hourly_time'
					)
				),
				'title' => __('Выполнять задание через', 'wbcr-scrapes'),
				'hint' => __('Выберите период, через какой промежуток времени повторять выполнение задания.', 'wbcr-scrapes')
			);

			$group_items[] = array(
				'type' => 'dropdown',
				'name' => 'shedule_hourly_time',
				//'title' => __('С интервалом', 'wbcr-scrapes'),
				'data' => array(
					array('1_hour', __('1 час', 'wbcr-scrapes')),
					array('3_hours', __('3 часа', 'wbcr-scrapes')),
					array('5_hours', __('5 часов', 'wbcr-scrapes')),
					array('7_hours', __('7 часов', 'wbcr-scrapes')),
					array('10_hours', __('10 часов', 'wbcr-scrapes')),
					array('15_hours', __('15 часов', 'wbcr-scrapes')),
					array('20_hours', __('20 часов', 'wbcr-scrapes'))
				),
				'hint' => __('Включить/выключить запуск парсинга автоматически по рассписаю.', 'wbcr-scrapes'),
				'default' => '1_hour'
			);

			$items[] = array(
				'type' => 'div',
				'id' => 'wbcr-scrapes-autoscrape-options',
				'items' => $group_items
			);

			$form->add($items);
		}


		public function afterSavingForm($task_id)
		{
			if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
				return;
			}

			wbcr_scrapes_handle_cron_job($task_id);
		}
	}
	
	Wbcr_FactoryMetaboxes401::register('WSC_SheduleOptionsMetaBox', WSCR_Plugin::app());

	