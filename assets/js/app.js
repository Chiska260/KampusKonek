// KampusKonek front-end helpers (no libraries)
(function () {
  'use strict';

  // ---- mobile sidebar ----
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.getElementById('sidebar');
  var scrim = document.getElementById('scrim');
  function setMenu(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (scrim) scrim.hidden = !open;
    if (menuBtn) menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (menuBtn) menuBtn.addEventListener('click', function () { setMenu(!sidebar.classList.contains('open')); });
  if (scrim) scrim.addEventListener('click', function () { setMenu(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });

  // ---- flash messages ----
  document.querySelectorAll('.flash-close').forEach(function (b) {
    b.addEventListener('click', function () { b.parentElement.remove(); });
  });
  document.querySelectorAll('.flash-success').forEach(function (f) {
    setTimeout(function () { if (f.parentElement) f.remove(); }, 6000);
  });

  // ---- selects that submit their form on change (filters, task status) ----
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { if (el.form) el.form.submit(); });
  });

  // ---- confirm before risky actions ----
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // ---- school email check on login / sign up ----
  var emailInput = document.querySelector('[data-domain-check]');
  var hint = document.querySelector('[data-domain-hint]');
  if (emailInput && hint && window.KK) {
    var original = hint.textContent;
    var check = function () {
      var v = emailInput.value.trim().toLowerCase();
      var at = v.lastIndexOf('@');
      if (at < 0 || at === v.length - 1) {
        hint.textContent = original; hint.className = 'field-hint';
        emailInput.removeAttribute('aria-invalid');
        return;
      }
      var domain = v.slice(at + 1);
      if (window.KK.students.indexOf(domain) > -1) {
        hint.textContent = 'Student or officer account.'; hint.className = 'field-hint ok';
        emailInput.removeAttribute('aria-invalid');
      } else if (window.KK.admins.indexOf(domain) > -1) {
        hint.textContent = 'Faculty, adviser, or executive account.'; hint.className = 'field-hint ok';
        emailInput.removeAttribute('aria-invalid');
      } else {
        hint.textContent = 'That is not a school email. ' + original; hint.className = 'field-hint bad';
        emailInput.setAttribute('aria-invalid', 'true');
      }
    };
    emailInput.addEventListener('input', check);
    emailInput.addEventListener('blur', check);
    check();
  }

  // ---- tiny charts: <canvas data-chart='{"type":"doughnut|bars",...}'> ----
  function setupCanvas(c, w, h) {
    var dpr = window.devicePixelRatio || 1;
    c.width = w * dpr; c.height = h * dpr;
    c.style.width = w + 'px'; c.style.height = h + 'px';
    var ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    return ctx;
  }

  function doughnut(c, cfg) {
    var size = 190, ctx = setupCanvas(c, size, size);
    var cx = size / 2, cy = size / 2, r = 84, inner = 54;
    var total = cfg.values.reduce(function (a, b) { return a + b; }, 0);
    if (total === 0) {
      ctx.beginPath(); ctx.arc(cx, cy, (r + inner) / 2, 0, Math.PI * 2);
      ctx.lineWidth = r - inner; ctx.strokeStyle = '#E6EAF4'; ctx.stroke();
    } else {
      var start = -Math.PI / 2;
      cfg.values.forEach(function (v, i) {
        if (!v) return;
        var angle = v / total * Math.PI * 2;
        ctx.beginPath(); ctx.arc(cx, cy, (r + inner) / 2, start, start + angle);
        ctx.lineWidth = r - inner; ctx.strokeStyle = cfg.colors[i]; ctx.stroke();
        start += angle;
      });
    }
    ctx.fillStyle = '#222A52'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.font = '700 26px "Bricolage Grotesque", Figtree, sans-serif';
    ctx.fillText(total, cx, cy - 6);
    ctx.fillStyle = '#5B6486'; ctx.font = '500 12px Figtree, sans-serif';
    ctx.fillText(total === 1 ? 'item' : 'items', cx, cy + 14);
  }

  function bars(c, cfg) {
    var w = 260, h = 190, ctx = setupCanvas(c, w, h);
    var padTop = 22, padBottom = 12, gap = 10;
    var n = cfg.values.length, max = Math.max.apply(null, cfg.values.concat([1]));
    var bw = (w - gap * (n + 1)) / n, usable = h - padTop - padBottom;
    ctx.strokeStyle = '#DCE1EE'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(0, h - padBottom + .5); ctx.lineTo(w, h - padBottom + .5); ctx.stroke();
    ctx.textAlign = 'center'; ctx.font = '600 12px Figtree, sans-serif';
    cfg.values.forEach(function (v, i) {
      var bh = Math.max(v / max * usable, v ? 3 : 0), x = gap + i * (bw + gap), y = h - padBottom - bh;
      ctx.fillStyle = cfg.colors[i];
      ctx.beginPath();
      if (ctx.roundRect) { ctx.roundRect(x, y, bw, bh, [5, 5, 0, 0]); } else { ctx.rect(x, y, bw, bh); }
      ctx.fill();
      ctx.fillStyle = '#222A52';
      ctx.fillText(v, x + bw / 2, y - 6);
    });
  }

  document.querySelectorAll('canvas[data-chart]').forEach(function (c) {
    try {
      var cfg = JSON.parse(c.getAttribute('data-chart'));
      if (cfg.type === 'bars') bars(c, cfg); else doughnut(c, cfg);
    } catch (err) { /* chart is optional, the legend still shows the numbers */ }
  });
})();
