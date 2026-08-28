<?php

$assets_url = plugins_url('assets/user-cart.svg', dirname(__FILE__));
$globalVarIconPostType = $assets_url;


// Register subscriptions Post Type
function subscriptions_post_type()
{
    global $globalVarIconPostType;

    $labels = array(
        'name' => _x('اشتراک‌ها', 'Post Type General Name', 'i8_publisher_copilot'),
        'singular_name' => _x('اشتراک', 'Post Type Singular Name', 'i8_publisher_copilot'),
        'menu_name' => __('اشتراک‌ها', 'i8_publisher_copilot'),
        'name_admin_bar' => __('اشتراک‌ها', 'i8_publisher_copilot'),
        'archives' => __('آرشیو اشتراک‌ها', 'i8_publisher_copilot'),
        'attributes' => __('خصوصیات اشتراک‌ها', 'i8_publisher_copilot'),
        'parent_item_colon' => __('مادر', 'i8_publisher_copilot'),
        'all_items' => __('همه اشتراک‌ها', 'i8_publisher_copilot'),
        'add_new_item' => __('افزودن اشتراک', 'i8_publisher_copilot'),
        'add_new' => __('افزودن جدید', 'i8_publisher_copilot'),
        'new_item' => __('اشتراک‌ جدید', 'i8_publisher_copilot'),
        'edit_item' => __('ویرایش اشتراک', 'i8_publisher_copilot'),
        'update_item' => __('به روزرسانی اشتراک', 'i8_publisher_copilot'),
        'view_item' => __('نمایش اشتراک', 'i8_publisher_copilot'),
        'view_items' => __('نمایش اشتراک‌ها', 'i8_publisher_copilot'),
        'search_items' => __('جستجوی اشتراک', 'i8_publisher_copilot'),
        'not_found' => __('پیدا نشد', 'i8_publisher_copilot'),
        'not_found_in_trash' => __('در زباله دان پیدا نشد', 'i8_publisher_copilot'),
        'insert_into_item' => __('درج در اشتراک', 'i8_publisher_copilot'),
        'uploaded_to_this_item' => __('در این اشتراک آپلود شد', 'i8_publisher_copilot'),
        'items_list' => __('لیست اشتراک‌ها', 'i8_publisher_copilot'),
        'items_list_navigation' => __('پیمایش فهرست اشتراک‌ها', 'i8_publisher_copilot'),
        'filter_items_list' => __('لیست اشتراک‌ها را فیلتر کنید', 'i8_publisher_copilot'),
    );
    $args = array(
        'label' => __('subscriptions', 'i8_publisher_copilot'),
        'description' => __('اشتراک ها', 'i8_publisher_copilot'),
        'labels' => $labels,
        'supports' => array('title', 'custom-fields'),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 6,
        'menu_icon' => $globalVarIconPostType,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'page',
    );
    register_post_type('subscriptions', $args);
}
add_action('init', 'subscriptions_post_type', 0);



// Post meta
function subscriptions_custom_meta_box()
{
    add_meta_box(
        'subscriptions_custom_meta_box',
        'جزییات اشتراک',
        'display_subscriptions_custom_meta_box',
        'subscriptions',
        'normal',
        'high'
    );
    add_meta_box(
        'subscriptions_history_meta_box',
        'تاریخچه تغییرات',
        'display_subscriptions_history_meta_box',
        'subscriptions',
        'normal',
        'low'
    );
}

