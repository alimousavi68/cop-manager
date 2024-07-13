<?php
//اضافه کردن اسکریپت های select2 برای یک دراپ داون حرفه ایerror_log('i am server, subscription ' . )



//بازیابی لیست همه کاربران در قالب دارپ داون
function cop_list_users_dropdown($name, $class, $id, $selected_item)
{
    wp_enqueue_style('select2-css', plugins_url('assets/css/select2.min.css', dirname(__FILE__)));
    wp_enqueue_script('select2-js', plugins_url('assets/js/select2.min.js', dirname(__FILE__)), array('jquery'), '4.0.13', true);
    wp_add_inline_script('select2-js', 'jQuery(document).ready(function($) { $(".select2").select2(); });');


    // دریافت تمام کاربران وب‌سایت
    $users = get_users();

    // شروع ساخت دراپ‌داون
    $output = '<select name="' . $name . '" id="' . $id . '" class="select2 ' . $class . '">';
    $output .= '<option value="">یک کاربر انتخاب کنید...</option>';

    // اضافه کردن هر کاربر به دراپ‌داون
    foreach ($users as $user) {
        $is_selected = ($user->ID == $selected_item) ? ' selected ' : '';
        $output .= sprintf(
            '<option value="%s"' . $is_selected . '>%s (%s)</option>',
            esc_attr($user->ID), // اطمینان از امنیت خروجی
            esc_html($user->display_name), // نمایش نام نمایشی کاربر
            esc_html($user->ID) // نمایش شناسه کاربر
        );
    }

    // پایان دراپ‌داون
    $output .= '</select>';

    // چاپ دراپ‌داون
    echo $output;
}



//بازیابی لیست همه پلن ها در قالب دارپ داون
function cop_plans_list_dropdown($name, $class, $id, $selected_item)
{

    wp_enqueue_style('select2-css', plugins_url('assets/css/select2.min.css', dirname(__FILE__)));
    wp_enqueue_script('select2-js', plugins_url('assets/js/select2.min.js', dirname(__FILE__)), array('jquery'), '4.0.13', true);
    wp_add_inline_script('select2-js', 'jQuery(document).ready(function($) { $(".select2").select2(); });');

    $args = array(
        'post_type' => 'plans',
        'post_status' => 'publish',
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => '-1',
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true
    );
    $plans = new WP_Query($args);

    if ($plans->have_posts())
        // شروع ساخت دراپ‌داون
        $output = '<select name="' . $name . '" id="' . $id . '" class="select2 ' . $class . '">';
    $output .= '<option value="">یک کاربر انتخاب کنید...</option>';

    // اضافه کردن هر کاربر به دراپ‌داون
    while ($plans->have_posts()) {
        $plans->the_post();

        $is_selected = (get_the_ID() == $selected_item) ? ' selected ' : '';
        $output .= sprintf(
            '<option value="%s"' . $is_selected . '>%s</option>',
            esc_attr(get_the_ID()),
            esc_html(get_the_title()),
        );
    }

    // پایان دراپ‌داون
    $output .= '</select>';

    // چاپ دراپ‌داون
    echo $output;
}


//بازیابی لیست همه منابع در قالب دارپ داون
function cop_resources_list_dropdown($name, $class, $id, $selected_items)
{
    wp_enqueue_style('select2-css', plugins_url('assets/css/select2.min.css', dirname(__FILE__)));
    wp_enqueue_script('select2-js', plugins_url('assets/js/select2.min.js', dirname(__FILE__)), array('jquery'), '4.0.13', true);
    $maximumSelectionLength = 5;
    $multiple_js_query =
        "jQuery(document).ready(function($) {
            $('.resource_multiple').select2({
                placeholder: 'انتخاب منابع',
                allowClear: true,
                maximumSelectionLength: " . $maximumSelectionLength . ",  
                width: 'resolve'
            });
        });";
    wp_add_inline_script('select2-multiple-js', $multiple_js_query);

    $args = array(
        'post_type' => 'resource',
        'post_status' => 'publish',
        'order' => 'DESC',
        'orderby' => 'date',
        'posts_per_page' => '-1',
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true
    );
    $resources = new WP_Query($args);

    $output = '<select name="' . $name . '[]" id="' . $id . '" class="select2 resource_multiple ' . $class . '" multiple="multiple" style="width: 100%;">';
    $output .= '<option value="">منابع را انتخاب کنید</option>';

    if ($resources->have_posts()) {
        while ($resources->have_posts()) {
            $resources->the_post();
            $is_selected = in_array(get_the_ID(), $selected_items) ? ' selected="selected"' : '';
            $output .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr(get_the_ID()),
                $is_selected,
                esc_html(get_the_title())
            );
        }
    }

    $output .= '</select>';
    echo $output;
}

