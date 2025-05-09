<?php

/**
 * The file that defines the admin settings functionality of the plugin
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

if (!class_exists('ALS_DRW_Settings')) {
    /**
     * Class ALS_DRW_Settings
     *
     * Handles the admin settings page and functionalities for the plugin.
     * This class includes methods for adding menu pages, registering settings,
     * and enqueuing scripts and styles.
     */
    class ALS_DRW_Settings {

        // Declare private variables for plugin name and data structures
        private $plugin_name;
        private $data_structure_product;
        private $data_structure_category;

        /**
         * Constructor for the ALS_DRW_Settings class
         * 
         * @param string $plugin_name The name of the plugin
         */
        public function __construct($plugin_name) {
            $this->plugin_name = $plugin_name;

            // Define data structure for products
            $this->data_structure_product = [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => [
                            'type' => 'integer'
                        ],
                        'parent_id' => [
                            'type' => 'integer'
                        ],
                        'product_type' => [
                            'type' => 'string'
                        ],
                        'label' => [
                            'type' => 'string'
                        ],
                    ],
                    'required' => ['label', 'value']
                ]
            ];

            // Define data structure for categories
            $this->data_structure_category = [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => [
                            'type' => 'integer'
                        ],
                        'label' => [
                            'type' => 'string'
                        ],
                    ],
                    'required' => ['label', 'value']
                ]
            ];
            
            // Hook functions to WordPress actions
            add_action('admin_menu', [$this, 'add_menu_page']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('init', [$this, 'register_settings']);
            add_filter('plugin_action_links_' . plugin_basename(dirname(dirname(__FILE__))) . '/als-discount-rules-for-woocommerce.php', [$this, 'add_menu_link']);
        }

        /**
         * Registers plugin settings in WordPress
         * 
         * Registers settings using WordPress' register_setting function
         * and defines schema and default values for the settings.
         */
        public function register_settings() {
            $schema = [
                'type' => 'object',
                'properties' => [
                    'rules' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'discount_on' => [
                                    'type' => 'string',
                                    'enum' => ['total', 'product']
                                ],
                                'discount_type' => [
                                    'type' => 'string',
                                    'enum' => ['percent', 'flat']
                                ],
                                'amount' => ['type' => 'string'],
                                'min_order' => ['type' => 'string'],
                                'is_active' => [
                                    'type' => 'string',
                                    'enum' => ['yes', 'no']
                                ],
                                'inc_products' => $this->data_structure_product,
                                'inc_categories' => $this->data_structure_category,
                                'ex_products' => $this->data_structure_product,
                                'ex_categories' => $this->data_structure_category,

                            ],
                            'required' => ['title', 'discount_type', 'discount_on', 'amount', 'is_active']
                        ]
                    ],
                    'others' => [
                        'type' => 'object',
                        'properties' => [
                            'apply_rule' => [
                                'type' => 'string',
                                'enum' => ['lowest', 'highest']
                            ],
                            'show_to' => [
                                'type' => 'string',
                                'enum' => ['all', 'logged_in']
                            ],
                            'exclusive_rule' => ['type' => 'string'],
                            'from_text' => ['type' => 'string'],
                            'show_notice' => [
                                'type' => 'string',
                                'enum' => ['yes', 'no']
                            ],
                            'notice_text' => ['type' => 'string'],
                            'text_color' => ['type' => 'string'],
                            'bg_color' => ['type' => 'string'],
                        ]
                    ]
                ]
            ];

            $default = [
                'rules' => [],
                'others' => [
                    'apply_rule' => 'lowest',
                    'show_to' => 'all',
                    'exclusive_rule' => '',
                    'from_text' => __('From', 'als-discount-rules-for-woocommerce'),
                    'show_notice' => 'no',
                    'notice_text' => '',
                    'text_color' => '',
                    'bg_color' => '',
                ]
            ];

            // Register settings with WordPress
            register_setting(
                'options',
                $this->plugin_name,
                [
                    'type' => 'object',
                    'default' => $default,
                    'show_in_rest' => [
                        'schema' => $schema
                    ]
                ]
            );
        }

        /**
         * Adds a link to plugin settings page to plugin links
         * 
         * This function hooks into 'plugin_action_links_{plugin_name}' to show
         * a link to setup page 
         */
        public function add_menu_link($links){
            $plugin_links = array(
                '<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' . __('Settings', 'als-discount-rules-for-woocommerce') . '</a>'
            );

            return array_merge($plugin_links, $links);
        }

        /**
         * Adds a submenu page under WooCommerce menu
         * 
         * This function hooks into the 'admin_menu' action to add
         * a submenu page to the WooCommerce menu where the plugin settings
         * can be accessed by administrators.
         */
        public function add_menu_page() {
            add_submenu_page(
                'woocommerce',
                __('Business Discount Rules - WooCommerce', 'als-discount-rules-for-woocommerce'),
                __('Business Discount', 'als-discount-rules-for-woocommerce'),
                'manage_options',
                $this->plugin_name,
                [$this, 'render_menu_page']
            );
        }

        /**
         * Renders the settings page content
         * 
         * This function loads the settings page view from the plugin
         * directory, typically a PHP file containing HTML form elements
         * for configuring the plugin settings.
         */
        public function render_menu_page() {
            require_once plugin_dir_path(dirname(__FILE__)) . 'views/settings.php';
        }

        /**
         * Enqueues necessary scripts and styles for the settings page
         * 
         * This function hooks into 'admin_enqueue_scripts' to enqueue
         * JavaScript and CSS files needed for the plugin settings page.
         * It ensures that these files are only loaded when on the settings
         * page of the plugin.
         */
        public function enqueue_scripts() {

            // Exit early if the JS asset file doesn't exist
            if (!file_exists(plugin_dir_path(dirname(__FILE__)) . 'js/build/index.asset.php')) return;

            // Include asset file and enqueue script
            $asset_file = include(plugin_dir_path(dirname(__FILE__)) . 'js/build/index.asset.php');
            wp_enqueue_script(
                $this->plugin_name,
                plugins_url('js/build/index.js', dirname(__FILE__)),
                $asset_file['dependencies'],
                $asset_file['version'],
                ['in_footer' => true],
            );

            // Localize script with currency symbol
            wp_localize_script(
                $this->plugin_name,
                'alsDrw',
                [
                    'currencySymbol' => get_woocommerce_currency_symbol()
                ]
            );

            // Set script translations
            wp_set_script_translations(
                $this->plugin_name,
                'als-discount-rules-for-woocommerce', // Text domain
                plugin_dir_path(__FILE__) . 'languages' // Path to .mo files
            );

            // Enqueue CSS for the settings page
            wp_enqueue_style(
                $this->plugin_name,
                plugins_url('css/admin.css', dirname(__FILE__)),
                array_filter(
                    $asset_file['dependencies'],
                    function ($style) {
                        return wp_style_is($style, 'registered');
                    }
                ),
                $asset_file['version'],
            );
        }
    }
}
