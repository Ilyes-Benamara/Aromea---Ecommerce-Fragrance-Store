const FRAGRANCES = (window.DB_FRAGRANCES && window.DB_FRAGRANCES.length) ? window.DB_FRAGRANCES : [];

// ── CART ─────────────────────────────────────────────────────
let cart = JSON.parse(localStorage.getItem("aroCart") || "[]");
function saveCart() {
  localStorage.setItem("aroCart", JSON.stringify(cart));
}
function addToCart(fragranceId) {
  const frag = FRAGRANCES.find((f) => f.id === fragranceId);
  if (!frag) return;
  const existing = cart.find((c) => c.id === fragranceId);
  if (existing) existing.qty++;
  else cart.push({ id: fragranceId, qty: 1 });
  saveCart();
  updateCartBadge();
  showToast(`✓ ${frag.name} added to cart`);
}
function removeFromCart(fragranceId) {
  cart = cart.filter((c) => c.id !== fragranceId);
  saveCart();
  updateCartBadge();
  renderCartItems();
}
function updateQty(fragranceId, delta) {
  const item = cart.find((c) => c.id === fragranceId);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) removeFromCart(fragranceId);
  else {
    saveCart();
    updateCartBadge();
    renderCartItems();
  }
}
function updateCartBadge() {
  const total = cart.reduce((s, c) => s + c.qty, 0);
  document.querySelectorAll(".cart-badge").forEach((b) => {
    b.textContent = total;
    b.style.display = total > 0 ? "flex" : "none";
  });
}
function renderCartItems() {
  const container = document.getElementById("cart-items");
  if (!container) return;
  if (cart.length === 0) {
    container.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
    document.getElementById("cart-total-line").style.display = "none";
    return;
  }
  document.getElementById("cart-total-line").style.display = "flex";
  let total = 0;
  container.innerHTML = cart
    .map((item) => {
      const f = FRAGRANCES.find((x) => x.id === item.id);
      if (!f) return "";
      total += f.price * item.qty;
      return `<div class="cart-item">
      <img src="${f.image}" alt="${f.name}">
      <div class="cart-item-info">
        <span class="cart-item-name">${f.name}</span>
        <span class="cart-item-brand">${f.brand}</span>
        <span class="cart-item-price">${(f.price * item.qty).toLocaleString()} DZD</span>
      </div>
      <div class="cart-item-qty">
        <button onclick="updateQty(${f.id},-1)">−</button>
        <span>${item.qty}</span>
        <button onclick="updateQty(${f.id},1)">+</button>
      </div>
      <button class="cart-item-remove" onclick="removeFromCart(${f.id})"><i class="fa-solid fa-xmark"></i></button>
    </div>`;
    })
    .join("");
  document.getElementById("cart-total-amount").textContent =
    total.toLocaleString() + " DZD";
}

// ── AUTH ──────────────────────────────────────────────────────
let currentUser = (function () {
  // Prefer PHP session user (injected by fragrance-data.php) — avoids re-auth
  if (window.PHP_USER) {
    const u = window.PHP_USER;
    u.initials = (u.name || "")
      .split(" ")
      .map((w) => w[0])
      .join("")
      .toUpperCase()
      .slice(0, 2);
    return u;
  }
  return JSON.parse(localStorage.getItem("aroUser") || "null");
})();
function updateAuthUI() {
  const loginIcon = document.getElementById("login-nav-icon");
  const userMenu = document.getElementById("user-nav-menu");
  if (!loginIcon || !userMenu) return;
  if (currentUser) {
    loginIcon.style.display = "none";
    userMenu.style.display = "flex";
    userMenu.querySelector(".user-name").textContent =
      currentUser.name.split(" ")[0];
  } else {
    loginIcon.style.display = "inline";
    userMenu.style.display = "none";
  }
}
function signIn(name, email) {
  currentUser = {
    name,
    email,
    initials: name
      .split(" ")
      .map((w) => w[0])
      .join("")
      .toUpperCase()
      .slice(0, 2),
  };
  localStorage.setItem("aroUser", JSON.stringify(currentUser));
  updateAuthUI();
  closeModal("signin-modal");
  showToast(`Welcome back, ${currentUser.name.split(" ")[0]}! 👋`);
}
function signOut() {
  currentUser = null;
  localStorage.removeItem("aroUser");
  updateAuthUI();
  showToast("Signed out successfully");
}

