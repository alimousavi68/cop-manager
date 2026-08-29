<?php
//اضافه کردن اسکریپت های select2 برای یک دراپ داون حرفه ای



//بازیابی لیست همه کاربران در قالب دارپ داون
function cop_list_users_dropdown($name, $class, $id, $selected_item)
{
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


// بازیابی لیست همه منابع در قالب دراپ‌داون (UI جدید: Resource Manager)
function cop_resources_list_dropdown($name, $class, $id, $selected_items)
{
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

    // Create mapping of all resources for quick lookup in JS
    $resources_map = array();
    $options_html = '<option value="">جستجو و افزودن منبع...</option>';

    if ($resources->have_posts()) {
        while ($resources->have_posts()) {
            $resources->the_post();
            $res_id = get_the_ID();
            $res_title = get_the_title();
            $resources_map[$res_id] = $res_title;
            $options_html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($res_id),
                esc_html($res_title)
            );
        }
        wp_reset_postdata();
    }

    // Prepare selected items HTML
    $selected_html = '';
    $inputs_html = '';
    
    if (!empty($selected_items) && is_array($selected_items)) {
        foreach ($selected_items as $res_id) {
            if (isset($resources_map[$res_id])) {
                $inputs_html .= '<input type="hidden" name="' . esc_attr($name) . '[]" value="' . esc_attr($res_id) . '">';
                $selected_html .= '<li data-id="' . esc_attr($res_id) . '" style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#fff; border:1px solid var(--cop-slate-200); border-radius:6px; margin-bottom:8px; font-size:13px; color:var(--cop-slate-700); box-shadow:0 1px 2px rgba(0,0,0,0.02);">' . esc_html($resources_map[$res_id]) . ' <button type="button" class="cop-remove-res" style="background:none; border:none; color:var(--cop-danger); cursor:pointer; font-size:16px; padding:0; line-height:1; transition:0.2s;" onmouseover="this.style.color=\'#b91c1c\'" onmouseout="this.style.color=\'var(--cop-danger)\'">&times;</button></li>';
            }
        }
    }

    $js = "
    jQuery(document).ready(function($) {
        var map = " . json_encode($resources_map) . ";
        var searchBox = $('#cop_res_search_" . esc_js($id) . "');
        var listContainer = $('#cop_res_list_" . esc_js($id) . "');
        var inputContainer = $('#cop_res_inputs_" . esc_js($id) . "');
        var inputName = '" . esc_js($name) . "[]';
        var planSelect = $('#subscription_plan_id');
        
        function getMaxRes() {
            var selectedOption = planSelect.find('option:selected');
            var maxRes = selectedOption.data('max-resources');
            if (maxRes && parseInt(maxRes) > 0) return parseInt(maxRes);
            return 9999;
        }
        
        function updateCount() {
            var currentCount = inputContainer.find('input').length;
            var maxCount = getMaxRes();
            $('#cop_res_count_" . esc_js($id) . "').text(currentCount + ' / ' + (maxCount === 9999 ? 'نامحدود' : maxCount));
            
            if (currentCount >= maxCount) {
                searchBox.prop('disabled', true);
            } else {
                searchBox.prop('disabled', false);
            }
        }
        
        searchBox.select2({
            width: '100%',
            placeholder: 'جستجو و افزودن منبع...'
        });
        
        searchBox.on('select2:select', function(e) {
            var val = e.params.data.id;
            if(!val) return;
            
            var currentCount = inputContainer.find('input').length;
            var maxCount = getMaxRes();
            
            if (currentCount >= maxCount) {
                alert('به سقف مجاز منابع در این پلن رسیده‌اید.');
                searchBox.val(null).trigger('change');
                return;
            }
            
            // Check if already exists
            if(inputContainer.find('input[value=\"' + val + '\"]').length > 0) {
                searchBox.val(null).trigger('change');
                return;
            }
            
            var title = map[val] || val;
            
            // Add hidden input
            inputContainer.append('<input type=\"hidden\" name=\"' + inputName + '\" value=\"' + val + '\">');
            
            // Add list item
            listContainer.append('<li data-id=\"' + val + '\" style=\"display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#fff; border:1px solid var(--cop-slate-200); border-radius:6px; margin-bottom:8px; font-size:13px; color:var(--cop-slate-700); box-shadow:0 1px 2px rgba(0,0,0,0.02);\">' + title + ' <button type=\"button\" class=\"cop-remove-res\" style=\"background:none; border:none; color:var(--cop-danger); cursor:pointer; font-size:16px; padding:0; line-height:1; transition:0.2s;\" onmouseover=\"this.style.color=\\'#b91c1c\\'\" onmouseout=\"this.style.color=\\'var(--cop-danger)\\'\">&times;</button></li>');
            
            searchBox.val(null).trigger('change');
            updateCount();
            
            // Scroll to bottom
            listContainer.scrollTop(listContainer[0].scrollHeight);
        });
        
        listContainer.on('click', '.cop-remove-res', function(e) {
            e.preventDefault();
            var li = $(this).closest('li');
            var val = li.data('id');
            li.remove();
            inputContainer.find('input[value=\"' + val + '\"]').remove();
            updateCount();
        });
        
        planSelect.on('change', function() {
            updateCount();
        });
        
        updateCount();
    });
    ";

    wp_add_inline_script('select2-js', $js);

    $output = '<div class="cop-resource-manager" style="border: 1px solid var(--cop-slate-200); border-radius: 8px; overflow: hidden;">';
    
    // Header & Search
    $output .= '<div style="background: var(--cop-slate-50); padding: 12px; border-bottom: 1px solid var(--cop-slate-200);">';
    $output .= '<select id="cop_res_search_' . esc_attr($id) . '">' . $options_html . '</select>';
    $output .= '</div>';
    
    // List container
    $output .= '<div style="padding: 12px; background: #fff;">';
    $output .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-size:12px; color:var(--cop-slate-500); font-weight:600;">';
    $output .= '<span>منابع انتخاب شده</span>';
    $output .= '<span id="cop_res_count_' . esc_attr($id) . '" class="cop-badge cop-badge-neutral" style="font-size:11px;">0</span>';
    $output .= '</div>';
    
    $output .= '<div id="cop_res_inputs_' . esc_attr($id) . '">' . $inputs_html . '</div>';
    $output .= '<ul id="cop_res_list_' . esc_attr($id) . '" style="margin:0; padding:4px; list-style:none; max-height:220px; overflow-y:auto; background:var(--cop-slate-50); border-radius:6px; min-height:60px;">' . $selected_html . '</ul>';
    
    $output .= '</div></div>';

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
        $db_site_url = get_post_meta($subscription_post->ID, 'subscription_site_url', true);
        
        if (cop_normalize_url($db_site_url) === cop_normalize_url($subscription_site_url)) {
            // Use absolute expiry date (Phase 9)
            $expiry_date = get_post_meta($subscription_post->ID, 'subscription_expiry_date', true);

            // Fallback: if no expiry_date set yet, calculate and save it (migration on-the-fly)
            if (empty($expiry_date)) {
                $expiry_date = cop_calculate_expiry_date($subscription_post->ID);
                if ($expiry_date) {
                    update_post_meta($subscription_post->ID, 'subscription_expiry_date', $expiry_date);
                }
            }

            // B-08 Fix: if still no expiry (no plan set), reject immediately
            if (empty($expiry_date)) {
                $result = false;
                wp_reset_postdata();
                return $result;
            }

            $plan_id = get_post_meta($subscription_post->ID, 'subscription_plan_id', true);
            $plan_data = get_plan_data($plan_id);
            $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;
            $grace_end_ts = strtotime($expiry_date) + ($plan_grace_period * DAY_IN_SECONDS);

            if (current_time('timestamp') > $grace_end_ts) {
                $result = false;
            } else {
                $result = $subscription_post->ID;
            }
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
        
        // Get absolute expiry date (Phase 9), migrate on-the-fly if missing
        $expiry_date = get_post_meta($post_obj->ID, 'subscription_expiry_date', true);
        if (empty($expiry_date)) {
            $expiry_date = cop_calculate_expiry_date($post_obj->ID);
            if ($expiry_date) {
                update_post_meta($post_obj->ID, 'subscription_expiry_date', $expiry_date);
            }
        }
        
        $subscription_data = array(
            'subscription_start_date' => $post_obj->post_date,
            'subscription_expiry_date' => $expiry_date,
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

/**
 * Calculate expiry date from post_date + plan_duration + extra_days
 * Used for on-the-fly migration of old subscriptions.
 * @return string|false MySQL datetime or false if data is missing/invalid.
 */
function cop_calculate_expiry_date($post_id) {
    $start_date = get_post_field('post_date', $post_id);
    if (empty($start_date) || $start_date === '0000-00-00 00:00:00') return false;
    
    $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
    $extra_days = (int) get_post_meta($post_id, 'subscription_extra_days', true);
    
    if (!$plan_id) return false;
    
    $plan_duration = (int) get_post_meta($plan_id, 'plan_duration', true);
    if ($plan_duration <= 0) return false;
    
    $start_ts = strtotime($start_date);
    if (!$start_ts || $start_ts === false) return false;
    
    $expiry_ts = $start_ts + (($plan_duration + $extra_days) * DAY_IN_SECONDS);
    return date('Y-m-d H:i:s', $expiry_ts);
}

/**
 * Renew a subscription by extending its expiry_date.
 * If already expired: new expiry = today + plan_duration.
 * If still active:    new expiry = current_expiry + plan_duration.
 * This allows pre-booking renewals without wasting any days.
 *
 * @param int $post_id  Subscription post ID
 * @param int $days     Number of days to extend (if 0, uses plan's duration)
 * @return string|false New expiry date string on success, false on failure
 */
function cop_renew_subscription($post_id, $days = 0) {
    $current_expiry = get_post_meta($post_id, 'subscription_expiry_date', true);
    
    // If no expiry date, calculate it first
    if (empty($current_expiry)) {
        $current_expiry = cop_calculate_expiry_date($post_id);
    }
    
    // If still no expiry (no plan assigned), use today
    if (empty($current_expiry)) {
        $current_expiry = current_time('mysql');
    }
    
    // Determine renewal days
    if ($days <= 0) {
        $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
        $days = $plan_id ? (int) get_post_meta($plan_id, 'plan_duration', true) : 0;
    }
    
    if ($days <= 0) return false;
    
    $current_ts = current_time('timestamp');
    $expiry_ts = strtotime($current_expiry);
    
    // If expired: start fresh from today. If active: extend from current expiry.
    $base_ts = ($expiry_ts < $current_ts) ? $current_ts : $expiry_ts;
    $new_expiry_ts = $base_ts + ($days * DAY_IN_SECONDS);
    $new_expiry = date('Y-m-d H:i:s', $new_expiry_ts);
    
    update_post_meta($post_id, 'subscription_expiry_date', $new_expiry);
    
    // Invalidate transient cache
    $url = get_post_meta($post_id, 'subscription_site_url', true);
    $secret = get_post_meta($post_id, 'subscription_secret_code', true);
    if ($url && $secret) {
        delete_transient('cop_val_' . md5($url . '_' . $secret));
    }
    
    return $new_expiry;
}

/**
 * Migrate all existing subscriptions to absolute expiry dates.
 * Safe to run multiple times (skips already-migrated ones).
 */
function cop_migrate_subscriptions_to_expiry_date() {
    if (get_option('cop_expiry_migration_done')) return;
    
    $all_subs = get_posts(array(
        'post_type' => 'subscriptions',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    foreach ($all_subs as $post_id) {
        $existing = get_post_meta($post_id, 'subscription_expiry_date', true);
        if (!empty($existing)) continue; // already migrated
        
        $expiry = cop_calculate_expiry_date($post_id);
        if ($expiry) {
            update_post_meta($post_id, 'subscription_expiry_date', $expiry);
        }
    }
    
    update_option('cop_expiry_migration_done', '1');
}
add_action('admin_init', 'cop_migrate_subscriptions_to_expiry_date');

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
            $resources_data = get_resource_data($subscription_resources_ids);
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
    if (empty($resource_ids) || !is_array($resource_ids)) {
        return array();
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
                'escape_elements' => get_post_meta(get_the_ID(), 'escape_elements', true) ?: '[]',
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

    // Parse attributes like [itemprop="value"] or [id*="advert"]
    if (preg_match_all('/\[([a-zA-Z\-]+)([*^$]?)=["\']?([^"\'\]]+)["\']?\]/', $part, $matches)) {
        foreach ($matches[1] as $i => $attr) {
            $op = $matches[2][$i];
            $val = $matches[3][$i];
            if ($op === '*') {
                $conditions[] = "contains(@$attr, '$val')";
            } elseif ($op === '^') {
                $conditions[] = "starts-with(@$attr, '$val')";
            } elseif ($op === '$') {
                $conditions[] = "contains(@$attr, '$val')"; // XPath 1.0 doesn't have ends-with, use contains as fallback
            } else {
                $conditions[] = "@$attr='$val'";
            }
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

/**
 * Normalizes a URL by converting to lowercase, trimming trailing slash, and removing protocols/www prefix.
 */
function cop_normalize_url($url) {
    $url = trim($url);
    $url = strtolower($url);
    $url = rtrim($url, '/');
    $url = preg_replace('/^https?:\/\/(www\.)?/', '', $url);
    return $url;
}





/**
 * بررسی HTTP status code یک URL با یک درخواست HEAD سبک
 *
 * @param string $url
 * @return int کد HTTP یا ۰ در صورت خطا
 */
function cop_check_url_http_status($url) {
    $response = wp_remote_head($url, array(
        'timeout'     => 8,
        'redirection' => 3,
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    ));
    if (is_wp_error($response)) {
        return 0;
    }
    return (int) wp_remote_retrieve_response_code($response);
}

/**
 * تعیین هوشمند آدرس نهایی یک آیتم فید با اولویت‌بندی guid و link
 *
 * منطق:
 * ۱. اگر guid آدرس کامل (absolute) بود → مستقیم استفاده شود
 * ۲. اگر guid آدرس نسبی بود → با source_root_link ترکیب شود؛
 *    سپس HTTP status چک می‌شود؛ اگر ۴۰۴ یا خطا داد → به link فال‌بک می‌شود
 * ۳. اگر guid خالی بود → از link استفاده شود
 * ۴. اگر هر دو خالی بودند → رشته خالی برگردانده می‌شود
 *
 * @param string $guid_str   مقدار تگ <guid> از فید
 * @param string $link_str   مقدار تگ <link> از فید
 * @param string $root_url   آدرس پایه (root) منبع خبری (مثلاً https://safheeghtesad.ir)
 * @return string  آدرس نهایی معتبر یا رشته خالی
 */
function cop_resolve_feed_item_url($guid_str, $link_str, $root_url) {
    $guid_str = trim((string) $guid_str);
    $link_str = trim((string) $link_str);
    $root_url = rtrim(trim((string) $root_url), '/');

    // حالت ۱: guid آدرس کامل و معتبر است
    if (!empty($guid_str) && preg_match('#^https?://#i', $guid_str)) {
        return $guid_str;
    }

    // حالت ۲: guid آدرس نسبی است، ترکیب با root_url
    if (!empty($guid_str) && !empty($root_url)) {
        $merged_url = $root_url . '/' . ltrim($guid_str, '/');
        $status = cop_check_url_http_status($merged_url);
        // HTTP 2xx و 3xx به عنوان موفق تلقی می‌شوند
        if ($status >= 200 && $status < 400) {
            return $merged_url;
        }
        // آدرس ترکیبی نامعتبر بود → فال‌بک به link
    }

    // حالت ۳: از link استفاده می‌شود
    if (!empty($link_str)) {
        return $link_str;
    }

    // حالت ۴: هیچ آدرس معتبری یافت نشد
    return '';
}
