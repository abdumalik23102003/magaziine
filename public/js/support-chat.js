(function () {
  'use strict';

  var root = document.getElementById('support-chat');
  if (!root) return;

  var cfg = {};
  try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }

  var q = function (s) { return root.querySelector(s); };
  var panel = q('[data-panel]');
  var list = q('[data-list]');
  var form = q('[data-form]');
  var input = q('[data-input]');
  var nameWrap = q('[data-name-wrap]');
  var nameInput = q('[data-name]');
  var badge = q('[data-badge]');
  var note = q('[data-note]');
  var offline = q('[data-offline]');

  var state = { open: false, lastId: 0, unread: 0, timer: null, sending: false, hasConversation: false };
  var seen = {};

  function api(method, url, body) {
    var headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': cfg.csrf
    };
    if (body) headers['Content-Type'] = 'application/json';
    return fetch(url, {
      method: method,
      headers: headers,
      credentials: 'same-origin',
      body: body ? JSON.stringify(body) : undefined
    }).then(function (r) {
      if (!r.ok) {
        var err = new Error('http ' + r.status);
        err.status = r.status;
        throw err;
      }
      return r.json();
    });
  }

  function scrollDown() { list.scrollTop = list.scrollHeight; }

  function setNote(text) {
    note.textContent = text || '';
    note.classList.toggle('hidden', !text);
  }

  function setUnread(n) {
    state.unread = n;
    badge.textContent = n > 99 ? '99+' : String(n);
    badge.classList.toggle('hidden', n < 1 || state.open);
    window.dispatchEvent(new CustomEvent('support-chat:unread', { detail: { count: n } }));
  }

  function bubble(type, text) {
    var tpl = q('[data-tpl="' + (type === 'customer' || type === 'operator' ? type : 'system') + '"]');
    var node = tpl.content.firstElementChild.cloneNode(true);
    node.querySelector('[data-body]').textContent = text;
    return node;
  }

  function renderMessage(m) {
    if (seen[m.id]) return;
    seen[m.id] = true;
    list.appendChild(bubble(m.sender_type, m.body));
    if (m.id > state.lastId) state.lastId = m.id;
  }

  function resetThread() {
    list.textContent = '';
    seen = {};
    state.lastId = 0;
    state.hasConversation = false;
  }

  function applyData(data, replace) {
    if (replace) resetThread();

    state.hasConversation = !!data.conversation;
    nameWrap.classList.toggle('hidden', !(cfg.guest && !state.hasConversation));
    offline.classList.toggle('hidden', data.working !== false);

    var stick = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
    (data.messages || []).forEach(renderMessage);
    if (state.open && (stick || replace || (data.messages || []).length)) scrollDown();

    setUnread(state.open ? 0 : (data.unread || 0));
  }

  async function load(open) {
    var data = await api('GET', cfg.urls.show + '?open=' + (open ? 1 : 0));
    applyData(data, true);
  }

  async function poll() {
    if (state.sending || document.hidden) return;
    try {
      var data = await api('GET', cfg.urls.messages + '?after=' + state.lastId + '&open=' + (state.open ? 1 : 0));
      if (!data.conversation && state.hasConversation) {
        resetThread();
        setNote('Suhbat yopildi.');
        setUnread(0);
        nameWrap.classList.toggle('hidden', !cfg.guest);
        return;
      }
      if (data.conversation) setNote('');
      applyData(data, false);
    } catch (e) {
      if (state.open && e.status !== 429) setNote('Aloqa uzildi, qayta urinilmoqda...');
    }
  }

  function schedule() {
    clearTimeout(state.timer);
    var seconds = state.open ? cfg.pollOpen : cfg.pollClosed;
    if (!seconds || document.hidden) return;
    state.timer = setTimeout(async function () {
      await poll();
      schedule();
    }, seconds * 1000);
  }

  function toggle(force) {
    var next = typeof force === 'boolean' ? force : !state.open;
    state.open = next;
    panel.classList.toggle('hidden', !next);
    panel.classList.toggle('flex', next);
    if (next) {
      setUnread(0);
      load(true).then(function () { scrollDown(); input.focus(); }).catch(function () {
        setNote('Chatni yuklab bo‘lmadi.');
      });
    }
    schedule();
  }

  function addPending(text) {
    var el = bubble('customer', text);
    el.style.opacity = '0.6';
    list.appendChild(el);
    scrollDown();
    return el;
  }

  async function send(text, el) {
    state.sending = true;
    el = el || addPending(text);
    el.style.opacity = '0.6';
    el.querySelector('[data-state]').classList.add('hidden');

    var payload = { body: text };
    if (cfg.guest && !state.hasConversation && nameInput.value.trim()) {
      payload.guest_name = nameInput.value.trim();
    }

    try {
      var data = await api('POST', cfg.urls.store, payload);
      el.remove();
      applyData(data, false);
      setNote('');
      scrollDown();
    } catch (e) {
      el.style.opacity = '1';
      var st = el.querySelector('[data-state]');
      st.classList.remove('hidden');
      var retry = st.querySelector('[data-retry]');
      retry.onclick = function () { send(text, el); };
      if (e.status === 429) setNote('Juda tez yozyapsiz, biroz kuting.');
      else if (e.status === 422) setNote('Xabar noto‘g‘ri yoki juda uzun.');
    } finally {
      state.sending = false;
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = input.value.trim();
    if (!text) return;
    if (text.length > cfg.maxLength) {
      setNote('Xabar ' + cfg.maxLength + ' belgidan oshmasin.');
      return;
    }
    input.value = '';
    send(text, null);
  });

  q('[data-hide]').addEventListener('click', function () { toggle(false); });
  q('[data-launcher]').addEventListener('click', function () { toggle(); });

  q('[data-end]').addEventListener('click', async function () {
    if (!state.hasConversation) return;
    if (!window.confirm('Suhbatni yopmoqchimisiz?')) return;
    try {
      await api('POST', cfg.urls.close, {});
      resetThread();
      setUnread(0);
      nameWrap.classList.toggle('hidden', !cfg.guest);
      setNote('Suhbat yopildi.');
    } catch (e) {
      setNote('Suhbatni yopib bo‘lmadi.');
    }
  });

  window.addEventListener('support-chat:toggle', function (e) {
    var open = e && e.detail && typeof e.detail.open === 'boolean' ? e.detail.open : undefined;
    if (e && e.detail) e.detail.handled = true;
    toggle(open);
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(state.timer);
    } else {
      poll().then(schedule);
    }
  });

  load(false).then(schedule).catch(schedule);
})();
