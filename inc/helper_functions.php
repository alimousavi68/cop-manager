<?php
//اضافه کردن اسکریپت های select2 برای یک دراپ داون حرفه ای



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
