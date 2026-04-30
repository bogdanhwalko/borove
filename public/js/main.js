/* Village site — main.js */
(function () {
  'use strict';

  /* ── helpers ─────────────────────────────────── */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function formatDate(d) {
    var months = ['січня','лютого','березня','квітня','травня','червня',
                  'липня','серпня','вересня','жовтня','листопада','грудня'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }

  function fmtIsoDate(iso) {
    if (!iso) return '';
    var parts = iso.split('-');
    var monthsShort = ['','січ.','лют.','бер.','квіт.','трав.','черв.',
                       'лип.','серп.','вер.','жовт.','лист.','груд.'];
    var m = parseInt(parts[1], 10);
    return parseInt(parts[2], 10) + ' ' + (monthsShort[m] || '') + ' ' + parts[0];
  }

  function fmtIsoTime(t) {
    return t ? t.substring(0, 5) : '';
  }

  function setSubmitLoading(btn, loading) {
    if (loading) {
      btn._origText = btn.innerHTML;
      btn.innerHTML = '<span class="btn-spinner"></span>Публікую...';
      btn.disabled = true;
    } else {
      btn.innerHTML = btn._origText || btn.innerHTML;
      btn.disabled = false;
    }
  }

  function showToast(msg) {
    var t = $('#toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 3500);
  }

  /* ── mobile nav drawer ───────────────────────── */
  function initNav() {
    var btn = $('#navToggle');
    var menu = $('#siteMenu');
    if (!btn || !menu) return;

    var overlay = document.createElement('div');
    overlay.className = 'nav-overlay';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.appendChild(overlay);

    var drawerHead = document.createElement('div');
    drawerHead.className = 'drawer-head';
    drawerHead.innerHTML =
      '<span class="drawer-logo">&#127807; Борове</span>' +
      '<button class="drawer-close" aria-label="Закрити меню">&#10005;</button>';
    menu.insertBefore(drawerHead, menu.firstChild);

    function openDrawer() {
      menu.classList.add('open');
      overlay.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
      btn.innerHTML = '&#10005;';
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      menu.classList.remove('open');
      overlay.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      btn.innerHTML = '&#9776;';
      document.body.style.overflow = '';
    }

    btn.addEventListener('click', function () {
      menu.classList.contains('open') ? closeDrawer() : openDrawer();
    });

    overlay.addEventListener('click', closeDrawer);
    drawerHead.querySelector('.drawer-close').addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
      if ((e.key === 'Escape' || e.keyCode === 27) && menu.classList.contains('open')) {
        closeDrawer();
      }
    });

    $$('a', menu).forEach(function (a) {
      a.addEventListener('click', function () { closeDrawer(); });
    });
  }

  /* ── active nav link ─────────────────────────── */
  function initActiveLink() {
    var path = location.pathname.replace(/\/$/, '') || '/';
    $$('.site-nav a').forEach(function (a) {
      var href = (a.getAttribute('href') || '').split('?')[0].replace(/\/$/, '') || '/';
      if (href === path) a.classList.add('active');
    });
  }

  /* ── date in header ──────────────────────────── */
  function initHeaderDate() {
    var el = $('#headerDate');
    if (el) el.textContent = formatDate(new Date());
  }

  /* ── photo URL helper ────────────────────────── */
  function photoUrl(photo, w, h) {
    if (photo.file_path) return '/storage/' + photo.file_path;
    return 'https://picsum.photos/seed/' + photo.image_seed + '/' + w + '/' + h;
  }

  function albumCoverUrl(album, w, h) {
    if (album.cover_path) return '/storage/' + album.cover_path;
    return 'https://picsum.photos/seed/' + encodeURIComponent(album.cover_seed || '') + '/' + w + '/' + h;
  }

  function articleImg(a, w, h) {
    if (a && a.image_path) return '/storage/' + a.image_path;
    var seed = (a && (a.image_seed || a.slug)) || 'article';
    return 'https://picsum.photos/seed/' + encodeURIComponent(seed) + '/' + w + '/' + h;
  }

  /* CSP-friendly: one global capture-phase error listener instead of inline onerror */
  function initImgErrorFallback() {
    document.addEventListener('error', function (e) {
      var t = e.target;
      if (!t || t.tagName !== 'IMG') return;
      var parent = t.parentNode;
      if (parent && parent.classList && parent.classList.contains('card-img')) {
        t.style.display = 'none';
        parent.classList.add('card-img--no-img');
      }
    }, true);
  }

  /* ── simple XSS guard ────────────────────────── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function pluralUa(n) {
    var mod10 = n % 10, mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return '';
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'и';
    return 'ів';
  }

  function formatUaPhone(raw) {
    var digits = String(raw || '').replace(/\D/g, '');
    if (digits.length === 12 && digits.indexOf('380') === 0) digits = '0' + digits.substring(3);
    if (digits.length === 9) digits = '0' + digits;
    if (digits.length === 10 && digits[0] === '0') {
      return '+380 ' + digits.substring(1, 3) + ' ' + digits.substring(3, 6) + ' ' + digits.substring(6, 8) + ' ' + digits.substring(8);
    }
    return raw ? String(raw) : '';
  }

  /* ── auth / API ────────────────────────────────── */
  var TOKEN_KEY = 'boroveToken';
  var USER_KEY  = 'boroveUser';

  function getToken() { return localStorage.getItem(TOKEN_KEY) || null; }

  function getCachedUser() {
    try { return JSON.parse(localStorage.getItem(USER_KEY) || 'null'); } catch (e) { return null; }
  }

  function saveSession(user, token) {
    localStorage.setItem(USER_KEY,  JSON.stringify(user));
    localStorage.setItem(TOKEN_KEY, token);
  }

  function clearSession() {
    localStorage.removeItem(USER_KEY);
    localStorage.removeItem(TOKEN_KEY);
  }

  function apiUpload(method, path, formData, token) {
    var headers = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return fetch('/api' + path, { method: method, headers: headers, body: formData });
  }

  function apiFetch(method, path, body, token) {
    var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return fetch('/api' + path, {
      method:  method,
      headers: headers,
      body:    body ? JSON.stringify(body) : undefined
    });
  }

  /* ── ANNOUNCEMENTS ──────────────────────────── */
  function typeLabel(type) {
    var map = { urgent: 'Терміново', info: 'Інформація', event: 'Подія', services: 'Послуги' };
    return map[type] || type;
  }

  function renderAnnCard(ann, isAdmin) {
    var tag = '<span class="ann-tag ' + ann.type + '">' + typeLabel(ann.type) + '</span>';
    var contact = ann.contact ? '<div class="ann-card-contact">&#128222; ' + escHtml(ann.contact) + '</div>' : '';
    var date = fmtIsoDate(ann.created_at ? ann.created_at.substring(0, 10) : '');
    var img = ann.image_path
      ? '<img class="ann-card-img" src="/storage/' + ann.image_path + '" alt="' + escHtml(ann.title) + '" loading="lazy">'
      : '';
    var delBtn = isAdmin
      ? '<button class="card-admin-del" data-id="' + ann.id + '" title="Видалити">&#128465;</button>'
      : '';
    return '<div class="ann-card ' + ann.type + '" data-type="' + ann.type + '">' +
      '<div class="ann-card-header">' +
        '<div>' + tag + '<h3>' + escHtml(ann.title) + '</h3></div>' +
        '<div class="ann-card-header-right">' +
          (date ? '<span class="ann-card-date">&#128197; ' + date + '</span>' : '') +
          delBtn +
        '</div>' +
      '</div>' +
      img +
      '<p>' + escHtml(ann.body) + '</p>' +
      contact +
    '</div>';
  }

  function initAnnouncementsPage() {
    var list = $('#annList');
    if (!list) return;

    var toggleBtn = $('#annFormToggle');
    var formSection = $('#annFormSection');
    if (toggleBtn && formSection) {
      toggleBtn.addEventListener('click', function () {
        var isOpen = formSection.classList.toggle('open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    }

    var formWrap = $('#annFormWrap');
    var user = getCachedUser();
    var token = getToken();
    var ANN_PER_PAGE = 6;
    var annPage = 1;
    var annFilter = 'all';
    var isAdmin = !!(user && user.is_admin);

    function showAnnAuthNotice() {
      if (!formWrap) return;
      formWrap.innerHTML =
        '<div class="form-auth-notice">' +
          '<div class="form-auth-icon">&#128274;</div>' +
          '<p>Щоб додати оголошення, потрібно <a href="/auth">увійти</a></p>' +
        '</div>';
    }

    function renderAnnProfileInfo(profile) {
      if (!formWrap || !profile) return;
      var infoBox = document.getElementById('annProfileInfo');
      var nameEl = document.getElementById('annProfileName');
      var phoneEl = document.getElementById('annProfilePhone');
      var fullName = [profile.last_name, profile.first_name].filter(Boolean).join(' ');
      var nameDisplay;
      if (fullName) {
        nameDisplay = profile.nickname ? fullName + ' (' + profile.nickname + ')' : fullName;
      } else {
        nameDisplay = profile.nickname || 'Користувач';
      }
      var phoneDisplay = formatUaPhone(profile.phone);
      if (nameEl) nameEl.textContent = nameDisplay;
      if (phoneEl) phoneEl.textContent = phoneDisplay || '—';
      if (infoBox)   infoBox.style.display = '';
    }

    if (!token) {
      showAnnAuthNotice();
    } else if (user) {
      renderAnnProfileInfo(user);
    }

    function loadPage() {
      var pag = document.getElementById('annPagination');
      list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Завантаження...</p></div>';
      if (pag) pag.innerHTML = '';

      var qs = '?per_page=' + ANN_PER_PAGE + '&page=' + annPage;
      if (annFilter !== 'all') qs += '&type=' + annFilter;

      apiFetch('GET', '/announcements' + qs)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          var items = resp.data || [];
          var currentPage = resp.current_page || 1;
          var lastPage = resp.last_page || 1;

          if (!items.length) {
            list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Оголошень не знайдено</p></div>';
            return;
          }

          list.innerHTML = items.map(function (a) { return renderAnnCard(a, isAdmin); }).join('');

          if (isAdmin) {
            list.querySelectorAll('.card-admin-del').forEach(function (btn) {
              btn.addEventListener('click', function () {
                if (!confirm('Видалити це оголошення?')) return;
                apiFetch('DELETE', '/admin/announcements/' + btn.dataset.id, null, getToken())
                  .then(function (res) { if (!res.ok) throw new Error(); })
                  .then(function () {
                    showToast('✓ Оголошення видалено');
                    if (currentPage > 1 && items.length === 1) annPage--;
                    loadPage();
                  })
                  .catch(function () { showToast('Помилка видалення'); });
              });
            });
          }

          if (!pag || lastPage <= 1) { if (pag) pag.innerHTML = ''; return; }
          pag.innerHTML =
            '<button class="page-btn"' + (currentPage <= 1 ? ' disabled' : '') + '>&#8592; Назад</button>' +
            '<span class="page-info">' + currentPage + ' / ' + lastPage + '</span>' +
            '<button class="page-btn"' + (currentPage >= lastPage ? ' disabled' : '') + '>Вперід &#8594;</button>';
          var btns = pag.querySelectorAll('.page-btn');
          btns[0].addEventListener('click', function () { annPage--; loadPage(); list.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
          btns[1].addEventListener('click', function () { annPage++; loadPage(); list.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
        })
        .catch(function () {
          list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Не вдалося завантажити оголошення</p></div>';
        });
    }

    if (token) {
      apiFetch('GET', '/me', null, token)
        .then(function (res) {
          if (res.status === 401) throw new Error('auth');
          if (!res.ok) throw new Error('profile');
          return res.json();
        })
        .then(function (profile) {
          var wasAdmin = isAdmin;
          user = profile;
          isAdmin = !!(user && user.is_admin);
          saveSession(user, token);
          renderAnnProfileInfo(user);
          if (wasAdmin !== isAdmin) loadPage();
        })
        .catch(function (err) {
          if (err.message === 'auth') {
            clearSession();
            showAnnAuthNotice();
          }
        });
    }

    loadPage();

    $$('.filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        annFilter = btn.dataset.filter;
        annPage = 1;
        loadPage();
      });
    });

    var imgInput = document.getElementById('annImage');
    var imgLabel = document.getElementById('annImageLabel');
    var imgName  = document.getElementById('annImageName');
    if (imgInput && imgLabel && imgName) {
      imgInput.addEventListener('change', function () {
        if (imgInput.files[0]) {
          imgName.textContent = imgInput.files[0].name;
          imgLabel.classList.add('has-file');
        } else {
          imgName.textContent = 'Вибрати фото';
          imgLabel.classList.remove('has-file');
        }
      });
    }

    var form = $('#addAnnForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var title   = $('#annTitle').value.trim();
        var body    = $('#annBody').value.trim();
        var type    = $('#annType').value;
        if (!title) { showToast('Вкажіть заголовок оголошення'); return; }
        if (!body)  { showToast('Введіть текст оголошення'); return; }

        var token = getToken();
        if (!token) { showToast('Увійдіть, щоб додати оголошення'); return; }
        var currentUser = getCachedUser();
        if (!currentUser || !currentUser.phone) {
          showToast('Заповніть телефон у профілі перед створенням оголошення');
          return;
        }

        var btn = form.querySelector('button[type="submit"]');
        setSubmitLoading(btn, true);

        var fd = new FormData();
        fd.append('type',  type);
        fd.append('title', title);
        fd.append('body',  body);
        var imgInput = document.getElementById('annImage');
        if (imgInput && imgInput.files[0]) fd.append('image', imgInput.files[0]);

        apiUpload('POST', '/announcements', fd, token)
          .then(function (res) {
            if (res.status === 401) throw new Error('auth');
            if (!res.ok) return res.json().then(function (d) {
              throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'error'));
            });
            return res.json();
          })
          .then(function () {
            $$('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
            var allBtn = $('[data-filter="all"]');
            if (allBtn) allBtn.classList.add('active');
            annFilter = 'all';
            annPage = 1;
            form.reset();
            if (imgName) { imgName.textContent = 'Вибрати фото'; }
            if (imgLabel) { imgLabel.classList.remove('has-file'); }
            showToast('✓ Оголошення додано!');
            loadPage();
            list.scrollIntoView({ behavior: 'smooth', block: 'start' });
          })
          .catch(function (err) {
            if (err.message === 'auth') {
              showToast('Сесія закінчилась — увійдіть знову');
            } else {
              showToast(err.message || 'Помилка. Спробуйте ще раз.');
            }
          })
          .finally(function () { setSubmitLoading(btn, false); });
      });
    }
  }

  /* ── RIDES ────────────────────────────────────── */
  function seatsBadge(seats) {
    if (seats === 0) return '<span class="ride-badge full">Місць немає</span>';
    if (seats === 1) return '<span class="ride-badge seats-1">1 місце</span>';
    return '<span class="ride-badge">' + seats + ' місця</span>';
  }

  function renderRideCard(r, isAdmin, myUserId) {
    var comment = r.comment ? '<div class="ride-card-comment">&#8220;' + escHtml(r.comment) + '&#8221;</div>' : '';
    var date = r.ride_date ? fmtIsoDate(r.ride_date) : '';
    var time = r.ride_time ? fmtIsoTime(r.ride_time) : '';
    var isOwn = myUserId && r.user_id === myUserId;
    var delBtn = isAdmin
      ? '<button class="card-admin-del" data-id="' + r.id + '" title="Видалити">&#128465;</button>'
      : '';

    var ownerActions = '';
    if (isOwn) {
      var fullBtn = r.seats > 0
        ? '<button type="button" class="btn-ride-full" data-id="' + r.id + '">&#128683; Місць немає</button>'
        : '';
      ownerActions =
        '<div class="ride-card-owner-actions">' +
          '<span class="ride-card-own-tag">&#127775; Ваша поїздка</span>' +
          fullBtn +
        '</div>';
    }

    return '<div class="ride-card' + (isOwn ? ' ride-card--own' : '') + '">' +
      '<div class="ride-card-icon">&#128664;</div>' +
      '<div class="ride-card-body">' +
        '<div class="ride-card-top">' +
          seatsBadge(r.seats) +
          delBtn +
        '</div>' +
        '<div class="ride-card-route">' + escHtml(r.from_place) + '<span class="sep">&#8594;</span>' + escHtml(r.to_place) + '</div>' +
        '<div class="ride-card-meta">' +
          '<span class="meta-item">&#128197; ' + date + '</span>' +
          '<span class="meta-item">&#128336; ' + time + '</span>' +
          '<span class="meta-item">&#128100; ' + escHtml(r.name) + '</span>' +
        '</div>' +
        comment +
        '<div class="ride-card-contact">&#128222; ' + escHtml(r.contact) + '</div>' +
        ownerActions +
      '</div>' +
    '</div>';
  }

  function initRidesPage() {
    var list = $('#ridesList');
    if (!list) return;

    var toggleBtn = $('#rideFormToggle');
    var formSection = $('#rideFormSection');
    if (toggleBtn && formSection) {
      toggleBtn.addEventListener('click', function () {
        var isOpen = formSection.classList.toggle('open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    }

    var formWrap = $('#rideFormWrap');
    var user = getCachedUser();
    if (formWrap && !user) {
      formWrap.innerHTML =
        '<div class="form-auth-notice">' +
          '<div class="form-auth-icon">&#128274;</div>' +
          '<p>Щоб додати попутку, потрібно <a href="/auth">увійти</a></p>' +
        '</div>';
    } else if (formWrap && user) {
      var infoBox = document.getElementById('rideProfileInfo');
      var nameEl  = document.getElementById('rideProfileName');
      var phoneEl = document.getElementById('rideProfilePhone');
      var fullName = [user.last_name, user.first_name].filter(Boolean).join(' ');
      var displayName;
      if (fullName) {
        displayName = user.nickname ? fullName + ' (' + user.nickname + ')' : fullName;
      } else {
        displayName = user.nickname || 'Користувач';
      }
      var phoneDisplay = formatUaPhone(user.phone);
      if (nameEl)  nameEl.textContent  = displayName;
      if (phoneEl) phoneEl.textContent = phoneDisplay || '—';
      if (infoBox) infoBox.style.display = '';
    }

    var RIDES_PER_PAGE = 5;
    var ridesPage = 1;
    var allRides = [];
    var ridesFilter = 'all';
    var isAdminRides = !!(user && user.is_admin);
    var myUserId = user ? user.id : null;

    // Show "Мої поїздки" filter only for logged-in users
    var ownFilterBtn = document.getElementById('ridesFilterOwn');
    if (ownFilterBtn && myUserId) ownFilterBtn.style.display = '';

    $$('.rides-filters .filter-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.rides-filters .filter-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        ridesFilter = btn.dataset.ridesFilter;
        ridesPage = 1;
        renderPage();
      });
    });

    function getVisibleRides() {
      if (ridesFilter === 'own' && myUserId) {
        return allRides.filter(function (r) { return r.user_id === myUserId; });
      }
      return allRides;
    }

    function applySeatsUpdate(rideId, newSeats) {
      var token = getToken();
      if (!token) { showToast('Сесія закінчилась — увійдіть знову'); return; }
      apiFetch('PATCH', '/rides/' + rideId + '/seats', { seats: newSeats }, token)
        .then(function (res) {
          if (!res.ok) return res.json().then(function (d) { throw new Error(d.message || 'error'); });
          return res.json();
        })
        .then(function (updated) {
          var idx = allRides.findIndex(function (r) { return r.id === rideId; });
          if (idx >= 0) allRides[idx].seats = updated.seats;
          renderPage();
          showToast(newSeats === 0 ? 'Поїздку позначено як заповнену' : '✓ Кількість місць оновлено');
        })
        .catch(function () { showToast('Помилка оновлення'); });
    }

    function renderPage() {
      var visible = getVisibleRides();
      var total = visible.length;
      var pages = Math.max(1, Math.ceil(total / RIDES_PER_PAGE));
      if (ridesPage > pages) ridesPage = pages;

      var pag = document.getElementById('ridesPagination');

      if (!total) {
        var emptyMsg = ridesFilter === 'own'
          ? 'У вас ще немає поїздок'
          : 'Попуток ще немає';
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128664;</div><p>' + emptyMsg + '</p></div>';
        if (pag) pag.innerHTML = '';
        return;
      }

      list.innerHTML = visible.slice((ridesPage - 1) * RIDES_PER_PAGE, ridesPage * RIDES_PER_PAGE)
        .map(function (r) { return renderRideCard(r, isAdminRides, myUserId); }).join('');

      if (isAdminRides) {
        list.querySelectorAll('.card-admin-del').forEach(function (btn) {
          btn.addEventListener('click', function () {
            if (!confirm('Видалити цю попутку?')) return;
            apiFetch('DELETE', '/admin/rides/' + btn.dataset.id, null, getToken())
              .then(function (res) { if (!res.ok) throw new Error(); })
              .then(function () {
                allRides = allRides.filter(function (r) { return r.id !== parseInt(btn.dataset.id, 10); });
                renderPage();
                showToast('✓ Попутку видалено');
              })
              .catch(function () { showToast('Помилка видалення'); });
          });
        });
      }

      // Owner action: "no seats" button
      list.querySelectorAll('.btn-ride-full').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Позначити цю поїздку як заповнену (місць немає)?')) return;
          var id = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          applySeatsUpdate(id, 0);
        });
      });

      if (!pag) return;
      if (pages <= 1) { pag.innerHTML = ''; return; }
      pag.innerHTML =
        '<button class="page-btn"' + (ridesPage <= 1 ? ' disabled' : '') + '>&#8592; Назад</button>' +
        '<span class="page-info">' + ridesPage + ' / ' + pages + '</span>' +
        '<button class="page-btn"' + (ridesPage >= pages ? ' disabled' : '') + '>Вперід &#8594;</button>';
      var btns = pag.querySelectorAll('.page-btn');
      btns[0].addEventListener('click', function () { ridesPage--; renderPage(); list.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
      btns[1].addEventListener('click', function () { ridesPage++; renderPage(); list.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    }

    function render() {
      ridesPage = 1;
      renderPage();
    }

    list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128664;</div><p>Завантаження...</p></div>';

    apiFetch('GET', '/rides')
      .then(function (res) { return res.json(); })
      .then(function (data) { allRides = data; render(); })
      .catch(function () {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128664;</div><p>Не вдалося завантажити попутки</p></div>';
      });

    var form = $('#addRideForm');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var from    = $('#rideFrom').value.trim();
        var to      = $('#rideTo').value.trim();
        var date    = $('#rideDate').value;
        var time    = $('#rideTime').value;
        var seats   = parseInt($('#rideSeats').value, 10);
        var comment = $('#rideComment').value.trim();

        if (!from) { showToast("Вкажіть звідки їдете"); return; }
        if (!to)   { showToast("Вкажіть куди їдете"); return; }
        if (!date) { showToast("Оберіть дату поїздки"); return; }
        if (!time) { showToast("Вкажіть час відправлення"); return; }

        var token = getToken();
        if (!token) { showToast('Увійдіть, щоб додати попутку'); return; }
        var currentUser = getCachedUser();
        if (!currentUser || !currentUser.phone) {
          showToast('Заповніть телефон у профілі перед створенням поїздки');
          return;
        }

        var btn = form.querySelector('button[type="submit"]');
        setSubmitLoading(btn, true);

        apiFetch('POST', '/rides', {
          from_place: from, to_place: to,
          ride_date: date, ride_time: time,
          seats: seats,
          comment: comment || null
        }, token)
          .then(function (res) {
            if (res.status === 401) throw new Error('auth');
            if (!res.ok) return res.json().then(function (d) {
              throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'error'));
            });
            return res.json();
          })
          .then(function (ride) {
            allRides.unshift(ride);
            render();
            form.reset();
            showToast('✓ Попутку додано!');
            list.scrollIntoView({ behavior: 'smooth', block: 'start' });
          })
          .catch(function (err) {
            if (err.message === 'auth') {
              showToast('Сесія закінчилась — увійдіть знову');
            } else {
              showToast(err.message || 'Помилка. Спробуйте ще раз.');
            }
          })
          .finally(function () { setSubmitLoading(btn, false); });
      });
    }
  }

  /* ── sidebar widgets on index ────────────────── */
  function initSidebarWidgets() {
    var sideAnnList = $('#sideAnnList');
    if (sideAnnList) {
      apiFetch('GET', '/announcements?per_page=4')
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          var anns = resp.data || [];
          if (!anns.length) {
            sideAnnList.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Немає оголошень</p></div>';
            return;
          }
          sideAnnList.innerHTML = anns.map(function (a) {
            var date = fmtIsoDate(a.created_at ? a.created_at.substring(0, 10) : '');
            return '<div class="announce-item">' +
              '<span class="ann-tag ' + a.type + '">' + typeLabel(a.type) + '</span>' +
              '<p>' + escHtml(a.title) + '</p>' +
              (date ? '<span class="ann-date">&#128197; ' + date + '</span>' : '') +
            '</div>';
          }).join('');
        })
        .catch(function () {
          sideAnnList.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>—</p></div>';
        });
    }

    var indexRideList = $('#indexRideList');
    if (indexRideList) {
      apiFetch('GET', '/rides')
        .then(function (res) { return res.json(); })
        .then(function (data) {
          var rs = data.slice(0, 3);
          if (!rs.length) {
            indexRideList.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128664;</div><p>Немає попуток</p></div>';
            return;
          }
          indexRideList.innerHTML = '<div class="index-rides-list">' +
            rs.map(function (r) {
              var date = r.ride_date ? fmtIsoDate(r.ride_date) : '';
              var time = r.ride_time ? fmtIsoTime(r.ride_time) : '';
              return '<div class="index-ride-card">' +
                '<div class="irc-icon">&#128664;</div>' +
                '<div class="irc-body">' +
                  '<div class="irc-route">' +
                    '<span class="irc-place">' + escHtml(r.from_place) + '</span>' +
                    '<span class="irc-arrow">&#8594;</span>' +
                    '<span class="irc-place">' + escHtml(r.to_place) + '</span>' +
                  '</div>' +
                  '<div class="irc-meta">' +
                    (date ? '<span class="irc-chip">&#128197; ' + date + '</span>' : '') +
                    (time ? '<span class="irc-chip">&#128336; ' + time + '</span>' : '') +
                    '<span class="irc-chip">&#128222; ' + escHtml(r.contact) + '</span>' +
                  '</div>' +
                '</div>' +
                '<div class="irc-badge">' + seatsBadge(r.seats) + '</div>' +
              '</div>';
            }).join('') +
          '</div>';
        })
        .catch(function () {
          indexRideList.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128664;</div><p>—</p></div>';
        });
    }
  }

  /* ── article page ─────────────────────────────── */
  function initArticlePage() {
    var container = $('#articleContent');
    if (!container) return;

    var slug = window.location.pathname.split('/').filter(Boolean).pop();
    if (!slug) { container.innerHTML = '<p>Статтю не знайдено.</p>'; return; }

    apiFetch('GET', '/articles/' + slug)
      .then(function (res) {
        if (!res.ok) throw new Error(res.status);
        return res.json();
      })
      .then(function (a) {
        document.title = a.title + ' | Борове';

        var heroEl = $('#articleHero');
        if (heroEl) {
          heroEl.querySelector('img').src = articleImg(a, 1400, 500);
          heroEl.querySelector('img').alt = a.title;
        }

        container.innerHTML =
          '<nav class="article-breadcrumb" aria-label="Навігація">' +
            '<a href="/">Головна</a><span>›</span>' +
            '<a href="/">Новини</a><span>›</span>' +
            escHtml(a.category) +
          '</nav>' +
          '<span class="article-category">' + escHtml(a.category) + '</span>' +
          '<h1>' + escHtml(a.title) + '</h1>' +
          '<div class="article-meta">' +
            '<span>&#9997;&#65039; ' + escHtml(a.author) + '</span>' +
            '<span>&#128197; ' + fmtIsoDate(a.published_at) + '</span>' +
            '<span>&#128065; ' + a.views + ' переглядів</span>' +
          '</div>' +
          a.body +
          '<a href="/" class="article-back">&#8592; Повернутись до новин</a>';
      })
      .catch(function () {
        container.innerHTML = '<p>Статтю не знайдено або сталася помилка.</p>';
      });
  }

  /* ── album page ───────────────────────────────── */
  function initAlbumPage() {
    var container = $('#albumContent');
    if (!container) return;

    var slug = window.location.pathname.split('/').filter(Boolean).pop();
    if (!slug) { container.innerHTML = '<p>Альбом не знайдено.</p>'; return; }

    apiFetch('GET', '/albums/' + slug)
      .then(function (res) {
        if (!res.ok) throw new Error(res.status);
        return res.json();
      })
      .then(function (album) {
        document.title = album.title + ' | Борове';

        var heroEl = $('#albumHero');
        if (heroEl) {
          heroEl.querySelector('img').src = albumCoverUrl(album, 1400, 500);
          heroEl.querySelector('img').alt = album.title;
        }

        var header = document.querySelector('.album-page-title');
        if (header) header.textContent = album.title;

        var meta = document.querySelector('.album-page-meta');
        if (meta) meta.textContent = '&#128247; ' + album.photos.length + ' фото · ' + fmtIsoDate(album.album_date);

        var grid = document.querySelector('.photo-grid');
        if (!grid) return;

        if (!album.photos.length) {
          grid.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128247;</div><p>Фото відсутні</p></div>';
          return;
        }

        grid.innerHTML = album.photos.map(function (p, i) {
          var cls = 'photo-item' + (i === 0 ? ' photo-item--featured' : '');
          return '<div class="' + cls + '">' +
            '<img src="' + photoUrl(p, 900, 600) + '" alt="' + escHtml(p.caption || '') + '" loading="lazy">' +
            '<div class="photo-item-overlay"><span class="photo-zoom-icon">&#128269;</span></div>' +
            (p.caption ? '<div class="photo-item-caption">' + escHtml(p.caption) + '</div>' : '') +
          '</div>';
        }).join('');

        initLightbox();
      })
      .catch(function () {
        container.innerHTML = '<p>Альбом не знайдено або сталася помилка.</p>';
      });
  }

  /* ── gallery page ─────────────────────────────── */
  function initGalleryPage() {
    var grid = $('#galleryGrid');
    if (!grid) return;

    // User submission section
    var submitWrap   = document.getElementById('gallerySubmitWrap');
    var submitToggle = document.getElementById('gallerySubmitToggle');
    var submitForm   = document.getElementById('gallerySubmitForm');
    var user = getCachedUser();

    if (submitToggle && user) {
      submitToggle.style.display = '';

      submitToggle.addEventListener('click', function () {
        var willOpen = (submitWrap.style.display === 'none');
        submitWrap.style.display = willOpen ? '' : 'none';
        submitToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        submitToggle.innerHTML = willOpen ? '&#x2715; Закрити' : '&#43; Додати фотоальбом';
      });

      // Photo preview
      var photosInput = document.getElementById('uaPhotos');
      var photosLabel = document.getElementById('uaPhotosLabel');
      var photosName  = document.getElementById('uaPhotosName');
      var previewBox  = document.getElementById('uaPreview');
      var uaCoverIndex = 0;
      if (photosInput) {
        photosInput.addEventListener('change', function () {
          var files = Array.prototype.slice.call(photosInput.files);
          uaCoverIndex = 0;
          if (!files.length) {
            photosName.textContent = 'Вибрати фото (можна кілька)';
            photosLabel.classList.remove('has-file');
            previewBox.style.display = 'none';
            previewBox.innerHTML = '';
            return;
          }
          photosName.textContent = files.length + ' фото вибрано';
          photosLabel.classList.add('has-file');
          previewBox.innerHTML = '';

          var hint = document.createElement('span');
          hint.className = 'ua-cover-hint';
          hint.textContent = 'Натисніть на фото щоб зробити його обкладинкою альбому';
          previewBox.appendChild(hint);

          var thumbs = [];
          files.forEach(function (f, idx) {
            var img = document.createElement('img');
            img.className = 'ua-preview-thumb' + (idx === 0 ? ' is-cover' : '');
            img.alt = f.name;
            img.src = URL.createObjectURL(f);
            img.title = 'Зробити обкладинкою';
            img.addEventListener('click', function () {
              thumbs.forEach(function (t) { t.classList.remove('is-cover'); });
              img.classList.add('is-cover');
              uaCoverIndex = idx;
            });
            previewBox.appendChild(img);
            thumbs.push(img);
          });

          previewBox.style.display = 'flex';
        });
      }

      var albumForm = document.getElementById('userAlbumForm');
      if (albumForm) {
        albumForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var title   = document.getElementById('uaTitle').value.trim();
          var date    = document.getElementById('uaDate').value;
          var files   = photosInput ? photosInput.files : null;
          if (!title)           { showToast('Вкажіть назву альбому'); return; }
          if (!date)            { showToast('Оберіть дату події'); return; }
          if (!files || !files.length) { showToast('Додайте хоча б одне фото'); return; }

          var token = getToken();
          if (!token) { showToast('Увійдіть, щоб завантажити фото'); return; }

          var fd = new FormData();
          fd.append('title', title);
          fd.append('album_date', date);
          fd.append('cover_index', uaCoverIndex);
          var desc = document.getElementById('uaDesc').value.trim();
          if (desc) fd.append('description', desc);
          Array.prototype.forEach.call(files, function (f) { fd.append('photos[]', f); });

          var btn = albumForm.querySelector('button[type="submit"]');
          setSubmitLoading(btn, true);

          apiUpload('POST', '/my/albums', fd, token)
            .then(function (res) {
              if (res.status === 401) throw new Error('auth');
              if (!res.ok) return res.json().then(function (d) {
                throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'error'));
              });
              return res.json();
            })
            .then(function () {
              albumForm.reset();
              photosName.textContent = 'Вибрати фото (можна кілька)';
              photosLabel.classList.remove('has-file');
              previewBox.style.display = 'none';
              previewBox.innerHTML = '';
              submitWrap.style.display = 'none';
              submitToggle.setAttribute('aria-expanded', 'false');
              submitToggle.innerHTML = '&#43; Додати фотоальбом';
              showToast('✓ Альбом відправлено на модерацію!');
            })
            .catch(function (err) {
              if (err.message === 'auth') {
                showToast('Сесія закінчилась — увійдіть знову');
              } else {
                showToast(err.message || 'Помилка. Спробуйте ще раз.');
              }
            })
            .finally(function () { setSubmitLoading(btn, false); });
        });
      }
    }

    var ALBUMS_PER_PAGE = 15;
    var loadedAlbums   = [];
    var albumPage      = 0;
    var albumTotal     = 0;
    var pagEl = document.getElementById('galleryPagination');

    function renderAlbumGrid() {
      if (!loadedAlbums.length) {
        grid.innerHTML = '<div class="empty-state"><p>Альбоми відсутні</p></div>';
        if (pagEl) pagEl.innerHTML = '';
        return;
      }
      grid.innerHTML = loadedAlbums.map(function (a) {
        return '<a href="/gallery/' + a.slug + '" class="album-cover fade-in">' +
          '<img src="' + albumCoverUrl(a, 800, 600) + '" alt="' + escHtml(a.title) + '" loading="lazy">' +
          '<div class="album-cover-overlay">' +
            '<div class="album-cover-title">' + escHtml(a.title) + '</div>' +
            '<div class="album-cover-meta">' +
              '<span class="album-count">&#128247; ' + (a.photos_count || 0) + ' фото</span>' +
              '<span class="album-date">' + fmtIsoDate(a.album_date) + '</span>' +
            '</div>' +
          '</div>' +
        '</a>';
      }).join('');
      initFadeIn();
      if (!pagEl) return;
      var remaining = albumTotal - loadedAlbums.length;
      if (remaining > 0) {
        pagEl.innerHTML = '<button class="btn-show-more">+ Показати ще ' + remaining + ' альбом' + pluralUa(remaining) + '</button>';
        pagEl.querySelector('.btn-show-more').addEventListener('click', loadMoreAlbums);
      } else {
        pagEl.innerHTML = '';
      }
    }

    function loadMoreAlbums() {
      var nextPage = albumPage + 1;
      if (pagEl) pagEl.innerHTML = '<button class="btn-show-more" disabled>Завантаження…</button>';
      apiFetch('GET', '/albums?per_page=' + ALBUMS_PER_PAGE + '&page=' + nextPage)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          albumPage  = resp.current_page;
          albumTotal = resp.total;
          loadedAlbums = loadedAlbums.concat(resp.data || []);
          renderAlbumGrid();
        })
        .catch(function () {
          if (!loadedAlbums.length) {
            grid.innerHTML = '<div class="empty-state"><p>Не вдалося завантажити галерею</p></div>';
          }
          if (pagEl) pagEl.innerHTML = '';
        });
    }

    loadMoreAlbums();
  }

  /* ── index page articles ──────────────────────── */
  function initIndexArticles() {
    var featured = document.getElementById('featuredArticle');
    var grid     = document.getElementById('indexArticleGrid');
    if (!grid) return;

    var loadedArts = [], artPageIdx = 0;
    var PER_PAGE = 6;

    function renderFeatured(a) {
      if (!featured || !a) return;
      featured.innerHTML =
        '<article class="featured-article">' +
          '<img class="article-image" src="' + articleImg(a, 1200, 400) + '" alt="' + escHtml(a.title) + '" loading="lazy">' +
          '<div class="article-body">' +
            '<span class="article-category">&#11088; ' + escHtml(a.category) + '</span>' +
            '<h2>' + escHtml(a.title) + '</h2>' +
            '<div class="article-meta">' +
              '<span>&#9997;&#65039; ' + escHtml(a.author) + '</span>' +
              '<span>&#128197; ' + fmtIsoDate(a.published_at ? a.published_at.substring(0, 10) : '') + '</span>' +
              '<span>&#128065; ' + (a.views || 0) + ' переглядів</span>' +
            '</div>' +
            '<p>' + escHtml(a.summary) + '</p>' +
            '<a href="/articles/' + escHtml(a.slug) + '" class="btn-read">Читати далі &#8594;</a>' +
          '</div>' +
        '</article>';
    }

    function renderCards() {
      var cards = loadedArts.slice(1);
      if (!cards.length) {
        grid.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128240;</div><p>Новин поки немає</p></div>';
        return;
      }
      grid.innerHTML = cards.map(function (a) {
        return '<article class="article-card">' +
          '<div class="card-img">' +
            '<img src="' + articleImg(a, 600, 300) + '" alt="' + escHtml(a.title) + '" loading="lazy">' +
            '<span class="article-category">' + escHtml(a.category) + '</span>' +
          '</div>' +
          '<div class="card-body">' +
            '<h3>' + escHtml(a.title) + '</h3>' +
            '<p>' + escHtml(a.summary) + '</p>' +
            '<div class="card-footer">' +
              '<span class="card-date">&#128197; ' + fmtIsoDate(a.published_at ? a.published_at.substring(0, 10) : '') + '</span>' +
              '<a href="/articles/' + escHtml(a.slug) + '" class="btn-read">Читати &#8594;</a>' +
            '</div>' +
          '</div>' +
        '</article>';
      }).join('');
    }

    function loadMore() {
      apiFetch('GET', '/articles?per_page=' + PER_PAGE + '&page=1')
        .then(function (r) { return r.json(); })
        .then(function (resp) {
          artPageIdx = resp.current_page;
          loadedArts = resp.data || [];
          if (loadedArts.length) renderFeatured(loadedArts[0]);
          renderCards();
        })
        .catch(function () {
          grid.innerHTML = '<div class="empty-state"><p>Помилка завантаження</p></div>';
        });
    }

    loadMore();
  }

  /* ── all articles page ───────────────────────── */
  function initArticlesListPage() {
    var grid = document.getElementById('articlesListGrid');
    if (!grid) return;

    var pagEl = document.getElementById('articlesListPagination');
    var ARTS_PER_PAGE = 12;
    var artListPage = 0;
    var artListTotal = 0;
    var renderedCount = 0;
    var firstBatch = true;

    function pluralStattya(n) {
      var mod10 = n % 10, mod100 = n % 100;
      if (mod10 === 1 && mod100 !== 11) return 'статтю';
      if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'статті';
      return 'статей';
    }

    function cardHtml(a) {
      return '<article class="article-card fade-in">' +
        '<div class="card-img">' +
          '<img src="' + articleImg(a, 800, 500) + '" alt="' + escHtml(a.title) + '" loading="lazy">' +
          '<span class="article-category">' + escHtml(a.category) + '</span>' +
        '</div>' +
        '<div class="card-body">' +
          '<h3>' + escHtml(a.title) + '</h3>' +
          '<p>' + escHtml(a.summary) + '</p>' +
          '<div class="card-footer">' +
            '<span class="card-date">&#128197; ' + fmtIsoDate(a.published_at ? String(a.published_at).substring(0, 10) : '') + '</span>' +
            '<a href="/articles/' + encodeURIComponent(a.slug) + '" class="btn-read">Читати &#8594;</a>' +
          '</div>' +
        '</div>' +
      '</article>';
    }

    function renderPagination() {
      if (!pagEl) return;
      var remaining = artListTotal - renderedCount;
      if (remaining > 0) {
        var n = Math.min(remaining, ARTS_PER_PAGE);
        pagEl.innerHTML = '<button class="btn-show-more">+ Показати ще ' + n + ' ' + pluralStattya(n) + '</button>';
        pagEl.querySelector('.btn-show-more').addEventListener('click', loadMore);
      } else {
        pagEl.innerHTML = '';
      }
    }

    function appendArticles(items) {
      if (firstBatch) {
        grid.innerHTML = '';
        firstBatch = false;
      }
      grid.insertAdjacentHTML('beforeend', items.map(cardHtml).join(''));
      var fresh = Array.prototype.slice.call(grid.querySelectorAll('.article-card.fade-in:not(.visible)'));
      observeFadeIn(fresh);
      renderedCount += items.length;
    }

    function loadMore() {
      var nextPage = artListPage + 1;
      if (pagEl) pagEl.innerHTML = '<button class="btn-show-more" disabled>Завантаження…</button>';
      apiFetch('GET', '/articles?per_page=' + ARTS_PER_PAGE + '&page=' + nextPage)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          artListPage = resp.current_page;
          artListTotal = resp.total;
          var items = resp.data || [];
          if (firstBatch && !items.length) {
            grid.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128240;</div><p>Статей ще немає</p></div>';
            if (pagEl) pagEl.innerHTML = '';
            return;
          }
          appendArticles(items);
          renderPagination();
        })
        .catch(function () {
          if (firstBatch) {
            grid.innerHTML = '<div class="empty-state"><p>Не вдалося завантажити статті</p></div>';
          }
          if (pagEl) pagEl.innerHTML = '';
        });
    }

    loadMore();
  }

  /* ── index page sidebar albums ────────────────── */
  function initIndexAlbums() {
    var grid = $('#indexAlbumGrid');
    if (!grid) return;

    apiFetch('GET', '/albums?per_page=3')
      .then(function (res) { return res.json(); })
      .then(function (resp) {
        var recent = resp.data || [];
        grid.innerHTML = recent.map(function (a) {
          return '<a href="/gallery/' + a.slug + '" class="album-cover fade-in">' +
            '<img src="' + albumCoverUrl(a, 800, 600) + '" alt="' + escHtml(a.title) + '" loading="lazy">' +
            '<div class="album-cover-overlay">' +
              '<div class="album-cover-title">' + escHtml(a.title) + '</div>' +
              '<div class="album-cover-meta">' +
                '<span class="album-count">&#128247; ' + (a.photos_count || 0) + ' фото</span>' +
                '<span class="album-date">' + fmtIsoDate(a.album_date) + '</span>' +
              '</div>' +
            '</div>' +
          '</a>';
        }).join('');
        initFadeIn();
      })
      .catch(function () {});
  }

  /* ── lightbox ────────────────────────────────── */
  function initLightbox() {
    var items = $$('.photo-item');
    if (!items.length) return;

    var lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.innerHTML =
      '<button class="lightbox-close" aria-label="Закрити">&#10005;</button>' +
      '<button class="lightbox-prev" aria-label="Попереднє">&#8249;</button>' +
      '<div class="lightbox-img-wrap"><img src="" alt=""></div>' +
      '<button class="lightbox-next" aria-label="Наступне">&#8250;</button>' +
      '<div class="lightbox-counter"></div>';
    document.body.appendChild(lb);

    var lbImg     = lb.querySelector('img');
    var lbCounter = lb.querySelector('.lightbox-counter');
    var current   = 0;
    var srcs      = items.map(function (el) { return el.querySelector('img').src; });

    function show(idx) {
      current = (idx + srcs.length) % srcs.length;
      lbImg.src = srcs[current];
      lbImg.alt = items[current].querySelector('img').alt;
      lbCounter.textContent = (current + 1) + ' / ' + srcs.length;
    }

    function open(idx) {
      show(idx);
      lb.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function close() {
      lb.classList.remove('open');
      document.body.style.overflow = '';
      lbImg.src = '';
    }

    items.forEach(function (el, i) {
      el.addEventListener('click', function () { open(i); });
    });

    lb.querySelector('.lightbox-close').addEventListener('click', close);
    lb.querySelector('.lightbox-prev').addEventListener('click', function () { show(current - 1); });
    lb.querySelector('.lightbox-next').addEventListener('click', function () { show(current + 1); });

    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });

    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape'     || e.keyCode === 27) close();
      if (e.key === 'ArrowLeft'  || e.keyCode === 37) show(current - 1);
      if (e.key === 'ArrowRight' || e.keyCode === 39) show(current + 1);
    });
  }

  /* ── single-image lightbox (products) ───────── */
  var _slb = null;
  function openSingleLightbox(src, alt) {
    if (!_slb) {
      _slb = document.createElement('div');
      _slb.className = 'lightbox';
      _slb.innerHTML =
        '<button class="lightbox-close" aria-label="Закрити">&#10005;</button>' +
        '<div class="lightbox-img-wrap"><img src="" alt=""></div>';
      document.body.appendChild(_slb);
      _slb.querySelector('.lightbox-close').addEventListener('click', function () {
        _slb.classList.remove('open');
        document.body.style.overflow = '';
        _slb.querySelector('img').src = '';
      });
      _slb.addEventListener('click', function (e) {
        if (e.target === _slb) {
          _slb.classList.remove('open');
          document.body.style.overflow = '';
          _slb.querySelector('img').src = '';
        }
      });
      document.addEventListener('keydown', function (e) {
        if (!_slb || !_slb.classList.contains('open')) return;
        if (e.key === 'Escape' || e.keyCode === 27) {
          _slb.classList.remove('open');
          document.body.style.overflow = '';
          _slb.querySelector('img').src = '';
        }
      });
    }
    var img = _slb.querySelector('img');
    img.src = src;
    img.alt = alt || '';
    _slb.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  /* ── fade-in on scroll ───────────────────────── */
  var _fadeObs = null;

  function observeFadeIn(els) {
    if (!els || !els.length) return;
    if ('IntersectionObserver' in window) {
      if (!_fadeObs) {
        _fadeObs = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              _fadeObs.unobserve(entry.target);
            }
          });
        }, { threshold: 0.08 });
      }
      els.forEach(function (el) { _fadeObs.observe(el); });
    } else {
      els.forEach(function (el) { el.classList.add('visible'); });
    }
  }

  function initFadeIn() {
    observeFadeIn($$('.fade-in'));
  }

  /* ── nav user area ─────────────────────────────── */
  function initUserArea() {
    var li = document.getElementById('navAuthLi');
    if (!li) return;
    var user  = getCachedUser();
    var token = getToken();
    if (!user || !token) { clearSession(); user = null; }
    if (user) {
      var display = escHtml(user.nickname || user.first_name || 'Користувач');
      var avatarHtml = user.avatar_path
        ? '<img src="/storage/' + user.avatar_path + '" class="nav-avatar" alt="">'
        : '<span class="nav-avatar nav-avatar--initials">' + escHtml((user.first_name || user.nickname || '?')[0].toUpperCase()) + '</span>';
      li.innerHTML =
        (user.is_admin ? '<a href="/admin" class="btn-nav-admin" title="Адмінпанель">&#9881;</a>' : '') +
        '<a href="/profile" class="nav-user-info">' + avatarHtml + '<span class="nav-user-name">' + display + '</span></a>' +
        '<button class="btn-nav-logout" id="btnNavLogout">Вийти</button>';

      // Mobile drawer user card — inserted right after drawer-head, links to /profile
      var menu = document.getElementById('siteMenu');
      var drawerHead = menu ? menu.querySelector('.drawer-head') : null;
      if (menu) {
        var bigAvatar = user.avatar_path
          ? '<img src="/storage/' + user.avatar_path + '" class="nav-avatar drawer-avatar" alt="">'
          : '<span class="nav-avatar nav-avatar--initials drawer-avatar">' + escHtml((user.first_name || user.nickname || '?')[0].toUpperCase()) + '</span>';
        var card = document.createElement('a');
        card.className = 'drawer-user-card';
        card.href = '/profile';
        card.setAttribute('aria-label', 'Перейти у профіль');
        card.innerHTML = bigAvatar +
          '<div class="drawer-user-name">' + display + '</div>' +
          '<span class="drawer-user-arrow" aria-hidden="true">&#8250;</span>';
        if (drawerHead) {
          drawerHead.parentNode.insertBefore(card, drawerHead.nextSibling);
        } else {
          menu.insertBefore(card, menu.firstChild);
        }

        if (user.is_admin) {
          var adminLink = document.createElement('a');
          adminLink.className = 'drawer-admin-link';
          adminLink.href = '/admin';
          adminLink.innerHTML = '&#9881;&#65039; <span>Адмінпанель</span>';
          card.parentNode.insertBefore(adminLink, card.nextSibling);
        }
      }

      var logoutBtn = document.getElementById('btnNavLogout');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
          var tok = getToken();
          function done() { clearSession(); window.location.reload(); }
          if (tok) {
            apiFetch('POST', '/logout', null, tok).then(done).catch(done);
          } else {
            done();
          }
        });
      }
    } else {
      li.innerHTML = '<a href="/auth" class="btn-nav-login"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>Увійти</a>';
    }
  }

  /* ── phone mask (XX XXX-XX-XX) ─────────────────── */
  function initPhoneMasks() {
    function toDigits9(val) {
      var d = String(val).replace(/\D/g, '');
      if (d.length === 12 && d.substring(0, 3) === '380') d = d.substring(3);
      else if (d.length === 10 && d[0] === '0')           d = d.substring(1);
      return d.substring(0, 9);
    }

    function fmt(d) {
      if (!d) return '';
      var s = d.substring(0, 2);
      if (d.length > 2) s += ' ' + d.substring(2, 5);
      if (d.length > 5) s += '-' + d.substring(5, 7);
      if (d.length > 7) s += '-' + d.substring(7, 9);
      return s;
    }

    [].forEach.call(document.querySelectorAll('input[data-phone-mask]'), function (inp) {
      inp.addEventListener('input', function () {
        var pos = this.selectionStart;
        var before = (this.value.substring(0, pos).replace(/\D/g, '')).length;
        var d = toDigits9(this.value);
        this.value = fmt(d);
        var count = 0, i = 0;
        while (i < this.value.length && count < before) {
          if (/\d/.test(this.value[i])) count++;
          i++;
        }
        this.setSelectionRange(i, i);
      });

      inp.addEventListener('paste', function (e) {
        e.preventDefault();
        var txt = (e.clipboardData || window.clipboardData).getData('text');
        this.value = fmt(toDigits9(txt));
      });
    });
  }

  /* ── auth page logic ───────────────────────────── */
  function normalizePhone(raw) {
    var digits = String(raw || '').replace(/\D/g, '');
    if (digits.length === 12 && digits.substring(0, 2) === '38') digits = digits.substring(2);
    if (digits.length === 11 && digits[0] === '8')               digits = digits.substring(1);
    if (digits.length === 9  && digits[0] !== '0')               digits = '0' + digits;
    return digits;
  }

  function initAuthPage() {
    var loginForm    = document.getElementById('loginForm');
    var registerForm = document.getElementById('registerForm');
    if (!loginForm && !registerForm) return;

    if (getCachedUser()) { window.location.replace('/'); return; }

    var tabs = Array.prototype.slice.call(document.querySelectorAll('.auth-tab'));

    function switchTab(name) {
      tabs.forEach(function (t) {
        t.classList.toggle('active', t.getAttribute('data-tab') === name);
      });
      if (loginForm)    loginForm.style.display    = name === 'login'    ? '' : 'none';
      if (registerForm) registerForm.style.display = name === 'register' ? '' : 'none';
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () { switchTab(this.getAttribute('data-tab')); });
    });

    var switchBtns = Array.prototype.slice.call(document.querySelectorAll('.auth-switch button[data-tab]'));
    switchBtns.forEach(function (btn) {
      btn.addEventListener('click', function () { switchTab(this.getAttribute('data-tab')); });
    });

    switchTab(/[?&]tab=register/.test(window.location.search) ? 'register' : 'login');

    var pwToggles = Array.prototype.slice.call(document.querySelectorAll('.btn-pw-toggle'));
    pwToggles.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var inp = this.parentNode.querySelector('input');
        if (!inp) return;
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        this.innerHTML = show ? '&#128064;' : '&#128065;';
      });
    });

    function showErr(form, msg) {
      var box = form.querySelector('.auth-error');
      if (box) { box.textContent = msg; box.classList.add('show'); }
    }
    function clearErr(form) {
      var box = form.querySelector('.auth-error');
      if (box) box.classList.remove('show');
    }
    function setLoading(btn, on) {
      btn.disabled = on;
      btn.style.opacity = on ? '.6' : '';
    }

    if (loginForm) {
      loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErr(this);
        var btn = this.querySelector('.btn-auth-submit');
        setLoading(btn, true);
        apiFetch('POST', '/login', {
          phone:    normalizePhone(this.elements.phone.value),
          password: this.elements.password.value
        })
        .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
        .then(function (r) {
          if (!r.ok) {
            var msg = (r.d.errors && r.d.errors.phone)
              ? r.d.errors.phone[0]
              : (r.d.message || 'Помилка входу');
            showErr(loginForm, msg);
            return;
          }
          saveSession(r.d.user, r.d.token);
          showToast('Ласкаво просимо, ' + (r.d.user.nickname || r.d.user.first_name) + '!');
          setTimeout(function () { window.location.href = '/'; }, 700);
        })
        .catch(function () { showErr(loginForm, 'Сервер недоступний. Спробуйте пізніше.'); })
        .finally(function () { setLoading(btn, false); });
      });
    }

    if (registerForm) {
      registerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErr(this);
        var els = this.elements;
        var phone = normalizePhone(els.phone.value);

        if (!els.lastName.value.trim())   { showErr(registerForm, 'Вкажіть прізвище');       return; }
        if (!els.firstName.value.trim())  { showErr(registerForm, "Вкажіть ім'я");            return; }
        if (!els.patronymic.value.trim()) { showErr(registerForm, 'Вкажіть по батькові');     return; }
        if (!els.street.value.trim())     { showErr(registerForm, 'Вкажіть вулицю');          return; }
        if (!els.nickname.value.trim())   { showErr(registerForm, 'Вкажіть кличку');          return; }
        if (phone.length !== 10 || phone[0] !== '0') {
          showErr(registerForm, 'Введіть повний номер: +380 XX XXX-XX-XX');
          return;
        }
        if (els.password.value.length < 8) {
          showErr(registerForm, 'Пароль — мінімум 8 символів');
          return;
        }
        if (els.password.value !== els.passwordConfirm.value) {
          showErr(registerForm, 'Паролі не збігаються');
          return;
        }

        var btn = this.querySelector('.btn-auth-submit');
        setLoading(btn, true);
        apiFetch('POST', '/register', {
          last_name:             els.lastName.value.trim(),
          first_name:            els.firstName.value.trim(),
          patronymic:            els.patronymic.value.trim(),
          street:                els.street.value.trim(),
          nickname:              els.nickname.value.trim(),
          phone:                 phone,
          password:              els.password.value,
          password_confirmation: els.passwordConfirm.value
        })
        .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
        .then(function (r) {
          if (!r.ok) {
            var first = r.d.errors ? Object.values(r.d.errors)[0][0] : (r.d.message || 'Помилка реєстрації');
            showErr(registerForm, first);
            return;
          }
          saveSession(r.d.user, r.d.token);
          showToast('Реєстрацію завершено! Ласкаво просимо, ' + r.d.user.nickname + '!');
          setTimeout(function () { window.location.href = '/'; }, 700);
        })
        .catch(function () { showErr(registerForm, 'Сервер недоступний. Спробуйте пізніше.'); })
        .finally(function () { setLoading(btn, false); });
      });
    }
  }

  /* ── MARKETPLACE ─────────────────────────────── */
  function photoSrc(path) {
    return path && /^https?:\/\//.test(path) ? path : '/storage/' + path;
  }

  function productCoverHtml(p, cls) {
    if (p.photo_path) {
      return '<img class="' + cls + '" src="' + photoSrc(p.photo_path) + '" alt="' + escHtml(p.title) + '" loading="lazy">';
    }
    return '<div class="product-card-img-placeholder">&#128717;</div>';
  }

  function productPriceHtml(p) {
    if (p.price != null) {
      return '<div class="product-card-price">' + Number(p.price).toLocaleString('uk-UA') + ' грн</div>';
    }
    return '<div class="product-card-price product-card-price--free">Ціна за домовленістю</div>';
  }

  function sellerName(p) {
    if (!p.shop) return '';
    var u = p.shop.user;
    return p.shop.name || (u ? (u.nickname || u.first_name || '') : '');
  }

  function sellerLink(p) {
    var name = sellerName(p);
    if (!name) return '';
    var sid = p.shop_id || (p.shop && p.shop.id);
    return sid
      ? '<a href="/shop?shop=' + sid + '" class="product-seller-link">' + escHtml(name) + '</a>'
      : escHtml(name);
  }

  function renderProductCard(p, myShopId, sentIds) {
    var isOwn   = myShopId && p.shop_id === myShopId;
    var isSent  = sentIds && sentIds.indexOf(p.id) !== -1;
    var actionsHtml = '';
    if (isOwn) {
      actionsHtml = '<span class="product-card-own-tag">&#127775; Ваш товар</span>' +
        '<button class="btn-product-del" data-id="' + p.id + '" title="Видалити товар">&#128465; Видалити</button>';
    } else {
      var label = isSent ? '&#10003; Запит надіслано' : '&#128222; Бажаю купити';
      actionsHtml = '<button class="btn-buy' + (isSent ? ' btn-buy--sent' : '') + '" data-id="' + p.id + '" data-title="' + escHtml(p.title) + '"' + (isSent ? ' disabled' : '') + '>' + label + '</button>';
    }
    var date = p.created_at ? fmtIsoDate(p.created_at.substring(0, 10)) : '';
    var imgHtml = p.photo_path
      ? '<img class="product-card-img product-card-img--clickable" src="' + photoSrc(p.photo_path) + '" alt="' + escHtml(p.title) + '" loading="lazy" data-src="' + photoSrc(p.photo_path) + '" data-alt="' + escHtml(p.title) + '">'
      : '<div class="product-card-img-placeholder">&#128717;</div>';
    return '<div class="product-card fade-in' + (isOwn ? ' product-card--own' : '') + '">' +
      imgHtml +
      '<div class="product-card-body">' +
        '<div class="product-card-title">' + escHtml(p.title) + '</div>' +
        (p.description ? '<div class="product-card-desc">' + escHtml(p.description) + '</div>' : '') +
        productPriceHtml(p) +
        '<div class="product-card-seller">&#128100; ' + sellerLink(p) + (date ? ' &middot; ' + date : '') + '</div>' +
        (p.purchase_requests_count > 0 ? '<div class="product-card-requests">&#128276; ' + p.purchase_requests_count + ' запит' + pluralUa(p.purchase_requests_count) + '</div>' : '') +
        '<div class="product-card-actions">' + actionsHtml + '</div>' +
      '</div>' +
    '</div>';
  }

  function initShopPage() {
    var listEl = document.getElementById('productList');
    if (!listEl) return;

    var urlParams = new URLSearchParams(window.location.search);
    var filterShopId = urlParams.get('shop') ? parseInt(urlParams.get('shop'), 10) : null;

    var user      = getCachedUser();
    var token     = getToken();
    var myShopId  = null;
    var loadedProds = [];
    var prodPage    = 0;
    var prodTotal   = 0;
    var sentIds     = [];
    var pendingBuyProduct = null;
    var PRODS_PER_PAGE = 9;

    /* when viewing a specific shop, hide sidebar and show header */
    if (filterShopId) {
      var heroEl = document.querySelector('.page-hero');
      if (heroEl) heroEl.style.display = 'none';
      var sidebarEl = document.querySelector('.content-sidebar');
      if (sidebarEl) sidebarEl.style.display = 'none';
      var headerEl = document.getElementById('shopHeader');
      if (headerEl) {
        headerEl.innerHTML = '<div class="shop-header-banner"><div class="empty-icon">&#128717;</div><p>Завантаження магазину...</p></div>';
        headerEl.style.display = '';
      }
      apiFetch('GET', '/shops/' + filterShopId)
        .then(function (res) {
          if (!res.ok) throw new Error();
          return res.json();
        })
        .then(function (shop) {
          if (!headerEl) return;
          var ownerName = shop.user
            ? (shop.user.nickname || shop.user.first_name || '')
            : '';
          headerEl.innerHTML =
            '<div class="shop-header-banner">' +
              '<div class="shop-header-icon">&#128717;</div>' +
              '<div class="shop-header-info">' +
                '<h2 class="shop-header-name">' + escHtml(shop.name) + '</h2>' +
                (shop.description ? '<p class="shop-header-desc">' + escHtml(shop.description) + '</p>' : '') +
                '<div class="shop-header-meta">' +
                  (ownerName ? '&#128100; ' + escHtml(ownerName) + ' &nbsp;&middot;&nbsp; ' : '') +
                  '&#128722; ' + (shop.products_count || 0) + ' товар' + pluralUa(shop.products_count || 0) +
                  ' &nbsp;&middot;&nbsp; <a href="/shop" class="shop-header-back">&#8592; Всі товари</a>' +
                '</div>' +
              '</div>' +
            '</div>';
        })
        .catch(function () {
          if (headerEl) headerEl.innerHTML = '<div class="shop-header-banner"><p>Магазин не знайдено. <a href="/shop">&#8592; Назад</a></p></div>';
        });
    }

    /* sidebar toggle */
    var sideToggle  = document.getElementById('shopSidebarToggle');
    var sideSection = document.getElementById('shopSidebarSection');
    if (sideToggle && sideSection) {
      sideToggle.addEventListener('click', function () {
        var open = sideSection.classList.toggle('open');
        sideToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    /* buy modal */
    var modal       = document.getElementById('buyModal');
    var modalTitle  = document.getElementById('buyModalProduct');
    var modalMsg    = document.getElementById('buyMessage');
    var modalOk     = document.getElementById('buyModalConfirm');
    var modalCancel = document.getElementById('buyModalCancel');
    if (modalCancel) modalCancel.addEventListener('click', function () { modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });

    /* fetch next page and append */
    function loadMoreProds() {
      var nextPage = prodPage + 1;
      var url = '/products?per_page=' + PRODS_PER_PAGE + '&page=' + nextPage;
      if (filterShopId) url += '&shop_id=' + filterShopId;

      var pag = document.getElementById('productPagination');
      if (pag) pag.innerHTML = '<button class="btn-show-more" disabled>Завантаження…</button>';

      apiFetch('GET', url)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          prodPage  = resp.current_page;
          prodTotal = resp.total;
          loadedProds = loadedProds.concat(resp.data || []);
          renderPage();
        })
        .catch(function () {
          if (!loadedProds.length) {
            listEl.innerHTML = '<div class="empty-state"><p>Не вдалося завантажити товари</p></div>';
          }
          if (pag) pag.innerHTML = '';
        });
    }

    /* render product grid */
    function renderPage() {
      var pag = document.getElementById('productPagination');
      if (!loadedProds.length) {
        listEl.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128717;</div><p>Товарів поки немає</p></div>';
        if (pag) pag.innerHTML = '';
        return;
      }

      var gridCls = filterShopId ? 'product-grid product-grid--wide' : 'product-grid';
      listEl.innerHTML = '<div class="' + gridCls + '">' + loadedProds.map(function (p) {
        return renderProductCard(p, myShopId, sentIds);
      }).join('') + '</div>';
      initFadeIn();

      /* product image lightbox */
      listEl.querySelectorAll('.product-card-img--clickable').forEach(function (img) {
        img.addEventListener('click', function () {
          openSingleLightbox(img.dataset.src, img.dataset.alt);
        });
      });

      /* buy buttons */
      listEl.querySelectorAll('.btn-buy:not(:disabled)').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!token) { showToast('Увійдіть, щоб надіслати запит'); return; }
          pendingBuyProduct = { id: parseInt(btn.dataset.id, 10), title: btn.dataset.title };
          if (modalTitle) modalTitle.textContent = btn.dataset.title;
          if (modalMsg)   modalMsg.value = '';
          if (modal)      modal.style.display = 'flex';
        });
      });

      /* delete-own-product buttons */
      listEl.querySelectorAll('.btn-product-del').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Видалити цей товар? Цю дію не можна скасувати.')) return;
          var id = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          apiFetch('DELETE', '/my/shop/products/' + id, null, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              loadedProds = loadedProds.filter(function (p) { return p.id !== id; });
              prodTotal = Math.max(0, prodTotal - 1);
              renderPage();
              loadMySidebar();
              showToast('✓ Товар видалено');
            })
            .catch(function () { showToast('Помилка видалення'); btn.disabled = false; });
        });
      });

      /* "show more" button */
      if (!pag) return;
      var remaining = prodTotal - loadedProds.length;
      if (remaining <= 0) { pag.innerHTML = ''; return; }
      var showMore = Math.min(remaining, PRODS_PER_PAGE);
      pag.innerHTML = '<button class="btn-show-more">&#43; Показати ще ' + showMore + ' товар' + pluralUa(showMore) + '</button>';
      pag.querySelector('.btn-show-more').addEventListener('click', loadMoreProds);
    }

    /* confirm buy */
    if (modalOk) {
      modalOk.addEventListener('click', function () {
        if (!pendingBuyProduct) return;
        var id  = pendingBuyProduct.id;
        var msg = modalMsg ? modalMsg.value.trim() : '';
        setSubmitLoading(modalOk, true);
        apiFetch('POST', '/products/' + id + '/buy-request', { message: msg || null }, token)
          .then(function (res) {
            if (res.status === 422) return res.json().then(function (d) { throw new Error(d.message || 'error'); });
            if (!res.ok) throw new Error();
            return res.json();
          })
          .then(function () {
            if (sentIds.indexOf(id) === -1) sentIds.push(id);
            modal.style.display = 'none';
            renderPage();
            showToast('✓ Запит надіслано! Продавець побачить ваш контакт.');
          })
          .catch(function (err) { showToast(err.message || 'Помилка'); })
          .finally(function () { setSubmitLoading(modalOk, false); });
      });
    }

    /* sidebar: my shop management */
    function loadMySidebar() {
      var wrap = document.getElementById('shopSidebarWrap');
      if (!wrap) return;
      if (!user || !token) {
        wrap.innerHTML =
          '<div class="form-auth-notice">' +
            '<div class="form-auth-icon">&#128274;</div>' +
            '<p>Щоб керувати магазином, потрібно <a href="/auth">увійти</a></p>' +
          '</div>';
        return;
      }
      wrap.innerHTML = '<p class="admin-loading">Завантаження...</p>';
      Promise.all([
        apiFetch('GET', '/my/shop', null, token).then(function (r) { return r.json(); }),
        apiFetch('GET', '/my/shop/requests', null, token).then(function (r) { return r.json(); }),
      ]).then(function (results) {
        var shop     = results[0];
        var requests = results[1];
        var prevShopId = myShopId;
        if (shop) myShopId = shop.id;
        wrap.innerHTML = renderShopSidebar(shop, requests);
        if (myShopId !== prevShopId) renderPage();
        bindShopSidebar(shop);
      }).catch(function () {
        wrap.innerHTML = '<p class="admin-loading">Помилка завантаження</p>';
      });
    }

    function renderShopSidebar(shop, requests) {
      if (!shop) {
        return '<div class="add-form">' +
          '<h3>&#128717; Створити магазин</h3>' +
          '<form id="shopCreateForm" novalidate>' +
            '<div class="form-group"><label for="shopName">Назва магазину *</label>' +
              '<input type="text" id="shopName" placeholder="напр. Городина від Марії" required maxlength="200"></div>' +
            '<div class="form-group"><label for="shopDesc">Опис</label>' +
              '<textarea id="shopDesc" placeholder="Що продаєте..." maxlength="500"></textarea></div>' +
            '<button type="submit" class="btn-submit">&#128717; Створити</button>' +
          '</form>' +
        '</div>';
      }

      var allCount    = (requests || []).length;
      var unreadCount = (requests || []).filter(function (r) { return !r.viewed_at; }).length;
      var reqSubtitle = allCount
        ? allCount + ' запит' + pluralUa(allCount) + (unreadCount ? ' · ' + unreadCount + ' нов' + (unreadCount === 1 ? 'ий' : (unreadCount < 5 ? 'их' : 'их')) : '')
        : 'Поки немає запитів';

      var quickLinks =
        '<div class="shop-quick-links">' +
          '<a href="/shop?shop=' + shop.id + '" class="shop-quick-link shop-quick-link--shop" title="Відкрити мій магазин">' +
            '<span class="sql-icon">&#128717;</span>' +
            '<span class="sql-body">' +
              '<span class="sql-label">Мій магазин</span>' +
              '<span class="sql-name">' + escHtml(shop.name) + '</span>' +
            '</span>' +
            '<span class="sql-arrow">&#8594;</span>' +
          '</a>' +
          '<a href="/requests" class="shop-quick-link shop-quick-link--requests' + (unreadCount ? ' has-unread' : '') + '">' +
            '<span class="sql-icon">&#128276;</span>' +
            '<span class="sql-body">' +
              '<span class="sql-label">Запити на покупку</span>' +
              '<span class="sql-name">' + reqSubtitle + '</span>' +
            '</span>' +
            (unreadCount
              ? '<span class="pending-badge sql-badge">' + unreadCount + '</span>'
              : '<span class="sql-arrow">&#8594;</span>') +
          '</a>' +
        '</div>';

      var addForm =
        '<div class="add-form">' +
          '<h3 style="font-size:.95rem;margin-bottom:12px">&#128722; Додати товар</h3>' +
          '<form id="productAddForm" novalidate>' +
            '<div class="form-group"><label for="prodTitle">Назва *</label>' +
              '<input type="text" id="prodTitle" placeholder="Назва товару" required maxlength="200"></div>' +
            '<div class="form-group"><label for="prodDesc">Опис</label>' +
              '<textarea id="prodDesc" placeholder="Опис, стан, кількість..." maxlength="1000" style="min-height:60px"></textarea></div>' +
            '<div class="form-group"><label for="prodPrice">Ціна (грн)</label>' +
              '<input type="number" id="prodPrice" placeholder="залиш пустим якщо за домовленістю" min="0" step="0.01"></div>' +
            '<div class="form-group"><label>Фото (необов\'язково)</label>' +
              '<label class="file-upload-label" id="prodPhotoLabel" for="prodPhoto">' +
                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>' +
                '<span id="prodPhotoName">Вибрати фото</span>' +
              '</label>' +
              '<input type="file" id="prodPhoto" accept="image/*" class="file-upload-input"></div>' +
            '<button type="submit" class="btn-submit">&#128722; Додати товар</button>' +
          '</form>' +
        '</div>';

      return quickLinks + addForm;
    }

    function bindShopSidebar(shop) {
      var createForm = document.getElementById('shopCreateForm');
      if (createForm) {
        createForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var name = document.getElementById('shopName').value.trim();
          if (!name) { showToast('Введіть назву магазину'); return; }
          var btn = createForm.querySelector('button[type="submit"]');
          setSubmitLoading(btn, true);
          apiFetch('POST', '/my/shop', {
            name: name,
            description: document.getElementById('shopDesc').value.trim() || null,
          }, token)
            .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
            .then(function (s) { myShopId = s.id; loadMySidebar(); renderPage(); showToast('✓ Магазин створено!'); })
            .catch(function () { showToast('Помилка'); })
            .finally(function () { setSubmitLoading(btn, false); });
        });
      }

      var prodPhoto    = document.getElementById('prodPhoto');
      var prodPhotoLbl = document.getElementById('prodPhotoLabel');
      var prodPhotoNm  = document.getElementById('prodPhotoName');
      if (prodPhoto && prodPhotoLbl && prodPhotoNm) {
        prodPhoto.addEventListener('change', function () {
          if (prodPhoto.files[0]) {
            prodPhotoNm.textContent = prodPhoto.files[0].name;
            prodPhotoLbl.classList.add('has-file');
          } else {
            prodPhotoNm.textContent = 'Вибрати фото';
            prodPhotoLbl.classList.remove('has-file');
          }
        });
      }

      var addForm = document.getElementById('productAddForm');
      if (addForm) {
        addForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var title = document.getElementById('prodTitle').value.trim();
          if (!title) { showToast('Введіть назву товару'); return; }
          var btn = addForm.querySelector('button[type="submit"]');
          setSubmitLoading(btn, true);
          var fd = new FormData();
          fd.append('title', title);
          var desc  = document.getElementById('prodDesc').value.trim();
          var price = document.getElementById('prodPrice').value.trim();
          if (desc)  fd.append('description', desc);
          if (price) fd.append('price', price);
          if (prodPhoto && prodPhoto.files[0]) fd.append('photo', prodPhoto.files[0]);

          apiUpload('POST', '/my/shop/products', fd, token)
            .then(function (res) {
              if (!res.ok) return res.json().then(function (d) {
                throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'error'));
              });
              return res.json();
            })
            .then(function (prod) {
              prod.shop = { id: myShopId, name: shop ? shop.name : '', user: user };
              loadedProds.unshift(prod);
              prodTotal += 1;
              renderPage();
              addForm.reset();
              if (prodPhotoNm) prodPhotoNm.textContent = 'Вибрати фото';
              if (prodPhotoLbl) prodPhotoLbl.classList.remove('has-file');
              loadMySidebar();
              showToast('✓ Товар додано!');
            })
            .catch(function (err) { showToast(err.message || 'Помилка'); })
            .finally(function () { setSubmitLoading(btn, false); });
        });
      }

    }

    /* load first page of products */
    loadMoreProds();

    if (!filterShopId) {
      loadMySidebar();
    } else if (user && token) {
      apiFetch('GET', '/my/shop', null, token)
        .then(function (r) { return r.json(); })
        .then(function (shop) {
          if (shop && shop.id) { myShopId = shop.id; renderPage(); }
        });
    }
  }

  /* ── Buy requests page ───────────────────────── */
  function initRequestsPage() {
    var wrap = document.getElementById('requestsWrap');
    if (!wrap) return;

    var user  = getCachedUser();
    var token = getToken();

    if (!user || !token) {
      wrap.innerHTML =
        '<div class="form-auth-notice">' +
          '<div class="form-auth-icon">&#128274;</div>' +
          '<p>Щоб переглянути запити, потрібно <a href="/auth">увійти</a></p>' +
        '</div>';
      return;
    }

    wrap.innerHTML = '<p class="admin-loading">Завантаження...</p>';

    function requestCardHtml(r) {
      var buyer    = r.buyer || {};
      var prod     = r.product || {};
      var name     = buyer.nickname || buyer.first_name || 'Покупець';
      var phone    = buyer.phone
        ? (String(buyer.phone).startsWith('0') ? '+38' + buyer.phone : '+380' + buyer.phone)
        : null;
      var date     = r.created_at ? fmtIsoDate(r.created_at.substring(0, 10)) : '';
      var prodThumb = prod.photo_path
        ? '<img class="request-card-thumb" src="' + photoSrc(prod.photo_path) + '" alt="' + escHtml(prod.title || '') + '" loading="lazy">'
        : '<div class="request-card-thumb request-card-thumb--empty">&#128717;</div>';
      var unread = !r.viewed_at;
      var badge = unread
        ? '<span class="request-unread-badge">&#9679; Новий</span>'
        : '<span class="request-viewed-badge">&#10003; Переглянуто</span>';
      var actionBtn = unread
        ? '<button class="btn-mark-viewed" data-id="' + r.id + '">&#10003; Позначити як переглянуто</button>'
        : '';
      return '<div class="request-card' + (unread ? ' request-card--unread' : '') + '" data-id="' + r.id + '">' +
        '<div class="request-card-status">' + badge + '</div>' +
        '<div class="request-card-product-row">' +
          prodThumb +
          '<div class="request-card-product-info">' +
            '<div class="request-card-product-title">' + escHtml(prod.title || '—') + '</div>' +
            (date ? '<div class="request-card-date">&#128197; ' + date + '</div>' : '') +
          '</div>' +
        '</div>' +
        '<div class="request-card-buyer">&#128100; ' + escHtml(name) + '</div>' +
        (phone ? '<div class="request-card-phone"><a href="tel:' + escHtml(phone) + '">&#128222; ' + escHtml(phone) + '</a></div>' : '') +
        (r.message ? '<div class="request-card-msg">&#128172; &laquo;' + escHtml(r.message) + '&raquo;</div>' : '') +
        (actionBtn ? '<div class="request-card-actions">' + actionBtn + '</div>' : '') +
      '</div>';
    }

    function bindRequestActions(scope, requests) {
      scope.querySelectorAll('.btn-mark-viewed').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
          var id = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          apiFetch('POST', '/my/shop/requests/' + id + '/view', {}, token)
            .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
            .then(function () {
              var r = requests.find(function (x) { return x.id === id; });
              if (r) r.viewed_at = new Date().toISOString();
              renderList();
              showToast('✓ Позначено як переглянуто');
            })
            .catch(function () { showToast('Помилка'); btn.disabled = false; });
        });
      });
      var btnAll = document.getElementById('btnMarkAllViewed');
      if (btnAll && !btnAll.dataset.bound) {
        btnAll.dataset.bound = '1';
        btnAll.addEventListener('click', function () {
          btnAll.disabled = true;
          apiFetch('POST', '/my/shop/requests/view-all', {}, token)
            .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
            .then(function () {
              requests.forEach(function (r) { if (!r.viewed_at) r.viewed_at = new Date().toISOString(); });
              renderList();
              showToast('✓ Усі позначено як переглянуті');
            })
            .catch(function () { showToast('Помилка'); btnAll.disabled = false; });
        });
      }
    }

    var loadedRequests = [];

    function renderList() {
      var unreadCount = loadedRequests.filter(function (r) { return !r.viewed_at; }).length;
      var countEl = document.getElementById('requestsCount');
      if (countEl) {
        countEl.textContent = loadedRequests.length
          ? loadedRequests.length + ' запит' + pluralUa(loadedRequests.length) +
            (unreadCount ? ' · ' + unreadCount + ' нов' + (unreadCount === 1 ? 'ий' : (unreadCount < 5 ? 'их' : 'их')) : '')
          : 'Немає запитів';
      }

      if (!loadedRequests.length) {
        wrap.innerHTML =
          '<div class="empty-state">' +
            '<div class="empty-icon">&#128276;</div>' +
            '<p>Запитів на покупку поки немає</p>' +
            '<p style="font-size:.85rem;color:var(--muted)">Коли покупець натисне «Бажаю купити», ви побачите його контакт тут</p>' +
          '</div>';
        return;
      }

      var toolbar = unreadCount
        ? '<div class="requests-toolbar"><button id="btnMarkAllViewed" class="btn-mark-all">&#10003; Позначити всі як переглянуті</button></div>'
        : '';
      wrap.innerHTML = toolbar + loadedRequests.map(requestCardHtml).join('');
      bindRequestActions(wrap, loadedRequests);
      initFadeIn();
    }

    apiFetch('GET', '/my/shop/requests', null, token)
      .then(function (res) { return res.json(); })
      .then(function (requests) {
        loadedRequests = requests || [];
        renderList();
      })
      .catch(function () {
        wrap.innerHTML = '<div class="empty-state"><p>Помилка завантаження</p></div>';
      });
  }

  /* ── Weather widget (Open-Meteo) ──────────────── */
  function initWeatherWidget() {
    var wrap = document.getElementById('weatherWidgetBody');
    if (!wrap) return;

    var WMO = {
      0:  ['&#9728;&#65039;',  'Ясно'],
      1:  ['&#127780;&#65039;', 'Переважно ясно'],
      2:  ['&#9925;',           'Мінлива хмарність'],
      3:  ['&#9729;',           'Похмуро'],
      45: ['&#127787;&#65039;', 'Туман'],
      48: ['&#127787;&#65039;', 'Туман (іній)'],
      51: ['&#127746;',         'Слабка мряка'],
      53: ['&#127746;',         'Помірна мряка'],
      55: ['&#127746;',         'Сильна мряка'],
      61: ['&#127783;&#65039;', 'Невеликий дощ'],
      63: ['&#127783;&#65039;', 'Помірний дощ'],
      65: ['&#127783;&#65039;', 'Сильний дощ'],
      71: ['&#127784;&#65039;', 'Слабкий снігопад'],
      73: ['&#127784;&#65039;', 'Помірний снігопад'],
      75: ['&#127784;&#65039;', 'Сильний снігопад'],
      77: ['&#127784;&#65039;', 'Крупа'],
      80: ['&#127746;',         'Зливи'],
      81: ['&#127746;',         'Помірні зливи'],
      82: ['&#127746;',         'Сильні зливи'],
      85: ['&#127784;&#65039;', 'Снігові зливи'],
      86: ['&#127784;&#65039;', 'Сильні снігові зливи'],
      95: ['&#9928;&#65039;',   'Гроза'],
      96: ['&#9928;&#65039;',   'Гроза з градом'],
      99: ['&#9928;&#65039;',   'Гроза з сильним градом'],
    };

    /* Борове, Зарічненський р-н, Рівненська обл. */
    var LAT = 51.62452, LON = 25.86599;

    fetch('https://api.open-meteo.com/v1/forecast' +
      '?latitude=' + LAT + '&longitude=' + LON +
      '&current=temperature_2m,apparent_temperature,weathercode,windspeed_10m,relativehumidity_2m,surface_pressure' +
      '&wind_speed_unit=kmh&timezone=Europe%2FKyiv')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var c    = data.current;
        var code = c.weathercode;
        var info = WMO[code] || ['&#127780;&#65039;', 'Змінна хмарність'];
        var temp = Math.round(c.temperature_2m);
        var feel = Math.round(c.apparent_temperature);
        var hpa  = Math.round(c.surface_pressure);
        var mmhg = Math.round(hpa * 0.750064);

        wrap.innerHTML =
          '<div class="weather-display">' +
            '<div class="weather-icon">' + info[0] + '</div>' +
            '<div class="weather-temp">' + (temp > 0 ? '+' : '') + temp + '°C</div>' +
            '<div class="weather-desc">' + info[1] + '</div>' +
            '<div class="weather-feel">Відчувається як ' + (feel > 0 ? '+' : '') + feel + '°C</div>' +
          '</div>' +
          '<div class="weather-grid">' +
            '<div class="w-item"><strong>Вологість</strong>' + c.relativehumidity_2m + '%</div>' +
            '<div class="w-item"><strong>Вітер</strong>' + Math.round(c.windspeed_10m) + '&nbsp;км/г</div>' +
            '<div class="w-item"><strong>Тиск</strong>' + mmhg + '&nbsp;мм</div>' +
          '</div>' +
          '<div class="weather-source"><a href="https://open-meteo.com" target="_blank" rel="noopener">Open-Meteo</a></div>';
      })
      .catch(function () {
        wrap.innerHTML = '<p class="weather-error">Не вдалося завантажити погоду</p>';
      });
  }

  /* ── Homepage products widget ─────────────────── */
  function initHomeProducts() {
    var grid = document.getElementById('homeProductGrid');
    if (!grid) return;
    apiFetch('GET', '/products?per_page=50')
      .then(function (res) { return res.json(); })
      .then(function (resp) {
        var data = resp.data || [];
        /* top product sidebar widget */
        renderTopProductWidget(data);

        var items = data.slice(0, 4);
        if (!items.length) {
          grid.innerHTML = '<div class="empty-state"><p>Товарів поки немає</p></div>';
          return;
        }
        grid.className = 'product-grid product-grid--home';
        grid.innerHTML = items.map(function (p) {
            var price = p.price != null
              ? '<span style="font-weight:700;color:var(--p)">' + Number(p.price).toLocaleString('uk-UA') + ' грн</span>'
              : '<span style="color:var(--muted);font-size:.78rem">за домовленістю</span>';
            var sid = p.shop_id || (p.shop && p.shop.id);
            var shopUrl = sid ? '/shop?shop=' + sid : '/shop';
            var imgHtml = p.photo_path
              ? '<img class="product-card-img product-card-img--clickable" src="' + photoSrc(p.photo_path) + '" alt="' + escHtml(p.title) + '" loading="lazy" data-src="' + photoSrc(p.photo_path) + '" data-alt="' + escHtml(p.title) + '">'
              : '<div class="product-card-img-placeholder">&#128717;</div>';
            return '<div class="product-card fade-in">' +
              imgHtml +
              '<div class="product-card-body">' +
                '<a href="' + shopUrl + '" style="text-decoration:none;color:inherit"><div class="product-card-title">' + escHtml(p.title) + '</div></a>' +
                price +
                '<div class="product-card-seller">&#128100; ' + sellerLink(p) + '</div>' +
              '</div>' +
            '</div>';
          }).join('');
        initFadeIn();
        grid.querySelectorAll('.product-card-img--clickable').forEach(function (img) {
          img.addEventListener('click', function () { openSingleLightbox(img.dataset.src, img.dataset.alt); });
        });
      })
      .catch(function () {
        grid.innerHTML = '<div class="empty-state"><p>—</p></div>';
      });
  }

  function renderTopProductWidget(data) {
    var widget  = document.getElementById('topProductWidget');
    var body    = document.getElementById('topProductBody');
    if (!widget || !body) return;

    var withReqs = data.filter(function (p) { return p.purchase_requests_count > 0; });
    if (!withReqs.length) return;

    withReqs.sort(function (a, b) { return b.purchase_requests_count - a.purchase_requests_count; });
    var p   = withReqs[0];
    var sid = p.shop_id || (p.shop && p.shop.id);
    var shopUrl = sid ? '/shop?shop=' + sid : '/shop';

    var count   = p.purchase_requests_count;
    var imgSrc  = p.photo_path ? photoSrc(p.photo_path) : null;
    var price   = p.price != null
      ? Number(p.price).toLocaleString('uk-UA') + ' грн'
      : 'за домовленістю';
    var priceClass = p.price != null ? 'top-product-price' : 'top-product-price top-product-price--free';
    var seller  = sellerName(p);

    body.innerHTML =
      '<a href="' + shopUrl + '" class="top-product-link">' +
        '<div class="top-product-thumb">' +
          (imgSrc
            ? '<img src="' + imgSrc + '" alt="' + escHtml(p.title) + '" loading="lazy">'
            : '<div class="top-product-no-img">&#128717;</div>') +
        '</div>' +
        '<div class="top-product-body">' +
          '<div class="top-product-title">' + escHtml(p.title) + '</div>' +
          '<div class="top-product-badge">&#128293; ' + count + ' запит' + pluralUa(count) + '</div>' +
          '<div class="top-product-footer">' +
            '<span class="' + priceClass + '">' + price + '</span>' +
            (seller ? '<span class="top-product-seller">&#128100; ' + escHtml(seller) + '</span>' : '') +
          '</div>' +
        '</div>' +
      '</a>';

    widget.style.display = '';
    initFadeIn();
  }

  /* ── ADMIN PAGE ──────────────────────────────── */
  function initAdminPage() {
    var gate  = document.getElementById('adminGate');
    var panel = document.getElementById('adminPanel');
    if (!gate && !panel) return;

    var user  = getCachedUser();
    var token = getToken();

    if (!user || !user.is_admin) {
      if (gate) gate.innerHTML =
        '<div class="form-auth-notice">' +
          '<div class="form-auth-icon">&#128274;</div>' +
          '<p>Доступ лише для адміністраторів. <a href="/auth">Увійти</a></p>' +
        '</div>';
      return;
    }

    gate.style.display  = 'none';
    panel.style.display = '';

    /* ── tabs ── */
    $$('.admin-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        $$('.admin-tab').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var name = tab.dataset.tab;
        $$('.admin-section').forEach(function (s) {
          s.style.display = s.id === 'tab' + name.charAt(0).toUpperCase() + name.slice(1) ? '' : 'none';
        });
        if (name === 'gallery') loadAlbums();
        if (name === 'moderation') loadPendingAlbums();
        if (name === 'profiles') loadProfileRequests();
      });
    });

    /* ════════════════ ARTICLES ════════════════ */
    var allArticles  = [];
    var artPage      = 0;
    var artTotal     = 0;
    var ART_PER_PAGE = 10;
    var artMode      = 'new';
    var editingArtId = null;

    function resetArticles() {
      allArticles = []; artPage = 0; artTotal = 0;
      var el = document.getElementById('adminArticleList');
      if (el) el.innerHTML = '<p class="admin-loading">Завантаження...</p>';
      var pag = document.getElementById('adminArticlePagination');
      if (pag) pag.innerHTML = '';
      loadMoreArticles();
    }

    function loadMoreArticles() {
      var pag = document.getElementById('adminArticlePagination');
      if (pag) pag.innerHTML = '<button class="btn-show-more btn-show-more--sm" disabled>Завантаження…</button>';
      apiFetch('GET', '/admin/articles?per_page=' + ART_PER_PAGE + '&page=' + (artPage + 1), null, token)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          artPage  = resp.current_page;
          artTotal = resp.total;
          allArticles = allArticles.concat(resp.data || []);
          renderArticleList();
        })
        .catch(function () {
          var el = document.getElementById('adminArticleList');
          if (el && !allArticles.length) el.innerHTML = '<p class="admin-loading">Помилка завантаження</p>';
          if (pag) pag.innerHTML = '';
        });
    }

    function renderArticleList() {
      var el  = document.getElementById('adminArticleList');
      var pag = document.getElementById('adminArticlePagination');
      if (!el) return;
      if (!allArticles.length) {
        el.innerHTML = '<div class="empty-state"><p>Статей ще немає</p></div>';
        if (pag) pag.innerHTML = '';
        return;
      }
      el.innerHTML = allArticles.map(function (a) {
        return '<div class="admin-row" data-id="' + a.id + '">' +
          '<div class="admin-row-info">' +
            '<div class="admin-row-title">' + escHtml(a.title) + '</div>' +
            '<div class="admin-row-meta">' + escHtml(a.category) + ' &middot; ' + fmtIsoDate(a.published_at) + ' &middot; ' + (a.views || 0) + ' перегл.</div>' +
          '</div>' +
          '<div class="admin-row-actions">' +
            '<button class="btn-admin-act edit" title="Редагувати">&#9998;</button>' +
            '<button class="btn-admin-act del"  title="Видалити">&#128465;</button>' +
          '</div>' +
        '</div>';
      }).join('');

      el.querySelectorAll('.admin-row').forEach(function (row) {
        var id = parseInt(row.dataset.id, 10);
        row.querySelector('.btn-admin-act.edit').addEventListener('click', function () { editArticle(id); });
        row.querySelector('.btn-admin-act.del').addEventListener('click', function () {
          if (!confirm('Видалити статтю "' + escHtml(allArticles.find(function(x){return x.id===id;}).title) + '"?')) return;
          apiFetch('DELETE', '/admin/articles/' + id, null, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () { showToast('✓ Статтю видалено'); resetArticles(); })
            .catch(function () { showToast('Помилка видалення'); });
        });
      });

      if (!pag) return;
      var remaining = artTotal - allArticles.length;
      if (remaining > 0) {
        pag.innerHTML = '<button class="btn-show-more btn-show-more--sm">Показати ще</button>';
        pag.querySelector('.btn-show-more').addEventListener('click', loadMoreArticles);
      } else {
        pag.innerHTML = '';
      }
    }

    var artImageRemoved = false;

    function refreshArtImagePreview(article) {
      var box     = document.getElementById('artImageCurrent');
      var img     = document.getElementById('artImageCurrentPreview');
      if (!box || !img) return;
      if (article && article.image_path && !artImageRemoved) {
        img.src = '/storage/' + article.image_path;
        box.style.display = '';
      } else {
        img.removeAttribute('src');
        box.style.display = 'none';
      }
    }

    function editArticle(id) {
      var a = allArticles.find(function (x) { return x.id === id; });
      if (!a) return;
      artMode      = 'edit';
      editingArtId = id;
      artImageRemoved = false;
      document.getElementById('articleFormTitle').textContent = '✏️ Редагувати статтю';
      document.getElementById('articleId').value      = id;
      document.getElementById('artTitle').value       = a.title;
      document.getElementById('artCategory').value    = a.category;
      document.getElementById('artAuthor').value      = a.author;
      document.getElementById('artDate').value        = a.published_at ? String(a.published_at).substring(0, 10) : '';
      document.getElementById('artImageSeed').value   = a.image_seed || '';
      document.getElementById('artSummary').value     = a.summary;
      document.getElementById('artBody').value        = a.body;
      var artImgInp = document.getElementById('artImage');
      var artImgNm  = document.getElementById('artImageName');
      var artImgLbl = document.getElementById('artImageLabel');
      if (artImgInp) artImgInp.value = '';
      if (artImgNm)  artImgNm.textContent = 'Вибрати фото';
      if (artImgLbl) artImgLbl.classList.remove('has-file');
      refreshArtImagePreview(a);
      document.getElementById('articleFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetArticleForm() {
      artMode      = 'new';
      editingArtId = null;
      artImageRemoved = false;
      document.getElementById('articleFormTitle').textContent = '📝 Нова стаття';
      document.getElementById('articleId').value = '';
      document.getElementById('articleForm').reset();
      var artImgNm  = document.getElementById('artImageName');
      var artImgLbl = document.getElementById('artImageLabel');
      if (artImgNm)  artImgNm.textContent = 'Вибрати фото';
      if (artImgLbl) artImgLbl.classList.remove('has-file');
      refreshArtImagePreview(null);
    }

    var artImgInp = document.getElementById('artImage');
    var artImgLbl = document.getElementById('artImageLabel');
    var artImgNm  = document.getElementById('artImageName');
    if (artImgInp && artImgLbl && artImgNm) {
      artImgInp.addEventListener('change', function () {
        if (artImgInp.files[0]) {
          artImgNm.textContent = artImgInp.files[0].name;
          artImgLbl.classList.add('has-file');
          artImageRemoved = false;
        } else {
          artImgNm.textContent = 'Вибрати фото';
          artImgLbl.classList.remove('has-file');
        }
      });
    }
    var artImgRm = document.getElementById('artImageRemove');
    if (artImgRm) {
      artImgRm.addEventListener('click', function () {
        artImageRemoved = true;
        document.getElementById('artImageCurrent').style.display = 'none';
        if (artImgInp) artImgInp.value = '';
        if (artImgNm)  artImgNm.textContent = 'Вибрати фото';
        if (artImgLbl) artImgLbl.classList.remove('has-file');
      });
    }

    var artForm = document.getElementById('articleForm');
    if (artForm) {
      artForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var title    = document.getElementById('artTitle').value.trim();
        var category = document.getElementById('artCategory').value.trim();
        var author   = document.getElementById('artAuthor').value.trim();
        var summary  = document.getElementById('artSummary').value.trim();
        var body     = document.getElementById('artBody').value.trim();
        if (!title || !category || !author || !summary || !body) {
          showToast('Заповніть усі обов\'язкові поля');
          return;
        }
        var fd = new FormData();
        fd.append('title',    title);
        fd.append('category', category);
        fd.append('author',   author);
        fd.append('summary',  summary);
        fd.append('body',     body);
        var seed = document.getElementById('artImageSeed').value.trim();
        if (seed) fd.append('image_seed', seed);
        var pubDate = document.getElementById('artDate').value;
        if (pubDate) fd.append('published_at', pubDate);
        if (artImgInp && artImgInp.files[0]) fd.append('image', artImgInp.files[0]);
        if (artMode === 'edit' && artImageRemoved) fd.append('remove_image', '1');
        if (artMode === 'edit') fd.append('_method', 'PUT');

        var btn = artForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        var path = artMode === 'edit' ? '/admin/articles/' + editingArtId : '/admin/articles';
        apiUpload('POST', path, fd, token)
          .then(function (res) {
            if (!res.ok) return res.json().then(function (d) {
              throw new Error(d.errors ? Object.values(d.errors)[0][0] : (d.message || 'error'));
            });
            return res.json();
          })
          .then(function () {
            showToast(artMode === 'edit' ? '✓ Статтю оновлено' : '✓ Статтю додано');
            resetArticleForm();
            resetArticles();
          })
          .catch(function (err) { showToast(err.message || 'Помилка збереження'); })
          .finally(function () { btn.disabled = false; });
      });
    }

    var btnNewArt = document.getElementById('btnNewArticle');
    if (btnNewArt) btnNewArt.addEventListener('click', function () {
      resetArticleForm();
      document.getElementById('articleFormCard').scrollIntoView({ behavior: 'smooth' });
    });

    var btnCancelArt = document.getElementById('btnCancelArticle');
    if (btnCancelArt) btnCancelArt.addEventListener('click', resetArticleForm);

    resetArticles();

    /* ════════════════ GALLERY ════════════════ */
    var allAlbums        = [];
    var adminAlbumPage   = 0;
    var adminAlbumTotal  = 0;
    var ADMIN_ALB_PER_PG = 10;
    var selectedAlbumId  = null;
    var selectedAlbum    = null;
    var currentPhotos    = [];

    var pendingAlbums = [];
    var pendingPage   = 0;
    var pendingTotal  = 0;
    var PENDING_PER_PAGE = 20;

    function pendingCardHtml(a) {
      var submitter = a.user
        ? escHtml(a.user.nickname || a.user.first_name || 'Користувач')
        : 'Невідомо';
      var thumbs = (a.photos || []).map(function (p) {
        var src = p.file_path ? '/storage/' + p.file_path : 'https://picsum.photos/seed/' + encodeURIComponent(p.image_seed || '') + '/160/160';
        return '<img class="pending-card-thumb" src="' + src + '" alt="" loading="lazy" data-full="' + src.replace(/\/160\/160$/, '/1400/1000') + '">';
      }).join('');
      var desc = a.description
        ? '<p class="pending-card-desc">' + escHtml(a.description) + '</p>'
        : '';
      return '<div class="pending-card" data-id="' + a.id + '">' +
        '<div class="pending-card-header">' +
          '<div class="pending-card-info">' +
            '<div class="pending-card-title">' + escHtml(a.title) + '</div>' +
            '<div class="pending-card-meta">&#128100; ' + submitter +
              ' &nbsp;&middot;&nbsp; &#128197; ' + fmtIsoDate(a.album_date) +
              ' &nbsp;&middot;&nbsp; &#128247; ' + (a.photos_count || 0) + ' фото' +
            '</div>' +
          '</div>' +
        '</div>' +
        (thumbs ? '<div class="pending-card-photos">' + thumbs + '</div>' : '') +
        desc +
        '<div class="pending-card-actions">' +
          '<button class="btn-publish" data-id="' + a.id + '">&#10003; Опублікувати</button>' +
          '<button class="btn-reject"  data-id="' + a.id + '">&#10005; Відхилити</button>' +
        '</div>' +
      '</div>';
    }

    function bindPendingActions(scope) {
      scope.querySelectorAll('.btn-publish').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
          var id = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          apiFetch('POST', '/admin/albums/' + id + '/publish', {}, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              showToast('✓ Альбом опубліковано');
              loadPendingAlbums();
              loadAlbums();
            })
            .catch(function () { showToast('Помилка'); btn.disabled = false; });
        });
      });
      scope.querySelectorAll('.btn-reject').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
          if (!confirm('Відхилити та видалити цей альбом?')) return;
          var id = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          apiFetch('DELETE', '/admin/albums/' + id, null, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              showToast('Альбом відхилено');
              loadPendingAlbums();
            })
            .catch(function () { showToast('Помилка'); btn.disabled = false; });
        });
      });
      scope.querySelectorAll('.pending-card-thumb').forEach(function (img) {
        if (img.dataset.bound) return;
        img.dataset.bound = '1';
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () {
          openSingleLightbox(img.dataset.full || img.src, img.alt || '');
        });
      });
    }

    function renderPendingPagination() {
      var pag = document.getElementById('adminPendingPagination');
      if (!pag) return;
      var remaining = pendingTotal - pendingAlbums.length;
      if (remaining > 0) {
        pag.innerHTML = '<button class="btn-show-more">+ Показати ще ' + Math.min(remaining, PENDING_PER_PAGE) + ' альбом' + pluralUa(Math.min(remaining, PENDING_PER_PAGE)) + '</button>';
        pag.querySelector('.btn-show-more').addEventListener('click', loadMorePending);
      } else {
        pag.innerHTML = '';
      }
    }

    function loadPendingAlbums() {
      pendingAlbums = [];
      pendingPage   = 0;
      pendingTotal  = 0;
      var list = document.getElementById('adminPendingList');
      if (list) list.innerHTML = '<p class="admin-loading">Завантаження...</p>';
      var pag = document.getElementById('adminPendingPagination');
      if (pag) pag.innerHTML = '';
      loadMorePending();
    }

    function loadMorePending() {
      var list   = document.getElementById('adminPendingList');
      var badge  = document.getElementById('pendingBadge');
      var tabBtn = document.getElementById('tabModerationBtn');
      if (!list) return;
      var pag = document.getElementById('adminPendingPagination');
      if (pag) pag.innerHTML = '<button class="btn-show-more" disabled>Завантаження…</button>';

      apiFetch('GET', '/admin/albums/pending?per_page=' + PENDING_PER_PAGE + '&page=' + (pendingPage + 1), null, token)
        .then(function (res) {
          if (!res.ok) throw new Error('http');
          return res.json();
        })
        .then(function (resp) {
          pendingPage  = resp.current_page || 1;
          pendingTotal = resp.total || 0;
          var items = resp.data || [];
          if (badge) badge.textContent = pendingTotal || '';

          if (!pendingAlbums.length && !items.length) {
            list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128247;</div><p>Немає альбомів на модерацію</p></div>';
            if (pag) pag.innerHTML = '';
            return;
          }

          if (!pendingAlbums.length) list.innerHTML = '';
          pendingAlbums = pendingAlbums.concat(items);
          list.insertAdjacentHTML('beforeend', items.map(pendingCardHtml).join(''));
          bindPendingActions(list);
          renderPendingPagination();
        })
        .catch(function () {
          if (tabBtn) tabBtn.style.display = 'none';
          if (list) list.innerHTML = '<p class="admin-loading">Помилка завантаження</p>';
          if (pag) pag.innerHTML = '';
        });
    }

    /* ── Profile change moderation ─────────────────── */
    var profileRequests = [];
    var profileReqPage  = 0;
    var profileReqTotal = 0;
    var PROFILE_REQ_PER_PAGE = 20;

    var FIELD_LABELS = {
      first_name: "Ім'я",
      last_name:  'Прізвище',
      patronymic: 'По батькові',
      street:     'Вулиця',
      nickname:   'Нікнейм',
      phone:      'Телефон'
    };

    function fmtFieldValue(key, val) {
      if (val == null || val === '') return '<em class="text-muted">—</em>';
      if (key === 'phone') {
        var s = String(val);
        return escHtml(s.charAt(0) === '0' ? '+38' + s : '+380' + s);
      }
      return escHtml(String(val));
    }

    function profileRequestCardHtml(req) {
      var u = req.user || {};
      var fullName = [u.last_name, u.first_name, u.patronymic].filter(Boolean).join(' ') || u.nickname || ('Користувач #' + (u.id || ''));
      var diffRows = '';
      var payload = req.payload || {};

      Object.keys(payload).forEach(function (k) {
        if (!FIELD_LABELS[k]) return;
        diffRows +=
          '<tr>' +
            '<td class="prof-diff-label">' + escHtml(FIELD_LABELS[k]) + '</td>' +
            '<td class="prof-diff-old">' + fmtFieldValue(k, u[k]) + '</td>' +
            '<td class="prof-diff-arrow">&#8594;</td>' +
            '<td class="prof-diff-new">' + fmtFieldValue(k, payload[k]) + '</td>' +
          '</tr>';
      });

      var avatarBlock = '';
      if (req.avatar_path) {
        var currentAv = u.avatar_path
          ? '<img src="/storage/' + escHtml(u.avatar_path) + '" alt="Поточне фото" class="prof-av prof-av--current">'
          : '<div class="prof-av prof-av--empty">' + escHtml(((u.first_name || u.nickname || '?')[0] || '?').toUpperCase()) + '</div>';
        avatarBlock =
          '<div class="prof-avatar-diff">' +
            '<div class="prof-avatar-diff-col"><div class="prof-avatar-diff-cap">Поточне</div>' + currentAv + '</div>' +
            '<div class="prof-diff-arrow">&#8594;</div>' +
            '<div class="prof-avatar-diff-col"><div class="prof-avatar-diff-cap">Запропоноване</div>' +
              '<img src="/storage/' + escHtml(req.avatar_path) + '" alt="Нове фото" class="prof-av prof-av--new">' +
            '</div>' +
          '</div>';
      }

      var diffTable = diffRows
        ? '<table class="prof-diff-table"><tbody>' + diffRows + '</tbody></table>'
        : (req.avatar_path ? '' : '<p class="text-muted">Немає змін</p>');

      var date = req.created_at ? fmtIsoDate(String(req.created_at).substring(0, 10)) : '';

      return '<div class="prof-req-card" data-id="' + req.id + '">' +
        '<div class="prof-req-header">' +
          '<div>' +
            '<div class="prof-req-name">&#128100; ' + escHtml(fullName) + '</div>' +
            (u.nickname ? '<div class="prof-req-nick">@' + escHtml(u.nickname) + '</div>' : '') +
          '</div>' +
          (date ? '<div class="prof-req-date">&#128197; ' + date + '</div>' : '') +
        '</div>' +
        avatarBlock +
        diffTable +
        '<div class="pending-card-actions">' +
          '<button class="btn-publish" data-action="approve" data-id="' + req.id + '">&#10003; Схвалити</button>' +
          '<button class="btn-reject"  data-action="reject"  data-id="' + req.id + '">&#10005; Відхилити</button>' +
        '</div>' +
      '</div>';
    }

    function bindProfileReqActions(scope) {
      scope.querySelectorAll('.prof-req-card .btn-publish, .prof-req-card .btn-reject').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
          var id = parseInt(btn.dataset.id, 10);
          var action = btn.dataset.action;
          if (action === 'reject' && !confirm('Відхилити зміни користувача?')) return;
          btn.disabled = true;
          var path = '/admin/profile-requests/' + id + '/' + action;
          apiFetch('POST', path, {}, token)
            .then(function (res) {
              if (!res.ok) return res.json().then(function (d) { throw new Error(d.message || 'error'); });
              return res.json();
            })
            .then(function () {
              showToast(action === 'approve' ? '✓ Зміни схвалено' : 'Зміни відхилено');
              loadProfileRequests();
            })
            .catch(function (err) {
              showToast(err.message || 'Помилка');
              btn.disabled = false;
            });
        });
      });
      scope.querySelectorAll('.prof-av').forEach(function (img) {
        if (img.dataset.bound || img.tagName !== 'IMG') return;
        img.dataset.bound = '1';
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () { openSingleLightbox(img.src, img.alt || ''); });
      });
    }

    function renderProfileReqPagination() {
      var pag = document.getElementById('adminProfilesPagination');
      if (!pag) return;
      var remaining = profileReqTotal - profileRequests.length;
      if (remaining > 0) {
        pag.innerHTML = '<button class="btn-show-more">+ Показати ще ' + Math.min(remaining, PROFILE_REQ_PER_PAGE) + '</button>';
        pag.querySelector('.btn-show-more').addEventListener('click', loadMoreProfileReq);
      } else {
        pag.innerHTML = '';
      }
    }

    function loadProfileRequests() {
      profileRequests = [];
      profileReqPage  = 0;
      profileReqTotal = 0;
      var list = document.getElementById('adminProfilesList');
      if (list) list.innerHTML = '<p class="admin-loading">Завантаження...</p>';
      var pag = document.getElementById('adminProfilesPagination');
      if (pag) pag.innerHTML = '';
      loadMoreProfileReq();
    }

    function loadMoreProfileReq() {
      var list   = document.getElementById('adminProfilesList');
      var badge  = document.getElementById('profilesBadge');
      var tabBtn = document.getElementById('tabProfilesBtn');
      if (!list) return;
      var pag = document.getElementById('adminProfilesPagination');
      if (pag) pag.innerHTML = '<button class="btn-show-more" disabled>Завантаження…</button>';

      apiFetch('GET', '/admin/profile-requests?per_page=' + PROFILE_REQ_PER_PAGE + '&page=' + (profileReqPage + 1), null, token)
        .then(function (res) {
          if (!res.ok) throw new Error('http');
          return res.json();
        })
        .then(function (resp) {
          profileReqPage  = resp.current_page || 1;
          profileReqTotal = resp.total || 0;
          var items = resp.data || [];
          if (badge) badge.textContent = profileReqTotal || '';

          if (!profileRequests.length && !items.length) {
            list.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128100;</div><p>Немає запитів на зміну профілю</p></div>';
            if (pag) pag.innerHTML = '';
            return;
          }

          if (!profileRequests.length) list.innerHTML = '';
          profileRequests = profileRequests.concat(items);
          list.insertAdjacentHTML('beforeend', items.map(profileRequestCardHtml).join(''));
          bindProfileReqActions(list);
          renderProfileReqPagination();
        })
        .catch(function () {
          if (tabBtn) tabBtn.style.display = 'none';
          if (list) list.innerHTML = '<p class="admin-loading">Помилка завантаження</p>';
          if (pag) pag.innerHTML = '';
        });
    }

