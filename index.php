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
require_once plugin_dir_path(__FILE__) .'inc/'.'selector_assistant.php';

