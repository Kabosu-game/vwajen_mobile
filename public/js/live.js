/* Vwajèn Live — diffusion WebRTC depuis le navigateur (plusieurs hôtes), lecture HLS, chat, réactions, replay. */
(function () {
  'use strict';
  const app = document.getElementById('live-app');
  if (!app) return;
  const V = window.Vwajen || {};
  const d = app.dataset;
  const $ = (s, r = app) => r.querySelector(s);
  const api = (url, opts) => V.api(url, opts);
  const toast = (m, t) => V.toast && V.toast(m, t);
  const ice = JSON.parse(d.ice || '{}');
  const isAudio = d.kind === 'audio';

  let status = d.status;
  const peerKey = 'vw-live-peer-' + d.live;
  let peerId = sessionStorage.getItem(peerKey);
  if (!peerId) { peerId = 'p' + Math.random().toString(36).slice(2, 12) + Date.now().toString(36); sessionStorage.setItem(peerKey, peerId); }

  const grid = $('[data-grid]');
  const empty = $('[data-stage-empty]');
  const pcs = new Map();          // peer -> RTCPeerConnection
  const tiles = new Map();        // peer -> element
  let hlsActive = false, localStream = null, publishing = false, lastSignal = 0, lastReaction = 0, recorder = null, recChunks = [];

  // ---------------------------------------------------------------- Scène
  function layout() {
    const n = grid ? grid.children.length : 0;
    if (!grid) return;
    grid.className = 'stage-grid ' + (n <= 1 ? 'n1' : n === 2 ? 'n2' : n <= 4 ? 'n4' : n <= 6 ? 'n6' : 'nmany');
    if (empty) empty.hidden = n > 0 || !!(hlsActive);
  }
  function addTile(key, stream, label, muted) {
    let tile = tiles.get(key);
    if (!tile) {
      tile = document.createElement('div');
      tile.className = 'tile-video' + (isAudio ? ' audio-only' : '');
      tile.innerHTML = (isAudio ? '<img class="avatar" alt=""><audio autoplay></audio>' : '<video autoplay playsinline></video>') + '<span class="who"></span>';
      grid.appendChild(tile); tiles.set(key, tile);
    }
    const media = tile.querySelector(isAudio ? 'audio' : 'video');
    if (media.srcObject !== stream) media.srcObject = stream;
    media.muted = !!muted;
    if (isAudio) { const img = tile.querySelector('img'); img.src = key === 'local' ? (V.user && V.user.avatar) : 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><circle cx="48" cy="48" r="48" fill="#1d4ed8"/><text x="48" y="60" font-size="40" text-anchor="middle" fill="#fff">🎙</text></svg>'); }
    tile.querySelector('.who').textContent = label || '';
    media.play && media.play().catch(() => {});
    layout();
  }
  function removeTile(key) { const t = tiles.get(key); if (t) { t.remove(); tiles.delete(key); layout(); } }

  // ---------------------------------------------------------------- Signalisation
  async function sendSignal(to, type, payload) {
    try { await api(d.signal, { body: { from: peerId, to, type, payload: JSON.stringify(payload) } }); } catch (e) {}
  }
  function makePc(remote, name) {
    const pc = new RTCPeerConnection(ice);
    pc.onicecandidate = (e) => { if (e.candidate) sendSignal(remote, 'candidate', e.candidate); };
    pc.ontrack = (e) => { addTile(remote, e.streams[0], name); };
    pc.onconnectionstatechange = () => {
      if (['failed', 'closed'].includes(pc.connectionState)) { closePc(remote); }
    };
    pcs.set(remote, pc);
    return pc;
  }
  function closePc(remote) {
    const pc = pcs.get(remote); if (pc) { try { pc.close(); } catch (e) {} pcs.delete(remote); }
    removeTile(remote);
  }
  // Spectateur : ouvre une connexion (réception seule) vers chaque diffuseur.
  async function connectTo(host) {
    if (pcs.has(host.peer_id)) return;
    const pc = makePc(host.peer_id, host.name);
    pc.addTransceiver('audio', { direction: 'recvonly' });
    if (!isAudio) pc.addTransceiver('video', { direction: 'recvonly' });
    const offer = await pc.createOffer();
    await pc.setLocalDescription(offer);
    sendSignal(host.peer_id, 'offer', pc.localDescription);
  }
  // Diffuseur : répond aux offres en envoyant son flux local.
  async function handleOffer(from, desc) {
    if (!localStream || !publishing) return;
    closePc(from);
    const pc = makePc(from, '');
    localStream.getTracks().forEach((t) => pc.addTrack(t, localStream));
    await pc.setRemoteDescription(desc);
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    sendSignal(from, 'answer', pc.localDescription);
  }
  async function pollSignals() {
    try {
      const list = await api(`${d.signals}?peer_id=${encodeURIComponent(peerId)}&after=${lastSignal}`, { method: 'GET' });
      for (const s of list) {
        lastSignal = Math.max(lastSignal, s.id);
        const payload = JSON.parse(s.payload);
        const pc = pcs.get(s.from_peer);
        if (s.type === 'offer') await handleOffer(s.from_peer, payload);
        else if (s.type === 'answer' && pc && pc.signalingState === 'have-local-offer') await pc.setRemoteDescription(payload);
        else if (s.type === 'candidate' && pc) { try { await pc.addIceCandidate(payload); } catch (e) {} }
        else if (s.type === 'bye') closePc(s.from_peer);
      }
    } catch (e) {}
  }

  // ---------------------------------------------------------------- Présence
  async function heartbeat() {
    try {
      const r = await api(d.heartbeat, { body: { peer_id: peerId, publish: publishing ? 1 : 0, after_reaction: lastReaction } });
      if (r.kicked) { stopAll(); toast(r.banned ? 'Bloqué' : 'Expulsé', 'error'); app.innerHTML = '<div class="card empty"><h3>⛔</h3></div>'; return; }
      status = r.status;
      const vc = $('[data-viewers]'); vc && (vc.textContent = r.viewers);
      const badge = $('[data-live-badge]'); badge && (badge.hidden = status !== 'live');
      const go = $('[data-go-live]'), end = $('[data-end-live]'), join = $('[data-join-stage]');
      go && (go.hidden = status === 'live'); end && (end.hidden = status !== 'live'); join && (join.disabled = status !== 'live');
      if (status === 'live') {
        const hostIds = new Set(r.hosts.map((h) => h.peer_id));
        r.hosts.forEach((h) => connectTo(h).catch(() => {}));
        [...pcs.keys()].forEach((p) => { if (!hostIds.has(p) && !publishing) closePc(p); });
        if (!r.hosts.length && !publishing && d.playback) startHls();
        const c = $('[data-connecting]'); if (c && !r.hosts.length && !d.playback) c.textContent = '…';
      } else if (status === 'ended' && !publishing) {
        [...pcs.keys()].forEach(closePc);
      }
      (r.reactions || []).forEach((x) => { lastReaction = Math.max(lastReaction, x.id); floaty(x.emoji); });
      if (r.audience) renderAudience(r.audience);
    } catch (e) {}
  }

  // ---------------------------------------------------------------- HLS (OBS / serveur média)
  function startHls() {
    if (hlsActive) return;
    const v = $('[data-hls]'); if (!v) return;
    hlsActive = true; v.hidden = false; empty && (empty.hidden = true);
    if (v.canPlayType('application/vnd.apple.mpegurl')) { v.src = d.playback; }
    else if (window.Hls && window.Hls.isSupported()) {
      const hls = new window.Hls({ capLevelToPlayerSize: true, startLevel: V.dataSaver ? 0 : -1 });
      hls.loadSource(d.playback); hls.attachMedia(v);
    }
    if (!V.reduceAutoplay) v.play().catch(() => {});
  }

  // ---------------------------------------------------------------- Studio (hôtes)
  async function startCamera() {
    try {
      localStream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true }, video: isAudio ? false : { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 24 } } });
      addTile('local', localStream, (V.user && V.user.name) + ' (vous)', true);
      ['[data-toggle-mic]', '[data-toggle-cam]', '[data-screen]'].forEach((s) => { const b = $(s); b && (b.hidden = false); });
      const b = $('[data-cam-start]'); b && (b.hidden = true);
      if (status === 'live' && d.host === '1' && d.owner === '1') publishing = true;
    } catch (e) { toast('Caméra / micro indisponible : ' + e.message, 'error'); }
  }
  function startRecording() {
    const box = $('[data-record]');
    if (!localStream || !box || !box.checked || !window.MediaRecorder) return;
    const type = ['video/webm;codecs=vp8,opus', 'video/webm', 'audio/webm'].find((t) => MediaRecorder.isTypeSupported(t));
    recChunks = [];
    recorder = new MediaRecorder(localStream, type ? { mimeType: type, videoBitsPerSecond: 1200000 } : undefined);
    recorder.ondataavailable = (e) => e.data.size && recChunks.push(e.data);
    recorder.start(5000);
  }
  function uploadReplay() {
    return new Promise((resolve) => {
      if (!recorder) return resolve();
      recorder.onstop = () => {
        if (!recChunks.length) return resolve();
        const file = new File(recChunks, `live-${d.live}.webm`, { type: recChunks[0].type || 'video/webm' });
        const bar = $('[data-replay-progress]'), st = $('[data-replay-status]');
        bar && (bar.hidden = false);
        new V.Uploader(file, {
          onProgress: (p) => { bar && (bar.querySelector('span').style.width = p + '%'); st && (st.textContent = 'Replay : ' + p + '%'); },
          onDone: async (uuid) => { try { await api(d.replay, { body: { upload: uuid } }); st && (st.textContent = '✓ Replay publié'); } catch (e) { toast(e.message, 'error'); } resolve(); },
          onError: () => { st && (st.textContent = 'Replay : nouvelle tentative…'); },
        }).start();
      };
      recorder.state !== 'inactive' ? recorder.stop() : recorder.onstop();
    });
  }
  function stopAll() {
    [...pcs.keys()].forEach((p) => { sendSignal(p, 'bye', {}); closePc(p); });
    publishing = false;
  }

  app.addEventListener('click', async (e) => {
    const t = e.target.closest('button'); if (!t) return;
    if (t.matches('[data-cam-start]')) return startCamera();
    if (t.matches('[data-toggle-mic]') && localStream) { const a = localStream.getAudioTracks()[0]; if (a) { a.enabled = !a.enabled; t.style.opacity = a.enabled ? 1 : .4; } return; }
    if (t.matches('[data-toggle-cam]') && localStream) { const v = localStream.getVideoTracks()[0]; if (v) { v.enabled = !v.enabled; t.style.opacity = v.enabled ? 1 : .4; } return; }
    if (t.matches('[data-screen]') && localStream) {
      try {
        const screen = await navigator.mediaDevices.getDisplayMedia({ video: true });
        const track = screen.getVideoTracks()[0], old = localStream.getVideoTracks()[0];
        pcs.forEach((pc) => { const s = pc.getSenders().find((x) => x.track && x.track.kind === 'video'); s && s.replaceTrack(track); });
        track.onended = () => pcs.forEach((pc) => { const s = pc.getSenders().find((x) => x.track && x.track.kind === 'video'); s && s.replaceTrack(old); });
      } catch (err) {}
      return;
    }
    if (t.matches('[data-go-live]')) {
      if (!localStream && !d.playback) await startCamera();
      try { await api(d.start); status = 'live'; publishing = !!localStream; startRecording(); toast('● LIVE'); heartbeat(); } catch (err) { toast(err.message, 'error'); }
      return;
    }
    if (t.matches('[data-join-stage]')) {
      if (!localStream) await startCamera();
      publishing = !!localStream; t.hidden = true; heartbeat(); return;
    }
    if (t.matches('[data-end-live]')) {
      if (t.dataset.confirm && !confirm(t.dataset.confirm)) return;
      try { await api(d.end); } catch (err) {}
      stopAll(); status = 'ended';
      await uploadReplay();
      localStream && localStream.getTracks().forEach((tr) => tr.stop());
      toast('Live terminé'); setTimeout(() => location.reload(), 1500);
      return;
    }
    if (t.matches('[data-react]')) {
      if (!V.user) return;
      floaty(t.dataset.react);
      try { await api(d.react, { body: { emoji: t.dataset.react } }); } catch (err) {}
      return;
    }
    if (t.matches('[data-kick-viewer],[data-ban-viewer]')) {
      const action = t.matches('[data-ban-viewer]') ? 'ban' : 'kick';
      try { await api(`${d.kick}/${t.dataset.id}/${action}`); t.closest('div').remove(); } catch (err) { toast(err.message, 'error'); }
    }
  });

  function renderAudience(list) {
    const box = $('[data-audience]'); if (!box) return;
    box.innerHTML = '';
    list.forEach((v) => {
      const row = document.createElement('div'); row.className = 'row'; row.style.padding = '.2rem 0';
      row.innerHTML = '<span class="grow truncate"></span><button type="button" class="btn btn-ghost btn-sm" data-kick-viewer>⏏</button><button type="button" class="btn btn-ghost btn-sm" data-ban-viewer>⛔</button>';
      row.querySelector('span').textContent = v.name + (v.username ? ' @' + v.username : '');
      row.querySelectorAll('button').forEach((b) => (b.dataset.id = v.id));
      box.appendChild(row);
    });
  }

  function floaty(emoji) {
    const layer = $('[data-reactions-layer]'); if (!layer) return;
    const el = document.createElement('span'); el.className = 'floaty'; el.textContent = emoji; el.style.left = Math.random() * 30 + 'px';
    layer.appendChild(el); setTimeout(() => el.remove(), 2700);
  }

  // ---------------------------------------------------------------- Chat
  const chatBody = $('[data-chat-body]');
  let lastChat = 0;
  async function pollChat() {
    try {
      const r = await api(`${d.chat}?after=${lastChat}`, { method: 'GET' });
      r.messages.forEach((m) => {
        lastChat = Math.max(lastChat, m.id);
        const row = document.createElement('div'); row.className = 'chat-msg' + (m.hidden ? ' hidden-msg' : ''); row.dataset.id = m.id;
        row.innerHTML = `<img class="avatar avatar-xs" alt=""><div class="grow"><span class="who${m.user.host ? ' host' : ''}"></span><span class="txt"></span></div>` +
          (d.moderator === '1' ? '<span class="mod"><button type="button" class="btn btn-ghost btn-sm" data-chat-pin title="📌">📌</button><button type="button" class="btn btn-ghost btn-sm" data-chat-hide title="🚫">🚫</button></span>' : '');
        row.querySelector('img').src = m.user.avatar;
        row.querySelector('.who').textContent = m.user.name + (m.user.verified ? ' ✔' : '');
        const tmp = document.createElement('textarea'); tmp.innerHTML = m.body; row.querySelector('.txt').textContent = tmp.value;
        const nearBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight < 80;
        chatBody.appendChild(row);
        if (nearBottom) chatBody.scrollTop = chatBody.scrollHeight;
      });
      const pin = $('[data-pinned]');
      if (pin) { if (r.pinned) { pin.hidden = false; const tmp = document.createElement('textarea'); tmp.innerHTML = r.pinned.body; pin.textContent = '📌 ' + r.pinned.name + ' : ' + tmp.value; } else pin.hidden = true; }
      const input = $('[data-chat-form] input'); if (input) { input.disabled = !r.enabled && d.moderator !== '1'; }
    } catch (e) {}
  }
  const chatForm = $('[data-chat-form]');
  chatForm && chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = chatForm.querySelector('input'); const body = input.value.trim(); if (!body) return;
    input.value = '';
    try { await api(d.chatPost, { body: { body } }); pollChat(); } catch (err) { toast(err.message, 'error'); input.value = body; }
  });
  chatBody && chatBody.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-chat-hide],[data-chat-pin]'); if (!b) return;
    const id = b.closest('.chat-msg').dataset.id;
    try {
      const r = await api(`${d.chatMod}/${id}/${b.matches('[data-chat-hide]') ? 'hide' : 'pin'}`);
      if (r.hidden !== undefined) b.closest('.chat-msg').classList.toggle('hidden-msg', r.hidden);
      pollChat();
    } catch (err) { toast(err.message, 'error'); }
  });

  // ---------------------------------------------------------------- Boucles
  const slow = V.dataSaver ? 2 : 1;
  if (grid) {
    heartbeat(); setInterval(heartbeat, 5000 * slow);
    setInterval(pollSignals, 1500);
    if (status === 'live' && d.playback && d.host !== '1') setTimeout(() => { if (!pcs.size) startHls(); }, 4000);
  }
  if (chatBody) { pollChat(); setInterval(pollChat, 3000 * slow); }
  window.addEventListener('beforeunload', () => { if (publishing) stopAll(); });

  // Vues du replay
  const rv = app.querySelector('video[data-track-view]');
  if (rv) { let sent = false; rv.addEventListener('play', () => { if (!sent) { sent = true; api(rv.dataset.trackView, { body: { seconds: 0 } }).catch(() => {}); } }); }
})();
