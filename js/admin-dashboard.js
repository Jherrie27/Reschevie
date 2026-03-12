// Admin Dashboard Logic for Reschevie
// Handles CRUD for all tables, image upload, and admin authentication

// --- Authentication ---
function checkAdminAuth() {
  const session = getSession();
  if (!session || session.role !== 'admin') {
    window.location.href = 'login.html';
  }
}
checkAdminAuth();

// --- Utility ---
function apiGet(endpoint) {
  return fetch(endpoint, { credentials: 'include' }).then(r => r.json());
}
function apiPost(endpoint, data) {
  return fetch(endpoint, {
    method: 'POST',
    credentials: 'include',
    body: data instanceof FormData ? data : JSON.stringify(data),
    headers: data instanceof FormData ? {} : { 'Content-Type': 'application/json' }
  }).then(r => r.json());
}

// --- Products ---
async function loadProducts() {
  const products = await apiGet('/api/products.php');
  const table = document.createElement('table');
  table.className = 'table';
  table.innerHTML = `<tr><th>ID</th><th>Name</th><th>Type</th><th>Origin</th><th>Status</th><th>Actions</th></tr>`;
  products.forEach(p => {
    table.innerHTML += `<tr>
      <td>${p.product_id}</td>
      <td>${p.product_name}</td>
      <td>${p.product_type}</td>
      <td>${p.product_origin}</td>
      <td>${p.product_status}</td>
      <td>
        <button class="btn-outline" onclick="editProduct(${p.product_id})">Edit</button>
        <button class="btn-danger" onclick="deleteProduct(${p.product_id})">Delete</button>
      </td>
    </tr>`;
  });
  document.getElementById('products-table').innerHTML = '';
  document.getElementById('products-table').appendChild(table);
}

// --- Add Product ---
document.getElementById('add-product-form').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  // Send to PHP upload endpoint
  const res = await apiPost('/api/admin-upload-product.php', formData);
  if (res.success) {
    loadProducts();
    form.reset();
    alert('Product added!');
  } else {
    alert(res.message || 'Failed to add product');
  }
});

// --- Users ---
async function loadUsers() {
  const users = await apiGet('/api/users.php');
  const table = document.createElement('table');
  table.className = 'table';
  table.innerHTML = `<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>`;
  users.forEach(u => {
    table.innerHTML += `<tr>
      <td>${u.user_id}</td>
      <td>${u.user_fname} ${u.user_lname}</td>
      <td>${u.user_email}</td>
      <td>${u.role || 'user'}</td>
      <td>
        <button class="btn-outline" onclick="editUser(${u.user_id})">Edit</button>
        <button class="btn-danger" onclick="deleteUser(${u.user_id})">Delete</button>
      </td>
    </tr>`;
  });
  document.getElementById('users-table').innerHTML = '';
  document.getElementById('users-table').appendChild(table);
}

// --- Load All ---
window.addEventListener('DOMContentLoaded', () => {
  loadProducts();
  loadUsers();
});

// --- TODO: Add CRUD for admins, client_stories, inquiries, newsletters, etc. ---
// --- TODO: Add edit forms/modal logic ---
// --- TODO: Add image upload handler in PHP backend ---
