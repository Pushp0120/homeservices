// assets/js/main.js

// ── TOAST NOTIFICATIONS ──────────────────────────────────
function showToast(message, type = 'success', duration = 4000) {
  const container = document.getElementById('toast-container');
  const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
  const id = 'toast-' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${icons[type] || 'bi-bell-fill'}"></i> ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById(id);
  const toast = new bootstrap.Toast(el, { delay: duration });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

// ── SIDEBAR TOGGLE ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }
});

// ── AJAX HELPER ───────────────────────────────────────────
async function ajaxPost(url, data) {
  const formData = new FormData();
  for (const [k, v] of Object.entries(data)) formData.append(k, v);
  const res = await fetch(url, { method: 'POST', body: formData });
  return res.json();
}

// ── BOOKING SERVICES (dynamic add/remove) ─────────────────
// serviceRow counter – initialized here, can be reset per page
if (typeof window._serviceRowIdx === 'undefined') window._serviceRowIdx = 0;

function addServiceRow(serviceId, serviceName, servicePrice, qty = 1) {
  const tbody = document.getElementById('serviceRows');
  if (!tbody) return;
  const idx = window._serviceRowIdx++;
  const row = document.createElement('tr');
  row.setAttribute('data-row', idx);
  row.innerHTML = `
    <td>
      <input type="hidden" name="services[${idx}][id]" value="${serviceId}">
      <span>${serviceName}</span>
    </td>
    <td><input type="hidden" name="services[${idx}][price]" value="${servicePrice}">
        <span>₹${parseFloat(servicePrice).toLocaleString('en-IN')}</span>
    </td>
    <td><input type="number" name="services[${idx}][qty]" value="${qty}" min="1" max="99"
        class="form-control form-control-sm qty-input" style="width:80px" data-price="${servicePrice}"></td>
    <td class="subtotal-cell fw-semibold">
      ₹${(servicePrice * qty).toLocaleString('en-IN')}
    </td>
    <td><button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-row" data-row="${idx}">
      <i class="bi bi-trash"></i></button></td>`;
  tbody.appendChild(row);
  updateTotals();
}

document.addEventListener('input', e => {
  if (e.target.classList.contains('qty-input')) {
    const row = e.target.closest('tr');
    const price = parseFloat(e.target.dataset.price);
    const qty   = parseInt(e.target.value) || 1;
    row.querySelector('.subtotal-cell').textContent = '₹' +
      (price * qty).toLocaleString('en-IN');
    updateTotals();
  }
});

document.addEventListener('click', e => {
  if (e.target.closest('.remove-row')) {
    e.target.closest('tr').remove();
    updateTotals();
  }
});

function updateTotals() {
  const rows = document.querySelectorAll('#serviceRows tr');
  let subtotal = 0;
  rows.forEach(row => {
    const price = parseFloat(row.querySelector('.qty-input')?.dataset.price || 0);
    const qty   = parseInt(row.querySelector('.qty-input')?.value || 1);
    subtotal += price * qty;
  });
  const taxRate  = parseFloat(document.getElementById('taxRate')?.value || 0);
  const tax      = subtotal * taxRate / 100;
  const total    = subtotal + tax;
  const fmt = v => '₹' + Math.round(v).toLocaleString('en-IN');
  if (document.getElementById('subtotalDisplay')) document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
  if (document.getElementById('taxDisplay'))      document.getElementById('taxDisplay').textContent      = fmt(tax);
  if (document.getElementById('totalDisplay'))    document.getElementById('totalDisplay').textContent    = fmt(total);
}

// ── DATATABLE SEARCH ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('tableSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const val = this.value.toLowerCase();
      document.querySelectorAll('[data-searchable] tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
      });
    });
  }
});

// ── CONFIRM DELETE ────────────────────────────────────────
function confirmAction(message, callback) {
  if (confirm(message)) callback();
}

