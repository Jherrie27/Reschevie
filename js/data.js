// ===========================
// RESCHEVIE — DATA STORE
// Simulates database in localStorage
// In production, replace with PHP/MySQL API calls
// ===========================

const RESCHEVIE_DATA = {
  products: [
    {
      id: 1,
      name: "Serpent Bohème Necklace",
      type: "necklace",
      origin: "Italy",
      materials: "18K Yellow Gold, VS1 Diamond",
      karat: "18K",
      weight: "12.4g",
      price: "POA",
      priceNum: 0,
      description: "Inspired by Renaissance-era serpent motifs, this sinuous necklace features a hand-engraved snake body set with pavé diamonds along its spine. Crafted in Arezzo, Italy.",
      status: "available",
      emoji: "🐍",
      featured: true
    },
    {
      id: 2,
      name: "Diamond Pavé Ring",
      type: "ring",
      origin: "Saudi Arabia",
      materials: "22K Yellow Gold, VVS2 Diamonds",
      karat: "22K",
      weight: "8.2g",
      price: "₱ 85,000",
      priceNum: 85000,
      description: "A bold statement ring set with 48 pavé diamonds in traditional Arabian filigree setting. Handcrafted by master goldsmiths in Riyadh.",
      status: "available",
      emoji: "💍",
      featured: true
    },
    {
      id: 3,
      name: "Wabi-Sabi Cuff",
      type: "bracelet",
      origin: "Japan",
      materials: "24K Pure Gold",
      karat: "24K",
      weight: "22.1g",
      price: "₱ 120,000",
      priceNum: 120000,
      description: "An intentionally imperfect cuff that celebrates the Japanese philosophy of beauty in imperfection. Hand-hammered by a third-generation artisan in Kyoto.",
      status: "available",
      emoji: "⭕",
      featured: true
    },
    {
      id: 4,
      name: "Dragon Phoenix Earrings",
      type: "earring",
      origin: "Hong Kong",
      materials: "18K Rose Gold, Ruby, Diamond",
      karat: "18K",
      weight: "6.8g (pair)",
      price: "₱ 148,000",
      priceNum: 148000,
      description: "Dangle earrings featuring a stylized dragon and phoenix motif — symbols of eternal partnership. Crafted in the Tsim Sha Tsui jewelry district.",
      status: "available",
      emoji: "🐉",
      featured: true
    },
    {
      id: 5,
      name: "Minimalist Bar Necklace",
      type: "necklace",
      origin: "Japan",
      materials: "18K Yellow Gold",
      karat: "18K",
      weight: "4.1g",
      price: "₱ 42,000",
      priceNum: 42000,
      description: "Clean geometric lines meet Japanese precision. A brushed gold bar with mirror-polished edges, perfect for everyday luxury.",
      status: "available",
      emoji: "➖",
      featured: false
    },
    {
      id: 6,
      name: "Florentine Coin Pendant",
      type: "necklace",
      origin: "Italy",
      materials: "21K Yellow Gold",
      karat: "21K",
      weight: "9.3g",
      price: "₱ 68,000",
      priceNum: 68000,
      description: "A hand-engraved coin pendant featuring the Florentine lily motif, an ancient symbol of refinement. Crafted using centuries-old Florentine techniques.",
      status: "available",
      emoji: "🔮",
      featured: false
    },
    {
      id: 7,
      name: "Arabian Star Ring",
      type: "ring",
      origin: "Saudi Arabia",
      materials: "21K Gold, Sapphire",
      karat: "21K",
      weight: "7.6g",
      price: "₱ 76,500",
      priceNum: 76500,
      description: "An eight-pointed star ring set with a deep blue sapphire at its heart. Inspired by traditional Arabian geometric art.",
      status: "available",
      emoji: "⭐",
      featured: false
    },
    {
      id: 8,
      name: "Kanji Luck Pendant",
      type: "necklace",
      origin: "Japan",
      materials: "24K Pure Gold",
      karat: "24K",
      weight: "5.2g",
      price: "₱ 55,000",
      priceNum: 55000,
      description: "The Kanji character for 'fortune' (富) masterfully carved into pure 24K gold. A meaningful gift for milestone occasions.",
      status: "sold",
      emoji: "🎋",
      featured: false
    }
  ],

  stories: [
    {
      id: 1,
      name: "A Wedding That Started a Legacy",
      author: "Maria Santos",
      description: "When I commissioned the Diamond Pavé Ring for my wedding, I never imagined it would become a family heirloom. My daughter wore it at her own wedding last year. The quality truly endures.",
      date_posted: "2025-11-12"
    },
    {
      id: 2,
      name: "Gifting Beyond Borders",
      author: "James Chen",
      description: "I sent the Dragon Phoenix Earrings to my partner in Manila from Hong Kong. The craftsmanship resonated with our shared heritage. Reschevie understood exactly what we needed.",
      date_posted: "2025-12-01"
    },
    {
      id: 3,
      name: "Investing in Beauty",
      author: "Priya Nanwani",
      description: "The Wabi-Sabi Cuff is my most prized possession. It is not just jewelry — it is a philosophy made tangible. I wear it every day and receive compliments from strangers constantly.",
      date_posted: "2026-01-08"
    }
  ],

  users: [],  // populated on registration
  inquiries: [],
  newsletters: []
};

