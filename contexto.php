<?php
/**
 * @package Contexto
 * @version 1.0
 */
/*
Plugin Name: Contexto
Plugin URI: https://contexto.thetwentyseven.co.uk
Description: This is a plugin to generate automatically extra information to enrich your content.
Author: Adrian Vazquez
Version: 1.0
Author URI: https://thetwentyseven.co.uk/
License: GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  contexto

Contexto is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Contexto is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Contexto. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


// Create global instances
define( 'CONTEXTO_PLUGIN', __FILE__ );
define( 'CONTEXTO_PLUGIN_DIR', untrailingslashit( dirname( CONTEXTO_PLUGIN ) ) );


add_action( 'admin_init', 'contexto_settings_init' );
function contexto_settings_init() {
   // Register a new setting for "contexto" admin page
   register_setting( 'contexto_options_menu', 'contexto_options_data' );

   // Register a group
   add_settings_section( 'contexto_options_id', __( 'Configuration', 'contexto_options_menu' ), 'contexto_setting_section_callback', 'contexto_options_menu' );

   // Add fields
   add_settings_field('contexto_options_apikey', __( 'API Key: ', 'contexto_options_menu' ), 'contexto_setting_apikey_callback', 'contexto_options_menu', 'contexto_options_id');
   add_settings_field('contexto_options_confidence', __( 'Confidence: ', 'contexto_options_menu' ), 'contexto_setting_confidence_callback', 'contexto_options_menu', 'contexto_options_id');

}


// Create top-level in admin menu
add_action('admin_menu', 'contexto_options_admin_page');
function contexto_options_admin_page(){
    add_menu_page(
        'Contexto Plugin', // $page_title
        'Contexto', // $menu_title
        'manage_options', // $capability
        'contexto_options_menu', // $menu_slug. The slug name to refer to this menu by. Should be unique for this menu page.
        'contexto_options_admin_page_content', // $function. The function to be called to output the content for this page
        plugins_url( '/public/images/contexto-icon.png', __FILE__ ), // $icon_url
        30 // $position
    );
}


// Settings API, display for admin users in the panel admin
function contexto_options_admin_page_content(){
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
    add_settings_error( 'contexto_messages', 'contexto_message', __( 'Settings Saved', 'contexto_options_menu' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'contexto_messages' );

    require_once CONTEXTO_PLUGIN_DIR . '/admin/view.php';
}


// Adding a new tinymce button with 'mce_buttons' filter and his JS plugin with 'mce_external_plugins' filter
add_action( 'admin_head', 'contexto_tinymce' );
function contexto_tinymce() {
    global $typenow;

    // Only on Post Type: post and page
    if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        return ;

    add_filter( 'mce_external_plugins', 'contexto_tinymce_plugin' );
    add_filter( 'mce_buttons', 'contexto_tinymce_button' );
}


// Include the JS for TinyMCE
function contexto_tinymce_plugin( $plugin_array ) {
    $plugin_array['contexto'] = plugins_url( '/public/js/tinymce/plugins/contexto/plugin.js',__FILE__ );
    return $plugin_array;
}

// Add the button key for address via JS
function contexto_tinymce_button( $buttons ) {
    array_push( $buttons, 'contexto_button_key' );
    return $buttons;
}


// Enqueue files for the plugin to manage data via AJAX.
// 'admin_enqueue_scripts' Just enqueue for the admin panel - More info: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
add_action( 'admin_enqueue_scripts', 'contexto_admin_enqueue' );
function contexto_admin_enqueue() {

  // Register JavaScript
  wp_register_script( 'contexto-plugin-script', null);
  wp_enqueue_script( 'contexto-plugin-script');

	// in JavaScript, object properties are accessed as contexto_ajax_object.ajax_url, contexto_ajax_object.content...
	wp_localize_script( 'contexto-plugin-script', 'contexto_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ),
                                                                    'content' => '',
                                                                    'highlight' => '',
                                                                    'images_folder' => plugins_url( '/public/images/',__FILE__ ))
                                                                  );
}


// These files would be reflected within the TinyMCE visual editor
// More info: https://developer.wordpress.org/reference/functions/add_editor_style/
add_action( 'admin_init', 'contexto_add_editor_styles' );
function contexto_add_editor_styles() {
    add_editor_style( plugins_url( '/public/css/style.css', __FILE__ )  );
}


// Enqueue the files for the frontend
// More info: https://codex.wordpress.org/Plugin_API/Action_Reference/wp_enqueue_scripts
add_action( 'wp_enqueue_scripts', 'contexto_frontend_enqueue' );
function contexto_frontend_enqueue() {

  wp_register_style( 'contexto-plugin-style', plugins_url( '/public/css/style.css', __FILE__ ) );
  wp_enqueue_style( 'contexto-plugin-style' );
}


// Include the settings page with all the files required
require_once CONTEXTO_PLUGIN_DIR . '/settings.php';

?>
