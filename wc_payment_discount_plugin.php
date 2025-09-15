<?php
/**
 * Plugin Name: WooCommerce Payment Method Discount
 * Description: Provides percentage or fixed discounts based on selected payment method
 * Version: 1.0.0
 * Author: Expert Web Developer
 * Author URI: https://expertwebdeveloper.com
 * Plugin URI: https://expertwebdeveloper.com/free-woocommerce-payment-method-discount-plugin-boost-conversions-with-strategic-payment-incentives/
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 * 
 * Developer Contact:
 * Phone: +447448418213
 * Website: expertwebdeveloper.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class WC_Payment_Method_Discount {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Admin settings
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Frontend functionality
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
//         add_action('woocommerce_review_order_before_payment', array($this, 'add_discount_info'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_payment_method_discount'));
        
        // AJAX handlers
        add_action('wp_ajax_update_payment_discount', array($this, 'ajax_update_payment_discount'));
        add_action('wp_ajax_nopriv_update_payment_discount', array($this, 'ajax_update_payment_discount'));
        
        // Handle checkout form updates
        add_action('woocommerce_checkout_update_order_review', array($this, 'handle_checkout_update'));
        add_action('woocommerce_checkout_process', array($this, 'save_payment_method_to_session'));
    }
    
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Payment Method Discounts',
            'Payment Discounts',
            'manage_options',
            'wc-payment-discounts',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('wc_payment_discount_settings', 'wc_payment_discount_rules');
        register_setting('wc_payment_discount_settings', 'wc_payment_discount_enabled');
    }
    
    public function admin_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'wc_payment_discount_settings')) {
            $this->save_settings();
        }
        
        $this->display_admin_page();
    }
    
    private function save_settings() {
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        update_option('wc_payment_discount_enabled', $enabled);
        
        $rules = array();
        if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
            foreach ($_POST['payment_methods'] as $method => $data) {
                if (!empty($data['discount_value'])) {
                    $rules[$method] = array(
                        'type' => sanitize_text_field($data['type']),
                        'value' => floatval($data['discount_value']),
                        'label' => sanitize_text_field($data['label'])
                    );
                }
            }
        }
        update_option('wc_payment_discount_rules', $rules);
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    private function display_admin_page() {
        $enabled = get_option('wc_payment_discount_enabled', 0);
        $rules = get_option('wc_payment_discount_rules', array());
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        ?>
        <div class="wrap">
            <h1>Payment Method Discounts</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wc_payment_discount_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Payment Method Discounts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($enabled, 1); ?>>
                                Enable discounts based on payment method
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>Discount Rules</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Discount Type</th>
                            <th>Discount Value</th>
                            <th>Display Label</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_gateways as $gateway_id => $gateway): ?>
                        <tr>
                            <td><strong><?php echo esc_html($gateway->get_title()); ?></strong></td>
                            <td>
                                <select name="payment_methods[<?php echo esc_attr($gateway_id); ?>][type]">
                                    <option value="percentage" <?php selected(isset($rules[$gateway_id]) ? $rules[$gateway_id]['type'] : '', 'percentage'); ?>>Percentage (%)</option>
                                    <option value="fixed" <?php selected(isset($rules[$gateway_id]) ? $rules[$gateway_id]['type'] : '', 'fixed'); ?>>Fixed Amount</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" 
                                       name="payment_methods[<?php echo esc_attr($gateway_id); ?>][discount_value]" 
                                       value="<?php echo isset($rules[$gateway_id]) ? esc_attr($rules[$gateway_id]['value']) : ''; ?>"
                                       step="0.01" 
                                       min="0" 
                                       placeholder="0.00"
                                       style="width: 100px;">
                            </td>
                            <td>
                                <input type="text" 
                                       name="payment_methods[<?php echo esc_attr($gateway_id); ?>][label]" 
                                       value="<?php echo isset($rules[$gateway_id]) ? esc_attr($rules[$gateway_id]['label']) : ''; ?>"
                                       placeholder="e.g., Cash Payment Discount"
                                       style="width: 200px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="description">
                    Enter discount values for each payment method. Leave blank to disable discount for that method.
                    Percentage discounts are calculated as a percentage of the cart total.
                    Fixed discounts are applied as a flat amount.
                </p>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function enqueue_scripts() {
        if (is_admin() || !is_checkout()) {
            return;
        }
        
        // Add inline script directly without external file
        wp_enqueue_script('jquery');
        
        wp_localize_script('jquery', 'wc_payment_discount', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payment_discount_nonce')
        ));
        
        // Add inline script
        add_action('wp_footer', array($this, 'add_inline_script'));
    }
    
    public function add_inline_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var currentPaymentMethod = '';
            var isUpdating = false;
            
            function updatePaymentDiscount() {
                var selectedPayment = $('input[name="payment_method"]:checked').val();
                
                // Only update if payment method actually changed and we're not already updating
                if (selectedPayment && selectedPayment !== currentPaymentMethod && !isUpdating) {
                    isUpdating = true;
                    var previousMethod = currentPaymentMethod;
                    currentPaymentMethod = selectedPayment;
                    
                    $.ajax({
                        url: wc_payment_discount.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_payment_discount',
                            payment_method: selectedPayment,
                            nonce: wc_payment_discount.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Always trigger checkout update when payment method changes
                                // This ensures discounts are properly added/removed/switched
                                if (response.data && response.data.discount_applied) {
                                    $('body').trigger('update_checkout');
                                }
                            }
                        },
                        error: function() {
                            // Reset on error
                            currentPaymentMethod = previousMethod;
                        },
                        complete: function() {
                            isUpdating = false;
                        }
                    });
                }
            }
            
            // Listen for payment method changes only
            $(document).on('change', 'input[name="payment_method"]', function() {
                updatePaymentDiscount();
            });
            
            // Initialize current payment method on page load
            $(document).ready(function() {
                currentPaymentMethod = $('input[name="payment_method"]:checked').val() || '';
            });
        });
        </script>
        <?php
    }
    
//     public function add_discount_info() {
//         if (!get_option('wc_payment_discount_enabled', 0)) {
//             return;
//         }
        
//         $rules = get_option('wc_payment_discount_rules', array());
//         if (empty($rules)) {
//             return;
//         }
        
//         echo '<div id="payment-discount-info" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">';
//         echo '<p><strong>Payment Method Discounts Available:</strong></p>';
//         echo '<ul>';
        
//         foreach ($rules as $method_id => $rule) {
//             $gateway = WC()->payment_gateways->get_available_payment_gateways()[$method_id] ?? null;
//             if ($gateway) {
//                 $discount_text = $rule['type'] === 'percentage' ? $rule['value'] . '%' : wc_price($rule['value']);
//                 $label = !empty($rule['label']) ? $rule['label'] : $gateway->get_title() . ' Discount';
//                 echo '<li>' . esc_html($label) . ': ' . $discount_text . ' off</li>';
//             }
//         }
        
//         echo '</ul>';
//         echo '</div>';
//     }
    
    public function ajax_update_payment_discount() {
        check_ajax_referer('payment_discount_nonce', 'nonce');
        
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $previous_method = WC()->session->get('selected_payment_method');
        
        // Store selected payment method in session
        WC()->session->set('selected_payment_method', $payment_method);
        WC()->session->set('chosen_payment_method', $payment_method);
        
        // Check if this payment method has a discount rule
        $rules = get_option('wc_payment_discount_rules', array());
        $has_discount = isset($rules[$payment_method]) && !empty($rules[$payment_method]['value']);
        
        // Check if previous method had a discount
        $previous_had_discount = !empty($previous_method) && isset($rules[$previous_method]) && !empty($rules[$previous_method]['value']);
        
        // Always trigger checkout update if:
        // 1. Current method has a discount, OR
        // 2. Previous method had a discount (need to remove it), OR
        // 3. Both methods have discounts but are different (need to switch discount)
        $should_update = $has_discount || $previous_had_discount || ($payment_method !== $previous_method);
        
        wp_send_json_success(array(
            'discount_applied' => $should_update,
            'payment_method' => $payment_method,
            'has_discount' => $has_discount,
            'previous_had_discount' => $previous_had_discount
        ));
    }
    
    public function handle_checkout_update($post_data) {
        parse_str($post_data, $data);
        
        if (isset($data['payment_method'])) {
            $payment_method = sanitize_text_field($data['payment_method']);
            WC()->session->set('selected_payment_method', $payment_method);
            WC()->session->set('chosen_payment_method', $payment_method);
        }
    }
    
    public function save_payment_method_to_session() {
        if (isset($_POST['payment_method'])) {
            $payment_method = sanitize_text_field($_POST['payment_method']);
            WC()->session->set('selected_payment_method', $payment_method);
            WC()->session->set('chosen_payment_method', $payment_method);
        }
    }
    
    public function add_payment_method_discount() {
        if (!get_option('wc_payment_discount_enabled', 0)) {
            return;
        }
        
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        // Remove any existing payment method discount fees first
        $this->remove_payment_method_fees();
        
        $rules = get_option('wc_payment_discount_rules', array());
        if (empty($rules)) {
            return;
        }
        
        // Get selected payment method
        $selected_method = WC()->session->get('selected_payment_method');
        
        // Fallback to POST data if session is empty
        if (empty($selected_method) && isset($_POST['payment_method'])) {
            $selected_method = sanitize_text_field($_POST['payment_method']);
        }
        
        // Try to get from chosen payment method
        if (empty($selected_method)) {
            $chosen_payment_method = WC()->session->get('chosen_payment_method');
            if (!empty($chosen_payment_method)) {
                $selected_method = $chosen_payment_method;
            }
        }
        
        if (empty($selected_method) || !isset($rules[$selected_method])) {
            return;
        }
        
        $rule = $rules[$selected_method];
        $cart_total = WC()->cart->get_subtotal();
        
        // Calculate discount
        if ($rule['type'] === 'percentage') {
            $discount_amount = ($cart_total * $rule['value']) / 100;
        } else {
            $discount_amount = $rule['value'];
        }
        
        // Don't apply discount if it's greater than cart total
        if ($discount_amount > $cart_total) {
            $discount_amount = $cart_total;
        }
        
        if ($discount_amount > 0) {
            $label = !empty($rule['label']) ? $rule['label'] : 'Payment Method Discount';
            
            WC()->cart->add_fee($label, -$discount_amount);
        }
    }
    
    // Helper function to remove existing payment method fees
    private function remove_payment_method_fees() {
        if (!WC()->cart) {
            return;
        }
        
        $fees = WC()->cart->get_fees();
        $rules = get_option('wc_payment_discount_rules', array());
        
        // Create array of all possible discount labels
        $discount_labels = array('Payment Method Discount'); // Default label
        foreach ($rules as $rule) {
            if (!empty($rule['label'])) {
                $discount_labels[] = $rule['label'];
            }
        }
        
        // Remove fees that match payment method discount labels
        foreach ($fees as $fee_key => $fee) {
            if (in_array($fee->name, $discount_labels)) {
                unset(WC()->cart->fees_api()->fees[$fee_key]);
            }
        }
    }
}

// Initialize the plugin
new WC_Payment_Method_Discount();

// Hook to declare compatibility with WooCommerce features
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
