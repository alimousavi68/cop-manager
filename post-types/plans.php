<?php
// Public Variable 
// $assets_src = dirname(plugin_dir_path(__FILE__)) . '/assets/'; 
// $globalVar = $assets_src . 'calendar.svg';
// error_log('icon:' . $globalVar);

$assets_url = plugins_url('assets/calendar.svg', dirname(__FILE__));
$globalVarIconPostType_subscriptions = $assets_url;



// Register Plans Post Type
function plans_post_type()
{
    global $globalVarIconPostType_subscriptions; 

    $labels = array(
        'name' => _x('پلن‌ها', 'Post Type General Name', 'i8_publisher_copilot'),
        'singular_name' => _x('پلن', 'Post Type Singular Name', 'i8_publisher_copilot'),
        'menu_name' => __('پلن‌ها', 'i8_publisher_copilot'),
        'name_admin_bar' => __('پلن‌ها', 'i8_publisher_copilot'),
        'archives' => __('آرشیو پلن‌ها', 'i8_publisher_copilot'),
        'attributes' => __('خصوصیات پلن‌ها', 'i8_publisher_copilot'),
        'parent_item_colon' => __('مادر', 'i8_publisher_copilot'),
        'all_items' => __('همه پلن‌ها', 'i8_publisher_copilot'),
        'add_new_item' => __('افزودن پلن', 'i8_publisher_copilot'),
        'add_new' => __('افزودن جدید', 'i8_publisher_copilot'),
        'new_item' => __('پلن‌ جدید', 'i8_publisher_copilot'),
        'edit_item' => __('ویرایش پلن', 'i8_publisher_copilot'),
        'update_item' => __('به روزرسانی پلن', 'i8_publisher_copilot'),
        'view_item' => __('نمایش پلن', 'i8_publisher_copilot'),
        'view_items' => __('نمایش پلن‌ها', 'i8_publisher_copilot'),
        'search_items' => __('جستجوی پلن', 'i8_publisher_copilot'),
        'not_found' => __('پیدا نشد', 'i8_publisher_copilot'),
        'not_found_in_trash' => __('در زباله دان پیدا نشد', 'i8_publisher_copilot'),
        'insert_into_item' => __('درج در پلن', 'i8_publisher_copilot'),
        'uploaded_to_this_item' => __('در این پلن آپلود شد', 'i8_publisher_copilot'),
        'items_list' => __('لیست پلن‌ها', 'i8_publisher_copilot'),
        'items_list_navigation' => __('پیمایش فهرست پلن‌ها', 'i8_publisher_copilot'),
        'filter_items_list' => __('لیست پلن‌ها را فیلتر کنید', 'i8_publisher_copilot'),
    );
    $args = array(
        'label' => __('plans', 'i8_publisher_copilot'),
        'description' => __('پلن ها', 'i8_publisher_copilot'),
        'labels' => $labels,
        'supports' => array('title', 'custom-fields'),
        'menu_position' => 6,
        'menu_icon' => $globalVarIconPostType_subscriptions,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'exclude_from_search' => true,
        'capability_type' => 'page',
        'public'             => false,  // این باعث می‌شود که پست‌ها در فرانت سایت نمایش داده نشوند
        'publicly_queryable' => false,  // این گزینه جلوی دسترسی عمومی به این نوع پست را می‌گیرد
        'show_ui'            => true,   // نمایش در بخش مدیریت
        'show_in_menu'       => true,   // نمایش در منوی مدیریت
        'query_var'          => false,  // جلوگیری از استفاده از query vars برای دسترسی به پست‌ها
        'rewrite'            => false,  // غیرفعال کردن rewrite rules
        'has_archive'        => false,  // غیرفعال کردن بایگانی برای این نوع پست
        'exclude_from_search' => true  // این پست‌ها در جستجوهای سایت نمایش داده نمی‌شوند

    );
    register_post_type('plans', $args);
}
add_action('init', 'plans_post_type', 0);



