<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// 1. Register dynamic cron schedules
add_filter('cron_schedules', 'cop_add_monitoring_schedules');
function cop_add_monitoring_schedules($schedules) {
    $schedules['cop_1_hour']   = array('interval' => 3600,  'display' => 'هر ۱ ساعت');
    $schedules['cop_3_hours']  = array('interval' => 10800, 'display' => 'هر ۳ ساعت');
    $schedules['cop_6_hours']  = array('interval' => 21600, 'display' => 'هر ۶ ساعت');
    $schedules['cop_12_hours'] = array('interval' => 43200, 'display' => 'هر ۱۲ ساعت');
    $schedules['cop_24_hours'] = array('interval' => 86400, 'display' => 'هر ۲۴ ساعت');
    return $schedules;
}

// 2. Schedule the event if not already scheduled
add_action('admin_init', 'cop_schedule_monitoring_event');
function cop_schedule_monitoring_event() {
    $schedule_key = get_option('cop_monitoring_schedule', 'cop_6_hours');
    if (!wp_next_scheduled('cop_central_monitoring_cycle')) {
        wp_schedule_event(time(), $schedule_key, 'cop_central_monitoring_cycle');
    }
}

// 3. The cycle trigger (runs every 6 hours)
add_action('cop_central_monitoring_cycle', 'cop_run_monitoring_cycle');
function cop_run_monitoring_cycle() {
    global $wpdb;

    // Fetch all active resources
    $resources = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'resource' AND post_status = 'publish'");
    
    if (empty($resources)) {
        return;
    }

    foreach ($resources as $res) {
        $resource_id = intval($res->ID);
        
        // Queue it using Action Scheduler if available to prevent server timeouts
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('cop_test_single_resource_action', array('resource_id' => $resource_id), 'cop_monitoring');
        } else {
            // Fallback: run synchronously (could be risky if too many resources)
            cop_test_single_resource($resource_id);
        }
    }

    // Schedule the digest email to run slightly after the monitoring cycle finishes
    if (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(time() + 600, 'cop_send_monitoring_digest_action', array(), 'cop_monitoring');
    } else {
        cop_send_monitoring_digest();
    }
}

// Hook for the single resource test
add_action('cop_test_single_resource_action', 'cop_test_single_resource', 10, 1);