// ── MODALS ────────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.remove("open");
  if (!document.querySelector(".modal.open")) document.body.style.overflow = "";
}
function toggleCart() {
  const m = document.getElementById("cart-modal");
  if (!m) return;
  if (m.classList.contains("open")) closeModal("cart-modal");
  else {
    renderCartItems();
    openModal("cart-modal");
  }
}


// ── TOAST ─────────────────────────────────────────────────────
function showToast(msg) {
  let t = document.getElementById("toast");
  if (!t) {
    t = document.createElement("div");
    t.id = "toast";
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add("show");
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove("show"), 3000);
}

// ── AI CHAT ───────────────────────────────────────────────────
let chatMessages = [];
function openAIChat() {
  // If somehow no user, open chat anyway with a guest name
  if (!currentUser) {
    currentUser = { name: "Guest", email: "", initials: "G" };
  }
  renderChat();
  openModal("ai-chat-modal");
}
function renderChat() {
  const container = document.getElementById("chat-messages");
  if (!container) return;
  if (chatMessages.length === 0) {
    container.innerHTML = `<div class="chat-msg ai"><div class="chat-bubble">Hello ${currentUser.name.split(" ")[0]}! 🌸 I'm Aromea's AI fragrance advisor. Ask me anything — what to wear for a date, which scent suits the weather, or how to layer fragrances!</div></div>`;
    return;
  }
  container.innerHTML = chatMessages
    .map(
      (m) =>
        `<div class="chat-msg ${m.role}"><div class="chat-bubble">${m.content}</div></div>`,
    )
    .join("");
  container.scrollTop = container.scrollHeight;
}
async function sendChatMessage() {
  const input = document.getElementById("chat-input");
  const msg = input.value.trim();
  if (!msg) return;
  input.value = "";
  chatMessages.push({ role: "user", content: msg });
  renderChat();
  const container = document.getElementById("chat-messages");
  container.innerHTML += `<div class="chat-msg ai thinking"><div class="chat-bubble"><span class="typing-dots"><span></span><span></span><span></span></span></div></div>`;
  container.scrollTop = container.scrollHeight;
  try {
    const systemPrompt = `You are Aromea's AI fragrance advisor for an Algerian luxury fragrance store.
Fragrances available:
${FRAGRANCES.map((f) => `- ${f.name} by ${f.brand}: ${f.price.toLocaleString()} DZD, ${f.gender === "him" ? "For Him" : f.gender === "her" ? "For Her" : "Unisex"}, ${f.classification}, seasons: ${f.seasons.join("/")}, ${f.timeOfDay}. Top: ${f.topNotes.join(", ")}; Heart: ${f.heartNotes.join(", ")}; Base: ${f.baseNotes.join(", ")}.`).join("\n")}
Be helpful, elegant, concise (2-4 sentences). Suggest specific fragrances when relevant.`;
    const response = await fetch("https://api.anthropic.com/v1/messages", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        model: "claude-sonnet-4-20250514",
        max_tokens: 1000,
        system: systemPrompt,
        messages: chatMessages.map((m) => ({
          role: m.role === "ai" ? "assistant" : "user",
          content: m.content,
        })),
      }),
    });
    const data = await response.json();
    const reply =
      data.content?.[0]?.text || "I'm having trouble connecting right now.";
    chatMessages.push({ role: "ai", content: reply });
  } catch (e) {
    chatMessages.push({
      role: "ai",
      content: "Connection issue. Please try again!",
    });
  }
  renderChat();
}

// ── FILTER SIDEBAR ────────────────────────────────────────────
let activeFilters = {
  brands: [],
  priceMin: 0,
  priceMax: Infinity,
  seasons: [],
  timeOfDay: "",
  classification: "",
  gender: "",
};
const allPrices = FRAGRANCES.map((f) => f.price);
const PRICE_MIN_GLOBAL = Math.min(...allPrices);
const PRICE_MAX_GLOBAL = Math.max(...allPrices);

