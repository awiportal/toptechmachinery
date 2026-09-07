/* Ricky Tools - UI behaviours (sticky header, slider, back-to-top). ES2024, no deps. */
(() => {
  'use strict';
  const on = (el, ev, fn, o) => el && el.addEventListener(ev, fn, o);
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  /* Sticky header */
  const header = $('.rk-header');
  if (header) {
    const mid = $('.rk-header__mid', header);
    const trigger = mid ? mid.offsetTop + mid.offsetHeight : 200;
    const onScroll = () => header.classList.toggle('is-stuck', window.scrollY > trigger);
    on(window, 'scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Hero slider: touch + keyboard + auto */
  $$('.rk-slider').forEach((slider) => {
    const slides = $$('.rk-slide', slider);
    const dotsWrap = $('.rk-slider__dots', slider);
    if (slides.length < 1) return;
    let i = 0, timer;
    const go = (n) => {
      i = (n + slides.length) % slides.length;
      slides.forEach((s, k) => s.classList.toggle('is-active', k === i));
      if (dotsWrap) $$('button', dotsWrap).forEach((d, k) => d.setAttribute('aria-current', k === i));
    };
    if (dotsWrap) slides.forEach((_, k) => {
      const b = document.createElement('button');
      b.type = 'button'; b.setAttribute('aria-label', `Slide ${k + 1}`);
      on(b, 'click', () => { go(k); reset(); });
      dotsWrap.appendChild(b);
    });
    on($('.rk-slider__arrow--next', slider), 'click', () => { go(i + 1); reset(); });
    on($('.rk-slider__arrow--prev', slider), 'click', () => { go(i - 1); reset(); });
    on(slider, 'keydown', (e) => {
      if (e.key === 'ArrowRight') { go(i + 1); reset(); }
      if (e.key === 'ArrowLeft') { go(i - 1); reset(); }
    });
    let x0 = null;
    on(slider, 'touchstart', (e) => (x0 = e.touches[0].clientX), { passive: true });
    on(slider, 'touchend', (e) => {
      if (x0 === null) return;
      const dx = e.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) { go(dx < 0 ? i + 1 : i - 1); reset(); }
      x0 = null;
    });
    const auto = () => (timer = setInterval(() => go(i + 1), 6000));
    const reset = () => { clearInterval(timer); auto(); };
    go(0); auto();
    on(slider, 'mouseenter', () => clearInterval(timer));
    on(slider, 'mouseleave', auto);
  });

  /* Mobile hamburger -> product category panel */
  const navToggle = $('.rk-nav-toggle');
  const mobile = $('.rk-mobile');
  const mobileOverlay = $('.rk-mobile__overlay');
  const closeMobile = () => {
    if (!mobile) return;
    mobile.classList.remove('is-open');
    if (mobileOverlay) mobileOverlay.classList.remove('is-open');
    if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('rk-noscroll');
  };
  if (navToggle && mobile) {
    on(navToggle, 'click', () => {
      const open = mobile.classList.toggle('is-open');
      if (mobileOverlay) mobileOverlay.classList.toggle('is-open', open);
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('rk-noscroll', open);
    });
    $$('[data-rk-mob-close]').forEach((el) => on(el, 'click', closeMobile));
    mobile.querySelectorAll('a').forEach((a) => on(a, 'click', closeMobile));
    on(document, 'keydown', (e) => { if (e.key === 'Escape') closeMobile(); });
  }

  /* Category horizontal scroller */
  $$('.rk-catscroll').forEach((wrap) => {
    const track = $('.rk-catgrid', wrap);
    if (!track) return;
    const step = () => Math.max(track.clientWidth * 0.85, 240);
    on($('.rk-catscroll__arrow--prev', wrap), 'click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    on($('.rk-catscroll__arrow--next', wrap), 'click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    const upd = () => {
      wrap.classList.toggle('at-start', track.scrollLeft <= 4);
      wrap.classList.toggle('at-end', track.scrollLeft + track.clientWidth >= track.scrollWidth - 4);
    };
    on(track, 'scroll', upd, { passive: true });
    on(window, 'resize', upd, { passive: true });
    upd();
  });

  /* Sticky add-to-cart bar on product pages */
  const stickyBar = $('.rk-sticky-atc');
  const cartForm = $('form.cart') || $('.single_add_to_cart_button');
  if (stickyBar && cartForm) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        // Show the bar once the main add-to-cart has scrolled out of view.
        const show = !e.isIntersecting && e.boundingClientRect.top < 0;
        stickyBar.classList.toggle('is-visible', show);
        document.body.classList.toggle('rk-sticky-on', show);
      });
    }, { threshold: 0 });
    io.observe(cartForm);
    const jump = $('.rk-sticky-atc__jump', stickyBar);
    if (jump) on(jump, 'click', (ev) => { ev.preventDefault(); cartForm.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
  }


  /* Shop filters slide-in drawer (mobile) */
  const filters = $('.rk-filters');
  const filtersOverlay = $('.rk-filters__overlay');
  const setFilterExpanded = (v) => $$('[data-rk-filters-open]').forEach((b) => b.setAttribute('aria-expanded', v));
  const openFilters = () => {
    if (!filters) return;
    filters.classList.add('is-open');
    if (filtersOverlay) filtersOverlay.classList.add('is-open');
    document.body.classList.add('rk-noscroll');
    setFilterExpanded('true');
  };
  const closeFilters = () => {
    if (!filters) return;
    filters.classList.remove('is-open');
    if (filtersOverlay) filtersOverlay.classList.remove('is-open');
    document.body.classList.remove('rk-noscroll');
    setFilterExpanded('false');
  };
  if (filters) {
    $$('[data-rk-filters-open]').forEach((el) => on(el, 'click', openFilters));
    $$('[data-rk-filters-close]').forEach((el) => on(el, 'click', closeFilters));
    on(document, 'keydown', (e) => { if (e.key === 'Escape') closeFilters(); });
  }

  /* Back to top */
  const top = $('.rk-backtop');
  if (top) {
    on(window, 'scroll', () => top.classList.toggle('is-visible', window.scrollY > 600), { passive: true });
    on(top, 'click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }
})();
