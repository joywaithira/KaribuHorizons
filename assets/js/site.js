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
        <a class="secondary" href="checkout.php">Buy now</a>
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
          <button data-remove="${ci.id}" style="margin-left:auto;background:transparent;border:0;color:var(--wood-brown)">Remove</button>
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

async function checkoutFromDrawer(){
  const cart = getCart();
  if(!cart || cart.length===0){ alert('Your cart is empty'); return }
  try{
    const res = await fetch('api/checkout.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({cart})});
    const data = await res.json();
    if(!res.ok){ alert(data.error||'Checkout failed'); return }
    const total = data.total;
    const ok = confirm('Your total is '+formatCurrency(total)+". Proceed to pay now?");
    if(!ok) return;
    // simulate payment confirmation (in real app redirect to payment provider)
    const payRes = await fetch('api/confirm_payment.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'order_id='+encodeURIComponent(data.order_id)+'&method=card'});
    const payData = await payRes.json();
    if(!payRes.ok){ alert(payData.error||'Payment failed'); return }
    // show receipt
    showReceipt(payData);
    clearCart();
    closeCartDrawer();
  } catch(err){ console.error(err); alert('Checkout error') }
}

function showReceipt(payData){
  const modal = document.createElement('div'); modal.className='receipt-modal';
  modal.style.position='fixed'; modal.style.left='0'; modal.style.top='0'; modal.style.right='0'; modal.style.bottom='0'; modal.style.background='rgba(0,0,0,0.6)'; modal.style.display='flex'; modal.style.alignItems='center'; modal.style.justifyContent='center'; modal.style.zIndex='9999';
  const box = document.createElement('div'); box.style.background='#fff'; box.style.padding='20px'; box.style.borderRadius='10px'; box.style.maxWidth='720px'; box.style.width='90%'; box.style.maxHeight='80%'; box.style.overflow='auto';
  const order = payData.order || payData.order_id ? payData.order : null;
  box.innerHTML = `<h3>Receipt — Order ${order?order.id:payData.order_id}</h3>`;
  const items = payData.items || [];
  const list = document.createElement('div'); list.style.marginTop='12px';
  items.forEach(it=>{ const row = document.createElement('div'); row.textContent = `${it.qty} x ${it.product_id} @ ${formatCurrency(parseFloat(it.price))}`; list.appendChild(row); });
  const total = order?order.total:payData.total;
  const tot = document.createElement('div'); tot.style.marginTop='12px'; tot.style.fontWeight='700'; tot.textContent = 'Total: '+formatCurrency(parseFloat(total || 0));
  const close = document.createElement('button'); close.className='primary'; close.textContent='Close'; close.style.marginTop='14px'; close.onclick=()=>{ document.body.removeChild(modal) };
  box.appendChild(list); box.appendChild(tot); box.appendChild(close);
  modal.appendChild(box); document.body.appendChild(modal);
}

// expose for console / inline buttons
window.addToCart = addToCart; window.viewCart = showCartDrawer; window.clearCart = clearCart; window.removeFromCart = removeFromCart;

// initialize product grid on shop pages
document.addEventListener('DOMContentLoaded', ()=>{ loadProducts(); renderCartDrawer(); });

// mobile drawer creation
document.addEventListener('DOMContentLoaded', ()=>{ initSlideshows(); });

function initSlideshows(){
  document.querySelectorAll('.top-slideshow').forEach(slideshow=>{
    const slides = Array.from(slideshow.querySelectorAll('.slide'));
    if(slides.length===0) return;
    let idx=0;
    slides.forEach((s,i)=> s.classList.toggle('active', i===0));
    const dotsWrap = document.createElement('div'); dotsWrap.className='slideshow-dots';
    slides.forEach((_,i)=>{ const b=document.createElement('button'); b.onclick=()=>{ go(i) }; if(i===0) b.classList.add('active'); dotsWrap.appendChild(b) });
    slideshow.appendChild(dotsWrap);
    // add prev/next controls
    const prev = document.createElement('button'); prev.className='slideshow-prev'; prev.textContent='‹';
    const next = document.createElement('button'); next.className='slideshow-next'; next.textContent='›';
    [prev,next].forEach(b=>{ b.style.position='absolute'; b.style.top='50%'; b.style.transform='translateY(-50%)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.border='0'; b.style.width='36px'; b.style.height='36px'; b.style.borderRadius='18px'; b.style.cursor='pointer'; b.style.zIndex='40' });
    prev.style.left='12px'; next.style.right='12px'; slideshow.appendChild(prev); slideshow.appendChild(next);


    // caption element (richer HTML: title + small description)
    const caption = document.createElement('div'); caption.className='slideshow-caption'; caption.style.position='absolute'; caption.style.left='20px'; caption.style.bottom='20px'; caption.style.padding='8px 12px'; caption.style.background='rgba(0,0,0,0.5)'; caption.style.color='#fff'; caption.style.borderRadius='6px'; caption.style.zIndex='40'; caption.style.maxWidth='70%'; caption.style.transition='opacity .18s ease'; caption.style.opacity='0'; slideshow.appendChild(caption);

    function updateCaption(){ const s = slides[idx]; const title = s.dataset.title || ''; const desc = s.dataset.desc || ''; caption.innerHTML = `<div class="slideshow-caption-title" style="font-weight:700">${title}</div><div class="slideshow-caption-desc" style="font-size:13px;margin-top:6px;color:rgba(255,255,255,0.9)">${desc}</div>` }

    let timer = setInterval(()=>{ go((idx+1)%slides.length) },4000);
    function go(n){ slides[idx].classList.remove('active'); dotsWrap.children[idx].classList.remove('active'); idx=n; slides[idx].classList.add('active'); dotsWrap.children[idx].classList.add('active'); updateCaption(); clearInterval(timer); timer=setInterval(()=>{ go((idx+1)%slides.length) },4000) }

    prev.onclick = ()=> go((idx-1+slides.length)%slides.length);
    next.onclick = ()=> go((idx+1)%slides.length);

    // pause on hover and show caption on hover
    slideshow.addEventListener('mouseenter', ()=>{ clearInterval(timer); caption.style.opacity='1' });
    slideshow.addEventListener('mouseleave', ()=>{ clearInterval(timer); timer=setInterval(()=>{ go((idx+1)%slides.length) },4000); caption.style.opacity='0' });

    // initialize caption (hidden by default)
    updateCaption(); caption.style.opacity='0';
  });
}

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


