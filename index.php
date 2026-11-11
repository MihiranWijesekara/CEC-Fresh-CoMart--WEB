<?php
require_once __DIR__ . '/connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$store = require __DIR__ . '/storeConfig.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;
$user_name = 'User';
$logo_initials = 'FC';
$status_dot_color = $is_logged_in ? '#22c55e' : '#ef4444';

// User profile & avatar initials
if ($is_logged_in) {
    $u_res = Database::search("SELECT first_name, last_name, email FROM users WHERE id = '$user_id' LIMIT 1");
    if ($u_res && $u = $u_res->fetch_assoc()) {
        $user_name = !empty($u['first_name']) ? htmlspecialchars($u['first_name']) : 'User';
        $first = strtoupper(substr($u['first_name'] ?? 'F', 0, 1));
        $last = strtoupper(substr($u['last_name'] ?? 'C', 0, 1));
        $logo_initials = $first . $last;
    }
}

// Authoritative user cart items & total
$cart_count = 0;
$cart_subtotal = 0.00;
$inCart = [];

if ($is_logged_in) {
    $c_res = Database::search("SELECT id FROM carts WHERE user_id = '$user_id' AND status = 'Active' ORDER BY id DESC LIMIT 1");
    if ($c_res && $c_row = $c_res->fetch_assoc()) {
        $cart_id = (int)$c_row['id'];
        $ci_res = Database::search("
            SELECT ci.item_id, ci.quantity, ci.price 
            FROM cart_items ci 
            WHERE ci.cart_id = '$cart_id'
        ");
        if ($ci_res) {
            while ($ci = $ci_res->fetch_assoc()) {
                $qty = (float)$ci['quantity'];
                $pr = (float)$ci['price'];
                $inCart[(int)$ci['item_id']] = $qty;
                $cart_subtotal += ($qty * $pr);
                $cart_count++;
            }
        }
    }
}

// 1. Fetch categories with item counts
$categories = [];
$cat_res = Database::search("
    SELECT c.id, c.name, c.image_path, COUNT(i.id) AS item_count
    FROM categories c
    LEFT JOIN items i ON i.category_id = c.id AND i.status = 'active'
    GROUP BY c.id, c.name, c.image_path
    ORDER BY c.id ASC
");
if ($cat_res) {
    while ($cr = $cat_res->fetch_assoc()) {
        $categories[] = $cr;
    }
}

// 2. Fetch Featured Essentials / Deals of the Day (high stock / popular items)
$featured_res = Database::search("
    SELECT i.*, COALESCE(c.name, 'Grocery') AS category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.status = 'active'
    ORDER BY i.stock_quantity DESC, i.id ASC
    LIMIT 8
");

// 3. Fetch Daily Grocery & Pantry Staples (diverse mix)
$staples_res = Database::search("
    SELECT i.*, COALESCE(c.name, 'Grocery') AS category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE i.status = 'active' AND i.category_id IN (1, 2, 3, 14, 15, 16)
    ORDER BY i.id ASC
    LIMIT 12
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Freshcomart – Online Supermarket &amp; Fresh Grocery Delivery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Shop fresh vegetables, fruits, dairy, meats, and pantry essentials online at Freshcomart. Cash on delivery and free store pickup in Colombo.">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/logo-freshco.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Freshcomart Design System -->
    <link rel="stylesheet" href="assets/css/supermarket-ui.css">

    <style>
      .fc-avatar {
          width: 40px;
          height: 40px;
          background: #16a34a;
          color: #ffffff;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 700;
          font-size: 14px;
          position: relative;
          cursor: pointer;
      }
      .fc-status-dot {
          width: 10px;
          height: 10px;
          border-radius: 50%;
          border: 2px solid #ffffff;
          position: absolute;
          bottom: 0;
          right: 0;
      }
      .dropdown-menu.profile-menu {
          min-width: 220px;
          border-radius: 12px;
          box-shadow: 0 10px 25px rgba(0,0,0,0.1);
          border: 1px solid #e2e8f0;
          padding: 8px 0;
      }
      .dropdown-menu.profile-menu .dropdown-item {
          padding: 8px 18px;
          font-weight: 500;
          font-size: 14px;
          color: #334155;
      }
      .dropdown-menu.profile-menu .dropdown-item:hover {
          background: #f0fdf4;
          color: #16a34a;
      }
    </style>
</head>
<body class="fc-body">

    <!-- 1. Top Announcement Bar -->
    <div class="fc-topbar">
        <span>
            🚚 <strong>Cash on Delivery</strong> (Flat Rs. 450 Delivery) &nbsp;|&nbsp; 
            🏬 <strong>Free In-Store Pickup</strong> Available at Main Branch (Rajagiriya)!
        </span>
    </div>

    <!-- 2. Sticky Main Header -->
    <header class="fc-main-header">
        <div class="container-fluid px-lg-5 py-2">
            <div class="row align-items-center">
                <!-- Brand Logo & Store Location Chip -->
                <div class="col-auto d-flex align-items-center gap-3">
                    <a href="index.php" class="d-flex align-items-center text-decoration-none">
                        <img src="assets/images/logo/logo-freshco.png" alt="Freshcomart" height="46">
                    </a>
                    <div class="d-none d-md-flex fc-store-chip" title="<?php echo htmlspecialchars($store['address']); ?>">
                        <i class="bi bi-geo-alt-fill text-success"></i>
                        <span>Store: <strong><?php echo htmlspecialchars($store['label']); ?></strong> (8:00 AM - 9:00 PM)</span>
                    </div>
                </div>

                <!-- Desktop / Tablet Live Search Bar -->
                <div class="col my-2 my-lg-0">
                    <div class="fc-search-wrapper mx-auto" style="max-width: 640px;">
                        <form action="product/product.php" method="GET" class="fc-search-group">
                            <select name="category" id="fcLiveSearchCategory" class="fc-search-category d-none d-sm-block" aria-label="Category filter">
                                <option value="0">All Items</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="q" id="fcLiveSearchInput" class="fc-search-input" placeholder="Search fresh vegetables, fruits, dairy, rice, beverages..." autocomplete="off">
                            <button type="submit" class="fc-search-btn" aria-label="Submit search">
                                <i class="bi bi-search"></i>
                                <span class="d-none d-md-inline">Search</span>
                            </button>
                        </form>
                        <!-- Live Search AJAX Results Dropdown -->
                        <div id="fcLiveSearchDropdown" class="fc-search-dropdown"></div>
                    </div>
                </div>

                <!-- User Controls & Cart Trigger -->
                <div class="col-auto d-flex align-items-center gap-3">
                    <?php if ($is_logged_in): ?>
                        <div class="dropdown">
                            <div class="fc-avatar" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                                <?php echo htmlspecialchars($logo_initials); ?>
                                <span class="fc-status-dot" style="background-color: <?php echo $status_dot_color; ?>;"></span>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end profile-menu">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-success">Hello, <?php echo $user_name; ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></small>
                                </li>
                                <li><a class="dropdown-item" href="my-account.php"><i class="bi bi-person-gear me-2"></i>My Account &amp; Address</a></li>
                                <li><a class="dropdown-item" href="product/my-orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="product/shopping-cart.php"><i class="bi bi-cart3 me-2"></i>View Cart</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-2">
                            <a href="login/sign.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold">Sign In</a>
                            <a href="login/register.php" class="btn btn-success btn-sm rounded-pill px-3 fw-bold d-none d-sm-inline-block">Register</a>
                        </div>
                    <?php endif; ?>

                    <!-- Mini Cart Drawer Trigger -->
                    <button type="button" class="btn btn-success rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2 shadow-sm" onclick="openMiniCartDrawer()">
                        <i class="bi bi-basket2-fill fs-6"></i>
                        <span class="d-none d-sm-inline fw-bold">Cart</span>
                        <span class="badge bg-white text-success rounded-pill px-2 py-1 fw-extrabold" id="fcCartBadge"><?php echo $cart_count; ?></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Secondary Quick-Access Category Navigation Bar -->
        <nav class="fc-quick-catbar" aria-label="Category chips">
            <div class="container-fluid px-lg-5 d-flex gap-2 align-items-center">
                <a href="product/product.php" class="fc-cat-chip active">
                    <i class="bi bi-grid-fill"></i>All Products
                </a>
                <?php 
                $quickIcons = [
                    1 => 'bi-carrot', 2 => 'bi-apple', 3 => 'bi-cookie', 4 => 'bi-cup-straw',
                    5 => 'bi-cup-hot', 6 => 'bi-egg', 14 => 'bi-fire', 15 => 'bi-box-seam', 16 => 'bi-droplet'
                ];
                foreach ($categories as $cat): 
                    $icon = $quickIcons[(int)$cat['id']] ?? 'bi-tag';
                ?>
                    <a href="product/product.php?category=<?php echo $cat['id']; ?>" class="fc-cat-chip">
                        <i class="bi <?php echo $icon; ?>"></i><?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </header>

    <main class="container-fluid px-lg-5 py-4">
        <!-- Section A: Hero Banner -->
        <section class="fc-hero">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="fc-hero-tag">
                        <i class="bi bi-stars"></i> 100% Farm Fresh &amp; Daily Essentials
                    </span>
                    <h1>Fresh Groceries Delivered Fast To Your Doorstep</h1>
                    <p>Order fresh farm vegetables, tropical fruits, premium meats, pantry staples, and dairy. Choose doorstep Cash on Delivery or Free In-Store Pickup in Colombo.</p>
                    <div class="fc-hero-cta">
                        <a href="product/product.php" class="fc-btn-hero-primary">
                            <i class="bi bi-truck"></i> Shop for Delivery
                        </a>
                        <a href="product/product.php?pickup=1" class="fc-btn-hero-secondary">
                            <i class="bi bi-shop"></i> Order for Store Pickup (Free)
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section B: Value Proposition & Trust Features -->
        <section class="fc-trust-strip">
            <div class="fc-trust-card">
                <div class="fc-trust-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <div class="fc-trust-info">
                    <h4>Fast Home Delivery</h4>
                    <p>Flat Rs. 450 island-wide &amp; local Colombo dispatch</p>
                </div>
            </div>
            <div class="fc-trust-card">
                <div class="fc-trust-icon" style="background:#fef3c7; color:#b45309;"><i class="bi bi-shop-window"></i></div>
                <div class="fc-trust-info">
                    <h4>Free Click &amp; Collect</h4>
                    <p>Pick up at Rajagiriya store in as little as 2 hours</p>
                </div>
            </div>
            <div class="fc-trust-card">
                <div class="fc-trust-icon"><i class="bi bi-shield-check"></i></div>
                <div class="fc-trust-info">
                    <h4>100% Quality Guaranteed</h4>
                    <p>Daily inspected produce sourced directly from farmers</p>
                </div>
            </div>
            <div class="fc-trust-card">
                <div class="fc-trust-icon" style="background:#e0f2fe; color:#0369a1;"><i class="bi bi-cash-coin"></i></div>
                <div class="fc-trust-info">
                    <h4>Cash on Delivery</h4>
                    <p>Pay cash when delivered or directly at counter</p>
                </div>
            </div>
        </section>

        <!-- Section C: Shop by Category Grid -->
        <section class="mb-5">
            <div class="fc-section-head">
                <h2><i class="bi bi-grid-3x3-gap-fill text-success me-2"></i>Shop by Category</h2>
                <a href="product/product.php">Explore All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="fc-cat-grid">
                <?php foreach ($categories as $cat): 
                    $catImg = !empty($cat['image_path']) ? $cat['image_path'] : 'assets/images/logo/logo-freshco.png';
                ?>
                    <a href="product/product.php?category=<?php echo $cat['id']; ?>" class="fc-cat-card">
                        <div class="fc-cat-img">
                            <img src="<?php echo htmlspecialchars($catImg); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" onerror="this.src='assets/images/logo/logo-freshco.png'">
                        </div>
                        <div class="title"><?php echo htmlspecialchars($cat['name']); ?></div>
                        <div class="count"><?php echo (int)$cat['item_count']; ?> items</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section D: Deals of the Day / Featured Essentials -->
        <section class="mb-5">
            <div class="fc-section-head">
                <div>
                    <h2><i class="bi bi-fire text-warning me-2"></i>Deals of the Day &amp; Featured Essentials</h2>
                    <small class="text-muted">Fresh picks updated daily for maximum nutrition &amp; savings</small>
                </div>
                <a href="product/product.php">View Catalog <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="fc-product-grid">
                <?php 
                if ($featured_res && $featured_res->num_rows > 0) {
                    while ($item = $featured_res->fetch_assoc()) {
                        $itemId = (int)$item['id'];
                        $stock = (float)$item['stock_quantity'];
                        $inStock = ($item['status'] === 'active' && $stock > 0);
                        $itemCartQty = $inCart[$itemId] ?? 0;
                        $imgPath = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/logo/logo-freshco.png';
                        ?>
                        <div class="fc-card <?php echo $inStock ? '' : 'out-of-stock'; ?>">
                            <div class="fc-card-thumb">
                                <span class="fc-card-badge-stock <?php echo $inStock ? 'in-stock' : 'out-stock'; ?>">
                                    <?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?>
                                </span>
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy" onerror="this.src='assets/images/logo/logo-freshco.png'">
                            </div>
                            <div class="fc-card-body">
                                <span class="fc-card-unit"><?php echo htmlspecialchars($item['unit'] ?: '1 unit'); ?></span>
                                <h3 class="fc-card-title" title="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <div class="fc-card-price-row">
                                    <span class="fc-card-price">Rs. <?php echo number_format((float)$item['price'], 2); ?></span>
                                </div>
                                <div class="fc-card-action" id="card-action-<?php echo $itemId; ?>" data-card-action="<?php echo $itemId; ?>">
                                    <?php if (!$inStock): ?>
                                        <button class="fc-btn-add" disabled>Out of Stock</button>
                                    <?php elseif ($itemCartQty > 0): ?>
                                        <div class="fc-stepper">
                                            <button type="button" onclick="updateCartItem(<?php echo $itemId; ?>, -1, this)" aria-label="Decrease">&minus;</button>
                                            <span class="qty"><?php echo $itemCartQty; ?></span>
                                            <button type="button" onclick="updateCartItem(<?php echo $itemId; ?>, 1, this)" aria-label="Increase">&plus;</button>
                                        </div>
                                    <?php else: ?>
                                        <button class="fc-btn-add" onclick="updateCartItem(<?php echo $itemId; ?>, 1, this)">
                                            <i class="bi bi-cart-plus me-1"></i>Add
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                } ?>
            </div>
        </section>

        <!-- Section E: Promotional Supermarket Callout Banners -->
        <section class="fc-promo-split">
            <div class="fc-promo-banner pickup-banner">
                <h3><i class="bi bi-shop me-2"></i>Save Time – Order Online &amp; Pick Up In-Store</h3>
                <p>No delivery fees! Collect your pre-packed order at our dedicated supermarket pickup counter in as little as 2 hours.</p>
                <a href="product/product.php?pickup=1">Schedule Store Pickup <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="fc-promo-banner delivery-banner">
                <h3><i class="bi bi-truck me-2"></i>Fresh Produce Delivered to Your Doorstep</h3>
                <p>Enjoy island-wide flat-rate delivery with complete peace of mind. Pay cash when your grocery basket arrives at your door.</p>
                <a href="product/product.php">Order Home Delivery <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </section>

        <!-- Section F: Daily Grocery & Pantry Staples Tabbed View -->
        <section class="mb-5">
            <div class="fc-section-head">
                <div>
                    <h2><i class="bi bi-basket-fill text-success me-2"></i>Daily Grocery &amp; Pantry Staples</h2>
                    <small class="text-muted">High quality kitchen essentials for your family</small>
                </div>
            </div>

            <!-- Tabs -->
            <div class="fc-tab-nav">
                <button type="button" class="fc-tab-btn active" onclick="filterHomeStaples('all', this)">All Staples</button>
                <button type="button" class="fc-tab-btn" onclick="filterHomeStaples('1,2', this)">Fresh Produce</button>
                <button type="button" class="fc-tab-btn" onclick="filterHomeStaples('15', this)">Pantry &amp; Rice</button>
                <button type="button" class="fc-tab-btn" onclick="filterHomeStaples('16', this)">Dairy &amp; Milk</button>
                <button type="button" class="fc-tab-btn" onclick="filterHomeStaples('14', this)">Meat &amp; Seafood</button>
                <button type="button" class="fc-tab-btn" onclick="filterHomeStaples('3,4', this)">Snacks &amp; Biscuits</button>
            </div>

            <div class="fc-product-grid" id="homeStaplesGrid">
                <?php 
                if ($staples_res && $staples_res->num_rows > 0) {
                    while ($item = $staples_res->fetch_assoc()) {
                        $itemId = (int)$item['id'];
                        $stock = (float)$item['stock_quantity'];
                        $inStock = ($item['status'] === 'active' && $stock > 0);
                        $itemCartQty = $inCart[$itemId] ?? 0;
                        $imgPath = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : 'assets/images/logo/logo-freshco.png';
                        ?>
                        <div class="fc-card <?php echo $inStock ? '' : 'out-of-stock'; ?>" data-category="<?php echo $item['category_id']; ?>">
                            <div class="fc-card-thumb">
                                <span class="fc-card-badge-stock <?php echo $inStock ? 'in-stock' : 'out-stock'; ?>">
                                    <?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?>
                                </span>
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy" onerror="this.src='assets/images/logo/logo-freshco.png'">
                            </div>
                            <div class="fc-card-body">
                                <span class="fc-card-unit"><?php echo htmlspecialchars($item['unit'] ?: '1 unit'); ?></span>
                                <h3 class="fc-card-title" title="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <div class="fc-card-price-row">
                                    <span class="fc-card-price">Rs. <?php echo number_format((float)$item['price'], 2); ?></span>
                                </div>
                                <div class="fc-card-action" id="card-action-<?php echo $itemId; ?>" data-card-action="<?php echo $itemId; ?>">
                                    <?php if (!$inStock): ?>
                                        <button class="fc-btn-add" disabled>Out of Stock</button>
                                    <?php elseif ($itemCartQty > 0): ?>
                                        <div class="fc-stepper">
                                            <button type="button" onclick="updateCartItem(<?php echo $itemId; ?>, -1, this)" aria-label="Decrease">&minus;</button>
                                            <span class="qty"><?php echo $itemCartQty; ?></span>
                                            <button type="button" onclick="updateCartItem(<?php echo $itemId; ?>, 1, this)" aria-label="Increase">&plus;</button>
                                        </div>
                                    <?php else: ?>
                                        <button class="fc-btn-add" onclick="updateCartItem(<?php echo $itemId; ?>, 1, this)">
                                            <i class="bi bi-cart-plus me-1"></i>Add
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                } ?>
            </div>
        </section>
    </main>

    <!-- Offcanvas Mini-Cart Drawer -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="miniCartOffcanvas" aria-labelledby="miniCartLabel" style="width: 380px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold text-success" id="miniCartLabel">
                <i class="bi bi-basket2 me-2"></i>Your Grocery Basket
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="miniCartItemsContainer">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border text-success" role="status"></div>
                <div class="mt-2">Loading your basket...</div>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top bg-light" id="miniCartFooter" style="display:none;">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold">Items Subtotal:</span>
                <span class="fw-bold text-dark fs-5" id="miniCartSubtotal">Rs. 0.00</span>
            </div>
            <p class="small text-muted mb-3">Taxes &amp; delivery options calculated at checkout.</p>
            <div class="d-grid gap-2">
                <a href="proceed.php?method=cod" class="btn btn-success fw-bold py-2 rounded-pill">
                    <i class="bi bi-truck me-1"></i>Checkout – Home Delivery (COD)
                </a>
                <a href="proceed.php?method=pickup" class="btn btn-warning text-dark fw-bold py-2 rounded-pill">
                    <i class="bi bi-shop me-1"></i>Checkout – Free Store Pickup
                </a>
                <a href="product/shopping-cart.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    View Full Cart Details
                </a>
            </div>
        </div>
    </div>

    <!-- Section G: Modern Supermarket Footer -->
    <footer class="fc-footer">
        <div class="container-fluid px-lg-5">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="assets/images/logo/logo-freshco.png" alt="Freshcomart Logo" height="42" class="mb-3">
                    <p class="small text-muted">
                        Freshcomart is Colombo's premier online supermarket, delivering fresh farm produce, groceries, and daily household essentials straight to your door or ready for fast in-store pickup.
                    </p>
                    <div class="small text-light mt-2">
                        <div><i class="bi bi-geo-alt text-success me-2"></i><?php echo htmlspecialchars($store['address']); ?></div>
                        <div class="mt-1"><i class="bi bi-clock text-success me-2"></i>Open Daily: 8:00 AM – 9:00 PM</div>
                        <div class="mt-1"><i class="bi bi-telephone text-success me-2"></i>Hotline: +94 11 234 5678</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php">Home</a></li>
                        <li class="mb-2"><a href="product/product.php">Shop Catalog</a></li>
                        <li class="mb-2"><a href="about-us.html">About Us</a></li>
                        <li class="mb-2"><a href="contact-us.php">Contact Us</a></li>
                        <li class="mb-2"><a href="my-account.php">My Account</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-3 col-6">
                    <h5>Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="product/product.php?category=1">Vegetables &amp; Greens</a></li>
                        <li class="mb-2"><a href="product/product.php?category=2">Fresh Fruits</a></li>
                        <li class="mb-2"><a href="product/product.php?category=16">Dairy &amp; Eggs</a></li>
                        <li class="mb-2"><a href="product/product.php?category=14">Meat &amp; Seafood</a></li>
                        <li class="mb-2"><a href="product/product.php?category=15">Pantry Staples</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-12">
                    <h5>Fulfillment &amp; Payment</h5>
                    <p class="small text-muted">We provide two convenient payment and collection channels:</p>
                    <div class="d-flex flex-column gap-2">
                        <div class="p-2 border rounded border-secondary bg-dark small text-light">
                            <i class="bi bi-truck text-success me-2"></i>Cash on Delivery (Flat Rs. 450)
                        </div>
                        <div class="p-2 border rounded border-secondary bg-dark small text-light">
                            <i class="bi bi-shop text-warning me-2"></i>Free In-Store Pickup (Rajagiriya)
                        </div>
                    </div>
                </div>
            </div>
            <div class="fc-footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Freshcomart Supermarket (CEC Web). All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Base Path for JS -->
    <script>window.FC_BASE = '';</script>
    <script src="assets/js/supermarket-store.js"></script>

    <script>
        // Mini Cart Offcanvas Drawer Loader
        function openMiniCartDrawer() {
            var offcanvasEl = document.getElementById('miniCartOffcanvas');
            var bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
            bsOffcanvas.show();

            var container = document.getElementById('miniCartItemsContainer');
            var footer = document.getElementById('miniCartFooter');
            var subtotalEl = document.getElementById('miniCartSubtotal');

            fetch('product/miniCart.php')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.items || data.items.length === 0) {
                        container.innerHTML = '<div class="text-center py-5 text-muted">' +
                            '<i class="bi bi-basket3 fs-1 text-secondary d-block mb-2"></i>' +
                            '<span class="fw-semibold">Your grocery basket is empty.</span>' +
                            '<div class="mt-3"><a href="product/product.php" class="btn btn-success btn-sm rounded-pill px-3">Start Shopping</a></div>' +
                            '</div>';
                        footer.style.display = 'none';
                        return;
                    }

                    var html = '<div class="list-group list-group-flush">';
                    data.items.forEach(function(item) {
                        var imgPath = item.image ? item.image : 'assets/images/logo/logo-freshco.png';
                        html += '<div class="list-group-item px-0 py-3 d-flex align-items-center gap-3">' +
                                '<img src="' + imgPath + '" alt="' + item.name + '" width="54" height="54" class="rounded border object-fit-contain p-1" onerror="this.src=\'assets/images/logo/logo-freshco.png\'">' +
                                '<div class="flex-grow-1">' +
                                    '<div class="fw-semibold small text-truncate" style="max-width:180px;">' + item.name + '</div>' +
                                    '<div class="text-muted small">Qty: ' + item.qty + (item.unit ? ' &middot; ' + item.unit : '') + '</div>' +
                                '</div>' +
                                '<div class="fw-bold text-success text-end small">Rs. ' + item.line_total + '</div>' +
                            '</div>';
                    });
                    html += '</div>';

                    container.innerHTML = html;
                    subtotalEl.textContent = 'Rs. ' + data.subtotal;
                    footer.style.display = 'block';

                    // Synchronize header badges
                    var count = data.items.length;
                    document.querySelectorAll('#fcCartBadge').forEach(function(b) { b.textContent = count; });
                })
                .catch(function(err) {
                    container.innerHTML = '<div class="text-danger py-4 text-center">Unable to load cart. Please try again.</div>';
                });
        }
    </script>
</body>
</html>