function buildFilterSidebar() {
  const brands = [...new Set(FRAGRANCES.map((f) => f.brand))].sort();
  const sidebar = document.getElementById("filter-sidebar");
  if (!sidebar) return;
  sidebar.innerHTML = `
    <div class="filter-header">
      <h3>Filter</h3>
      <button class="filter-close" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="filter-body">
      <div class="filter-group">
        <h4>Sort by popular brands</h4>
        ${brands.map((b) => `<label class="filter-checkbox"><input type="checkbox" value="${b}" onchange="applyFilters()"> ${b}</label>`).join("")}
      </div>
      <div class="filter-group">
        <h4>Price range</h4>
        <div class="price-inputs">
          <div class="price-input-group">
            <label>Min</label>
            <input type="number" id="price-min-input" value="${PRICE_MIN_GLOBAL}" min="${PRICE_MIN_GLOBAL}" max="${PRICE_MAX_GLOBAL}" step="500" oninput="syncPriceSliders()">
          </div>
          <span class="price-separator">—</span>
          <div class="price-input-group">
            <label>Max</label>
            <input type="number" id="price-max-input" value="${PRICE_MAX_GLOBAL}" min="${PRICE_MIN_GLOBAL}" max="${PRICE_MAX_GLOBAL}" step="500" oninput="syncPriceSliders()">
          </div>
        </div>
        <div class="price-range-track">
          <input type="range" id="price-slider-min" min="${PRICE_MIN_GLOBAL}" max="${PRICE_MAX_GLOBAL}" step="500" value="${PRICE_MIN_GLOBAL}" oninput="syncPriceInputs('min')">
          <input type="range" id="price-slider-max" min="${PRICE_MIN_GLOBAL}" max="${PRICE_MAX_GLOBAL}" step="500" value="${PRICE_MAX_GLOBAL}" oninput="syncPriceInputs('max')">
        </div>
        <div class="price-range-labels">
          <span id="price-min-label">${PRICE_MIN_GLOBAL.toLocaleString()} DZD</span>
          <span id="price-max-label">${PRICE_MAX_GLOBAL.toLocaleString()} DZD</span>
        </div>
      </div>
      <div class="filter-group">
        <h4>Season</h4>
        ${["summer", "spring", "fall", "winter"].map((s) => `<label class="filter-checkbox"><input type="checkbox" value="${s}" onchange="applyFilters()"> ${s.charAt(0).toUpperCase() + s.slice(1)}</label>`).join("")}
      </div>
      <div class="filter-group">
        <h4>Time of Day</h4>
        <label class="filter-checkbox"><input type="radio" name="tod" value="" onchange="applyFilters()" checked> Any</label>
        <label class="filter-checkbox"><input type="radio" name="tod" value="day" onchange="applyFilters()"> ☀️ Day</label>
        <label class="filter-checkbox"><input type="radio" name="tod" value="night" onchange="applyFilters()"> 🌙 Night</label>
      </div>
      <div class="filter-group">
        <h4>Classification</h4>
        <label class="filter-checkbox"><input type="radio" name="cls" value="" onchange="applyFilters()" checked> Any</label>
        <label class="filter-checkbox"><input type="radio" name="cls" value="designer" onchange="applyFilters()"> 💎 Designer</label>
        <label class="filter-checkbox"><input type="radio" name="cls" value="niche" onchange="applyFilters()"> 🌸 Niche</label>
        <label class="filter-checkbox"><input type="radio" name="cls" value="middle-eastern" onchange="applyFilters()"> 🌙 Middle Eastern</label>
      </div>
      <div class="filter-group">
        <h4>Gender</h4>
        <label class="filter-checkbox"><input type="radio" name="gen" value="" onchange="applyFilters()" checked> Any</label>
        <label class="filter-checkbox"><input type="radio" name="gen" value="him" onchange="applyFilters()"> For Him</label>
        <label class="filter-checkbox"><input type="radio" name="gen" value="her" onchange="applyFilters()"> For Her</label>
        <label class="filter-checkbox"><input type="radio" name="gen" value="unisex" onchange="applyFilters()"> Unisex</label>
      </div>
      <button class="filter-reset-btn" onclick="resetFilters()">Reset All Filters</button>
    </div>`;
}

