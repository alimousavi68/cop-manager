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
    $domain = $request->get_param('domain');
    $license_code = $request->get_param('license_code');

    // بررسی اعتبار لایسنس
    if ($response = check_license($domain, $license_code)) {
        return new WP_REST_Response( $response , 200);
    } else {
        return new WP_REST_Response('License is not valid', 403);
    }
}

function check_license($domain, $license_code)
{
    $licenses_tbl = array(
        array(
            'name' => 'co_pro',
            'days' => 30,
            'update_preiod' => 5,
            'day_max_post_publish' => 100,
        ),
        array(
            'name' => 'co_mid',
            'days' => 30,
            'update_preiod' => 10,
            'day_max_post_publish' => 50,
        )
        ,
        array(
            'name' => 'co_base',
            'days' => 30,
            'update_preiod' => 5,
            'day_max_post_publish' => 30,
        )
    );
    $subscription = array(
        array(
            'user_id' => 1,
            'site_domain' => 'http://localhost:8888/rasadi',
            'secret_code' => 'lc_rasadi',
            'license' => $licenses_tbl[0]
        ),
        array(
            'user_id' => 2,
            'site_domain' => 'http://localhost:8888/sarkhaat',
            'secret_code' => 'lc_sarkhaat',
            'license' => $licenses_tbl[1]
        )
    );


    foreach ($subscription as $item):
        if ($domain == $item['site_domain'] && $license_code == $item['secret_code']) {
            return $item;
        } else {
            return false; // یا false بر اساس بررسی‌ها

        }
    endforeach;
}
