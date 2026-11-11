/* Freshcomart header behaviour (loaded after nav.php is injected).
   Public helpers kept from the previous version: toggleDropdown, openSidebar,
   closeSidebar, toggleMobileCategories, filterCategory.
   New: mini-cart drawer, live badge refresh, search clear, active category. */

// ---- legacy-compatible helpers -------------------------------------------
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
	var form = new FormData();
	form.append("cid", id);
	var r = new XMLHttpRequest();
	r.onreadystatechange = function () {
		if (r.readyState == 4 && r.status == 200) {
			if (r.responseText == "success") {
				window.location = "product.php?category=" + id;
			} else {
				alert(r.responseText);
			}
		}
	};
	r.open("POST", "categoryProcess.php", true);
	r.send(form);
}

// ---- mini-cart drawer ------------------------------------------------------
(function () {
	var drawer = document.getElementById("fcDrawer");
	if (!drawer || drawer.dataset.bound) return;
	drawer.dataset.bound = "1";

	var scrim = document.getElementById("fcScrim");
	var body = document.getElementById("fcDrawerBody");
	var foot = document.getElementById("fcDrawerFoot");
	var trigger = document.getElementById("fcCartTrigger");
	var endpoint = drawer.dataset.endpoint;
	var base = drawer.dataset.base || "";

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

	function render(data) {
		setBadges(data.count);
		if (!data.items.length) {
			body.innerHTML = '<div class="fc-drawer-empty"><i class="fa-solid fa-basket-shopping"></i>Your cart is empty.<br>Add something fresh!</div>';
			foot.style.display = "none";
			return;
		}
		body.innerHTML = data.items.map(function (it) {
			return '<div class="fc-drawer-item">' +
				'<img src="' + esc(base + it.image) + '" alt="' + esc(it.name) + '" onerror="this.style.visibility=\'hidden\'">' +
				'<div class="meta"><div class="name">' + esc(it.name) + '</div>' +
				'<div class="qty">Qty: ' + esc(it.qty) + (it.unit ? ' &middot; ' + esc(it.unit) : '') + '</div></div>' +
				'<div class="line">Rs. ' + esc(it.line_total) + '</div></div>';
		}).join("");
		document.getElementById("fcDrawerSubtotal").textContent = "Rs. " + data.subtotal;
		foot.style.display = "";
	}

	function refresh() {
		return fetch(endpoint, { credentials: "same-origin" })
			.then(function (r) { return r.json(); })
			.then(render)
			.catch(function () {
				body.innerHTML = '<div class="fc-drawer-empty">Could not load your cart.</div>';
			});
	}

	function open() {
		drawer.classList.add("open");
		scrim.classList.add("show");
		document.body.style.overflow = "hidden";
		refresh();
	}
	function close() {
		drawer.classList.remove("open");
		scrim.classList.remove("show");
		document.body.style.overflow = "";
	}

	if (trigger) {
		trigger.addEventListener("click", function (e) {
			// the cart page itself keeps the normal link behaviour
			if (/shopping-cart\.php$/.test(location.pathname)) return;
			e.preventDefault();
			open();
		});
	}
	var bottom = document.getElementById("fcBottomCart");
	if (bottom) {
		bottom.addEventListener("click", function (e) {
			if (/shopping-cart\.php$/.test(location.pathname) || !trigger) return;
			e.preventDefault();
			open();
		});
	}
	document.getElementById("fcDrawerClose").addEventListener("click", close);
	scrim.addEventListener("click", close);
	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape") { close(); closeSidebar(); }
	});

	// other scripts call this after changing the cart
	window.refreshMiniCart = function (openDrawer) {
		return refresh().then(function () { if (openDrawer) open(); });
	};
})();