function syncPriceInputs(which) {
  const minSlider = document.getElementById("price-slider-min");
  const maxSlider = document.getElementById("price-slider-max");
  const minInput = document.getElementById("price-min-input");
  const maxInput = document.getElementById("price-max-input");
  if (!minSlider || !maxSlider) return;
  let minVal = parseInt(minSlider.value);
  let maxVal = parseInt(maxSlider.value);
  if (minVal > maxVal - 500) {
    if (which === "min") minSlider.value = maxVal - 500;
    else maxSlider.value = minVal + 500;
    minVal = parseInt(minSlider.value);
    maxVal = parseInt(maxSlider.value);
  }
  if (minInput) minInput.value = minVal;
  if (maxInput) maxInput.value = maxVal;
  document.getElementById("price-min-label").textContent =
    minVal.toLocaleString() + " DZD";
  document.getElementById("price-max-label").textContent =
    maxVal.toLocaleString() + " DZD";
  applyFilters();
}

function syncPriceSliders() {
  const minSlider = document.getElementById("price-slider-min");
  const maxSlider = document.getElementById("price-slider-max");
  const minInput = document.getElementById("price-min-input");
  const maxInput = document.getElementById("price-max-input");
  if (!minSlider || !maxSlider || !minInput || !maxInput) return;
  let minVal = Math.max(
    PRICE_MIN_GLOBAL,
    Math.min(parseInt(minInput.value) || PRICE_MIN_GLOBAL, PRICE_MAX_GLOBAL),
  );
  let maxVal = Math.max(
    PRICE_MIN_GLOBAL,
    Math.min(parseInt(maxInput.value) || PRICE_MAX_GLOBAL, PRICE_MAX_GLOBAL),
  );
  if (minVal > maxVal - 500) maxVal = minVal + 500;
  minSlider.value = minVal;
  maxSlider.value = maxVal;
  document.getElementById("price-min-label").textContent =
    minVal.toLocaleString() + " DZD";
  document.getElementById("price-max-label").textContent =
    maxVal.toLocaleString() + " DZD";
  applyFilters();
}

function openSidebar() {
  buildFilterSidebar();
  document.getElementById("filter-sidebar").classList.add("open");
  document.getElementById("filter-overlay").classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeSidebar() {
  document.getElementById("filter-sidebar")?.classList.remove("open");
  document.getElementById("filter-overlay")?.classList.remove("open");
  document.body.style.overflow = "";
}

function applyFilters() {
  const checkedBrands = [
    ...document.querySelectorAll(
      "#filter-sidebar .filter-group:nth-child(1) input:checked",
    ),
  ].map((i) => i.value);
  const priceMin = parseInt(
    document.getElementById("price-slider-min")?.value || PRICE_MIN_GLOBAL,
  );
  const priceMax = parseInt(
    document.getElementById("price-slider-max")?.value || PRICE_MAX_GLOBAL,
  );
  const seasonChecked = [
    ...document.querySelectorAll(
      "#filter-sidebar .filter-group:nth-child(3) input:checked",
    ),
  ].map((i) => i.value);
  const tod =
    document.querySelector("#filter-sidebar input[name=tod]:checked")?.value ||
    "";
  const cls =
    document.querySelector("#filter-sidebar input[name=cls]:checked")?.value ||
    "";
  const gen =
    document.querySelector("#filter-sidebar input[name=gen]:checked")?.value ||
    "";
  filterAndRender({
    brands: checkedBrands,
    priceMin,
    priceMax,
    seasons: seasonChecked,
    timeOfDay: tod,
    classification: cls,
    gender: gen,
  });
}

function resetFilters() {
  buildFilterSidebar();
  filterAndRender({
    brands: [],
    priceMin: 0,
    priceMax: Infinity,
    seasons: [],
    timeOfDay: "",
    classification: "",
    gender: "",
  });
}

let currentSearch = "";
function filterAndRender(filters) {
  activeFilters = filters;
  const cards = document.querySelectorAll(".explore-offer[data-id]");
  if (!cards.length) return;
  cards.forEach((card) => {
    const f = FRAGRANCES.find((x) => x.id === parseInt(card.dataset.id));
    if (!f) return;
    let show = true;
    if (filters.brands.length > 0 && !filters.brands.includes(f.brand))
      show = false;
    if (f.price < filters.priceMin || f.price > filters.priceMax) show = false;
    if (
      filters.seasons.length > 0 &&
      !filters.seasons.some((s) => f.seasons.includes(s))
    )
      show = false;
    if (filters.timeOfDay && f.timeOfDay !== filters.timeOfDay) show = false;
    if (filters.classification && f.classification !== filters.classification)
      show = false;
    if (filters.gender && f.gender !== filters.gender) show = false;
    if (currentSearch) {
      const q = currentSearch.toLowerCase();
      if (
        !f.name.toLowerCase().includes(q) &&
        !f.brand.toLowerCase().includes(q)
      )
        show = false;
    }
    card.style.display = show ? "" : "none";
  });
  const visible = [...cards].filter((c) => c.style.display !== "none").length;
  const noResults = document.getElementById("no-results");
  if (noResults) noResults.style.display = visible === 0 ? "block" : "none";
}

// ── SEARCH ────────────────────────────────────────────────────
function initSearch() {
  document.querySelectorAll(".search-bar input").forEach((input) => {
    input.addEventListener("input", () => {
      currentSearch = input.value.trim();
      filterAndRender(activeFilters);
    });
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        currentSearch = input.value.trim();
        filterAndRender(activeFilters);
      }
    });
  });
  document.querySelectorAll("#search-button").forEach((btn) => {
    btn.addEventListener("click", () => {
      const input = btn.closest(".search-bar")?.querySelector("input");
      if (input) {
        currentSearch = input.value.trim();
        filterAndRender(activeFilters);
      }
    });
  });
}

