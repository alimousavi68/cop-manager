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
    wp_add_inline_script('select2-js', 'jQuery(document).ready(function($) { $(".select2:not(.resource_multiple)").select2(); });');

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

    $output = '<select name="' . $name . '" id="' . $id . '" class="select2 ' . $class . '">';
    $output .= '<option value="">یک پلن انتخاب کنید...</option>';

    // اضافه کردن هر پلن به دراپ‌داون
    while ($plans->have_posts()) {
        $plans->the_post();

        $is_selected = (get_the_ID() == $selected_item) ? ' selected ' : '';
        $max_res = get_post_meta(get_the_ID(), 'plan_max_resources', true);
        $output .= sprintf(
            '<option value="%s"%s data-max-resources="%s">%s</option>',
            esc_attr(get_the_ID()),
            $is_selected,
            esc_attr($max_res),
            esc_html(get_the_title()),
        );
    }
    wp_reset_postdata();

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
    
    $multiple_js_query =
        "jQuery(document).ready(function($) {
            function initResourcesSelect2() {
                var planSelect = $('#subscription_plan_id');
                var resourcesSelect = $('.resource_multiple');
                
                var selectedOption = planSelect.find('option:selected');
                var maxRes = selectedOption.data('max-resources');
                
                var select2Args = {
                    placeholder: 'انتخاب منابع',
                    allowClear: true,
                    width: 'resolve'
                };
                
                if (maxRes !== undefined && maxRes !== '') {
                    var maxLimit = parseInt(maxRes, 10);
                    if (!isNaN(maxLimit) && maxLimit > 0) {
                        select2Args.maximumSelectionLength = maxLimit;
                    }
                }
                
                if (resourcesSelect.hasClass('select2-hidden-accessible')) {
                    resourcesSelect.select2('destroy');
                }
                
                resourcesSelect.select2(select2Args);
            }

            initResourcesSelect2();

            $('#subscription_plan_id').on('change', function() {
                initResourcesSelect2();
            });
        });";
    wp_add_inline_script('select2-js', $multiple_js_query);

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
        wp_reset_postdata();
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
        'meta_query' => array(
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
    $result = false;

    if ($subscription->have_posts()) {
        $subscription_post = $subscription->post;
        $plan_data = get_plan_data($subscription_post->subscription_plan_id);
        $subscription_extra_days = get_post_meta($subscription_post->ID, 'subscription_extra_days', true) ? get_post_meta($subscription_post->ID, 'subscription_extra_days', true) : 0;
        
        $plan_duration = isset($plan_data['plan_duration']) ? intval($plan_data['plan_duration']) : 0;
        $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;
        $days_elapsed = date('Y-m-d H:i:s', strtotime($subscription_post->post_date . ' + ' . $plan_duration . ' days + ' . intval($subscription_extra_days) . ' days'));
        $grace_elapsed = date('Y-m-d H:i:s', strtotime($days_elapsed . ' + ' . $plan_grace_period . ' days'));
       
        if (current_time('mysql') > $grace_elapsed) {
            $result = false;
        } else {
            $result = $subscription_post->ID;
        }
        wp_reset_postdata();
    }

    return $result;
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
    $subscription_data = false;

    if ($subscription->have_posts()) {
        $post_obj = $subscription->post;
        $subscription_data = array(
            'subscription_start_date' => $post_obj->post_date,
            'subscription_user_id' => get_post_meta($post_obj->ID, 'subscription_user_id', true),
            'subscription_site_url' => get_post_meta($post_obj->ID, 'subscription_site_url', true),
            'subscription_plan_id' => get_post_meta($post_obj->ID, 'subscription_plan_id', true),
            'subscription_resources_ids' => get_post_meta($post_obj->ID, 'subscription_resources_ids', true),
            'subscription_secret_code' => get_post_meta($post_obj->ID, 'subscription_secret_code', true),
            'subscription_extra_days' => get_post_meta($post_obj->ID, 'subscription_extra_days', true),
        );
        wp_reset_postdata();
    }

    return $subscription_data;
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
    $plan_data = false;

    if ($plan->have_posts()) {
        $post_obj = $plan->post;
        $plan_data = array(
            'plan_name' => $post_obj->post_title,
            'plan_duration' => get_post_meta($post_obj->ID, 'plan_duration', true),
            'plan_cron_interval' => get_post_meta($post_obj->ID, 'plan_cron_interval', true),
            'plan_max_post_fetch' => get_post_meta($post_obj->ID, 'plan_max_post_fetch', true),
            'plan_max_resources' => get_post_meta($post_obj->ID, 'plan_max_resources', true),
            'plan_grace_period' => get_post_meta($post_obj->ID, 'plan_grace_period', true),
        );
        wp_reset_postdata();
    }

    return $plan_data;
}