// Generate a secret code
function generate_secret_code($length = 16)
{
    // مشخص کردن کاراکترهای ممکن برای کد
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $charactersLength = strlen($characters);
    $randomString = 'i8-';

    // تولید رشته تصادفی با طول مشخص
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    $randomString .= '#';

    return $randomString;
}


// Check license is valid or not
function check_subscription_existence($subscription_site_url, $subscription_secret_code)
{

    $args = array(
        'post_type' => 'subscriptions',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_qurty' => array(
            'relation' => 'AND',
            array(
                'key' => 'subscription_site_url',
                'value' => $subscription_site_url,
                'compare' => '='
            ),
            array(
                'key' => 'subscription_secret_code',
                'value' => $subscription_secret_code,
                'compare' => '='
            )
        )
    );
    $subscription = new WP_Query($args);
    if ($subscription->have_posts()) {
        // error_log('i am server, subscription is valid');
        return $subscription->post->ID;
    } else {
        // error_log('i am server, subscription is Noooot valid');
        return false;
    }

}


// get retirive a subscription data
function get_subscription_data($subscription_id)
{
    $args = array(
        'post_type' => 'subscriptions',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'p' => $subscription_id,
    );
    $subscription = new WP_Query($args);
    if ($subscription->have_posts()) {

        // post fileds push the array and return array
        $subscription_data = array(
            'subscription_start_date' => $subscription->post->post_date,
            'subscription_user_id' => $subscription->post->subscription_user_id,
            'subscription_site_url' => $subscription->post->subscription_site_url,
            'subscription_plan_id' => $subscription->post->subscription_plan_id,
            'subscription_resources_ids' => $subscription->post->subscription_resources_ids,
            'subscription_secret_code' => $subscription->post->subscription_secret_code,
        );
        return $subscription_data;

    } else {
        return false;
    }

}

//get retrive a plan data
function get_plan_data($plan_id)
{
    $args = array(
        'post_type' => 'plans',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'p' => $plan_id,
    );
    $plan = new WP_Query($args);
    if ($plan->have_posts()) {
        $plan_data = array(
            'plan_name' => $plan->post->post_title,
            'plan_duration' => get_post_meta($plan->post->ID, 'plan_duration', true),
            'plan_cron_interval' => get_post_meta($plan->post->ID, 'plan_cron_interval', true),
            'plan_max_post_fetch' => get_post_meta($plan->post->ID, 'plan_max_post_fetch', true),
        );
        return $plan_data;
    } else {
        return false;
    }
}

//get retrive a subscription resources data
function get_subscription_resources_data($subscription_id)
{
    // if this subscription is exist
    if (get_subscription_data($subscription_id)) {
        $subscription_resources_ids = get_post_meta($subscription_id, 'subscription_resources_ids', true);

        if ($subscription_resources_ids) {
            $resources_data = get_resources_data($subscription_resources_ids);
            return $resources_data;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// get get resource data 
function get_resource_data($resource_ids)
{
    $args = array(
        'post_type' => 'resource',
        'post_status' => 'publish',
        'post__in' => $resource_ids,
    );

    $resource = new WP_Query($args);

    if ($resource->have_posts()) {
        // return array of posts
        $resources_data = array();
        while ($resource->have_posts()) {
            $resource->the_post();
            $resources_data[] = array(
                'resource_id' => get_the_ID(),
                'resource_title' => get_the_title(),
                'title_selector' => get_post_meta(get_the_ID(), 'title_selector', true),
                'img_selector' => get_post_meta(get_the_ID(), 'img_selector', true),
                'lead_selector' => get_post_meta(get_the_ID(), 'lead_selector', true),
                'body_selector' => get_post_meta(get_the_ID(), 'body_selector', true),
                'bup_date_selector' => get_post_meta(get_the_ID(), 'bup_date_selector', true),
                'category_selector' => get_post_meta(get_the_ID(), 'category_selector', true),
                'tags_selector' => get_post_meta(get_the_ID(), 'tags_selector', true),
                'escape_elements' => get_post_meta(get_the_ID(), 'escape_elements', true),
                'source_root_link' => get_post_meta(get_the_ID(), 'source_root_link', true),
                'source_feed_link' => get_post_meta(get_the_ID(), 'source_feed_link', true),
                'need_to_merge_guid_link' => get_post_meta(get_the_ID(), 'need_to_merge_guid_link', true),
            );
        }
        return $resources_data;
    } else {
        return false;
    }
}