// ── SMOOTH SCROLL + SCROLL REVEAL ────────────────────────────
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", (e) => {
      const target = document.querySelector(a.getAttribute("href"));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    });
  });
}
function initScrollReveal() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add("revealed");
          observer.unobserve(e.target);
        }
      });
    },
    { threshold: 0.08 },
  );
  document
    .querySelectorAll(
      ".collection-card, .explore-card, .collection-offer, .explore-offer, .fragrance-attributes, .about-block, .value-card",
    )
    .forEach((el) => {
      el.classList.add("reveal-on-scroll");
      observer.observe(el);
    });
}
function initStickyHeader() {
  const header = document.querySelector("header");
  if (!header) return;
  let lastY = 0;
  window.addEventListener(
    "scroll",
    () => {
      const y = window.scrollY;
      header.classList.toggle("scrolled", y > 50);
      if (y > lastY + 10 && y > 200) header.classList.add("header-hidden");
      else if (y < lastY) header.classList.remove("header-hidden");
      lastY = y;
    },
    { passive: true },
  );
}
function initPageTransitions() {
  document
    .querySelectorAll('a:not([href^="#"]):not([href^="mailto"])')
    .forEach((a) => {
      const href = a.getAttribute("href");
      if (!href || href === "#" || href.startsWith("http")) return;
      a.addEventListener("click", (e) => {
        e.preventDefault();
        document.body.classList.add("page-exit");
        setTimeout(() => {
          window.location.href = href;
        }, 300);
      });
    });
  document.body.classList.add("page-enter");
  requestAnimationFrame(() => document.body.classList.add("page-entered"));
}

