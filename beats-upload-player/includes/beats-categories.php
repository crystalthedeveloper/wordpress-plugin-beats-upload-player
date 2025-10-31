<?php
if (!defined('ABSPATH')) exit;

/**
 * Beats Categories — Ordered by Popularity
 */
function beats_get_categories() {
  return [
    // 🔥 Mainstream / Most Popular
    'Hip hop','Trap','Rnb','Pop','Afro beat','Reggae','House','Rock','Jazz','Dance',
    // 💎 Trending Fusions & Core Influences
    'Alternative RnB','Electronic','Soul','Pop Rap','Beats','Club','Drill','Latin','Pop RnB','Pop hip hop',
    // 🎧 Mid-tier / Consistently Active
    'Lo-fi','Afro Pop','Indie Rock','Alternative Rock','Dance Hall','Chill','Ambient','Techno','Neo Soul','Pop Rock',
    // 🎹 Classic, Funky & Niche Grooves
    'Old School Hip Hop','Boom Bap','Funk','Downtempo','Synthwave','Dubstep','Jazz fusion','Country','Beats','Trap Soul',
    // 🎶 Experimental & Global Blends
    'Alternative Hip Hop','Gangsta Rap','Dirty South','Instrumental Hip Hop','Cloud Rap','Freestyle Rap','Lo Fi Hip Hop','Experimental Hip Hop','Rage Beats','World',
    // 🌍 International & Crossover Styles
    'Reggaeton','Afro','Grime','Latin Trap','Trip hop','Dub','Roots','Two step','Jersey Club','Hyperpop',
    // 🎵 Indie / Folk / Niche
    'Folk','Indie','Break Beat','K-pop','Country rock','Christian','Gospel','Country Rap','Pop 80s','Jazz Rap',
    // ⚙️ Alternative Subgenres & Heavy Styles
    'Metal','Nu metal','Metalcore','Smooth rnb','Industrial','Alternative','Orchestral','Classical','California Sound','Edm'
  ];
}