// Post meta
function plans_custom_meta_box()
{
    add_meta_box(
        'plans_custom_meta_box',
        'جزییات پلن',
        'display_plans_custom_meta_box',
        'plans',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'plans_custom_meta_box');

function display_plans_custom_meta_box($post)
{
    // Retrieve saved meta values
    $plan_duration = get_post_meta($post->ID, 'plan_duration', true);
    $plan_cron_interval = get_post_meta($post->ID, 'plan_cron_interval', true);
    $plan_max_post_fetch = get_post_meta($post->ID, 'plan_max_post_fetch', true);
    $plan_max_resources = get_post_meta($post->ID, 'plan_max_resources', true);
    $plan_grace_period = get_post_meta($post->ID, 'plan_grace_period', true);
    ?>
    <style>
        .cop-premium-metabox {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #fafafa;
            border-radius: 10px;
            padding: 15px;
        }
        
        .cop-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .cop-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .cop-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.1), 0 4px 6px -4px rgba(99, 102, 241, 0.1);
            border-color: #c7d2fe;
        }
        
        .cop-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .cop-card-title svg {
            width: 20px;
            height: 20px;
            color: #6366f1;
        }
        
        .cop-field-group {
            margin-bottom: 20px;
        }
        
        .cop-field-group:last-child {
            margin-bottom: 0;
        }
        
        .cop-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .cop-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .cop-input-icon {
            position: absolute;
            right: 12px;
            color: #94a3b8;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cop-input {
            width: 100%;
            padding: 10px 38px 10px 12px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            background-color: #fff !important;
            color: #0f172a !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
            height: auto !important;
            box-shadow: none !important;
            direction: ltr;
            text-align: left;
        }
        
        .cop-input:focus {
            border-color: #6366f1 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
        }
        
        .cop-help-text {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
            line-height: 1.5;
        }
    </style>

    <div class="cop-premium-metabox">
        <?php wp_nonce_field('cop_save_plans_meta', 'cop_plans_nonce'); ?>
        <div class="cop-grid-container">
            
            <!-- Card 1: Time & Validity Settings -->
            <div class="cop-card">
                <h3 class="cop-card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    دوره اعتبار و زمان‌بندی
                </h3>
                
                <div class="cop-field-group">
                    <label for="plan_duration" class="cop-label">مدت زمان اعتبار پلن (روز):</label>
                    <div class="cop-input-wrapper">
                        <span class="cop-input-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <input type="number" id="plan_duration" name="plan_duration" min="0" class="cop-input" value="<?php echo esc_attr($plan_duration); ?>">
                    </div>
                    <span class="cop-help-text">مدت زمان فعال بودن لایسنس بر حسب روز از لحظه ثبت اشتراک.</span>
                </div>

                <div class="cop-field-group">
                    <label for="plan_cron_interval" class="cop-label">فرکانس کرون جاب کلاینت (ثانیه):</label>
                    <div class="cop-input-wrapper">
                        <span class="cop-input-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </span>
                        <input type="number" id="plan_cron_interval" name="plan_cron_interval" min="0" class="cop-input" value="<?php echo esc_attr($plan_cron_interval); ?>">
                    </div>
                    <span class="cop-help-text">زمان تناوب بررسی منابع در ربات کلاینت. برای نمونه: ۳۶۰۰ برای ۱ ساعت.</span>
                </div>

                <div class="cop-field-group">
                    <label for="plan_grace_period" class="cop-label">دوره ارفاق انقضا (روز):</label>
                    <div class="cop-input-wrapper">
                        <span class="cop-input-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h3.75a2.25 2.25 0 0 1 2.25 2.25v.75m-8.25-3h8.25m-8.25 0a2.25 2.25 0 0 0-2.25 2.25v.75m8.25-3V3a2.25 2.25 0 0 0-2.25-2.25H12A2.25 2.25 0 0 0 9.75 3v.75m0 0H3.75a2.25 2.25 0 0 0-2.25 2.25v13.5a2.25 2.25 0 0 0 2.25 2.25h16.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H14.25m-4.5 0h4.5m-4.5 0v3a2.25 2.25 0 0 0 2.25 2.25h1.5a2.25 2.25 0 0 0 2.25-2.25v-3" />
                            </svg>
                        </span>
                        <input type="number" id="plan_grace_period" name="plan_grace_period" min="0" class="cop-input" value="<?php echo esc_attr($plan_grace_period); ?>" placeholder="0">
                    </div>
                    <span class="cop-help-text">مدت روزهای مجاز برای کارکرد موقت افزونه پس از اتمام تاریخ اعتبار.</span>
                </div>
            </div>
            
            <!-- Card 2: Quota & Limit Settings -->
            <div class="cop-card">
                <h3 class="cop-card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                    </svg>
                    محدودیت‌ها و سهمیه
                </h3>
                
                <div class="cop-field-group">
                    <label for="plan_max_post_fetch" class="cop-label">حداکثر سهمیه انتشار روزانه:</label>
                    <div class="cop-input-wrapper">
                        <span class="cop-input-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 17.25 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </span>
                        <input type="number" id="plan_max_post_fetch" name="plan_max_post_fetch" min="0" class="cop-input" value="<?php echo esc_attr($plan_max_post_fetch); ?>">
                    </div>
                    <span class="cop-help-text">سقف تعداد مطالب مجاز برای استخراج و بازنشر توسط ربات کلاینت در طول یک شبانه‌روز.</span>
                </div>

                <div class="cop-field-group">
                    <label for="plan_max_resources" class="cop-label">حداکثر منابع خبری مجاز:</label>
                    <div class="cop-input-wrapper">
                        <span class="cop-input-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" />
                            </svg>
                        </span>
                        <input type="number" id="plan_max_resources" name="plan_max_resources" min="0" class="cop-input" value="<?php echo esc_attr($plan_max_resources); ?>" placeholder="بدون محدودیت">
                    </div>
                    <span class="cop-help-text">سقف تعداد منابع خبری هدف که کلاینت می‌تواند برای ربات خود فعال کند (خالی = نامحدود).</span>
                </div>
            </div>
            
        </div>
    </div>
    <?php
}