// ===========================
// LOCAL STORAGE HELPERS
// (These would be replaced by API calls in a real PHP backend)
// ===========================

function dbGet(key) {
  try {
    const stored = localStorage.getItem('reschevie_' + key);
    return stored ? JSON.parse(stored) : RESCHEVIE_DATA[key] || [];
  } catch(e) {
    return RESCHEVIE_DATA[key] || [];
  }
}

function dbSet(key, val) {
  try {
    localStorage.setItem('reschevie_' + key, JSON.stringify(val));
  } catch(e) {}
}

async function getProducts() {
  const res = await fetch('api/products.php');
  return await res.json();
}
function saveProducts(p) {
  dbSet('products', p);
}
function getStories() { return dbGet('stories'); }
function saveStories(s) { dbSet('stories', s); }
function getUsers() { return dbGet('users'); }
function saveUsers(u) { dbSet('users', u); }
function getInquiries() { return dbGet('inquiries'); }
function saveInquiries(i) { dbSet('inquiries', i); }
function getNewsletters() { return dbGet('newsletters'); }
function saveNewsletters(n) { dbSet('newsletters', n); }

// Session helpers
function getSession() {
  try { return JSON.parse(sessionStorage.getItem('reschevie_session')); } catch(e) { return null; }
}
function setSession(data) { sessionStorage.setItem('reschevie_session', JSON.stringify(data)); }
function clearSession() { sessionStorage.removeItem('reschevie_session'); }

// Inquiry cart (wishlist)
function getCart() {
  try { return JSON.parse(localStorage.getItem('reschevie_cart')) || []; } catch(e) { return []; }
}
function saveCart(c) { localStorage.setItem('reschevie_cart', JSON.stringify(c)); }
function addToCart(productId) {
  const cart = getCart();
  if (!cart.includes(productId)) {
    cart.push(productId);
    saveCart(cart);
  }
}
function removeFromCart(productId) {
  const cart = getCart().filter(id => id !== productId);
  saveCart(cart);
}
function isInCart(productId) { return getCart().includes(productId); }

// Wishlist
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

// Toast notification
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

// Initialize default data if not set
(function initData() {
  if (!localStorage.getItem('reschevie_products')) {
    dbSet('products', RESCHEVIE_DATA.products);
  }
  if (!localStorage.getItem('reschevie_stories')) {
    dbSet('stories', RESCHEVIE_DATA.stories);
  }
  // Create default admin if not exists
  const users = getUsers();
  const adminExists = users.find(u => u.role === 'admin');
  if (!adminExists) {
    users.push({
      id: 1,
      username: 'admin',
      email: 'admin@reschevie.com',
      password: 'Admin@2026',
      fname: 'Samantha',
      lname: 'Sayaman',
      role: 'admin',
      created_at: new Date().toISOString()
    });
    saveUsers(users);
  }
})();