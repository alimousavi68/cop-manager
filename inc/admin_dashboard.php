<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'cop_monitoring_dashboard_menu');

function cop_monitoring_dashboard_menu() {
    add_submenu_page(
        'edit.php?post_type=resource',
        'وضعیت سلامت منابع',
        'سلامت منابع',
        'manage_options',
        'cop-monitoring-dashboard',
        'cop_monitoring_dashboard_page'
    );
}

function cop_monitoring_dashboard_page() {
    global $wpdb;
    $table_logs = $wpdb->prefix . 'cop_monitoring_logs';
    $table_posts = $wpdb->prefix . 'posts';
    $max_retries = intval(get_option('cop_monitoring_max_retries', 3));

    // Handle settings save
    if (isset($_POST['cop_save_settings']) && check_admin_referer('cop_monitoring_settings_nonce')) {
        $schedule = sanitize_text_field($_POST['cop_monitoring_schedule']);
        $email = sanitize_email($_POST['cop_monitoring_email']);
        $retries = intval($_POST['cop_monitoring_max_retries']);
        $enabled = isset($_POST['cop_monitoring_notifications_enabled']) ? '1' : '0';

        // Update options
        $old_schedule = get_option('cop_monitoring_schedule', 'cop_6_hours');
        update_option('cop_monitoring_schedule', $schedule);
        update_option('cop_monitoring_email', $email);
        update_option('cop_monitoring_max_retries', $retries);
        update_option('cop_monitoring_notifications_enabled', $enabled);

        // Reschedule event if cron interval changed
        if ($old_schedule !== $schedule) {
            wp_clear_scheduled_hook('cop_central_monitoring_cycle');
            wp_schedule_event(time(), $schedule, 'cop_central_monitoring_cycle');
        }

        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات مانیتورینگ با موفقیت ذخیره شد.</p></div>';
    }

    // Determine current active tab
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';

    // Manual Recheck trigger for a single resource (Synchronous for immediate feedback)
    if (isset($_GET['recheck_resource']) && current_user_can('manage_options')) {
        $res_id = intval($_GET['recheck_resource']);
        
        // Execute synchronously
        cop_test_single_resource($res_id);
        
        $log_res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_logs WHERE resource_id = %d", $res_id));
        if ($log_res) {
            $status_lbl = $log_res->status == 'ok' ? '<span style="color: #10b981; font-weight: bold;">سالم</span>' : '<span style="color: #ef4444; font-weight: bold;">خطا</span>';
            $msg = 'بررسی منبع انجام شد. وضعیت جدید: ' . $status_lbl;
            if ($log_res->status != 'ok') {
                $msg .= ' - علت خطا: <span style="color: #ef4444;">' . esc_html($log_res->error_details) . '</span>';
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
        }
    }

    // Manual Recheck trigger for all resources (Synchronous report)
    if (isset($_GET['run_all']) && current_user_can('manage_options')) {
        $resources = $wpdb->get_results("SELECT ID, post_title FROM $table_posts WHERE post_type = 'resource' AND post_status = 'publish'");
        $report = [];
        if (!empty($resources)) {
            foreach ($resources as $res) {
                cop_test_single_resource(intval($res->ID));
                $log_res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_logs WHERE resource_id = %d", intval($res->ID)));
                $report[] = [
                    'title' => $res->post_title,
                    'status' => $log_res ? $log_res->status : 'unknown',
                    'details' => $log_res ? $log_res->error_details : ''
                ];
            }
        }
        
        echo '<div class="notice notice-success is-dismissible" style="padding: 15px;">';
        echo '<h3 style="margin-top:0;">گزارش عملکرد بررسی منابع:</h3>';
        echo '<ul style="margin: 0; padding-right: 20px; list-style-type: disc;">';
        foreach ($report as $r) {
            $status_lbl = $r['status'] == 'ok' ? '<span style="color: #10b981; font-weight: bold;">سالم</span>' : '<span style="color: #ef4444; font-weight: bold;">خطا</span>';
            echo '<li>منبع <strong>' . esc_html($r['title']) . '</strong>: ' . $status_lbl . ($r['status'] != 'ok' ? ' (' . esc_html($r['details']) . ')' : '') . '</li>';
        }
        echo '</ul></div>';
    }

    // Stats calculations
    $total_resources = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_posts WHERE post_type = %s AND post_status = %s", 'resource', 'publish')));
    $error_resources = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_logs l JOIN $table_posts p ON l.resource_id = p.ID WHERE p.post_type = %s AND p.post_status = %s AND l.status != 'ok' AND l.fail_count >= %d", 'resource', 'publish', $max_retries)));
    $pending_retries_count = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_logs l JOIN $table_posts p ON l.resource_id = p.ID WHERE p.post_type = %s AND p.post_status = %s AND l.status != 'ok' AND l.fail_count > 0 AND l.fail_count < %d", 'resource', 'publish', $max_retries)));
    $healthy_resources = max(0, $total_resources - $error_resources - $pending_retries_count);

    ?>
    <style>
        :root {
            --cop-primary: #4f46e5;
            --cop-primary-hover: #4338ca;
            --cop-success: #10b981;
            --cop-danger: #ef4444;
            --cop-warning: #f59e0b;
            --cop-neutral-50: #f8fafc;
            --cop-neutral-100: #f1f5f9;
            --cop-neutral-800: #1e293b;
            --cop-glass-bg: rgba(255, 255, 255, 0.75);
            --cop-glass-border: rgba(226, 232, 240, 0.8);
        }

        .cop-mon-wrap {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px 20px 0 0;
            max-width: 98%;
            direction: rtl;
        }

        .cop-mon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .cop-title-sec h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--cop-neutral-800);
            margin: 0 0 6px 0;
        }

        .cop-title-sec .description {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        /* Stats Grid */
        .cop-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .cop-stat-card {
            background: var(--cop-glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--cop-glass-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cop-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .cop-stat-info {
            display: flex;
            flex-direction: column;
        }

        .cop-stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .cop-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--cop-neutral-800);
            margin-top: 4px;
        }

        .cop-bg-primary { background: rgba(79, 70, 229, 0.1); color: var(--cop-primary); }
        .cop-bg-success { background: rgba(16, 185, 129, 0.1); color: var(--cop-success); }
        .cop-bg-danger { background: rgba(239, 68, 68, 0.1); color: var(--cop-danger); }
        .cop-bg-warning { background: rgba(245, 158, 11, 0.1); color: var(--cop-warning); }

        /* Navigation Tabs */
        .cop-tabs {
            margin-bottom: 20px;
            border-bottom: 1px solid #cbd5e1;
        }

        .cop-tabs a {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: #64748b;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s ease;
        }

        .cop-tabs a:hover {
            color: var(--cop-primary);
        }

        .cop-tabs a.active {
            color: var(--cop-primary);
            border-bottom-color: var(--cop-primary);
        }

        /* Table and Cards */
        .cop-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            padding: 24px;
            margin-bottom: 24px;
        }

        .cop-card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--cop-neutral-800);
            margin-bottom: 20px;
        }

        /* Form styling */
        .cop-settings-form table {
            width: 100%;
            border-collapse: collapse;
        }

        .cop-settings-form th {
            width: 250px;
            text-align: right;
            padding: 15px 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--cop-neutral-800);
        }

        .cop-settings-form td {
            padding: 15px 10px;
        }

        .cop-settings-form input[type="text"],
        .cop-settings-form input[type="email"],
        .cop-settings-form input[type="number"],
        .cop-settings-form select {
            width: 350px;
            max-width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .cop-settings-form .description {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
        }
    </style>

    <div class="wrap cop-mon-wrap">
        <!-- Header -->
        <div class="cop-mon-header">
            <div class="cop-title-sec">
                <h1>سامانه پایش سلامت منابع خبری</h1>
                <p class="description">پایش خودکار و بررسی مستمر اتصالات و سلکتورهای استخراج محتوا</p>
            </div>
            <?php if ($active_tab === 'dashboard'): ?>
                <a href="?post_type=resource&page=cop-monitoring-dashboard&run_all=1" class="button button-primary">⚡ بررسی مجدد تمام منابع</a>
            <?php endif; ?>
        </div>

        <!-- Navigation Tabs -->
        <h2 class="nav-tab-wrapper cop-tabs">
            <a href="?post_type=resource&page=cop-monitoring-dashboard&tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active active' : ''; ?>">داشبورد مانیتورینگ</a>
            <a href="?post_type=resource&page=cop-monitoring-dashboard&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active active' : ''; ?>">تنظیمات پایش</a>
        </h2>

        <?php if ($active_tab === 'dashboard'): ?>
            <!-- Stats Grid -->
            <div class="cop-stats-grid">
                <div class="cop-stat-card">
                    <div class="cop-stat-icon cop-bg-primary">📡</div>
                    <div class="cop-stat-info">
                        <span class="cop-stat-label">کل منابع فعال</span>
                        <span class="cop-stat-value"><?php echo $total_resources; ?> منبع</span>
                    </div>
                </div>
                <div class="cop-stat-card">
                    <div class="cop-stat-icon cop-bg-success">🛡️</div>
                    <div class="cop-stat-info">
                        <span class="cop-stat-label">منابع بدون خطا</span>
                        <span class="cop-stat-value"><?php echo $healthy_resources; ?> منبع</span>
                    </div>
                </div>
                <div class="cop-stat-card">
                    <div class="cop-stat-icon cop-bg-danger">❌</div>
                    <div class="cop-stat-info">
                        <span class="cop-stat-label">خطاهای قطعی</span>
                        <span class="cop-stat-value"><?php echo $error_resources; ?> مورد</span>
                    </div>
                </div>
                <div class="cop-stat-card">
                    <div class="cop-stat-icon cop-bg-warning">⏳</div>
                    <div class="cop-stat-info">
                        <span class="cop-stat-label">تلاش مجدد موقت</span>
                        <span class="cop-stat-value"><?php echo $pending_retries_count; ?> مورد</span>
                    </div>
                </div>
            </div>

            <!-- Logs list -->
            <?php
            $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
            $where = "";
            if ($filter == 'errors') {
                $where = "WHERE l.status != 'ok'";
            }

            $logs = $wpdb->get_results("
                SELECT l.*, p.post_title 
                FROM $table_logs l
                LEFT JOIN $table_posts p ON l.resource_id = p.ID
                $where
                ORDER BY l.last_checked DESC
            ");
            ?>

            <ul class="subsubsub">
                <li class="all"><a href="?post_type=resource&page=cop-monitoring-dashboard&filter=all" class="<?php echo $filter == 'all' ? 'current' : ''; ?>">همه منابع</a> |</li>
                <li class="trash"><a href="?post_type=resource&page=cop-monitoring-dashboard&filter=errors" class="<?php echo $filter == 'errors' ? 'current' : ''; ?>">خطادارها</a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title">نام منبع</th>
                        <th scope="col" class="manage-column">وضعیت</th>
                        <th scope="col" class="manage-column">جزئیات خطا</th>
                        <th scope="col" class="manage-column">آخرین بررسی</th>
                        <th scope="col" class="manage-column">تعداد خطا (تلاش مجدد)</th>
                        <th scope="col" class="manage-column">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6">هیچ اطلاعات مانیتورینگی یافت نشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $status_label = '';
                            $color = '';
                            $is_error = ($log->status !== 'ok');
                            
                            if ($log->status === 'ok') {
                                $status_label = 'سالم';
                                $color = 'color: #10b981;';
                            } else {
                                if ($log->fail_count >= $max_retries) {
                                    $status_label = 'خطای قطعی';
                                    $color = 'color: #ef4444;';
                                } else {
                                    $status_label = 'خطای موقت (تلاش مجدد)';
                                    $color = 'color: #f59e0b;';
                                }
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($log->post_title ? $log->post_title : 'منبع ' . $log->resource_id); ?></strong></td>
                            <td style="font-weight: bold; <?php echo $color; ?>"><?php echo esc_html($status_label); ?></td>
                            <td><?php echo esc_html($log->status == 'ok' ? '-' : $log->error_details); ?></td>
                            <td><?php echo esc_html(wp_date('Y/m/d H:i:s', strtotime($log->last_checked))); ?></td>
                            <td><?php echo intval($log->fail_count) . ' / ' . $max_retries; ?></td>
                            <td>
                                <a href="?post_type=resource&page=cop-monitoring-dashboard&recheck_resource=<?php echo intval($log->resource_id); ?>&filter=<?php echo $filter; ?>" class="button button-small">تست مجدد</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($active_tab === 'settings'): ?>
            <!-- Settings Form -->
            <div class="cop-card">
                <div class="cop-card-title">⚙️ تنظیمات مانیتورینگ خودکار</div>
                <form method="post" action="" class="cop-settings-form">
                    <?php wp_nonce_field('cop_monitoring_settings_nonce'); ?>
                    <table>
                        <tr>
                            <th>دوره زمانی پایش خودکار:</th>
                            <td>
                                <select name="cop_monitoring_schedule">
                                    <?php
                                    $current_sched = get_option('cop_monitoring_schedule', 'cop_6_hours');
                                    $schedules = array(
                                        'cop_1_hour'   => 'هر ۱ ساعت',
                                        'cop_3_hours'  => 'هر ۳ ساعت',
                                        'cop_6_hours'  => 'هر ۶ ساعت',
                                        'cop_12_hours' => 'هر ۱۲ ساعت',
                                        'cop_24_hours' => 'هر ۲۴ ساعت',
                                    );
                                    foreach ($schedules as $key => $lbl) {
                                        echo '<option value="' . esc_attr($key) . '" ' . selected($current_sched, $key, false) . '>' . esc_html($lbl) . '</option>';
                                    }
                                    ?>
                                </select>
                                <span class="description">مدت زمان بین هر پایش سیستم بر روی منابع.</span>
                            </td>
                        </tr>
                        <tr>
                            <th>تعداد دفعات مجاز خطا (Retry Count):</th>
                            <td>
                                <input type="number" name="cop_monitoring_max_retries" min="1" max="10" value="<?php echo intval(get_option('cop_monitoring_max_retries', 3)); ?>" />
                                <span class="description">تعداد خطاهای پی‌درپی که پس از آن وضعیت منبع «خطای قطعی» شده و ایمیل ارسال می‌شود.</span>
                            </td>
                        </tr>
                        <tr>
                            <th>آدرس ایمیل اطلاع‌رسانی:</th>
                            <td>
                                <input type="email" name="cop_monitoring_email" value="<?php echo esc_attr(get_option('cop_monitoring_email', get_option('admin_email'))); ?>" />
                                <span class="description">ایمیل‌های هشدار خرابی فیدها یا سلکتورها به این آدرس ارسال خواهند شد.</span>
                            </td>
                        </tr>
                        <tr>
                            <th>ارسال هشدارهای ایمیلی:</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="cop_monitoring_notifications_enabled" value="1" <?php checked(get_option('cop_monitoring_notifications_enabled', '1'), '1'); ?> />
                                    <span>فعال‌سازی ارسال ایمیل هشدار در صورت خرابی منابع</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="cop_save_settings" class="button button-primary" value="ذخیره تنظیمات" />
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