function display_subscriptions_history_meta_box($post) {
    $logs = get_post_meta($post->ID, 'subscription_change_log', false);
    if (empty($logs)) {
        echo '<p style="padding: 12px; color: #6b7280; font-size: 13px;">هیچ تغییری تاکنون ثبت نشده است.</p>';
        return;
    }
    
    // Sort logs by timestamp descending
    usort($logs, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    echo '<div class="cop-admin-wrap" style="padding: 10px;">';
    foreach (array_slice($logs, 0, 15) as $log) {
        $date = date_i18n('Y-m-d H:i:s', strtotime($log['timestamp']));
        echo '<div class="cop-table-item" style="margin-bottom:8px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px;">';
        echo '<div style="display:flex; align-items:center; gap: 12px;">';
        echo '<div class="cop-item-accent primary" style="width: 4px; height: 100%; border-radius: 4px;"></div>';
        echo '<div style="flex-grow:1;">';
        echo '<div style="margin-bottom:6px;"><strong>' . esc_html($log['field']) . '</strong></div>';
        echo '<div style="font-size: 12px; color: #4b5563; margin-bottom: 6px;">';
        echo '<span class="cop-badge cop-badge-neutral">' . esc_html($log['old_value']) . '</span> &larr; <span class="cop-badge cop-badge-primary">' . esc_html($log['new_value']) . '</span>';
        echo '</div>';
        echo '<div style="font-size: 11px; color: #9ca3af;">توسط ' . esc_html($log['user']) . ' | ' . esc_html($date) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}
add_action('add_meta_boxes', 'subscriptions_custom_meta_box');

function display_subscriptions_custom_meta_box($post)
{
    // Retrieve saved meta values
    $subscription_user_id = get_post_meta($post->ID, 'subscription_user_id', true);
    $subscription_site_url = get_post_meta($post->ID, 'subscription_site_url', true);
    $subscription_plan_id = get_post_meta($post->ID, 'subscription_plan_id', true);
    $subscription_resources_ids = get_post_meta($post->ID, 'subscription_resources_ids', true);
    $subscription_secret_code = get_post_meta($post->ID, 'subscription_secret_code', true);
    $subscription_extra_days = get_post_meta($post->ID, 'subscription_extra_days', true);
    $subscription_note = get_post_meta($post->ID, 'subscription_note', true);

    // Read absolute expiry date (Phase 9); calculate on-the-fly for old records
    $subscription_start_date = $post->post_date;
    $expiry_date = get_post_meta($post->ID, 'subscription_expiry_date', true);
    if (empty($expiry_date) && $subscription_plan_id) {
        $expiry_date = cop_calculate_expiry_date($post->ID);
        if ($expiry_date) {
            update_post_meta($post->ID, 'subscription_expiry_date', $expiry_date);
        }
    }

    $plan_data = $subscription_plan_id ? get_plan_data($subscription_plan_id) : false;
    $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;
    $plan_duration = isset($plan_data['plan_duration']) ? intval($plan_data['plan_duration']) : 0;

    $current_time = current_time('timestamp');
    $end_date_ts = $expiry_date ? strtotime($expiry_date) : 0;
    $grace_end_date_ts = $end_date_ts ? ($end_date_ts + ($plan_grace_period * DAY_IN_SECONDS)) : 0;

    $status_label = 'تنظیم نشده';
    $status_class = 'neutral';
    
    if ($subscription_plan_id && $plan_data && $end_date_ts) {
        if ($current_time <= $end_date_ts) {
            $status_label = 'فعال';
            $status_class = 'success';
        } elseif ($current_time <= $grace_end_date_ts) {
            $status_label = 'دوره ارفاق';
            $status_class = 'warning';
        } else {
            $status_label = 'منقضی شده';
            $status_class = 'danger';
        }
    }
    ?>
    <div class="cop-admin-wrap">
        <div class="cop-metabox-wrap">
            <?php wp_nonce_field('cop_save_subscriptions_meta', 'cop_subscriptions_nonce'); ?>
            
            <div class="cop-form-grid grid-2">
                
                <!-- Card 1: Connection -->
                <div class="cop-form-card">
                    <h3 class="cop-form-card-title">
                        <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                        اطلاعات اتصال
                    </h3>
                    
                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_user_id">کاربر مرتبط:</label>
                        <div class="cop-input-wrap" style="display:block;">
                            <?php cop_list_users_dropdown('subscription_user_id', 'subscription_user_id cop-input', 'subscription_user_id', $subscription_user_id); ?>
                        </div>
                    </div>

                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_site_url">آدرس سایت کلاینت:</label>
                        <div class="cop-input-wrap">
                            <input type="url" id="subscription_site_url" name="subscription_site_url" class="cop-input" placeholder="https://example.com" value="<?php echo esc_attr($subscription_site_url); ?>" dir="ltr" style="text-align:left;">
                            <button type="button" id="cop_test_conn_btn" class="cop-btn-icon primary" title="تست اتصال"><svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" /></svg></button>
                        </div>
                        <div id="cop_conn_result" style="margin-top:8px;font-size:11px;font-weight:600;"></div>
                    </div>

                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_secret_code">کد لایسنس:</label>
                        <div class="cop-input-wrap">
                            <input type="text" id="subscription_secret_code" name="subscription_secret_code" class="cop-input" readonly value="<?php echo esc_attr($subscription_secret_code); ?>" dir="ltr" style="text-align:left; background:#f8fafc!important;">
                            <button type="button" id="cop_copy_license_btn" class="cop-btn-icon" title="کپی لایسنس"><svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg></button>
                            <button type="button" id="cop_renew_license_btn" class="cop-btn-icon danger" title="تجدید لایسنس"><svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg></button>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Plan & Quota -->
                <div class="cop-form-card">
                    <h3 class="cop-form-card-title">
                        <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                        پلن و سهمیه
                    </h3>

                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_plan_id">پلن اشتراکی:</label>
                        <div class="cop-input-wrap" style="display:block;">
                            <?php cop_plans_list_dropdown('subscription_plan_id', 'subscription_plan_id cop-input', 'subscription_plan_id', $subscription_plan_id); ?>
                        </div>
                    </div>

                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_extra_days">روزهای تشویقی / تمدید:</label>
                        <div class="cop-input-wrap">
                            <input type="number" name="subscription_extra_days" id="subscription_extra_days" class="cop-input" value="<?php echo $subscription_extra_days; ?>" >
                        </div>
                        <span class="cop-help-text">این مقدار با دوره اعتبار پلن جمع می‌شود. برای تمدید دستی، تعداد روزها را افزایش دهید.</span>
                    </div>

                    <div class="cop-field-group">
                        <label class="cop-label" for="subscription_resources_ids">منابع مجاز:</label>
                        <div class="cop-input-wrap" style="display:block;">
                            <?php cop_resources_list_dropdown('subscription_resources_ids', 'subscription_resources_ids cop-input', 'subscription_resources_ids', (array) $subscription_resources_ids); ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="cop-form-grid grid-full" style="margin-top:20px;">
                <!-- Card 3: Status & Dates -->
                <div class="cop-form-card" style="background:var(--cop-slate-50);">
                    <h3 class="cop-form-card-title">
                        <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        زمان‌بندی و وضعیت
                    </h3>
                    
                    <?php if ($subscription_plan_id && $plan_data && $end_date_ts): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                            <span style="font-size: 13px; color: var(--cop-slate-600); font-weight: 600;">وضعیت اشتراک: </span>
                            <span class="cop-badge cop-badge-<?php echo $status_class; ?>" style="font-size:12px;"><?php echo $status_label; ?></span>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <div style="font-size: 13px; color: var(--cop-slate-600); display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                                <span style="background:#fff; padding:6px 12px; border-radius:6px; border:1px solid var(--cop-slate-200);">
                                    📅 تاریخ شروع: <b style="color:var(--cop-slate-800); margin-right:4px;"><?php echo date_i18n('Y-m-d', strtotime($subscription_start_date)); ?></b>
                                </span>
                                <span style="background:#fff; padding:6px 12px; border-radius:6px; border:1px solid var(--cop-slate-200);">
                                    ⌛ تاریخ انقضا: <b style="color:var(--cop-slate-800); margin-right:4px;"><?php echo date_i18n('Y-m-d', $end_date_ts); ?></b>
                                </span>
                                <?php if ($plan_grace_period > 0): ?>
                                <span style="background:var(--cop-warning-light, #fffbeb); padding:6px 12px; border-radius:6px; border:1px solid #fde68a; color:#92400e;">
                                    🛡️ پایان ارفاق: <b style="margin-right:4px;"><?php echo date_i18n('Y-m-d', $grace_end_date_ts); ?></b>
                                </span>
                                <?php endif; ?>
                            </div>
                            <!-- Smart Renewal Button -->
                            <button type="button" id="cop_smart_renew_btn"
                                data-post-id="<?php echo $post->ID; ?>"
                                data-plan-days="<?php echo $plan_duration; ?>"
                                data-nonce="<?php echo wp_create_nonce('cop_ajax_nonce'); ?>"
                                class="cop-btn <?php echo ($status_class === 'danger') ? 'cop-btn-danger' : 'cop-btn-success'; ?>"
                                style="gap:6px;">
                                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                <?php echo ($status_class === 'danger') ? 'تمدید مجدد' : 'تمدید / رزرو تمدید'; ?>
                            </button>
                        </div>
                    </div>
                    <div id="cop_renew_result" style="margin-top:12px;"></div>

                    <!-- Renewal modal inline -->
                    <div id="cop_renew_modal" style="display:none; margin-top:12px; padding:16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
                        <p style="margin:0 0 10px; font-weight:600; color:#166534; font-size:14px;">
                            <?php echo ($status_class === 'danger') ? '⚠️ اشتراک منقضی شده — تمدید از امروز شروع می‌شود:' : '✅ اشتراک فعال است — تمدید از پایان دوره فعلی اعمال می‌شود (رزرو خودکار):'; ?>
                        </p>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <select id="cop_renew_days_select" style="height:36px; padding:0 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                <option value="30">۳۰ روز</option>
                                <option value="60">۶۰ روز</option>
                                <option value="90">۹۰ روز (۳ ماه)</option>
                                <option value="180">۱۸۰ روز (۶ ماه)</option>
                                <option value="<?php echo $plan_duration; ?>" selected>مطابق پلن (<?php echo $plan_duration; ?> روز)</option>
                                <option value="365">۳۶۵ روز (۱ سال)</option>
                            </select>
                            <button type="button" id="cop_renew_confirm_btn" class="cop-btn cop-btn-success">اعمال تمدید</button>
                            <button type="button" id="cop_renew_cancel_btn" class="cop-btn cop-btn-neutral">انصراف</button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="cop-alert cop-alert-warning" style="margin:0;">لطفا ابتدا یک پلن انتخاب کنید و ذخیره نمایید تا زمان‌بندی محاسبه شود.</div>
                    <?php endif; ?>
                </div>

                <!-- Card 4: Note -->
                <div class="cop-form-card">
                    <h3 class="cop-form-card-title">
                        <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        یادداشت مدیر (صرفاً برای استفاده داخلی)
                    </h3>
                    <div class="cop-field-group" style="margin:0;">
                        <textarea name="subscription_note" id="subscription_note" class="cop-input" rows="3" placeholder="مثال: این اشتراک متعلق به خبرگزاری فلانی است..."><?php echo esc_textarea($subscription_note); ?></textarea>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Copy License
        $('#cop_copy_license_btn').on('click', function(e) {
            e.preventDefault();
            var copyText = document.getElementById('subscription_secret_code');
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            
            var btn = $(this);
            var oldHtml = btn.html();
            btn.html('<svg style="width:16px;height:16px;color:var(--cop-success);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>');
            setTimeout(function() { btn.html(oldHtml); }, 2000);
        });

        // Test Connection
        $('#cop_test_conn_btn').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var url = $('#subscription_site_url').val();
            var resDiv = $('#cop_conn_result');
            
            if(!url) {
                resDiv.html('<span style="color:var(--cop-danger);">ابتدا آدرس را وارد کنید.</span>');
                return;
            }
            
            btn.prop('disabled', true).css('opacity', '0.5');
            resDiv.html('<span style="color:var(--cop-slate-500);">در حال بررسی...</span>');
            
            $.post(ajaxurl, {
                action: 'cop_test_connection',
                nonce: '<?php echo wp_create_nonce("cop_ajax_nonce"); ?>',
                url: url
            }, function(response) {
                btn.prop('disabled', false).css('opacity', '1');
                if(response.success) {
                    resDiv.html('<span style="color:var(--cop-success);">✅ ' + response.data + '</span>');
                } else {
                    resDiv.html('<span style="color:var(--cop-danger);">❌ ' + response.data + '</span>');
                }
            }).fail(function() {
                btn.prop('disabled', false).css('opacity', '1');
                resDiv.html('<span style="color:var(--cop-danger);">❌ خطای ارتباط با سرور.</span>');
            });
        });

        // Renew License
        $('#cop_renew_license_btn').on('click', function(e) {
            e.preventDefault();
            if(!confirm('آیا از تجدید لایسنس مطمئن هستید؟ کلاینت باید لایسنس جدید را در تنظیمات خود وارد کند.')) return;
            
            var btn = $(this);
            var post_id = <?php echo isset($post->ID) ? $post->ID : 0; ?>;
            if(!post_id) {
                alert('لطفاً ابتدا اشتراک را ذخیره کنید.');
                return;
            }
            
            btn.prop('disabled', true).css('opacity', '0.5');
            
            $.post(ajaxurl, {
                action: 'cop_renew_license',
                nonce: '<?php echo wp_create_nonce("cop_ajax_nonce"); ?>',
                post_id: post_id
            }, function(response) {
                btn.prop('disabled', false).css('opacity', '1');
                if(response.success) {
                    $('#subscription_secret_code').val(response.data.new_code);
                    alert('لایسنس با موفقیت تجدید شد.');
                } else {
                    alert('خطا: ' + response.data);
                }
            }).fail(function() {
                btn.prop('disabled', false).css('opacity', '1');
                alert('خطای ارتباط با سرور.');
            });
        });

        // Smart Renewal (Phase 9)
        $('#cop_smart_renew_btn').on('click', function(e) {
            e.preventDefault();
            $('#cop_renew_modal').slideDown(200);
        });
        $('#cop_renew_cancel_btn').on('click', function(e) {
            e.preventDefault();
            $('#cop_renew_modal').slideUp(200);
        });
        $('#cop_renew_confirm_btn').on('click', function(e) {
            e.preventDefault();
            var days = $('#cop_renew_days_select').val();
            var btn = $(this);
            var postId = $('#cop_smart_renew_btn').data('post-id');
            var nonce = $('#cop_smart_renew_btn').data('nonce');
            
            btn.prop('disabled', true).text('در حال پردازش...');
            
            $.post(ajaxurl, {
                action: 'cop_renew_subscription_ajax',
                nonce: nonce,
                post_id: postId,
                days: days
            }, function(response) {
                btn.prop('disabled', false).text('اعمال تمدید');
                if (response.success) {
                    $('#cop_renew_result').html('<div style="padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; color:#166534; font-size:13px; margin-top:10px;">'
                        + '✅ <strong>تمدید با موفقیت انجام شد!</strong> '
                        + 'تاریخ انقضای جدید: <strong>' + response.data.new_expiry_formatted + '</strong>'
                        + '</div>');
                    $('#cop_renew_modal').slideUp(200);
                    // Reload page after 2s so status badge refreshes
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    alert('خطا: ' + response.data);
                }
            }).fail(function() {
                btn.prop('disabled', false).text('اعمال تمدید');
                alert('خطای ارتباط با سرور.');
            });
        });
    });
    </script>
    <?php
}

