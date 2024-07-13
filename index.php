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
