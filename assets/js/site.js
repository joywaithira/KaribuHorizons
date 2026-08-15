document.addEventListener('click', function (e) {
  // mobile menu toggle
  if (e.target.matches('.mobile-toggle')) {
    document.documentElement.classList.toggle('nav-open');
    return;
  }

  // close drawer when clicking backdrop
  if (e.target.matches('.cart-backdrop')) {
    closeCartDrawer();
  }

  // toggle dropdowns
  if (e.target.matches('.dropdown-toggle')) {
    const menu = e.target.nextElementSibling;
    // toggle arrow class
    e.target.classList.toggle('open');
    // close others
    document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== menu) m.style.display = 'none' });
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    return;
  }

  // close any open menus when clicking outside
  document.querySelectorAll('.dropdown-menu').forEach(m => { if (!m.contains(e.target)) m.style.display = 'none' });
});

/* Cart and catalog functionality */
const CART_KEY = 'kh_cart';

async function loadProducts(){
  try {
    const res = await fetch('assets/data/products.json');
    const products = await res.json();
    renderProducts(products);
  } catch(err){ console.error('Failed to load products', err) }
}

function renderProducts(products){
  const grid = document.querySelector('.cards');
  if(!grid) return;
  // clear existing to avoid duplicates
  grid.innerHTML = '';
  products.forEach(p=>{
    const card = document.createElement('div'); card.className='card';
    card.innerHTML = `
      <img src="${p.image}" alt="${p.title}">
      <h3>${p.title}</h3>
      <p class="muted">$${p.price.toFixed(2)}</p>
      <p>${p.description}</p>
      <div style="display:flex;gap:8px;margin-top:8px">
        <button class="primary" data-add="${p.id}">Add to cart</button>
        <a class="secondary" href="checkout.html">Buy now</a>
      </div>
    `;
    grid.appendChild(card);
  });

  // bind add buttons
  document.querySelectorAll('[data-add]').forEach(btn=>btn.addEventListener('click',e=>{
    addToCart(e.target.getAttribute('data-add'));
  }));
}

function getCart(){return JSON.parse(localStorage.getItem(CART_KEY)||'[]')}
function saveCart(c){localStorage.setItem(CART_KEY, JSON.stringify(c))}

function addToCart(id){
  const cart = getCart();
  const item = cart.find(i=>i.id===id);
  if(item) item.qty +=1; else cart.push({id:id,qty:1});
  saveCart(cart);
  showCartDrawer();
  renderCartDrawer();
}

function setQty(id,qty){
  const cart = getCart();
  const item = cart.find(i=>i.id===id);
  if(!item) return;
  item.qty = Math.max(0,qty);
  const newCart = cart.filter(i=>i.qty>0);
  saveCart(newCart);
  renderCartDrawer();
}

function removeFromCart(id){
  let cart = getCart(); cart = cart.filter(i=>i.id!==id); saveCart(cart); renderCartDrawer();
}

function clearCart(){ localStorage.removeItem(CART_KEY); renderCartDrawer(); }

function formatCurrency(x){ return '$'+x.toFixed(2) }

