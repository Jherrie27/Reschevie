// ===========================
// RESCHEVIE — MAIN JS
// ===========================

document.addEventListener('DOMContentLoaded', () => {

  // NAVBAR SCROLL
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    });
  }

  // HAMBURGER / MOBILE NAV
  const hamburger = document.getElementById('hamburger');
  if (hamburger) {
    let mobileNav = document.getElementById('mobile-nav');
    if (!mobileNav) {
      mobileNav = document.createElement('div');
      mobileNav.id = 'mobile-nav';
      mobileNav.className = 'mobile-nav';
      mobileNav.innerHTML = `
        <button class="mobile-nav-close" id="mobile-nav-close">✕</button>
        <a href="index.html">Home</a>
        <a href="catalog.html">Catalog</a>
        <a href="index.html#curations">Curations</a>
        <a href="index.html#about">Our Story</a>
        <a href="inquiry.html">Inquiry</a>
        <a href="login.html" style="color:var(--gold)">Sign In</a>
      `;
      document.body.appendChild(mobileNav);
    }
    hamburger.addEventListener('click', () => mobileNav.classList.add('open'));
    document.getElementById('mobile-nav-close')?.addEventListener('click', () => mobileNav.classList.remove('open'));
  }

  // CART BADGE UPDATE
  function updateCartBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) badge.textContent = getCart().length;
  }
  updateCartBadge();

  // FEATURED PRODUCTS
  const featuredGrid = document.getElementById('featured-products');
  if (featuredGrid) {
    const products = getProducts().filter(p => p.featured);
    featuredGrid.innerHTML = products.map(p => renderProductCard(p)).join('');
    attachProductCardEvents();
  }

  // CLIENT STORIES
  const storiesGrid = document.getElementById('stories-grid');
  if (storiesGrid) {
    const stories = getStories();
    storiesGrid.innerHTML = stories.map(s => `
      <div class="story-card fade-up">
        <div class="story-quote">"</div>
        <p class="story-text">${s.description}</p>
        <div class="story-author">${s.author}</div>
        <div class="story-date">${formatDate(s.date_posted)}</div>
      </div>
    `).join('') || '<div class="empty-state"><div class="empty-icon">💬</div><h3>No stories yet</h3></div>';
    observeFadeUp();
  }

  // NEWSLETTER
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = newsletterForm.querySelector('input').value;
      const newsletters = getNewsletters();
      if (newsletters.find(n => n.email === email)) {
        document.getElementById('newsletter-msg').textContent = 'You are already subscribed!';
      } else {
        newsletters.push({ id: Date.now(), email, subbed_at: new Date().toISOString() });
        saveNewsletters(newsletters);
        document.getElementById('newsletter-msg').textContent = 'Thank you for joining the Reschevie inner circle!';
        newsletterForm.reset();
      }
    });
  }

  // INTERSECTION OBSERVER FOR FADE UP
  function observeFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
    }, { threshold: 0.1 });
    els.forEach(el => obs.observe(el));
  }
  observeFadeUp();

  // PRODUCT MODAL
  const modalOverlay = document.getElementById('product-modal');
  if (!document.getElementById('product-modal')) {
    const modal = document.createElement('div');
    modal.id = 'product-modal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal" id="modal-content">
        <button class="modal-close" id="modal-close">✕</button>
        <div id="modal-body"></div>
      </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('#modal-close').addEventListener('click', () => modal.classList.remove('open'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('open'); });
  }

  window.openProductModal = function(id) {
    const p = getProducts().find(x => x.id == id);
    if (!p) return;
    const isCarted = isInCart(p.id);
    const isWished = isWishlisted(p.id);
    document.getElementById('modal-body').innerHTML = `
      <div class="modal-grid">
        <div class="modal-img">${p.emoji}</div>
        <div class="modal-info">
          <div class="product-type">${p.type}</div>
          <h2 class="product-name">${p.name}</h2>
          <div class="product-origin">Origin: ${p.origin}</div>
          <span class="product-price ${p.price === 'POA' ? 'poa' : ''}">${p.price === 'POA' ? 'Price Upon Request' : p.price}</span>
          <p>${p.description}</p>
          <div class="modal-details">
            <div class="modal-detail-row"><strong>Materials</strong> ${p.materials}</div>
            <div class="modal-detail-row"><strong>Gold Karat</strong> ${p.karat}</div>
            <div class="modal-detail-row"><strong>Weight</strong> ${p.weight}</div>
            <div class="modal-detail-row"><strong>Origin</strong> ${p.origin}</div>
            <div class="modal-detail-row"><strong>Status</strong> <span class="badge-status badge-${p.status}">${p.status}</span></div>
          </div>
          <div class="modal-actions">
            <button class="btn-gold" id="modal-inquire-btn" onclick="handleModalInquire(${p.id})">
              ${isCarted ? '✓ Added to Inquiry List' : 'Add to Inquiry List'}
            </button>
            <button class="btn-outline" onclick="handleWishlist(${p.id}, this)">
              ${isWished ? '♥ Wishlisted' : '♡ Save to Wishlist'}
            </button>
          </div>
        </div>
      </div>
    `;
    document.getElementById('product-modal').classList.add('open');
  };

  window.handleModalInquire = function(id) {
    const session = getSession();
    if (!session) {
      showToast('Please sign in to add items to your inquiry list.');
      setTimeout(() => window.location.href = 'login.html', 1500);
      return;
    }
    addToCart(id);
    document.getElementById('modal-inquire-btn').textContent = '✓ Added to Inquiry List';
    updateCartBadge();
    showToast('Added to your inquiry list!');
  };

  window.handleWishlist = function(id, btn) {
    const added = toggleWishlist(id);
    btn.textContent = added ? '♥ Wishlisted' : '♡ Save to Wishlist';
    showToast(added ? 'Added to wishlist!' : 'Removed from wishlist');
  };

  window.attachProductCardEvents = function() {
    document.querySelectorAll('.product-card').forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.closest('.product-wishlist') || e.target.closest('.btn-inquire')) return;
        openProductModal(card.dataset.id);
      });
      const wishBtn = card.querySelector('.product-wishlist');
      if (wishBtn) wishBtn.addEventListener('click', () => {
        const added = toggleWishlist(parseInt(card.dataset.id));
        wishBtn.classList.toggle('active', added);
        wishBtn.innerHTML = added ? '♥' : '♡';
        showToast(added ? 'Saved to wishlist!' : 'Removed from wishlist');
      });
      const inquireBtn = card.querySelector('.btn-inquire');
      if (inquireBtn) inquireBtn.addEventListener('click', () => {
        const session = getSession();
        if (!session) { showToast('Please sign in first.'); setTimeout(() => window.location.href = 'login.html', 1500); return; }
        addToCart(parseInt(card.dataset.id));
        updateCartBadge();
        showToast('Added to inquiry list!');
      });
    });
  };
});

// ===========================
// RENDER HELPERS
// ===========================

function renderProductCard(p) {
  const wished = isWishlisted(p.id);
  const carted = isInCart(p.id);
  return `
    <div class="product-card" data-id="${p.id}">
      <div class="product-img">
        <div class="product-img-inner" style="background: linear-gradient(135deg, #0D0D0D, #1A1408)">
          ${p.emoji}
        </div>
        <span class="product-origin-badge">${p.origin}</span>
        <button class="product-wishlist ${wished ? 'active' : ''}">${wished ? '♥' : '♡'}</button>
      </div>
      <div class="product-info">
        <div class="product-type">${p.type}</div>
        <div class="product-name">${p.name}</div>
        <div class="product-origin">${p.karat} · ${p.materials}</div>
        <div class="product-footer">
          <span class="product-price ${p.price === 'POA' ? 'poa' : ''}">${p.price === 'POA' ? 'Price Upon Request' : p.price}</span>
          <button class="btn-inquire">${carted ? 'Added ✓' : 'Inquire'}</button>
        </div>
      </div>
    </div>
  `;
}

function formatDate(str) {
  try {
    return new Date(str).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
  } catch(e) { return str; }
}
