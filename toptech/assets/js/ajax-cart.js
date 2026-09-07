/* TopTech Machinery - AJAX add-to-cart with slide-in cart drawer, mini-cart fragments, live search. */
(() => {
  'use strict';
  if (typeof ToptechAjax === 'undefined') return;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  const drawer = $('.rk-drawer');
  const openDrawer = () => {
    if (!drawer) return;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('rk-noscroll');
  };
  const closeDrawer = () => {
    if (!drawer) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('rk-noscroll');
  };

  const applyFragments = (data) => {
    if (!data || !data.fragments) return false;
    Object.entries(data.fragments).forEach(([sel, html]) => {
      $$(sel).forEach((el) => { el.outerHTML = html; });
    });
    document.body.dispatchEvent(new CustomEvent('wc_fragments_refreshed'));
    return true;
  };

  const addToCart = async (id, qty, btn) => {
    if (!id) return;
    let label;
    if (btn) { label = btn.innerHTML; btn.disabled = true; btn.classList.add('is-loading'); }
    if (drawer) { drawer.classList.add('is-loading'); openDrawer(); }
    try {
      const body = new URLSearchParams({ action: 'toptech_add_to_cart', nonce: ToptechAjax.nonce, product_id: id, quantity: qty || 1 });
      const res = await fetch(ToptechAjax.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
      const data = await res.json();
      if (applyFragments(data)) {
        openDrawer();
      } else {
        window.location.href = ToptechAjax.cartUrl;
      }
    } catch (err) {
      window.location.href = ToptechAjax.cartUrl;
    } finally {
      if (btn) { btn.disabled = false; btn.classList.remove('is-loading'); if (label !== undefined) btn.innerHTML = label; }
      if (drawer) drawer.classList.remove('is-loading');
    }
  };

  /* Delegated clicks: card add buttons + drawer open/close */
  document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-toptech-add]');
    if (add) { e.preventDefault(); addToCart(add.getAttribute('data-toptech-add'), add.getAttribute('data-qty') || 1, add); return; }
    const wcAdd = e.target.closest('a.add_to_cart_button[data-product_id], .ajax_add_to_cart[data-product_id]');
    if (wcAdd) {
      const complex = wcAdd.classList.contains('product_type_variable') || wcAdd.classList.contains('product_type_grouped');
      if (complex === false) { e.preventDefault(); addToCart(wcAdd.getAttribute('data-product_id'), wcAdd.getAttribute('data-quantity') || 1, wcAdd); return; }
    }
    if (e.target.closest('[data-rk-drawer-close]')) { e.preventDefault(); closeDrawer(); return; }
    const opener = e.target.closest('[data-rk-drawer-open]');
    if (opener && drawer) { e.preventDefault(); openDrawer(); return; }
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });

  /* Single product page: AJAX-ify the add-to-cart for simple, in-stock products */
  const form = $('.single-product form.cart');
  if (form && !form.classList.contains('variations_form') && !form.classList.contains('grouped_form')) {
    form.addEventListener('submit', (e) => {
      const btn = form.querySelector('.single_add_to_cart_button');
      const idField = form.querySelector('[name="add-to-cart"]');
      const id = (idField && idField.value) || (btn && btn.value);
      if (!id) return; /* let WooCommerce handle it */
      const qtyEl = form.querySelector('input.qty, [name="quantity"]');
      const qty = qtyEl ? qtyEl.value : 1;
      e.preventDefault();
      addToCart(id, qty, btn);
    });
  }

  /* Live AJAX search with debounce */
  const search = $('.rk-search');
  if (search) {
    const input = $('input[type="search"], input[name="s"]', search);
    const panel = $('.rk-search__panel', search) || Object.assign(document.createElement('div'), { className: 'rk-search__panel' });
    if (!panel.parentNode) search.appendChild(panel);
    let ctrl, timer;
    const cache = new Map();
    const render = (d) => {
      let h = '';
      if (d.terms && d.terms.length) {
        h += `<div class="rk-search__section">Categories & Brands</div>`;
        h += d.terms.map((t) => `<a class="rk-search__row" href="${t.url}"><div class="rk-search__meta">${t.label} <small>&middot; ${t.type}</small></div></a>`).join('');
      }
      if (d.products && d.products.length) {
        h += `<div class="rk-search__section">Products</div>`;
        h += d.products.map((p) => `<a class="rk-search__row" href="${p.url}"><img src="${p.image}" alt="" loading="lazy"><div class="rk-search__meta">${p.title}<br><small>${p.sku ? 'SKU ' + p.sku + ' &middot; ' : ''}</small>${p.price}</div></a>`).join('');
      }
      panel.innerHTML = h || `<div class="rk-search__row">No matches</div>`;
      panel.classList.add('is-open');
    };
    const run = async (q) => {
      if (cache.has(q)) { render(cache.get(q)); return; }
      for (let n = q.length - 1; n >= 2; n--) {
        const pre = q.slice(0, n);
        if (cache.has(pre)) { render(cache.get(pre)); break; }
      }
      if (ctrl) ctrl.abort();
      ctrl = new AbortController();
      try {
        const url = `${ToptechAjax.ajaxUrl}?action=toptech_search&nonce=${ToptechAjax.nonce}&q=${encodeURIComponent(q)}`;
        const res = await fetch(url, { signal: ctrl.signal, credentials: 'same-origin' });
        const json = await res.json();
        if (json && json.success) { cache.set(q, json.data); render(json.data); }
      } catch (_) {}
    };
    input && input.addEventListener('input', () => {
      const q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { panel.classList.remove('is-open'); return; }
      timer = setTimeout(() => run(q), 70);
    });
    document.addEventListener('click', (e) => { if (!search.contains(e.target)) panel.classList.remove('is-open'); });
  }
})();
