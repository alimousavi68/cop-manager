<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Hook to create the custom table for monitoring errors
add_action('admin_init', 'cop_create_monitoring_logs_table');

function cop_create_monitoring_logs_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'cop_monitoring_logs';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            resource_id bigint(20) NOT NULL,
            last_checked datetime NOT NULL,
            status varchar(50) NOT NULL,
            error_details text NOT NULL,
            fail_count int(11) DEFAULT 0 NOT NULL,
            success_count int(11) DEFAULT 0 NOT NULL,
            PRIMARY KEY (resource_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
