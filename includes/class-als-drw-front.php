<?php

/**
 * Front-end functionality of the Business Discount Rules - WooCommerce plugin
 *
 * This class handles price display modifications, discount application logic,
 * cart total manipulation, and script/style enqueuing for the frontend.
 *
 * @package    ALS_DRW
 * @subpackage ALS_DRW/includes
 */

if (!class_exists('ALS_DRW_Front')) {

    class ALS_DRW_Front {
        /** @var string Plugin name, used for options and asset handles */
        private $plugin_name;

        /**
         * Constructor
         * Hooks WooCommerce filters and actions needed for frontend discount features
         */
        public function __construct($plugin_name) {
            $this->plugin_name = $plugin_name;

            // Enqueue frontend scripts and styles
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

            // Modify price display on product pages
            add_filter('woocommerce_get_price_html', [$this, 'modifyProductPriceDisplay'], 10, 2);

            // Apply discounts to cart fees
            add_action('woocommerce_cart_calculate_fees', [$this, 'applyDiscountToCart']);

            // Show discounted prices in cart line items
            add_filter('woocommerce_cart_item_price', [$this, 'modifyCartItemPrice'], 10, 3);

            // Save custom prices during cart item addition
            add_filter('woocommerce_add_cart_item_data', [$this, 'saveCartItemData'], 10, 4);

            // Modify cart total prices with discount
            add_action('woocommerce_before_calculate_totals', [$this, 'modifyCartTotal'], 999);
        }

        /**
         * Adjusts cart item total price if a custom discounted price is set
         * Hooked into `woocommerce_before_calculate_totals`
         */
        public function modifyCartTotal($cart) {
            if (is_admin() && ! defined('DOING_AJAX')) return;
            if (did_action('woocommerce_before_calculate_totals') >= 2) return;

            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if (isset($cart_item['als_drw_custom_price'])) {
                    $custom_price = (float) $cart_item['als_drw_custom_price'];
                    $cart_item['data']->set_price($custom_price);
                }
            }
        }

        /**
         * Shows strike-through original price and discounted price in cart items
         */
        public function modifyCartItemPrice($price, $cart_item, $cart_item_key) {
            if (isset($cart_item['als_drw_custom_price'])) {
                $dp = wc_get_price_decimals();

                $custom_price = (float) $cart_item['als_drw_custom_price'];
                $original_price = (float) $cart_item['als_drw_original_price'];

                return '<div class="awdr_cart_strikeout_line">'
                    . '<del>' . wc_price(wc_format_decimal($original_price, $dp)) . '</del>&nbsp;'
                    . '<ins>' . wc_price(wc_format_decimal($custom_price, $dp)) . '</ins>'
                    . '</div>';
            }

            return $price;
        }

        /**
         * Save custom pricing data during add-to-cart
         * Used later to calculate custom totals and display discounts
         */
        public function saveCartItemData($cart_item_data, $product_id, $variation_id, $quantity) {
            $settings = get_option($this->plugin_name);

            $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
            if (!$product || is_wp_error($product)) return $cart_item_data;
            if (!wc_get_price_including_tax($product)) return $cart_item_data;

            $product_price = (float) $product->get_price();
            $discounted_price = (float) $this->applyDiscountToProduct($product_price, $product, $settings);

            if ($discounted_price < $product_price) {
                $cart_item_data['als_drw_custom_price'] = $discounted_price;
                $cart_item_data['als_drw_original_price'] = $product_price;
            }

            return $cart_item_data;
        }

        /**
         * Modify price HTML on product page if discount is applicable
         */
        public function modifyProductPriceDisplay($price, $product) {
            if (!wc_get_price_including_tax($product)) return $price;

            $settings = get_option($this->plugin_name);
            $from_text = $settings['others']['from_text'] ?? __('From', 'als-drw');

            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $lowest_price = PHP_FLOAT_MAX;
                $lowest_discounted_price = PHP_FLOAT_MAX;

                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if (!$variation) continue;

                    $variation_price = (float) $variation->get_price();
                    $discounted_price = (float) $this->applyDiscountToProduct($variation_price, $variation, $settings);

                    $lowest_price = min($lowest_price, $variation_price);
                    $lowest_discounted_price = min($lowest_discounted_price, $discounted_price);
                }

                if ($lowest_discounted_price === PHP_FLOAT_MAX || $lowest_discounted_price === $lowest_price) {
                    return $price;
                }

                return '<span class="als-drw-from">' . $from_text . '</span>&nbsp;'
                    . wc_format_sale_price($lowest_price, $lowest_discounted_price) . $product->get_price_suffix();
            }

            $product_price = (float) $product->get_price();
            $discounted_price = (float) $this->applyDiscountToProduct($product_price, $product, $settings);

            if ($discounted_price === $product_price) return $price;

            return wc_format_sale_price($product_price, $discounted_price) . $product->get_price_suffix();
        }

        /**
         * Core logic to apply product-level discounts
         * Handles category/product inclusion/exclusion and rule priority
         */
        private function applyDiscountToProduct($product_price, $product, $settings) {
            $product_id = $product->get_id();
            $parent_id = $product->get_parent_id();
            $product_cats = wc_get_product_term_ids($product_id, 'product_cat');

            $calculate_discount = function ($price) use ($product_id, $parent_id, $product_cats, $settings) {
                $discounts = [];
                $exclusive_discount = null;

                $discount_rules = $settings['rules'] ?? [];
                $exclusive_rule = $settings['others']['exclusive_rule'] ?? '';
                $show_to = $settings['others']['show_to'] ?? 'all';
                $apply_rule = $settings['others']['apply_rule'] ?? 'highest';

                if ($show_to === 'logged_in' && !is_user_logged_in()) return $price;

                foreach ($discount_rules as $rule) {
                    if (
                        !isset($rule['is_active'], $rule['discount_type'], $rule['amount'], $rule['discount_on']) ||
                        $rule['is_active'] !== 'yes' || $rule['discount_on'] !== 'product'
                    ) {
                        continue;
                    }

                    $discount_type = sanitize_text_field($rule['discount_type']);
                    $amount = floatval($rule['amount']);

                    $inc_categories = isset($rule['inc_categories']) ? array_column($rule['inc_categories'], 'value') : [];
                    $ex_categories = isset($rule['ex_categories']) ? array_column($rule['ex_categories'], 'value') : [];

                    $inc_products = isset($rule['inc_products']) ? array_column($rule['inc_products'], 'value') : [];
                    $ex_products = isset($rule['ex_products']) ? array_column($rule['ex_products'], 'value') : [];

                    $matched = false;

                    if (!empty($ex_products) && (in_array($product_id, $ex_products) || in_array($parent_id, $ex_products))) {
                        continue;
                    }

                    if (!empty($ex_categories) && array_intersect($ex_categories, $product_cats)) {
                        continue;
                    }

                    if (!empty($inc_products) && (in_array($product_id, $inc_products) || in_array($parent_id, $inc_products))) {
                        $matched = true;
                    }

                    if (!empty($inc_categories) && array_intersect($inc_categories, $product_cats)) {
                        $matched = true;
                    }

                    if (empty($inc_products) && empty($inc_categories)) {
                        $matched = true;
                    }

                    if (!$matched) continue;

                    $discount_amount = ($discount_type === 'percent') ? ($amount / 100) * $price : $amount;

                    if (!empty($exclusive_rule) && esc_attr($rule['title']) === $exclusive_rule) {
                        if ($exclusive_discount === null || $discount_amount > $exclusive_discount) {
                            $exclusive_discount = $discount_amount;
                        }
                    } else {
                        $discounts[] = $discount_amount;
                    }
                }

                if ($exclusive_discount !== null) {
                    return max(0, $price - $exclusive_discount);
                }

                if (!empty($discounts)) {
                    $final_discount = ($apply_rule === 'lowest') ? min($discounts) : max($discounts);
                    return max(0, $price - $final_discount);
                }

                return $price;
            };

            return is_array($product_price)
                ? array_map($calculate_discount, $product_price)
                : $calculate_discount($product_price);
        }

        /**
         * Applies cart-level discounts as fees (negative amounts)
         * Hooked into `woocommerce_cart_calculate_fees`
         */
        public function applyDiscountToCart($cart) {
            if (is_admin() || !$cart || $cart->is_empty()) return;

            foreach ($cart->get_cart() as $cart_item) {
                if (isset($cart_item['als_drw_custom_price'])) return; // Skip if product-level discount already applied
            }

            $settings = get_option($this->plugin_name);
            $discount_rules = $settings['rules'] ?? [];
            $exclusive_rule = sanitize_text_field($settings['others']['exclusive_rule'] ?? '');
            $show_to = $settings['others']['show_to'] ?? 'all';
            $apply_rule = $settings['others']['apply_rule'] ?? 'highest';

            if ($show_to === 'logged_in' && !is_user_logged_in()) return;
            if (empty($discount_rules)) return;

            $cart_subtotal = (float) $cart->get_subtotal();
            $discounts = [];
            $exclusive_discount = null;

            foreach ($discount_rules as $rule) {
                if ($rule['is_active'] !== 'yes' || $rule['discount_on'] !== 'total') continue;

                $discount_type = sanitize_text_field($rule['discount_type']);
                $amount = floatval($rule['amount']);
                $title = sanitize_text_field($rule['title']);
                $min_order = isset($rule['min_order']) ? floatval($rule['min_order']) : 0;

                if ($cart_subtotal < $min_order) continue;

                $discount_amount = ($discount_type === 'percent') ? ($amount / 100) * $cart_subtotal : $amount;

                if (!empty($exclusive_rule) && esc_attr($title) === $exclusive_rule && $discount_amount > 0) {
                    $exclusive_discount = ['amount' => $discount_amount, 'title' => $title];
                    break;
                }

                if ($discount_amount > 0) {
                    $discounts[] = ['amount' => $discount_amount, 'title' => $title];
                }
            }

            if ($exclusive_discount) {
                $cart->add_fee($exclusive_discount['title'], -$exclusive_discount['amount'], false, '');
            } elseif ($discounts) {
                usort($discounts, fn($a, $b) => $a['amount'] <=> $b['amount']);
                $applicable_discount = ($apply_rule === 'lowest') ? $discounts[0] : end($discounts);
                $cart->add_fee($applicable_discount['title'], -$applicable_discount['amount'], false, '');
            }
        }

        /**
         * Enqueues frontend JS and CSS with dynamic notice injection
         */
        public function enqueue_scripts() {
            wp_enqueue_script(
                $this->plugin_name . '-front',
                plugins_url('js/front.js', dirname(__FILE__)),
                ['jquery'],
                ALS_DRW_VERSION,
                true // Fixed trailing comma here
            );

            $settings = get_option($this->plugin_name);
            $show_notice = $settings['others']['show_notice'] ?? 'no';
            $notice_text = $settings['others']['notice_text'] ?? '';

            $inline_style = '';

            if (!empty($settings['others']['bg_color'])) {
                $inline_style .= sprintf('.als-drw-notice-container{background-color: %s}', esc_attr($settings['others']['bg_color']));
            }

            if (!empty($settings['others']['text_color'])) {
                $inline_style .= sprintf('.als-drw-notice{color: %s}', esc_attr($settings['others']['text_color']));
            }

            wp_localize_script(
                $this->plugin_name . '-front',
                'alsDrw',
                [
                    'notice' => [
                        'show' => esc_js(sanitize_text_field($show_notice)),
                        'text' => esc_js(sanitize_text_field($notice_text))
                    ]
                ]
            );

            wp_enqueue_style(
                $this->plugin_name . '-front',
                plugins_url('css/front.css', dirname(__FILE__)),
                [],
                ALS_DRW_VERSION
            );

            wp_add_inline_style($this->plugin_name . '-front', $inline_style);
        }
    }
}