// ── DETAILS PAGE ──────────────────────────────────────────────
function renderDetailsPage() {
  const params = new URLSearchParams(window.location.search);
  const id = parseInt(params.get("id") || 1);
  const f = FRAGRANCES.find((x) => x.id === id) || FRAGRANCES[0];
  document.title = `Aromea — ${f.name}`;
  const title = document.getElementById("details-title");
  if (title)
    title.innerHTML = `<b>${f.brand}</b> ${f.name.replace(f.brand, "").trim()} <span class="${f.gender === "him" ? "for-him" : f.gender === "her" ? "for-her" : "for-him-her"}">${f.gender === "him" ? "For Him" : f.gender === "her" ? "For Her" : "Unisex"}</span>`;
  const priceEl = document.getElementById("details-price");
  if (priceEl) priceEl.textContent = f.price.toLocaleString() + " DZD";
  const img = document.getElementById("details-img");
  if (img) {
    img.src = f.image;
    img.alt = f.name;
  }
  // Notes with image support
  const buildNotes = (arr, containerId) => {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = arr
      .map((n) => {
        const imgName = n.toLowerCase().replace(/\s+/g, "-");
        return `<div class="note-item">
        <div class="note-img-wrap">
          <img src="images/notes/${imgName}.png" alt="${n}" onerror="this.parentElement.classList.add('no-img')">
        </div>
        <span class="note-name">${n}</span>
      </div>`;
      })
      .join("");
  };
  buildNotes(f.topNotes, "top-notes");
  buildNotes(f.heartNotes, "heart-notes");
  buildNotes(f.baseNotes, "base-notes");
  const accordsEl = document.getElementById("accords-bars");
  if (accordsEl) {
    accordsEl.innerHTML = f.accords
      .map(
        (a) =>
          `<div class="fragrance-accord" style="background:${a.color};width:${a.pct}%;color:${a.pct < 60 ? "#fff" : "inherit"}">${a.name}</div>`,
      )
      .join("");
  }
  const desc = document.getElementById("details-description");
  if (desc) desc.textContent = f.description;
  const seasonEl = document.getElementById("details-seasons");
  if (seasonEl) {
    const icons = {
      summer: "images/summer logo.png",
      spring: "images/spring logo.png",
      fall: "images/fall logo.png",
      winter: "images/winter logo.png",
    };
    seasonEl.innerHTML = f.seasons
      .map(
        (s) =>
          `<img src="${icons[s]}" alt="${s}" class="season-icon-sm" title="${s.charAt(0).toUpperCase() + s.slice(1)}">`,
      )
      .join("");
  }
  const timeEl = document.getElementById("details-time");
  if (timeEl)
    timeEl.innerHTML =
      f.timeOfDay === "day"
        ? '<img src="images/morning icon.png" alt="day" class="time-icon-sm"> Day'
        : '<img src="images/night icon.png" alt="night" class="time-icon-sm"> Night';
  const clsEl = document.getElementById("details-classification");
  if (clsEl) {
    const clsMap = {
      designer: "Designer",
      niche: "Niche",
      "middle-eastern": "Middle Eastern",
    };
    clsEl.textContent = clsMap[f.classification] || f.classification;
  }
  const addBtn = document.getElementById("purchaseAddToCart");
  if (addBtn) addBtn.onclick = () => addToCart(f.id);
  // Large season display
  setTimeout(() => {
    const large = document.getElementById("details-seasons-large");
    const icons = {
      summer: "images/summer logo.png",
      spring: "images/spring logo.png",
      fall: "images/fall logo.png",
      winter: "images/winter logo.png",
    };
    if (large)
      large.innerHTML =
        "<h4>Seasons</h4>" +
        f.seasons
          .map(
            (s) =>
              `<img src="${icons[s]}" alt="${s}" style="height:80px;width:80px;">`,
          )
          .join("") +
        `<h4>Time</h4><img src="images/${f.timeOfDay === "day" ? "morning icon" : "night icon"}.png" alt="${f.timeOfDay}" style="height:80px;width:80px;">`;
  }, 50);
}

