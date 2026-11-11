<?php
  // Header fragment. Works two ways:
  //  1. fetched by product pages (fetch("nav.php")) – default base "../"
  //  2. included by root pages (e.g. proceed.php) after setting $FC_BASE = '' and $FC_EMBED = true
  if (!isset($FC_BASE)) { $FC_BASE = '../'; }
  $FC_EMBED = isset($FC_EMBED) && $FC_EMBED;
  $P = $FC_BASE . 'product/';

  require_once __DIR__ . '/../connection.php';
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  $store = require __DIR__ . '/../storeConfig.php';

  $cart_count = 0;
  $can_access_cart = false;
  $cart_disabled_reason = 'Login required';
  $is_logged_in = isset($_SESSION['user_id']);

  // Set status dot color based on session
  $status_dot_color = isset($_SESSION['user_id']) ? 'GreenYellow' : 'red';

  // Set initials for logo
  $logo_initials = 'FC';
  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_rs = Database::search("SELECT first_name, last_name FROM users WHERE id='$user_id'");
    if ($user_rs && $user_row = $user_rs->fetch_assoc()) {
      $first = isset($user_row['first_name']) ? strtoupper(substr($user_row['first_name'], 0, 1)) : '';
      $last = isset($user_row['last_name']) ? strtoupper(substr($user_row['last_name'], 0, 1)) : '';
      $logo_initials = $first . $last;
      if ($logo_initials === '') $logo_initials = 'FC';
    }
  }

  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");

    if ($cart_rs && $cart_rs->num_rows > 0) {
      $cart_data = $cart_rs->fetch_assoc();
      $cart_id = $cart_data['id'];
      $items_rs = Database::search("SELECT COUNT(id) AS total_items FROM cart_items WHERE cart_id='$cart_id'");
      $items_data = $items_rs->fetch_assoc();
      $cart_count = (int)$items_data['total_items'];
    } else {
      $cart_count = 0;
    }

    // Always allow logged-in users to access the cart page
    $can_access_cart = true;
    $cart_disabled_reason = '';
  }

  // Category catalogue (ids match items.category_id)
  $all_categories = [
    1 => ['Vegetables', 'fa-carrot'], 2 => ['Fruits', 'fa-apple-whole'], 3 => ['Snacks', 'fa-cookie-bite'],
    4 => ['Biscuits', 'fa-cookie'], 5 => ['Coffee', 'fa-mug-hot'], 6 => ['Eggs', 'fa-egg'],
    7 => ['Water', 'fa-bottle-water'], 8 => ['Tea', 'fa-mug-saucer'], 9 => ['Cheese', 'fa-cheese'],
    10 => ['Yoghurts & Curd', 'fa-ice-cream'], 11 => ['Desserts', 'fa-cake-candles'], 12 => ['Beverages', 'fa-glass-water'],
    13 => ['Bakery', 'fa-bread-slice'], 14 => ['Meat & Seafood', 'fa-drumstick-bite'], 15 => ['Pantry Staples', 'fa-bowl-rice'],
    16 => ['Dairy Staples', 'fa-cow'], 17 => ['Frozen Foods', 'fa-snowflake'], 18 => ['Household & Cleaning', 'fa-soap'],
    19 => ['Personal Care & Baby', 'fa-baby-carriage'],
  ];
  // Quick-access bar: label, icon, comma separated category ids
  $quick_bar = [
    ['Fruits & Veg', 'fa-carrot', '1,2'],
    ['Dairy', 'fa-cow', '16,9,10,6'],
    ['Meat', 'fa-drumstick-bite', '14'],
    ['Beverages', 'fa-glass-water', '12,5,8,7'],
    ['Pantry', 'fa-bowl-rice', '15,3,4'],
    ['Household', 'fa-soap', '18'],
  ];
  $q_value = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
