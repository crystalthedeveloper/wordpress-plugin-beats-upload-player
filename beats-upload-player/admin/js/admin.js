//admin.js
(function () {
  const $ = jQuery;
  const defaultArt = BeatsAdmin.defaultArt || '';
  const uploadLink = BeatsAdmin.uploadLink || '#';
  const api = (action, body) =>
    $.post(BeatsAdmin.ajax, Object.assign({ action, nonce: BeatsAdmin.nonce }, body));

  const app = $('#beats-admin-app');
  const filterBar = $('<div class="beats-admin-filter-bar"></div>');
  const searchBar = $(`
    <div class="beats-admin-search">
      <span class="dashicons dashicons-search"></span>
      <input type="search" id="beats-admin-search-field" placeholder="Search beats by name, producer, category, or price…">
    </div>
  `);
  filterBar.append(searchBar);
  const searchInput = searchBar.find('input');
  const countBadge = $('<span class="beats-admin-count"></span>');
  filterBar.append(countBadge);
  const pagination = $(`
    <div class="beats-admin-pagination">
      <button type="button" class="button button-secondary prev" disabled>‹</button>
      <span class="page-info">Page 1 of 1</span>
      <button type="button" class="button button-secondary next" disabled>›</button>
    </div>
  `);
  filterBar.append(pagination);
  const prevBtn = pagination.find('.prev');
  const nextBtn = pagination.find('.next');
  const pageInfo = pagination.find('.page-info');
  const noResults = $('<p class="beats-search-empty">No beats match your search.</p>').hide();
  app.before(filterBar);
  app.after(noResults);

  let currentPage = 1;
  const perPage = 12;
  let totalPages = 1;
  let currentTerm = '';

  prevBtn.on('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      fetchList();
    }
  });

  nextBtn.on('click', () => {
    if (currentPage < totalPages) {
      currentPage += 1;
      fetchList();
    }
  });

  let searchTimer;
  searchInput.on('input', function () {
    currentTerm = this.value.trim();
    currentPage = 1;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchList, 300);
  });

  searchInput.on('keypress', function (evt) {
    if (evt.key === 'Enter') {
      evt.preventDefault();
      clearTimeout(searchTimer);
      fetchList();
    }
  });

  // Generate each beat card
  function card(beat) {
    const imgUrl = beat.image ? BeatsAdmin.baseUrl + beat.image : cltdDefaultArt();
    const categories = Array.isArray(BeatsAdmin.categories)
      ? [...BeatsAdmin.categories]
      : [];
    if (beat.category && !categories.includes(beat.category)) {
      categories.unshift(beat.category);
    }

    return $(`
      <div class="beat-card-admin" data-file="${beat.file}">
        <div class="top">
          <img class="cover" src="${imgUrl}" alt="cover">
          <audio controls src="${BeatsAdmin.baseUrl + beat.file}"></audio>
        </div>
        <div class="fields">
          <label>Beat Name</label>
          <input class="fld-name" type="text" value="${escapeHtml(beat.name)}">

          <label>Producer</label>
          <input class="fld-producer" type="text" value="${escapeHtml(beat.producer || '')}" placeholder="Enter producer name">

          <label>Category</label>
          <select class="fld-cat">
            ${categories
              .map(c => `<option ${c === beat.category ? 'selected' : ''}>${escapeHtml(c)}</option>`)
              .join('')}
          </select>

          <label>Price (CAD)</label>
          <input class="fld-price" type="number" min="0" step="0.01" value="${escapeHtml(
            beat.price || ''
          )}" placeholder="19.99">
        </div>

        <div class="actions">
          <label class="btn">Replace Image
            <input class="file-img" type="file" accept=".jpg,.jpeg,.png,.webp" hidden>
          </label>
          ${beat.image ? `<button class="btn btn-ghost btn-del-img">Remove Image</button>` : ''}
          <button class="btn btn-primary btn-save">Save</button>
          <button class="btn btn-danger btn-del">Delete Beat</button>
        </div>
      </div>
    `);
  }

  function updatePaginationControls() {
    pageInfo.text(`Page ${totalPages ? currentPage : 0} of ${totalPages || 1}`);
    prevBtn.prop('disabled', currentPage <= 1);
    nextBtn.prop('disabled', currentPage >= totalPages);
  }

  function fetchList() {
    app.html('<div class="beats-loading">Loading…</div>');
    countBadge.text('Loading…');
    filterBar.show();

    const payload = { page: currentPage, per_page: perPage };
    if (currentTerm) payload.term = currentTerm;

    api('beats_list', payload)
      .done(res => {
        if (!res.success) {
          app.html('<p class="no-beats error">⚠️ Error loading beats.</p>');
          countBadge.text('0 beats');
          totalPages = 1;
          currentPage = 1;
          updatePaginationControls();
          return;
        }

        const items = Array.isArray(res.data.items) ? res.data.items : [];
        const total = typeof res.data.total === 'number' ? res.data.total : items.length;
        totalPages = res.data.total_pages || 1;
        currentPage = res.data.page || currentPage;
        countBadge.text(total === 1 ? '1 beat' : `${total} beats`);
        updatePaginationControls();

        if (!items.length) {
          if (currentTerm) {
            app.html('<div class="no-beats"><p>No beats match your filters.</p></div>');
            noResults.show();
          } else {
            app.html(`
          <div class="no-beats">
            <p>No beats uploaded yet.</p>
            <a class="button button-primary" href="${uploadLink}">Upload a beat</a>
          </div>
        `);
            noResults.hide();
          }
          return;
        }

        const grid = $('<div class="beats-grid"></div>');
        items.forEach(b => grid.append(card(b)));
        app.empty().append(grid);
        noResults.hide();
      })
      .fail(() => {
        app.html('<p class="no-beats error">⚠️ Unable to fetch beats. Please refresh.</p>');
        countBadge.text('0 beats');
        totalPages = 1;
        currentPage = 1;
        updatePaginationControls();
      });
  }

  // ✅ Save updates (Name + Producer + Category)
  app.on('click', '.btn-save', function () {
    const card = $(this).closest('.beat-card-admin');
    const file = card.data('file');
    const name = card.find('.fld-name').val();
    const producer = card.find('.fld-producer').val();
    const cat = card.find('.fld-cat').val();
    const price = card.find('.fld-price').val();

    card.addClass('saving');
    api('beats_update', { file, name, producer, category: cat, price })
      .done(res => {
        if (!res || !res.success) {
          const msg = (res && res.data && res.data.message) || 'Update failed. Please try again.';
          alert(msg);
        } else {
          if (price) {
            const num = Number(price);
            if (!Number.isNaN(num)) {
              card.find('.fld-price').val(num.toFixed(2));
            }
          } else {
            card.find('.fld-price').val('');
          }
          flash(card);
        }
      })
      .fail(jq => {
        const msg = (jq.responseJSON && jq.responseJSON.data && jq.responseJSON.data.message)
          || 'Network error. Please retry.';
        alert(msg);
      })
      .always(() => {
        card.removeClass('saving');
      });
  });

  // Replace Image
  app.on('change', '.file-img', function () {
    const cardEl = $(this).closest('.beat-card-admin');
    const file = cardEl.data('file');
    const fd = new FormData();
    fd.append('action', 'beats_replace_image');
    fd.append('nonce', BeatsAdmin.nonce);
    fd.append('file', file);
    fd.append('image', this.files[0]);

    fetch(BeatsAdmin.ajax, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(r => {
        if (r.success) {
          cardEl.find('img.cover').attr('src', r.data.imageUrl);
          if (!cardEl.find('.btn-del-img').length) {
            cardEl.find('.actions').prepend(
              '<button class="btn btn-ghost btn-del-img">Remove Image</button>'
            );
          }
          flash(cardEl);
        } else alert('⚠️ Image upload failed.');
      });
  });

  // Delete Image
  app.on('click', '.btn-del-img', function () {
    const cardEl = $(this).closest('.beat-card-admin');
    const file = cardEl.data('file');
    api('beats_delete_image', { file }).done(() => {
      cardEl.find('img.cover').attr('src', cltdDefaultArt());
      $(this).remove();
      flash(cardEl);
    });
  });

  // Delete Beat
  app.on('click', '.btn-del', function () {
    if (!confirm('🗑️ Delete this beat (audio + image)?')) return;
    const cardEl = $(this).closest('.beat-card-admin');
    const file = cardEl.data('file');
    cardEl.fadeTo(150, 0.3);
    api('beats_delete', { file }).done(() => cardEl.slideUp(250, () => {
      cardEl.remove();
      fetchList();
    }));
  });

  // Helper functions
  function flash(el) {
    el.addClass('saved');
    setTimeout(() => el.removeClass('saved'), 800);
  }

  function cltdDefaultArt() {
    return defaultArt;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, m =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])
    );
  }

  $(fetchList);

})();
