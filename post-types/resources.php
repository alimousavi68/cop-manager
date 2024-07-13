<?php
// Register Custom Post Type
function resouces_post_type()
{

    $labels = array(
        'name' => _x('منابع', 'Post Type General Name', 'i8_publisher_copilot'),
        'singular_name' => _x('منبع', 'Post Type Singular Name', 'i8_publisher_copilot'),
        'menu_name' => __('منابع خبری', 'i8_publisher_copilot'),
        'name_admin_bar' => __('منابع خبری', 'i8_publisher_copilot'),
        'archives' => __('آرشیو منابع', 'i8_publisher_copilot'),
        'attributes' => __('خصوصیات منبع', 'i8_publisher_copilot'),
        'parent_item_colon' => __('مادر', 'i8_publisher_copilot'),
        'all_items' => __('همه منابع', 'i8_publisher_copilot'),
        'add_new_item' => __('افزودن منبع', 'i8_publisher_copilot'),
        'add_new' => __('افزودن جدید', 'i8_publisher_copilot'),
        'new_item' => __('منبع جدید', 'i8_publisher_copilot'),
        'edit_item' => __('ویرایش منبع', 'i8_publisher_copilot'),
        'update_item' => __('به روزرسانی منبع', 'i8_publisher_copilot'),
        'view_item' => __('نمایش منبع', 'i8_publisher_copilot'),
        'view_items' => __('نمایش منابع', 'i8_publisher_copilot'),
        'search_items' => __('جستجوی منبع', 'i8_publisher_copilot'),
        'not_found' => __('پیدا نشد', 'i8_publisher_copilot'),
        'not_found_in_trash' => __('در زباله دان پیدا نشد', 'i8_publisher_copilot'),
        'featured_image' => __('تصویر لوگو', 'i8_publisher_copilot'),
        'set_featured_image' => __('تنظیم تصویر لوگو', 'i8_publisher_copilot'),
        'remove_featured_image' => __('حذف تصویر لوگو', 'i8_publisher_copilot'),
        'use_featured_image' => __('استفاده از تصویر لوگو', 'i8_publisher_copilot'),
        'insert_into_item' => __('درج در منبع', 'i8_publisher_copilot'),
        'uploaded_to_this_item' => __('در این منبع آپلود شد', 'i8_publisher_copilot'),
        'items_list' => __('لیست منابع', 'i8_publisher_copilot'),
        'items_list_navigation' => __('پیمایش فهرست منابع', 'i8_publisher_copilot'),
        'filter_items_list' => __('لیست منابع را فیلتر کنید', 'i8_publisher_copilot'),
    );
    $args = array(
        'label' => __('resource', 'i8_publisher_copilot'),
        'description' => __('منابع خبری', 'i8_publisher_copilot'),
        'labels' => $labels,
        'supports' => array('title', 'thumbnail', 'custom-fields'),
        'taxonomies' => array('category', 'post_tag'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-admin-site',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => true,
        'publicly_queryable' => true,
        'capability_type' => 'page',
    );
    register_post_type('resource', $args);

}
add_action('init', 'resouces_post_type', 0);


// Post meta
function custom_meta_box()
{
    add_meta_box(
        'custom_meta_box',
        'Custom Fields',
        'display_custom_meta_box',
        'resource',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'custom_meta_box');

function display_custom_meta_box($post)
{
    // Retrieve saved meta values
    $title_selector = get_post_meta($post->ID, 'title_selector', true);
    $img_selector = get_post_meta($post->ID, 'img_selector', true);
    $lead_selector = get_post_meta($post->ID, 'lead_selector', true);
    $body_selector = get_post_meta($post->ID, 'body_selector', true);
    $bup_date_selector = get_post_meta($post->ID, 'bup_date_selector', true);
    $category_selector = get_post_meta($post->ID, 'category_selector', true);
    $tags_selector = get_post_meta($post->ID, 'tags_selector', true);
    $escape_elements = get_post_meta($post->ID, 'escape_elements', true);
    $source_root_link = get_post_meta($post->ID, 'source_root_link', true);
    $source_feed_link = get_post_meta($post->ID, 'source_feed_link', true);

    ?>
    <p style="text-align:left;direction:ltr;">
        <label for="title_selector">Title Selector:</label><br>
        <input type="text" id="title_selector" name="title_selector" class="widefat"
            value="<?php echo esc_attr($title_selector); ?>"><br><br>

        <label for="img_selector">Image Selector:</label><br>
        <input type="text" id="img_selector" name="img_selector" class="widefat"
            value="<?php echo esc_attr($img_selector); ?>"><br><br>

        <label for="lead_selector">Lead Selector:</label><br>
        <input type="text" id="lead_selector" name="lead_selector" class="widefat"
            value="<?php echo esc_attr($lead_selector); ?>"><br><br>

        <label for="body_selector">Body Selector:</label><br>
        <input type="text" id="body_selector" name="body_selector" class="widefat"
            value="<?php echo esc_attr($body_selector); ?>"><br><br>

        <label for="bup_date_selector">Bup Date Selector:</label><br>
        <input type="text" id="bup_date_selector" name="bup_date_selector" class="widefat"
            value="<?php echo esc_attr($bup_date_selector); ?>"><br><br>

        <label for="category_selector">Category Selector:</label><br>
        <input type="text" id="category_selector" name="category_selector" class="widefat"
            value="<?php echo esc_attr($category_selector); ?>"><br><br>

        <label for="tags_selector">Tags Selector:</label><br>
        <input type="text" id="tags_selector" name="tags_selector" class="widefat"
            value="<?php echo esc_attr($tags_selector); ?>"><br><br>

        <label for="escape_elements">Escape Elements:</label><br>
        <textarea id="escape_elements" name="escape_elements"
            class="widefat"><?php echo esc_textarea($escape_elements); ?></textarea><br><br>

        <label for="source_root_link">Source root Link: </label><br>
        <input type="text" id="source_root_link" name="source_root_link" class="widefat"
            value="<?php echo esc_attr($source_root_link); ?>"><br><br>


        <label for="source_feed_link">Source Feed Link:</label><br>
        <input type="text" id="source_feed_link" name="source_feed_link" class="widefat"
            value="<?php echo esc_attr($source_feed_link); ?>"><br><br>

<?php
error_log('start need value:'.get_post_meta($post->ID, 'need_to_merge_guid_link', true));
?>
        <label for="need_to_merge_guid_link">need to merge guid link:
            <input type="checkbox" name="need_to_merge_guid_link" id="need_to_merge_guid_link"
             value="<?php echo(get_post_meta($post->ID, 'need_to_merge_guid_link', true) == 1) ? 1 : 0 ; ?>"
               <?php checked(get_post_meta($post->ID, 'need_to_merge_guid_link', 1 , true)); ?> >
        </label><br>
    </p>
    <?php
}

function save_custom_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Save meta values
    if (isset($_POST['title_selector'])) {
        update_post_meta($post_id, 'title_selector', sanitize_text_field($_POST['title_selector']));
    }
    if (isset($_POST['img_selector'])) {
        update_post_meta($post_id, 'img_selector', sanitize_text_field($_POST['img_selector']));
    }
    if (isset($_POST['lead_selector'])) {
        update_post_meta($post_id, 'lead_selector', sanitize_text_field($_POST['lead_selector']));
    }
    if (isset($_POST['body_selector'])) {
        update_post_meta($post_id, 'body_selector', sanitize_text_field($_POST['body_selector']));
    }
    if (isset($_POST['bup_date_selector'])) {
        update_post_meta($post_id, 'bup_date_selector', sanitize_text_field($_POST['bup_date_selector']));
    }
    if (isset($_POST['category_selector'])) {
        update_post_meta($post_id, 'category_selector', sanitize_text_field($_POST['category_selector']));
    }
    if (isset($_POST['tags_selector'])) {
        update_post_meta($post_id, 'tags_selector', sanitize_text_field($_POST['tags_selector']));
    }
    if (isset($_POST['escape_elements'])) {
        update_post_meta($post_id, 'escape_elements', sanitize_textarea_field($_POST['escape_elements']));
    }
    if (isset($_POST['source_root_link'])) {
        update_post_meta($post_id, 'source_root_link', sanitize_text_field($_POST['source_root_link']));
    }
    if (isset($_POST['source_feed_link'])) {
        update_post_meta($post_id, 'source_feed_link', sanitize_text_field($_POST['source_feed_link']));
    }
    
    
    // save need_to_merge_guid_link
    if (isset($_POST['need_to_merge_guid_link'])) { 
        error_log('if, need to merge: ' . $_POST['need_to_merge_guid_link']);
        update_post_meta($post_id, 'need_to_merge_guid_link', 1);
    }
    else {
        update_post_meta($post_id, 'need_to_merge_guid_link', 0);
    }

}
add_action('save_post', 'save_custom_meta_box');