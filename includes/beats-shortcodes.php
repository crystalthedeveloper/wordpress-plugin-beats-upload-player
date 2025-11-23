<?php
if (!defined('ABSPATH')) exit;

/**
 * Front-end Shortcodes
 */

/* ===============================
   Upload Form
=============================== */
function beats_upload_form_shortcode() {
  wp_enqueue_style('beats-upload-style');

  ob_start();
  $paths = beats_paths();

  if (!is_user_logged_in()) {
    $redirect = home_url();
    if (function_exists('get_permalink')) {
      $permalink = get_permalink();
      if ($permalink) {
        $redirect = $permalink;
      }
    }
    $login_url = esc_url(wp_login_url($redirect));
    echo '<p class="beats-upload-login-required">' . esc_html__('Please log in to upload your beats.', 'beats-upload-player') . ' ';
    echo '<a href="' . $login_url . '">' . esc_html__('Log in', 'beats-upload-player') . '</a></p>';
    return ob_get_clean();
  }

  if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_FILES['beat_file']['name'])) {
    $beat_name = sanitize_text_field($_POST['beat_name'] ?? '');
    $producer  = sanitize_text_field($_POST['beat_producer'] ?? '');
    $category  = sanitize_text_field($_POST['beat_category'] ?? '');
    $price_raw = sanitize_text_field($_POST['beat_price'] ?? '');
    if ($price_raw !== '' && !is_numeric($price_raw)) {
      echo '<p style="color:red;">❌ Please enter a valid numeric price.</p>';
      return ob_get_clean();
    }
    $price     = $price_raw !== '' ? floatval($price_raw) : '';
    $file = $_FILES['beat_file'];
    $image= $_FILES['beat_image'] ?? null;

    if (empty($image['name'])) {
      echo '<p style="color:red;">❌ Please upload a cover image.</p>';
      return ob_get_clean();
    }

    $allowed_audio = ['mp3','wav','m4a'];
    $allowed_img   = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_audio)) {
      $audio_name = time().'-'.wp_unique_filename($paths['audio_dir'], basename($file['name']));
      $audio_path = $paths['audio_dir'].$audio_name;

      if (move_uploaded_file($file['tmp_name'], $audio_path)) {
        $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        if (!in_array($img_ext, $allowed_img)) {
          echo '<p style="color:red;">❌ Invalid image format.</p>';
          return ob_get_clean();
        }

        $img_name = time().'-'.wp_unique_filename($paths['img_dir'], basename($image['name']));
        $img_path = $paths['img_dir'].$img_name;
        move_uploaded_file($image['tmp_name'], $img_path);
        $image_rel = 'images/'.$img_name;

        $meta = [
          'name'     => $beat_name ?: pathinfo($audio_name, PATHINFO_FILENAME),
          'producer' => $producer ?: 'Unknown Producer',
          'file'     => 'audio/'.$audio_name,
          'category' => $category ?: 'Uncategorized',
          'image'    => $image_rel,
          'price'    => $price !== '' ? number_format((float)$price, 2, '.', '') : '',
          'uploaded' => current_time('mysql')
        ];

        $data = beats_read_json();
        $data[] = $meta;
        beats_write_json($data);
        echo '<p style="color:limegreen;font-weight:700;">✅ Beat uploaded successfully.</p>';
      } else {
        echo '<p style="color:red;">❌ Upload failed.</p>';
      }
    } else {
      echo '<p style="color:red;">Only MP3, WAV, and M4A allowed.</p>';
    }
  }

  echo '<form method="POST" enctype="multipart/form-data" class="beats-upload-form">';
  echo '<label>Beat Name:</label><br><input type="text" name="beat_name" required><br><br>';
  echo '<label>Producer Name:</label><br><input type="text" name="beat_producer" required><br><br>';
  echo '<label>Price (CAD, optional):</label><br><input type="number" name="beat_price" min="0" step="0.01" placeholder="19.99"><br><br>';
  echo '<label>🎵 Upload Beat File:</label><br><input type="file" name="beat_file" accept=".mp3,.wav,.m4a" required><br><br>';
  echo '<label>*Upload Cover Image:</label><br><input type="file" name="beat_image" accept=".jpg,.jpeg,.png,.webp" required><br><br>';
  echo '<label>Genre:</label><br><select name="beat_category" required>';
  foreach (beats_get_categories() as $cat) echo '<option value="'.esc_attr($cat).'">'.esc_html($cat).'</option>';
  echo '</select><br><br><button type="submit">Upload Beat</button></form>';

  return ob_get_clean();
}
add_shortcode('beats_upload_form', 'beats_upload_form_shortcode');

/* ===============================
   Infinite Scroll Display
=============================== */
function beats_display_home_shortcode() {
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_script('beats-loader');
  wp_enqueue_script('beats-player');

  ob_start(); ?>
  <div id="beats-wrapper" data-offset="0"></div>
  <?php return ob_get_clean();
}
add_shortcode('beats_display_home', 'beats_display_home_shortcode');

/* ===============================
   Global Player
=============================== */
function beats_global_player_shortcode() {
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_script('beats-player');

  ob_start(); ?>
  <div id="beats-global-player" class="beats-global-player glassy-player">
    <div class="player-left">
      <img id="beats-player-cover" src="<?php echo plugin_dir_url(__FILE__); ?>../public/images/logo-gold.webp" alt="Cover">
      <div class="player-info">
        <p id="beats-player-name">Select a beat to play</p>
        <small id="beats-player-category"></small>
        <small id="beats-player-producer"></small>
      </div>
    </div>
    <div class="player-controls">
      <audio id="beats-player-audio" controls></audio>
    </div>
  </div>
  <?php return ob_get_clean();
}
add_shortcode('beats_global_player', 'beats_global_player_shortcode');

/**
 * Playground demo shim that renders the main player with a heading.
 */
function beats_player_demo_shortcode() {
  wp_enqueue_style('beats-upload-style');
  wp_enqueue_style('beats-category-search-style');
  wp_enqueue_script('beats-loader');
  wp_enqueue_script('beats-player');

  $heading = '<h3 class="beats-player-demo__heading">' . esc_html__('Beats Upload Player Demo', 'beats-upload-player') . '</h3>';

  $player_markup = '';
  if (shortcode_exists('beats_upload_player')) {
    $player_markup = do_shortcode('[beats_upload_player]');
  }

  if ($player_markup === '') {
    $player_markup = do_shortcode('[beats_category_search]');
    $player_markup .= do_shortcode('[beats_display_home]');
    $player_markup .= do_shortcode('[beats_global_player]');
  }

  return '<div class="beats-player-demo">' . $heading . $player_markup . '</div>';
}
add_shortcode('beats_player_demo', 'beats_player_demo_shortcode');
