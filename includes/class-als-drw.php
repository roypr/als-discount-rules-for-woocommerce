<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://#
 * @since      1.0.0
 *
 * @package    ALS_DRW
 * @subpackage ALS_DRW/includes
 */

if (!class_exists('ALS_DRW')) {
    class ALS_DRW {

        protected $plugin_name;

        public function __construct()
        {
            $this->plugin_name = 'als_drw';

            $this->load_dependencies();
        }

        public function init(){
            add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

            $settings = new ALS_DRW_Settings($this->plugin_name);
        }

        private function load_dependencies() {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-als-drw-settings.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-als-drw-front.php';
        }

        /**
         * Load the plugin text domain for translation.
         *
         * @since    1.0.0
         */
        public function load_plugin_textdomain() {

            load_plugin_textdomain(
                'als-drw',
                false,
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
            );
        }
    }
}