async function renderCartDrawer(){
  const drawer = ensureCartDrawer();
  const itemsEl = drawer.querySelector('.cart-items');
  itemsEl.innerHTML = '';
  const cart = getCart();
  if(cart.length===0){ itemsEl.innerHTML = '<div class="muted">Your cart is empty.</div>'; drawer.querySelector('.cart-total').textContent=''; return }
  // load products to map details
  const res = await fetch('assets/data/products.json'); const products = await res.json();
  let subtotal=0;
  cart.forEach(ci=>{
    const p = products.find(pp=>pp.id===ci.id) || {title:ci.id,price:0,image:''};
    subtotal += (p.price||0)*ci.qty;
    const el = document.createElement('div'); el.className='cart-item';
    el.innerHTML = `
      <img src="${p.image}" alt="${p.title}">
      <div style="flex:1">
        <div><strong>${p.title}</strong></div>
        <div class="muted">${formatCurrency(p.price||0)}</div>
        <div class="qty-controls">
          <button data-decr="${ci.id}">-</button>
          <input type="number" min="0" value="${ci.qty}" data-qty="${ci.id}" style="width:56px;padding:6px;border-radius:6px;border:1px solid #eee">
          <button data-incr="${ci.id}">+</button>
          <button data-remove="${ci.id}" style="margin-left:auto;background:transparent;border:0;color:var(--brand-brown)">Remove</button>
        </div>
      </div>
    `;
    itemsEl.appendChild(el);
  });
  drawer.querySelector('.cart-total').textContent = 'Subtotal: '+formatCurrency(subtotal);

  // bind controls
  drawer.querySelectorAll('[data-incr]').forEach(b=>b.onclick=()=>{ const id=b.getAttribute('data-incr'); const cart=getCart(); const it=cart.find(x=>x.id===id); if(it){ setQty(id, it.qty+1) } });
  drawer.querySelectorAll('[data-decr]').forEach(b=>b.onclick=()=>{ const id=b.getAttribute('data-decr'); const cart=getCart(); const it=cart.find(x=>x.id===id); if(it){ setQty(id, it.qty-1) } });
  drawer.querySelectorAll('[data-remove]').forEach(b=>b.onclick=()=>{ removeFromCart(b.getAttribute('data-remove')) });
  drawer.querySelectorAll('[data-qty]').forEach(inp=>inp.onchange=(e)=>{ setQty(e.target.getAttribute('data-qty'), parseInt(e.target.value||0,10)) });
}

function ensureCartDrawer(){
  let drawer = document.querySelector('.cart-drawer');
  if(drawer) return drawer;
  drawer = document.createElement('div'); drawer.className='cart-drawer';
  drawer.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h3>Your Cart</h3>
      <button onclick="closeCartDrawer()">Close</button>
    </div>
    <div class="cart-items muted">Loading...</div>
    <div class="totals cart-total"></div>
    <div style="margin-top:18px;display:flex;gap:8px">
      <button class="primary" onclick="checkoutFromDrawer()">Checkout</button>
      <button onclick="clearCart()">Clear</button>
    </div>
  `;
  document.body.appendChild(drawer);
  // backdrop
  const backdrop = document.createElement('div'); backdrop.className='cart-backdrop'; backdrop.style.position='fixed'; backdrop.style.left='0'; backdrop.style.top='0'; backdrop.style.right='0'; backdrop.style.bottom='0'; backdrop.style.background='rgba(0,0,0,0.35)'; backdrop.style.opacity='0'; backdrop.style.transition='opacity .2s ease'; backdrop.style.zIndex='125'; document.body.appendChild(backdrop);
  return drawer;
}

function showCartDrawer(){
  const drawer = ensureCartDrawer();
  drawer.classList.add('open');
  const backdrop = document.querySelector('.cart-backdrop'); backdrop.style.opacity='1'; backdrop.style.pointerEvents='auto';
}

function closeCartDrawer(){
  const drawer = document.querySelector('.cart-drawer'); if(drawer) drawer.classList.remove('open');
  const backdrop = document.querySelector('.cart-backdrop'); if(backdrop) { backdrop.style.opacity='0'; backdrop.style.pointerEvents='none' }
}

function checkoutFromDrawer(){ window.location='checkout.html'; }

// expose for console / inline buttons
window.addToCart = addToCart; window.viewCart = showCartDrawer; window.clearCart = clearCart; window.removeFromCart = removeFromCart;

// initialize product grid on shop pages
document.addEventListener('DOMContentLoaded', ()=>{ loadProducts(); renderCartDrawer(); });

// mobile drawer creation
function ensureMobileMenu(){
  if(document.querySelector('.mobile-menu-drawer')) return;
  const d = document.createElement('nav'); d.className='mobile-menu-drawer';
  d.innerHTML = `
    <div class="close"><button onclick="document.documentElement.classList.remove('nav-open')">Close ✕</button></div>
    <a href="index.html">Home</a>
    <a href="about.html">About</a>
    <a href="packages.html">Packages</a>
    <a href="safaritours.html">Safari Trips</a>
    <a href="kenyantrips.html">Kenyan Trips</a>
    <a href="internationaltrips.html">International Trips</a>
    <a href="blog.html">Blog</a>
    <a href="shop.html">Shop</a>
    <a href="contact.html">Contact</a>
  `;
  document.body.appendChild(d);
}

document.addEventListener('DOMContentLoaded', ()=>{ ensureMobileMenu(); });


