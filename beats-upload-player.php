<?php
/**
 * Plugin Name: Beats Upload & Player
 * Description: Modular beat store toolkit with AJAX infinite scroll, global player, category search bar, and a Beats Manager for producers.
 * Version: 1.0.0
 * Author: Crystal The Developer
 */

if (!defined('ABSPATH')) exit;

if (!defined('BEATS_UPLOAD_PLAYER_VERSION')) {
  define('BEATS_UPLOAD_PLAYER_VERSION', '1.5.0');
}

// === Includes ===
require_once plugin_dir_path(__FILE__) . 'includes/beats-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/beats-category-search.php';


// === Public Assets ===
function beats_register_public_assets() {
  $dir = plugin_dir_url(__FILE__);
  $version = BEATS_UPLOAD_PLAYER_VERSION;

  wp_register_style('beats-upload-style', $dir . 'public/css/beats-upload.css', [], $version);
  wp_register_style('beats-category-search-style', $dir . 'public/css/beats-category-search.css', [], $version);

  wp_register_script('beats-loader', $dir . 'public/js/beats-loader.js', [], $version, true);
  wp_register_script('beats-player', $dir . 'public/js/beats-player.js', [], $version, true);

  wp_localize_script('beats-loader', 'beats_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('beats-load'),
  ]);
}
add_action('wp_enqueue_scripts', 'beats_register_public_assets');

// === Admin Assets ===
function beats_enqueue_admin_assets($hook) {
  if ($hook !== 'toplevel_page_beats-manager') {
    return;
  }

  $dir = plugin_dir_url(__FILE__);
  $paths = beats_paths();

  wp_enqueue_style('beats-admin-style', $dir . 'admin/css/admin.css', [], BEATS_UPLOAD_PLAYER_VERSION);
  wp_enqueue_script('beats-admin-script', $dir . 'admin/js/admin.js', ['jquery'], BEATS_UPLOAD_PLAYER_VERSION, true);

  wp_localize_script('beats-admin-script', 'BeatsAdmin', [
    'ajax'       => admin_url('admin-ajax.php'),
    'nonce'      => wp_create_nonce('beats-admin'),
    'baseUrl'    => $paths['url'],
    'categories' => beats_get_categories(),
    'defaultArt' => $dir . 'public/images/default-art.webp',
    'uploadLink' => admin_url('admin.php?page=beats-manager#beats-admin-upload'),
  ]);
}
add_action('admin_enqueue_scripts', 'beats_enqueue_admin_assets');
