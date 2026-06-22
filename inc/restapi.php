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

    $cache_key = 'cop_val_' . md5($subscription_site_url . '_' . $subscription_secret_code);
    $cached_response = get_transient($cache_key);

    if ($cached_response !== false) {
        if ($cached_response === 'invalid') {
            return new WP_REST_Response('License is not valid', 403);
        }
        return new WP_REST_Response($cached_response, 200);
    }

    $response_subscription_id = check_subscription_existence($subscription_site_url, $subscription_secret_code);

    error_log('im server,requested url : ' . $subscription_site_url);
    error_log('im server,requested secret code : ' . $subscription_secret_code);

    // بررسی اعتبار لایسنس
    if ($response_subscription_id) {
        $subscription_data = get_subscription_data($response_subscription_id);
        if ($subscription_data) {
            $subscription_plan_id = $subscription_data['subscription_plan_id'];
            $subscription_start_date = $subscription_data['subscription_start_date'];
            $subscription_resources_ids = $subscription_data['subscription_resources_ids'];
            $subscription_extra_days = ($subscription_data['subscription_extra_days']) ? intval($subscription_data['subscription_extra_days']) : 0;

            $plan_data = get_plan_data($subscription_plan_id);
            $plan_name = '';
            $plan_duration = 0;
            $plan_cron_interval = 0;
            $plan_max_post_fetch = 0;
            $plan_grace_period = 0;

            if ($plan_data) {
                $plan_name = $plan_data['plan_name'];
                $plan_duration = intval($plan_data['plan_duration']);
                $plan_cron_interval = intval($plan_data['plan_cron_interval']);
                $plan_max_post_fetch = intval($plan_data['plan_max_post_fetch']);
                $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;
            }

            $subscription_end_date = date('Y-m-d H:i:s', strtotime($subscription_start_date . ' + ' . $plan_duration . ' days + ' . $subscription_extra_days . ' days'));
            $resources_data = get_resource_data($subscription_resources_ids);

            // Determine status and grace days
            $current_mysql_time = current_time('mysql');
            $status = 'active';
            $grace_days_remaining = 0;

            if ($current_mysql_time > $subscription_end_date) {
                $status = 'grace_period';
                $grace_end_date = date('Y-m-d H:i:s', strtotime($subscription_end_date . ' + ' . $plan_grace_period . ' days'));
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
                'resources_data' => $resources_data,
                'license_status' => $status,
                'grace_days_remaining' => $grace_days_remaining
            );

            // Cache valid response for 12 hours
            set_transient($cache_key, $response_data, 12 * HOUR_IN_SECONDS);
            return new WP_REST_Response($response_data, 200);
        }
    }

    // Cache invalid result for 12 hours
    set_transient($cache_key, 'invalid', 12 * HOUR_IN_SECONDS);
    return new WP_REST_Response('License is not valid', 403);
}