// 4. The Core Robust Testing Logic
function cop_test_single_resource($resource_id) {
    global $wpdb;
    $table_logs = $wpdb->prefix . 'cop_monitoring_logs';
    $now = current_time('mysql');

    // Fetch resource data (selectors, feed url)
    $feed_url = get_post_meta($resource_id, 'source_feed_link', true);
    $title_sel = get_post_meta($resource_id, 'title_selector', true);
    $body_sel = get_post_meta($resource_id, 'body_selector', true);
    $lead_sel = get_post_meta($resource_id, 'lead_selector', true);
    $img_sel = get_post_meta($resource_id, 'img_selector', true);
    $root_link = get_post_meta($resource_id, 'source_root_link', true);
    $need_merge = get_post_meta($resource_id, 'need_to_merge_guid_link', true);

    if (empty($feed_url)) {
        cop_log_monitoring_result($resource_id, 'feed_error', 'لینک فید (RSS) برای این منبع تنظیم نشده است.');
        return;
    }

    // Step 1: Fetch Feed robustly
    $feed_response = wp_remote_get($feed_url, array(
        'timeout' => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($feed_response)) {
        cop_log_monitoring_result($resource_id, 'network_error', 'خطا در اتصال به فید: ' . $feed_response->get_error_message());
        return;
    }

    $feed_body = wp_remote_retrieve_body($feed_response);
    
    // Step 2: Parse Feed
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($feed_body);
    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        cop_log_monitoring_result($resource_id, 'feed_error', 'محتوای فید، XML معتبر نیست (احتمالاً خروجی HTML یا خطا است).');
        return;
    }
    libxml_use_internal_errors(false);

    $first_item = null;
    if (isset($xml->channel->item[0])) {
        $first_item = $xml->channel->item[0];
    } elseif (isset($xml->entry[0])) {
        $first_item = $xml->entry[0];
    }

    if (!$first_item) {
        cop_log_monitoring_result($resource_id, 'feed_error', 'ساختار فید صحیح است اما هیچ خبری (item) در آن یافت نشد.');
        return;
    }

    // Step 3: Extract Link (smart resolution: supports absolute, relative, and link fallback)
    $guid_raw  = isset($first_item->guid) ? (string) $first_item->guid : '';
    $link_raw  = isset($first_item->link) ? (string) $first_item->link : '';
    $guid = cop_resolve_feed_item_url($guid_raw, $link_raw, $root_link);

    if (empty($guid)) {
        cop_log_monitoring_result($resource_id, 'feed_error', 'لینک خبر (guid/link) در آیتم فید یافت نشد.');
        return;
    }

    // Encode Persian URLs properly
    $encoded_url = preg_replace_callback('/[^\x20-\x7f]/', function ($matches) { return rawurlencode($matches[0]); }, $guid);

    // Step 4: Fetch Article HTML
    $html_response = wp_remote_get($encoded_url, array(
        'timeout' => 20,
        'redirection' => 3,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($html_response)) {
        cop_log_monitoring_result($resource_id, 'network_error', 'خطا در بارگیری صفحه خبر: ' . $html_response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($html_response);
    if ($status_code != 200) {
        cop_log_monitoring_result($resource_id, 'network_error', 'کد وضعیت HTTP ناموفق هنگام بارگیری خبر: ' . $status_code);
        return;
    }

    $html_body = wp_remote_retrieve_body($html_response);
    
    // Prevent memory exhaustion on huge pages
    if (strlen($html_body) > 3145728) { // 3MB limit
        cop_log_monitoring_result($resource_id, 'timeout_error', 'حجم صفحه HTML خبر بیش از 3 مگابایت است که ممکن است باعث پر شدن RAM سرور شود.');
        return;
    }

    // Step 5: Parse HTML and Test Selectors
    $dom = new DOMDocument();
    
    // Handle Windows-1256 encoding if present
    $content_type = wp_remote_retrieve_header($html_response, 'content-type');
    if (stripos($content_type, 'windows-1256') !== false) {
        $html_body = mb_convert_encoding($html_body, 'HTML-ENTITIES', 'windows-1256');
    } else {
        $html_body = mb_convert_encoding($html_body, 'HTML-ENTITIES', 'UTF-8');
    }

    libxml_use_internal_errors(true);
    @$dom->loadHTML($html_body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Helper function to test selector (Robust implementation with edge cases handled)
    $test_selector = function($selector, $dom_xpath, $type) {
        if (empty($selector)) return true; // if not set, ignore testing it
        
        $xpath_query = cop_css_to_xpath($selector);

        $nodes = $dom_xpath->query($xpath_query);
        if ($nodes === false || $nodes->length === 0) {
            return false;
        }

        $node = $nodes->item(0);
        $tag_name = strtolower($node->nodeName);
        $content = '';

        if ($tag_name === 'meta') {
            // سناریو ۱: المان از جنس meta است (مثل متاتگ description یا og:image)
            $content = trim($node->getAttribute('content'));
        } elseif ($type === 'img') {
            // سناریو ۲: هدف استخراج تصویر است
            if ($tag_name === 'img') {
                // تجمیع اتربیوت‌های رایج تصاویر برای اطمینان از خالی نبودن
                $content = trim($node->getAttribute('src') . $node->getAttribute('data-src') . $node->getAttribute('data-lazy-src') . $node->getAttribute('srcset'));
            } else {
                // سناریو ۳: سلکتور تصویر به یک ظرف (Container/Wrapper) اشاره می‌کند
                $sub_imgs = $dom_xpath->query('.//img', $node);
                if ($sub_imgs && $sub_imgs->length > 0) {
                    $sub_img = $sub_imgs->item(0);
                    $content = trim($sub_img->getAttribute('src') . $sub_img->getAttribute('data-src') . $sub_img->getAttribute('data-lazy-src') . $sub_img->getAttribute('srcset'));
                }
            }
        } else {
            // سناریو ۴: تگ‌های استاندارد HTML دارای متن (مثل p, div, h1)
            $content = trim($node->textContent);
        }

        return !empty($content);
    };

    // Test Title
    if (!$test_selector($title_sel, $xpath, 'text')) {
        cop_log_monitoring_result($resource_id, 'selector_title_error', 'سلکتور عنوان (' . $title_sel . ') یافت نشد یا محتوای آن خالی است.');
        return;
    }

    // Test Body
    if (!$test_selector($body_sel, $xpath, 'text')) {
        cop_log_monitoring_result($resource_id, 'selector_body_error', 'سلکتور بدنه خبر (' . $body_sel . ') یافت نشد یا محتوای آن خالی است.');
        return;
    }

    // Test Lead (Optional depending on business logic, but logging error if set and fails)
    if (!empty($lead_sel) && !$test_selector($lead_sel, $xpath, 'text')) {
        cop_log_monitoring_result($resource_id, 'selector_error', 'سلکتور لید/خلاصه (' . $lead_sel . ') یافت نشد.');
        return;
    }

    // Test Image
    if (!empty($img_sel) && !$test_selector($img_sel, $xpath, 'img')) {
        cop_log_monitoring_result($resource_id, 'selector_image_error', 'سلکتور تصویر (' . $img_sel . ') یافت نشد.');
        return;
    }

    // If everything passes:
    cop_log_monitoring_result($resource_id, 'ok', 'بررسی با موفقیت انجام شد و تمامی سلکتورها صحیح بودند.');
}

// 5. Database Updater
function cop_log_monitoring_result($resource_id, $status, $details) {
    global $wpdb;
    $table = $wpdb->prefix . 'cop_monitoring_logs';
    $now = current_time('mysql');

    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE resource_id = %d", $resource_id));

    if (!$existing) {
        $wpdb->insert($table, array(
            'resource_id' => $resource_id,
            'last_checked' => $now,
            'status' => $status,
            'error_details' => $details,
            'fail_count' => ($status == 'ok') ? 0 : 1,
            'success_count' => ($status == 'ok') ? 1 : 0
        ));
    } else {
        $fail_count = intval($existing->fail_count);
        $success_count = intval($existing->success_count);

        if ($status == 'ok') {
            $success_count++;
            if ($success_count >= 2) { // Need 2 successes to consider it stable
                $fail_count = 0;
            }
        } else {
            $fail_count++;
            $success_count = 0; // Reset success streak
        }

        $wpdb->update($table, array(
            'last_checked' => $now,
            'status' => $status,
            'error_details' => $details,
            'fail_count' => $fail_count,
            'success_count' => $success_count
        ), array('resource_id' => $resource_id));
    }
}

// 6. Digest Email System
add_action('cop_send_monitoring_digest_action', 'cop_send_monitoring_digest');
function cop_send_monitoring_digest() {
    global $wpdb;
    $table_logs = $wpdb->prefix . 'cop_monitoring_logs';
    $table_posts = $wpdb->prefix . 'posts'; 

    // Check if notifications are enabled
    $notifications_enabled = get_option('cop_monitoring_notifications_enabled', '1');
    if ($notifications_enabled !== '1') {
        return;
    }

    $max_retries = intval(get_option('cop_monitoring_max_retries', 3));

    // Find resources that have fail_count >= max_retries (Persistent errors)
    $failed_resources = $wpdb->get_results($wpdb->prepare("
        SELECT l.*, p.post_title 
        FROM $table_logs l
        LEFT JOIN $table_posts p ON l.resource_id = p.ID
        WHERE l.fail_count >= %d
    ", $max_retries));

    if (empty($failed_resources)) {
        return; // No permanent errors, nothing to email
    }

    $admin_email = get_option('cop_monitoring_email', get_option('admin_email'));
    $subject = '🔴 هشدار مانیتورینگ منابع (COP Manager)';
    
    $message = "سلام مدیر عزیز،\n\n";
    $message .= "گزارش تجمیعی خطاهای قطعی در منابع خبری شما:\n\n";

    foreach ($failed_resources as $res) {
        $message .= "نام منبع: " . ($res->post_title ? $res->post_title : 'منبع ' . $res->resource_id) . "\n";
        $message .= "نوع خطا: " . $res->status . "\n";
        $message .= "جزئیات: " . $res->error_details . "\n";
        $message .= "تعداد تست ناموفق: " . $res->fail_count . " بار\n";
        $message .= "----------------------------------\n";
    }

    $message .= "\nلطفا جهت بررسی و اصلاح سلکتورها یا آدرس فید به پنل مدیریت وردپرس مراجعه کنید.";

    // Only send the email once per 24 hours per digest or just send it if there's an error. 
    $last_email_sent = get_option('cop_monitoring_last_email_sent', 0);
    if ((time() - $last_email_sent) > 86400) { // Only email once a day
        wp_mail($admin_email, $subject, $message);
        update_option('cop_monitoring_last_email_sent', time());
    }
}
