<?php 
/*
Plugin Name: manager - Co publisher
Description: اافزونه دستیار هوشمند (سرور)
Version: 1.0
Author: Hasht Behesht
*/


// Include Files 
require_once plugin_dir_path(__FILE__) .'post-types/'. 'resources.php';
require_once plugin_dir_path(__FILE__) .'post-types/'. 'plans.php';
require_once plugin_dir_path(__FILE__) .'post-types/'. 'subscriptions.php';

require_once plugin_dir_path(__FILE__) .'inc/'.'restapi.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'helper_functions.php';

// Include Action Scheduler if it exists
if ( file_exists( plugin_dir_path(__FILE__) . 'inc/action-scheduler/action-scheduler.php' ) ) {
    require_once plugin_dir_path(__FILE__) . 'inc/action-scheduler/action-scheduler.php';
}

// Include Monitoring System
require_once plugin_dir_path(__FILE__) .'inc/'.'db_monitoring.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'monitoring_engine.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'admin_dashboard.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'subscriptions_dashboard.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'subscription_notifications.php';
require_once plugin_dir_path(__FILE__) .'inc/'.'selector_assistant.php';

// Enqueue admin assets (Select2, external styles)
add_action('admin_enqueue_scripts', 'cop_enqueue_admin_assets');
function cop_enqueue_admin_assets($hook)
{
    global $post, $typenow;
    $post_type = '';
    if (isset($post->post_type)) {
        $post_type = $post->post_type;
    } elseif (isset($typenow)) {
        $post_type = $typenow;
    } elseif (isset($_GET['post_type'])) {
        $post_type = sanitize_text_field($_GET['post_type']);
    }

    if (in_array($post_type, array('subscriptions', 'plans', 'resource'))) {
        wp_enqueue_style('cop-admin-design-system', plugins_url('assets/css/admin-design-system.css', __FILE__));
        
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            wp_enqueue_style('select2-css', plugins_url('assets/css/select2.min.css', __FILE__));
            wp_enqueue_script('select2-js', plugins_url('assets/js/select2.min.js', __FILE__), array('jquery'), '4.0.13', true);
            wp_add_inline_script('select2-js', 'jQuery(document).ready(function($) { jQuery(".select2:not(.resource_multiple)").select2(); });');
        }
    }
}