function resetAlbums() {
      allAlbums = []; adminAlbumPage = 0; adminAlbumTotal = 0;
      var el = document.getElementById('adminAlbumList');
      if (el) el.innerHTML = '<p class="admin-loading">Завантаження...</p>';
      var pag = document.getElementById('adminAlbumPagination');
      if (pag) pag.innerHTML = '';
      loadMoreAdminAlbums();
    }

    function loadMoreAdminAlbums() {
      var pag = document.getElementById('adminAlbumPagination');
      if (pag) pag.innerHTML = '<button class="btn-show-more btn-show-more--sm" disabled>Завантаження…</button>';
      apiFetch('GET', '/albums?per_page=' + ADMIN_ALB_PER_PG + '&page=' + (adminAlbumPage + 1), null, token)
        .then(function (res) { return res.json(); })
        .then(function (resp) {
          adminAlbumPage  = resp.current_page;
          adminAlbumTotal = resp.total;
          allAlbums = allAlbums.concat(resp.data || []);
          renderAlbumList();
        })
        .catch(function () {
          var el = document.getElementById('adminAlbumList');
          if (el && !allAlbums.length) el.innerHTML = '<p class="admin-loading">Помилка завантаження</p>';
          if (pag) pag.innerHTML = '';
        });
    }

    /* alias used by tab-switch and post-publish callbacks */
    function loadAlbums() { resetAlbums(); }

    function renderAlbumList() {
      var el  = document.getElementById('adminAlbumList');
      var pag = document.getElementById('adminAlbumPagination');
      if (!el) return;
      if (!allAlbums.length) {
        el.innerHTML = '<div class="empty-state"><p>Альбомів ще немає</p></div>';
        if (pag) pag.innerHTML = '';
        return;
      }
      el.innerHTML = allAlbums.map(function (a) {
        var sel = selectedAlbumId === a.id ? ' selected' : '';
        return '<div class="admin-row' + sel + '" data-id="' + a.id + '">' +
          '<div class="admin-row-info">' +
            '<div class="admin-row-title">' + escHtml(a.title) + '</div>' +
            '<div class="admin-row-meta">' + fmtIsoDate(a.album_date) + ' &middot; ' + (a.photos_count || 0) + ' фото</div>' +
          '</div>' +
          '<div class="admin-row-actions">' +
            '<button class="btn-admin-act photo" title="Фото">&#128247;</button>' +
            '<button class="btn-admin-act del"   title="Видалити">&#128465;</button>' +
          '</div>' +
        '</div>';
      }).join('');

      el.querySelectorAll('.admin-row').forEach(function (row) {
        var id = parseInt(row.dataset.id, 10);
        row.querySelector('.btn-admin-act.photo').addEventListener('click', function () { openPhotoManager(id); });
        row.querySelector('.btn-admin-act.del').addEventListener('click', function () {
          var alb = allAlbums.find(function (x) { return x.id === id; });
          if (!confirm('Видалити альбом "' + escHtml(alb ? alb.title : '') + '" і всі його фото?')) return;
          apiFetch('DELETE', '/admin/albums/' + id, null, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              showToast('✓ Альбом видалено');
              if (selectedAlbumId === id) closePhotoManager();
              resetAlbums();
            })
            .catch(function () { showToast('Помилка видалення'); });
        });
      });

      if (!pag) return;
      var remaining = adminAlbumTotal - allAlbums.length;
      if (remaining > 0) {
        pag.innerHTML = '<button class="btn-show-more btn-show-more--sm">Показати ще</button>';
        pag.querySelector('.btn-show-more').addEventListener('click', loadMoreAdminAlbums);
      } else {
        pag.innerHTML = '';
      }
    }

    function openPhotoManager(albumId) {
      selectedAlbumId = albumId;
      selectedAlbum   = allAlbums.find(function (a) { return a.id === albumId; }) || null;
      var alb = selectedAlbum;
      document.getElementById('photoManagerTitle').textContent = '📷 ' + (alb ? alb.title : '');
      document.getElementById('uploadAlbumId').value = albumId;
      document.getElementById('photoManagerCard').style.display = '';
      document.getElementById('galleryPlaceholder').style.display = 'none';
      loadAlbumPhotos(albumId);
      renderAlbumList();
      document.getElementById('photoManagerCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closePhotoManager() {
      selectedAlbumId = null;
      selectedAlbum   = null;
      currentPhotos   = [];
      document.getElementById('photoManagerCard').style.display = 'none';
      document.getElementById('galleryPlaceholder').style.display = '';
      renderAlbumList();
    }

    function loadAlbumPhotos(albumId) {
      var grid = document.getElementById('adminPhotoGrid');
      var alb  = allAlbums.find(function (a) { return a.id === albumId; });
      if (!alb || !grid) return;
      grid.innerHTML = '<p class="admin-loading" style="grid-column:1/-1">Завантаження...</p>';
      apiFetch('GET', '/albums/' + alb.slug, null, token)
        .then(function (res) { return res.json(); })
        .then(function (data) { currentPhotos = data.photos || []; renderPhotoGrid(); })
        .catch(function () { grid.innerHTML = '<p class="admin-loading" style="grid-column:1/-1">Помилка</p>'; });
    }

    function renderPhotoGrid() {
      var grid = document.getElementById('adminPhotoGrid');
      if (!grid) return;
      if (!currentPhotos.length) {
        grid.innerHTML = '<p class="admin-loading" style="grid-column:1/-1">Фото ще немає — завантажте перше!</p>';
        return;
      }
      var coverPath = selectedAlbum ? selectedAlbum.cover_path : null;
      grid.innerHTML = currentPhotos.map(function (p) {
        var isCover = !!(coverPath && p.file_path && p.file_path === coverPath);
        return '<div class="admin-photo-item' + (isCover ? ' is-cover' : '') + '">' +
          '<img src="' + photoUrl(p, 300, 200) + '" alt="' + escHtml(p.caption || '') + '" loading="lazy">' +
          (p.caption ? '<div class="admin-photo-cap">' + escHtml(p.caption) + '</div>' : '') +
          '<button class="admin-photo-cover' + (isCover ? ' is-cover' : '') + '" data-id="' + p.id + '" title="Зробити обкладинкою">&#9733;</button>' +
          '<button class="admin-photo-del" data-id="' + p.id + '" title="Видалити">&#128465;</button>' +
        '</div>';
      }).join('');

      grid.querySelectorAll('.admin-photo-cover').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var photoId = parseInt(btn.dataset.id, 10);
          btn.disabled = true;
          apiFetch('POST', '/admin/albums/' + selectedAlbumId + '/cover', { photo_id: photoId }, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              var photo = currentPhotos.find(function (p) { return p.id === photoId; });
              if (photo && selectedAlbum) {
                selectedAlbum.cover_path = photo.file_path;
                var idx = allAlbums.findIndex(function (a) { return a.id === selectedAlbumId; });
                if (idx >= 0) allAlbums[idx].cover_path = photo.file_path;
              }
              renderPhotoGrid();
              showToast('✓ Обкладинку оновлено');
            })
            .catch(function () { showToast('Помилка'); btn.disabled = false; });
        });
      });

      grid.querySelectorAll('.admin-photo-del').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Видалити фото?')) return;
          var photoId = parseInt(btn.dataset.id, 10);
          apiFetch('DELETE', '/admin/photos/' + btn.dataset.id, null, token)
            .then(function (res) { if (!res.ok) throw new Error(); })
            .then(function () {
              showToast('✓ Фото видалено');
              currentPhotos = currentPhotos.filter(function (p) { return p.id !== photoId; });
              if (selectedAlbum && selectedAlbum.cover_path) {
                var stillExists = currentPhotos.some(function (p) { return p.file_path === selectedAlbum.cover_path; });
                if (!stillExists) { selectedAlbum.cover_path = null; }
              }
              renderPhotoGrid();
              loadAlbums();
            })
            .catch(function () { showToast('Помилка видалення'); });
        });
      });
    }

    /* Album creation form */
    var btnShowAlbumForm = document.getElementById('btnShowAlbumForm');
    if (btnShowAlbumForm) {
      btnShowAlbumForm.addEventListener('click', function () {
        var card = document.getElementById('albumFormCard');
        card.style.display = card.style.display === 'none' ? '' : 'none';
      });
    }
    var btnCancelAlbum = document.getElementById('btnCancelAlbum');
    if (btnCancelAlbum) {
      btnCancelAlbum.addEventListener('click', function () {
        document.getElementById('albumFormCard').style.display = 'none';
        document.getElementById('albumForm').reset();
      });
    }

    var albumForm = document.getElementById('albumForm');
    if (albumForm) {
      albumForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var title = document.getElementById('albumTitle').value.trim();
        if (!title) { showToast('Введіть назву альбому'); return; }
        var payload = {
          title:      title,
          cover_seed: document.getElementById('albumCoverSeed').value.trim() || undefined,
          album_date: document.getElementById('albumDate').value || undefined,
        };
        var btn = albumForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        apiFetch('POST', '/admin/albums', payload, token)
          .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
          .then(function () {
            showToast('✓ Альбом створено');
            albumForm.reset();
            document.getElementById('albumFormCard').style.display = 'none';
            loadAlbums();
          })
          .catch(function () { showToast('Помилка створення альбому'); })
          .finally(function () { btn.disabled = false; });
      });
    }

    /* Photo upload */
    var photoUploadForm = document.getElementById('photoUploadForm');
    if (photoUploadForm) {
      photoUploadForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var albumId   = document.getElementById('uploadAlbumId').value;
        var fileInput = document.getElementById('photoFile');
        if (!fileInput.files[0]) { showToast('Виберіть файл'); return; }
        var fd = new FormData();
        fd.append('photo', fileInput.files[0]);
        var caption = document.getElementById('photoCaption').value.trim();
        if (caption) fd.append('caption', caption);
        var btn = photoUploadForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = '⏳ Завантаження...';
        apiUpload('POST', '/admin/albums/' + albumId + '/photos', fd, token)
          .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
          .then(function (photo) {
            showToast('✓ Фото завантажено');
            photoUploadForm.reset();
            if (!currentPhotos.length && selectedAlbum && photo && photo.file_path) {
              selectedAlbum.cover_path = photo.file_path;
            }
            loadAlbumPhotos(parseInt(albumId, 10));
            loadAlbums();
          })
          .catch(function () { showToast('Помилка завантаження фото'); })
          .finally(function () { btn.disabled = false; btn.textContent = '📤 Завантажити'; });
      });
    }

    loadPendingAlbums();

    // Lightweight count for profiles tab badge (no full data fetch on init)
    apiFetch('GET', '/admin/profile-requests?per_page=1&page=1', null, token)
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (resp) {
        var badge = document.getElementById('profilesBadge');
        if (badge && resp) badge.textContent = resp.total || '';
      })
      .catch(function () {});
  }

  /* ── PROFILE PAGE ───────────────────────────── */
  function initProfilePage() {
    var gate  = document.getElementById('profileGate');
    var panel = document.getElementById('profilePanel');
    if (!gate || !panel) return;

    var token = getToken();
    var user  = getCachedUser();

    if (!token || !user) {
      window.location.replace('/auth');
      return;
    }

    gate.style.display  = 'none';
    panel.style.display = '';

    /* ensure only the first tab section is visible on load */
    $$('.profile-tab-section').forEach(function (s) { s.style.display = 'none'; });
    var firstTab = document.getElementById('ptabInfo');
    if (firstTab) firstTab.style.display = '';

    /* ── tab switching ── */
    $$('[data-ptab]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('[data-ptab]').forEach(function (b) { b.classList.remove('active'); });
        $$('.profile-tab-section').forEach(function (s) { s.style.display = 'none'; });
        btn.classList.add('active');
        var sec = document.getElementById('ptab' + btn.dataset.ptab.charAt(0).toUpperCase() + btn.dataset.ptab.slice(1));
        if (sec) sec.style.display = '';
        if (btn.dataset.ptab === 'logs') loadProfileLogs();
      });
    });

    /* ── avatar helpers ── */
    function renderAvatar(u) {
      var circle   = document.getElementById('profileAvatarCircle');
      var initials = document.getElementById('profileAvatarInitials');
      var img      = document.getElementById('profileAvatarImg');
      if (!circle) return;
      if (u.avatar_path) {
        img.src = '/storage/' + u.avatar_path;
        img.style.display = '';
        initials.style.display = 'none';
        circle.style.background = 'transparent';
      } else {
        img.style.display = 'none';
        initials.style.display = '';
        initials.textContent = ((u.first_name || u.nickname || '?')[0] || '?').toUpperCase();
        circle.style.background = '';
      }
    }

    function renderNames(u) {
      var nameEl = document.getElementById('profileDisplayName');
      var nickEl = document.getElementById('profileDisplayNick');
      if (nameEl) nameEl.textContent = [u.last_name, u.first_name].filter(Boolean).join(' ') || u.nickname || '';
      if (nickEl) nickEl.textContent = u.nickname ? '@' + u.nickname : '';
    }

    /* ── populate form ── */
    function populateForm(u) {
      var f = document.getElementById('pfLastName');   if (f) f.value = u.last_name   || '';
      var g = document.getElementById('pfFirstName');  if (g) g.value = u.first_name  || '';
      var h = document.getElementById('pfPatronymic'); if (h) h.value = u.patronymic  || '';
      var i = document.getElementById('pfNickname');   if (i) i.value = u.nickname    || '';
      var j = document.getElementById('pfStreet');     if (j) j.value = u.street      || '';
      var k = document.getElementById('pfPhone');      if (k) k.value = u.phone       || '';
    }

    /* ── pending change banner ── */
    var P_FIELD_LABELS = {
      first_name: "Ім'я", last_name: 'Прізвище', patronymic: 'По батькові',
      street: 'Вулиця', nickname: 'Нікнейм', phone: 'Телефон'
    };

    function renderPendingBanner(u) {
      var existing = document.getElementById('profilePendingBanner');
      if (existing) existing.remove();
      if (!u.pending_request) return;
      var keys = Object.keys(u.pending_request.payload || {});
      var fieldsList = keys.filter(function (k) { return P_FIELD_LABELS[k]; })
                           .map(function (k) { return P_FIELD_LABELS[k]; }).join(', ');
      var avatarNote = u.pending_request.avatar_path ? 'фото профілю' : '';
      var parts = [];
      if (fieldsList) parts.push(fieldsList);
      if (avatarNote) parts.push(avatarNote);
      var listHtml = parts.length
        ? '<div class="profile-pending-fields">' + escHtml(parts.join(' · ')) + '</div>'
        : '';

      var bannerHtml =
        '<div id="profilePendingBanner" class="profile-pending-banner">' +
          '<div class="profile-pending-icon">&#9203;</div>' +
          '<div class="profile-pending-body">' +
            '<div class="profile-pending-title">Зміни чекають модерації</div>' +
            '<div class="profile-pending-text">Адміністратор перегляне ваші зміни найближчим часом. До цього в профілі видно поточні дані.</div>' +
            listHtml +
          '</div>' +
        '</div>';
      var panel = document.getElementById('profilePanel');
      if (panel) panel.insertAdjacentHTML('afterbegin', bannerHtml);
    }

    /* ── load fresh profile from server ── */
    function loadProfile() {
      apiFetch('GET', '/profile', null, token)
        .then(function (r) { return r.json(); })
        .then(function (u) {
          saveSession(u, token);
          renderAvatar(u);
          renderNames(u);
          populateForm(u);
          renderPendingBanner(u);
        })
        .catch(function () { showToast('Помилка завантаження профілю'); });
    }

    loadProfile();

    /* ── avatar upload ── */
    var avatarInput = document.getElementById('avatarFileInput');
    if (avatarInput) {
      avatarInput.addEventListener('change', function () {
        var file = avatarInput.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('avatar', file);
        showToast('Завантаження...');
        apiUpload('POST', '/profile/avatar', fd, token)
          .then(function (r) { return r.json(); })
          .then(function (u) {
            saveSession(u, token);
            renderAvatar(u);
            renderNames(u);
            renderPendingBanner(u);
            showToast(u.pending_request && u.pending_request.avatar_path
              ? 'Фото відправлено на модерацію'
              : '✓ Фото оновлено');
            avatarInput.value = '';
          })
          .catch(function () { showToast('Помилка завантаження фото'); });
      });
    }

    /* ── profile info form ── */
    var profileForm = document.getElementById('profileForm');
    if (profileForm) {
      profileForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = profileForm.querySelector('[type="submit"]');
        btn.disabled = true;
        var body = {
          last_name:  document.getElementById('pfLastName').value.trim(),
          first_name: document.getElementById('pfFirstName').value.trim(),
          patronymic: document.getElementById('pfPatronymic').value.trim() || null,
          nickname:   document.getElementById('pfNickname').value.trim(),
          street:     document.getElementById('pfStreet').value.trim() || null,
          phone:      document.getElementById('pfPhone').value.trim(),
        };
        apiFetch('PATCH', '/profile', body, token)
          .then(function (r) {
            if (!r.ok) return r.json().then(function (d) { throw d; });
            return r.json();
          })
          .then(function (u) {
            saveSession(u, token);
            renderAvatar(u);
            renderNames(u);
            renderPendingBanner(u);
            showToast(u.pending_request && (u.pending_request.payload && Object.keys(u.pending_request.payload).length)
              ? 'Зміни відправлено на модерацію'
              : '✓ Профіль оновлено');
          })
          .catch(function (err) {
            var msg = (err && err.errors)
              ? Object.values(err.errors).flat().join(' ')
              : 'Помилка збереження';
            showToast(msg);
          })
          .finally(function () { btn.disabled = false; });
      });
    }

    /* ── password form ── */
    var passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
      passwordForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var newPwd     = document.getElementById('pfNewPwd').value;
        var confirmPwd = document.getElementById('pfNewPwdConfirm').value;
        if (newPwd !== confirmPwd) {
          showToast('Паролі не співпадають');
          return;
        }
        var btn = passwordForm.querySelector('[type="submit"]');
        btn.disabled = true;
        apiFetch('POST', '/profile/password', {
          current_password:      document.getElementById('pfCurrentPwd').value,
          password:              newPwd,
          password_confirmation: confirmPwd,
        }, token)
          .then(function (r) {
            if (!r.ok) return r.json().then(function (d) { throw d; });
            return r.json();
          })
          .then(function () {
            passwordForm.reset();
            showToast('✓ Пароль змінено');
          })
          .catch(function (err) {
            var msg = (err && err.errors)
              ? Object.values(err.errors).flat().join(' ')
              : 'Помилка зміни пароля';
            showToast(msg);
          })
          .finally(function () { btn.disabled = false; });
      });
    }

    /* ── activity log ── */
    var logsLoaded = false;
    function loadProfileLogs() {
      if (logsLoaded) return;
      var list = document.getElementById('profileLogList');
      if (!list) return;
      apiFetch('GET', '/profile/logs', null, token)
        .then(function (r) { return r.json(); })
        .then(function (logs) {
          logsLoaded = true;
          if (!logs.length) {
            list.innerHTML = '<p class="admin-loading">Журнал порожній</p>';
            return;
          }
          var actionLabels = {
            profile_updated:  '&#9998; Оновлено профіль',
            avatar_uploaded:  '&#128247; Фото профілю',
            password_changed: '&#128272; Зміна пароля',
          };
          list.innerHTML = logs.map(function (log) {
            var dt = log.created_at ? log.created_at.replace('T', ' ').substring(0, 16) : '';
            var icon = actionLabels[log.action] || log.action;
            return '<div class="profile-log-row">' +
              '<span class="profile-log-action">' + icon + '</span>' +
              '<span class="profile-log-desc">' + escHtml(log.description) + '</span>' +
              '<span class="profile-log-date">' + dt + '</span>' +
            '</div>';
          }).join('');
        })
        .catch(function () { showToast('Помилка завантаження журналу'); });
    }
  }

  /* ── DOMContentLoaded ────────────────────────── */
  function init() {
    initImgErrorFallback();
    initNav();
    initUserArea();
    initActiveLink();
    initHeaderDate();
    initSidebarWidgets();
    initAnnouncementsPage();
    initRidesPage();
    initFadeIn();
    initLightbox();
    initPhoneMasks();
    initAuthPage();
    initArticlePage();
    initAlbumPage();
    initGalleryPage();
    initIndexArticles();
    initArticlesListPage();
    initIndexAlbums();
    initShopPage();
    initRequestsPage();
    initHomeProducts();
    initWeatherWidget();
    initAdminPage();
    initProfilePage();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

}());