function cop_add_subscription_log($post_id, $field, $old_val, $new_val) {
    if ($old_val == $new_val) return;
    $user = wp_get_current_user();
    $log = array(
        'timestamp' => current_time('mysql'),
        'user' => $user->display_name . ' (ID: ' . $user->ID . ')',
        'field' => $field,
        'old_value' => $old_val,
        'new_value' => $new_val
    );
    add_post_meta($post_id, 'subscription_change_log', $log);
}

function save_subscriptions_custom_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Verify nonce
    if (!isset($_POST['cop_subscriptions_nonce']) || !wp_verify_nonce($_POST['cop_subscriptions_nonce'], 'cop_save_subscriptions_meta')) {
        return;
    }

    // Check post type
    if (get_post_type($post_id) !== 'subscriptions') {
        return;
    }

    // Fetch old values to clear cache and log changes
    $old_url = get_post_meta($post_id, 'subscription_site_url', true);
    $old_secret = get_post_meta($post_id, 'subscription_secret_code', true);
    $old_plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
    $old_extra_days = get_post_meta($post_id, 'subscription_extra_days', true);
    $old_resources = get_post_meta($post_id, 'subscription_resources_ids', true);

    // Save meta values
    if (isset($_POST['subscription_user_id'])) {
        update_post_meta($post_id, 'subscription_user_id', sanitize_text_field($_POST['subscription_user_id']));
    }
    if (isset($_POST['subscription_site_url'])) {
        update_post_meta($post_id, 'subscription_site_url', esc_url_raw($_POST['subscription_site_url']));
    }
    if (isset($_POST['subscription_plan_id'])) { 
        update_post_meta($post_id, 'subscription_plan_id', sanitize_text_field($_POST['subscription_plan_id']));
    }
    if (isset($_POST['subscription_resources_ids'])) {
        $resources_ids = array_map('intval', $_POST['subscription_resources_ids']);
        update_post_meta($post_id, 'subscription_resources_ids', $resources_ids);
    } else {
        delete_post_meta($post_id, 'subscription_resources_ids');
    }
    if (isset($_POST['subscription_extra_days'])) {
        update_post_meta($post_id, 'subscription_extra_days', sanitize_text_field($_POST['subscription_extra_days']));
    }
    if (isset($_POST['subscription_note'])) {
        update_post_meta($post_id, 'subscription_note', sanitize_textarea_field($_POST['subscription_note']));
    }
    if (empty(get_post_meta($post_id, 'subscription_secret_code', true))) {
        $seceret_code = generate_secret_code();
        update_post_meta($post_id, 'subscription_secret_code', sanitize_text_field($seceret_code));
    }
    
    // Clear transients
    if ($old_url && $old_secret) {
        delete_transient('cop_val_' . md5($old_url . '_' . $old_secret));
    }
    
    $new_url = get_post_meta($post_id, 'subscription_site_url', true);
    $new_secret = get_post_meta($post_id, 'subscription_secret_code', true);
    $new_plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
    $new_extra_days = get_post_meta($post_id, 'subscription_extra_days', true);
    $new_resources = get_post_meta($post_id, 'subscription_resources_ids', true);
    
    // Log changes (Phase 7)
    if ($old_plan_id != $new_plan_id) {
        $old_title = $old_plan_id ? get_the_title($old_plan_id) : 'بدون پلن';
        $new_title = $new_plan_id ? get_the_title($new_plan_id) : 'بدون پلن';
        cop_add_subscription_log($post_id, 'پلن اشتراک', $old_title, $new_title);
    }
    if ($old_extra_days != $new_extra_days) {
        cop_add_subscription_log($post_id, 'روزهای ارفاقی', $old_extra_days, $new_extra_days);
    }
    if ($old_secret != $new_secret && $old_secret != '') {
        cop_add_subscription_log($post_id, 'کد لایسنس', 'کد قبلی', 'تولید کد جدید');
    }
    if ($old_url != $new_url && $old_url != '') {
        cop_add_subscription_log($post_id, 'آدرس سایت', $old_url, $new_url);
    }
    
    if ($new_url && $new_secret && ($new_url !== $old_url || $new_secret !== $old_secret)) {
        delete_transient('cop_val_' . md5($new_url . '_' . $new_secret));
    }

    // ---------------------------------------------------------
    // Phase 9: Set/refresh subscription_expiry_date
    // We do this AFTER all fields are saved.
    // Rule: expiry_date is only written here if it hasn't been set yet
    // (new subscription), OR if plan/extra_days changed (which invalidates the existing date).
    // A manual renewal via cop_renew_subscription() writes its own expiry_date and
    // is NOT touched here to preserve the renewal date.
    // ---------------------------------------------------------
    $current_expiry = get_post_meta($post_id, 'subscription_expiry_date', true);
    $plan_changed = ($old_plan_id != $new_plan_id);
    $extra_days_changed = ($old_extra_days != $new_extra_days);
    
    if (empty($current_expiry) || ($plan_changed && !$extra_days_changed)) {
        // New sub, or plan changed without a manual extra_days override
        $calculated = cop_calculate_expiry_date($post_id);
        if ($calculated) {
            update_post_meta($post_id, 'subscription_expiry_date', $calculated);
            // Invalidate cache for new expiry as well
            if ($new_url && $new_secret) {
                delete_transient('cop_val_' . md5($new_url . '_' . $new_secret));
            }
        }
    } elseif ($extra_days_changed && !$plan_changed) {
        // extra_days changed: recalculate expiry from original plan start
        // This preserves the intent of the "grace/extra days" field for manual adjustments
        $calculated = cop_calculate_expiry_date($post_id);
        if ($calculated) {
            update_post_meta($post_id, 'subscription_expiry_date', $calculated);
            if ($new_url && $new_secret) {
                delete_transient('cop_val_' . md5($new_url . '_' . $new_secret));
            }
        }
    }

    // ---------------------------------------------------------
    // Phase 5.3 & 5.4: Save Validations & Warnings
    // ---------------------------------------------------------
    $user_id = get_current_user_id();
    
    // 5.3 Unique URL check
    if (!empty($new_url)) {
        $norm_new_url = cop_normalize_url($new_url);
        $duplicate_query = new WP_Query(array(
            'post_type' => 'subscriptions',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'post__not_in' => array($post_id),
            'fields' => 'ids'
        ));
        
        $is_duplicate = false;
        foreach ($duplicate_query->posts as $dup_id) {
            $dup_url = get_post_meta($dup_id, 'subscription_site_url', true);
            if ($dup_url && cop_normalize_url($dup_url) === $norm_new_url) {
                $is_duplicate = true;
                break;
            }
        }
        if ($is_duplicate) {
            set_transient('cop_sub_duplicate_url_' . $user_id, 'هشدار: آدرس سایتی که وارد کردید (' . $new_url . ') قبلاً برای اشتراک دیگری ثبت شده است. این موضوع ممکن است باعث تداخل در عملکرد کلاینت‌ها شود.', 45);
        }
    }

    // 5.4 Quota Check
    if (isset($_POST['subscription_plan_id'])) {
        $plan_id = intval($_POST['subscription_plan_id']);
        $max_resources = intval(get_post_meta($plan_id, 'plan_max_resources', true));
        
        if ($max_resources > 0 && isset($_POST['subscription_resources_ids'])) {
            $res_count = count($_POST['subscription_resources_ids']);
            if ($res_count > $max_resources) {
                set_transient('cop_sub_quota_exceeded_' . $user_id, 'هشدار: تعداد منابع انتخابی شما (' . $res_count . ') از سقف مجاز پلن (' . $max_resources . ' منبع) بیشتر است. کلاینت فقط به اندازه سقف مجاز به منابع دسترسی خواهد داشت.', 45);
            }
        }
    }
}
add_action('save_post_subscriptions', 'save_subscriptions_custom_meta_box');

