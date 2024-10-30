<?php
	/**
	 * Contains functions, hooks and classes required for activating the plugin.
	 *
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 2017, OnePress Ltd
	 *
	 * @since 1.0.0
	 * @package bizpand-popups-addon
	 */

	/**
	 * The activator class performing all the required actions on activation.
	 *
	 * @see Wbcr_Factory401_Activator
	 * @since 1.0.0
	 */
	class WSC_Activation extends Wbcr_Factory401_Activator {

		/**
		 * Runs activation actions.
		 *
		 * @since 1.0.1
		 */
		public function activate()
		{
			// Включаем крон задачу
			/*$all_tasks = get_posts(array(
				'numberposts' => -1,
				'post_type' => WSCR_SCRAPES_POST_TYPE,
				'post_status' => 'publish'
			));*/

			/*$time_offset = 0;
			foreach($all_tasks as $task) {
				$time_offset += 180;
				// Регистрируем задания для парсера
				wbcr_scrapes_handle_cron_job($task->ID, $time_offset);
			}*/
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			// Создаем таблицу для очереди загрузки изображений
			$autoposter_shedules_table = "
				CREATE TABLE {$wpdb->prefix}wbcr_scrapes_upload_shedules (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  post_id int(11) DEFAULT NULL,
					  task_id int(11) DEFAULT NULL,
					  created_at int(11) DEFAULT NULL,
					  PRIMARY KEY (id)
					)
					ENGINE = INNODB
					AUTO_INCREMENT = 1
					CHARACTER SET utf8
					COLLATE utf8_general_ci;
			";

			dbDelta($autoposter_shedules_table);

			WSCR_Helper::writeLog('Плагин активирован...' . PHP_EOL . WSCR_Helper::systemInfo() . PHP_EOL);
			WSCR_Helper::writeLog('Текущее время: ' . date('Y-m-d H:i:s'));
			WSCR_Helper::writeLog('Временная зона: ' . date_default_timezone_get());
		}

		public function deactivate()
		{
			//wbcr_scrapes_clear_all_cron_events();
			//wbcr_autoposter_clear_all_cron_events();

			WSCR_Helper::writeLog('Плагин деактивирован...');
		}
	}