// ── INJECT GLOBAL UI ─────────────────────────────────────────
function injectGlobalUI() {
  document.querySelectorAll(".nav-links").forEach((nav) => {
    const loginLink = nav.querySelector("#login-nav-icon");
    if (loginLink)
      loginLink.onclick = (e) => {
        e.preventDefault();
        openModal("signin-modal");
      };
    const cartA = document.createElement("a");
    cartA.href = "#";
    cartA.className = "cart-nav-btn";
    cartA.innerHTML =
      '<i class="fa-solid fa-bag-shopping"></i><span class="cart-badge" style="display:none">0</span>';
    cartA.onclick = (e) => {
      e.preventDefault();
      toggleCart();
    };
    nav.appendChild(cartA);
    const userDiv = document.createElement("div");
    userDiv.id = "user-nav-menu";
    userDiv.style.display = "none";
    userDiv.innerHTML = `<span class="user-name"></span><button class="sign-out-btn" onclick="signOut()">Sign Out</button>`;
    nav.appendChild(userDiv);
    // Bind to existing PHP-rendered hamburger button
    const existingHamburger = document.getElementById("hamburger-btn");
    if (existingHamburger) {
      existingHamburger.onclick = () => nav.classList.toggle("nav-open");
    }
  });

  document.body.insertAdjacentHTML(
    "beforeend",
    `
    <div id="filter-overlay" onclick="closeSidebar()"></div>
    <aside id="filter-sidebar"></aside>

    <!-- SIGN IN MODAL -->
    <div id="signin-modal" class="modal">
      <div class="modal-box modal-auth">
        <button class="modal-close" onclick="closeModal('signin-modal')"><i class="fa-solid fa-xmark"></i></button>
        <div class="auth-brand">Aromea</div>
        <h2>Sign In</h2>
        <p class="auth-subtitle">Welcome back to Aromea</p>
        <div class="auth-form">
          <div class="auth-input-group">
            <label>Email</label>
            <input type="email" id="signin-email" placeholder="your@email.com">
          </div>
          <div class="auth-input-group">
            <label>Password</label>
            <input type="password" id="signin-password" placeholder="••••••••">
          </div>
          <button class="auth-btn" onclick="handleSignIn()">Sign In</button>
          <p class="auth-switch">Don't have an account? <a href="register.php">Create one</a></p>
        </div>
      </div>
    </div>

    <!-- CART MODAL -->
    <div id="cart-modal" class="modal">
      <div class="modal-box modal-cart">
        <div class="cart-header">
          <h2><i class="fa-solid fa-bag-shopping"></i> Your Cart</h2>
          <button class="modal-close" onclick="closeModal('cart-modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="cart-items"></div>
        <div id="cart-total-line" style="display:none">
          <span>Total</span><span id="cart-total-amount"></span>
        </div>
        <button class="checkout-btn" onclick="showToast('Checkout coming soon! 🛍️')">Proceed to Checkout</button>
      </div>
    </div>

    <!-- AI CHAT MODAL -->
    <div id="ai-chat-modal" class="modal">
      <div class="modal-box modal-chat">
        <div class="chat-header">
          <div class="chat-header-info">
            <div class="chat-ai-avatar"><i class="fa-brands fa-openai"></i></div>
            <div><h3>Aromea AI</h3><span class="chat-status">Fragrance Advisor • Online</span></div>
          </div>
          <button class="modal-close" onclick="closeModal('ai-chat-modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="chat-messages" class="chat-messages"></div>
        <div class="chat-input-row">
          <input type="text" id="chat-input" placeholder="Ask about fragrances..." onkeydown="if(event.key==='Enter')sendChatMessage()">
          <button onclick="sendChatMessage()" class="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
      </div>
    </div>
    </div>

    <div id="toast"></div>
  `,
  );

  document.querySelectorAll(".modal").forEach((m) =>
    m.addEventListener("click", (e) => {
      if (e.target === m) closeModal(m.id);
    }),
  );
  document
    .querySelectorAll("#filter-button")
    .forEach((btn) => btn.addEventListener("click", openSidebar));
  document
    .querySelectorAll("#ai-button")
    .forEach((btn) => btn.addEventListener("click", openAIChat));
}

function handleSignIn() {
  const email = document.getElementById("signin-email").value.trim();
  const password = document.getElementById("signin-password").value;
  if (!email || !password) {
    showToast("Please fill in all fields");
    return;
  }
  const name = email
    .split("@")[0]
    .replace(/[._-]/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
  signIn(name, email);
}

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
  injectGlobalUI();
  updateAuthUI();
  updateCartBadge();
  initSearch();
  initSmoothScroll();
  initScrollReveal();
  initStickyHeader();
  initPageTransitions();
  if (document.getElementById("details-title")) renderDetailsPage();
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      document.querySelectorAll(".modal.open").forEach((m) => closeModal(m.id));
      closeSidebar();
    }
  });
});