<link rel="stylesheet" href="<?php echo $FC_BASE; ?>assets/css/custom-ui.css" />
<style>
  /* status dot + profile menu helpers (kept from previous header) */
  .fc-header .mt-status-dot { width: 9px; height: 9px; border-radius: 50%; position: absolute; }
  .profile-dropdown-wrapper { position: relative; display: inline-block; }
  .profile-dropdown-menu { display: none; position: absolute; right: 0; background: #fff; min-width: 230px; box-shadow: 0 10px 30px rgba(15,23,42,.15); border-radius: 12px; overflow: hidden; z-index: 1100; border: 1px solid #e2e8f0; font-family: Inter, system-ui, sans-serif; }
  .profile-dropdown-menu.show { display: block; animation: fcFade .2s ease; }
  .profile-dropdown-header { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
  .profile-dropdown-header .user-name { font-weight: 700; color: #15803d; font-size: 14px; }
  .profile-dropdown-header .user-email { color: #64748b; font-size: 12px; word-break: break-all; }
  .profile-dropdown-menu a { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 18px; text-decoration: none; color: #334155; font-size: 14px; font-weight: 600; }
  .profile-dropdown-menu a:hover { background: #f0fdf4; color: #15803d; }
  .profile-dropdown-menu i { width: 18px; text-align: center; color: #64748b; }
  /* Live search dropdown inside fc-search */
  .fc-search { position: relative; }
  .fc-nav-search-dropdown {
    display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 12px 30px rgba(15,23,42,.15); max-height: 360px; overflow-y: auto; z-index: 1200;
  }
  .fc-nav-search-dropdown.active { display: block; }
  .fc-nav-search-item {
    display: flex; align-items: center; gap: 12px; padding: 10px 14px;
    text-decoration: none; color: #0f172a; border-bottom: 1px solid #f1f5f9;
  }
  .fc-nav-search-item:hover { background: #f0fdf4; color: #15803d; }
  .fc-nav-search-item img { width: 42px; height: 42px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
</style>

<!-- ============ Sticky header ============ -->
<header class="fc-header" role="banner">
  <button class="fc-menu-toggle" type="button" onclick="openSidebar()" aria-label="Open menu"><i class="fas fa-bars"></i></button>

  <a class="fc-logo" href="<?php echo $FC_BASE; ?>index.php" aria-label="Freshcomart home">
    <img src="<?php echo $FC_BASE; ?>assets/images/logo/logo-freshco.png" alt="Freshcomart" />
  </a>

  <button class="fc-store-select" type="button" title="Pickup &amp; delivery store" aria-label="Selected store: <?php echo htmlspecialchars($store['label']); ?>">
    <i class="fa-solid fa-location-dot"></i>
    <span><small>Store</small><strong>Store: <?php echo htmlspecialchars($store['label']); ?></strong></span>
  </button>

  <form class="fc-search" id="fcSearch" action="<?php echo $P; ?>product.php" method="get" role="search" data-base="<?php echo $FC_BASE; ?>">
    <select name="category" aria-label="Search category">
      <option value="0">All Categories</option>
      <?php foreach ($all_categories as $cid => $c): ?>
        <option value="<?php echo $cid; ?>"><?php echo htmlspecialchars($c[0]); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="q" id="fcSearchInput" value="<?php echo $q_value; ?>" placeholder="Search for fruits, milk, rice…" autocomplete="off" aria-label="Search products" />
    <button type="button" class="fc-search-clear" id="fcSearchClear" aria-label="Clear search"><i class="fas fa-xmark"></i></button>
    <button type="submit" class="fc-search-submit" aria-label="Search"><i class="fas fa-magnifying-glass"></i><span class="d-none d-xl-inline">Search</span></button>
    <div id="fcNavSearchDropdown" class="fc-nav-search-dropdown"></div>
  </form>

  <div class="fc-actions">
    <?php if ($is_logged_in): ?>
      <a href="<?php echo $P; ?>my-orders.php" class="fc-pill-link hide-sm"><i class="fas fa-bag-shopping"></i>My Orders</a>
      <div class="profile-dropdown-wrapper">
        <div class="fc-avatar mt-logo" role="button" tabindex="0" aria-label="Account menu">
          <?php echo htmlspecialchars($logo_initials); ?>
          <div class="mt-status-dot" id="mtStatusDot" style="background: <?php echo $status_dot_color; ?>;"></div>
        </div>
        <div class="profile-dropdown-menu">
          <div class="profile-dropdown-header">
            <div class="user-name">Hello, <?php echo isset($_SESSION['users']['first_name']) ? htmlspecialchars($_SESSION['users']['first_name']) : 'User'; ?></div>
            <div class="user-email"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?></div>
          </div>
          <a href="<?php echo $FC_BASE; ?>my-account.php"><i class="fas fa-user-circle"></i> My Account &amp; Address</a>
          <a href="<?php echo $FC_BASE; ?>profile.php"><i class="fas fa-user-cog"></i> Edit Profile</a>
          <a href="<?php echo $P; ?>my-orders.php"><i class="fas fa-history"></i> Order History</a>
          <a href="<?php echo $FC_BASE; ?>contact-us.php"><i class="fas fa-headset"></i> Help &amp; Contact</a>
          <a href="<?php echo $FC_BASE; ?>login/logout.php" style="border-top: 1px solid #e2e8f0;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?php echo $FC_BASE; ?>login/sign.php" class="fc-pill-link login-btn">Login</a>
      <a href="<?php echo $FC_BASE; ?>login/register.php" class="fc-pill-link primary signup-btn hide-sm">Sign Up</a>
    <?php endif; ?>

    <?php if ($can_access_cart): ?>
      <a href="<?php echo $P; ?>shopping-cart.php" class="fc-cart-trigger cart-btn" id="fcCartTrigger" aria-label="Open cart" aria-haspopup="dialog">
        <i class="fa-solid fa-cart-shopping"></i><span class="label">Cart</span>
        <span class="fc-cart-badge" id="fcCartBadge"><?php echo $cart_count; ?></span>
      </a>
    <?php else: ?>
      <a href="<?php echo $FC_BASE; ?>login/sign.php" class="fc-cart-trigger cart-btn" title="<?php echo htmlspecialchars($cart_disabled_reason); ?>">
        <i class="fa-solid fa-cart-shopping"></i><span class="label">Cart</span>
      </a>
    <?php endif; ?>
  </div>
</header>

<!-- ============ Secondary category bar ============ -->
<nav class="fc-catbar" aria-label="Shop by category">
  <div class="fc-catbar-inner" id="fcCatbar">
    <a href="<?php echo $P; ?>product.php" data-cats="" class="active"><i class="fa-solid fa-store"></i>All</a>
    <?php foreach ($quick_bar as $q): ?>
      <a href="<?php echo $P; ?>product.php?category=<?php echo $q[2]; ?>" data-cats="<?php echo $q[2]; ?>"><i class="fa-solid <?php echo $q[1]; ?>"></i><?php echo htmlspecialchars($q[0]); ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<!-- ============ Mini-cart drawer ============ -->
<div class="fc-scrim" id="fcScrim"></div>
<aside class="fc-drawer" id="fcDrawer" role="dialog" aria-modal="true" aria-label="Shopping cart"
       data-endpoint="<?php echo $P; ?>miniCart.php" data-checkout="<?php echo $FC_BASE; ?>proceed.php" data-cart="<?php echo $P; ?>shopping-cart.php" data-base="<?php echo $FC_BASE; ?>">
  <div class="fc-drawer-head">
    <h3><i class="fa-solid fa-basket-shopping me-2" style="color:#16a34a"></i>Your Cart <span id="fcDrawerCount" style="color:#64748b;font-weight:500"></span></h3>
    <button class="fc-drawer-close" type="button" id="fcDrawerClose" aria-label="Close cart"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="fc-drawer-body" id="fcDrawerBody"><div class="fc-drawer-empty"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div></div>
  <div class="fc-drawer-foot" id="fcDrawerFoot" style="display:none">
    <div class="fc-drawer-sub"><span>Subtotal</span><span id="fcDrawerSubtotal">Rs. 0.00</span></div>
    <div class="fc-drawer-note">Delivery fee &amp; pickup options are chosen at checkout.</div>
    <a class="fc-btn fc-btn-primary" id="fcDrawerCheckout" href="<?php echo $FC_BASE; ?>proceed.php"><i class="fa-solid fa-lock"></i> Checkout</a>
    <a class="fc-btn fc-btn-outline" href="<?php echo $P; ?>shopping-cart.php">View full cart</a>
  </div>
</aside>

<!-- ============ Mobile sidebar (burger menu) ============ -->
<div class="fc-scrim" id="overlay" onclick="closeSidebar()"></div>
<aside class="fc-mobile-sidebar" id="mobileSidebar" aria-label="Menu">
  <div class="head">
    <h3>Menu</h3>
    <button class="close-sidebar" type="button" onclick="closeSidebar()" aria-label="Close menu" style="border:0;background:none"><i class="fas fa-times"></i></button>
  </div>
  <nav>
    <a href="<?php echo $FC_BASE; ?>index.php"><i class="fas fa-house"></i> Home</a>
    <?php if (!$is_logged_in): ?>
      <a href="<?php echo $FC_BASE; ?>login/sign.php"><i class="fas fa-user"></i> Login</a>
      <a href="<?php echo $FC_BASE; ?>login/register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
    <?php else: ?>
      <a href="<?php echo $P; ?>my-orders.php"><i class="fas fa-bag-shopping"></i> My Orders</a>
      <a href="<?php echo $FC_BASE; ?>profile.php"><i class="fas fa-user-cog"></i> Edit Profile</a>
    <?php endif; ?>
    <a href="<?php echo $P; ?>shopping-cart.php"><i class="fas fa-cart-shopping"></i> Shopping Cart <span class="fc-cart-badge ms-auto cart-count"><?php echo $cart_count; ?></span></a>
    <div class="section-title">Categories</div>
    <div class="mobile-category-list show" id="mobileCategoryList">
      <a href="<?php echo $P; ?>product.php" data-category="All" data-id="0"><i class="fa-solid fa-list"></i> All Items</a>
      <?php foreach ($all_categories as $cid => $c): ?>
        <a href="<?php echo $P; ?>product.php?category=<?php echo $cid; ?>" data-category="<?php echo htmlspecialchars($c[0]); ?>" data-id="<?php echo $cid; ?>"><i class="fa-solid <?php echo $c[1]; ?>" style="color:#16a34a"></i> <?php echo htmlspecialchars($c[0]); ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($is_logged_in): ?>
      <a href="<?php echo $FC_BASE; ?>login/logout.php" style="margin-top:8px;border-top:1px solid #e2e8f0"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php endif; ?>
  </nav>
</aside>

<!-- ============ Mobile bottom action bar ============ -->
<nav class="fc-bottombar" aria-label="Quick actions">
  <a href="<?php echo $FC_BASE; ?>index.php"><i class="fas fa-house"></i>Home</a>
  <a href="<?php echo $P; ?>product.php"><i class="fas fa-store"></i>Shop</a>
  <button type="button" onclick="openSidebar()"><i class="fas fa-table-cells-large"></i>Categories</button>
  <a href="<?php echo $P; ?>shopping-cart.php" id="fcBottomCart"><i class="fas fa-cart-shopping"></i>Cart<span class="fc-cart-badge fc-cart-badge-mirror"><?php echo $cart_count; ?></span></a>
  <a href="<?php echo $is_logged_in ? $FC_BASE . 'proceed.php' : $FC_BASE . 'login/sign.php'; ?>" style="color:#15803d"><i class="fas fa-circle-check"></i>Checkout</a>
</nav>

<?php if ($FC_EMBED): ?>
  <script src="<?php echo $P; ?>navBar.js"></script>
<?php endif; ?>