//get retrive a subscription resources data
function get_subscription_resources_data($subscription_id)
{
    // if this subscription is exist
    $sub_data = get_subscription_data($subscription_id);
    if ($sub_data) {
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
    if (empty($resource_ids)) {
        return false;
    }
    $args = array(
        'post_type' => 'resource',
        'post_status' => 'publish',
        'post__in' => $resource_ids,
        'posts_per_page' => -1
    );

    $resource = new WP_Query($args);
    $resources_data = false;

    if ($resource->have_posts()) {
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
        wp_reset_postdata();
    }

    return $resources_data;
}




/**
 * Convert CSS selector to XPath (handles combinators, class list, IDs, nth-of-type)
 */
function cop_css_to_xpath($selector) {
    if (empty($selector)) {
        return '';
    }
    if (strpos($selector, '//') === 0) {
        return $selector; // Already XPath
    }

    $selector = trim($selector);
    $selector = preg_replace('/\s*>\s*/', ' > ', $selector); // normalize > combinators
    $selector = preg_replace('/\s+/', ' ', $selector); // normalize multiple spaces

    $parts = explode(' ', $selector);
    $xpath_parts = [];
    $next_is_child = false;

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '>') {
            $next_is_child = true;
            continue;
        }

        $xpath_part = cop_parse_single_css_part($part);
        
        if (empty($xpath_parts)) {
            $xpath_parts[] = '//' . $xpath_part;
        } else {
            if ($next_is_child) {
                $xpath_parts[] = '/' . $xpath_part;
                $next_is_child = false;
            } else {
                $xpath_parts[] = '//' . $xpath_part;
            }
        }
    }

    return implode('', $xpath_parts);
}

function cop_parse_single_css_part($part) {
    $tag = '*';
    if (preg_match('/^[a-zA-Z0-9\-]+/', $part, $matches)) {
        $tag = $matches[0];
        $part = substr($part, strlen($tag));
    }

    $conditions = [];

    // Parse IDs
    if (preg_match_all('/#([a-zA-Z0-9\-_]+)/', $part, $matches)) {
        foreach ($matches[1] as $id) {
            $conditions[] = "@id='$id'";
        }
    }

    // Parse classes
    if (preg_match_all('/\.([a-zA-Z0-9\-_]+)/', $part, $matches)) {
        foreach ($matches[1] as $class) {
            $conditions[] = "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
        }
    }

    // Parse attributes like [itemprop="value"]
    if (preg_match_all('/\[([a-zA-Z\-]+)=["\']?([^"\'\]]+)["\']?\]/', $part, $matches)) {
        foreach ($matches[1] as $i => $attr) {
            $val = $matches[2][$i];
            $conditions[] = "@$attr='$val'";
        }
    }
    
    // Parse :nth-of-type(N)
    $index = null;
    if (preg_match('/:nth-of-type\((\d+)\)/', $part, $matches)) {
        $index = $matches[1];
    }

    $result = $tag;
    if (!empty($conditions)) {
        $result .= '[' . implode(' and ', $conditions) . ']';
    }
    
    if ($index !== null) {
        $result .= '[' . $index . ']';
    }

    return $result;
}




