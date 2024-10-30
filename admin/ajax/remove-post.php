<?php
	/**
	 * Remove post
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 08.11.2017, Webcraftic
	 * @version 1.0
	 */

	add_action("wp_ajax_" . "scrapes_ajax_remove_post", "wbcr_scrapes_ajax_remove_post");

	function wbcr_scrapes_ajax_remove_post()
	{
		$post_id = isset($_POST['post_id'])
			? (int)$_POST['post_id']
			: 0;

		if( empty($post_id) ) {
			echo json_encode(array('error' => 'Attribute post_id is empty'));
			exit;
		}

		// Remove post attachments
		WSCR_Helper::deletePostAttachments($post_id);

		if( wp_delete_post($post_id, true) ) {
			echo json_encode(array('success' => true));
			exit;
		}

		echo json_encode(array('error' => 'Can not delete the record.'));
		exit;
	}
