/**
 * Freshcomart Supermarket Storefront Interactive Controller (Firebase Edition)
 * Live search with 300ms debounce, Firestore cart steppers, and offcanvas drawer integration.
 */

(function () {
  'use strict';

  var cachedItems = null;
  var fbModulePromise = null;

  function getFirebaseService() {
    if (!fbModulePromise) {
      var base = window.FC_BASE || '';
      var url = new URL(base + 'assets/js/firebase-service.js', window.location.href).href;
      fbModulePromise = import(url);
    }
    return fbModulePromise;
  }

  // 1. Live Search with 300ms Debounce (Firestore items collection)
  var searchInput = document.getElementById('fcLiveSearchInput');
  var searchCategory = document.getElementById('fcLiveSearchCategory');
  var searchDropdown = document.getElementById('fcLiveSearchDropdown');
  var debounceTimer = null;

  if (searchInput && searchDropdown) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var query = searchInput.value.trim();
      var cat = searchCategory ? parseInt(searchCategory.value, 10) : 0;

      if (query.length < 2) {
        searchDropdown.innerHTML = '';
        searchDropdown.classList.remove('active');
        return;
      }

      debounceTimer = setTimeout(async function () {
        var base = window.FC_BASE || '';
        try {
          var fb = await getFirebaseService();
          if (!cachedItems) {
            cachedItems = await fb.getAllItems(true);
          }
          var qLower = query.toLowerCase();
          var results = cachedItems.filter(function (it) {
            if (cat > 0 && Number(it.category_id) !== cat) return false;
            return String(it.name || '').toLowerCase().indexOf(qLower) !== -1 ||
                   String(it.category_name || '').toLowerCase().indexOf(qLower) !== -1;
          }).slice(0, 8);

          if (!results.length) {
            searchDropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i>No products found matching "' + query.replace(/</g, '&lt;') + '"</div>';
            searchDropdown.classList.add('active');
            return;
          }

          var html = '';
          results.forEach(function (item) {
            var inStock = Number(item.stock_quantity) > 0 && item.status === 'active';
            var stockBadge = inStock
              ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0 ms-auto small">In Stock</span>'
              : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0 ms-auto small">Out of Stock</span>';

            var imgPath = fb.resolveImagePath(item.image_path, base);
            var linkUrl = base + 'product/product.html?q=' + encodeURIComponent(item.name);

            html += '<a href="' + linkUrl + '" class="fc-search-item">' +
                      '<img src="' + imgPath + '" alt="' + item.name + '" onerror="this.src=\'' + base + 'assets/images/logo/logo-freshco.png\'">' +
                      '<div class="meta">' +
                        '<div class="name">' + item.name + '</div>' +
                        '<div class="cat">' + item.category_name + ' &middot; ' + item.unit + '</div>' +
                      '</div>' +
                      '<div class="text-end ms-2">' +
                        '<div class="price">Rs. ' + fb.formatMoney(item.price) + '</div>' +
                        stockBadge +
                      '</div>' +
                    '</a>';
          });

          searchDropdown.innerHTML = html;
          searchDropdown.classList.add('active');
        } catch (err) {
          console.error('Search error:', err);
        }
      }, 300);
    });

    // Close dropdown on click outside
    document.addEventListener('click', function (e) {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.classList.remove('active');
      }
    });

    searchInput.addEventListener('focus', function () {
      if (searchInput.value.trim().length >= 2 && searchDropdown.children.length > 0) {
        searchDropdown.classList.add('active');
      }
    });
  }

  // 2. Firestore Stepper & Add to Cart Controller
  window.updateCartItem = async function (itemId, delta, btnEl) {
    var base = window.FC_BASE || '';
    var containers = document.querySelectorAll('[data-card-action="' + itemId + '"], #card-action-' + itemId);
    if (!containers.length) return;

    if (btnEl) btnEl.disabled = true;

    try {
      var fb = await getFirebaseService();
      var user = await fb.waitForAuthUser();
      if (!user) {
        if (btnEl) btnEl.disabled = false;
        if (confirm('Please sign in or create an account to start adding items to your Freshcomart cart!')) {
          window.location.href = base + 'login/sign.html';
        }
        return;
      }

      var res = await fb.addOrUpdateCartItem(user.uid, itemId, delta);
      if (btnEl) btnEl.disabled = false;

      if (!res.ok) {
        alert(res.message || 'Unable to update cart.');
        return;
      }

      var newQty = Number(res.newQuantity || 0);
      var updatedHtml = '';
      if (newQty <= 0) {
        updatedHtml = '<button class="fc-btn-add" onclick="updateCartItem(\'' + itemId + '\', 1, this)">' +
                        '<i class="bi bi-cart-plus me-1"></i>Add' +
                      '</button>';
      } else {
        updatedHtml = '<div class="fc-stepper">' +
                        '<button type="button" onclick="updateCartItem(\'' + itemId + '\', -1, this)" aria-label="Decrease">&minus;</button>' +
                        '<span class="qty">' + newQty + '</span>' +
                        '<button type="button" onclick="updateCartItem(\'' + itemId + '\', 1, this)" aria-label="Increase">&plus;</button>' +
                      '</div>';
      }
      containers.forEach(function (c) { c.innerHTML = updatedHtml; });

      // Refresh mini-cart drawer & header badges
      if (typeof window.refreshHomepageCartDrawer === 'function') {
        window.refreshHomepageCartDrawer(false);
      } else if (typeof window.refreshMiniCart === 'function') {
        window.refreshMiniCart(false);
      } else {
        var cart = await fb.getActiveCartWithItems(user.uid);
        document.querySelectorAll('#fcCartBadge, .fc-cart-badge, .cart-count').forEach(function (badge) {
          badge.textContent = cart.count || 0;
        });
      }
    } catch (err) {
      if (btnEl) btnEl.disabled = false;
      console.error('Cart action failed:', err);
    }
  };

  // 3. Tab Filter for Daily Grocery & Pantry Staples
  window.filterHomeStaples = function (catId, btnEl) {
    document.querySelectorAll('.fc-tab-btn').forEach(function (b) { b.classList.remove('active'); });
    if (btnEl) btnEl.classList.add('active');

    var cards = document.querySelectorAll('#homeStaplesGrid .fc-card');
    cards.forEach(function (c) {
      var cardCat = String(c.dataset.category || '');
      if (catId === 'all' || cardCat === String(catId) || (String(catId).indexOf(',') !== -1 && String(catId).split(',').indexOf(cardCat) !== -1)) {
        c.style.display = '';
      } else {
        c.style.display = 'none';
      }
    });
  };

})();