// ── RATING STARS ──────────────────────────────────────────
function initStarRating() {
  const container = document.querySelector('.star-rating');
  if (!container) return;
  const stars = container.querySelectorAll('input');
  stars.forEach(star => {
    star.addEventListener('change', () => {
      const ratingVal = document.getElementById('ratingValue');
      if (ratingVal) ratingVal.value = star.value;
    });
  });
}
document.addEventListener('DOMContentLoaded', initStarRating);

// ═══════════════════════════════════════════════════════════
// ANIMATIONS
// ═══════════════════════════════════════════════════════════

// ── 1. STAT NUMBER COUNTER ────────────────────────────────
// Animates numbers from 0 to their final value on page load
function animateCounter(el) {
  const target  = parseFloat(el.dataset.target || el.textContent.replace(/[^0-9.]/g, ''));
  const prefix  = el.dataset.prefix  || '';
  const suffix  = el.dataset.suffix  || '';
  const isFloat = String(target).includes('.');
  const duration = 1200;
  const start    = performance.now();

  function update(now) {
    const elapsed  = now - start;
    const progress = Math.min(elapsed / duration, 1);
    // Ease out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = target * eased;
    el.textContent = prefix + (isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString('en-IN')) + suffix;
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

// ── 2. INTERSECTION OBSERVER for scroll-triggered animations
function initScrollAnimations() {
  // Counter animation — triggers when stat cards enter viewport
  const counterEls = document.querySelectorAll('.stat-value');
  if (counterEls.length) {
    const counterObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
          entry.target.dataset.counted = '1';
          animateCounter(entry.target);
        }
      });
    }, { threshold: 0.5 });
    counterEls.forEach(el => {
      // Store original value as target
      el.dataset.target = el.textContent.replace(/[^0-9.]/g, '');
      counterObs.observe(el);
    });
  }

  // Scroll-reveal for elements with .reveal class (added dynamically below)
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    const revealObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          revealObs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    revealEls.forEach(el => revealObs.observe(el));
  }
}

