/* Freshcomart header behaviour (loaded after nav.html is injected or embedded).
   Uses Firebase Auth & Firestore via assets/js/firebase-service.js. */

function toggleDropdown() {
  var menu = document.getElementById("categoryMenu");
  var arrow = document.getElementById("arrowIcon");
  if (menu) menu.classList.toggle("show");
  if (arrow) arrow.classList.toggle("rotate");
}

function openSidebar() {
  var sb = document.getElementById("mobileSidebar");
  var ov = document.getElementById("overlay");
  if (sb) sb.classList.add("open");
  if (ov) ov.classList.add("show");
  document.body.style.overflow = "hidden";
}

function closeSidebar() {
  var sb = document.getElementById("mobileSidebar");
  var ov = document.getElementById("overlay");
  if (sb) sb.classList.remove("open");
  if (ov) ov.classList.remove("show");
  document.body.style.overflow = "";
}

function toggleMobileCategories() {
  var list = document.getElementById("mobileCategoryList");
  if (list) list.classList.toggle("show");
}

function filterCategory(id) {
  var base = typeof window.FC_BASE === "string" ? window.FC_BASE : "../";
  window.location = base + "product/product.html?category=" + id;
}

(function () {
  var isProductDir = /\/product(\/|$)/i.test(location.pathname);
  var base = typeof window.FC_BASE === "string" ? window.FC_BASE : (isProductDir ? "../" : "");
  window.FC_BASE = base;

  // Rewrite relative paths when embedded in root pages vs product/ pages
  var cssEl = document.getElementById("fcCustomUiCss");
  if (cssEl) cssEl.setAttribute("href", base + "assets/css/custom-ui.css");

  document.querySelectorAll("[data-fc-href]").forEach(function (el) {
    el.setAttribute("href", base + el.getAttribute("data-fc-href"));
  });
  document.querySelectorAll("[data-fc-src]").forEach(function (el) {
    el.setAttribute("src", base + el.getAttribute("data-fc-src"));
  });
  document.querySelectorAll("[data-fc-action]").forEach(function (el) {
    el.setAttribute("action", base + el.getAttribute("data-fc-action"));
    el.dataset.base = base;
  });

  var drawer = document.getElementById("fcDrawer");
  if (drawer) {
    drawer.dataset.base = base;
  }

  // Highlight active quick category in secondary bar
  var bar = document.getElementById("fcCatbar");
  if (bar) {
    var cur = new URLSearchParams(location.search).get("category") || "";
    if (!/product\.(html|php)$/i.test(location.pathname)) cur = null;
    bar.querySelectorAll("a").forEach(function (a) {
      var on = cur !== null && (a.dataset.cats || "") === (cur === "0" ? "" : cur);
      a.classList.toggle("active", on);
    });
  }

  // Profile dropdown toggle
  document.querySelectorAll(".profile-dropdown-wrapper").forEach(function (wrapper) {
    var logo = wrapper.querySelector(".mt-logo");
    var menu = wrapper.querySelector(".profile-dropdown-menu");
    if (!logo || !menu || logo.dataset.bound) return;
    logo.dataset.bound = "1";
    function toggle(e) {
      e.stopPropagation();
      menu.classList.toggle("show");
    }
    logo.addEventListener("click", toggle);
    logo.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggle(e);
      }
    });
  });
  document.addEventListener("click", function () {
    document.querySelectorAll(".profile-dropdown-menu").forEach(function (m) {
      m.classList.remove("show");
    });
  });

  // Load Firebase service dynamically to power Auth, Mini-Cart Drawer, and Live Search
  var serviceUrl = new URL(base + "assets/js/firebase-service.js", location.href).href;
  import(serviceUrl)
    .then(function (fb) {
      window.FCFirebase = fb;
      initNavWithFirebase(fb, base);
    })
    .catch(function (err) {
      console.error("Failed to load Firebase service in navBar:", err);
    });

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : s;
    return d.innerHTML;
  }

  function setBadges(count) {
    document.querySelectorAll("#fcCartBadge, .fc-cart-badge-mirror, .cart-count").forEach(function (b) {
      b.textContent = count;
      b.classList.remove("bump");
      void b.offsetWidth;
      b.classList.add("bump");
    });
    var c = document.getElementById("fcDrawerCount");
    if (c) c.textContent = count ? "(" + count + ")" : "";
  }

  function initNavWithFirebase(fb, base) {
    var currentUser = null;
    var cachedCatalog = null;

    // 1. Auth & Header Profile State
    fb.waitForAuthUser().then(async function (user) {
      currentUser = user;
      var guestLogin = document.getElementById("fcGuestLoginBtn");
      var guestSignup = document.getElementById("fcGuestSignupBtn");
      var authOrders = document.getElementById("fcAuthOrdersBtn");
      var profileWrap = document.getElementById("fcProfileDropdownWrapper");
      var mobileLogin = document.getElementById("fcMobileLoginLink");
      var mobileSignup = document.getElementById("fcMobileSignupLink");
      var mobileOrders = document.getElementById("fcMobileOrdersLink");
      var mobileProfile = document.getElementById("fcMobileProfileLink");
      var mobileLogout = document.getElementById("fcMobileLogoutLink");
      var bottomCheckout = document.getElementById("fcBottomCheckout");
      var cartTrigger = document.getElementById("fcCartTrigger");

      if (user) {
        if (guestLogin) guestLogin.style.display = "none";
        if (guestSignup) guestSignup.style.display = "none";
        if (authOrders) authOrders.style.display = "";
        if (profileWrap) profileWrap.style.display = "";
        if (mobileLogin) mobileLogin.style.display = "none";
        if (mobileSignup) mobileSignup.style.display = "none";
        if (mobileOrders) mobileOrders.style.display = "";
        if (mobileProfile) mobileProfile.style.display = "";
        if (mobileLogout) mobileLogout.style.display = "";
        if (bottomCheckout) bottomCheckout.setAttribute("href", base + "proceed.html");

        var profile = await fb.getUserProfile(user.uid);
        var fName = (profile && profile.first_name) ? profile.first_name : (user.displayName || "Customer");
        var lName = (profile && profile.last_name) ? profile.last_name : "";
        var initials = ((fName.charAt(0) || "") + (lName.charAt(0) || "")).toUpperCase() || "FC";

        var initEl = document.getElementById("fcAvatarInitials");
        if (initEl) initEl.textContent = initials;
        var nameEl = document.getElementById("fcDropdownUserName");
        if (nameEl) nameEl.textContent = "Hello, " + fName;
        var emailEl = document.getElementById("fcDropdownUserEmail");
        if (emailEl) emailEl.textContent = (profile && profile.email) || user.email || "";

        // Load initial cart count
        var cart = await fb.getActiveCartWithItems(user.uid);
        setBadges(cart.count || 0);
      } else {
        if (guestLogin) guestLogin.style.display = "";
        if (guestSignup) guestSignup.style.display = "";
        if (authOrders) authOrders.style.display = "none";
        if (profileWrap) profileWrap.style.display = "none";
        if (mobileLogin) mobileLogin.style.display = "";
        if (mobileSignup) mobileSignup.style.display = "";
        if (mobileOrders) mobileOrders.style.display = "none";
        if (mobileProfile) mobileProfile.style.display = "none";
        if (mobileLogout) mobileLogout.style.display = "none";
        if (bottomCheckout) bottomCheckout.setAttribute("href", base + "login/sign.html");
        if (cartTrigger) cartTrigger.setAttribute("href", base + "login/sign.html");
        setBadges(0);
      }
    });

    // 2. Mini-cart drawer controller
    if (drawer && !drawer.dataset.bound) {
      drawer.dataset.bound = "1";
      var scrim = document.getElementById("fcScrim");
      var body = document.getElementById("fcDrawerBody");
      var foot = document.getElementById("fcDrawerFoot");
      var trigger = document.getElementById("fcCartTrigger");

      function renderDrawer(cart) {
        setBadges(cart.count || 0);
        if (!cart.items || !cart.items.length) {
          body.innerHTML = '<div class="fc-drawer-empty"><i class="fa-solid fa-basket-shopping"></i>Your cart is empty.<br>Add something fresh!</div>';
          foot.style.display = "none";
          return;
        }
        body.innerHTML = cart.items.map(function (it) {
          var img = fb.resolveImagePath(it.image_path, base);
          return '<div class="fc-drawer-item">' +
            '<img src="' + esc(img) + '" alt="' + esc(it.name) + '" onerror="this.src=\'' + esc(base + 'assets/images/logo/logo-freshco.png') + '\'">' +
            '<div class="meta"><div class="name">' + esc(it.name) + '</div>' +
            '<div class="qty">Qty: ' + esc(it.formatted_qty) + (it.unit ? ' &middot; ' + esc(it.unit) : '') + '</div></div>' +
            '<div class="line">Rs. ' + esc(fb.formatMoney(it.line_total)) + '</div></div>';
        }).join("");
        document.getElementById("fcDrawerSubtotal").textContent = "Rs. " + fb.formatMoney(cart.subtotal);
        foot.style.display = "";
      }

      async function refreshDrawer() {
        var user = await fb.waitForAuthUser();
        if (!user) {
          body.innerHTML = '<div class="fc-drawer-empty"><i class="fa-solid fa-user-lock"></i>Please <a href="' + esc(base + 'login/sign.html') + '" style="color:#16a34a;font-weight:700;">sign in</a> to view your cart.</div>';
          foot.style.display = "none";
          return;
        }
        try {
          var cart = await fb.getActiveCartWithItems(user.uid);
          renderDrawer(cart);
        } catch (e) {
          body.innerHTML = '<div class="fc-drawer-empty">Could not load your cart.</div>';
        }
      }

      function openDrawer() {
        drawer.classList.add("open");
        scrim.classList.add("show");
        document.body.style.overflow = "hidden";
        refreshDrawer();
      }
      function closeDrawer() {
        drawer.classList.remove("open");
        scrim.classList.remove("show");
        document.body.style.overflow = "";
      }

      if (trigger) {
        trigger.addEventListener("click", async function (e) {
          if (/shopping-cart\.(html|php)$/i.test(location.pathname)) return;
          var user = await fb.waitForAuthUser();
          if (!user) {
            window.location.href = base + "login/sign.html";
            return;
          }
          e.preventDefault();
          openDrawer();
        });
      }
      var bottom = document.getElementById("fcBottomCart");
      if (bottom) {
        bottom.addEventListener("click", async function (e) {
          if (/shopping-cart\.(html|php)$/i.test(location.pathname)) return;
          var user = await fb.waitForAuthUser();
          if (!user) {
            window.location.href = base + "login/sign.html";
            return;
          }
          e.preventDefault();
          openDrawer();
        });
      }
      var closeBtn = document.getElementById("fcDrawerClose");
      if (closeBtn) closeBtn.addEventListener("click", closeDrawer);
      if (scrim) scrim.addEventListener("click", closeDrawer);
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") { closeDrawer(); closeSidebar(); }
      });

      window.refreshMiniCart = function (shouldOpen) {
        return refreshDrawer().then(function () { if (shouldOpen) openDrawer(); });
      };
    }

    // 3. Live Search Input & Clear Button in Header
    var form = document.getElementById("fcSearch");
    if (form && !form.dataset.bound) {
      form.dataset.bound = "1";
      var input = document.getElementById("fcSearchInput");
      var clear = document.getElementById("fcSearchClear");
      var select = form.querySelector("select");
      var dropdown = document.getElementById("fcNavSearchDropdown");

      var params = new URLSearchParams(location.search);
      if (params.get("q") && input) {
        input.value = params.get("q");
      }
      if (select && params.get("category") && /^\d+$/.test(params.get("category"))) {
        select.value = params.get("category");
      }

      function sync() {
        if (input) form.classList.toggle("has-text", input.value.length > 0);
      }
      if (input) input.addEventListener("input", sync);
      if (clear) {
        clear.addEventListener("click", function () {
          input.value = "";
          sync();
          if (dropdown) dropdown.classList.remove("active");
          input.focus();
          if (/[?&]q=/.test(location.search)) {
            var p = new URLSearchParams(location.search);
            p.delete("q");
            location.search = p.toString();
          }
        });
      }
      sync();

      var debounceTimer = null;
      if (input && dropdown) {
        input.addEventListener("input", function () {
          clearTimeout(debounceTimer);
          var q = input.value.trim().toLowerCase();
          var cat = select ? parseInt(select.value, 10) : 0;
          if (q.length < 2) {
            dropdown.innerHTML = "";
            dropdown.classList.remove("active");
            return;
          }
          debounceTimer = setTimeout(async function () {
            try {
              if (!cachedCatalog) {
                cachedCatalog = await fb.getAllItems(true);
              }
              var matches = cachedCatalog.filter(function (it) {
                if (cat > 0 && Number(it.category_id) !== cat) return false;
                var nameMatch = String(it.name || "").toLowerCase().indexOf(q) !== -1;
                var catMatch = String(it.category_name || "").toLowerCase().indexOf(q) !== -1;
                return nameMatch || catMatch;
              }).slice(0, 8);

              if (!matches.length) {
                dropdown.innerHTML = '<div style="padding:12px;text-align:center;color:#64748b;font-size:13px;">No products found matching "' + esc(input.value.trim()) + '"</div>';
                dropdown.classList.add("active");
                return;
              }
              dropdown.innerHTML = matches.map(function (it) {
                var img = fb.resolveImagePath(it.image_path, base);
                var link = base + "product/product.html?q=" + encodeURIComponent(it.name);
                var inStock = Number(it.stock_quantity) > 0 && it.status === "active";
                var badge = inStock
                  ? '<span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;">In Stock</span>'
                  : '<span style="background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;">Out of Stock</span>';
                return '<a href="' + esc(link) + '" class="fc-nav-search-item">' +
                  '<img src="' + esc(img) + '" alt="" onerror="this.src=\'' + esc(base + 'assets/images/logo/logo-freshco.png') + '\'">' +
                  '<div style="flex:1;min-width:0;"><div style="font-weight:700;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(it.name) + '</div>' +
                  '<div style="font-size:12px;color:#64748b;">' + esc(it.category_name) + ' &middot; ' + esc(it.unit) + '</div></div>' +
                  '<div style="text-align:right;"><div style="font-weight:800;color:#16a34a;font-size:13.5px;">Rs. ' + esc(fb.formatMoney(it.price)) + '</div>' + badge + '</div></a>';
              }).join("");
              dropdown.classList.add("active");
            } catch (e) {
              console.error("Live search error:", e);
            }
          }, 300);
        });
        document.addEventListener("click", function (e) {
          if (!form.contains(e.target)) dropdown.classList.remove("active");
        });
      }
    }
  }
})();
