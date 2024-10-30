<?php
	/**
	 * Factory Types
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package factory-types
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( defined('FACTORY_TYPES_401_LOADED') ) {
		return;
	}

	define('FACTORY_TYPES_401_LOADED', true);

	define('FACTORY_TYPES_401_DIR', dirname(__FILE__));
	define('FACTORY_TYPES_401_URL', plugins_url(null, __FILE__));

	load_plugin_textdomain('wbcr_factory_types_401', false, dirname(plugin_basename(__FILE__)) . '/langs');

	// sets version of admin interface
	if( is_admin() ) {
		if( !defined('FACTORY_FLAT_ADMIN') ) {
			define('FACTORY_FLAT_ADMIN', true);
		}
	}

	#comp merge
	require(FACTORY_TYPES_401_DIR . '/types.php');
	require(FACTORY_TYPES_401_DIR . '/type.class.php');
	require(FACTORY_TYPES_401_DIR . '/includes/type-menu.class.php');
	#endcomp