// ── 3. BUTTON RIPPLE EFFECT ───────────────────────────────
function initRipple() {
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const rect   = btn.getBoundingClientRect();
    const size   = Math.max(rect.width, rect.height);
    const x      = e.clientX - rect.left - size / 2;
    const y      = e.clientY - rect.top  - size / 2;
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px`;
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
  });
}

// ── 4. FORM SUBMIT LOADING SPINNER ───────────────────────
function initFormLoading() {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn && !submitBtn.classList.contains('no-loading')) {
        setTimeout(() => submitBtn.classList.add('btn-loading'), 100);
      }
    });
  });
}

// ── 5. INVOICE GRAND TOTAL COUNT-UP ──────────────────────
function initInvoiceAnimation() {
  const grandEl = document.querySelector('.invoice-grand');
  if (!grandEl) return;
  const raw = grandEl.textContent.replace(/[^0-9]/g, '');
  if (!raw) return;
  const target = parseInt(raw);
  grandEl.dataset.target = target;
  grandEl.dataset.prefix = '₹';
  setTimeout(() => animateCounter(grandEl), 400);
}

// ── 6. LANDING PAGE HERO ANIMATIONS ──────────────────────
function initHeroAnimations() {
  const heroEl = document.querySelector('.hero');
  if (!heroEl) return;

  // Animate hero badge
  const badge = heroEl.querySelector('.hero-badge');
  if (badge) {
    badge.style.cssText = 'opacity:0;transform:translateY(-20px)';
    setTimeout(() => {
      badge.style.transition = 'all .6s cubic-bezier(.34,1.56,.64,1)';
      badge.style.opacity = '1';
      badge.style.transform = 'translateY(0)';
    }, 100);
  }

  // Animate h1 word by word
  const h1 = heroEl.querySelector('h1');
  if (h1) {
    const words = h1.innerHTML.split(' ');
    h1.innerHTML = words.map((w, i) =>
      `<span style="display:inline-block;opacity:0;transform:translateY(20px);transition:all .5s ease ${.2 + i * .07}s">${w}</span>`
    ).join(' ');
    setTimeout(() => {
      h1.querySelectorAll('span').forEach(s => {
        s.style.opacity = '1';
        s.style.transform = 'translateY(0)';
      });
    }, 50);
  }

  // Animate lead paragraph
  const lead = heroEl.querySelector('.lead');
  if (lead) {
    lead.style.cssText = 'opacity:0;transform:translateY(20px)';
    setTimeout(() => {
      lead.style.transition = 'all .6s ease .5s';
      lead.style.opacity    = '1';
      lead.style.transform  = 'translateY(0)';
    }, 50);
  }

  // Animate CTA buttons
  heroEl.querySelectorAll('.hero-cta .btn').forEach((btn, i) => {
    btn.style.cssText = 'opacity:0;transform:translateY(20px)';
    setTimeout(() => {
      btn.style.transition = `all .5s cubic-bezier(.34,1.56,.64,1) ${.65 + i * .1}s`;
      btn.style.opacity    = '1';
      btn.style.transform  = 'translateY(0)';
    }, 50);
  });

  // Animate stats row (500+, 10K+, 4.8★)
  heroEl.querySelectorAll('.d-flex.gap-4 > div').forEach((stat, i) => {
    stat.style.cssText = 'opacity:0;transform:translateY(20px)';
    setTimeout(() => {
      stat.style.transition = `all .5s ease ${.85 + i * .1}s`;
      stat.style.opacity    = '1';
      stat.style.transform  = 'translateY(0)';
    }, 50);
  });

  // Floating background icon
  const heroIcon = heroEl.querySelector('.hero-image');
  if (heroIcon) {
    heroIcon.style.animation = 'heroFloat 6s ease-in-out infinite';
  }
}

// ── 7. HOW-IT-WORKS STEPS (landing page) ─────────────────
function initStepAnimations() {
  const steps = document.querySelectorAll('.col-sm-6.col-md-3');
  if (!steps.length) return;

  const obs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity    = '1';
        entry.target.style.transform  = 'translateY(0)';
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  steps.forEach((step, i) => {
    step.style.cssText = `opacity:0;transform:translateY(30px);transition:all .5s ease ${i * .12}s`;
    obs.observe(step);
  });
}

// ── 8. TOPBAR SCROLL SHADOW ───────────────────────────────
function initTopbarScroll() {
  const topbar = document.querySelector('.topbar');
  if (!topbar) return;
  window.addEventListener('scroll', () => {
    topbar.style.boxShadow = window.scrollY > 10
      ? '0 4px 20px rgba(0,0,0,.12)'
      : '0 1px 4px rgba(0,0,0,.05)';
  }, { passive: true });
}

// ── 9. SIDEBAR HOVER INDICATOR ────────────────────────────
function initSidebarHover() {
  document.querySelectorAll('.sidebar-nav a:not(.active)').forEach(link => {
    link.addEventListener('mouseenter', function() {
      this.style.paddingLeft = '1.15rem';
    });
    link.addEventListener('mouseleave', function() {
      this.style.paddingLeft = '';
    });
  });
}

// ── 10. ADD FLOATING KEYFRAME DYNAMICALLY ────────────────
(function addHeroFloatKeyframe() {
  const style = document.createElement('style');
  style.textContent = `
    @keyframes heroFloat {
      0%,100% { transform: translateY(0) rotate(0deg); }
      33%      { transform: translateY(-18px) rotate(2deg); }
      66%      { transform: translateY(-8px) rotate(-1deg); }
    }
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.revealed {
      opacity: 1;
      transform: translateY(0);
    }
  `;
  document.head.appendChild(style);
})();

// ── INIT ALL ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initScrollAnimations();
  initRipple();
  initFormLoading();
  initInvoiceAnimation();
  initHeroAnimations();
  initStepAnimations();
  initTopbarScroll();
  initSidebarHover();
});
