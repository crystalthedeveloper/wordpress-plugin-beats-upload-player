<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [beats_category_search]
 * Adds a smart search bar that scrolls to a matched category section.
 */
function beats_category_search_assets() {
  if (!wp_style_is('beats-category-search-style', 'registered')) {
    $dir = plugin_dir_url(__FILE__);
    wp_register_style(
      'beats-category-search-style',
      $dir . '../public/css/beats-category-search.css',
      [],
      defined('BEATS_UPLOAD_PLAYER_VERSION') ? BEATS_UPLOAD_PLAYER_VERSION : '1.0'
    );
  }
}
add_action('wp_enqueue_scripts', 'beats_category_search_assets');

function beats_category_search_shortcode() {
  wp_enqueue_style('beats-category-search-style');

  ob_start(); ?>
  
  <div class="beats-search-container">
    <input
      type="text"
      id="beats-search-input"
      placeholder="🔍 Search genre (e.g. Hip hop, Trap, Reggae)..."
    />
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const input = document.getElementById("beats-search-input");
      const defaultPlaceholder = input.getAttribute("placeholder") || "";

      function normalize(str) {
        return str.toLowerCase().trim().replace(/[\s_]+/g, "-"); // normalize spacing
      }

      function findMatchingSection(query) {
        const normalizedQuery = normalize(query);
        const sections = document.querySelectorAll(".beats-section");

        for (const sec of sections) {
          const id = normalize(sec.id || "");
          const title = normalize(sec.querySelector("h4")?.textContent || "");
          if (id === normalizedQuery || title === normalizedQuery || title.includes(normalizedQuery)) {
            return sec;
          }
        }
        return null;
      }

      function markNotFound(message) {
        input.classList.add("not-found");
        input.value = "";
        input.setAttribute("placeholder", message || defaultPlaceholder);
      }

      function clearNotFound() {
        if (!input.classList.contains("not-found")) return;
        input.classList.remove("not-found");
        input.setAttribute("placeholder", defaultPlaceholder);
      }

      input.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
          const query = input.value;
          if (!query) return;

          const target = findMatchingSection(query);
          if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
            target.classList.add("highlight");
            setTimeout(() => target.classList.remove("highlight"), 1500);
            clearNotFound();
          } else {
            markNotFound("No matching category found");
          }
        }
      });

      // Optional live search scroll
      input.addEventListener("input", function () {
        clearNotFound();
        const query = this.value;
        if (!query) return;
        const target = findMatchingSection(query);
        if (target) {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
          clearNotFound();
        }
      });

      input.addEventListener("focus", () => {
        clearNotFound();
      });
    });
  </script>

  <?php return ob_get_clean();
}
add_shortcode('beats_category_search', 'beats_category_search_shortcode');
