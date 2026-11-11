/**
 * Freshcomart Supermarket Storefront Interactive Controller
 * Live search with 300ms debounce, AJAX cart steppers, and offcanvas drawer integration.
 */

(function () {
  'use strict';

  // 1. AJAX Live Search with 300ms Debounce
  var searchInput = document.getElementById('fcLiveSearchInput');
  var searchCategory = document.getElementById('fcLiveSearchCategory');
  var searchDropdown = document.getElementById('fcLiveSearchDropdown');
  var debounceTimer = null;

  if (searchInput && searchDropdown) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var query = searchInput.value.trim();
      var cat = searchCategory ? searchCategory.value : 0;

      if (query.length < 2) {
        searchDropdown.innerHTML = '';
        searchDropdown.classList.remove('active');
        return;
      }

      debounceTimer = setTimeout(function () {
        var base = window.FC_BASE || '';
        fetch(base + 'liveSearch.php?q=' + encodeURIComponent(query) + '&category=' + encodeURIComponent(cat))
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (!data.results || data.results.length === 0) {
              searchDropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i>No products found matching "' + query + '"</div>';
              searchDropdown.classList.add('active');
              return;
            }

            var html = '';
            data.results.forEach(function (item) {
              var stockBadge = item.in_stock 
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0 ms-auto small">In Stock</span>'
                : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0 ms-auto small">Out of Stock</span>';

              var imgPath = base + (item.image_path ? item.image_path : 'assets/images/product/default.png');
              var linkUrl = base + 'product/product.php?q=' + encodeURIComponent(item.name);

              html += '<a href="' + linkUrl + '" class="fc-search-item">' +
                        '<img src="' + imgPath + '" alt="' + item.name + '" onerror="this.src=\'' + base + 'assets/images/logo/logo-freshco.png\'">' +
                        '<div class="meta">' +
                          '<div class="name">' + item.name + '</div>' +
                          '<div class="cat">' + item.category_name + ' &middot; ' + item.unit + '</div>' +
                        '</div>' +
                        '<div class="text-end ms-2">' +
                          '<div class="price">' + item.price_formatted + '</div>' +
                          stockBadge +
                        '</div>' +
                      '</a>';
            });

            searchDropdown.innerHTML = html;
            searchDropdown.classList.add('active');
          })
          .catch(function (err) {
            console.error('Search error:', err);
          });
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

  // 2. AJAX Stepper & Add to Cart Controller
  window.updateCartItem = function (itemId, delta, btnEl) {
    var base = window.FC_BASE || '';
    var container = document.getElementById('card-action-' + itemId);
    if (!container) return;

    var qtyEl = container.querySelector('.qty');
    var currentQty = qtyEl ? parseFloat(qtyEl.textContent) : 0;
    var newQty = currentQty + delta;

    var form = new FormData();
    form.append('item_id', itemId);
    form.append('quantity', delta);

    // Provide visual feedback
    if (btnEl) btnEl.disabled = true;

    fetch(base + 'product/productProcess.php', {
      method: 'POST',
      body: form,
      credentials: 'same-origin'
    })
      .then(function (res) { return res.text(); })
      .then(function (respText) {
        if (btnEl) btnEl.disabled = false;
        var text = respText.trim();

        if (text === 'User not logged in') {
          if (confirm('Please sign in or create an account to start adding items to your Freshcomart cart!')) {
            window.location.href = base + 'login/sign.php';
          }
          return;
        }

        if (text.indexOf('Sorry') !== -1 || text.indexOf('Error') !== -1) {
          alert(text);
          return;
        }

        // Successfully updated
        if (newQty <= 0) {
          // Revert back to Add button
          container.innerHTML = '<button class="fc-btn-add" onclick="updateCartItem(' + itemId + ', 1, this)">' +
                                  '<i class="bi bi-cart-plus me-1"></i>Add' +
                                '</button>';
        } else {
          // Render or update stepper
          container.innerHTML = '<div class="fc-stepper">' +
                                  '<button type="button" onclick="updateCartItem(' + itemId + ', -1, this)" aria-label="Decrease">&minus;</button>' +
                                  '<span class="qty">' + newQty + '</span>' +
                                  '<button type="button" onclick="updateCartItem(' + itemId + ', 1, this)" aria-label="Increase">&plus;</button>' +
                                '</div>';
        }

        // Trigger header badge and drawer refresh if available
        if (window.refreshMiniCart) {
          window.refreshMiniCart(false);
        } else {
          updateHeaderBadgeLocally(delta);
        }
      })
      .catch(function (err) {
        if (btnEl) btnEl.disabled = false;
        console.error('Cart action failed:', err);
      });
  };

  function updateHeaderBadgeLocally(delta) {
    document.querySelectorAll('#fcCartBadge, .fc-cart-badge, .cart-count').forEach(function (badge) {
      var current = parseInt(badge.textContent, 10) || 0;
      var updated = Math.max(0, current + (delta > 0 ? 1 : (delta < 0 ? -1 : 0)));
      badge.textContent = updated;
    });
  }

  // 3. Tab Filter for Daily Grocery & Pantry Staples
  window.filterHomeStaples = function (catId, btnEl) {
    document.querySelectorAll('.fc-tab-btn').forEach(function (b) { b.classList.remove('active'); });
    if (btnEl) btnEl.classList.add('active');

    var cards = document.querySelectorAll('#homeStaplesGrid .fc-card');
    cards.forEach(function (c) {
      var cardCat = c.dataset.category;
      if (catId === 'all' || cardCat === catId || (catId.indexOf(',') !== -1 && catId.split(',').indexOf(cardCat) !== -1)) {
        c.style.display = '';
      } else {
        c.style.display = 'none';
      }
    });
  };

})();

