<?php
/**
 * Beats Seeder (Full Category List)
 * Run manually in your browser:
 * http://beats.local/wp-content/plugins/beats-upload-player/tools/seed-demo.php
 */

// ✅ Dynamically detect WordPress root (works anywhere)
$root = dirname(__FILE__, 1);
while (!file_exists($root . '/wp-load.php')) {
  $parent = dirname($root);
  if ($parent === $root) {
    exit('❌ Could not locate wp-load.php — please place this inside a WordPress installation.');
  }
  $root = $parent;
}
require_once($root . '/wp-load.php');

if (!current_user_can('manage_options')) exit('🚫 Forbidden — Admins only.');

echo "<h2>🎶 Full Beats Seeder (All Categories)</h2>";

$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'] . '/beats/';
$base_url = $upload_dir['baseurl'] . '/beats/';

// Ensure folder structure exists
$audio_dir = $base_dir . 'audio/';
$img_dir   = $base_dir . 'images/';
if (!file_exists($audio_dir)) wp_mkdir_p($audio_dir);
if (!file_exists($img_dir)) wp_mkdir_p($img_dir);

$json_file = $base_dir . 'beats.json';
$data = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
$added = 0;
$updated = 0;

// ✅ All categories ordered by popularity
$categories = [
  // 🔥 Most popular first
  'Hip hop','Trap','Rnb','Pop','Afro beat','Reggae','House','Jazz','Rock','Dance',
  'Alternative RnB','Electronic','Soul','Beats','Club','Drill','Pop Rap','Latin','Techno','Ambient',

  // 🧠 Mid-tier categories
  'Pop hip hop','Pop RnB','Indie Rock','Alternative Rock','Lo-fi','Country','Funk','Neo Soul','Chill','Downtempo',
  'Afro','Pop Electronic','Jazz fusion','Dance Hall','Synthwave','Dubstep','Pop Rock','Old School Hip Hop','Boom Bap','Underground Hip Hop',

  // 🎧 Niche/underground styles
  'Alternative Hip Hop','Gangsta Rap','Dirty South','Instrumental Hip Hop','Cloud Rap','Freestyle Rap','Trap Soul','Lo Fi Hip Hop','Grime','Reggaeton',

  // 🎹 Experimental + Global
  'Experimental Hip Hop','Rage Beats','Synth Pop','Afro Pop','World','Country Rap','Latin Trap','Jazz Rap','Trip hop','Dub',

  // 🎵 Extended categories
  'Roots','Folk','Two step','Hyperpop','Jersey Club','Alternative','Indie','Break Beat','K-pop','Metal','Country rock',
  'Christian','Gospel','Nu metal','Metalcore','Smooth rnb','Industrial','Classical','Orchestral','Downtempo','California Sound'
];

$demo_producer = 'Dmitrii from Pixabay';
$names     = ['Sunset Drive','Dream Code','Golden Line','Echo City','Street Light','Ocean Tape','Skyline','Deep Cut','Silver Flow','Neon Beat'];
$prices    = [9.99, 14.99, 19.99, 24.99, 29.99, 34.99];

// ✅ Copy demo assets if available
$plugin_dir = dirname(__FILE__, 2);
$assets_demo = $plugin_dir . '/resources/demo/';
if (file_exists($assets_demo)) {
  for ($i = 1; $i <= 5; $i++) {
    $src = $assets_demo . "demo-$i.jpg";
    $dest = $img_dir . "demo-$i.jpg";
    if (file_exists($src)) copy($src, $dest);
  }
  if (file_exists($assets_demo . "demo.mp3")) copy($assets_demo . "demo.mp3", $audio_dir . "demo.mp3");
  echo "<p>✅ Demo assets copied successfully.</p>";
} else {
  echo "<p>⚠️ No /resources/demo/ folder found — please add demo.mp3 and demo-1.jpg … demo-5.jpg.</p>";
}

// ✅ Guarantee at least one beat per category
foreach ($categories as $cat) {
  $exists = false;
  foreach ($data as $b) {
    if (strtolower($b['category']) === strtolower($cat)) {
      $exists = true;
      break;
    }
  }

  if (!$exists) {
    $name = $names[array_rand($names)];
    $data[] = [
      'name'      => $name,
      'producer'  => $demo_producer,
      'file'      => 'audio/demo.mp3',
      'category'  => $cat,
      'image'     => 'images/demo-' . rand(1, 5) . '.jpg',
      'price'     => number_format($prices[array_rand($prices)], 2, '.', ''),
      'uploaded'  => current_time('mysql')
    ];
    $added++;
  }
}

// ✅ Add extra random filler beats (to look populated)
for ($i = 0; $i < 50; $i++) {
  $cat = $categories[array_rand($categories)];
  $name = $names[array_rand($names)];
  $data[] = [
    'name'      => $name . " " . rand(1, 999),
    'producer'  => $demo_producer,
    'file'      => 'audio/demo.mp3',
    'category'  => $cat,
    'image'     => 'images/demo-' . rand(1, 5) . '.jpg',
    'price'     => number_format($prices[array_rand($prices)], 2, '.', ''),
    'uploaded'  => current_time('mysql')
  ];
  $added++;
}

// ✅ Normalize existing entries (ensure price and metadata)
foreach ($data as &$beat) {
  $dirty = false;

  if ($beat['file'] === 'audio/demo.mp3' && $beat['producer'] !== $demo_producer) {
    $beat['producer'] = $demo_producer;
    $dirty = true;
  } elseif (empty($beat['producer'])) {
    $beat['producer'] = $demo_producer;
    $dirty = true;
  }

  if (empty($beat['price']) || !is_numeric($beat['price'])) {
    $beat['price'] = number_format($prices[array_rand($prices)], 2, '.', '');
    $dirty = true;
  } else {
    $beat['price'] = number_format((float)$beat['price'], 2, '.', '');
  }

  if (empty($beat['uploaded'])) {
    $beat['uploaded'] = current_time('mysql');
    $dirty = true;
  }

  if (empty($beat['image'])) {
    $beat['image'] = 'images/demo-' . rand(1, 5) . '.jpg';
    $dirty = true;
  }

  if ($dirty) {
    $updated++;
  }
}
unset($beat);

// ✅ Save file
file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "<p>✅ Seeder complete — every category now has at least one beat, plus random filler beats.</p>";
echo '<p>🆕 Added beats: ' . $added . '</p>';
echo '<p>♻️ Updated beats: ' . $updated . '</p>';
echo "<p><a href='" . site_url() . "' target='_blank'>🌐 View your site</a></p>";
echo "<hr><p><code>$json_file</code> now contains <strong>" . count($data) . "</strong> total beats.</p>";
?>