// Admin notices for save warnings
add_action('admin_notices', 'cop_subscriptions_admin_notices');
function cop_subscriptions_admin_notices() {
    $user_id = get_current_user_id();
    
    $dup_msg = get_transient('cop_sub_duplicate_url_' . $user_id);
    if ($dup_msg) {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>توجه:</strong> ' . esc_html($dup_msg) . '</p></div>';
        delete_transient('cop_sub_duplicate_url_' . $user_id);
    }
    
    $quota_msg = get_transient('cop_sub_quota_exceeded_' . $user_id);
    if ($quota_msg) {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>توجه:</strong> ' . esc_html($quota_msg) . '</p></div>';
        delete_transient('cop_sub_quota_exceeded_' . $user_id);
    }
}

// ---------------------------------------------------------
// Phase 3: Subscription List UX
// ---------------------------------------------------------

// 3.1 Columns for Subscriptions
add_filter('manage_subscriptions_posts_columns', 'cop_manage_subscriptions_columns');
function cop_manage_subscriptions_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        if ($key === 'title') {
            $new_columns[$key] = $title;
            $new_columns['sub_url'] = 'آدرس سایت';
            $new_columns['sub_plan'] = 'پلن';
            $new_columns['sub_status'] = 'وضعیت';
            $new_columns['sub_expiry'] = 'تاریخ انقضا';
            $new_columns['sub_resources'] = 'منابع';
        } else {
            $new_columns[$key] = $title;
        }
    }
    return $new_columns;
}

