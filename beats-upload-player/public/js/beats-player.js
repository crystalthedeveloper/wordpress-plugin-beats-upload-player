document.addEventListener("DOMContentLoaded", () => {
  const player = document.getElementById("beats-player-audio");
  const cover = document.getElementById("beats-player-cover");
  const name = document.getElementById("beats-player-name");
  const cat = document.getElementById("beats-player-category");
  const prod = document.getElementById("beats-player-producer");

  if (!player) return;

  let currentSrc = "";

  const overlayStates = ["show-info", "show-cart"];

  function clearAllOverlays() {
    document.querySelectorAll(".beat-overlay").forEach(overlay => {
      overlay.classList.remove(...overlayStates);
    });
  }

  function setOverlayState(overlay, state) {
    overlay.classList.remove(...overlayStates);
    if (state) {
      overlay.classList.add(state);
    }
  }

  function toggleOverlayState(overlay, state) {
    const isActive = overlay.classList.contains(state);
    clearAllOverlays();
    if (!isActive) {
      overlay.classList.add(state);
    }
  }

  function attachHandlers() {
    document.querySelectorAll(".beat-card").forEach(card => {
      if (card.dataset.playerBound === "1") return;
      const playBtn = card.querySelector(".beat-play-btn");
      const imgEl = card.querySelector(".beat-thumb img");
      const overlay = card.querySelector(".beat-overlay");
      const infoBtn = card.querySelector(".beat-info-btn");
      const cartBtn = card.querySelector(".beat-cart-btn");
      if (!playBtn || !imgEl) return;
      if (overlay) setOverlayState(overlay, null);

      const src = card.dataset.src;
      const img = card.dataset.img;
      const title = card.dataset.name;
      const category = card.dataset.cat;
      const producer = card.dataset.producer || "Unknown Producer";
      const price = card.dataset.price || "";

      const togglePlay = () => {
        // same track toggle
        if (player.src.includes(src)) {
          if (!player.paused) {
            player.pause();
            playBtn.textContent = "▶";
          } else {
            player.play().catch(() => {});
            playBtn.textContent = "⏸";
          }
          return;
        }

        // play new track
        resetAllButtons();
        if (overlay) {
          setOverlayState(overlay, null);
        }
        player.src = src;
        player.play().catch(() => {});
        playBtn.textContent = "⏸";

        currentSrc = src;
        cover.src = img;
        name.textContent = title;
        cat.textContent = price ? `${category} • ${price}` : category;
        if (prod) prod.textContent = producer;
      };

      playBtn.addEventListener("click", togglePlay);
      imgEl.addEventListener("click", togglePlay);

      if (infoBtn && overlay) {
        const showInfo = () => { clearAllOverlays(); setOverlayState(overlay, "show-info"); };
        const toggleInfo = () => toggleOverlayState(overlay, "show-info");
        infoBtn.addEventListener("mouseenter", showInfo);
        infoBtn.addEventListener("focus", showInfo);
        infoBtn.addEventListener("click", event => {
          event.stopPropagation();
          toggleInfo();
        });
      }

      if (cartBtn && overlay) {
        const showCart = () => { clearAllOverlays(); setOverlayState(overlay, "show-cart"); };
        const toggleCart = () => toggleOverlayState(overlay, "show-cart");
        cartBtn.addEventListener("mouseenter", showCart);
        cartBtn.addEventListener("focus", showCart);
        cartBtn.addEventListener("click", event => {
          event.stopPropagation();
          toggleCart();
        });
      }

      if (overlay) {
        overlay.addEventListener("mouseleave", () => setOverlayState(overlay, null));
      }
      card.addEventListener("mouseleave", () => {
        if (overlay) setOverlayState(overlay, null);
      });

      card.dataset.playerBound = "1";
    });
  }

  function resetAllButtons() {
    document.querySelectorAll(".beat-play-btn").forEach(btn => (btn.textContent = "▶"));
    clearAllOverlays();
  }

  // reset icons when player ends
  player.addEventListener("ended", resetAllButtons);

  // attach on load
  attachHandlers();

  // listen for custom events dispatched after AJAX loads
  document.addEventListener("beats-loaded", () => {
    clearAllOverlays();
    attachHandlers();
  });

  // reattach dynamically when AJAX loads new beats
  const observer = new MutationObserver(() => attachHandlers());
  const wrapper = document.getElementById("beats-wrapper");
  if (wrapper) observer.observe(wrapper, { childList: true, subtree: true });
});