// ---- search clear button ---------------------------------------------------
(function () {
	var form = document.getElementById("fcSearch");
	if (!form) return;
	var input = document.getElementById("fcSearchInput");
	var clear = document.getElementById("fcSearchClear");
	var select = form.querySelector("select");

	function sync() { form.classList.toggle("has-text", input.value.length > 0); }
	input.addEventListener("input", sync);
	clear.addEventListener("click", function () {
		input.value = "";
		sync();
		input.focus();
		// when a search is active on the product page, clear it and reload the listing
		if (/[?&]q=/.test(location.search)) {
			var p = new URLSearchParams(location.search);
			p.delete("q");
			location.search = p.toString();
		}
	});
	var params = new URLSearchParams(location.search);
	if (select && params.get("category") && /^\d+$/.test(params.get("category"))) {
		select.value = params.get("category");
	}
	sync();

	// Live AJAX search dropdown (300ms debounce)
	var dropdown = document.getElementById("fcNavSearchDropdown");
	var base = form.dataset.base != null ? form.dataset.base : "../";
	var debounceTimer = null;
	if (dropdown) {
		input.addEventListener("input", function () {
			clearTimeout(debounceTimer);
			var q = input.value.trim();
			var cat = select ? select.value : 0;
			if (q.length < 2) {
				dropdown.innerHTML = "";
				dropdown.classList.remove("active");
				return;
			}
			debounceTimer = setTimeout(function () {
				fetch(base + "liveSearch.php?q=" + encodeURIComponent(q) + "&category=" + encodeURIComponent(cat))
					.then(function (r) { return r.json(); })
					.then(function (data) {
						if (!data.results || !data.results.length) {
							dropdown.innerHTML = '<div style="padding:12px;text-align:center;color:#64748b;font-size:13px;">No products found matching "' + q.replace(/</g, "&lt;") + '"</div>';
							dropdown.classList.add("active");
							return;
						}
						dropdown.innerHTML = data.results.map(function (it) {
							var img = base + (it.image_path || "assets/images/logo/logo-freshco.png");
							var link = base + "product/product.php?q=" + encodeURIComponent(it.name);
							var badge = it.in_stock
								? '<span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;">In Stock</span>'
								: '<span style="background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;">Out of Stock</span>';
							return '<a href="' + link + '" class="fc-nav-search-item">' +
								'<img src="' + img + '" alt="" onerror="this.src=\'' + base + 'assets/images/logo/logo-freshco.png\'">' +
								'<div style="flex:1;min-width:0;"><div style="font-weight:700;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + it.name + '</div>' +
								'<div style="font-size:12px;color:#64748b;">' + it.category_name + ' &middot; ' + it.unit + '</div></div>' +
								'<div style="text-align:right;"><div style="font-weight:800;color:#16a34a;font-size:13.5px;">' + it.price_formatted + '</div>' + badge + '</div></a>';
						}).join("");
						dropdown.classList.add("active");
					})
					.catch(function () {});
			}, 300);
		});
		document.addEventListener("click", function (e) {
			if (!form.contains(e.target)) dropdown.classList.remove("active");
		});
	}
})();

// ---- highlight active quick category ---------------------------------------
(function () {
	var bar = document.getElementById("fcCatbar");
	if (!bar) return;
	var cur = new URLSearchParams(location.search).get("category") || "";
	if (!/product\.php$/.test(location.pathname)) cur = null;
	bar.querySelectorAll("a").forEach(function (a) {
		var on = cur !== null && (a.dataset.cats || "") === (cur === "0" ? "" : cur);
		a.classList.toggle("active", on);
	});
})();

// ---- profile dropdown ------------------------------------------------------
(function () {
	document.querySelectorAll(".profile-dropdown-wrapper").forEach(function (wrapper) {
		var logo = wrapper.querySelector(".mt-logo");
		var menu = wrapper.querySelector(".profile-dropdown-menu");
		function toggle(e) { e.stopPropagation(); menu.classList.toggle("show"); }
		logo.addEventListener("click", toggle);
		logo.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(e); } });
	});
	document.addEventListener("click", function () {
		document.querySelectorAll(".profile-dropdown-menu").forEach(function (m) { m.classList.remove("show"); });
	});
})();
