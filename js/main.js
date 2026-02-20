// ===========================
// RESCHEVIE — MAIN JS
// ===========================

document.addEventListener('DOMContentLoaded', async () => {

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
    const products = await getProducts();
    const featured = products.filter(p => p.product_featured == 1 || p.featured);
    featuredGrid.innerHTML = featured.length
        ? featured.map(p => renderProductCard(p)).join('')
        : '<div class="empty-state"><div class="empty-icon">💍</div><h3>No featured products</h3></div>';
    attachProductCardEvents();
  }

  // CLIENT STORIES
  const storiesGrid = document.getElementById('stories-grid');
  if (storiesGrid) {
    const stories = await getStories();
    storiesGrid.innerHTML = stories.length
        ? stories.map(s => `
          <div class="story-card fade-up">
            <div class="story-quote">"</div>
            <p class="story-text">${s.story_description ?? s.description}</p>
            <div class="story-author">${s.story_author ?? s.author}</div>
            <div class="story-date">${formatDate(s.story_date_posted ?? s.date_posted)}</div>
          </div>
        `).join('')
        : '<div class="empty-state"><div class="empty-icon">💬</div><h3>No stories yet</h3></div>';
    observeFadeUp();
  }

  // NEWSLETTER
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = newsletterForm.querySelector('input').value;
      const msgEl = document.getElementById('newsletter-msg');
      const result = await subscribeNewsletter(email);
      msgEl.textContent = result.success
          ? 'Thank you for joining the Reschevie inner circle!'
          : (result.message || 'Something went wrong. Please try again.');
      if (result.success) newsletterForm.reset();
    });
  }

  // INTERSECTION OBSERVER FOR FADE UP
  function observeFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    els.forEach(el => obs.observe(el));
  }
  observeFadeUp();

  // PRODUCT MODAL — create once
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

  // Open product modal — async because getProducts() is now async
  window.openProductModal = async function(id) {
    const products = await getProducts();
    // Support both DB field names (product_id) and legacy field names (id)
    const p = products.find(x => (x.product_id ?? x.id) == id);
    if (!p) return;

    // Normalize field names between DB response and legacy data shape
    const pid      = p.product_id      ?? p.id;
    const name     = p.product_name    ?? p.name;
    const type     = p.product_type    ?? p.type;
    const origin   = p.product_origin  ?? p.origin;
    const karat    = p.product_karat   ?? p.karat;
    const weight   = p.product_weight  ?? p.weight;
    const materials= p.product_materials ?? p.materials;
    const desc     = p.product_description ?? p.description;
    const status   = p.product_status  ?? p.status;
    const emoji    = p.product_emoji   ?? p.emoji;
    const poa      = p.product_price_poa == 1 || p.price === 'POA';
    const price    = poa ? 'Price Upon Request' : (p.product_price ? `₱ ${Number(p.product_price).toLocaleString()}` : p.price);

    const isCarted = isInCart(pid);
    const isWished = isWishlisted(pid);

    document.getElementById('modal-body').innerHTML = `
      <div class="modal-grid">
        <div class="modal-img">${emoji}</div>
        <div class="modal-info">
          <div class="product-type">${type}</div>
          <h2 class="product-name">${name}</h2>
          <div class="product-origin">Origin: ${origin}</div>
          <span class="product-price ${poa ? 'poa' : ''}">${price}</span>
          <p>${desc}</p>
          <div class="modal-details">
            <div class="modal-detail-row"><strong>Materials</strong> ${materials}</div>
            <div class="modal-detail-row"><strong>Gold Karat</strong> ${karat}</div>
            <div class="modal-detail-row"><strong>Weight</strong> ${weight}</div>
            <div class="modal-detail-row"><strong>Origin</strong> ${origin}</div>
            <div class="modal-detail-row"><strong>Status</strong> <span class="badge-status badge-${status}">${status}</span></div>
          </div>
          <div class="modal-actions">
            <button class="btn-gold" id="modal-inquire-btn" onclick="handleModalInquire(${pid})">
              ${isCarted ? '✓ Added to Inquiry List' : 'Add to Inquiry List'}
            </button>
            <button class="btn-outline" onclick="handleWishlist(${pid}, this)">
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

});

// ===========================
// RENDER HELPERS
// ===========================

function renderProductCard(p) {
  // Normalize field names between DB response and legacy data shape
  const id       = p.product_id       ?? p.id;
  const name     = p.product_name     ?? p.name;
  const type     = p.product_type     ?? p.type;
  const origin   = p.product_origin   ?? p.origin;
  const karat    = p.product_karat    ?? p.karat;
  const materials= p.product_materials ?? p.materials;
  const emoji    = p.product_emoji    ?? p.emoji;
  const status   = p.product_status   ?? p.status;
  const poa      = p.product_price_poa == 1 || p.price === 'POA';
  const price    = poa ? 'Price Upon Request' : (p.product_price ? `₱ ${Number(p.product_price).toLocaleString()}` : p.price);

  const wished = isWishlisted(id);
  const carted = isInCart(id);

  return `
    <div class="product-card" data-id="${id}">
      <div class="product-img">
        <div class="product-img-inner" style="background: linear-gradient(135deg, #0D0D0D, #1A1408)">
          ${emoji}
        </div>
        <span class="product-origin-badge">${origin}</span>
        <button class="product-wishlist ${wished ? 'active' : ''}">${wished ? '♥' : '♡'}</button>
      </div>
      <div class="product-info">
        <div class="product-type">${type}</div>
        <div class="product-name">${name}</div>
        <div class="product-origin">${karat} · ${materials}</div>
        <div class="product-footer">
          <span class="product-price ${poa ? 'poa' : ''}">${price}</span>
          <button class="btn-inquire ${status === 'sold' ? 'disabled' : ''}" ${status === 'sold' ? 'disabled' : ''}>
            ${carted ? 'Added ✓' : status === 'sold' ? 'Sold' : 'Inquire'}
          </button>
        </div>
      </div>
    </div>
  `;
}

function attachProductCardEvents() {
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
      if (!session) {
        showToast('Please sign in first.');
        setTimeout(() => window.location.href = 'login.html', 1500);
        return;
      }
      addToCart(parseInt(card.dataset.id));
      const badge = document.getElementById('cart-badge');
      if (badge) badge.textContent = getCart().length;
      showToast('Added to inquiry list!');
    });
  });
}

function formatDate(str) {
  try {
    return new Date(str).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
  } catch(e) { return str; }
}