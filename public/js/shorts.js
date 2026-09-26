/* Vwajèn Shorts — défilement vertical, lecture automatique, pause au toucher, clavier, découverte infinie. */
(function () {
  'use strict';
  const page = document.getElementById('shorts');
  if (!page) return;
  const V = window.Vwajen || {};
  let muted = true, loading = false;
  const seen = new Set();
  const viewed = new Set();

  function setMuted(m) {
    muted = m;
    page.querySelectorAll('video').forEach((v) => (v.muted = muted));
    page.querySelectorAll('[data-mute]').forEach((b) => (b.style.opacity = muted ? .7 : 1));
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => {
      const sec = en.target, v = sec.querySelector('video');
      if (en.isIntersecting && en.intersectionRatio > .6) {
        v.preload = 'auto'; v.muted = muted;
        if (!V.reduceAutoplay || sec.dataset.userPlayed) v.play().then(() => sec.classList.remove('paused')).catch(() => sec.classList.add('paused'));
        else sec.classList.add('paused');
        history.replaceState(null, '', sec.dataset.url);
        if (!viewed.has(sec.dataset.short)) { viewed.add(sec.dataset.short); setTimeout(() => { if (!v.paused) V.api(sec.dataset.view, { body: { seconds: 2 } }).catch(() => {}); }, 2000); }
        // Précharge le suivant (sauf économie de données)
        const next = sec.nextElementSibling;
        if (next && !V.dataSaver) { const nv = next.querySelector('video'); nv && (nv.preload = 'metadata'); }
        if (!sec.nextElementSibling || !sec.nextElementSibling.nextElementSibling) loadMore();
      } else {
        v.pause();
      }
    });
  }, { root: page, threshold: [0, .6, 1] });

  function register(root) {
    root.querySelectorAll('[data-short]').forEach((sec) => {
      if (sec.dataset.ready) return; sec.dataset.ready = '1';
      seen.add(sec.dataset.short);
      io.observe(sec);
      const v = sec.querySelector('video');
      v.addEventListener('click', () => {
        sec.dataset.userPlayed = '1';
        if (v.paused) { v.play(); sec.classList.remove('paused'); } else { v.pause(); sec.classList.add('paused'); }
      });
    });
    V.enhance && V.enhance(root);
  }

  async function loadMore() {
    if (loading) return; loading = true;
    try {
      const r = await V.api(page.dataset.feed + '?exclude=' + [...seen].join(','), { method: 'GET' });
      if (r.html && r.html.trim()) {
        const tmp = document.createElement('div'); tmp.innerHTML = r.html;
        [...tmp.children].forEach((c) => page.appendChild(c));
        register(page);
      }
    } catch (e) {} finally { loading = false; }
  }

  page.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-s-like],[data-s-repost],[data-s-save],[data-mute]');
    if (!b) return;
    e.preventDefault();
    if (b.matches('[data-mute]')) return setMuted(!muted);
    if (!V.user) { location.href = V.routes.login; return; }
    const url = b.dataset.sLike || b.dataset.sRepost || b.dataset.sSave;
    try {
      const d = await V.api(url);
      if (b.matches('[data-s-save]')) b.classList.toggle('saved', !!d.active);
      else b.classList.toggle('active', !!d.active);
      const c = b.querySelector('[data-count]'); if (c && d.count !== undefined) c.textContent = d.count;
      d.message && V.toast(d.message);
    } catch (err) { V.toast(err.message, 'error'); }
  });

  // Clavier : ↑ ↓ pour naviguer, espace pause, M son
  page.addEventListener('keydown', (e) => {
    const h = page.clientHeight;
    if (e.key === 'ArrowDown' || e.key === 'j') { e.preventDefault(); page.scrollBy({ top: h, behavior: 'smooth' }); }
    if (e.key === 'ArrowUp' || e.key === 'k') { e.preventDefault(); page.scrollBy({ top: -h, behavior: 'smooth' }); }
    if (e.key === 'm') setMuted(!muted);
    if (e.key === ' ') {
      e.preventDefault();
      const cur = [...page.querySelectorAll('[data-short]')].find((s) => Math.abs(s.getBoundingClientRect().top - page.getBoundingClientRect().top) < 50);
      cur && cur.querySelector('video').click();
    }
  });

  register(page);
  page.focus({ preventScroll: true });
})();
