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
    ?>
    <style>
        .form-flex-container {
            display: flex;
            flex-wrap: wrap;
            gap:10px;
        }

        .half-width {
            flex: 0 0 50%;
        }

        .full-width {
            flex: 0 0 100%;
        }

        .third-width {
            flex: 0 0 33.33%;
        }
    </style>

    <div class="form-container form-flex-container">

        <div class="full-width">
            <label for="title_selector" class="form-label">مدت به روز: </label><br>
            <input type="number" id="plan_duration" name="plan_duration" class="form-field form-input "
                value="<?php echo esc_attr($plan_duration); ?>">
        </div>

        <div class="full-width">
            <label for="plan_cron_interval" class="form-label"> آپدیت تایم کرون: (به ثانیه) </label><br>
            <input type="number" id="plan_cron_interval" name="plan_cron_interval" class="form-field form-input"
                value="<?php echo esc_attr($plan_cron_interval); ?>">
        </div>

        <div class="full-width">
            <label for="title_selector" class="form-label"> ماکزیمم انتشار روزانه : </label><br>
            <input type="number" id="plan_max_post_fetch" name="plan_max_post_fetch" class="form-field form-input"
                value="<?php echo esc_attr($plan_max_post_fetch); ?>">
        </div>
    </div>
    <?php
}

function save_plans_custom_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Save meta values
    if (isset($_POST['plan_duration'])) {
        update_post_meta($post_id, 'plan_duration', sanitize_text_field($_POST['plan_duration']));
    }
    if (isset($_POST['plan_cron_interval'])) {
        update_post_meta($post_id, 'plan_cron_interval', sanitize_text_field($_POST['plan_cron_interval']));
    }
    if (isset($_POST['plan_max_post_fetch'])) {
        update_post_meta($post_id, 'plan_max_post_fetch', sanitize_text_field($_POST['plan_max_post_fetch']));
    }

}
add_action('save_post', 'save_plans_custom_meta_box');