add_action('manage_subscriptions_posts_custom_column', 'cop_manage_subscriptions_custom_column', 10, 2);
function cop_manage_subscriptions_custom_column($column, $post_id) {
    switch ($column) {
        case 'sub_url':
            $url = get_post_meta($post_id, 'subscription_site_url', true);
            if ($url) {
                echo '<a href="' . esc_url($url) . '" target="_blank" class="cop-btn cop-btn-neutral" style="font-size:11px; padding:2px 8px; font-family:tahoma,sans-serif;" dir="ltr">' . esc_html(str_replace(array('http://', 'https://'), '', $url)) . ' <svg style="width:12px;height:12px;margin-right:4px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg></a>';
            } else {
                echo '—';
            }
            break;
        case 'sub_plan':
            $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
            if ($plan_id) {
                $plan = get_post($plan_id);
                echo $plan ? '<a href="' . get_edit_post_link($plan_id) . '" style="font-weight:bold;text-decoration:none;">' . esc_html($plan->post_title) . '</a>' : '—';
            } else {
                echo '—';
            }
            break;
        case 'sub_status':
            $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
            $status_label = 'نامشخص';
            $status_class = 'neutral';
            
            if ($plan_id) {
                $expiry_date = get_post_meta($post_id, 'subscription_expiry_date', true);
                if (empty($expiry_date)) {
                    $expiry_date = cop_calculate_expiry_date($post_id);
                }
                if ($expiry_date) {
                    $grace_period = (int) get_post_meta($plan_id, 'plan_grace_period', true);
                    $end_date_ts = strtotime($expiry_date);
                    $grace_end_ts = $end_date_ts + ($grace_period * DAY_IN_SECONDS);
                    $current_ts = current_time('timestamp');
                    if ($current_ts <= $end_date_ts) {
                        $status_label = 'فعال';
                        $status_class = 'success';
                    } elseif ($current_ts <= $grace_end_ts) {
                        $status_label = 'دوره ارفاق';
                        $status_class = 'warning';
                    } else {
                        $status_label = 'منقضی';
                        $status_class = 'danger';
                    }
                }
            }
            echo '<span class="cop-badge cop-badge-' . $status_class . '">' . $status_label . '</span>';
            break;
        case 'sub_expiry':
            $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
            if ($plan_id) {
                $expiry_date = get_post_meta($post_id, 'subscription_expiry_date', true);
                if (empty($expiry_date)) {
                    $expiry_date = cop_calculate_expiry_date($post_id);
                }
                if ($expiry_date) {
                    $end_date_ts = strtotime($expiry_date);
                    $days_left = floor(($end_date_ts - current_time('timestamp')) / DAY_IN_SECONDS);
                    $date_str = date_i18n('Y-m-d', $end_date_ts);
                    if ($days_left >= 0 && $days_left <= 7) {
                        echo '<span class="cop-badge cop-badge-warning" title="' . $date_str . '">⚠️ ' . $days_left . ' روز مانده</span>';
                    } else {
                        echo esc_html($date_str);
                    }
                } else {
                    echo '—';
                }
            } else {
                echo '—';
            }
            break;
        case 'sub_resources':
            $resources = get_post_meta($post_id, 'subscription_resources_ids', true);
            $count = !empty($resources) && is_array($resources) ? count($resources) : 0;
            echo '<span class="cop-badge cop-badge-neutral">' . $count . ' منبع</span>';
            break;
    }
}

