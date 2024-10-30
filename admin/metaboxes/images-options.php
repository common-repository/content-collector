<?php
	
	class WSC_ImagesOptionsMetaBox extends Wbcr_FactoryMetaboxes401_FormMetabox {
		
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
			
			$this->title = __('Настройки получаемых изображений', 'wbcr-scrapes');
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

		public function html()
		{
			parent::html();
			foreach($this->errors as $error) {
				echo $error;
			}
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
				'type' => 'dropdown',
				'way' => 'buttons',
				'name' => 'post_feature_image',
				'data' => array(
					array(
						'source_code',
						__('Содержание статьи', 'wbcr-scrapes')
					),
					array('facebook_feed', __('Запись в Facebook', 'wbcr-scrapes'))
				),
				'events' => array(
					'all_urls' => array(
						'hide' => '.factory-control-nesting_level'
					),
					'only_current_page' => array(
						'hide' => '.factory-control-nesting_level'
					),
					'custom' => array(
						'show' => '.factory-control-nesting_level'
					)
				),
				'title' => __('Превью записи', 'wbcr-scrapes'),
				'hint' => __('Выберите режим, откуда извлекать превью записи.', 'wbcr-scrapes'),
				'default' => 'facebook_feed'
			);*/

			/*$items[] = array(
				'type' => 'dropdown',
				'way' => 'buttons',
				'name' => 'post_feature_type',
				'data' => array(
					array('default', __('По умолчанию', 'wbcr-scrapes')),
					array('in_content', __('Впереди контента', 'wbcr-scrapes'))
				),
				'title' => __('Тип превью', 'wbcr-scrapes'),
				'hint' => __('Выберите режим, как устанавливать картинку превью. Вы можете установить картинку внутри контента или же по умолчанию привязать к записи.', 'wbcr-scrapes'),
				'default' => 'default'
			);*/

			$items[] = array(
				'type' => 'hidden',
				'way' => 'buttons',
				'name' => 'upload_images_to_local_storage',
				'title' => __('Загружать изображения?', 'wbcr-scrapes'),
				'hint' => __('Если Вкл., плагин будет загружать все собранные изображения на ваш сервер.', 'wbcr-scrapes'),
				'default' => true
			);

			$items[] = array(
				'type' => 'textbox',
				'name' => 'min_width_updaload_images',
				'title' => __('Минимальная ширина загружаемых изображений', 'wbcr-scrapes'),
				'hint' => __('Установите ограничение на ширину получаемых изображений. По умолчанию 300', 'wbcr-scrapes'),
				'default' => 300
			);

			$items[] = array(
				'type' => 'textbox',
				'name' => 'min_height_updaload_images',
				'title' => __('Минимальная высота загружаемых изображений', 'wbcr-scrapes'),
				'hint' => __('Установите ограничение на высоту получаемых изображений. По умолчанию 100', 'wbcr-scrapes'),
				'default' => 100
			);

			$form->add($items);
		}
	}
	
	Wbcr_FactoryMetaboxes401::register('WSC_ImagesOptionsMetaBox', WSCR_Plugin::app());

	