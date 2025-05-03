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
    /**
     * Main class for the ALS_DRW plugin
     * 
     * This class is responsible for loading dependencies, initializing the plugin,
     * and handling plugin-wide functionality such as text domain loading.
     */
    class ALS_DRW {

        // Declare protected variable to store plugin name
        protected $plugin_name;

        /**
         * Constructor for the ALS_DRW class
         * 
         * Initializes the plugin by setting the plugin name and loading dependencies.
         */
        public function __construct() {
            $this->plugin_name = 'als_drw'; // Set the plugin name

            // Load the necessary dependencies for the plugin
            $this->load_dependencies();
        }

        /**
         * Initializes the plugin by hooking functions to WordPress actions
         * 
         * This function hooks into WordPress' 'plugins_loaded' action to load
         * the plugin text domain and creates instances of the settings and front-end
         * classes for the plugin.
         */
        public function init() {
            // Hook function to load text domain for translations
            add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

            // Create instance of ALS_DRW_Settings class to handle admin settings
            $settings = new ALS_DRW_Settings($this->plugin_name);

            // Create instance of ALS_DRW_Front class for front-end functionality
            $front = new ALS_DRW_Front($this->plugin_name);
        }

        /**
         * Loads necessary dependencies for the plugin
         * 
         * This function includes the PHP files for the settings and front-end classes
         * which are used throughout the plugin.
         */
        private function load_dependencies() {
            // Include settings class
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-als-drw-settings.php';

            // Include front-end functionality class
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-als-drw-front.php';
        }

        /**
         * Load the plugin text domain for translation
         * 
         * This function makes the plugin translatable by loading the language files.
         * It is hooked to 'plugins_loaded' to ensure it's executed when all plugins
         * are fully loaded.
         *
         * @since    1.0.0
         */
        public function load_plugin_textdomain() {
            // Load translation files for the plugin
            load_plugin_textdomain(
                'als-drw', // Text domain
                false, // Load from the default location
                dirname(dirname(plugin_basename(__FILE__))) . '/languages/' // Path to languages folder
            );
        }
    }
}
