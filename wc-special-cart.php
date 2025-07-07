<?php
/*
Plugin Name: WooCommerce Specific Products Discount
Description: Applies customizable shipping fee for specific products or categories based on weight
Version: 1.0
Author: Alireza Fatemi
Author URI: https://alirezafatemi.ir
Plugin URI: https://github.com/Ftepic
Text Domain: wc-specific-discount
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
 exit;
}

class WC_Specific_Products_Discount {
 private $base_weight = 1000; // 1000 grams
 private $base_shipping_fee = 80000; // 80,000 Tomans for up to 1000g
 private $additional_kg_fee = 30000; // 30,000 Tomans per additional 1000g
 
 public function __construct() {
 add_action('plugins_loaded', array($this, 'load_textdomain'));
 add_action('admin_menu', array($this, 'add_admin_menu'));
 add_action('admin_init', array($this, 'register_settings'));
 add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_cart_fees'));
 add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
 add_action('woocommerce_checkout_create_order', array($this, 'add_order_meta'));
 add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_meta_in_admin'));
 add_action('woocommerce_order_details_after_order_table', array($this, 'display_order_meta_in_frontend'));
 add_filter('manage_edit-shop_order_columns', array($this, 'add_shipping_type_column'));
 add_action('manage_shop_order_posts_custom_column', array($this, 'populate_shipping_type_column'), 10, 2);
 
 if (get_option('wc_specific_discount_hide_weight', 'no') === 'yes') {
 add_filter('woocommerce_product_tabs', array($this, 'remove_additional_information_tab'), 98);
 add_filter('woocommerce_display_product_attributes', array($this, 'remove_weight_from_product_attributes'));
 }
 }
 
 public function load_textdomain() {
 load_plugin_textdomain('wc-specific-discount', false, dirname(plugin_basename(__FILE__)) . '/languages');
 }
 
 public function enqueue_admin_scripts($hook) {
 if ($hook != 'toplevel_page_wc-specific-discount' && $hook != 'edit.php') {
 return;
 }
 
 wp_enqueue_style('wp-color-picker');
 wp_enqueue_script('wp-color-picker');
 wp_enqueue_style('wc-specific-discount-admin', plugins_url('admin-style.css', __FILE__));
 
 $admin_css = "
 @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');
 
 .wc-discount-wrapper {
 background: #fff;
 border-radius: 8px;
 box-shadow: 0 2px 10px rgba(0,0,0,0.05);
 padding: 25px;
 margin: 20px 0;
 }
 
 html[lang='fa-IR'] .wc-discount-wrapper * {
 font-family: 'Vazir', sans-serif !important;
 }
 
 .wc-discount-header {
 border-bottom: 1px solid #eee;
 margin-bottom: 20px;
 padding-bottom: 15px;
 }
 
 .wc-discount-header h1 {
 color: #23282d;
 font-size: 24px;
 font-weight: 600;
 }
 
 .wc-discount-section {
 margin-bottom: 25px;
 }
 
 .wc-discount-section h2 {
 font-size: 18px;
 margin-bottom: 15px;
 color: #23282d;
 font-weight: 500;
 }
 
 .wc-discount-select {
 width: 100%;
 min-height: 200px;
 border: 1px solid #ddd;
 border-radius: 4px;
 padding: 8px;
 }
 
 .wc-discount-description {
 color: #666;
 font-style: italic;
 margin-top: 8px;
 }
 
 .wc-discount-submit {
 margin-top: 20px;
 }
 
 .wc-discount-submit .button-primary {
 background: #0073aa;
 border-color: #0073aa;
 color: #fff;
 padding: 5px 20px;
 height: auto;
 font-size: 14px;
 font-weight: 500;
 box-shadow: none;
 border-radius: 4px;
 }
 
 .wc-discount-submit .button-primary:hover {
 background: #006799;
 border-color: #006799;
 }
 
 .wc-discount-amount {
 padding: 8px;
 border-radius: 4px;
 border: 1px solid #ddd;
 width: 200px;
 }
 
 .wc-discount-amount-section {
 background: #f9f9f9;
 padding: 15px;
 border-radius: 4px;
 margin-bottom: 25px;
 }
 
 .shipping-type-cash-on-delivery {
 background-color: #ffe8e8;
 padding: 10px;
 border-right: 3px solid #ff4d4d;
 margin-bottom: 10px;
 border-radius: 4px;
 }
 
 .shipping-type-regular {
 background-color: #e8f4ff;
 padding: 10px;
 border-right: 3px solid #4d94ff;
 margin-bottom: 10px;
 border-radius: 4px;
 }
 
 .column-shipping_type {
 width: 100px;
 }
 
 .shipping-badge {
 display: inline-block;
 padding: 5px 10px;
 border-radius: 4px;
 font-weight: bold;
 text-align: center;
 min-width: 80px;
 }
 
 .shipping-badge.cash-on-delivery {
 background-color: #ffe8e8;
 color: #d63638;
 border: 1px solid #ffb1b1;
 }
 
 .shipping-badge.regular {
 background-color: #e8f4ff;
 color: #0073aa;
 border: 1px solid #b1d4ff;
 }
 
 .wc-shipping-rates {
 background: #f0f0f1;
 padding: 15px;
 border-radius: 4px;
 margin-bottom: 25px;
 }
 
 .wc-shipping-rates-title {
 font-size: 16px;
 margin-bottom: 10px;
 font-weight: 600;
 }
 
 .wc-shipping-rates-info {
 background: #fff;
 padding: 10px;
 border-radius: 4px;
 border: 1px solid #ddd;
 }
 
 .wc-weight-visibility {
 background: #f0f0f1;
 padding: 15px;
 border-radius: 4px;
 margin-bottom: 25px;
 }
 
 .wc-language-settings {
 background: #f0f0f1;
 padding: 15px;
 border-radius: 4px;
 margin-bottom: 25px;
 }";
 
 wp_add_inline_style('wc-specific-discount-admin', $admin_css);
 }
 
 public function add_admin_menu() {
 add_menu_page(
 $this->get_translated_text('Shipping Products Settings', 'تنظیمات ارسال محصولات'),
 $this->get_translated_text('Shipping Products', 'ارسال محصولات'),
 'manage_options',
 'wc-specific-discount',
 array($this, 'admin_page'),
 'dashicons-cart',
 56
 );
 }
 
 public function register_settings() {
 register_setting('wc-specific-discount', 'wc_specific_discount_products');
 register_setting('wc-specific-discount', 'wc_specific_discount_categories');
 register_setting('wc-specific-discount', 'wc_specific_discount_base_fee');
 register_setting('wc-specific-discount', 'wc_specific_discount_additional_kg_fee');
 register_setting('wc-specific-discount', 'wc_specific_discount_base_weight');
 register_setting('wc-specific-discount', 'wc_specific_discount_hide_weight');
 register_setting('wc-specific-discount', 'wc_specific_discount_cod_message');
 register_setting('wc-specific-discount', 'wc_specific_discount_fee_message');
 register_setting('wc-specific-discount', 'wc_specific_discount_language');
 }
 
 public function get_translated_text($english_text, $persian_text) {
 $language = get_option('wc_specific_discount_language', 'auto');
 
 if ($language === 'en') {
 return $english_text;
 } elseif ($language === 'fa') {
 return $persian_text;
 } else {
 $locale = get_locale();
 return ($locale === 'fa_IR') ? $persian_text : $english_text;
 }
 }
 
 public function get_cod_message() {
 $custom_message = get_option('wc_specific_discount_cod_message', '');
 if (!empty($custom_message)) {
 return $custom_message;
 }
 return $this->get_translated_text('Cash on Delivery', 'پس کرایه');
 }
 
 public function get_fee_message() {
 $custom_message = get_option('wc_specific_discount_fee_message', '');
 if (!empty($custom_message)) {
 return $custom_message;
 }
 return $this->get_translated_text('Postal Shipping', 'ارسال پستی');
 }
 
 public function admin_page() {
 $products = get_option('wc_specific_discount_products', array());
 $categories = get_option('wc_specific_discount_categories', array());
 $base_fee = get_option('wc_specific_discount_base_fee', $this->base_shipping_fee);
 $additional_kg_fee = get_option('wc_specific_discount_additional_kg_fee', $this->additional_kg_fee);
 $base_weight = get_option('wc_specific_discount_base_weight', $this->base_weight);
 $hide_weight = get_option('wc_specific_discount_hide_weight', 'no');
 $cod_message = get_option('wc_specific_discount_cod_message', '');
 $fee_message = get_option('wc_specific_discount_fee_message', '');
 $language = get_option('wc_specific_discount_language', 'auto');
 
 ?>
 <div class="wrap wc-discount-wrapper">
 <div class="wc-discount-header">
 <h1><?php echo $this->get_translated_text('WooCommerce Shipping Products Settings', 'تنظیمات ارسال محصولات ووکامرس'); ?></h1>
 </div>
 
 <form method="post" action="options.php">
 <?php settings_fields('wc-specific-discount'); ?>
 <?php do_settings_sections('wc-specific-discount'); ?>
 
 <div class="wc-language-settings">
 <div class="wc-shipping-rates-title"><?php echo $this->get_translated_text('Language Settings', 'تنظیمات زبان'); ?></div>
 <div class="wc-shipping-rates-info">
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Plugin Language', 'زبان افزونه'); ?></h2>
 <select name="wc_specific_discount_language" class="wc-discount-amount">
 <option value="auto" <?php selected($language, 'auto'); ?>><?php echo $this->get_translated_text('Auto (Based on WordPress Language)', 'خودکار (بر اساس زبان وردپرس)'); ?></option>
 <option value="fa" <?php selected($language, 'fa'); ?>><?php echo $this->get_translated_text('Persian', 'فارسی'); ?></option>
 <option value="en" <?php selected($language, 'en'); ?>><?php echo $this->get_translated_text('English', 'انگلیسی'); ?></option>
 </select>
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Select the language for the plugin interface.', 'زبان رابط کاربری افزونه را انتخاب کنید.'); ?></p>
 </div>
 </div>
 </div>
 
 <div class="wc-shipping-rates">
 <div class="wc-shipping-rates-title"><?php echo $this->get_translated_text('Postal Shipping Settings', 'تنظیمات هزینه ارسال پستی'); ?></div>
 <div class="wc-shipping-rates-info">
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Base Weight (grams)', 'وزن پایه (گرم)'); ?></h2>
 <input type="number" name="wc_specific_discount_base_weight" value="<?php echo esc_attr($base_weight); ?>" class="wc-discount-amount" min="0" step="100">
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Base weight for shipping fee calculation (in grams)', 'وزن پایه برای محاسبه هزینه ارسال (به گرم)'); ?></p>
 </div>
 
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Base Shipping Fee (Tomans)', 'هزینه پایه ارسال (تومان)'); ?></h2>
 <input type="number" name="wc_specific_discount_base_fee" value="<?php echo esc_attr($base_fee); ?>" class="wc-discount-amount" min="0" step="1000">
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Shipping fee for base weight (in Tomans)', 'هزینه ارسال برای وزن پایه را به تومان وارد کنید.'); ?></p>
 </div>
 
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Additional 1000g Fee (Tomans)', 'هزینه هر 1000 گرم اضافه (تومان)'); ?></h2>
 <input type="number" name="wc_specific_discount_additional_kg_fee" value="<?php echo esc_attr($additional_kg_fee); ?>" class="wc-discount-amount" min="0" step="1000">
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Fee for each additional 1000 grams (in Tomans)', 'هزینه هر 1000 گرم اضافی را به تومان وارد کنید.'); ?></p>
 </div>
 </div>
 </div>
 
 <div class="wc-shipping-rates">
 <div class="wc-shipping-rates-title"><?php echo $this->get_translated_text('Shipping Labels', 'برچسب‌های ارسال'); ?></div>
 <div class="wc-shipping-rates-info">
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Cash on Delivery Label', 'برچسب پس کرایه'); ?></h2>
 <input type="text" name="wc_specific_discount_cod_message" value="<?php echo esc_attr($cod_message); ?>" class="wc-discount-amount" placeholder="<?php echo esc_attr($this->get_translated_text('Cash on Delivery', 'پس کرایه')); ?>">
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Custom label for Cash on Delivery shipping method', 'برچسب سفارشی برای روش ارسال پس کرایه'); ?></p>
 </div>
 
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Postal Shipping Label', 'برچسب ارسال پستی'); ?></h2>
 <input type="text" name="wc_specific_discount_fee_message" value="<?php echo esc_attr($fee_message); ?>" class="wc-discount-amount" placeholder="<?php echo esc_attr($this->get_translated_text('Postal Shipping', 'ارسال پستی')); ?>">
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Custom label for Postal Shipping method', 'برچسب سفارشی برای روش ارسال پستی'); ?></p>
 </div>
 </div>
 </div>
 
 <div class="wc-weight-visibility">
 <div class="wc-shipping-rates-title"><?php echo $this->get_translated_text('Product Weight Display', 'نمایش وزن محصولات'); ?></div>
 <div class="wc-shipping-rates-info">
 <div class="wc-discount-section">
 <label>
 <input type="checkbox" name="wc_specific_discount_hide_weight" value="yes" <?php checked($hide_weight, 'yes'); ?>>
 <?php echo $this->get_translated_text('Hide product weight on product page', 'مخفی‌سازی وزن محصولات در صفحه محصول'); ?>
 </label>
 <p class="wc-discount-description"><?php echo $this->get_translated_text('When enabled, product weight will not be displayed on product pages.', 'با فعال‌سازی این گزینه، وزن محصولات در صفحه محصول نمایش داده نمی‌شود.'); ?></p>
 </div>
 </div>
 </div>
 
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Select Cash on Delivery Products', 'انتخاب محصولات پس کرایه'); ?></h2>
 <select name="wc_specific_discount_products[]" multiple class="wc-discount-select">
 <?php
 $args = array(
 'post_type' => 'product',
 'posts_per_page' => -1,
 );
 $all_products = get_posts($args);
 
 foreach ($all_products as $product) {
 $selected = in_array($product->ID, $products) ? 'selected="selected"' : '';
 echo '<option value="' . esc_attr($product->ID) . '" ' . $selected . '>' . esc_html($product->post_title) . '</option>';
 }
 ?>
 </select>
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Select products that should be shipped with Cash on Delivery. Hold Ctrl key to select multiple products.', 'محصولاتی که به صورت پس کرایه ارسال می‌شوند را انتخاب کنید. برای انتخاب چند محصول، دکمه Ctrl را نگه دارید.'); ?></p>
 </div>
 
 <div class="wc-discount-section">
 <h2><?php echo $this->get_translated_text('Select Cash on Delivery Categories', 'انتخاب دسته‌بندی‌های پس کرایه'); ?></h2>
 <select name="wc_specific_discount_categories[]" multiple class="wc-discount-select">
 <?php
 $all_categories = get_terms(array(
 'taxonomy' => 'product_cat',
 'hide_empty' => false,
 ));
 
 foreach ($all_categories as $category) {
 $selected = in_array($category->term_id, $categories) ? 'selected="selected"' : '';
 echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
 }
 ?>
 </select>
 <p class="wc-discount-description"><?php echo $this->get_translated_text('Select categories that should be shipped with Cash on Delivery. Hold Ctrl key to select multiple categories.', 'دسته‌بندی‌هایی که به صورت پس کرایه ارسال می‌شوند را انتخاب کنید. برای انتخاب چند دسته‌بندی، دکمه Ctrl را نگه دارید.'); ?></p>
 </div>
 
 <div class="wc-discount-submit">
 <?php submit_button($this->get_translated_text('Save Settings', 'ذخیره تنظیمات'), 'primary', 'submit', false); ?>
 </div>
 </form>
 </div>
 <?php
 }
 
 public function calculate_cart_fees($cart) {
 if (is_admin() && !defined('DOING_AJAX')) {
 return;
 }
 
 $has_cod_product = false;
 $has_regular_product = false;
 $total_weight = 0;
 $selected_products = get_option('wc_specific_discount_products', array());
 $selected_categories = get_option('wc_specific_discount_categories', array());
 $base_fee = get_option('wc_specific_discount_base_fee', $this->base_shipping_fee);
 $additional_kg_fee = get_option('wc_specific_discount_additional_kg_fee', $this->additional_kg_fee);
 $base_weight = get_option('wc_specific_discount_base_weight', $this->base_weight);
 
 if (empty($cart->get_cart())) {
 return;
 }
 
 foreach ($cart->get_cart() as $cart_item) {
 $product_id = $cart_item['product_id'];
 $is_cod_product = false;
 $product = wc_get_product($product_id);
 $quantity = $cart_item['quantity'];
 
 if (in_array($product_id, $selected_products)) {
 $is_cod_product = true;
 }
 
 if (!$is_cod_product) {
 $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
 foreach ($product_categories as $category) {
 if (in_array($category, $selected_categories)) {
 $is_cod_product = true;
 break;
 }
 }
 }
 
 if ($is_cod_product) {
 $has_cod_product = true;
 } else {
 $has_regular_product = true;
 $product_weight = (float) $product->get_weight();
 if ($product_weight > 0) {
 $total_weight += ($product_weight * 1000) * $quantity; // Convert kg to grams
 }
 }
 
 if ($has_cod_product && $has_regular_product) {
 break;
 }
 }
 
 if ($has_cod_product) {
 WC()->session->set('shipping_type', 'cash_on_delivery');
 WC()->session->set('cart_weight', 0);
 } else if ($has_regular_product) {
 $shipping_fee = $base_fee;
 
 if ($total_weight > $base_weight) {
 $extra_weight = $total_weight - $base_weight;
 $extra_weight_units = ceil($extra_weight / 1000); // Calculate how many additional 1000g units
 $shipping_fee += $extra_weight_units * $additional_kg_fee;
 }
 
 $cart->add_fee($this->get_fee_message(), $shipping_fee);
 WC()->session->set('shipping_type', 'regular');
 WC()->session->set('cart_weight', $total_weight);
 }
 }
 
 public function add_order_meta($order) {
 if (WC()->session->get('shipping_type') === 'cash_on_delivery') {
 $order->update_meta_data('_shipping_type', 'cash_on_delivery');
 $order->update_meta_data('_shipping_type_label', $this->get_cod_message());
 $order->update_meta_data('_cart_weight', 0);
 } elseif (WC()->session->get('shipping_type') === 'regular') {
 $order->update_meta_data('_shipping_type', 'regular');
 $order->update_meta_data('_shipping_type_label', $this->get_fee_message());
 $order->update_meta_data('_cart_weight', WC()->session->get('cart_weight'));
 }
 }
 
 public function display_order_meta_in_admin($order) {
 $shipping_type = $order->get_meta('_shipping_type');
 $shipping_label = $order->get_meta('_shipping_type_label');
 $cart_weight = $order->get_meta('_cart_weight');
 
 if (!empty($shipping_type)) {
 echo '<div class="shipping-type-' . esc_attr($shipping_type) . '">';
 echo '<p><strong>' . $this->get_translated_text('Shipping Type:', 'نوع ارسال:') . '</strong> ' . esc_html($shipping_label) . '</p>';
 if ($shipping_type === 'regular' && $cart_weight > 0) {
 echo '<p><strong>' . $this->get_translated_text('Total Order Weight:', 'وزن کل سفارش:') . '</strong> ' . esc_html($cart_weight) . ' ' . $this->get_translated_text('grams', 'گرم') . '</p>';
 }
 echo '</div>';
 }
 }
 
 public function display_order_meta_in_frontend($order) {
 $shipping_type = $order->get_meta('_shipping_type');
 $shipping_label = $order->get_meta('_shipping_type_label');
 $cart_weight = $order->get_meta('_cart_weight');
 
 if (!empty($shipping_type)) {
 echo '<section class="woocommerce-shipping-type">';
 echo '<h2>' . $this->get_translated_text('Shipping Information', 'اطلاعات ارسال') . '</h2>';
 echo '<p><strong>' . $this->get_translated_text('Shipping Type:', 'نوع ارسال:') . '</strong> ' . esc_html($shipping_label) . '</p>';
 if ($shipping_type === 'regular' && $cart_weight > 0) {
 echo '<p><strong>' . $this->get_translated_text('Total Order Weight:', 'وزن کل سفارش:') . '</strong> ' . esc_html($cart_weight) . ' ' . $this->get_translated_text('grams', 'گرم') . '</p>';
 }
 echo '</section>';
 }
 }
 
 public function add_shipping_type_column($columns) {
 $new_columns = array();
 
 foreach ($columns as $column_name => $column_info) {
 $new_columns[$column_name] = $column_info;
 
 if ($column_name === 'order_number') {
 $new_columns['shipping_type'] = $this->get_translated_text('Shipping Type', 'نوع ارسال');
 }
 }
 
 return $new_columns;
 }
 
 public function populate_shipping_type_column($column, $post_id) {
 if ($column == 'shipping_type') {
 $order = wc_get_order($post_id);
 $shipping_type = $order->get_meta('_shipping_type');
 $shipping_label = $order->get_meta('_shipping_type_label');
 $cart_weight = $order->get_meta('_cart_weight');
 
 if ($shipping_type === 'cash_on_delivery') {
 echo '<span class="shipping-badge cash-on-delivery">' . esc_html($shipping_label) . '</span>';
 } elseif ($shipping_type === 'regular') {
 echo '<span class="shipping-badge regular">' . esc_html($shipping_label) . '</span>';
 if ($cart_weight > 0) {
 echo '<br><small>' . esc_html($cart_weight) . ' ' . $this->get_translated_text('g', 'گرم') . '</small>';
 }
 } else {
 echo '-';
 }
 }
 }
 
 public function remove_additional_information_tab($tabs) {
 if (isset($tabs['additional_information'])) {
 unset($tabs['additional_information']);
 }
 return $tabs;
 }
 
 public function remove_weight_from_product_attributes($product_attributes) {
 if (isset($product_attributes['weight'])) {
 unset($product_attributes['weight']);
 }
 return $product_attributes;
 }
}

function wc_specific_discount_create_files() {
 $languages_dir = WP_PLUGIN_DIR . '/woocommerce-specific-products-discount/languages';
 if (!file_exists($languages_dir)) {
 mkdir($languages_dir, 0755, true);
 }
 
 $admin_css_file = WP_PLUGIN_DIR . '/woocommerce-specific-products-discount/admin-style.css';
 if (!file_exists($admin_css_file)) {
 file_put_contents($admin_css_file, '/* Admin styles for WooCommerce Specific Products Discount */');
 }
}

register_activation_hook(__FILE__, 'wc_specific_discount_create_files');

$wc_specific_products_discount = new WC_Specific_Products_Discount();
