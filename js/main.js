'use strict';
// ─────────────────────────────────────────────────
//  js/main.js — VØID Studio Scripts
//  UPDATE the API_URL below to match your server
// ─────────────────────────────────────────────────

const API_URL = './api/contact.php'; // ← adjust if needed

// ─── LOADER ──────────────────────────────────────
(function () {
  const loader  = document.getElementById('loader');
  const counter = document.getElementById('loader-count');
  if (!loader) return;

  let count = 0;
  const interval = setInterval(() => {
    count = Math.min(count + Math.floor(Math.random() * 15 + 5), 100);
    counter.textContent = String(count).padStart(3, '0');
    if (count >= 100) {
      clearInterval(interval);
      setTimeout(() => loader.classList.add('hidden'), 200);
    }
  }, 60);
})();

// ─── CUSTOM CURSOR ────────────────────────────────
(function () {
  const dot  = document.getElementById('cursor-dot');
  const ring = document.getElementById('cursor-ring');
  if (!dot || !ring) return;

  let mx = 0, my = 0, rx = 0, ry = 0;

  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    dot.style.left = mx + 'px';
    dot.style.top  = my + 'px';
  });

  (function lerp() {
    rx += (mx - rx) * 0.12;
    ry += (my - ry) * 0.12;
    ring.style.left = rx + 'px';
    ring.style.top  = ry + 'px';
    requestAnimationFrame(lerp);
  })();

  document.querySelectorAll('a, button, .project-card, .step, .service-item, .stat-card')
    .forEach(el => {
      el.addEventListener('mouseenter', () => document.body.classList.add('hovering'));
      el.addEventListener('mouseleave', () => document.body.classList.remove('hovering'));
    });
})();

// ─── SCROLL PROGRESS BAR ─────────────────────────
(function () {
  const bar = document.getElementById('progress');
  if (!bar) return;
  window.addEventListener('scroll', () => {
    const p = window.scrollY / (document.body.scrollHeight - window.innerHeight) * 100;
    bar.style.width = p + '%';
  }, { passive: true });
})();

// ─── NAV SCROLL SHRINK ───────────────────────────
(function () {
  const nav = document.getElementById('navbar');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });
})();

// ─── HAMBURGER MENU ──────────────────────────────
(function () {
  const btn  = document.getElementById('hamburger');
  const menu = document.getElementById('mobile-menu');
  if (!btn || !menu) return;

  btn.addEventListener('click', () => {
    btn.classList.toggle('open');
    menu.classList.toggle('open');
    document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
  });

  menu.querySelectorAll('.mob-link').forEach(link => {
    link.addEventListener('click', () => {
      btn.classList.remove('open');
      menu.classList.remove('open');
      document.body.style.overflow = '';
    });
  });
})();

// ─── SCROLL REVEAL ───────────────────────────────
(function () {
  const els = document.querySelectorAll('.reveal');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('revealed');
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  els.forEach(el => obs.observe(el));
})();

// ─── CONTACT FORM — real API call ────────────────
(function () {
  const form    = document.getElementById('contact-form');
  const success = document.getElementById('form-success');
  const errEl   = document.getElementById('form-error');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('.form-submit');

    // Client-side validation
    const name    = form.querySelector('#name').value.trim();
    const email   = form.querySelector('#email').value.trim();
    const message = form.querySelector('#message').value.trim();

    if (!name || !email || !message) {
      showError('Please fill in all required fields.');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError('Please enter a valid email address.');
      return;
    }

    // UI — loading state
    btn.textContent = 'Sending…';
    btn.disabled    = true;
    hideMessages();

    try {
      const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          email,
          project: form.querySelector('#project').value.trim(),
          message,
          website: '',  // honeypot (always empty from real users)
        }),
      });

      const data = await response.json();

      if (data.success) {
        form.reset();
        btn.style.display = 'none';
        if (success) {
          success.textContent = '✓ ' + data.message;
          success.style.display = 'block';
        }
      } else {
        showError(data.message || 'Something went wrong. Please try again.');
        btn.textContent = 'Send message';
        btn.disabled    = false;
      }
    } catch (err) {
      showError('Network error. Please check your connection and try again.');
      btn.textContent = 'Send message';
      btn.disabled    = false;
    }

    function showError(msg) {
      if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
    }
    function hideMessages() {
      if (errEl)   errEl.style.display   = 'none';
      if (success) success.style.display = 'none';
    }
  });
})();

// ─── MAGNETIC BUTTONS ────────────────────────────
(function () {
  document.querySelectorAll('.btn-primary, .btn-ghost, .form-submit').forEach(btn => {
    btn.addEventListener('mousemove', e => {
      const r  = btn.getBoundingClientRect();
      const dx = (e.clientX - (r.left + r.width  / 2)) * 0.25;
      const dy = (e.clientY - (r.top  + r.height / 2)) * 0.25;
      btn.style.transform = `translate(${dx}px, ${dy}px)`;
    });
    btn.addEventListener('mouseleave', () => {
      btn.style.transform = '';
    });
  });
})();

// ─── SMOOTH ANCHOR SCROLL ────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href');
    if (id === '#') return;
    const el = document.querySelector(id);
    if (!el) return;
    e.preventDefault();
    el.scrollIntoView({ behavior: 'smooth' });
  });
});

// ─── STAT COUNTER ANIMATION ──────────────────────
(function () {
  const stats = document.querySelectorAll('.stat-number');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el     = e.target;
      const raw    = el.textContent;
      const num    = parseInt(raw.replace(/\D/g, ''));
      const suffix = raw.replace(/[\d]/g, '').trim();
      let cur = 0;
      const inc = num / (1500 / 16);
      el.innerHTML = `0<span>${suffix}</span>`;
      const tick = setInterval(() => {
        cur = Math.min(cur + inc, num);
        el.innerHTML = `${Math.floor(cur)}<span>${suffix}</span>`;
        if (cur >= num) clearInterval(tick);
      }, 16);
      obs.unobserve(el);
    });
  }, { threshold: 0.5 });
  stats.forEach(s => obs.observe(s));
})();