<?php

$assets_url = plugins_url('assets/user-cart.svg', dirname(__FILE__));
$globalVarIconPostType = $assets_url;


// Register subscriptions Post Type
function subscriptions_post_type()
{
    global $globalVarIconPostType;
    generate_secret_code(16);

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
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 6,
        'menu_icon' => $globalVarIconPostType,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => true,
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
    $subscription_extra_days = get_post_meta( $post->ID, 'subscription_extra_days', true );

    // Calculate subscription status dynamically
    $subscription_start_date = $post->post_date;
    $plan_data = $subscription_plan_id ? get_plan_data($subscription_plan_id) : false;
    $plan_duration = isset($plan_data['plan_duration']) ? intval($plan_data['plan_duration']) : 0;
    $plan_grace_period = isset($plan_data['plan_grace_period']) ? intval($plan_data['plan_grace_period']) : 0;
    $extra_days = $subscription_extra_days ? intval($subscription_extra_days) : 0;
    
    $end_date_ts = strtotime($subscription_start_date . ' + ' . $plan_duration . ' days + ' . $extra_days . ' days');
    $end_date = date('Y-m-d H:i:s', $end_date_ts);
    $grace_end_date_ts = strtotime(date('Y-m-d H:i:s', $end_date_ts) . ' + ' . $plan_grace_period . ' days');
    
    $current_time = current_time('timestamp');
    
    $status_label = 'تنظیم نشده';
    $status_color = '#64748b';
    
    if ($subscription_plan_id && $plan_data) {
        if ($current_time <= $end_date_ts) {
            $status_label = 'فعال (Active)';
            $status_color = '#10b981'; // Green
        } elseif ($current_time <= $grace_end_date_ts) {
            $status_label = 'دوره ارفاق (Grace Period)';
            $status_color = '#f59e0b'; // Amber
        } else {
            $status_label = 'منقضی شده (Expired)';
            $status_color = '#ef4444'; // Red
        }
    }
    ?>
    <style>
        .form-flex-container {
            display: flex;
            flex-wrap: wrap;
           
        }

        .half-width {
            flex: 0 0 49%;
        }

        .full-width {
            flex: 0 0 99%;
        }

        .third-width {
            flex: 0 0 25%;
        }
    </style>

    <div class="form-container form-flex-container" style="display: flex;flex-wrap: wrap;gap:1%;row-gap:20px;padding: 20px 0px;">
        <?php wp_nonce_field('cop_save_subscriptions_meta', 'cop_subscriptions_nonce'); ?>
        <div class="third-width">
            <label for="subscription_user_id" class="form-label">کاربر : </label><br>
            <?php cop_list_users_dropdown('subscription_user_id', 'subscription_user_id form-field form-input', 'subscription_user_id', $subscription_user_id); ?>
        </div>
        <div class="third-width">
            <label for="subscription_site_url" class="form-label">آدرس سایت : </label><br>
            <input type="url" id="subscription_site_url" name="subscription_site_url" class="form-field form-input "
                placeholder="https://example.com" value="<?php echo esc_attr($subscription_site_url); ?>">
        </div>
        <div class="third-width">
            <label for="subscription_plan_id" class="form-label">پلن : </label><br>
            <?php cop_plans_list_dropdown('subscription_plan_id', 'subscription_plan_id form-field form-input', 'subscription_plan_id', $subscription_plan_id); ?>
        </div>
        <div class="third-width">
            <label for="subscription_plan_extra_day" class="form-label">روز تشویقی : </label><br>
            <input type="number" name="subscription_extra_days" id="subscription_extra_days" class="form-field form-input"
            value="<?php echo $subscription_extra_days; ?>" >
        </div>
        <div class="full-width">
            <label for="subscription_resources_ids" class="form-label">منابع : </label><br>
            <?php cop_resources_list_dropdown('subscription_resources_ids', 'subscription_resources_ids form-field form-input', 'subscription_resources_ids', (array) $subscription_resources_ids); ?>
        </div>
        <div class="full-width" style="margin-top: 15px;">
            <label for="subscription_secret_code" class="form-label" style="font-weight: 700; color: #1e293b;">لایسنس کد اختصاصی : </label><br>
            <div style="display: flex; gap: 10px; align-items: center; margin-top: 5px;">
                <input type="text" id="subscription_secret_code" name="subscription_secret_code"
                    class="form-field form-input widefat " readonly value="<?php echo esc_attr($subscription_secret_code); ?>" style="direction:ltr;text-align:left;font-size:16px; height:40px; border-radius: 8px; border: 1.5px solid #cbd5e1; flex: 1; padding: 10px;">
                <button type="button" id="cop_copy_license_btn" class="button button-secondary" style="height: 40px; border-radius: 8px; font-weight: 600;">📋 کپی لایسنس</button>
            </div>
            <script>
                document.getElementById('cop_copy_license_btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    var copyText = document.getElementById('subscription_secret_code');
                    copyText.select();
                    copyText.setSelectionRange(0, 99999);
                    navigator.clipboard.writeText(copyText.value);
                    
                    var btn = this;
                    btn.innerText = '✅ کپی شد!';
                    setTimeout(function() {
                        btn.innerText = '📋 کپی لایسنس';
                    }, 2000);
                });
            </script>
        </div>

        <?php if ($subscription_plan_id && $plan_data): ?>
        <div class="full-width" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box;">
            <div>
                <span style="font-size: 13px; color: #64748b; font-weight: 600;">وضعیت اشتراک: </span>
                <span style="background: <?php echo $status_color; ?>; color: #fff; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; margin-right: 5px;"><?php echo $status_label; ?></span>
            </div>
            <div style="font-size: 13px; color: #475569;">
                <span>📅 تاریخ شروع: <b><?php echo date('Y-m-d', strtotime($subscription_start_date)); ?></b></span>
                <span style="margin: 0 10px; color:#cbd5e1;">|</span>
                <span>⌛ تاریخ انقضا: <b><?php echo date('Y-m-d', $end_date_ts); ?></b></span>
                <?php if ($plan_grace_period > 0): ?>
                <span style="margin: 0 10px; color:#cbd5e1;">|</span>
                <span style="color: #b45309;">🛡️ پایان دوره ارفاق: <b><?php echo date('Y-m-d', $grace_end_date_ts); ?></b></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
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

    // Fetch old values to clear cache
    $old_url = get_post_meta($post_id, 'subscription_site_url', true);
    $old_secret = get_post_meta($post_id, 'subscription_secret_code', true);

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
    }
    if (isset($_POST['subscription_extra_days'])) {
        update_post_meta($post_id, 'subscription_extra_days', sanitize_text_field($_POST['subscription_extra_days']));
    }
    if (!isset($_POST['subscription_secret_code']) && (get_post_meta($post_id, 'subscription_secret_code', true) == '')) {
        $seceret_code = generate_secret_code();
        update_post_meta($post_id, 'subscription_secret_code', sanitize_text_field($seceret_code));
    }
    
    // Clear transients
    if ($old_url && $old_secret) {
        delete_transient('cop_val_' . md5($old_url . '_' . $old_secret));
    }
    
    $new_url = get_post_meta($post_id, 'subscription_site_url', true);
    $new_secret = get_post_meta($post_id, 'subscription_secret_code', true);
    if ($new_url && $new_secret && ($new_url !== $old_url || $new_secret !== $old_secret)) {
        delete_transient('cop_val_' . md5($new_url . '_' . $new_secret));
    }
}
add_action('save_post_subscriptions', 'save_subscriptions_custom_meta_box');
