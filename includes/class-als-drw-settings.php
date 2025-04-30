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

        private $data_structure_product;

        private $data_structure_category;

        public function __construct($plugin_name)
        {
            $this->plugin_name = $plugin_name;

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
            
            add_action('admin_menu', [$this, 'add_menu_page']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('init', [$this, 'register_settings']);
        }

        public function register_settings(){
            $schema = [
                'type' => 'object',
                'properties' => [
                    'rules' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => [ 'type' => 'string'],
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
                                'show_notice' => [
                                    'type' => 'string',
                                    'enum' => ['yes', 'no']
                                ],
                                'notice_text' => ['type' => 'string'],
                                'text_color' => ['type' => 'string'],
                                'bg_color' => ['type' => 'string'],
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
                            'from_text' => ['type' => 'string']
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
                    'from_text' => __('From', 'als-drw')
                ]
            ];


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
    
        public function add_menu_page(){
            add_submenu_page(
                'woocommerce',
                __('Business Discount Rules - WooCommerce', 'als-drw'),
                __('Business Discount', 'als-drw'),
                'manage_options',
                $this->plugin_name,
                [$this, 'render_menu_page']
            );
        }

        public function render_menu_page(){
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'views/settings.php';
        }

        public function enqueue_scripts(){
            global $pagenow;

            // Exit early if not on the target admin page
            if ($pagenow !== 'admin.php' || !isset($_GET['page']) || $_GET['page'] !== $this->plugin_name) {
                return;
            }

            if(!file_exists(plugin_dir_path(dirname(__FILE__)) . 'js/build/index.asset.php')) return;

            $asset_file = include(plugin_dir_path(dirname(__FILE__)) . 'js/build/index.asset.php');

            wp_enqueue_script(
                $this->plugin_name,
                plugins_url('js/build/index.js', dirname(__FILE__)),
                $asset_file['dependencies'],
                $asset_file['version'],
                array(
                    'in_footer' => true,
                )
            );

            wp_localize_script(
                $this->plugin_name,
                'alsDrw',
                array(
                    'currencySymbol' => get_woocommerce_currency_symbol()
                )
            );

            wp_set_script_translations(
                $this->plugin_name,
                'als-drw', // Text domain
                plugin_dir_path(__FILE__) . 'languages' // Path to .mo files
            );

            wp_enqueue_style(
                $this->plugin_name,
                plugins_url('css/als-discount-rules-for-woocommerce-admin.css', dirname(__FILE__)),
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