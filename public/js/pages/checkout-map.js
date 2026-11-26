// Checkout: Leaflet xaritasi + teskari geokodlash.
// Brauzer faqat bizning /checkout/geocode/* endpointlarimiz bilan gaplashadi (provayder serverda almashtiriladi).
(function () {
  'use strict';

  var mapEl = document.getElementById('delivery-map');
  var form = document.getElementById('checkout-form');
  if (!mapEl || !form) return;

  var $ = function (id) { return document.getElementById(id); };
  var d = mapEl.dataset;
  var latInput = $('delivery_lat');
  var lngInput = $('delivery_lng');
  var statusEl = $('map-status');
  var loaderEl = $('map-loading');
  var searchInput = $('map-search');
  var searchBtn = $('map-search-btn');
  var resultsEl = $('map-results');
  var locateBtn = $('map-locate');
  var applyBtn = $('map-apply');
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  // geokodlash kaliti -> forma maydoni id si
  var FIELDS = {
    country: 'country', country_code: 'country_code', region: 'region', city: 'city',
    district: 'district', street: 'address', house_number: 'house_number', postcode: 'postcode'
  };
  var LABELS = {
    country: 'Country', region: 'Region', city: 'City', district: 'District',
    address: 'Street address', house_number: 'House number', postcode: 'Postal code'
  };

  var DEBOUNCE_MS = 700;   // pin to'xtagandan keyin kutish (sudrash paytida so'rov yuborilmaydi)
  var MIN_GAP_MS = 1100;   // ketma-ket so'rovlar orasida kamida (Nominatim: ~1 so'rov/soniya)

  function setStatus(message, tone) {
    statusEl.textContent = message || '';
    statusEl.className = 'min-h-[1.25rem] text-xs font-mono ' +
      (tone === 'error' ? 'text-red-400' : tone === 'ok' ? 'text-primary' : 'text-text-muted');
  }

  function setLoading(on) {
    loaderEl.classList.toggle('hidden', !on);
    mapEl.setAttribute('aria-busy', on ? 'true' : 'false');
  }

  function clean(value) {
    return typeof value === 'string' ? value.trim() : '';
  }

  if (typeof L === 'undefined') {
    // Leaflet yuklanmadi (tarmoq/CDN): forma qo'lda to'ldirish bilan ishlashda davom etadi.
    $('map-block').classList.add('hidden');
    return;
  }

  // ---- xarita -------------------------------------------------------------
  var center = [parseFloat(d.centerLat) || 41.2995, parseFloat(d.centerLng) || 69.2401];
  var map = L.map(mapEl, {
    center: center,
    zoom: parseInt(d.zoom, 10) || 12,
    scrollWheelZoom: false // sahifani aylantirayotganda tasodifan zoom bo'lib ketmasin
  });

  L.tileLayer(d.tiles || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: d.attribution || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  map.on('focus click', function () { map.scrollWheelZoom.enable(); });
  mapEl.addEventListener('mouseleave', function () { map.scrollWheelZoom.disable(); });

  var pinIcon = L.divIcon({
    className: 'fm-pin',
    iconSize: [34, 44],
    iconAnchor: [17, 43],
    html: '<svg width="34" height="44" viewBox="0 0 34 44" aria-hidden="true">' +
      '<path d="M17 1C8.7 1 2 7.5 2 15.6 2 26 17 43 17 43s15-17 15-27.4C32 7.5 25.3 1 17 1z" fill="#00f59b" stroke="#0b1210" stroke-width="2"/>' +
      '<circle cx="17" cy="15.5" r="5.5" fill="#0b1210"/></svg>'
  });

  // Yashirin/dinamik konteynerda ham to'g'ri chizilishi uchun o'lcham o'zgarishini kuzatamiz.
  function refresh() { map.invalidateSize({ pan: false }); }
  var resizeTimer = null;
  function refreshSoon() { clearTimeout(resizeTimer); resizeTimer = setTimeout(refresh, 120); }
  window.addEventListener('load', refresh);
  window.addEventListener('resize', refreshSoon);
  window.addEventListener('orientationchange', refreshSoon);
  document.addEventListener('checkout-map:refresh', refresh);
  if ('ResizeObserver' in window) new ResizeObserver(refreshSoon).observe(mapEl);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      if (entries.some(function (e) { return e.isIntersecting; })) refresh();
    }).observe(mapEl);
  }
  setTimeout(refresh, 250);

  // ---- maydonlar: qo'lda yozilganini himoya qilish -------------------------
  var dirty = new Set(); // foydalanuvchi o'zi yozgan (yoki serverdan oldindan to'ldirilgan) maydonlar
  var auto = new Set();  // xaritadan avtomatik to'ldirilgan maydonlar
  var pending = null;    // oxirgi geokodlash natijasi ("Use map address" tugmasi uchun")

  Object.keys(FIELDS).forEach(function (key) {
    var id = FIELDS[key];
    var el = $(id);
    if (!el || el.type === 'hidden') return;
    if (el.value.trim() !== '') dirty.add(id);
    el.addEventListener('input', function (e) {
      if (!e.isTrusted) return;
      if (el.value.trim() === '') { dirty.delete(id); auto.delete(id); } else { dirty.add(id); auto.delete(id); }
      if (id === 'country') $('country_code').value = ''; // qo'lda yozilgan davlat eski kod bilan qolmasin
    });
  });

  function flash(el) {
    el.classList.remove('fm-flash');
    void el.offsetWidth;
    el.classList.add('fm-flash');
  }

  function applyAddress(addr, force) {
    pending = addr;
    var kept = [];
    var missing = [];

    Object.keys(FIELDS).forEach(function (key) {
      var id = FIELDS[key];
      var el = $(id);
      if (!el) return;
      var next = clean(addr[key]);

      if (key === 'country_code') {
        if (force || !dirty.has('country')) el.value = next.toUpperCase();
        return;
      }

      var locked = dirty.has(id) && !force;

      if (locked) {
        if (next && el.value.trim().toLowerCase() !== next.toLowerCase()) kept.push(LABELS[id]);
        return;
      }

      if (next) {
        if (el.value !== next) { el.value = next; flash(el); }
        auto.add(id);
        dirty.delete(id);
      } else if (auto.has(id)) {
        // oldingi pin dan qolgan eskirgan qiymat (masalan uy raqami) yangi joyda noto'g'ri bo'ladi
        el.value = '';
        auto.delete(id);
      }
    });

    ['address', 'house_number'].forEach(function (id) {
      var el = $(id);
      if (el && el.value.trim() === '') missing.push(LABELS[id]);
    });

    var message = kept.length
      ? 'Pin updated. We kept what you typed in: ' + kept.join(', ') + '.'
      : 'Address filled from the map. You can edit any field.';
    if (missing.length) message += ' Please check: ' + missing.join(', ') + '.';

    applyBtn.classList.toggle('hidden', kept.length === 0);
    setStatus(message, 'ok');
  }

  applyBtn.addEventListener('click', function () {
    if (pending) applyAddress(pending, true);
  });

  // ---- pin -----------------------------------------------------------------
  var marker = null;

  function setPoint(lat, lng, opts) {
    opts = opts || {};
    lat = Math.max(-90, Math.min(90, lat));
    lng = ((lng + 540) % 360) - 180; // Leaflet dunyoni takrorlaydi: -180..180 ga qaytaramiz

    if (!marker) {
      marker = L.marker([lat, lng], {
        draggable: true, icon: pinIcon, keyboard: true,
        title: 'Delivery location (drag to adjust)', alt: 'Delivery location'
      }).addTo(map);
      marker.on('dragend', function () {
        var p = marker.getLatLng();
        setPoint(p.lat, p.lng, { reverse: true });
      });
    } else {
      marker.setLatLng([lat, lng]);
    }

    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);

    if (opts.view) map.setView([lat, lng], Math.max(map.getZoom(), opts.zoom || 16));
    if (opts.reverse) scheduleReverse(lat, lng);
  }

  map.on('click', function (e) {
    var p = e.latlng.wrap();
    setPoint(p.lat, p.lng, { reverse: true });
  });

  // ---- teskari geokodlash (debounce + minimal oraliq + eskirgan javobni tashlash) ---
  var timer = null;
  var lastAt = 0;
  var seq = 0;
  var controller = null;
  var retried = false;

  function post(url, payload, signal) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      signal: signal,
      headers: {
        'Content-Type': 'application/json', 'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });
  }

  function scheduleReverse(lat, lng) {
    clearTimeout(timer);
    if (controller) controller.abort();
    retried = false;
    seq += 1;
    setLoading(true);
    setStatus('Looking up the address…');
    var wait = Math.max(DEBOUNCE_MS, lastAt + MIN_GAP_MS - Date.now());
    timer = setTimeout(function () { reverse(lat, lng); }, wait);
  }

  function explain(res) {
    if (res.status === 404) return res.json().then(function (b) { return b.message; }, function () { return null; });
    if (res.status === 422) return Promise.resolve('That location could not be used. Please pick another spot.');
    return Promise.resolve(null);
  }

  function reverse(lat, lng) {
    var mine = ++seq;
    lastAt = Date.now();
    controller = new AbortController();

    post(d.reverseUrl, { lat: lat, lng: lng }, controller.signal)
      .then(function (res) {
        if (mine !== seq) return;

        if (res.ok) {
          return res.json().then(function (body) {
            if (mine !== seq) return;
            applyAddress(body.address || {}, false);
            setLoading(false);
          });
        }

        if (res.status === 429 && !retried) {
          retried = true;
          setStatus('The address service is busy, trying again…');
          timer = setTimeout(function () { reverse(lat, lng); }, 1600);
          return;
        }

        return explain(res).then(function (message) {
          if (mine !== seq) return;
          setStatus(message || 'Address lookup is unavailable right now. Your pin is saved; please fill in the address below.', 'error');
          setLoading(false);
        });
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        if (mine !== seq) return;
        setStatus('Address lookup is unavailable right now. Your pin is saved; please fill in the address below.', 'error');
        setLoading(false);
      });
  }

  // ---- manzil qidirish (faqat tugma / Enter bilan, harf yozilganda emas) ---------
  var lastSearchAt = 0;

  function runSearch() {
    var q = searchInput.value.trim();
    resultsEl.classList.add('hidden');
    resultsEl.innerHTML = '';

    if (q.length < 3) { setStatus('Type at least 3 characters to search.', 'error'); return; }
    if (Date.now() - lastSearchAt < MIN_GAP_MS) return;
    lastSearchAt = Date.now();

    searchBtn.disabled = true;
    setLoading(true);
    setStatus('Searching…');

    post(d.searchUrl, { q: q })
      .then(function (res) {
        if (!res.ok) {
          return res.json().catch(function () { return {}; }).then(function (b) {
            throw new Error(res.status === 429 ? 'The address service is busy. Please try again in a moment.' : (b.message || 'Search is unavailable right now.'));
          });
        }
        return res.json();
      })
      .then(function (body) {
        var results = body.results || [];
        if (!results.length) { setStatus('No places found. Try a different spelling, or tap the map.', 'error'); return; }

        results.forEach(function (r) {
          var li = document.createElement('li');
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'w-full px-4 py-2.5 text-left text-sm text-text-crisp transition-colors hover:bg-primary/10 focus:bg-primary/10 focus:outline-none';
          b.textContent = r.display_name || [r.street, r.city].filter(Boolean).join(', ') || 'Unnamed place';
          b.addEventListener('click', function () {
            resultsEl.classList.add('hidden');
            setPoint(r.lat, r.lng, { view: true, zoom: 17 });
            applyAddress(r, false); // qidiruv natijasi manzilni o'zi olib keladi: qo'shimcha so'rov kerak emas
          });
          li.appendChild(b);
          resultsEl.appendChild(li);
        });
        resultsEl.classList.remove('hidden');
        setStatus('Pick the best match, or tap the map to fine-tune the pin.');
      })
      .catch(function (err) {
        setStatus(err.message || 'Search is unavailable right now.', 'error');
      })
      .then(function () {
        searchBtn.disabled = false;
        setLoading(false);
      });
  }

  searchBtn.addEventListener('click', runSearch);
  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); runSearch(); } // Enter buyurtmani yubormasin
  });

  // ---- mening joylashuvim (faqat tugma bosilganda so'raladi) ----------------------
  if (locateBtn && 'geolocation' in navigator) {
    locateBtn.classList.remove('hidden');
    locateBtn.addEventListener('click', function () {
      setStatus('Finding your location…');
      navigator.geolocation.getCurrentPosition(
        function (pos) { setPoint(pos.coords.latitude, pos.coords.longitude, { view: true, zoom: 17, reverse: true }); },
        function () { setStatus('We could not get your location. Tap the map or search for your address instead.', 'error'); },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
      );
    });
  }

  // ---- boshlang'ich holat -------------------------------------------------------
  var startLat = parseFloat(latInput.value || d.pinLat);
  var startLng = parseFloat(lngInput.value || d.pinLng);

  if (isFinite(startLat) && isFinite(startLng)) {
    setPoint(startLat, startLng, { view: true, zoom: 16 });
    var addressEmpty = $('address').value.trim() === '' && $('city').value.trim() === '';
    if (d.pinSource === 'saved' && addressEmpty) {
      scheduleReverse(startLat, startLng); // oldingi buyurtma joyi: manzilni bir marta to'ldiramiz
    } else {
      setStatus('Drag the pin or tap the map to change the location.');
    }
  } else {
    setStatus('Tap the map to drop a pin, or search for your address. You can also fill in the address below by hand.');
  }

  window.checkoutMap = { refresh: refresh, map: map };
})();
