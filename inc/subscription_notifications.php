<?php
/**
 * Subscription Expiry Notifications — Phase 9
 * 
 * A daily cron job that:
 * 1. Sends reminder emails to the subscribed user at 15, 7, 3, and 0 days before expiry.
 * 2. Sends a daily digest email to the admin with all subscriptions needing attention.
 */

if (!defined('ABSPATH')) exit;

// ---------------------------------------------------------
// Register & Schedule the daily cron job
// ---------------------------------------------------------

add_filter('cron_schedules', 'cop_add_daily_notification_schedule');
function cop_add_daily_notification_schedule($schedules) {
    if (!isset($schedules['cop_daily'])) {
        $schedules['cop_daily'] = array(
            'interval' => DAY_IN_SECONDS,
            'display' => 'روزانه (اطلاع‌رسانی اشتراک)'
        );
    }
    return $schedules;
}

register_activation_hook(dirname(dirname(__FILE__)) . '/index.php', 'cop_schedule_expiry_notifications');
function cop_schedule_expiry_notifications() {
    if (!wp_next_scheduled('cop_expiry_notification_cron')) {
        // Schedule to run at midnight server time
        $next_midnight = strtotime('tomorrow midnight');
        wp_schedule_event($next_midnight, 'cop_daily', 'cop_expiry_notification_cron');
    }
}

register_deactivation_hook(dirname(dirname(__FILE__)) . '/index.php', 'cop_unschedule_expiry_notifications');
function cop_unschedule_expiry_notifications() {
    wp_clear_scheduled_hook('cop_expiry_notification_cron');
}

add_action('cop_expiry_notification_cron', 'cop_run_expiry_notifications');

// ---------------------------------------------------------
// Main notification runner
// ---------------------------------------------------------