// 3.2 Status Filter Dropdown
add_action('restrict_manage_posts', 'cop_subscriptions_status_filter');
function cop_subscriptions_status_filter($post_type) {
    if ($post_type === 'subscriptions') {
        $selected = isset($_GET['sub_status_filter']) ? sanitize_text_field($_GET['sub_status_filter']) : '';
        echo '<select name="sub_status_filter" id="sub_status_filter">';
        echo '<option value="">تمام وضعیت‌ها</option>';
        echo '<option value="active" ' . selected($selected, 'active', false) . '>فعال</option>';
        echo '<option value="grace" ' . selected($selected, 'grace', false) . '>دوره ارفاق</option>';
        echo '<option value="expired" ' . selected($selected, 'expired', false) . '>منقضی</option>';
        echo '</select>';
    }
}

add_filter('parse_query', 'cop_subscriptions_filter_query');
function cop_subscriptions_filter_query($query) {
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'subscriptions' && isset($_GET['sub_status_filter']) && $_GET['sub_status_filter'] !== '') {
        $status_filter = sanitize_text_field($_GET['sub_status_filter']);
        
        $all_subs = get_posts(array(
            'post_type' => 'subscriptions',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $filtered_ids = array();
        $current_ts = current_time('timestamp');
        
        foreach ($all_subs as $post_id) {
            $plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
            if (!$plan_id) continue;
            
            // Use absolute expiry_date (Phase 9)
            $expiry_date = get_post_meta($post_id, 'subscription_expiry_date', true);
            if (empty($expiry_date)) {
                $expiry_date = cop_calculate_expiry_date($post_id);
            }
            if (!$expiry_date) continue;
            
            $grace_period = (int) get_post_meta($plan_id, 'plan_grace_period', true);
            $end_date_ts = strtotime($expiry_date);
            $grace_end_ts = $end_date_ts + ($grace_period * DAY_IN_SECONDS);
            
            $status = 'expired';
            if ($current_ts <= $end_date_ts) {
                $status = 'active';
            } elseif ($current_ts <= $grace_end_ts) {
                $status = 'grace';
            }
            
            if ($status === $status_filter) {
                $filtered_ids[] = $post_id;
            }
        }
        
        if (empty($filtered_ids)) {
            $filtered_ids = array(0);
        }
        
        $query->query_vars['post__in'] = $filtered_ids;
    }
}

// 3.3 Advanced Search (URL or Secret Code)
add_filter('posts_search', 'cop_subscriptions_search_by_url_or_code', 10, 2);
function cop_subscriptions_search_by_url_or_code($search, $wp_query) {
    global $wpdb;
    if (is_admin() && $wp_query->is_main_query() && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] === 'subscriptions') {
        $search_term = $wp_query->query_vars['s'] ?? '';
        if (!empty($search_term)) {
            $search_term = esc_sql($wpdb->esc_like($search_term));
            
            $search = " AND (
                ({$wpdb->posts}.post_title LIKE '%{$search_term}%')
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} AS pm
                    WHERE pm.post_id = {$wpdb->posts}.ID
                    AND (pm.meta_key = 'subscription_site_url' OR pm.meta_key = 'subscription_secret_code')
                    AND pm.meta_value LIKE '%{$search_term}%'
                )
            )";
        }
    }
    return $search;
}

