<?php
	/**
	 * Get site content for target
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 08.11.2017, Webcraftic
	 * @version 1.0
	 */

	add_action("wp_ajax_" . "scrapes_ajax_url_load", "wbcr_scrapes_ajax_url_load");

	function wbcr_scrapes_ajax_url_load()
	{
		if( isset($_GET['address']) ) {
			$get_address = esc_url($_GET['address']);

			$args = array(
				'headers' => array(
					'Accept' => 'application/json',
				),
				'method' => 'POST',
				'timeout' => 20,
                'sslverify' => false,
				'body' => array(
					'site_url' => $get_address
				)
			);
            if(defined('WSC_KI')) $args['body']['ki'] = WSC_KI;

			$request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v1/parser/read-site-content', $args);

			if( is_wp_error($request) ) {
				wp_die($request->get_error_message());
			}

			echo wp_remote_retrieve_body($request);

			wp_die();
		}
	}

