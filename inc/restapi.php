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
    $subscription_secret_code = $request->get_param('subscription_secret_code');
    $subscription_site_url = $_SERVER['HTTP_HOST'];

    // بررسی اعتبار لایسنس
    if ($response_subscription_id = check_subscription_existence($subscription_site_url, $subscription_secret_code)) {
        $subscription_data = get_subscription_data($response_subscription_id);
        if ($subscription_data) {
            $subscription_plan_id = $subscription_data['subscription_plan_id'];
            $subscription_start_date = $subscription_data['subscription_start_date'];
            $subscription_resources_ids = $subscription_data['subscription_resources_ids'];
            // $subscription_user_id = $subscription_data['subscription_user_id'];
            // $subscription_site_url = $subscription_data['subscription_site_url'];
            // $subscription_secret_code = $subscription_data['subscription_secret_code'];

            $plan_data = get_plan_data($subscription_plan_id);
            if ($plan_data) {
                $plan_name = $plan_data['plan_name'];
                $plan_duration = $plan_data['plan_duration'];
                $plan_cron_interval = $plan_data['plan_cron_interval'];
                $plan_max_post_fetch = $plan_data['plan_max_post_fetch'];
            }

            $resources_data = get_resource_data($subscription_resources_ids);
        }

        $response_data = array(
            'plan_name' => $plan_name,
            'subscription_start_date' => $subscription_start_date,
            'plan_duration' => $plan_duration,
            'plan_cron_interval' => $plan_cron_interval,
            'plan_max_post_fetch' => $plan_max_post_fetch,
            'resources_data' => $resources_data,

        );
        return new WP_REST_Response($response_data, 200);
    } else {
        return new WP_REST_Response('License is not valid', 403);
    }
}

