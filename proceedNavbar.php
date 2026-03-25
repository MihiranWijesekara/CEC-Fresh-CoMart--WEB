<?php
  require 'connection.php';
  session_start();

  $cart_count = 0;
  $can_access_cart = false;
  $cart_disabled_reason = 'Login required';

  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");
    $cart_count = $cart_rs->num_rows;

    if ($cart_count > 0) {
      $can_access_cart = true;
      $cart_disabled_reason = '';
    } else {
      $cart_disabled_reason = 'Your cart is empty';
    }
  }
?>
<!doctype html>
<html>
  <head>
    <title>Navbar Design</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />

    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        margin: 0;
        font-family: Arial, sans-serif;
      }

      /* MT Logo */
      .fc-wrapper {
        display: flex;
        align-items: center;
      }

      .mt-logo {
        /* margin-top: 20px; */
        margin-right: 50px;
        width: 43px;
        height: 43px;
        background: #007bff;
        border-radius: 60%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        color: white;
        font-size: 16px;
        font-weight: bold;
      }

      .mt-status-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        position: absolute;
        top: 0.05em;
        right: 4px;
        background: orange;
      }
      @media (max-width: 600px) {
        .mt-logo {
          width: 32px;
          height: 32px;
          font-size: 12px;
          top: 0.5em;
        }
        .mt-status-dot {
          width: 6px;
          height: 6px;
          right: 2px;
          top: 0.1em;
        }
      }

      /* Desktop Navbar */
      .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 45px;
        background-color: #27b62e;
        width: 100%;
        box-sizing: border-box;
      }

      .nav-left {
        display: flex;
        align-items: center;
        gap: 20px;
      }

      .nav-left img {
        height: 60px;
        width: 150px;
      }

      /* Dropdown */
      .dropdown {
        position: relative;
      }

      .dropbtn {
        background: white;
        color: #2e7d32;
        padding: 8px 18px;
        border-radius: 25px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .dropdown-content {
        display: none;
        position: absolute;
        top: 50px;
        left: 0;
        background-color: white;
        min-width: 220px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        border-radius: 10px;
        overflow: hidden;
        z-index: 1000;
      }

      .dropdown-content.show {
        display: block;
      }

      .dropdown-content a {
        display: block;
        padding: 8px 10px;
        text-decoration: none;
        color: #333;
        transition: background 0.3s;
      }

      .dropdown-content a:hover {
        background-color: #f1f1f1;
        color: #2e7d32;
      }

      /* Rotate icon */
      .rotate {
        transform: rotate(180deg);
        transition: 0.3s;
      }

      /* Search */
      .search-bar {
        display: flex;
        align-items: center;
      }

      .search-bar input {
        padding: 8px;
        width: 250px;
        border-radius: 5px 0 0 5px;
        border: none;
        outline: none;
      }

      .search-bar button {
        padding: 8px 12px;
        border: none;
        background-color: #247a2a;
        color: white;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
      }

      /* Right Side */
      .nav-right {
        display: flex;
        align-items: center;
        gap: 15px;
      }

      .login-btn,
      .signup-btn {
        padding: 8px 18px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
      }

      .login-btn {
        background: white;
        color: #2e7d32;
        border: 2px solid white;
      }

      .login-btn:hover {
        background: transparent;
        color: white;
      }

      .signup-btn {
        background: #ff9800;
        color: white;
      }

      .signup-btn:hover {
        background: #e68900;
        transform: translateY(-2px);
      }

      .cart-btn {
        font-size: 25px;
        text-decoration: none;
        color: rgb(254, 254, 254);
        transition: transform 0.3s ease;
      }

      /* Mobile menu toggle button */
      .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
      }

      /* Mobile Sidebar Menu */
      .mobile-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 280px;
        height: 100vh;
        background-color: #1a1a1a;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        transition: right 0.3s ease;
        overflow-y: auto;
      }

      .mobile-sidebar.open {
        right: 0;
      }

      .mobile-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background-color: #27b62e;
      }

      .mobile-sidebar-header h3 {
        color: white;
        font-size: 18px;
      }

      .close-sidebar {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
      }

      .mobile-menu {
        padding: 20px;
      }

      .mobile-menu-item {
        margin-bottom: 15px;
      }

      .mobile-menu-item a,
      .mobile-menu-item button {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 12px 15px;
        background: #2a2a2a;
        color: white;
        text-decoration: none;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        cursor: pointer;
        transition: background 0.3s;
      }

      .mobile-menu-item a:hover,
      .mobile-menu-item button:hover {
        background: #27b62e;
      }

      .mobile-category-list {
        display: none;
        margin-top: 10px;
        padding-left: 15px;
      }

      .mobile-category-list.show {
        display: block;
      }

      .mobile-category-list a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
        color: #ccc;
        text-decoration: none;
        font-size: 14px;
        border-left: 2px solid #27b62e;
        margin-bottom: 5px;
        transition: all 0.3s;
      }

      .mobile-category-list a:hover {
        color: white;
        background: #2a2a2a;
      }

      /* Overlay */
      .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
      }

      .overlay.show {
        display: block;
      }

      /* Category icon colors */
      .veg {
        color: #4caf50;
      }
      .fruit {
        color: #ff5252;
      }
      .snack {
        color: #ff9800;
      }
      .biscuit {
        color: #a1887f;
      }
      .coffee {
        color: #6d4c41;
      }
      .egg {
        color: #fbc02d;
      }
      .water {
        color: #2196f3;
      }
      .tea {
        color: #8bc34a;
      }
      .cheese {
        color: #ffca28;
      }
      .yoghurt {
        color: #ba68c8;
      }
      .dessert {
        color: #e91e63;
      }

      /* Mobile Responsive */
      @media screen and (max-width: 968px) {
        .navbar {
          padding: 12px 20px;
        }

        /* Hide desktop elements */
        .dropdown,
        .search-bar,
        .login-btn,
        .signup-btn {
          display: none;
        }

        /* Show mobile menu toggle */
        .mobile-menu-toggle {
          display: block;
        }

        .logo {
          height: 40px;
        }

        .nav-left {
          gap: 0;
        }

        .nav-right {
          display: flex;
          align-items: center;
          gap: 15px;
        }

        .cart-btn {
          font-size: 22px;
        }

        /* Mobile search in sidebar */
        .mobile-search {
          margin-bottom: 20px;
        }

        .mobile-search input {
          width: 100%;
          padding: 12px;
          border: none;
          border-radius: 8px;
          outline: none;
          background: #2a2a2a;
          color: white;
          font-size: 14px;
        }

        .mobile-search input::placeholder {
          color: #999;
        }

        .mobile-search button {
          display: none;
        }
      }

      @media screen and (max-width: 480px) {
        .mobile-sidebar {
          width: 100%;
          right: -100%;
        }

        .navbar {
          padding: 10px 15px;
        }
      }
    </style>
  </head>

  <body>
    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- Mobile Sidebar -->
    <div class="mobile-sidebar" id="mobileSidebar">
      <div class="mobile-sidebar-header">
        <h3>Menu</h3>
        <button class="close-sidebar" onclick="closeSidebar()">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="mobile-menu">
    
        <!-- Login -->
        <div class="mobile-menu-item">
          <a href="../login/sign.php">
            <i class="fas fa-user"></i>
            <span>Login</span>
          </a>
        </div>

        <!-- Sign Up -->
        <div class="mobile-menu-item">
          <a href="../login/register.php">
            <i class="fas fa-user-plus"></i>
            <span>Sign Up</span>
          </a>
        </div>

        <!-- Cart -->
        <div class="mobile-menu-item">
          <?php if ($can_access_cart): ?>
            <a href="../product/shopping-cart.php">
              <i class="fas fa-shopping-cart"></i>
              <span>Shopping Cart</span>
              <span class="cart-count"><?php echo $cart_count; ?></span>
            </a>
          <?php else: ?>
            <button type="button" disabled title="<?php echo $cart_disabled_reason; ?>">
              <i class="fas fa-shopping-cart"></i>
              <span>Shopping Cart</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <style>
      /* Mobile Menu Full Width */

      .mobile-menu {
        padding: 15px;
      }

      /* Search Box */
      .mobile-search input {
        width: 90%;
        height: 45px;
        padding: 0 12px;
        border-radius: 8px;
        border: 1px solid #dbd5d5;
        font-size: 15px;
        box-sizing: border-box;
        margin-bottom: 10px;
      }

      /* Menu Items (Buttons + Links) */
      .mobile-menu-item button,
      .mobile-menu-item a {
        width: 90%;
        height: 45px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        border-radius: 8px;
        border: none;
        background-color: #f0ecec;
        text-decoration: none;
        color: #333;
        font-size: 15px;
        box-sizing: border-box;
        margin-bottom: 10px;
        cursor: pointer;
      }

      /* Category List Links */
      .mobile-category-list a {
        width: 70%;
        height: 40px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 20px;
        text-decoration: none;
        color: #a71313;
        font-size: 14px;
        box-sizing: border-box;
      }

      /* Arrow Rotate Animation */
      .rotate {
        transform: rotate(180deg);
        transition: 0.3s;
      }

      /* Show/Hide Category */
      .mobile-category-list {
        display: none;
      }

      .mobile-category-list.show {
        display: block;
      }
    </style>

    <div class="navbar">
      <!-- Left Side -->
      <div class="nav-left">
        <a href="../index.php">
          <img src="assets/images/logo/logo-freshco.png" alt="Header Logo" />
        </a>
      </div>

      <!-- Right Side -->
      <div class="nav-right">
        <div class="mt-logo">
          FC
          <div class="mt-status-dot" id="mtStatusDot"></div>
        </div>
        <a href="../login/sign.php" class="login-btn">Login</a>
        <a href="../login/register.php" class="signup-btn">Sign Up</a>
        <?php if ($can_access_cart): ?>
          <a href="../product/shopping-cart.php" class="cart-btn">
            <i class="fa-solid fa-cart-shopping"></i>
          </a>
        <?php else: ?>
          <span class="cart-btn" aria-disabled="true" title="<?php echo $cart_disabled_reason; ?>">
            <i class="fa-solid fa-cart-shopping"></i>
          </span>
        <?php endif; ?>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="openSidebar()">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>

    <!-- JavaScript -->
    <script src="proceedNavbar.js"></script>
  </body>
</html>
