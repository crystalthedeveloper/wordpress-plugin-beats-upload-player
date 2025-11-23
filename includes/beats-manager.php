<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin Dashboard: Beats Manager (AJAX CRUD + Upload Form)
 */

// Add to admin menu
function beats_admin_menu() {
  add_menu_page(
    'Beats Manager',
    'Beats Manager',
    'manage_options',
    'beats-manager',
    'beats_admin_page',
    'dashicons-format-audio',
    25
  );
}
add_action('admin_menu', 'beats_admin_menu');

/**
 * Handle upload form submissions and return admin notice markup.
 */
function beats_admin_handle_upload_submission() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['beats_admin_upload'])) {
    return '';
  }

  if (!current_user_can('manage_options')) {
    return '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to upload beats.', 'beats-upload-player') . '</p></div>';
  }

  if (!isset($_POST['beats_admin_upload_nonce']) || !wp_verify_nonce($_POST['beats_admin_upload_nonce'], 'beats-admin-upload')) {
    return '<div class="notice notice-error"><p>' . esc_html__('Security check failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $paths     = beats_paths();
  $beat_name = sanitize_text_field($_POST['beat_name'] ?? '');
  $producer  = sanitize_text_field($_POST['beat_producer'] ?? '');
  $category  = sanitize_text_field($_POST['beat_category'] ?? '');
  $price_raw = sanitize_text_field($_POST['beat_price'] ?? '');
  if ($price_raw !== '' && !is_numeric($price_raw)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please enter a valid numeric price.', 'beats-upload-player') . '</p></div>';
  }
  $price     = $price_raw !== '' ? floatval($price_raw) : '';
  $audio     = $_FILES['beat_file'] ?? null;
  $image     = $_FILES['beat_image'] ?? null;

  if (!$audio || empty($audio['name'])) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please choose an audio file.', 'beats-upload-player') . '</p></div>';
  }
  if (!$image || empty($image['name'])) {
    return '<div class="notice notice-error"><p>' . esc_html__('Please choose a cover image.', 'beats-upload-player') . '</p></div>';
  }

  $allowed_audio = ['mp3', 'wav', 'm4a'];
  $allowed_img   = ['jpg', 'jpeg', 'png', 'webp'];

  $audio_ext = strtolower(pathinfo($audio['name'], PATHINFO_EXTENSION));
  if (!in_array($audio_ext, $allowed_audio, true)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Only MP3, WAV, or M4A files are allowed.', 'beats-upload-player') . '</p></div>';
  }

  $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
  if (!in_array($img_ext, $allowed_img, true)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Cover image must be JPG, PNG, or WEBP.', 'beats-upload-player') . '</p></div>';
  }

  $audio_filename = time() . '-' . wp_unique_filename($paths['audio_dir'], sanitize_file_name($audio['name']));
  $audio_path     = $paths['audio_dir'] . $audio_filename;

  if (!move_uploaded_file($audio['tmp_name'], $audio_path)) {
    return '<div class="notice notice-error"><p>' . esc_html__('Audio upload failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $image_filename = time() . '-' . wp_unique_filename($paths['img_dir'], sanitize_file_name($image['name']));
  $image_path     = $paths['img_dir'] . $image_filename;

  if (!move_uploaded_file($image['tmp_name'], $image_path)) {
    @unlink($audio_path);
    return '<div class="notice notice-error"><p>' . esc_html__('Image upload failed. Please try again.', 'beats-upload-player') . '</p></div>';
  }

  $meta = [
    'name'     => $beat_name ?: pathinfo($audio_filename, PATHINFO_FILENAME),
    'producer' => $producer ?: 'Unknown Producer',
    'file'     => 'audio/' . $audio_filename,
    'category' => $category ?: 'Uncategorized',
    'image'    => 'images/' . $image_filename,
    'price'     => $price !== '' ? number_format((float)$price, 2, '.', '') : '',
    'uploaded' => current_time('mysql'),
  ];

  $data   = beats_read_json();
  $data[] = $meta;
  beats_write_json($data);

  return '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Beat uploaded successfully.', 'beats-upload-player') . '</p></div>';
}

// Admin Page UI
function beats_admin_page() {
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'beats-upload-player'));
  }

  $notice = beats_admin_handle_upload_submission();

  echo '<div class="wrap beats-admin-wrap">';
  echo '<h1>🎵 Beats Manager</h1>';
  echo '<p class="description">' . esc_html__('Upload new beats, edit metadata, and manage artwork from one place.', 'beats-upload-player') . '</p>';

  if (!empty($notice)) {
    echo $notice; // already escaped markup
  }

  $categories = beats_get_categories();

  echo '<div id="beats-admin-upload" class="beats-admin-upload card">';
  echo '<h2>' . esc_html__('Upload New Beat', 'beats-upload-player') . '</h2>';
  echo '<form method="POST" enctype="multipart/form-data">';
  wp_nonce_field('beats-admin-upload', 'beats_admin_upload_nonce');
  echo '<input type="hidden" name="beats_admin_upload" value="1">';

  echo '<table class="form-table"><tbody>';

  echo '<tr><th scope="row"><label for="beat_name">' . esc_html__('Beat Name', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="text" id="beat_name" name="beat_name" class="regular-text" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_producer">' . esc_html__('Producer', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="text" id="beat_producer" name="beat_producer" class="regular-text" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_category">' . esc_html__('Category', 'beats-upload-player') . '</label></th>';
  echo '<td><select id="beat_category" name="beat_category" required>';
  foreach ($categories as $cat) {
    echo '<option value="' . esc_attr($cat) . '">' . esc_html($cat) . '</option>';
  }
  echo '</select></td></tr>';

  echo '<tr><th scope="row"><label for="beat_price">' . esc_html__('Price (CAD, optional)', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="number" id="beat_price" name="beat_price" class="regular-text" min="0" step="0.01" placeholder="19.99"></td></tr>';

  echo '<tr><th scope="row"><label for="beat_file">' . esc_html__('Beat File', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="file" id="beat_file" name="beat_file" accept=".mp3,.wav,.m4a" required></td></tr>';

  echo '<tr><th scope="row"><label for="beat_image">' . esc_html__('Cover Image', 'beats-upload-player') . '</label></th>';
  echo '<td><input type="file" id="beat_image" name="beat_image" accept=".jpg,.jpeg,.png,.webp" required></td></tr>';

  echo '</tbody></table>';
  submit_button(__('Upload Beat', 'beats-upload-player'));
  echo '</form>';
  echo '</div>'; // upload card

  echo '<div class="beats-admin-list card">';
  echo '<h2>' . esc_html__('Manage Library', 'beats-upload-player') . '</h2>';
  echo '<p class="description">' . esc_html__('Edit titles, producers, categories, or artwork. Changes save instantly.', 'beats-upload-player') . '</p>';
  echo '<div id="beats-admin-app" class="beats-admin-app"></div>';
  echo '</div>'; // list card

  echo '</div>'; // wrap
}

/**
 * Shared helper to validate AJAX permissions.
 */
function beats_admin_verify_ajax() {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => __('Permission denied.', 'beats-upload-player')], 403);
  }

  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'beats-admin')) {
    wp_send_json_error(['message' => __('Invalid security token.', 'beats-upload-player')], 403);
  }
}

