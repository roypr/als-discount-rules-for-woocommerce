<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://#
 * @since             1.0.0
 * @package           ALS_DRW
 *
 * @wordpress-plugin
 * Plugin Name:          Business Discount Rules - WooCommerce
 * Plugin URI:           https://#
 * Description:          A plugin to apply business discount rules in WooCommerce based on conditions.
 * Version:              1.0.0
 * Author:               Roy Parthapratim
 * Author URI:           https://#/
 * License:              GPL-2.0+
 * License URI:          http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:          als-drw
 * Domain Path:          /languages
 * Requires PHP: 	     7.4
 * Requires at least:    5.6
 * Tested up to: 	     6.6
 * Requires Plugins:     woocommerce
 * WC requires at least: 3.6.0
 * WC tested up to:      9.6.2
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('ALS_DRW_VERSION', '1.0.0');

//Check if woocommerce is activated
if (! function_exists('als_drw_is_woocommerce_activated')) {
    /**
     * See if WooCommerce is active.
     */
    function als_drw_is_woocommerce_activated() {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            return true;
        } else {
            return false;
        }
    }
}

if( !function_exists('als_drw_init')){

    //Initiate plugin
    function als_drw_init(){
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-als-drw.php';

        $plugin = new ALS_DRW();
        $plugin->init();

    }

    //Don't run if woocommerce is not activated
    if(als_drw_is_woocommerce_activated()){

        add_action(
            'before_woocommerce_init',
            function () {
                //Decalre woocommerce features support
                FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
                FeaturesUtil::declare_compatibility('remote_logging', __FILE__, true);
            }
        );

        als_drw_init();
    }
    
}

