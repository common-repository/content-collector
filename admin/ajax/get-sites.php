<?php
	/**
	 * Получает информацию о ссылках на стене facebook
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 24.11.2017, Webcraftic
	 * @version 1.0
	 */

	/**
	 * Returns the lists available for the current subscription service.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function wbcr_scrapes_get_sites_url()
	{

		$fb_page_slug = isset($_REQUEST['facebook_group_id'])
			? sanitize_text_field($_REQUEST['facebook_group_id'])
			: null;

		$task_id = isset($_REQUEST['task_id'])
			? (int)$_REQUEST['task_id']
			: 0;

		if( !empty($task_id) ) {
			$cached_items = get_transient('wbcr_get_sites_for_task_' . $task_id);

			if( !empty($cached_items) ) {
				echo json_encode(array(
					'items' => $cached_items
				));
				exit;
			}
		}

		if( empty($fb_page_slug) ) {
			echo json_encode(array(
				'error' => 'Empty facebook page id'
			));
			exit;
		}

		$args = array(
			'headers' => array(
				'Accept' => 'application/json',
			),
			'method' => 'POST',
			'timeout' => 20,
            'sslverify' => false,
			'body' => array(
				'fb_page_slug' => $fb_page_slug
			)
		);
        if(defined('WSC_KI')) $args['body']['ki'] = WSC_KI;

		$request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v1/parser/scrape-facebook-links', $args);

		if( is_wp_error($request) ) {
			echo json_encode(array(
				'error' => 'Facebook connection error ' . $request->get_error_message()
			));
			exit;
		}
		$body = wp_remote_retrieve_body($request);
		$body = trim($body);

		if( empty($body) ) {
			echo json_encode(array(
				'error' => 'Facebook feed body is empty ' . $request->get_error_message()
			));
			exit;
		}

		$links = @json_decode($body);

		if( !empty($links->error) ) {
			echo json_encode(array(
				'error' => $links->error
			));
			exit;
		}

		$items = array();
		if( !empty($links) && is_array($links) ) {
			foreach($links as $url) {
				$items[] = array('value' => $url->value, 'title' => $url->title);
			}
		}

		if( !empty($items) && !empty($task_id) ) {
			set_transient('wbcr_get_sites_for_task_' . $task_id, $items, HOUR_IN_SECONDS);
		}

		echo json_encode(array(
			'items' => $items
		));
		exit;
	}

	add_action('wp_ajax_wbcr_scrapes_get_sites', 'wbcr_scrapes_get_sites_url');