// ---------------------------------------------------------
// Phase 4: Subscription Form AJAX Handlers
// ---------------------------------------------------------

add_action('wp_ajax_cop_test_connection', 'cop_ajax_test_connection');
function cop_ajax_test_connection() {
    check_ajax_referer('cop_ajax_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('عدم دسترسی');
    $url = esc_url_raw($_POST['url']);
    if (empty($url)) wp_send_json_error('آدرس خالی است');
    $response = wp_remote_head($url, array('timeout' => 5));
    if (is_wp_error($response)) {
        wp_send_json_error('خطا در اتصال: ' . $response->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 200 && $code < 400) {
        wp_send_json_success('اتصال موفقیت آمیز بود. کد: ' . $code);
    } else {
        wp_send_json_error('پاسخ سرور: ' . $code);
    }
}

add_action('wp_ajax_cop_renew_license', 'cop_ajax_renew_license');
function cop_ajax_renew_license() {
    check_ajax_referer('cop_ajax_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('عدم دسترسی');
    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('شناسه اشتراک نامعتبر');
    
    $new_secret = generate_secret_code(16);
    update_post_meta($post_id, 'subscription_secret_code', $new_secret);
    
    $url = get_post_meta($post_id, 'subscription_site_url', true);
    if ($url) delete_transient('cop_val_' . md5($url . '_' . $new_secret));
    
    wp_send_json_success(array('new_code' => $new_secret));
}

// ---------------------------------------------------------
// Phase 6: Bulk Actions (Renew & Change Plan)
// ---------------------------------------------------------

add_filter('bulk_actions-edit-subscriptions', 'cop_register_subscriptions_bulk_actions');
function cop_register_subscriptions_bulk_actions($bulk_actions) {
    $bulk_actions['cop_bulk_renew'] = 'تمدید دسته‌ای';
    $bulk_actions['cop_bulk_change_plan'] = 'تغییر دسته‌ای پلن';
    return $bulk_actions;
}

add_filter('handle_bulk_actions-edit-subscriptions', 'cop_handle_subscriptions_bulk_actions', 10, 3);
function cop_handle_subscriptions_bulk_actions($redirect_to, $doaction, $post_ids) {
    if ($doaction === 'cop_bulk_renew') {
        $days = isset($_REQUEST['cop_renew_days']) ? intval($_REQUEST['cop_renew_days']) : 0;
        if ($days > 0) {
            foreach ($post_ids as $post_id) {
                // B-02 Fix: use cop_renew_subscription() which correctly extends expiry_date
                $old_expiry = get_post_meta($post_id, 'subscription_expiry_date', true);
                $old_label = $old_expiry ? date_i18n('Y-m-d', strtotime($old_expiry)) : 'نامشخص';
                $new_expiry = cop_renew_subscription($post_id, $days);
                if ($new_expiry && function_exists('cop_add_subscription_log')) {
                    cop_add_subscription_log($post_id, 'تمدید دسته‌ای (' . $days . ' روز)', $old_label, date_i18n('Y-m-d', strtotime($new_expiry)));
                }
            }
            $redirect_to = add_query_arg('cop_bulk_renewed', count($post_ids), $redirect_to);
        }
    } elseif ($doaction === 'cop_bulk_change_plan') {
        $plan_id = isset($_REQUEST['cop_new_plan_id']) ? intval($_REQUEST['cop_new_plan_id']) : 0;
        if ($plan_id > 0) {
            $plan_title = get_the_title($plan_id);
            foreach ($post_ids as $post_id) {
                $old_plan_id = get_post_meta($post_id, 'subscription_plan_id', true);
                if ($old_plan_id != $plan_id) {
                    update_post_meta($post_id, 'subscription_plan_id', $plan_id);
                    $old_plan_title = $old_plan_id ? get_the_title($old_plan_id) : 'ندارد';
                    if (function_exists('cop_add_subscription_log')) {
                        cop_add_subscription_log($post_id, 'تغییر دسته‌ای پلن', $old_plan_title, $plan_title);
                    }
                    // Note: expiry_date is intentionally NOT recalculated here.
                    // Changing plan changes quota/features, not the subscription period.
                    // Admin must manually renew if they also want to change the period.
                }
            }
            $redirect_to = add_query_arg('cop_bulk_plan_changed', count($post_ids), $redirect_to);
        }
    }
    return $redirect_to;
}

add_action('admin_notices', 'cop_bulk_actions_success_notices');
function cop_bulk_actions_success_notices() {
    if (!empty($_REQUEST['cop_bulk_renewed'])) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>موفقیت‌آمیز:</strong> ' . intval($_REQUEST['cop_bulk_renewed']) . ' اشتراک با موفقیت تمدید شدند.</p></div>';
    }
    if (!empty($_REQUEST['cop_bulk_plan_changed'])) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>موفقیت‌آمیز:</strong> پلن ' . intval($_REQUEST['cop_bulk_plan_changed']) . ' اشتراک با موفقیت تغییر کرد.</p></div>';
    }
}

add_action('admin_footer-edit.php', 'cop_subscriptions_bulk_modals');
function cop_subscriptions_bulk_modals() {
    global $typenow;
    if ($typenow !== 'subscriptions') return;
    
    $plans = get_posts(array('post_type' => 'plans', 'posts_per_page' => -1, 'post_status' => 'publish'));
    ?>
    <style>
    .cop-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; direction: rtl; }
    .cop-modal-overlay.open { display: flex; }
    .cop-modal-box { background: #fff; border-radius: 8px; width: 400px; max-width: 90%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: tahoma, sans-serif; }
    .cop-modal-header { padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .cop-modal-title { margin: 0; font-size: 16px; font-weight: 600; color: #111827; }
    .cop-modal-body { padding: 16px; }
    .cop-modal-footer { padding: 16px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px; }
    </style>
    
    <div class="cop-modal-overlay" id="cop_bulk_renew_modal">
        <div class="cop-modal-box">
            <div class="cop-modal-header">
                <h3 class="cop-modal-title">تمدید دسته‌ای اشتراک‌ها</h3>
                <button type="button" class="cop-modal-close" style="background:none; border:none; cursor:pointer; font-size:18px;">✕</button>
            </div>
            <div class="cop-modal-body">
                <label style="display:block; margin-bottom:8px; font-size:13px;">تعداد روزهایی که می‌خواهید به این اشتراک‌ها اضافه شود:</label>
                <input type="number" id="cop_bulk_renew_days" class="regular-text" placeholder="مثال: 30" style="width:100%;">
            </div>
            <div class="cop-modal-footer">
                <button type="button" class="cop-modal-close button">انصراف</button>
                <button type="button" id="cop_bulk_renew_submit" class="button button-primary">اعمال تمدید</button>
            </div>
        </div>
    </div>
    
    <div class="cop-modal-overlay" id="cop_bulk_plan_modal">
        <div class="cop-modal-box">
            <div class="cop-modal-header">
                <h3 class="cop-modal-title">تغییر دسته‌ای پلن</h3>
                <button type="button" class="cop-modal-close" style="background:none; border:none; cursor:pointer; font-size:18px;">✕</button>
            </div>
            <div class="cop-modal-body">
                <label style="display:block; margin-bottom:8px; font-size:13px;">پلن جدید را انتخاب کنید:</label>
                <select id="cop_bulk_plan_select" style="width:100%;">
                    <option value="">انتخاب پلن...</option>
                    <?php foreach ($plans as $plan) : ?>
                        <option value="<?php echo esc_attr($plan->ID); ?>"><?php echo esc_html($plan->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cop-modal-footer">
                <button type="button" class="cop-modal-close button">انصراف</button>
                <button type="button" id="cop_bulk_plan_submit" class="button button-primary">تغییر پلن</button>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).siblings('select').val();
            if (action === 'cop_bulk_renew' || action === 'cop_bulk_change_plan') {
                var selectedPosts = $('input[name="post[]"]:checked').length;
                if (selectedPosts === 0) {
                    alert('لطفاً حداقل یک مورد را انتخاب کنید.');
                    e.preventDefault();
                    return;
                }
                e.preventDefault();
                if (action === 'cop_bulk_renew') {
                    $('#cop_bulk_renew_modal').addClass('open');
                } else {
                    $('#cop_bulk_plan_modal').addClass('open');
                }
            }
        });

        $('#cop_bulk_renew_submit').on('click', function() {
            var days = $('#cop_bulk_renew_days').val();
            if (days) {
                $('<input>').attr({type: 'hidden', name: 'cop_renew_days', value: days}).appendTo('#posts-filter');
                $('#posts-filter').submit();
            }
        });
        
        $('#cop_bulk_plan_submit').on('click', function() {
            var plan = $('#cop_bulk_plan_select').val();
            if (plan) {
                $('<input>').attr({type: 'hidden', name: 'cop_new_plan_id', value: plan}).appendTo('#posts-filter');
                $('#posts-filter').submit();
            }
        });
        
        $('.cop-modal-close').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.cop-modal-overlay').removeClass('open');
        });
    });
    </script>
    <?php
}