function save_plans_custom_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Verify nonce
    if (!isset($_POST['cop_plans_nonce']) || !wp_verify_nonce($_POST['cop_plans_nonce'], 'cop_save_plans_meta')) {
        return;
    }

    // Check post type
    if (get_post_type($post_id) !== 'plans') {
        return;
    }

    // Save meta values with validation
    if (isset($_POST['plan_duration'])) {
        update_post_meta($post_id, 'plan_duration', absint($_POST['plan_duration']));
    }
    if (isset($_POST['plan_cron_interval'])) {
        update_post_meta($post_id, 'plan_cron_interval', absint($_POST['plan_cron_interval']));
    }
    if (isset($_POST['plan_max_post_fetch'])) {
        update_post_meta($post_id, 'plan_max_post_fetch', absint($_POST['plan_max_post_fetch']));
    }
    if (isset($_POST['plan_max_resources'])) {
        $max_res = $_POST['plan_max_resources'] === '' ? '' : absint($_POST['plan_max_resources']);
        update_post_meta($post_id, 'plan_max_resources', $max_res);
    }
    if (isset($_POST['plan_grace_period'])) {
        update_post_meta($post_id, 'plan_grace_period', absint($_POST['plan_grace_period']));
    }

    // Clear transients for all subscriptions using this plan
    $subs = new WP_Query(array(
        'post_type' => 'subscriptions',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'subscription_plan_id',
                'value' => $post_id,
                'compare' => '='
            )
        )
    ));
    if ($subs->have_posts()) {
        while ($subs->have_posts()) {
            $subs->the_post();
            $url = get_post_meta(get_the_ID(), 'subscription_site_url', true);
            $secret = get_post_meta(get_the_ID(), 'subscription_secret_code', true);
            if ($url && $secret) {
                delete_transient('cop_val_' . md5($url . '_' . $secret));
            }
        }
        wp_reset_postdata();
    }
}
add_action('save_post_plans', 'save_plans_custom_meta_box');