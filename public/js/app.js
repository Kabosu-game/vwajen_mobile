/* Vwajèn — interactions de l'interface (vanilla JS, sans dépendances) */
(function () {
  'use strict';
  const V = window.Vwajen || {};
  const t = (k) => (V.i18n && V.i18n[k]) || k;
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  // ---------------------------------------------------------------- HTTP
  async function api(url, opts = {}) {
    const headers = { 'X-CSRF-TOKEN': V.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(opts.headers || {}) };
    let body = opts.body;
    if (body && !(body instanceof FormData) && typeof body === 'object' && !(body instanceof Blob)) {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    const method = (opts.method || 'POST').toUpperCase();
    if (method !== 'GET' && method !== 'POST') headers['X-HTTP-Method-Override'] = method; // PUT / DELETE via POST
    const res = await fetch(url, { method: method === 'GET' ? 'GET' : 'POST', headers, body: method === 'GET' ? undefined : body, credentials: 'same-origin' });
    if (res.status === 401) { location.href = V.routes.login; throw new Error('auth'); }
    const type = res.headers.get('content-type') || '';
    const data = type.includes('json') ? await res.json() : await res.text();
    if (!res.ok) {
      const msg = (data && data.message) || (data && data.errors && Object.values(data.errors)[0][0]) || t('error');
      const err = new Error(msg); err.status = res.status; err.data = data; throw err;
    }
    return data;
  }
  V.api = api;

  // ---------------------------------------------------------------- Toasts
  function toast(msg, type) {
    if (!msg) return;
    const box = $('#toasts'); if (!box) return;
    const el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : '');
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, 3200);
    setTimeout(() => el.remove(), 3600);
  }
  V.toast = toast;
  const fail = (e) => { if (e && e.message !== 'auth') toast(e.message || t('error'), 'error'); };

  function requireAuth(e) {
    if (!V.user) { e && e.preventDefault(); toast(t('loginRequired')); setTimeout(() => (location.href = V.routes.login), 700); return false; }
    return true;
  }

  // ---------------------------------------------------------------- Menus déroulants
  function closeMenus(except) {
    $$('.menu.open').forEach((m) => { if (m !== except) { m.classList.remove('open'); const b = m.parentElement.querySelector('[data-dropdown]'); b && b.setAttribute('aria-expanded', 'false'); } });
  }
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-dropdown]');
    if (trigger) {
      e.preventDefault(); e.stopPropagation();
      const menu = trigger.parentElement.querySelector('.menu');
      const open = !menu.classList.contains('open');
      closeMenus(menu);
      menu.classList.toggle('open', open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { const f = menu.querySelector('a,button'); f && f.focus({ preventScroll: true }); }
      return;
    }
    if (!e.target.closest('.menu')) closeMenus();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeMenus(); closeModals(); closeLightbox(); }
    const menu = e.target.closest && e.target.closest('.menu.open');
    if (menu && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      const items = $$('a,button', menu); const i = items.indexOf(document.activeElement);
      const next = items[(i + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length]; next && next.focus();
    }
  });

  // ---------------------------------------------------------------- Modales
  let lastFocus = null;
  function openModal(id) {
    const m = document.getElementById(id); if (!m) return;
    lastFocus = document.activeElement;
    m.classList.add('open'); document.body.style.overflow = 'hidden';
    const f = m.querySelector('textarea, input:not([type=hidden]):not([readonly]), select, button'); f && setTimeout(() => f.focus(), 30);
  }
  function closeModals() {
    $$('.modal-backdrop.open').forEach((m) => m.classList.remove('open'));
    document.body.style.overflow = '';
    lastFocus && lastFocus.focus && lastFocus.focus();
  }
  V.openModal = openModal; V.closeModals = closeModals;
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-modal-open]');
    if (o) { e.preventDefault(); if (o.hasAttribute('data-auth') && !requireAuth(e)) return; openModal(o.dataset.modalOpen); return; }
    if (e.target.closest('[data-modal-close]') || e.target.classList.contains('modal-backdrop')) closeModals();
  });
  // Piège à focus basique dans les modales
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab') return;
    const m = $('.modal-backdrop.open'); if (!m) return;
    const f = $$('a[href],button:not([disabled]),textarea,input:not([type=hidden]),select', m).filter((el) => el.offsetParent !== null);
    if (!f.length) return;
    if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
    else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
  });

  // ---------------------------------------------------------------- Confirmation
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.dataset.confirm && !confirm(f.dataset.confirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);

  // ---------------------------------------------------------------- Actions génériques (like, repost, enregistrement, masquer…)
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-action]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    if (b.hasAttribute('data-auth') && !requireAuth(e)) return;
    if (b.dataset.confirm && !confirm(b.dataset.confirm)) return;
    if (b.dataset.busy) return; b.dataset.busy = '1';
    try {
      const data = await api(b.dataset.action, { method: b.dataset.method || 'POST' });
      closeMenus();
      if (b.hasAttribute('data-like') || b.hasAttribute('data-support')) {
        b.classList.toggle('active', !!data.active); b.classList.toggle('btn-soft', b.hasAttribute('data-support') && !!data.active);
        b.setAttribute('aria-pressed', data.active ? 'true' : 'false');
        const c = b.querySelector('[data-count]'); if (c && data.count !== undefined) c.textContent = data.count > 0 ? shortNum(data.count) : (b.hasAttribute('data-support') ? '0' : '');
        if (data.active) { b.classList.add('pop'); setTimeout(() => b.classList.remove('pop'), 320); }
      } else if (b.dataset.toggleClass) {
        const target = b.dataset.targetClosest ? b.closest(b.dataset.targetClosest).querySelector('.action') : b;
        (target || b).classList.toggle(b.dataset.toggleClass, !!data.active);
        if (data.count !== undefined && target) { const c = target.querySelector('[data-count]'); if (c) c.textContent = data.count > 0 ? shortNum(data.count) : ''; }
      }
      if (b.dataset.removeClosest) { const el = b.closest(b.dataset.removeClosest); if (el) { el.style.transition = 'opacity .2s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 200); } }
      if (b.hasAttribute('data-reload')) return location.reload();
      toast(data.message);
    } catch (err) { fail(err); } finally { delete b.dataset.busy; }
  });
  function shortNum(n) { n = +n; return n >= 1e6 ? (n / 1e6).toFixed(1).replace('.0', '') + 'M' : n >= 1e3 ? (n / 1e3).toFixed(1).replace('.0', '') + 'k' : String(n); }

  // ---------------------------------------------------------------- Suivre / ne plus suivre
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-follow]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    if (!requireAuth(e)) return;
    const state = b.dataset.state;
    try {
      const data = await api(state === 'none' ? b.dataset.follow : b.dataset.unfollow, { method: state === 'none' ? 'POST' : 'DELETE' });
      b.dataset.state = data.state;
      if (data.state === 'accepted') { b.className = b.className.replace('btn-dark', 'btn-outline') + ' is-following'; b.innerHTML = `<span class="when-idle">${t('following')}</span><span class="when-hover">${t('unfollow')}</span>`; b.dataset.state = 'following'; }
      else if (data.state === 'pending') { b.className = b.className.replace('btn-dark', 'btn-outline'); b.textContent = t('requested'); }
      else { b.className = b.className.replace('btn-outline', 'btn-dark').replace(' is-following', ''); b.textContent = t('follow'); }
      const fc = $('[data-followers-count]'); if (fc && data.followers !== undefined) fc.textContent = shortNum(data.followers);
      toast(data.message);
    } catch (err) { fail(err); }
  });

  // ---------------------------------------------------------------- data-auth sur liens / boutons simples
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-auth]');
    if (el && !el.matches('[data-action],[data-follow],[data-modal-open]') && !V.user) requireAuth(e);
  }, true);

  // ---------------------------------------------------------------- Copier
  async function copy(text) {
    try { await navigator.clipboard.writeText(text); } catch (e) {
      const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
    }
    toast(t('copied'));
  }
  document.addEventListener('click', (e) => { const b = e.target.closest('[data-copy]'); if (b) { e.preventDefault(); copy(b.dataset.copy); closeMenus(); } });

  // ---------------------------------------------------------------- Partage
  let share = {};
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-share]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    share = { url: b.dataset.shareUrl, title: b.dataset.shareTitle || document.title, type: b.dataset.shareType, id: b.dataset.shareId };
    const input = $('#share-url'); if (input) input.value = share.url;
    const internal = $('[data-share-internal]');
    if (internal) { internal.action = `/i/${share.type}/${share.id}/send`; internal.hidden = !['post', 'video', 'live', 'debate', 'question', 'answer', 'event', 'discussion', 'user', 'community'].includes(share.type); }
    $$('#share-modal [data-share-channel="repost"], #share-modal [data-share-channel="quote"]').forEach((el) => { el.hidden = !['post', 'video', 'live', 'debate', 'question', 'event'].includes(share.type) || (el.dataset.shareChannel === 'quote' && share.type !== 'post'); });
    if (navigator.share && window.matchMedia('(max-width: 640px)').matches && b.hasAttribute('data-share-native-first')) { nativeShare(); return; }
    openModal('share-modal');
  });
  function logShare(channel) { if (share.type && share.id) api(`/i/${share.type}/${share.id}/share`, { body: { channel } }).catch(() => {}); }
  async function nativeShare() { try { await navigator.share({ title: share.title, url: share.url }); logShare('native'); } catch (e) {} }
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-share-channel]');
    if (!b) return;
    const ch = b.dataset.shareChannel, u = encodeURIComponent(share.url), tt = encodeURIComponent(share.title || '');
    const links = { whatsapp: `https://wa.me/?text=${tt}%20${u}`, facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`, x: `https://twitter.com/intent/tweet?url=${u}&text=${tt}`,
      telegram: `https://t.me/share/url?url=${u}&text=${tt}`, email: `mailto:?subject=${tt}&body=${tt}%20${u}` };
    if (ch === 'link') { copy(share.url); logShare('link'); }
    else if (ch === 'native') { if (navigator.share) nativeShare(); else { copy(share.url); logShare('link'); } }
    else if (links[ch]) { window.open(links[ch], '_blank', 'noopener,width=620,height=560'); logShare(ch); }
    else if (ch === 'repost') { try { const d = await api(`/i/${share.type}/${share.id}/repost`); toast(d.message); closeModals(); } catch (err) { fail(err); } }
    else if (ch === 'quote') { closeModals(); openQuote(share.id, share.title, ''); }
  });
  function openQuote(id, text, author) {
    const i = $('#quote-of-id'); if (!i) return requireAuth();
    i.value = id; const p = $('#quote-preview'); p.innerHTML = ''; const s = document.createElement('div');
    if (author) { const a = document.createElement('strong'); a.textContent = author; p.appendChild(a); }
    s.textContent = text; p.appendChild(s); openModal('quote-modal');
  }
  document.addEventListener('click', (e) => { const b = e.target.closest('[data-quote]'); if (b) { e.preventDefault(); closeMenus(); if (requireAuth(e)) openQuote(b.dataset.quote, b.dataset.quoteText, b.dataset.quoteAuthor); } });

  // ---------------------------------------------------------------- Traduction automatique
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-translate]');
    if (!b) return;
    e.preventDefault(); closeMenus();
    const item = b.closest('[data-item], .comment, article') || document;
    const target = b.dataset.translateTarget ? $(b.dataset.translateTarget) : item.querySelector('[data-text]');
    if (!target) return;
    if (target.dataset.original) { target.innerHTML = target.dataset.original; delete target.dataset.original; return; }
    const label = b.innerHTML; b.textContent = t('translating');
    try {
      const d = await api(b.dataset.translate);
      target.dataset.original = target.innerHTML;
      target.innerHTML = `<div class="translated">${d.html}</div>`;
    } catch (err) { fail(err); } finally { b.innerHTML = label; }
  });

  // ---------------------------------------------------------------- Lightbox
  function closeLightbox() { const l = $('#lightbox'); l && l.classList.remove('open'); }
  document.addEventListener('click', (e) => {
    const img = e.target.closest('img[data-lightbox]');
    if (img) { e.preventDefault(); e.stopPropagation(); const l = $('#lightbox'); l.querySelector('img').src = img.currentSrc || img.src; l.querySelector('img').alt = img.alt; l.classList.add('open'); return; }
    if (e.target.closest('[data-lightbox-close]') || e.target.id === 'lightbox') closeLightbox();
  });

  // ---------------------------------------------------------------- Cartes cliquables
  document.addEventListener('click', (e) => {
    const card = e.target.closest('[data-href]');
    if (!card || e.target.closest('a,button,input,textarea,select,label,video,audio,form,.menu,[data-lightbox]')) return;
    if (window.getSelection && String(window.getSelection()).length) return;
    if (e.ctrlKey || e.metaKey) window.open(card.dataset.href, '_blank'); else location.href = card.dataset.href;
  });

  // ---------------------------------------------------------------- Défilement infini
  const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    entries.forEach(async (en) => {
      if (!en.isIntersecting) return;
      const el = en.target; io.unobserve(el);
      el.innerHTML = '<div class="spinner"></div>';
      try {
        const html = await (await fetch(el.dataset.infinite, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
        const tmp = document.createElement('div'); tmp.innerHTML = html;
        el.replaceWith(...tmp.childNodes);
        observeInfinite(); enhance(document);
      } catch (e) { el.innerHTML = `<a href="${el.dataset.infinite.replace(/[?&]partial=1/, '')}" class="btn btn-outline">${t('loading')}</a>`; }
    });
  }, { rootMargin: '600px' }) : null;
  function observeInfinite() { if (io) $$('[data-infinite]').forEach((el) => io.observe(el)); }

  // ---------------------------------------------------------------- Formulaires AJAX génériques
  document.addEventListener('submit', async (e) => {
    const f = e.target;
    if (!f.matches('[data-ajax]')) return;
    e.preventDefault();
    const btn = f.querySelector('button[type=submit], button:not([type])'); btn && (btn.disabled = true);
    try { const d = await api(f.action, { body: new FormData(f) }); toast(d.message); f.reset(); closeModals(); if (f.dataset.reload !== undefined) location.reload(); }
    catch (err) { fail(err); } finally { btn && (btn.disabled = false); }
  });

  // Vote de sondage
  document.addEventListener('submit', async (e) => {
    const f = e.target;
    if (!f.matches('[data-poll-form]')) return;
    e.preventDefault();
    if (!requireAuth(e)) return;
    const fd = new FormData(f);
    if (e.submitter && e.submitter.name) fd.append(e.submitter.name, e.submitter.value);
    try { const d = await api(f.action, { body: fd }); const w = f.closest('[data-poll-wrap]'); if (w) w.innerHTML = d.html; } catch (err) { fail(err); }
  });

  // ---------------------------------------------------------------- Commentaires
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-reply]');
    if (!b) return;
    const section = b.closest('#comments') || document;
    const form = section.querySelector('[data-comment-form]'); if (!form) return;
    form.parent_id.value = b.dataset.reply;
    const lbl = form.querySelector('[data-reply-label]'); lbl.hidden = false; lbl.textContent = '↳ @' + b.dataset.replyName;
    const ta = form.querySelector('textarea'); ta.value = '@' + b.dataset.replyName + ' '; ta.focus();
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  document.addEventListener('submit', async (e) => {
    const f = e.target;
    if (!f.matches('[data-comment-form]')) return;
    e.preventDefault();
    const btn = f.querySelector('button'); btn.disabled = true;
    try {
      const d = await api(f.action, { body: new FormData(f) });
      const tmp = document.createElement('div'); tmp.innerHTML = d.html; const node = tmp.firstElementChild;
      const parent = f.parent_id.value;
      const list = parent ? $(`[data-replies="${parent}"]`) : f.closest('#comments').querySelector('[data-comment-list]');
      if (list) { const empty = list.querySelector('[data-empty]'); empty && empty.remove(); parent ? list.appendChild(node) : list.prepend(node); enhance(node); }
      f.reset(); f.parent_id.value = ''; const lbl = f.querySelector('[data-reply-label]'); lbl && (lbl.hidden = true);
    } catch (err) { fail(err); } finally { btn.disabled = false; }
  });
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-edit-comment]');
    if (!b) return;
    closeMenus();
    const c = b.closest('.comment'); const txt = c.querySelector('[data-text]');
    if (c.querySelector('.edit-box')) return;
    const box = document.createElement('form'); box.className = 'edit-box mt-sm';
    box.innerHTML = `<textarea class="textarea" rows="2" maxlength="1000"></textarea><div class="row mt-sm"><button class="btn btn-primary btn-sm">OK</button><button type="button" class="btn btn-ghost btn-sm" data-cancel>✕</button></div>`;
    box.querySelector('textarea').value = txt.innerText.trim();
    txt.after(box); txt.hidden = true;
    box.querySelector('[data-cancel]').onclick = () => { box.remove(); txt.hidden = false; };
    box.onsubmit = async (ev) => {
      ev.preventDefault();
      try { const d = await api(b.dataset.editComment, { method: 'PUT', body: { body: box.querySelector('textarea').value } }); txt.innerHTML = d.html; box.remove(); txt.hidden = false; }
      catch (err) { fail(err); }
    };
  });

  // ---------------------------------------------------------------- Zones de texte : hauteur auto, compteur
  function autosize(ta) { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 420) + 'px'; }
  document.addEventListener('input', (e) => {
    const ta = e.target;
    if (ta.matches('[data-autosize]')) autosize(ta);
    const counter = ta.id && $(`[data-count-for="${ta.id}"]`);
    if (counter) { const left = +counter.dataset.max - [...ta.value].length; counter.textContent = left; counter.classList.toggle('over', left < 0); const sub = ta.form && ta.form.querySelector('[data-submit]'); sub && (sub.disabled = left < 0); }
  });

  // ---------------------------------------------------------------- Composer
  function initComposer(form) {
    if (form.dataset.ready) return; form.dataset.ready = '1';
    const images = form.querySelector('[data-images]'), previews = form.querySelector('[data-previews]');
    let files = [];
    const renderImages = () => {
      previews.innerHTML = '';
      files.forEach((f, i) => {
        const d = document.createElement('div'); d.className = 'preview';
        d.innerHTML = `<img alt=""><button type="button" class="remove" aria-label="✕">✕</button><input name="alts[${i}]" placeholder="Alt" maxlength="250" aria-label="Texte alternatif">`;
        d.querySelector('img').src = URL.createObjectURL(f);
        d.querySelector('.remove').onclick = () => { files.splice(i, 1); sync(); renderImages(); };
        previews.appendChild(d);
      });
    };
    const sync = () => { const dt = new DataTransfer(); files.forEach((f) => dt.items.add(f)); images.files = dt.files; };
    images && images.addEventListener('change', () => { files = files.concat(Array.from(images.files)).slice(0, 4); sync(); renderImages(); });

    // Vidéo : téléversement fragmenté reprenable
    const vf = form.querySelector('[data-video-file]');
    vf && vf.addEventListener('change', () => {
      const file = vf.files[0]; if (!file) return;
      const box = form.querySelector('[data-video-preview]'); box.hidden = false;
      box.querySelector('video').src = URL.createObjectURL(file);
      const bar = box.querySelector('.upload-progress span'), status = box.querySelector('[data-upload-status]');
      const sub = form.querySelector('[data-submit]'); sub.disabled = true;
      new Uploader(file, {
        onProgress: (p) => { bar.style.width = p + '%'; status.textContent = t('uploading') + ' ' + p + '%'; },
        onDone: (uuid) => { form.querySelector('[data-upload-target]').value = uuid; status.textContent = t('uploadDone'); sub.disabled = false; },
        onError: () => { status.textContent = t('uploadFailed'); },
      }).start();
    });

    // Sondage
    const poll = form.querySelector('[data-poll]');
    form.querySelector('[data-poll-toggle]') && form.querySelector('[data-poll-toggle]').addEventListener('click', () => { poll.hidden = !poll.hidden; });
    form.querySelector('[data-poll-remove]') && form.querySelector('[data-poll-remove]').addEventListener('click', () => { poll.hidden = true; poll.querySelectorAll('input[type=text]').forEach((i) => (i.value = '')); });

    // Émojis
    form.addEventListener('click', (e) => {
      const b = e.target.closest('[data-emoji]'); if (!b) return;
      const ta = form.querySelector('textarea'); const s = ta.selectionStart || ta.value.length;
      ta.value = ta.value.slice(0, s) + b.dataset.emoji + ta.value.slice(s); ta.focus(); ta.dispatchEvent(new Event('input', { bubbles: true })); closeMenus();
    });

    // Aperçu de lien
    const ta = form.querySelector('textarea'), lp = form.querySelector('[data-link-preview]');
    let lastUrl = null, timer;
    ta && lp && ta.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const m = ta.value.match(/https?:\/\/[^\s<>"']+/i); const url = m ? m[0].replace(/[.,;:!?)]+$/, '') : null;
        if (url === lastUrl) return; lastUrl = url;
        if (!url || V.dataSaver) { lp.hidden = true; return; }
        try {
          const d = await api(V.routes.linkPreview + '?url=' + encodeURIComponent(url), { method: 'GET' });
          if (!d.link_title) { lp.hidden = true; return; }
          lp.innerHTML = ''; lp.hidden = false;
          if (d.link_image) { const i = document.createElement('img'); i.src = d.link_image; i.alt = ''; lp.appendChild(i); }
          const body = document.createElement('div'); body.className = 'lc-body';
          body.innerHTML = '<div class="lc-domain"></div><div style="font-weight:650"></div><div class="small muted clamp-2"></div>';
          body.children[0].textContent = new URL(url).host; body.children[1].textContent = d.link_title; body.children[2].textContent = d.link_description || '';
          lp.appendChild(body);
        } catch (e) { lp.hidden = true; }
      }, 700);
    });

    // Envoi AJAX
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const sub = form.querySelector('[data-submit]'); sub.disabled = true;
      try {
        const d = await api(form.action, { body: new FormData(form) });
        const feed = $('#feed');
        if (feed && d.html) { const tmp = document.createElement('div'); tmp.innerHTML = d.html; const n = tmp.firstElementChild; feed.prepend(n); enhance(n); }
        else if (d.url) { location.href = d.url; return; }
        form.reset(); files = []; previews.innerHTML = ''; poll && (poll.hidden = true); lp && (lp.hidden = true);
        const vp = form.querySelector('[data-video-preview]'); vp && (vp.hidden = true);
        form.querySelector('[data-upload-target]').value = '';
        const c = form.querySelector('[data-count-for]'); c && (c.textContent = c.dataset.max);
        closeModals(); toast(d.message || '✓');
      } catch (err) { fail(err); } finally { sub.disabled = false; }
    });
  }

  // ---------------------------------------------------------------- Téléversement fragmenté reprenable
  class Uploader {
    constructor(file, cb = {}) { this.file = file; this.cb = cb; this.key = 'vw-up:' + file.name + ':' + file.size + ':' + file.lastModified; this.retries = 0; }
    async start() {
      try {
        const init = await api(V.routes.uploads, { body: { filename: this.file.name, size: this.file.size, mime: this.file.type } });
        this.uuid = init.uuid; this.chunk = init.chunk; this.offset = init.received || 0;
        if (this.offset > 0 && this.cb.onResume) this.cb.onResume();
        try { localStorage.setItem(this.key, this.uuid); } catch (e) {}
        await this.loop();
      } catch (e) { this.retry(e); }
    }
    async loop() {
      while (this.offset < this.file.size) {
        if (!navigator.onLine) { await new Promise((r) => window.addEventListener('online', r, { once: true })); if (this.cb.onResume) this.cb.onResume(); }
        const blob = this.file.slice(this.offset, this.offset + this.chunk);
        const res = await fetch(`/uploads/${this.uuid}/chunk`, { method: 'POST', body: blob, credentials: 'same-origin',
          headers: { 'X-CSRF-TOKEN': V.csrf, 'X-Offset': String(this.offset), 'Content-Type': 'application/octet-stream', 'Accept': 'application/json' } });
        const d = await res.json().catch(() => ({}));
        if (res.status === 409 && d.received !== undefined) { this.offset = d.received; continue; }
        if (!res.ok) throw new Error(d.message || 'upload');
        this.offset = d.received; this.retries = 0;
        this.cb.onProgress && this.cb.onProgress(Math.round(this.offset * 100 / this.file.size));
      }
      try { localStorage.removeItem(this.key); } catch (e) {}
      this.cb.onDone && this.cb.onDone(this.uuid);
    }
    retry(err) {
      this.cb.onError && this.cb.onError(err);
      const delay = Math.min(30000, 1000 * Math.pow(2, this.retries++));
      setTimeout(async () => {
        try {
          if (this.uuid) { const s = await api(`/uploads/${this.uuid}`, { method: 'GET' }); this.offset = s.received; this.cb.onResume && this.cb.onResume(); await this.loop(); }
          else await this.start();
        } catch (e) { this.retry(e); }
      }, delay);
    }
  }
  V.Uploader = Uploader;

  // Champs d'upload génériques : <input type="file" data-resumable="#hiddenInput">
  function initResumable(input) {
    if (input.dataset.ready) return; input.dataset.ready = '1';
    input.addEventListener('change', () => {
      const file = input.files[0]; if (!file) return;
      const target = $(input.dataset.resumable), wrap = input.closest('[data-upload-wrap]') || input.parentElement;
      const bar = wrap.querySelector('.upload-progress span'), status = wrap.querySelector('[data-upload-status]');
      const form = input.form, sub = form && form.querySelector('[type=submit]');
      sub && (sub.disabled = true);
      const pv = wrap.querySelector('[data-file-preview]');
      if (pv) { pv.src = URL.createObjectURL(file); pv.hidden = false; pv.onloadedmetadata = () => { const d = wrap.querySelector('[data-duration]'); d && (d.value = Math.round(pv.duration)); }; }
      input.removeAttribute('name');
      new Uploader(file, {
        onProgress: (p) => { bar && (bar.style.width = p + '%'); status && (status.textContent = t('uploading') + ' ' + p + '%'); },
        onResume: () => { status && (status.textContent = t('uploadResumed')); },
        onDone: (uuid) => { target.value = uuid; status && (status.textContent = t('uploadDone')); sub && (sub.disabled = false); },
        onError: () => { status && (status.textContent = t('uploadFailed')); },
      }).start();
    });
  }

  // ---------------------------------------------------------------- Autocomplétion (@mentions, recherche)
  function attachSuggest(input, box, onPick, getQuery) {
    let timer, active = -1, items = [];
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = getQuery();
      if (!q || q.length < 1) { box.hidden = true; return; }
      timer = setTimeout(async () => {
        try {
          items = await api(V.routes.suggest + '?q=' + encodeURIComponent(q), { method: 'GET' });
          box.innerHTML = ''; active = -1;
          items.forEach((it, i) => {
            const a = document.createElement('a'); a.href = it.url;
            a.innerHTML = (it.avatar ? '<img class="avatar avatar-sm" alt="">' : '<span class="avatar avatar-sm" style="display:grid;place-items:center">#</span>') + '<span class="grow"><span class="name"></span><span class="handle small" style="display:block"></span></span>';
            if (it.avatar) a.querySelector('img').src = it.avatar;
            a.querySelector('.name').textContent = it.label; a.querySelector('.handle').textContent = it.sub;
            a.addEventListener('mousedown', (e) => { if (onPick) { e.preventDefault(); onPick(it); box.hidden = true; } });
            box.appendChild(a);
          });
          box.hidden = !items.length;
        } catch (e) { box.hidden = true; }
      }, 180);
    });
    input.addEventListener('keydown', (e) => {
      if (box.hidden) return;
      const links = $$('a', box);
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); active = (active + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length; links.forEach((l, i) => l.classList.toggle('active', i === active)); }
      else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); onPick ? (onPick(items[active]), (box.hidden = true)) : (location.href = links[active].href); }
      else if (e.key === 'Escape') box.hidden = true;
    });
    input.addEventListener('blur', () => setTimeout(() => (box.hidden = true), 150));
  }
  function initMention(ta) {
    if (ta.dataset.mentionReady) return; ta.dataset.mentionReady = '1';
    const wrap = document.createElement('div'); wrap.style.position = 'relative'; ta.parentNode.insertBefore(wrap, ta); wrap.appendChild(ta);
    const box = document.createElement('div'); box.className = 'suggest'; box.hidden = true; wrap.appendChild(box);
    const token = () => { const before = ta.value.slice(0, ta.selectionStart); const m = before.match(/(^|\s)([@#][\p{L}\p{N}_]{1,30})$/u); return m ? m[2] : null; };
    attachSuggest(ta, box, (it) => {
      const before = ta.value.slice(0, ta.selectionStart), after = ta.value.slice(ta.selectionStart);
      const ins = it.type === 'user' ? '@' + it.value + ' ' : '#' + it.value + ' ';
      ta.value = before.replace(/[@#][\p{L}\p{N}_]{1,30}$/u, ins) + after; ta.focus();
    }, token);
  }
  function initSearchSuggest(form) {
    if (form.dataset.ready) return; form.dataset.ready = '1';
    const input = form.querySelector('input[type=search]'), box = form.querySelector('.suggest');
    attachSuggest(input, box, null, () => input.value.trim());
  }

  // ---------------------------------------------------------------- Notifications (actualisation légère)
  let lastNotif = null;
  async function pollCounts() {
    if (!V.user || !V.routes.notificationsCount || document.hidden) return;
    try {
      const d = await api(V.routes.notificationsCount, { method: 'GET' });
      $$('[data-count="notifications"]').forEach((el) => { el.textContent = d.notifications; el.hidden = !d.notifications; });
      $$('[data-count="messages"]').forEach((el) => { el.textContent = d.messages; el.hidden = !d.messages; });
      if (d.latest && lastNotif && d.latest.id !== lastNotif) toast(d.latest.text);
      lastNotif = d.latest ? d.latest.id : lastNotif;
      document.title = document.title.replace(/^\(\d+\) /, '') ; if (d.notifications) document.title = `(${d.notifications}) ` + document.title;
    } catch (e) {}
  }

  // ---------------------------------------------------------------- Notifications push
  async function subscribePush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !V.vapid) return;
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') return toast(t('pushDenied'), 'error');
    const reg = await navigator.serviceWorker.ready;
    const key = Uint8Array.from(atob(V.vapid.replace(/-/g, '+').replace(/_/g, '/') + '=='.slice(0, (4 - V.vapid.length % 4) % 4)), (c) => c.charCodeAt(0));
    const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
    const json = sub.toJSON();
    await api(V.routes.pushSubscribe, { body: { endpoint: json.endpoint, keys: json.keys, contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0] } });
    toast(t('pushEnabled'));
  }
  document.addEventListener('click', (e) => { if (e.target.closest('[data-push-subscribe]')) { e.preventDefault(); subscribePush().catch(fail); } });

  // ---------------------------------------------------------------- Thème
  document.addEventListener('click', async (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const root = document.documentElement;
    const isDark = root.dataset.theme === 'dark' || (!root.dataset.theme && matchMedia('(prefers-color-scheme: dark)').matches);
    const theme = isDark ? 'light' : 'dark';
    root.dataset.theme = theme; closeMenus();
    try { V.user ? await api('/settings/theme', { body: { theme } }) : (document.cookie = 'theme=' + theme + ';path=/;max-age=31536000;samesite=lax'); } catch (err) {}
  });

  // ---------------------------------------------------------------- Cookies
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-cookie]'); if (!b) return;
    try { await api(V.routes.cookies, { body: { choice: b.dataset.cookie } }); } catch (err) {}
    const banner = $('#cookie-banner'); banner && banner.remove();
  });

  // ---------------------------------------------------------------- Raccourcis clavier
  document.addEventListener('keydown', (e) => {
    if (e.target.closest('input,textarea,select,[contenteditable]') || e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key === 'n' && V.user && $('#compose-modal')) { e.preventDefault(); openModal('compose-modal'); }
    if (e.key === '/') { const s = $('#side-search') || $('input[type=search]'); if (s) { e.preventDefault(); s.focus(); } }
  });

  // ---------------------------------------------------------------- Connexion / hors ligne
  window.addEventListener('offline', () => toast(t('offline'), 'error'));
  window.addEventListener('online', () => toast(t('online')));

  // ---------------------------------------------------------------- Économie de données
  function dataSaver(root) {
    if (!V.reduceAutoplay) return;
    $$('video[autoplay]', root).forEach((v) => { v.removeAttribute('autoplay'); v.pause && v.pause(); });
    if (V.dataSaver) $$('video', root).forEach((v) => (v.preload = 'none'));
  }

  // ---------------------------------------------------------------- Initialisation
  function enhance(root) {
    $$('[data-composer]', root).forEach(initComposer);
    $$('[data-mention]', root).forEach(initMention);
    $$('form[data-suggest]', root).forEach(initSearchSuggest);
    $$('[data-resumable]', root).forEach(initResumable);
    $$('textarea[data-autosize]', root).forEach(autosize);
    dataSaver(root);
  }
  V.enhance = enhance;

  document.addEventListener('DOMContentLoaded', () => {
    enhance(document);
    observeInfinite();
    if (V.user) { pollCounts(); setInterval(pollCounts, V.dataSaver ? 90000 : 30000); }
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
})();
