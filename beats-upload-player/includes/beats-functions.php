<?php
if (!defined('ABSPATH')) exit;

/**
 * Paths, File Handling, JSON Helpers
 */
function beats_paths() {
  $u = wp_upload_dir();
  $base = trailingslashit($u['basedir']).'beats/';
  $url  = trailingslashit($u['baseurl']).'beats/';
  $audio = $base.'audio/';
  $img   = $base.'images/';
  if (!file_exists($audio)) wp_mkdir_p($audio);
  if (!file_exists($img))   wp_mkdir_p($img);
  return [
    'base' => $base,
    'url' => $url,
    'audio_dir' => $audio,
    'img_dir' => $img,
    'json' => $base.'beats.json'
  ];
}

function beats_read_json() {
  $p = beats_paths();
  $f = $p['json'];
  if (!file_exists($f)) return [];
  $data = json_decode(@file_get_contents($f), true);
  return is_array($data) ? $data : [];
}

function beats_write_json($data) {
  $p = beats_paths();
  file_put_contents($p['json'], json_encode(array_values($data), JSON_PRETTY_PRINT));
}
