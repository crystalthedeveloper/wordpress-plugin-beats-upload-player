<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX: Load Beats Dynamically (Infinite Scroll)
 */
add_action('wp_ajax_load_more_beats', 'beats_ajax_load_more');
add_action('wp_ajax_nopriv_load_more_beats', 'beats_ajax_load_more');

function beats_ajax_load_more() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'beats-load')) {
    wp_send_json_error(['message' => 'Invalid request.'], 403);
  }

  $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
  $limit  = 4; // categories per load

  $data = beats_read_json();
  if (empty($data)) {
    wp_send_json_error(['message' => 'No beats found.']);
    return;
  }

  $grouped = [];
  foreach ($data as $beat) {
    $grouped[$beat['category']][] = $beat;
  }

  $categories = array_keys($grouped);
  $batch = array_slice($categories, $offset, $limit);
  $paths = beats_paths();

  ob_start();
  foreach ($batch as $cat) {
    echo '<div class="beats-section" id="' . sanitize_title($cat) . '">';
    echo '<h4>' . esc_html($cat) . '</h4><div class="beats-row">';
    
    foreach ($grouped[$cat] as $b) {
      $url = esc_url($paths['url'] . $b['file']);
      $img = !empty($b['image'])
        ? esc_url($paths['url'] . $b['image'])
        : plugin_dir_url(__FILE__) . '../public/images/default-art.webp';
      $producer = esc_html($b['producer'] ?? 'Unknown Producer');
      $price_raw = isset($b['price']) ? trim((string)$b['price']) : '';
      $price_value = $price_raw !== '' ? number_format((float)$price_raw, 2, '.', '') : '';
      $price_display = $price_value !== '' ? 'CAD $' . $price_value : '';

      echo '<div class="beat-card"
              data-src="' . $url . '"
              data-name="' . esc_attr($b['name']) . '"
              data-producer="' . esc_attr($producer) . '"
              data-cat="' . esc_attr($b['category']) . '"
              data-price="' . esc_attr($price_display !== '' ? $price_display : 'Free') . '"
              data-img="' . $img . '">';
      echo '<div class="beat-thumb">';
      echo '<img src="' . $img . '" alt="Beat Cover" loading="lazy">';
      echo '<div class="beat-title-ribbon">' . esc_html($b['name']) . '</div>';
      echo '<div class="beat-overlay">';
      echo '<div class="beat-overlay-actions">';
      echo '<button type="button" class="beat-info-btn" aria-label="Show beat info">&#9432;</button>';
      echo '<button type="button" class="beat-cart-btn" aria-label="Show price">&#128722;</button>';
      echo '<button type="button" class="beat-play-btn" aria-label="Play beat">▶</button>';
      echo '</div>';
      echo '<div class="beat-overlay-panel">';
      echo '<div class="beat-panel beat-panel-info"><small class="beat-producer">By ' . $producer . '</small></div>';
      if ($price_display) {
        echo '<div class="beat-panel beat-panel-cart">';
        echo '<span class="beat-price">' . esc_html($price_display) . '</span>';
        echo '<a class="beat-store-btn" href="https://www.crystalthedeveloper.ca/store" target="_blank" rel="noopener noreferrer">Buy Now</a>';
        echo '</div>';
      } else {
        echo '<div class="beat-panel beat-panel-cart beat-panel-cart--empty">';
        echo '<span class="beat-price">Free</span>';
        echo '</div>';
      }
      echo '</div>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
    }

    echo '</div></div>';
  }

  $html = ob_get_clean();

  wp_send_json_success([
    'html'        => $html,
    'next_offset' => $offset + $limit,
    'has_more'    => ($offset + $limit) < count($categories),
  ]);
}
