<?php
add_action('rest_api_init', function () {
    register_rest_route(
        'license/v1',
        '/validate/',
        array(
            'methods' => 'POST',
            'callback' => 'validate_license',
            'permission_callback' => '__return_true'
        )
    );
});

function validate_license(WP_REST_Request $request)
{
    $subscription_secret_code = sanitize_text_field($request->get_param('subscription_secret_code'));
    $subscription_site_url = esc_url_raw($request->get_param('subscription_site_url'));

    if (empty($subscription_secret_code) || empty($subscription_site_url)) {
        return new WP_REST_Response('Bad Request: Missing or invalid parameters', 400);
    }

    // Rate Limiting by IP and Secret Code using Transients (Max 30 requests per minute)
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
    $rate_limit_key_ip = 'cop_rate_limit_ip_' . md5($ip);
    $rate_limit_key_secret = 'cop_rate_limit_secret_' . md5($subscription_secret_code);
    
    $requests_ip = get_transient($rate_limit_key_ip);
    $requests_secret = get_transient($rate_limit_key_secret);

    if ($requests_ip !== false && intval($requests_ip) >= 30) {
        return new WP_REST_Response('Too Many Requests: Rate limit exceeded (IP).', 429);
    }
    if ($requests_secret !== false && intval($requests_secret) >= 30) {
        return new WP_REST_Response('Too Many Requests: Rate limit exceeded (Secret).', 429);
    }
    
    set_transient($rate_limit_key_ip, ($requests_ip === false ? 1 : intval($requests_ip) + 1), 60);
    set_transient($rate_limit_key_secret, ($requests_secret === false ? 1 : intval($requests_secret) + 1), 60);

    $cache_key = 'cop_val_' . md5($subscription_site_url . '_' . $subscription_secret_code);
    $cached_response = get_transient($cache_key);

    if ($cached_response !== false) {
        if ($cached_response === 'invalid') {
            return new WP_REST_Response('License is not valid', 403);
        }
        return new WP_REST_Response($cached_response, 200);
    }

    $response_subscription_id = check_subscription_existence($subscription_site_url, $subscription_secret_code);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('cop-manager API: requested url : ' . $subscription_site_url);
        error_log('cop-manager API: requested secret code : ' . $subscription_secret_code);
    }

    // بررسی اعتبار لایسنس
    if ($response_subscription_id) {
        $subscription_data = get_subscription_data($response_subscription_id);
        if ($subscription_data) {
            $subscription_plan_id = $subscription_data['subscription_plan_id'];
            $subscription_start_date = $subscription_data['subscription_start_date'];
            $subscription_resources_ids = $subscription_data['subscription_resources_ids'];
            $subscription_extra_days = ($subscription_data['subscription_extra_days']) ? intval($subscription_data['subscription_extra_days']) : 0;

            $plan_data = get_plan_data($subscription_plan_id);
            if (!$plan_data) {
                return new WP_REST_Response('Plan data not found or inactive', 403);
            }

            $plan_name = $plan_data['plan_name'];
            $plan_cron_interval = intval($plan_data['plan_cron_interval']);
            $plan_max_post_fetch = intval($plan_data['plan_max_post_fetch']);
            $plan_max_resources = isset($plan_data['plan_max_resources']) ? intval($plan_data['plan_max_resources']) : 0;
            $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;

            // Use absolute expiry date (Phase 9)
            $subscription_end_date = $subscription_data['subscription_expiry_date'];
            if (empty($subscription_end_date)) {
                // Fallback: calculate on-the-fly (should not normally happen after migration)
                $plan_duration = intval($plan_data['plan_duration']);
                $subscription_extra_days = ($subscription_data['subscription_extra_days']) ? intval($subscription_data['subscription_extra_days']) : 0;
                $subscription_end_date = date('Y-m-d H:i:s', strtotime($subscription_start_date . ' + ' . $plan_duration . ' days + ' . $subscription_extra_days . ' days'));
            }

            $resources_data = get_resource_data($subscription_resources_ids);

            // Determine status and grace days
            $current_mysql_time = current_time('mysql');
            $status = 'active';
            $grace_days_remaining = 0;

            if ($current_mysql_time > $subscription_end_date) {
                $status = 'grace_period';
                $grace_end_date = date('Y-m-d H:i:s', strtotime($subscription_end_date) + ($plan_grace_period * DAY_IN_SECONDS));
                $diff = strtotime($grace_end_date) - strtotime($current_mysql_time);
                $grace_days_remaining = max(0, intval(ceil($diff / (3600 * 24))));
            }


            $response_data = array(
                'plan_name' => $plan_name,
                'subscription_start_date' => $subscription_start_date,
                'subscription_end_date' => $subscription_end_date,
                'plan_duration' => $plan_duration,
                'plan_cron_interval' => $plan_cron_interval,
                'plan_max_post_fetch' => $plan_max_post_fetch,
                'plan_max_resources' => $plan_max_resources,
                'resources_data' => $resources_data,
                'license_status' => $status,
                'grace_days_remaining' => $grace_days_remaining
            );

            // Cache valid response
            $cache_duration = ($status === 'grace_period') ? HOUR_IN_SECONDS : (12 * HOUR_IN_SECONDS);
            set_transient($cache_key, $response_data, $cache_duration);
            return new WP_REST_Response($response_data, 200);
        }
    }

    // Cache invalid result for 12 hours
    set_transient($cache_key, 'invalid', 12 * HOUR_IN_SECONDS);
    return new WP_REST_Response('License is not valid', 403);
}

