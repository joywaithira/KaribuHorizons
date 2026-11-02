document.addEventListener('click', function (e) {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;

  // mobile menu toggle
  if (target.closest('.mobile-toggle')) {
    setMobileMenu(!document.documentElement.classList.contains('nav-open'));
    return;
  }

  if (target.closest('.mobile-menu-close, .mobile-menu-backdrop') || target.closest('.mobile-menu-drawer a')) {
    setMobileMenu(false);
    return;
  }

  // toggle dropdowns
  const dropdownToggle = target.closest('.dropdown-toggle');
  if (dropdownToggle) {
    const menu = dropdownToggle.nextElementSibling;
    // toggle arrow class
    const isOpening = menu.style.display !== 'block';
    dropdownToggle.classList.toggle('open', isOpening);
    dropdownToggle.setAttribute('aria-expanded', String(isOpening));
    // close others
    document.querySelectorAll('.dropdown-menu').forEach(m => {
      if (m !== menu) {
        m.style.display = 'none';
        m.previousElementSibling?.classList.remove('open');
        m.previousElementSibling?.setAttribute('aria-expanded', 'false');
      }
    });
    menu.style.display = isOpening ? 'block' : 'none';
    return;
  }

  // close any open menus when clicking outside
  document.querySelectorAll('.dropdown-menu').forEach(m => {
    if (!m.contains(target)) {
      m.style.display = 'none';
      m.previousElementSibling?.classList.remove('open');
      m.previousElementSibling?.setAttribute('aria-expanded', 'false');
    }
  });
});

document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    setMobileMenu(false);
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.style.display = 'none';
      menu.previousElementSibling?.classList.remove('open');
      menu.previousElementSibling?.setAttribute('aria-expanded', 'false');
    });
  }
});

function setMobileMenu(isOpen){
  document.documentElement.classList.toggle('nav-open', isOpen);
  document.body.classList.toggle('menu-open', isOpen);
  document.querySelector('.mobile-toggle')?.setAttribute('aria-expanded', String(isOpen));
}

