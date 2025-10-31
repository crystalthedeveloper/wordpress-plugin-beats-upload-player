document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.getElementById("beats-wrapper");
  if (!wrapper) return;

  const ajaxConfig = window.beats_ajax || {};
  const ajaxUrl =
    ajaxConfig.ajax_url ||
    (window.wp?.ajax?.settings?.url || `${window.location.origin}/wp-admin/admin-ajax.php`);
  const ajaxNonce = ajaxConfig.nonce || "";

  let isLoading = false;
  let hasMore = true;

  const loader = document.createElement("div");
  loader.id = "beats-loader";
  loader.textContent = "Loading more beats...";
  loader.style.cssText = "text-align:center; padding:20px; display:none;";

  const sentinel = document.createElement("div");
  sentinel.id = "scroll-sentinel";
  sentinel.style.cssText = "height:1px; width:100%;";

  wrapper.appendChild(sentinel);
  wrapper.after(loader);

  function sentinelIsClose() {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const rect = sentinel.getBoundingClientRect();
    return rect.top <= viewportHeight + 200;
  }

  async function loadMore() {
    if (isLoading || !hasMore) return;
    isLoading = true;
    loader.style.display = "block";

    const offset = parseInt(wrapper.dataset.offset || 0, 10);
    const formData = new FormData();
    formData.append("action", "load_more_beats");
    formData.append("offset", offset);
    if (ajaxNonce) {
      formData.append("nonce", ajaxNonce);
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: "POST",
        body: formData,
      });
      const res = await response.json();

      if (res.success && res.data.html) {
        wrapper.insertAdjacentHTML("beforeend", res.data.html);
        wrapper.dataset.offset = res.data.next_offset;
        hasMore = res.data.has_more;

        // Notify other scripts (like beats-player.js)
        document.dispatchEvent(
          new CustomEvent("beats-loaded", { detail: res.data })
        );
      } else {
        hasMore = false;
      }
    } catch (err) {
      console.error("⚠️ Beats load failed:", err);
    } finally {
      isLoading = false;
      loader.style.display = "none";

      // If the sentinel never left the viewport (e.g., short content),
      // immediately queue another fetch so we keep filling the page.
      if (hasMore && sentinelIsClose()) {
        requestAnimationFrame(loadMore);
      }
    }
  }

  const observer = new IntersectionObserver(
    entries => {
      if (entries[0].isIntersecting) loadMore();
    },
    { rootMargin: "400px" }
  );

  observer.observe(sentinel);
  loadMore(); // Initial load
});
