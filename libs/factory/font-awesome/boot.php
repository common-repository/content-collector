<?php
	/**
	 * Factory Plugin
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package core
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	// module provides function only for the admin area
	if( !is_admin() ) {
		return;
	}

	if( defined('FACTORY_FONTAWESOME_321_LOADED') ) {
		return;
	}

	define('FACTORY_FONTAWESOME_321_LOADED', true);

	define('FACTORY_FONTAWESOME_321_DIR', dirname(__FILE__));
	define('FACTORY_FONTAWESOME_321_URL', plugins_url(null, __FILE__));

	if( !function_exists('wbcr_factory_fontawesome_321_load_assets') ) {
		function wbcr_factory_fontawesome_321_load_assets()
		{
			wp_enqueue_style('factory-fontawesome-321', '//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		}

		add_action('admin_enqueue_scripts', 'wbcr_factory_fontawesome_321_load_assets');
	}