function formatKes(amount){ return 'KSh ' + Number(amount || 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

async function loadBlogPosts(){
  try {
    let res = await fetch('api/posts.php', {cache: 'no-store'});
    if (!res.ok) res = await fetch('assets/data/blog.json');
    const posts = await res.json();
    renderBlogPosts(posts);
  } catch(err){ console.error('Failed to load blog posts', err) }
}

async function loadTrips(category){
  try {
    const res = await fetch(`api/trips.php?category=${encodeURIComponent(category)}`, {cache: 'no-store'});
    if (!res.ok) throw new Error('Trip request failed');
    renderTrips(await res.json());
  } catch(err){ console.error('Failed to load trips', err) }
}

function renderTrips(trips){
  const grid = document.querySelector('[data-trip-grid]');
  if(!grid) return;
  const controls = document.createElement('div');
  controls.className = 'trip-filters';
  controls.innerHTML = `
    <label class="trip-search-label">
      <span>Find your next adventure</span>
      <input type="search" class="trip-search" placeholder="Search destinations or experiences" aria-label="Search trips">
    </label>
    <label class="trip-sort-label">
      <span>Sort by</span>
      <select class="trip-sort" aria-label="Sort trips">
        <option value="featured">Featured</option>
        <option value="price-asc">Price: low to high</option>
        <option value="price-desc">Price: high to low</option>
      </select>
    </label>
    <p class="trip-results" aria-live="polite"></p>
  `;
  grid.before(controls);

  grid.innerHTML = trips.map(trip => `
    <article class="card">
      ${trip.image ? `<img src="${trip.image}" alt="${trip.title}">` : ''}
      <h3>${trip.title}</h3>
      <p class="muted">From ${formatKes(trip.price)} per person</p>
      <p>${trip.description}</p>
      <div style="display:flex;gap:8px;margin-top:10px"><a class="secondary" href="bookings.html?trip=${encodeURIComponent(trip.title)}">Book Now</a>${trip.detail_link ? `<a href="${trip.detail_link}">View details</a>` : ''}</div>
    </article>`).join('');

  const search = controls.querySelector('.trip-search');
  const sort = controls.querySelector('.trip-sort');
  const results = controls.querySelector('.trip-results');
  Array.from(grid.querySelectorAll('.card')).forEach((card, index) => {
    card.dataset.price = String(Number(trips[index]?.price || 0));
    card.dataset.featuredIndex = String(index);
  });
  const updateTrips = () => {
    const query = search.value.trim().toLowerCase();
    const cards = Array.from(grid.querySelectorAll('.card'));
    cards.sort((a, b) => {
      if (sort.value === 'featured') return Number(a.dataset.featuredIndex) - Number(b.dataset.featuredIndex);
      const priceA = Number(a.dataset.price || 0);
      const priceB = Number(b.dataset.price || 0);
      return sort.value === 'price-asc' ? priceA - priceB : priceB - priceA;
    });
    let visible = 0;
    cards.forEach(card => {
      const matches = card.textContent.toLowerCase().includes(query);
      card.hidden = !matches;
      if (matches) visible++;
      grid.appendChild(card);
    });
    results.textContent = `${visible} ${visible === 1 ? 'trip' : 'trips'} found`;
  };
  search.addEventListener('input', updateTrips);
  sort.addEventListener('change', updateTrips);
  updateTrips();
}

const countryCallingCodes = `
Afghanistan|93,Albania|355,Algeria|213,American Samoa|1684,Andorra|376,Angola|244,Anguilla|1264,Antigua and Barbuda|1268,Argentina|54,Armenia|374,Aruba|297,Australia|61,Austria|43,Azerbaijan|994,Bahamas|1242,Bahrain|973,Bangladesh|880,Barbados|1246,Belarus|375,Belgium|32,Belize|501,Benin|229,Bermuda|1441,Bhutan|975,Bolivia|591,Bosnia and Herzegovina|387,Botswana|267,Brazil|55,British Virgin Islands|1284,Brunei|673,Bulgaria|359,Burkina Faso|226,Burundi|257,Cambodia|855,Cameroon|237,Canada|1,Cape Verde|238,Cayman Islands|1345,Central African Republic|236,Chad|235,Chile|56,China|86,Colombia|57,Comoros|269,Congo|242,Congo (DRC)|243,Cook Islands|682,Costa Rica|506,Croatia|385,Cuba|53,Curaçao|599,Cyprus|357,Czechia|420,Denmark|45,Djibouti|253,Dominica|1767,Dominican Republic|1809,Ecuador|593,Egypt|20,El Salvador|503,Equatorial Guinea|240,Eritrea|291,Estonia|372,Eswatini|268,Ethiopia|251,Falkland Islands|500,Faroe Islands|298,Fiji|679,Finland|358,France|33,French Guiana|594,French Polynesia|689,Gabon|241,Gambia|220,Georgia|995,Germany|49,Ghana|233,Gibraltar|350,Greece|30,Greenland|299,Grenada|1473,Guadeloupe|590,Guam|1671,Guatemala|502,Guernsey|441481,Guinea|224,Guinea-Bissau|245,Guyana|592,Haiti|509,Honduras|504,Hong Kong|852,Hungary|36,Iceland|354,India|91,Indonesia|62,Iran|98,Iraq|964,Ireland|353,Isle of Man|441624,Israel|972,Italy|39,Ivory Coast|225,Jamaica|1876,Japan|81,Jersey|441534,Jordan|962,Kazakhstan|7,Kenya|254,Kiribati|686,Kosovo|383,Kuwait|965,Kyrgyzstan|996,Laos|856,Latvia|371,Lebanon|961,Lesotho|266,Liberia|231,Libya|218,Liechtenstein|423,Lithuania|370,Luxembourg|352,Macao|853,Madagascar|261,Malawi|265,Malaysia|60,Maldives|960,Mali|223,Malta|356,Marshall Islands|692,Martinique|596,Mauritania|222,Mauritius|230,Mayotte|262,Mexico|52,Micronesia|691,Moldova|373,Monaco|377,Mongolia|976,Montenegro|382,Montserrat|1664,Morocco|212,Mozambique|258,Myanmar|95,Namibia|264,Nauru|674,Nepal|977,Netherlands|31,New Caledonia|687,New Zealand|64,Nicaragua|505,Niger|227,Nigeria|234,Niue|683,North Korea|850,North Macedonia|389,Northern Mariana Islands|1670,Norway|47,Oman|968,Pakistan|92,Palau|680,Palestine|970,Panama|507,Papua New Guinea|675,Paraguay|595,Peru|51,Philippines|63,Poland|48,Portugal|351,Puerto Rico|1787,Qatar|974,Reunion|262,Romania|40,Russia|7,Rwanda|250,Saint Barthelemy|590,Saint Helena|290,Saint Kitts and Nevis|1869,Saint Lucia|1758,Saint Martin|590,Saint Pierre and Miquelon|508,Saint Vincent and the Grenadines|1784,Samoa|685,San Marino|378,Sao Tome and Principe|239,Saudi Arabia|966,Senegal|221,Serbia|381,Seychelles|248,Sierra Leone|232,Singapore|65,Sint Maarten|1721,Slovakia|421,Slovenia|386,Solomon Islands|677,Somalia|252,South Africa|27,South Korea|82,South Sudan|211,Spain|34,Sri Lanka|94,Sudan|249,Suriname|597,Sweden|46,Switzerland|41,Syria|963,Taiwan|886,Tajikistan|992,Tanzania|255,Thailand|66,Timor-Leste|670,Togo|228,Tokelau|690,Tonga|676,Trinidad and Tobago|1868,Tunisia|216,Turkey|90,Turkmenistan|993,Turks and Caicos Islands|1649,Tuvalu|688,Uganda|256,Ukraine|380,United Arab Emirates|971,United Kingdom|44,United States|1,Uruguay|598,Uzbekistan|998,Vanuatu|678,Vatican City|379,Venezuela|58,Vietnam|84,Wallis and Futuna|681,Yemen|967,Zambia|260,Zimbabwe|263
`.trim().split(',').map(entry => {
  const [name, code] = entry.split('|');
  return {name, code};
});

function initCountryCallingCodes(){
  document.querySelectorAll('select.country-code').forEach(select => {
    if (select.options.length) return;
    countryCallingCodes.forEach(country => {
      const option = document.createElement('option');
      option.value = `+${country.code}`;
      option.textContent = `${country.name} (+${country.code})`;
      option.selected = country.name === 'Kenya';
      select.appendChild(option);
    });
  });
}

function ensureDesktopShopLink(){
  document.querySelectorAll('.nav-links').forEach(nav => {
    if (nav.querySelector('a[href="/shop/"]')) return;
    const shopLink = document.createElement('a');
    shopLink.href = '/shop/';
    shopLink.textContent = 'Shop';
    nav.appendChild(shopLink);
  });
}

// initialize page-specific content after the DOM is ready
document.addEventListener('DOMContentLoaded', ()=>{
  initCountryCallingCodes();
  if (document.body.dataset.page === 'blog') {
    loadBlogPosts();
  }

  const tripPage = document.querySelector('[data-trip-category]');
  if (tripPage) loadTrips(tripPage.dataset.tripCategory);

  initSlideshows();
  ensureMobileMenu();
  initActiveNavigation();
  initScrollInteractions();
});

function initActiveNavigation(){
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .mobile-menu-drawer a').forEach(link => {
    const linkPage = new URL(link.href, window.location.href).pathname.split('/').pop();
    if (linkPage === currentPage) link.setAttribute('aria-current', 'page');
  });
}