function cop_run_expiry_notifications() {
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    // Notification thresholds in days
    $thresholds = array(15, 7, 3, 0);
    
    $all_subs = get_posts(array(
        'post_type' => 'subscriptions',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $admin_digest = array(); // Collected items for admin digest email
    $current_ts = current_time('timestamp');
    $today_date = date('Y-m-d', $current_ts);
    
    foreach ($all_subs as $post_id) {
        $expiry_date = get_post_meta($post_id, 'subscription_expiry_date', true);
        
        // Migrate on-the-fly if needed
        if (empty($expiry_date)) {
            $expiry_date = cop_calculate_expiry_date($post_id);
            if ($expiry_date) {
                update_post_meta($post_id, 'subscription_expiry_date', $expiry_date);
            } else {
                continue; // no plan set, skip
            }
        }
        
        $expiry_ts = strtotime($expiry_date);
        $days_remaining = (int) ceil(($expiry_ts - $current_ts) / DAY_IN_SECONDS);
        
        // Collect for admin digest: expiring in ≤15 days or already expired
        if ($days_remaining <= 15) {
            $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
            $admin_digest[] = array(
                'title' => get_the_title($post_id),
                'url' => get_post_meta($post_id, 'subscription_site_url', true),
                'expiry' => date_i18n('Y-m-d', $expiry_ts),
                'days' => $days_remaining,
                'edit_link' => get_edit_post_link($post_id, 'raw'),
                'plan' => $plan_id ? get_the_title($plan_id) : 'نامشخص'
            );
        }
        
        // Send client notification on specific thresholds
        if (in_array($days_remaining, $thresholds)) {
            // Avoid double-sending on the same day
            $sent_key = 'cop_notif_sent_' . $post_id . '_' . $days_remaining . '_' . $today_date;
            if (get_transient($sent_key)) continue;
            
            $user_id = get_post_meta($post_id, 'subscription_user_id', true);
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                if ($user && !empty($user->user_email)) {
                    $sent = cop_send_client_expiry_email($user->user_email, $post_id, $expiry_date, $days_remaining, $site_name);
                    if ($sent) {
                        // Mark as sent for today to prevent duplicate
                        set_transient($sent_key, '1', DAY_IN_SECONDS + HOUR_IN_SECONDS);
                    }
                }
            }
        }
    }
    
    // Send admin digest if there are items needing attention
    if (!empty($admin_digest)) {
        $digest_sent_key = 'cop_admin_digest_sent_' . $today_date;
        if (!get_transient($digest_sent_key)) {
            cop_send_admin_digest_email($admin_email, $admin_digest, $site_name);
            set_transient($digest_sent_key, '1', DAY_IN_SECONDS + HOUR_IN_SECONDS);
        }
    }
}

// ---------------------------------------------------------
// Email: Client Expiry Reminder
// ---------------------------------------------------------

function cop_send_client_expiry_email($to_email, $post_id, $expiry_date, $days_remaining, $site_name) {
    $site_url = get_post_meta($post_id, 'subscription_site_url', true);
    $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
    $plan_name = $plan_id ? get_the_title($plan_id) : 'نامشخص';
    
    if ($days_remaining === 0) {
        $subject = "[$site_name] اشتراک شما امروز منقضی می‌شود!";
        $urgency = "⛔ امروز آخرین روز اشتراک شماست.";
        $cta = "لطفاً هر چه زودتر با ما تماس بگیرید تا اشتراک‌تان را تمدید کنیم.";
    } else {
        $subject = "[$site_name] اشتراک شما $days_remaining روز دیگر منقضی می‌شود";
        $urgency = "⏳ اشتراک شما $days_remaining روز دیگر به پایان می‌رسد.";
        $cta = "برای تداوم استفاده از خدمات، لطفاً نسبت به تمدید اشتراک خود اقدام فرمایید.";
    }
    
    $message = "
<div dir='rtl' style='font-family:Tahoma,sans-serif; max-width:600px; margin:0 auto; background:#f8fafc; padding:20px; border-radius:12px;'>
    <div style='background:#4f46e5; color:#fff; padding:20px; border-radius:8px; text-align:center; margin-bottom:20px;'>
        <h2 style='margin:0;'>اطلاعیه اشتراک</h2>
        <p style='margin:6px 0 0; opacity:0.85;'>$site_name</p>
    </div>
    
    <div style='background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb;'>
        <p style='font-size:16px; font-weight:bold; color:#dc2626;'>$urgency</p>
        <p style='color:#374151;'>$cta</p>
        
        <table style='width:100%; border-collapse:collapse; margin-top:16px;'>
            <tr style='background:#f1f5f9;'>
                <td style='padding:10px; font-weight:bold; color:#374151;'>آدرس سایت:</td>
                <td style='padding:10px; color:#4f46e5;'>$site_url</td>
            </tr>
            <tr>
                <td style='padding:10px; font-weight:bold; color:#374151;'>پلن فعلی:</td>
                <td style='padding:10px;'>$plan_name</td>
            </tr>
            <tr style='background:#f1f5f9;'>
                <td style='padding:10px; font-weight:bold; color:#374151;'>تاریخ انقضا:</td>
                <td style='padding:10px; color:#dc2626; font-weight:bold;'>" . date_i18n('Y-m-d', strtotime($expiry_date)) . "</td>
            </tr>
        </table>
    </div>
    
    <p style='text-align:center; color:#9ca3af; font-size:12px; margin-top:16px;'>این ایمیل به صورت خودکار ارسال شده است.</p>
</div>
";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    return wp_mail($to_email, $subject, $message, $headers);
}

// ---------------------------------------------------------
// Email: Admin Daily Digest
// ---------------------------------------------------------

function cop_send_admin_digest_email($to_email, $items, $site_name) {
    $count = count($items);
    $subject = "[$site_name] گزارش روزانه: $count اشتراک نیازمند توجه";
    
    $rows = '';
    foreach ($items as $item) {
        $days_label = $item['days'] < 0
            ? '<span style="color:#dc2626; font-weight:bold;">منقضی شده (' . abs($item['days']) . ' روز پیش)</span>'
            : '<span style="color:' . ($item['days'] <= 3 ? '#dc2626' : ($item['days'] <= 7 ? '#d97706' : '#374151')) . '; font-weight:bold;">' . $item['days'] . ' روز</span>';
        
        $rows .= "
<tr>
    <td style='padding:10px; border-bottom:1px solid #e5e7eb;'>
        <a href='{$item['edit_link']}' style='color:#4f46e5; text-decoration:none; font-weight:bold;'>{$item['title']}</a>
    </td>
    <td style='padding:10px; border-bottom:1px solid #e5e7eb; direction:ltr;'>{$item['url']}</td>
    <td style='padding:10px; border-bottom:1px solid #e5e7eb;'>{$item['plan']}</td>
    <td style='padding:10px; border-bottom:1px solid #e5e7eb;'>{$item['expiry']}</td>
    <td style='padding:10px; border-bottom:1px solid #e5e7eb;'>$days_label</td>
</tr>";
    }
    
    $message = "
<div dir='rtl' style='font-family:Tahoma,sans-serif; max-width:800px; margin:0 auto; background:#f8fafc; padding:20px; border-radius:12px;'>
    <div style='background:#1e293b; color:#fff; padding:20px; border-radius:8px; margin-bottom:20px;'>
        <h2 style='margin:0;'>📊 گزارش روزانه اشتراک‌ها</h2>
        <p style='margin:6px 0 0; opacity:0.7;'>$site_name — " . date_i18n('Y-m-d') . "</p>
    </div>
    
    <div style='background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb;'>
        <p style='color:#374151;'>اشتراک‌های زیر در ۱۵ روز آینده منقضی می‌شوند یا قبلاً منقضی شده‌اند:</p>
        
        <table style='width:100%; border-collapse:collapse;'>
            <thead>
                <tr style='background:#f1f5f9;'>
                    <th style='padding:10px; text-align:right; color:#374151;'>عنوان</th>
                    <th style='padding:10px; text-align:right; color:#374151;'>آدرس</th>
                    <th style='padding:10px; text-align:right; color:#374151;'>پلن</th>
                    <th style='padding:10px; text-align:right; color:#374151;'>تاریخ انقضا</th>
                    <th style='padding:10px; text-align:right; color:#374151;'>روز مانده</th>
                </tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
    </div>
    
    <div style='text-align:center; margin-top:16px;'>
        <a href='" . admin_url('edit.php?post_type=subscriptions&page=cop-subscriptions-dashboard') . "' style='background:#4f46e5; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;'>مشاهده داشبورد اشتراک‌ها</a>
    </div>
    
    <p style='text-align:center; color:#9ca3af; font-size:12px; margin-top:16px;'>این ایمیل به صورت خودکار ارسال شده است.</p>
</div>
";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    return wp_mail($to_email, $subject, $message, $headers);
}

// ---------------------------------------------------------
// Admin: Manual trigger for testing (button in dashboard)
// ---------------------------------------------------------

add_action('admin_post_cop_trigger_notifications', 'cop_manually_trigger_notifications');
function cop_manually_trigger_notifications() {
    if (!current_user_can('manage_options') || !check_admin_referer('cop_trigger_notifications')) {
        wp_die('دسترسی غیرمجاز');
    }
    cop_run_expiry_notifications();
    wp_redirect(add_query_arg(
        array('post_type' => 'subscriptions', 'page' => 'cop-subscriptions-dashboard', 'cop_notif_triggered' => '1'),
        admin_url('edit.php')
    ));
    exit;
}
