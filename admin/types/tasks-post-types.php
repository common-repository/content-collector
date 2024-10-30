<?php

	/**
	 * Opt-In Panda Type
	 * Declaration for custom post type of Social Locler.
	 * @link http://codex.wordpress.org/Post_Types
	 */
	class WSC_TasksItemType extends Wbcr_FactoryTypes401_Type {

		/**
		 * Custom post name.
		 * @var string
		 */
		//public $name = 'wbcr-scrapes';

		/**
		 * Template that defines a set of type options.
		 * Allowed values: public, private, internal.
		 * @var string
		 */
		public $template = 'private';

		/**
		 * Capabilities for roles that have access to manage the type.
		 * @link http://codex.wordpress.org/Roles_and_Capabilities
		 * @var array
		 */
		public $capabilities = array('administrator');

		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			$this->name = WSCR_SCRAPES_POST_TYPE;
			$this->plural_title = __('WP Parser', 'wbcr-scrapes');
			$this->singular_title = __('WP Parser', 'wbcr-scrapes');

			parent::__construct($plugin);
		}

		//public function actionAddMetaboxs()
		//{
		//$dsd = 'gfdg';
		//remove_meta_box('submitdiv', $this->name, 'side');
		//}

		/**
		 * Type configurator.
		 */
		public function configure()
		{

			$plural_name = $this->plural_title;
			$singular_name = $this->singular_title;

			$labels = array(
				'singular_name' => $this->singular_title,
				'name' => $this->plural_title,
				'all_items' => sprintf(__('Задания', 'wbcr-scrapes'), $plural_name),
				'add_new' => sprintf(__('+ Новое задание', 'wbcr-scrapes'), $singular_name),
				'add_new_item' => sprintf(__('Добавить новое', 'wbcr-scrapes'), $singular_name),
				'edit' => sprintf(__('Редактировать', 'wbcr-scrapes')),
				'edit_item' => sprintf(__('Редактировать задание', 'wbcr-scrapes'), $singular_name),
				'new_item' => sprintf(__('Новое задание', 'wbcr-scrapes'), $singular_name),
				'view' => sprintf(__('Просмотр', 'factory')),
				'view_item' => sprintf(__('Просмотре задания', 'wbcr-scrapes'), $singular_name),
				'search_items' => sprintf(__('Поиск заданий', 'wbcr-scrapes'), $plural_name),
				'not_found' => sprintf(__('Заданий не найдено', 'wbcr-scrapes'), $plural_name),
				'not_found_in_trash' => sprintf(__('В корзине нет заданий', 'wbcr-scrapes'), $plural_name),
				'parent' => sprintf(__('Родительское задание', 'wbcr-scrapes'), $plural_name)
			);

			$this->options['labels'] = apply_filters('wbcr_scrapes_items_lables', $labels);

			if(defined('WBCR_DISABLE_ADD_TASKS')){
                $this->options['capabilities']['create_posts'] = false;
            }


			//$this->options['show_in_menu'] = 'index-' . $wbcr_scrapes->pluginName;

			/**
			 * Menu
			 */

			//$this->menu->title = 'fsdfsdf';
			//$this->menu->icon = '\f100';

			//$this->menu->title = 'Tasks';

			/**
			 * View table
			 */

			$this->view_table = 'WSC_TasksViewTable';

			/**
			 * Scripts & styles
			 */

			$this->scripts->request(array('jquery', 'jquery-effects-highlight', 'jquery-effects-slide'));

			$this->scripts->request(array(
				'bootstrap.transition',
				'bootstrap.datepicker',
				'bootstrap.tab',
				'holder.more-link',
				'control.checkbox',
				'control.dropdown',
				'control.list',
				'bootstrap.modal',
				'plugin.moment-with-locales',
				'bootstrap.datetimepicker',
				'control.multiple-textbox'
			), 'bootstrap');

			$this->styles->request(array(
				'bootstrap.core',
				'bootstrap.datepicker',
				'bootstrap.form-group',
				'bootstrap.form-metabox',
				'bootstrap.tab',
				'bootstrap.wp-editor',
				'bootstrap.separator',
				'bootstrap.modal',
				'control.checkbox',
				'control.dropdown',
				'control.list',
				'holder.more-link',
				'bootstrap.datetimepicker',
				'control.multiple-textbox'
			), 'bootstrap');

			$this->styles->add(WSCR_PLUGIN_URL . '/assets/css/general.css');
			$this->scripts->add(WSCR_PLUGIN_URL . '/assets/js/jquery.base64.js');
			$this->scripts->add(WSCR_PLUGIN_URL . '/assets/js/general.js');
			/*$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/filters.010000.js');
			$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/libs/json2.js');
			$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/preview.010000.js');
			$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/item-edit.010009.js')
				->request('jquery-ui-sortable');
				$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/libs/jquery.qtip.min.js');
				$this->styles->add(OPANDA_BIZPANDA_URL . '/assets/admin/css/libs/jquery.qtip.min.css');
			
*/
		}
	}


	//Wbcr_FactoryTypes401::register('WSC_TasksItemType', $wbcr_scrapes);