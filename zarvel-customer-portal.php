<?php
/**
 * Plugin Name: Zarvel Customer Portal
 * Description: Frontend Zarvel customer login, registration, and private custom product access.
 * Version: 0.1.0
 * Author: Zarvel Creatives
 * Text Domain: zarvel-customer-portal
 */

defined('ABSPATH') || exit;

const ZARVEL_PORTAL_COOKIE = 'zarvel_portal_session';
const ZARVEL_PORTAL_SESSION_DAYS = 14;
const ZARVEL_PORTAL_CUSTOMER_TYPE = 'zarvel_customer';

function zarvel_portal_design_request_type() {
    return defined('ZARVEL_DESIGN_REQUEST_TYPE') ? ZARVEL_DESIGN_REQUEST_TYPE : 'zarvel_design_req';
}

function zarvel_portal_account_url() {
    return home_url('/my-account/');
}

function zarvel_portal_register_customer_type() {
    register_post_type(ZARVEL_PORTAL_CUSTOMER_TYPE, array(
        'labels' => array(
            'name'          => __('Zarvel Customers', 'zarvel-customer-portal'),
            'singular_name' => __('Zarvel Customer', 'zarvel-customer-portal'),
            'menu_name'     => __('Zarvel Customers', 'zarvel-customer-portal'),
        ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => false,
        'supports'        => array('title'),
        'capability_type' => 'post',
    ));
}
add_action('init', 'zarvel_portal_register_customer_type');

function zarvel_portal_admin_overview() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $view_customer_id = isset($_GET['zarvel_customer']) ? absint(wp_unslash($_GET['zarvel_customer'])) : 0;

    if ($view_customer_id && get_post_type($view_customer_id) === ZARVEL_PORTAL_CUSTOMER_TYPE) {
        zarvel_portal_admin_customer_view($view_customer_id);
        return;
    }

    $customer_counts = wp_count_posts(ZARVEL_PORTAL_CUSTOMER_TYPE);
    $customer_count = !empty($customer_counts->private) ? (int) $customer_counts->private : 0;
    $request_counts = post_type_exists(zarvel_portal_design_request_type()) ? wp_count_posts(zarvel_portal_design_request_type()) : null;
    $request_count = $request_counts && !empty($request_counts->private) ? (int) $request_counts->private : 0;
    $customer_ids = get_posts(array(
        'post_type'      => ZARVEL_PORTAL_CUSTOMER_TYPE,
        'post_status'    => 'private',
        'fields'         => 'ids',
        'posts_per_page' => 100,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Zarvel Portal', 'zarvel-customer-portal'); ?></h1>
        <p><?php esc_html_e('Manage Zarvel customer emails, private products, orders, and design request follow-up here in WordPress.', 'zarvel-customer-portal'); ?></p>
        <div class="zc-portal-admin-metrics">
            <article>
                <span><?php esc_html_e('Portal Customers', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($customer_count)); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Design Requests', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($request_count)); ?></strong>
            </article>
        </div>
        <p class="zc-portal-admin-actions">
            <a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=' . zarvel_portal_design_request_type())); ?>">
                <?php esc_html_e('Open Design Requests', 'zarvel-customer-portal'); ?>
            </a>
            <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>">
                <?php esc_html_e('Open Products', 'zarvel-customer-portal'); ?>
            </a>
        </p>
        <h2><?php esc_html_e('Customers', 'zarvel-customer-portal'); ?></h2>
        <div class="zc-portal-admin-table">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Gmail', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Orders', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Paid Orders', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Total Spent', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Design Requests', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Private Products', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Added', 'zarvel-customer-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$customer_ids) : ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('Customers will appear here after they verify a Gmail code.', 'zarvel-customer-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($customer_ids as $customer_id) : ?>
                        <?php
                        $email = zarvel_portal_customer_email($customer_id);
                        $orders = zarvel_portal_get_customer_orders($email, -1);
                        $order_summary = zarvel_portal_paid_order_summary($orders);
                        $request_ids = zarvel_portal_get_design_request_ids($email);
                        $product_ids = zarvel_portal_get_private_product_ids($email);
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(add_query_arg('zarvel_customer', $customer_id, admin_url('admin.php?page=zarvel-portal'))); ?>">
                                        <?php echo esc_html($email); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html(number_format_i18n(count($orders))); ?></td>
                            <td><?php echo esc_html(number_format_i18n($order_summary['count'])); ?></td>
                            <td><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($order_summary['spent']) : '$' . number_format($order_summary['spent'], 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n(count($request_ids))); ?></td>
                            <td><?php echo esc_html(number_format_i18n(count($product_ids))); ?></td>
                            <td><?php echo esc_html(get_the_date('', $customer_id)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function zarvel_portal_admin_customer_view($customer_id) {
    $email = zarvel_portal_customer_email($customer_id);
    $orders = zarvel_portal_get_customer_orders($email, -1);
    $order_summary = zarvel_portal_paid_order_summary($orders);
    $request_ids = zarvel_portal_get_design_request_ids($email);
    $product_ids = zarvel_portal_get_private_product_ids($email);
    ?>
    <div class="wrap">
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=zarvel-portal')); ?>">
                <?php esc_html_e('Back To Customers', 'zarvel-customer-portal'); ?>
            </a>
        </p>
        <h1><?php echo esc_html($email); ?></h1>
        <div class="zc-portal-admin-metrics">
            <article>
                <span><?php esc_html_e('Orders', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo esc_html(number_format_i18n(count($orders))); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Paid Orders', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($order_summary['count'])); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Total Spent', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo wp_kses_post(function_exists('wc_price') ? wc_price($order_summary['spent']) : '$' . number_format($order_summary['spent'], 2)); ?></strong>
            </article>
            <article>
                <span><?php esc_html_e('Private Products', 'zarvel-customer-portal'); ?></span>
                <strong><?php echo esc_html(number_format_i18n(count($product_ids))); ?></strong>
            </article>
        </div>
        <h2><?php esc_html_e('Design Requests', 'zarvel-customer-portal'); ?></h2>
        <div class="zc-portal-admin-table">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Request', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Deposit Status', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Deposit Order', 'zarvel-customer-portal'); ?></th>
                        <th><?php esc_html_e('Submitted', 'zarvel-customer-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$request_ids) : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e('No design requests are linked to this Gmail yet.', 'zarvel-customer-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($request_ids as $request_id) : ?>
                        <?php $payment = zarvel_portal_design_request_payment_status($request_id); ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($request_id)); ?>">
                                    <?php echo esc_html(get_the_title($request_id)); ?>
                                </a>
                            </td>
                            <td>
                                <span class="zc-portal-admin-tag <?php echo esc_attr($payment['class']); ?>">
                                    <?php echo esc_html($payment['label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['order'] && current_user_can('edit_shop_orders')) : ?>
                                    <a href="<?php echo esc_url($payment['order']->get_edit_order_url()); ?>">
                                        <?php echo esc_html('#' . $payment['order']->get_order_number()); ?>
                                    </a>
                                <?php else : ?>
                                    <?php esc_html_e('None', 'zarvel-customer-portal'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(get_the_date('', $request_id)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

add_action('admin_head-toplevel_page_zarvel-portal', function () {
    ?>
    <style>
        .zc-portal-admin-metrics { display: flex; gap: 16px; flex-wrap: wrap; margin: 22px 0 14px; }
        .zc-portal-admin-metrics article { min-width: 180px; padding: 18px 20px; border: 1px solid #dcdcde; background: #fff; }
        .zc-portal-admin-metrics span { display: block; margin-bottom: 8px; color: #50575e; font-weight: 600; }
        .zc-portal-admin-metrics strong { display: block; font-size: 30px; line-height: 1; }
        .zc-portal-admin-actions { margin: 0 0 26px; }
        .zc-portal-admin-table { max-width: 1280px; }
        .zc-portal-admin-table td, .zc-portal-admin-table th { vertical-align: middle; }
        .zc-portal-admin-tag { display: inline-flex; min-height: 24px; align-items: center; padding: 0 9px; border-radius: 999px; background: #fde2e2; color: #8a1111; font-weight: 600; }
        .zc-portal-admin-tag.is-paid { background: #dff5e5; color: #075b24; }
        .zc-portal-admin-tag.is-pending { background: #fff1cf; color: #6f4b00; }
    </style>
    <?php
});

add_action('admin_menu', function () {
    add_menu_page(
        __('Zarvel Portal', 'zarvel-customer-portal'),
        __('Zarvel Portal', 'zarvel-customer-portal'),
        'edit_posts',
        'zarvel-portal',
        'zarvel_portal_admin_overview',
        'dashicons-id-alt',
        26
    );
});

function zarvel_portal_find_customer_by_email($email) {
    $email = strtolower(sanitize_email($email));

    if (!$email) {
        return 0;
    }

    $customer_ids = get_posts(array(
        'post_type'      => ZARVEL_PORTAL_CUSTOMER_TYPE,
        'post_status'    => 'private',
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'meta_key'       => '_zarvel_portal_email',
        'meta_value'     => $email,
    ));

    return $customer_ids ? (int) $customer_ids[0] : 0;
}

function zarvel_portal_customer_email($customer_id) {
    return strtolower(sanitize_email((string) get_post_meta($customer_id, '_zarvel_portal_email', true)));
}

function zarvel_portal_is_gmail($email) {
    $email = strtolower(sanitize_email($email));

    return is_email($email) && str_ends_with($email, '@gmail.com');
}

function zarvel_portal_cookie_args($expires) {
    return array(
        'expires'  => $expires,
        'path'     => COOKIEPATH ?: '/',
        'domain'   => COOKIE_DOMAIN,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    );
}

function zarvel_portal_set_customer_session($customer_id) {
    $token = wp_generate_password(48, false, false);
    $expires = time() + (DAY_IN_SECONDS * ZARVEL_PORTAL_SESSION_DAYS);

    update_post_meta($customer_id, '_zarvel_portal_session_hash', wp_hash($token));
    update_post_meta($customer_id, '_zarvel_portal_session_expires', $expires);

    setcookie(ZARVEL_PORTAL_COOKIE, $customer_id . '|' . $token, zarvel_portal_cookie_args($expires));
    $_COOKIE[ZARVEL_PORTAL_COOKIE] = $customer_id . '|' . $token;
}

function zarvel_portal_clear_customer_session() {
    $customer_id = zarvel_portal_current_customer_id();

    if ($customer_id) {
        delete_post_meta($customer_id, '_zarvel_portal_session_hash');
        delete_post_meta($customer_id, '_zarvel_portal_session_expires');
    }

    setcookie(ZARVEL_PORTAL_COOKIE, '', zarvel_portal_cookie_args(time() - HOUR_IN_SECONDS));
    unset($_COOKIE[ZARVEL_PORTAL_COOKIE]);
}

function zarvel_portal_current_customer_id() {
    if (empty($_COOKIE[ZARVEL_PORTAL_COOKIE])) {
        return 0;
    }

    $cookie_parts = explode('|', sanitize_text_field(wp_unslash($_COOKIE[ZARVEL_PORTAL_COOKIE])), 2);
    $customer_id = !empty($cookie_parts[0]) ? absint($cookie_parts[0]) : 0;
    $token = !empty($cookie_parts[1]) ? $cookie_parts[1] : '';
    $stored_hash = $customer_id ? (string) get_post_meta($customer_id, '_zarvel_portal_session_hash', true) : '';
    $expires = $customer_id ? (int) get_post_meta($customer_id, '_zarvel_portal_session_expires', true) : 0;

    if (
        !$customer_id ||
        !$token ||
        get_post_type($customer_id) !== ZARVEL_PORTAL_CUSTOMER_TYPE ||
        !$stored_hash ||
        !$expires ||
        $expires < time() ||
        !hash_equals($stored_hash, wp_hash($token))
    ) {
        return 0;
    }

    return $customer_id;
}

function zarvel_portal_current_customer_email() {
    $customer_id = zarvel_portal_current_customer_id();

    return $customer_id ? zarvel_portal_customer_email($customer_id) : '';
}

function zarvel_portal_redirect_with_status($status, $args = array()) {
    $args['portal_status'] = sanitize_key($status);
    wp_safe_redirect(add_query_arg($args, zarvel_portal_account_url()));
    exit;
}

function zarvel_portal_login_code_key($email) {
    return 'zarvel_portal_code_' . md5(strtolower(sanitize_email($email)));
}

function zarvel_portal_create_customer($email) {
    $email = strtolower(sanitize_email($email));
    $customer_id = zarvel_portal_find_customer_by_email($email);

    if ($customer_id) {
        return $customer_id;
    }

    $customer_id = wp_insert_post(array(
        'post_type'   => ZARVEL_PORTAL_CUSTOMER_TYPE,
        'post_status' => 'private',
        'post_title'  => $email,
    ), true);

    if (is_wp_error($customer_id) || !$customer_id) {
        return 0;
    }

    update_post_meta($customer_id, '_zarvel_portal_email', $email);

    return (int) $customer_id;
}

function zarvel_portal_send_login_code($email) {
    $email = strtolower(sanitize_email($email));
    $rate_key = 'zarvel_portal_rate_' . md5($email . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    if (get_transient($rate_key)) {
        return false;
    }

    $code = (string) wp_rand(100000, 999999);
    set_transient(zarvel_portal_login_code_key($email), array(
        'hash'     => wp_hash_password($code),
        'attempts' => 0,
    ), 10 * MINUTE_IN_SECONDS);
    set_transient($rate_key, true, MINUTE_IN_SECONDS);

    return wp_mail(
        $email,
        __('Your Zarvel sign-in code', 'zarvel-customer-portal'),
        sprintf(
            /* translators: %s: six digit account login code. */
            __("Your Zarvel sign-in code is %s.\n\nThis code expires in 10 minutes. If you did not request it, you can ignore this email.", 'zarvel-customer-portal'),
            $code
        )
    );
}

function zarvel_portal_verify_login_code($email, $code) {
    $email = strtolower(sanitize_email($email));
    $code = preg_replace('/\D+/', '', (string) $code);
    $code_state = get_transient(zarvel_portal_login_code_key($email));

    if (!$code_state || empty($code_state['hash']) || strlen($code) !== 6) {
        return new WP_Error('code_invalid', __('That code is wrong or expired.', 'zarvel-customer-portal'));
    }

    $attempts = !empty($code_state['attempts']) ? (int) $code_state['attempts'] : 0;

    if ($attempts >= 5 || !wp_check_password($code, $code_state['hash'])) {
        $code_state['attempts'] = $attempts + 1;
        set_transient(zarvel_portal_login_code_key($email), $code_state, 10 * MINUTE_IN_SECONDS);

        return new WP_Error('code_invalid', __('That code is wrong or expired.', 'zarvel-customer-portal'));
    }

    $customer_id = zarvel_portal_create_customer($email);

    if (!$customer_id) {
        return new WP_Error('customer_failed', __('Your Zarvel account could not be opened yet.', 'zarvel-customer-portal'));
    }

    delete_transient(zarvel_portal_login_code_key($email));
    zarvel_portal_set_customer_session($customer_id);

    return $customer_id;
}

function zarvel_portal_handle_account_actions() {
    if (!empty($_GET['zarvel_portal_logout'])) {
        if (
            empty($_GET['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'zarvel_portal_logout')
        ) {
            zarvel_portal_redirect_with_status('security');
        }

        zarvel_portal_clear_customer_session();
        zarvel_portal_redirect_with_status('logged_out');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['zarvel_portal_action'])) {
        return;
    }

    if (
        empty($_POST['zarvel_portal_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['zarvel_portal_nonce'])), 'zarvel_portal_account')
    ) {
        zarvel_portal_redirect_with_status('security');
    }

    $action = sanitize_key(wp_unslash($_POST['zarvel_portal_action']));
    $email = isset($_POST['email']) ? strtolower(sanitize_email(wp_unslash($_POST['email']))) : '';

    if (!zarvel_portal_is_gmail($email)) {
        zarvel_portal_redirect_with_status('gmail_only');
    }

    if ($action === 'send_code') {
        if (!zarvel_portal_send_login_code($email)) {
            zarvel_portal_redirect_with_status('code_failed', array('portal_email' => $email));
        }

        zarvel_portal_redirect_with_status('code_sent', array('portal_email' => $email));
    }

    if ($action !== 'verify_code') {
        zarvel_portal_redirect_with_status('failed');
    }

    $code = isset($_POST['code']) ? wp_unslash($_POST['code']) : '';
    $customer_id = zarvel_portal_verify_login_code($email, $code);

    if (is_wp_error($customer_id)) {
        zarvel_portal_redirect_with_status('code_invalid', array('portal_email' => $email));
    }

    zarvel_portal_redirect_with_status('logged_in');
}
add_action('init', 'zarvel_portal_handle_account_actions', 5);

function zarvel_portal_modal_ajax_email() {
    check_ajax_referer('zarvel_portal_design_modal', 'nonce');

    $email = isset($_POST['email']) ? strtolower(sanitize_email(wp_unslash($_POST['email']))) : '';

    if (!zarvel_portal_is_gmail($email)) {
        wp_send_json_error(array('message' => __('Enter a Gmail address ending in @gmail.com.', 'zarvel-customer-portal')), 400);
    }

    return $email;
}

function zarvel_portal_modal_send_code_ajax() {
    $email = zarvel_portal_modal_ajax_email();

    if (!zarvel_portal_send_login_code($email)) {
        wp_send_json_error(array('message' => __('The code could not be sent yet. Wait a minute and try again.', 'zarvel-customer-portal')), 429);
    }

    wp_send_json_success(array('message' => __('Code sent. Check your Gmail.', 'zarvel-customer-portal')));
}
add_action('wp_ajax_zarvel_portal_modal_send_code', 'zarvel_portal_modal_send_code_ajax');
add_action('wp_ajax_nopriv_zarvel_portal_modal_send_code', 'zarvel_portal_modal_send_code_ajax');

function zarvel_portal_modal_verify_code_ajax() {
    $email = zarvel_portal_modal_ajax_email();
    $code = isset($_POST['code']) ? wp_unslash($_POST['code']) : '';
    $customer_id = zarvel_portal_verify_login_code($email, $code);

    if (is_wp_error($customer_id)) {
        wp_send_json_error(array('message' => $customer_id->get_error_message()), 400);
    }

    wp_send_json_success(array(
        'email'   => $email,
        'message' => __('You are logged in. Sending your design request now.', 'zarvel-customer-portal'),
    ));
}
add_action('wp_ajax_zarvel_portal_modal_verify_code', 'zarvel_portal_modal_verify_code_ajax');
add_action('wp_ajax_nopriv_zarvel_portal_modal_verify_code', 'zarvel_portal_modal_verify_code_ajax');

function zarvel_portal_status_message() {
    $status = isset($_GET['portal_status']) ? sanitize_key(wp_unslash($_GET['portal_status'])) : '';
    $messages = array(
        'logged_in'     => __('Welcome back.', 'zarvel-customer-portal'),
        'logged_out'    => __('You are logged out.', 'zarvel-customer-portal'),
        'code_sent'     => __('Check your email for your Zarvel sign-in code.', 'zarvel-customer-portal'),
        'gmail_only'    => __('Enter a Gmail address ending in @gmail.com.', 'zarvel-customer-portal'),
        'code_invalid'  => __('That code is wrong or expired. Request a new code if needed.', 'zarvel-customer-portal'),
        'code_failed'   => __('The sign-in code could not be sent yet. Wait a minute and try again.', 'zarvel-customer-portal'),
        'security'      => __('Please try again.', 'zarvel-customer-portal'),
        'failed'        => __('The account request could not be completed.', 'zarvel-customer-portal'),
    );

    if (empty($messages[$status])) {
        return '';
    }

    $error_statuses = array('gmail_only', 'code_invalid', 'code_failed', 'security', 'failed');

    return sprintf(
        '<p class="zc-portal-notice%s">%s</p>',
        in_array($status, $error_statuses, true) ? ' is-error' : '',
        esc_html($messages[$status])
    );
}

function zarvel_portal_get_private_product_ids($email) {
    return get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => 24,
        'meta_key'       => '_zarvel_private_customer_email',
        'meta_value'     => strtolower(sanitize_email($email)),
    ));
}

function zarvel_portal_get_design_request_ids($email) {
    $email = strtolower(sanitize_email($email));

    return get_posts(array(
        'post_type'      => zarvel_portal_design_request_type(),
        'post_status'    => 'private',
        'fields'         => 'ids',
        'posts_per_page' => 24,
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'   => '_email',
                'value' => $email,
            ),
            array(
                'key'   => '_customer_email',
                'value' => $email,
            ),
        ),
    ));
}

function zarvel_portal_design_request_deposit_action($request_id) {
    $order_id = absint(get_post_meta($request_id, '_design_deposit_order_id', true));
    $order = $order_id && function_exists('wc_get_order') ? wc_get_order($order_id) : null;

    if ($order && $order->is_paid()) {
        return array(
            'label' => __('Deposit Paid', 'zarvel-customer-portal'),
            'url'   => '',
            'class' => 'is-paid',
        );
    }

    if ($order && $order->needs_payment()) {
        return array(
            'label' => __('Pay Now', 'zarvel-customer-portal'),
            'url'   => $order->get_checkout_payment_url(),
            'class' => 'is-payable',
        );
    }

    if (function_exists('zarvel_get_design_deposit_checkout_url')) {
        return array(
            'label' => __('Pay Now', 'zarvel-customer-portal'),
            'url'   => zarvel_get_design_deposit_checkout_url($request_id),
            'class' => 'is-payable',
        );
    }

    return array(
        'label' => '',
        'url'   => '',
        'class' => '',
    );
}

function zarvel_portal_design_request_payment_status($request_id) {
    $order_id = absint(get_post_meta($request_id, '_design_deposit_order_id', true));
    $order = $order_id && function_exists('wc_get_order') ? wc_get_order($order_id) : null;

    if (!$order) {
        return array(
            'label' => __('Not Paid', 'zarvel-customer-portal'),
            'class' => 'is-unpaid',
            'order' => null,
        );
    }

    if ($order->is_paid()) {
        return array(
            'label' => __('Paid', 'zarvel-customer-portal'),
            'class' => 'is-paid',
            'order' => $order,
        );
    }

    return array(
        'label' => __('Awaiting Payment', 'zarvel-customer-portal'),
        'class' => 'is-pending',
        'order' => $order,
    );
}

function zarvel_portal_get_customer_orders($email, $limit = 12) {
    if (!function_exists('wc_get_orders')) {
        return array();
    }

    return wc_get_orders(array(
        'customer' => strtolower(sanitize_email($email)),
        'limit'    => $limit,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ));
}

function zarvel_portal_paid_order_summary($orders) {
    $summary = array(
        'count' => 0,
        'spent' => 0,
    );

    foreach ($orders as $order) {
        if (!$order || !$order->is_paid()) {
            continue;
        }

        $summary['count']++;
        $summary['spent'] += (float) $order->get_total();
    }

    return $summary;
}

function zarvel_customer_portal_shortcode() {
    wp_enqueue_style(
        'zarvel-customer-portal',
        plugin_dir_url(__FILE__) . 'assets/customer-portal.css',
        array(),
        '0.1.0'
    );

    $customer_id = zarvel_portal_current_customer_id();
    ob_start();
    echo wp_kses_post(zarvel_portal_status_message());

    if (!$customer_id) {
        $pending_email = isset($_GET['portal_email']) ? strtolower(sanitize_email(wp_unslash($_GET['portal_email']))) : '';
        $show_code_form = $pending_email && zarvel_portal_is_gmail($pending_email);
        ?>
        <div class="zc-portal-auth zc-portal-auth--single">
            <?php if (!$show_code_form) : ?>
                <form method="post" class="zc-portal-form">
                    <h2><?php esc_html_e('Continue With Gmail', 'zarvel-customer-portal'); ?></h2>
                    <?php wp_nonce_field('zarvel_portal_account', 'zarvel_portal_nonce'); ?>
                    <input type="hidden" name="zarvel_portal_action" value="send_code">
                    <label>
                        <?php esc_html_e('Gmail', 'zarvel-customer-portal'); ?>
                        <input name="email" type="email" autocomplete="email" placeholder="name@gmail.com" pattern="[^@\s]+@gmail\.com" required>
                    </label>
                    <button type="submit"><?php esc_html_e('Send Code', 'zarvel-customer-portal'); ?></button>
                </form>
            <?php else : ?>
            <form method="post" class="zc-portal-form">
                <h2><?php esc_html_e('Check Your Gmail', 'zarvel-customer-portal'); ?></h2>
                <?php wp_nonce_field('zarvel_portal_account', 'zarvel_portal_nonce'); ?>
                <input type="hidden" name="zarvel_portal_action" value="verify_code">
                <input name="email" type="hidden" value="<?php echo esc_attr($pending_email); ?>">
                <p class="zc-portal-form__email"><?php echo esc_html($pending_email); ?></p>
                <label>
                    <?php esc_html_e('6-Digit Code', 'zarvel-customer-portal'); ?>
                    <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                </label>
                <button type="submit"><?php esc_html_e('Continue', 'zarvel-customer-portal'); ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    $email = zarvel_portal_customer_email($customer_id);
    $product_ids = zarvel_portal_get_private_product_ids($email);
    $request_ids = zarvel_portal_get_design_request_ids($email);
    $orders = zarvel_portal_get_customer_orders($email);
    $logout_url = wp_nonce_url(add_query_arg('zarvel_portal_logout', '1', zarvel_portal_account_url()), 'zarvel_portal_logout');
    ?>
    <div class="zc-portal-dashboard">
        <header>
            <div>
                <p><?php esc_html_e('Zarvel Account', 'zarvel-customer-portal'); ?></p>
                <h2><?php echo esc_html($email); ?></h2>
            </div>
            <a href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Log Out', 'zarvel-customer-portal'); ?></a>
        </header>
        <section>
            <h3><?php esc_html_e('Order Status', 'zarvel-customer-portal'); ?></h3>
            <?php if (!$orders) : ?>
                <p><?php esc_html_e('Orders using this checkout email will appear here.', 'zarvel-customer-portal'); ?></p>
            <?php else : ?>
                <div class="zc-portal-orders">
                    <?php foreach ($orders as $order) : ?>
                        <article>
                            <strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
                            <span><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section>
            <h3><?php esc_html_e('Private Products', 'zarvel-customer-portal'); ?></h3>
            <?php if (!$product_ids) : ?>
                <p><?php esc_html_e('No private custom products have been shared yet.', 'zarvel-customer-portal'); ?></p>
            <?php else : ?>
                <div class="zc-portal-products">
                    <?php foreach ($product_ids as $product_id) : ?>
                        <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                            <strong><?php echo esc_html(get_the_title($product_id)); ?></strong>
                            <span><?php esc_html_e('Open Product', 'zarvel-customer-portal'); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section>
            <h3><?php esc_html_e('Design Requests', 'zarvel-customer-portal'); ?></h3>
            <?php if (!$request_ids) : ?>
                <p><?php esc_html_e('Your submitted requests will appear here after the email matches this account.', 'zarvel-customer-portal'); ?></p>
            <?php else : ?>
                <div class="zc-portal-requests">
                    <?php foreach ($request_ids as $request_id) : ?>
                        <?php $deposit_action = zarvel_portal_design_request_deposit_action($request_id); ?>
                        <article>
                            <strong><?php echo esc_html(get_the_title($request_id)); ?></strong>
                            <span><?php echo esc_html(get_the_date('', $request_id)); ?></span>
                            <?php if ($deposit_action['url']) : ?>
                                <a class="zc-portal-request-payment <?php echo esc_attr($deposit_action['class']); ?>" href="<?php echo esc_url($deposit_action['url']); ?>">
                                    <?php echo esc_html($deposit_action['label']); ?>
                                </a>
                            <?php elseif ($deposit_action['label']) : ?>
                                <b class="zc-portal-request-payment <?php echo esc_attr($deposit_action['class']); ?>">
                                    <?php echo esc_html($deposit_action['label']); ?>
                                </b>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('zarvel_customer_portal', 'zarvel_customer_portal_shortcode');