function initScrollInteractions(){
  const progress = document.createElement('div');
  progress.className = 'reading-progress';
  progress.setAttribute('aria-hidden', 'true');
  document.body.appendChild(progress);

  const topButton = document.createElement('button');
  topButton.className = 'back-to-top';
  topButton.type = 'button';
  topButton.setAttribute('aria-label', 'Back to top');
  topButton.textContent = '↑';
  topButton.addEventListener('click', () => window.scrollTo({top: 0, behavior: 'smooth'}));
  document.body.appendChild(topButton);

  const updateScroll = () => {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    const percent = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
    progress.style.transform = `scaleX(${percent / 100})`;
    topButton.classList.toggle('visible', window.scrollY > 480);
  };
  window.addEventListener('scroll', updateScroll, {passive: true});
  updateScroll();

  const revealItems = document.querySelectorAll('main h2, main > h3, main .card, main .about-card, main .testimonial');
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {threshold: 0.08, rootMargin: '0px 0px -24px 0px'});
    revealItems.forEach((item, index) => {
      item.classList.add('reveal-on-scroll');
      item.style.setProperty('--reveal-delay', `${Math.min(index % 4, 3) * 65}ms`);
      observer.observe(item);
    });
  }
}

// mobile drawer creation

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


    let timer = setInterval(()=>{ go((idx+1)%slides.length) },4000);
    function go(n){ slides[idx].classList.remove('active'); dotsWrap.children[idx].classList.remove('active'); idx=n; slides[idx].classList.add('active'); dotsWrap.children[idx].classList.add('active'); clearInterval(timer); timer=setInterval(()=>{ go((idx+1)%slides.length) },4000) }

    prev.onclick = ()=> go((idx-1+slides.length)%slides.length);
    next.onclick = ()=> go((idx+1)%slides.length);

    slideshow.addEventListener('mouseenter', ()=>{ clearInterval(timer) });
    slideshow.addEventListener('mouseleave', ()=>{ clearInterval(timer); timer=setInterval(()=>{ go((idx+1)%slides.length) },4000); });
  });
}