function beats_admin_ajax_list() {
  beats_admin_verify_ajax();

  $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
  $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
  $per_page = max(1, min(50, $per_page));
  $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
  $term_lower = strtolower($term);

  $items = beats_read_json();

  if ($term_lower !== '') {
    $items = array_filter($items, function ($beat) use ($term_lower) {
      $haystack = strtolower(($beat['name'] ?? '') . ' ' . ($beat['producer'] ?? '') . ' ' . ($beat['category'] ?? '') . ' ' . ($beat['price'] ?? ''));
      return strpos($haystack, $term_lower) !== false;
    });
  }

  $items = array_values($items);

  usort($items, function ($a, $b) {
    $timeA = isset($a['uploaded']) ? strtotime($a['uploaded']) : 0;
    $timeB = isset($b['uploaded']) ? strtotime($b['uploaded']) : 0;
    return $timeB <=> $timeA;
  });

  $total = count($items);
  $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;
  if ($page > $total_pages) {
    $page = $total_pages;
  }
  if ($page < 1) {
    $page = 1;
  }
  $offset = ($page - 1) * $per_page;
  $paged_items = array_slice($items, $offset, $per_page);

  wp_send_json_success([
    'items' => $paged_items,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages,
  ]);
}
add_action('wp_ajax_beats_list', 'beats_admin_ajax_list');

function beats_admin_ajax_update() {
  beats_admin_verify_ajax();

  $file      = sanitize_text_field($_POST['file'] ?? '');
  $name      = sanitize_text_field($_POST['name'] ?? '');
  $producer  = sanitize_text_field($_POST['producer'] ?? '');
  $category  = sanitize_text_field($_POST['category'] ?? '');
  $price_raw = sanitize_text_field($_POST['price'] ?? '');
  if ($price_raw !== '' && !is_numeric($price_raw)) {
    wp_send_json_error(['message' => __('Please enter a valid numeric price.', 'beats-upload-player')], 400);
  }
  $price = ($price_raw !== '' && is_numeric($price_raw))
    ? number_format((float)$price_raw, 2, '.', '')
    : '';

  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $data    = beats_read_json();
  $updated = false;

  foreach ($data as &$beat) {
    if ($beat['file'] === $file) {
      if (!empty($name)) {
        $beat['name'] = $name;
      }
      if (!empty($producer)) {
        $beat['producer'] = $producer;
      }
      if (!empty($category)) {
        // Allow custom categories but trim whitespace.
        $beat['category'] = trim($category);
      }
      $beat['price'] = $price;
      $updated = true;
      break;
    }
  }
  unset($beat);

  if (!$updated) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success();
}
add_action('wp_ajax_beats_update', 'beats_admin_ajax_update');

