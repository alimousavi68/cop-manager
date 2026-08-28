<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'cop_subscriptions_dashboard_menu');

function cop_subscriptions_dashboard_menu() {
    add_submenu_page(
        'edit.php?post_type=subscriptions',
        'داشبورد اشتراک‌ها',
        'داشبورد مانیتورینگ',
        'manage_options',
        'cop-subscriptions-dashboard',
        'cop_subscriptions_dashboard_page'
    );
}

function cop_subscriptions_dashboard_page() {
    // 1. Calculate Stats
    // Show manual trigger notice
    if (!empty($_GET['cop_notif_triggered'])) {
        echo '<div class="notice notice-success is-dismissible"><p>✅ اطلاع‌رسانی با موفقیت اجرا شد. ایمیل‌ها (در صورت وجود مورد واجد) ارسال شدند.</p></div>';
    }
    
    $all_subs = get_posts(array(
        'post_type' => 'subscriptions',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));

    $total = count($all_subs);
    $active_count = 0;
    $grace_count = 0;
    $expired_count = 0;
    $expiring_soon = array();

    $current_ts = current_time('timestamp');
    $seven_days_later_ts = $current_ts + (7 * DAY_IN_SECONDS);

    foreach ($all_subs as $post_id) {
        $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
        if (!$plan_id) continue;
        
        // Use absolute expiry date
        $expiry_date = get_post_meta($post_id, 'subscription_expiry_date', true);
        if (empty($expiry_date)) {
            $expiry_date = cop_calculate_expiry_date($post_id);
            if ($expiry_date) update_post_meta($post_id, 'subscription_expiry_date', $expiry_date);
            else continue;
        }
        
        $grace_period = (int) get_post_meta($plan_id, 'plan_grace_period', true);
        $end_date_ts = strtotime($expiry_date);
        $grace_end_ts = $end_date_ts + ($grace_period * DAY_IN_SECONDS);
        
        if ($current_ts <= $end_date_ts) {
            $active_count++;
            if ($end_date_ts <= $seven_days_later_ts) {
                $expiring_soon[] = array('id' => $post_id, 'end_ts' => $end_date_ts, 'status' => 'active');
            }
        } elseif ($current_ts <= $grace_end_ts) {
            $grace_count++;
            $expiring_soon[] = array('id' => $post_id, 'end_ts' => $grace_end_ts, 'status' => 'grace');
        } else {
            $expired_count++;
        }
    }

    // Sort expiring soon by date (closest first)
    usort($expiring_soon, function($a, $b) {
        return $a['end_ts'] - $b['end_ts'];
    });

    // Handle Quick Renew Form Submission
    if (isset($_POST['cop_quick_renew_submit']) && check_admin_referer('cop_quick_renew_nonce')) {
        $sub_id = intval($_POST['sub_id']);
        $days = intval($_POST['renew_days']);
        if ($sub_id > 0 && $days > 0) {
            $current_days = (int) get_post_meta($sub_id, 'subscription_extra_days', true);
            update_post_meta($sub_id, 'subscription_extra_days', $current_days + $days);
            if (function_exists('cop_add_subscription_log')) {
                cop_add_subscription_log($sub_id, 'تمدید سریع (داشبورد)', $current_days, $current_days + $days);
            }
            echo '<div class="notice notice-success is-dismissible"><p>اشتراک با موفقیت تمدید شد.</p></div>';
            // Recalculate to update list
            echo '<script>window.location = window.location.href;</script>';
            exit;
        }
    }

    ?>
    <div class="cop-admin-wrap">
        <div class="cop-app-container" style="max-width: 1200px; margin: 20px auto;">
            
            <!-- Header -->
            <div class="cop-sticky-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 48px; height: 48px; background: rgba(79, 70, 229, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--cop-primary);">
                        <svg style="width: 28px; height: 28px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                        </svg>
                    </div>
                    <div>
                        <h1 style="margin:0; font-size: 22px; font-weight: 700; color: #1f2937;">داشبورد مانیتورینگ اشتراک‌ها</h1>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">پایش لحظه‌ای وضعیت لایسنس‌ها و انقضا</p>
                    </div>
                </div>
                <?php if ($expired_count > 0): ?>
                <div class="cop-badge cop-badge-danger" style="font-size: 14px; padding: 8px 16px;">
                    <?php echo $expired_count; ?> اشتراک منقضی شده!
                </div>
                <?php endif; ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin:0;">
                    <?php wp_nonce_field('cop_trigger_notifications'); ?>
                    <input type="hidden" name="action" value="cop_trigger_notifications">
                    <button type="submit" class="cop-btn cop-btn-neutral" title="ارسال دستی ایمیل‌های اطلاع‌رسانی (برای تست یا اجرای فوری)">
                        <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        ارسال ایمیل‌های هشدار الان
                    </button>
                </form>
            </div>

            <!-- Stats Grid -->
            <div class="cop-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
                <!-- Total -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; border-bottom: 4px solid var(--cop-primary);">
                    <div style="width: 50px; height: 50px; background: rgba(79,70,229,0.1); color: var(--cop-primary); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width:24px; height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #6b7280; font-weight: 600;">کل اشتراک‌ها</div>
                        <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?php echo $total; ?></div>
                    </div>
                </div>
                
                <!-- Active -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; border-bottom: 4px solid var(--cop-success);">
                    <div style="width: 50px; height: 50px; background: rgba(16,185,129,0.1); color: var(--cop-success); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width:24px; height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #6b7280; font-weight: 600;">فعال و سالم</div>
                        <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?php echo $active_count; ?></div>
                    </div>
                </div>

                <!-- Grace -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; border-bottom: 4px solid var(--cop-warning);">
                    <div style="width: 50px; height: 50px; background: rgba(245,158,11,0.1); color: var(--cop-warning); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width:24px; height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #6b7280; font-weight: 600;">در دوره ارفاق</div>
                        <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?php echo $grace_count; ?></div>
                    </div>
                </div>

                <!-- Expired -->
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; border-bottom: 4px solid var(--cop-danger);">
                    <div style="width: 50px; height: 50px; background: rgba(239,68,68,0.1); color: var(--cop-danger); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <svg style="width:24px; height:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #6b7280; font-weight: 600;">منقضی و مسدود</div>
                        <div style="font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px;"><?php echo $expired_count; ?></div>
                    </div>
                </div>
            </div>

            <!-- Table: Expiring Soon -->
            <div class="cop-card" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <svg style="width:20px; height:20px; color:var(--cop-warning);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        انقضاهای نزدیک (۷ روز آینده) و دوره‌های ارفاق
                    </h3>
                    <a href="<?php echo admin_url('edit.php?post_type=subscriptions'); ?>" class="cop-btn cop-btn-neutral">مدیریت همه اشتراک‌ها &larr;</a>
                </div>

                <?php if (empty($expiring_soon)): ?>
                    <div style="padding: 40px; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db;">
                        هیچ اشتراکی در ۷ روز آینده منقضی نمی‌شود.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($expiring_soon as $sub): 
                            $post_title = get_the_title($sub['id']);
                            $url = get_post_meta($sub['id'], 'subscription_site_url', true);
                            $plan_id = get_post_meta($sub['id'], 'subscription_plan_id', true);
                            $plan_name = $plan_id ? get_the_title($plan_id) : 'بدون پلن';
                            $days_left = ceil(($sub['end_ts'] - $current_ts) / (24*3600));
                            
                            $accent = $sub['status'] === 'grace' ? 'warning' : 'danger';
                            $badge = $sub['status'] === 'grace' 
                                ? '<span class="cop-badge cop-badge-warning">دوره ارفاق (' . $days_left . ' روز)</span>' 
                                : '<span class="cop-badge cop-badge-danger">انقضا تا ' . $days_left . ' روز دیگر</span>';
                        ?>
                        <div class="cop-table-item" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="cop-item-accent <?php echo $accent; ?>" style="width: 4px; height: 40px; border-radius: 4px;"></div>
                                <div>
                                    <div style="font-weight: 700; color: #111827; margin-bottom: 4px;">
                                        <a href="<?php echo get_edit_post_link($sub['id']); ?>" style="text-decoration: none; color: inherit;"><?php echo esc_html($post_title); ?></a>
                                    </div>
                                    <div style="display: flex; gap: 10px; font-size: 12px; color: #6b7280;">
                                        <span>🌍 <?php echo esc_html(str_replace(array('http://','https://'),'',$url)); ?></span>
                                        <span>📦 <?php echo esc_html($plan_name); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <?php echo $badge; ?>
                                
                                <form method="post" style="display: flex; align-items: center; gap: 8px; margin: 0; background: #f8fafc; padding: 6px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <?php wp_nonce_field('cop_quick_renew_nonce'); ?>
                                    <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                    <select name="renew_days" class="cop-input" style="height: 32px; min-height: 32px; padding: 0 8px; font-size: 12px;">
                                        <option value="30">۳۰ روز</option>
                                        <option value="60">۶۰ روز</option>
                                        <option value="90">۹۰ روز</option>
                                        <option value="180">۱۸۰ روز</option>
                                        <option value="365">۱ سال</option>
                                    </select>
                                    <button type="submit" name="cop_quick_renew_submit" class="cop-btn cop-btn-success" style="height: 32px; padding: 0 12px; font-size: 12px;">تمدید سریع</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    <?php
}
