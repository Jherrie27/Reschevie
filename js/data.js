// ===========================
// RESCHEVIE — DATA STORE
// All storage backed by PHP/MySQL API
// ===========================

// ===========================
// INTERNAL FETCH HELPERS
// ===========================

async function _apiGet(endpoint) {
  try {
    const res = await fetch(endpoint, { credentials: 'include' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error(`[RESCHEVIE] GET ${endpoint} failed:`, e);
    return null;
  }
}

async function _apiPost(endpoint, data) {
  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(data)
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error(`[RESCHEVIE] POST ${endpoint} failed:`, e);
    return { success: false, message: e.message };
  }
}

async function _apiPut(endpoint, data) {
  try {
    const res = await fetch(endpoint, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(data)
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error(`[RESCHEVIE] PUT ${endpoint} failed:`, e);
    return { success: false, message: e.message };
  }
}

async function _apiDelete(endpoint) {
  try {
    const res = await fetch(endpoint, {
      method: 'DELETE',
      credentials: 'include'
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error(`[RESCHEVIE] DELETE ${endpoint} failed:`, e);
    return { success: false, message: e.message };
  }
}

// ===========================
// AUTH
// ===========================

/**
 * Log in a user or admin.
 * @param {string} email
 * @param {string} password
 * @returns {Promise<{success: boolean, role?: string, message?: string}>}
 */
async function login(email, password) {
  const form = new FormData();
  form.append('action', 'login');
  form.append('email', email);
  form.append('password', password);
  try {
    const res = await fetch('api/auth.php', {
      method: 'POST',
      credentials: 'include',
      body: form
    });
    const data = await res.json();
    if (data.success) setSession({ email, role: data.role, fname: data.fname });
    return data;
  } catch (e) {
    console.error('[RESCHEVIE] login failed:', e);
    return { success: false, message: e.message };
  }
}

/**
 * Register a new user.
 * @param {{username, email, password, fname, lname, contact}} userData
 * @returns {Promise<{success: boolean, message?: string}>}
 */
async function register(userData) {
  const form = new FormData();
  form.append('action', 'register');
  Object.entries(userData).forEach(([k, v]) => form.append(k, v));
  try {
    const res = await fetch('api/auth.php', {
      method: 'POST',
      credentials: 'include',
      body: form
    });
    return await res.json();
  } catch (e) {
    console.error('[RESCHEVIE] register failed:', e);
    return { success: false, message: e.message };
  }
}

/**
 * Log out the current user.
 * @returns {Promise<{success: boolean}>}
 */
async function logout() {
  clearSession();
  const form = new FormData();
  form.append('action', 'logout');
  try {
    const res = await fetch('api/auth.php', {
      method: 'POST',
      credentials: 'include',
      body: form
    });
    return await res.json();
  } catch (e) {
    console.error('[RESCHEVIE] logout failed:', e);
    return { success: false, message: e.message };
  }
}

// ===========================
// PRODUCTS
// ===========================

/**
 * Fetch all products, with optional filters.
 * @param {{origin?: string, type?: string, status?: string}} [filters]
 * @returns {Promise<Array>}
 */
async function getProducts(filters = {}) {
  const params = new URLSearchParams();
  if (filters.origin) params.set('origin', filters.origin);
  if (filters.type)   params.set('type',   filters.type);
  if (filters.status) params.set('status', filters.status);
  const query = params.toString() ? `?${params}` : '';
  return (await _apiGet(`api/products.php${query}`)) ?? [];
}

/**
 * Add a new product (admin only).
 * @param {Object} productData
 * @returns {Promise<{success: boolean, id?: number}>}
 */
async function addProduct(productData) {
  return await _apiPost('api/products.php', productData);
}

/**
 * Update an existing product (admin only).
 * @param {Object} productData  — must include product_id
 * @returns {Promise<{success: boolean}>}
 */
async function updateProduct(productData) {
  return await _apiPut('api/products.php', productData);
}

/**
 * Delete a product by ID (admin only).
 * @param {number} productId
 * @returns {Promise<{success: boolean}>}
 */
async function deleteProduct(productId) {
  return await _apiDelete(`api/products.php?id=${productId}`);
}

// ===========================
// INQUIRIES
// ===========================

/**
 * Submit a new inquiry with cart items.
 * @param {{fname, lname, email, phone, contactPref, notes, items: number[]}} inquiryData
 * @returns {Promise<{success: boolean, inquiry_id?: number}>}
 */
async function submitInquiry(inquiryData) {
  return await _apiPost('api/inquiries.php', inquiryData);
}

/**
 * Fetch all inquiries (admin only).
 * @returns {Promise<Array>}
 */
async function getInquiries() {
  return (await _apiGet('api/inquiries.php')) ?? [];
}

/**
 * Update the status of an inquiry (admin only).
 * @param {number} inquiryId
 * @param {string} status
 * @returns {Promise<{success: boolean}>}
 */
async function updateInquiryStatus(inquiryId, status) {
  return await _apiPut('api/inquiries.php', { inquiry_id: inquiryId, status });
}

// ===========================
// STORIES
// ===========================

/**
 * Fetch all stories.
 * @returns {Promise<Array>}
 */
async function getStories() {
  return (await _apiGet('api/stories.php')) ?? [];
}

/**
 * Add a new story (admin only).
 * @param {{name, author, description}} storyData
 * @returns {Promise<{success: boolean, id?: number}>}
 */
async function addStory(storyData) {
  return await _apiPost('api/stories.php', storyData);
}

/**
 * Update an existing story (admin only).
 * @param {Object} storyData — must include story_id
 * @returns {Promise<{success: boolean}>}
 */
async function updateStory(storyData) {
  return await _apiPut('api/stories.php', storyData);
}

/**
 * Delete a story by ID (admin only).
 * @param {number} storyId
 * @returns {Promise<{success: boolean}>}
 */
async function deleteStory(storyId) {
  return await _apiDelete(`api/stories.php?id=${storyId}`);
}

// ===========================
// USERS (admin)
// ===========================

/**
 * Fetch all users (admin only).
 * @returns {Promise<Array>}
 */
async function getUsers() {
  return (await _apiGet('api/users.php')) ?? [];
}

/**
 * Update a user's details (admin only).
 * @param {Object} userData — must include user_id
 * @returns {Promise<{success: boolean}>}
 */
async function updateUser(userData) {
  return await _apiPut('api/users.php', userData);
}

/**
 * Delete a user by ID (admin only).
 * @param {number} userId
 * @returns {Promise<{success: boolean}>}
 */
async function deleteUser(userId) {
  return await _apiDelete(`api/users.php?id=${userId}`);
}

// ===========================
// NEWSLETTER
// ===========================

/**
 * Subscribe an email to the newsletter.
 * @param {string} email
 * @returns {Promise<{success: boolean, message?: string}>}
 */
async function subscribeNewsletter(email) {
  return await _apiPost('api/newsletter.php', { email });
}

/**
 * Fetch all newsletter subscribers (admin only).
 * @returns {Promise<Array>}
 */
async function getNewsletters() {
  return (await _apiGet('api/newsletter.php')) ?? [];
}

/**
 * Unsubscribe an email from the newsletter.
 * @param {string} email
 * @returns {Promise<{success: boolean}>}
 */
async function unsubscribeNewsletter(email) {
  return await _apiDelete(`api/newsletter.php?email=${encodeURIComponent(email)}`);
}

// ===========================
// SESSION (client-side only)
// ===========================

function getSession() {
  try { return JSON.parse(sessionStorage.getItem('reschevie_session')); } catch(e) { return null; }
}
function setSession(data) { sessionStorage.setItem('reschevie_session', JSON.stringify(data)); }
function clearSession() { sessionStorage.removeItem('reschevie_session'); }

// ===========================
// INQUIRY CART (client-side)
// ===========================

function getCart() {
  try { return JSON.parse(localStorage.getItem('reschevie_cart')) || []; } catch(e) { return []; }
}
function saveCart(c) { localStorage.setItem('reschevie_cart', JSON.stringify(c)); }
function addToCart(productId) {
  const cart = getCart();
  if (!cart.includes(productId)) { cart.push(productId); saveCart(cart); }
}
function removeFromCart(productId) { saveCart(getCart().filter(id => id !== productId)); }
function clearCart() { localStorage.removeItem('reschevie_cart'); }
function isInCart(productId) { return getCart().includes(productId); }

// ===========================
// WISHLIST (client-side)
// ===========================

function getWishlist() {
  try { return JSON.parse(localStorage.getItem('reschevie_wishlist')) || []; } catch(e) { return []; }
}
function toggleWishlist(productId) {
  const wl = getWishlist();
  const idx = wl.indexOf(productId);
  if (idx > -1) wl.splice(idx, 1); else wl.push(productId);
  localStorage.setItem('reschevie_wishlist', JSON.stringify(wl));
  return wl.includes(productId);
}
function isWishlisted(productId) { return getWishlist().includes(productId); }

// ===========================
// TOAST NOTIFICATION (UI)
// ===========================

function showToast(msg) {
  let t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'toast';
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}