function ensureMobileMenu(){
  ensureDesktopShopLink();
  if(document.querySelector('.mobile-menu-drawer')) return;
    const rootPrefix = window.location.pathname.includes('/tripspages/') ? '../' : '';
  const d = document.createElement('nav'); d.className='mobile-menu-drawer';
  d.innerHTML = `
    <div class="mobile-menu-heading"><strong>Explore</strong><button class="mobile-menu-close" type="button" aria-label="Close menu">✕</button></div>
      <a href="${rootPrefix}index.html">Home</a>
      <a href="${rootPrefix}about.html">About</a>
      <a href="${rootPrefix}packages.html">Packages</a>
      <a href="${rootPrefix}safaritours.html">Safari Trips</a>
      <a href="${rootPrefix}kenyantrips.html">Kenyan Trips</a>
      <a href="${rootPrefix}internationaltrips.html">International Trips</a>
      <a href="${rootPrefix}blog.html">Blog</a>
      <a href="/shop/">Shop</a>
      <a href="${rootPrefix}contact.html">Contact</a>
      <a href="${rootPrefix}terms.html">Terms</a>
  `;
  document.body.appendChild(d);
  const backdrop = document.createElement('button');
  backdrop.className = 'mobile-menu-backdrop';
  backdrop.type = 'button';
  backdrop.setAttribute('aria-label', 'Close menu');
  document.body.appendChild(backdrop);
}

function renderBlogPosts(posts){
  const feed = document.querySelector('#blog-posts');
  if(!feed) return;
  feed.innerHTML = '';

  posts.forEach(post => {
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `
      ${post.cover ? `<img src="${post.cover}" alt="${post.title}">` : ''}
      <h3>${post.title}</h3>
      <p class="muted">${post.date} • ${post.category}</p>
      <p>${post.summary}</p>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:8px">
        <a class="secondary" href="${post.link || 'blog.html'}">Read story</a>
      </div>
    `;
    feed.appendChild(card);
  });
}