// ---------------------------------------------------------
// Phase 9: AJAX Handler — Smart Subscription Renewal
// ---------------------------------------------------------
add_action('wp_ajax_cop_renew_subscription_ajax', 'cop_ajax_renew_subscription_handler');
function cop_ajax_renew_subscription_handler() {
    check_ajax_referer('cop_ajax_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('عدم دسترسی');
    
    $post_id = intval($_POST['post_id']);
    $days = intval($_POST['days']);
    
    if (!$post_id || $days <= 0) {
        wp_send_json_error('پارامترهای نامعتبر');
    }
    
    // Read current expiry before renewal (for log)
    $old_expiry = get_post_meta($post_id, 'subscription_expiry_date', true);
    
    $new_expiry = cop_renew_subscription($post_id, $days);
    
    if (!$new_expiry) {
        wp_send_json_error('خطا در تمدید اشتراک. لطفاً مطمئن شوید پلنی برای این اشتراک تنظیم شده است.');
    }
    
    // Log the renewal
    if (function_exists('cop_add_subscription_log')) {
        $old_label = $old_expiry ? date_i18n('Y-m-d', strtotime($old_expiry)) : 'نامشخص';
        $new_label = date_i18n('Y-m-d', strtotime($new_expiry));
        cop_add_subscription_log($post_id, 'تمدید اشتراک (' . $days . ' روز)', $old_label, $new_label);
    }
    
    wp_send_json_success(array(
        'new_expiry' => $new_expiry,
        'new_expiry_formatted' => date_i18n('Y-m-d', strtotime($new_expiry)),
    ));
}
