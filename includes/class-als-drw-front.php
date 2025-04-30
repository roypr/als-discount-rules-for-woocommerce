<?php

/**
 * The file that defines the front end functinality of the plugin
 *
 * A class definition that includes attributes and functions
 * for front end functionality
 *
 * @link       https://#
 * @since      1.0.0
 *
 * @package    ALS_DRW
 * @subpackage ALS_DRW/includes
 */

use Booknetic_Mpdf\Tag\Em;

if (!class_exists('ALS_DRW_Front')) {
    class ALS_DRW_Front {
        private $plugin_name;

        public function __construct($plugin_name) {
            $this->plugin_name = $plugin_name;

            add_filter('woocommerce_get_price_html', [$this, 'modifyProductPriceDisplay'], 10, 2);
            add_action('woocommerce_cart_calculate_fees', [$this, 'applyDiscountRules']);
            add_filter('woocommerce_cart_item_price', [$this, 'modifyCartItemPrice'], 10, 3);

            add_filter('woocommerce_add_cart_item_data', [$this, 'saveCartItemData'], 10, 4);

            add_action('woocommerce_before_calculate_totals', [$this, 'modifyCartTotal'], 999);
        }

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

        public function saveCartItemData($cart_item_data, $product_id, $variation_id, $quantity) {

            // Retrieve discount rules
            $settings = get_option($this->plugin_name);

            if ($variation_id) {
                $product = wc_get_product($variation_id);
            } else {
                $product = wc_get_product($product_id);
            }

            if (!$product || is_wp_error($product)) {
                return $cart_item_data;
            }

            if (!wc_get_price_including_tax($product)) {
                return $cart_item_data;
            }

            $product_price = (float) $product->get_price();

            $discounted_price = (float) $this->applyDiscountToProduct(
                $product_price,
                $product,
                $settings
            );

            if ($discounted_price < $product_price) {
                $cart_item_data['als_drw_custom_price'] = $discounted_price;
                $cart_item_data['als_drw_original_price'] = $product_price;
            }

            return $cart_item_data;
        }

        public function modifyProductPriceDisplay($price, $product) {
            // If product has no price set, return the original price.
            if (!wc_get_price_including_tax($product)) {
                return $price;
            }

            // Retrieve discount rules
            $settings = get_option($this->plugin_name);

            $from_text = $settings['others']['from_text'] ?? __('From', 'als-drw');

            // Handle Variable Products
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

                if ($lowest_discounted_price === PHP_FLOAT_MAX) {
                    return $price; // No valid variations found
                }

                if ($lowest_discounted_price == $lowest_price) {
                    return $price;
                }

                return '<span class="als-drw-from">' . $from_text . '</span>&nbsp;'
                       . wc_format_sale_price($lowest_price, $lowest_discounted_price) . $product->get_price_suffix();
            }

            $product_price = (float) $product->get_price();
            $discounted_price = (float) $this->applyDiscountToProduct($product_price, $product, $settings);

            if($discounted_price == $product_price){
                return $price;
            }else{
                return wc_format_sale_price($product_price, $discounted_price) . $product->get_price_suffix();
            }
            
            return $price;
        }

        // Helper function to apply discount logic to a single product
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

                // Condition: only show to logged-in users
                if ($show_to === 'logged_in' && !is_user_logged_in()) {
                    return $price;
                }

                foreach ($discount_rules as $rule) {
                    if (!isset($rule['is_active'], $rule['discount_type'], $rule['amount'], $rule['discount_on'])) {
                        continue;
                    }

                    if ($rule['is_active'] !== 'yes' || $rule['discount_on'] !== 'product') {
                        continue;
                    }

                    $discount_type = sanitize_text_field($rule['discount_type']);
                    $amount = floatval($rule['amount']);

                    $inc_categories = isset($rule['inc_categories']) ? array_column($rule['inc_categories'], 'value') : [];
                    $ex_categories = isset($rule['ex_categories']) ? array_column($rule['ex_categories'], 'value') : [];

                    $inc_products = isset($rule['inc_products']) ? array_column($rule['inc_products'], 'value') : [];
                    $ex_products = isset($rule['ex_products']) ? array_column($rule['ex_products'], 'value') : [];

                    $parent_has_discount = (!empty($parent_id) && in_array($parent_id, $inc_products));
                    $product_in_included_list = in_array($product_id, $inc_products);

                    // If product is explicitly excluded, skip rule
                    if (!empty($ex_products) && (in_array($product_id, $ex_products) || in_array($parent_id, $ex_products))) {
                        continue;
                    }

                    if (!empty($ex_categories) && !empty(array_intersect($ex_categories, $product_cats))) {
                        continue;
                    }

                    // $has_product_match = !empty($inc_products) && ($parent_has_discount || $product_in_included_list);
                    // $has_category_match = !empty($inc_categories) && !empty(array_intersect($inc_categories, $product_cats));

                    $matched = false;

                    // Product match
                    if (!empty($inc_products) && ($product_in_included_list || $parent_has_discount)) {
                        $matched = true;
                    }

                    // Category match
                    if (!empty($inc_categories) && !empty(array_intersect($inc_categories, $product_cats))) {
                        $matched = true;
                    }

                    // Default: if no filters are set, match all
                    if (empty($inc_products) && empty($inc_categories)) {
                        $matched = true;
                    }

                    // Final skip if nothing matched
                    if (!$matched) {
                        continue;
                    }

                    // Apply discount
                    $discount_amount = ($discount_type === 'percent') ? ($amount / 100) * $price : $amount;

                    if (!empty($exclusive_rule) && esc_attr($rule['title']) === $exclusive_rule) {
                        if ($exclusive_discount === null || $discount_amount > $exclusive_discount) {
                            $exclusive_discount = $discount_amount;
                        }
                    } else {
                        $discounts[] = $discount_amount;
                    }
                }

                // Determine final discount
                if ($exclusive_discount !== null) {
                    return max(0, $price - $exclusive_discount);
                }

                if (!empty($discounts)) {
                    $final_discount = ($apply_rule === 'lowest') ? min($discounts) : max($discounts);
                    return max(0, $price - $final_discount);
                }

                return $price;
            };

            if (is_array($product_price)) {
                $discounted_prices = [];
                foreach ($product_price as $key => $price) {
                    $discounted_prices[$key] = $calculate_discount($price);
                }
                return $discounted_prices;
            } else {
                return $calculate_discount($product_price);
            }
        }

        public function applyDiscountToCart($cart) {
            // Exit if cart is empty or in admin
            if (is_admin() || !WC()->cart || WC()->cart->is_empty()) {
                return;
            }

            $discount_applied_on_product = false;

            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if (isset($cart_item['als_drw_custom_price'])) {
                    $discount_applied_on_product = true;
                    break;
                }
            }

            if ($discount_applied_on_product) {
                //Product discount applied already
                return;
            }

            // Retrieve discount rules
            $settings = get_option($this->plugin_name);
            $discount_rules = isset($settings['rules']) ? $settings['rules'] : [];
            $exclusive_rule = $settings['others']['exclusive_rule'] ?? '';
            $show_to = $settings['others']['show_to'] ?? 'all';
            $apply_rule = $settings['others']['apply_rule'] ?? 'highest';

            // Condition: only show to logged-in users
            if ($show_to === 'logged_in' && !is_user_logged_in()) {
                return;
            }

            if (empty($discount_rules)) {
                return; // No discount rules, exit
            }

            $applied_discount = 0; // Track highest discount
            $applied_title = ''; // Track title of applied discount
            $cart_subtotal = floatval($cart->get_subtotal());

            $exclusive_discount = null; // Store exclusive rule (if any)

            // Loop through discount rules
            foreach ($discount_rules as $rule) {
                if (!isset($rule['is_active'], $rule['discount_on'], $rule['discount_type'], $rule['amount'], $rule['title'])) {
                    continue; // Skip invalid rules
                }

                // Skip inactive rules
                if ($rule['is_active'] !== 'yes') {
                    continue;
                }

                // Parse rule properties
                $discount_on = sanitize_text_field($rule['discount_on']);
                $discount_type = sanitize_text_field($rule['discount_type']);
                $amount = floatval($rule['amount']);
                $title = sanitize_text_field($rule['title']);
                $min_order = isset($rule['min_order']) ? floatval($rule['min_order']) : 0;

                $discount_amount = 0;

                if ($discount_on === 'total') {
                    // Apply discount on cart subtotal if min_order is met
                    if ($cart_subtotal >= $min_order) {
                        $discount_amount = ($discount_type === 'percent') ? ($amount / 100) * $cart_subtotal : $amount;
                    }
                }

                // Handle exclusive rule
                if (!empty($exclusive_rule) && esc_attr($rule['title']) == $exclusive_rule && $discount_amount > 0) {
                    $exclusive_discount = ['amount' => $discount_amount, 'title' => $title];
                    break;
                }

                // Keep only the highest discount
                if( $apply_rule == 'highest'){
                    if ($discount_amount > $applied_discount) {
                        $applied_discount = $discount_amount;
                        $applied_title = $title;
                    }
                }else{
                    if ($discount_amount < $applied_discount) {
                        $applied_discount = $discount_amount;
                        $applied_title = $title;
                    }
                }
                
            }

            // Apply discount as a negative fee
            if ($exclusive_discount) {
                $cart->add_fee($exclusive_discount['title'], -$exclusive_discount['amount'], true, '');
            } elseif ($applied_discount > 0) {
                $cart->add_fee($applied_title, -$applied_discount, true, '');
            }
        }
    }
}
