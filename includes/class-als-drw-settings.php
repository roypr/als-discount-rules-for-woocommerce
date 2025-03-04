<?php

/**
 * The file that defines the admin settings functinality of the plugin
 *
 * A class definition that includes attributes and functions
 * for admin settings
 *
 * @link       https://#
 * @since      1.0.0
 *
 * @package    ALS_DRW
 * @subpackage ALS_DRW/includes
 */

if( !class_exists('ALS_DRW_Settings')){
    class ALS_DRW_Settings{

        private $plugin_name;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;
            
            add_action('admin_menu', [$this, 'add_menu_page']);
        }
    
        public function add_menu_page(){
            add_submenu_page(
                'woocommerce',
                __('Business Discount Rules - WooCommerce', 'als-drw'),
                __('Business Discount', 'als-drw'),
                'manage_options',
                'als-drw',
                [$this, 'render_menu_page']
            );
        }

        public function render_menu_page(){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'views/settings.php';
        }
    }
}