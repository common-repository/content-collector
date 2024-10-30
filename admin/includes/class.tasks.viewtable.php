<?php

	class WSC_TasksViewTable extends Wbcr_FactoryViewtables401_Viewtable {

		public function configure()
		{
			/**
			 * Columns
			 */

			$this->columns->clear();

			//$this->columns->add('stats', __('<span title="Unlocks / Impressions / Conversion">U / I / %', 'wbcr-scrapes'));
			$this->columns->add('title', __('Заголовок задания', 'wbcr-scrapes'));

			//if( !BizPanda::isSinglePlugin() ) {
			//$this->columns->add('type', __('Type', 'wbcr-scrapes'));
			///}

			$this->columns->add('site', __('Источник', 'wbcr-scrapes'));
			//$this->columns->add('facebook', __('Facebook Id', 'wbcr-scrapes'));
			$this->columns->add('actions', __('Действия', 'wbcr-scrapes'));
			$this->columns->add('autoparsing', __('Запуск по расписанию', 'wbcr-scrapes'));

			//$this->columns->add('bulk', __('Bulk Lock', 'wbcr-scrapes'));
			//$this->columns->add('visibility', __('Visibility Conditions', 'wbcr-scrapes'));

			/**
			 * Scripts & styles
			 */

			//$this->scripts->add(OPANDA_BIZPANDA_URL . '/assets/admin/js/item-view.js');
			$this->styles->add(WSCR_PLUGIN_URL . '/assets/css/general.css');
			//do_action('bizpanda_view_table_register_scripts', $this->scripts, $this->styles);
		}

		/**
		 * Column 'Title'
		 */
		public function columnTitle($post)
		{
			echo $post->post_title;
		}

		public function columnSite($post)
		{
			$source_channel = WSCR_Helper::getMetaOption($post->ID, 'source_channel');

			if( $source_channel == 'facebook_feed' ) {
				$facebook_group_id = WSCR_Helper::getMetaOption($post->ID, 'facebook_group_id', true);
				echo '<a href="https://www.facebook.com/' . $facebook_group_id . '" target="_blank">https://www.facebook.com/' . $facebook_group_id . '</a>';
			} else if( $source_channel == 'default' ) {
				echo 'Коллекция ссылок';
			} else {
				$page_scheme_url = WSCR_Helper::getMetaOption($post->ID, 'site_url');
				if( !empty($page_scheme_url) ) {
					$split_url = parse_url($page_scheme_url);
					echo '<a href="' . $split_url['scheme'] . '://' . $split_url['host'] . '" target="_blank">' . $split_url['scheme'] . '://' . $split_url['host'] . '</a>';
				}
			}
		}

		public function columnAutoparsing($post)
		{
			$autoparsing = get_post_meta($post->ID, 'wbcr_scrapes_autoscrape', true);

			$color_class = 'wbcr-green';

			if( !$autoparsing ) {
				$color_class = 'wbcr-grey';
			}

			echo '<span class="wbcr-scrapes-table-circle ' . $color_class . '"></span>';
		}

		/**
		 * Column 'Type'
		 */
		public function columnActions($post)
		{

			echo '<a href="' . admin_url('edit.php?post_type=' . WSCR_SCRAPES_POST_TYPE . '&page=index-' . WSCR_Plugin::app()
						->getPluginName() . '&task_id=' . $post->ID) . '" class="button run ol_status_complete"><i class="icon ion-play"></i>' . __('Начать парсинг', 'wbcr-scrapes') . '</a>';
		}
	}
	