function beats_admin_ajax_replace_image() {
  beats_admin_verify_ajax();

  $file  = sanitize_text_field($_POST['file'] ?? '');
  $image = $_FILES['image'] ?? null;

  if (!$file || !$image || empty($image['name'])) {
    wp_send_json_error(['message' => __('Invalid request.', 'beats-upload-player')], 400);
  }

  $allowed_img = ['jpg', 'jpeg', 'png', 'webp'];
  $img_ext     = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
  if (!in_array($img_ext, $allowed_img, true)) {
    wp_send_json_error(['message' => __('Unsupported image type.', 'beats-upload-player')], 400);
  }

  $paths    = beats_paths();
  $data     = beats_read_json();
  $replaced = false;
  $new_rel  = '';

  foreach ($data as &$beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    $image_filename = time() . '-' . wp_unique_filename($paths['img_dir'], sanitize_file_name($image['name']));
    $image_path     = $paths['img_dir'] . $image_filename;

    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
      wp_send_json_error(['message' => __('Failed to store image.', 'beats-upload-player')], 500);
    }

    // Remove previous image if no other beats are using it.
    if (!empty($beat['image'])) {
      $old_rel  = $beat['image'];
      $old_path = $paths['base'] . ltrim($old_rel, '/');
      $shared   = false;
      foreach ($data as $maybe) {
        if (($maybe['file'] ?? '') === $beat['file']) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $old_rel) {
          $shared = true;
          break;
        }
      }
      if (!$shared && file_exists($old_path)) {
        @unlink($old_path);
      }
    }

    $new_rel       = 'images/' . $image_filename;
    $beat['image'] = $new_rel;
    $replaced      = true;
    break;
  }
  unset($beat);

  if (!$replaced) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success(['imageUrl' => $paths['url'] . $new_rel]);
}
add_action('wp_ajax_beats_replace_image', 'beats_admin_ajax_replace_image');

function beats_admin_ajax_delete_image() {
  beats_admin_verify_ajax();

  $file = sanitize_text_field($_POST['file'] ?? '');
  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $paths = beats_paths();
  $data  = beats_read_json();
  $found = false;

  foreach ($data as &$beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    if (!empty($beat['image'])) {
      $old_rel  = $beat['image'];
      $old_path = $paths['base'] . ltrim($old_rel, '/');

      $shared = false;
      foreach ($data as $maybe) {
        if (($maybe['file'] ?? '') === $beat['file']) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $old_rel) {
          $shared = true;
          break;
        }
      }
      if (!$shared && file_exists($old_path)) {
        @unlink($old_path);
      }

      $beat['image'] = '';
    }

    $found = true;
    break;
  }
  unset($beat);

  if (!$found) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json($data);
  wp_send_json_success();
}
add_action('wp_ajax_beats_delete_image', 'beats_admin_ajax_delete_image');

function beats_admin_ajax_delete() {
  beats_admin_verify_ajax();

  $file = sanitize_text_field($_POST['file'] ?? '');
  if (!$file) {
    wp_send_json_error(['message' => __('Missing beat identifier.', 'beats-upload-player')], 400);
  }

  $paths = beats_paths();
  $data  = beats_read_json();
  $found = false;

  foreach ($data as $index => $beat) {
    if ($beat['file'] !== $file) {
      continue;
    }

    $audio_rel = $beat['file'];
    $audio_path = $paths['base'] . ltrim($audio_rel, '/');

    // Only remove audio if no other beat references it.
    $shared_audio = false;
    foreach ($data as $maybe) {
      if (($maybe['file'] ?? '') === $beat['file']) {
        continue;
      }
      if (!empty($maybe['file']) && $maybe['file'] === $audio_rel) {
        $shared_audio = true;
        break;
      }
    }
    if (!$shared_audio && file_exists($audio_path)) {
      @unlink($audio_path);
    }

    if (!empty($beat['image'])) {
      $image_rel  = $beat['image'];
      $image_path = $paths['base'] . ltrim($image_rel, '/');
      $shared_img = false;
      foreach ($data as $maybe) {
        if (($maybe['file'] ?? '') === $beat['file']) {
          continue;
        }
        if (!empty($maybe['image']) && $maybe['image'] === $image_rel) {
          $shared_img = true;
          break;
        }
      }
      if (!$shared_img && file_exists($image_path)) {
        @unlink($image_path);
      }
    }

    unset($data[$index]);
    $found = true;
    break;
  }

  if (!$found) {
    wp_send_json_error(['message' => __('Beat not found.', 'beats-upload-player')], 404);
  }

  beats_write_json(array_values($data));
  wp_send_json_success();
}
add_action('wp_ajax_beats_delete', 'beats_admin_ajax_delete');
