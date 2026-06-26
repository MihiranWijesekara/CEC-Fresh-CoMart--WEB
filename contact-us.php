<!doctype html>
<?php
  require 'connection.php';
  session_start();
  $status_dot_color = isset($_SESSION['user_id']) ? 'GreenYellow' : 'red';

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
?>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>CEC Fresh-Co Mart </title>
    <meta name="robots" content="noindex, follow" />
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/logo-freshco.png">

    <!-- CSS
	============================================ -->
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/vendor/bootstrap.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="assets/css/vendor/font.awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="assets/css/vendor/ionicons.min.css">
    <!-- Slick CSS -->
    <link rel="stylesheet" href="assets/css/plugins/slick.min.css">
    <!-- Animation -->
    <link rel="stylesheet" href="assets/css/plugins/animate.min.css">
    <!-- jQuery Ui -->
    <link rel="stylesheet" href="assets/css/plugins/jquery-ui.min.css">
    <!-- Nice Select -->
    <link rel="stylesheet" href="assets/css/plugins/nice-select.min.css">
    <!-- Magnific Popup -->
    <link rel="stylesheet" href="assets/css/plugins/magnific-popup.css">

    <!-- Vendor & Plugins CSS (Please remove the comment from below vendor.min.css & plugins.min.css for better website load performance and remove css files from the above) -->

    <!-- <link rel="stylesheet" href="assets/css/vendor/vendor.min.css">
    <link rel="stylesheet" href="assets/css/plugins/plugins.min.css"> -->

    <!-- Main Style CSS (Please use minify version for better website load performance) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- <link rel="stylesheet" href="assets/css/style.min.css"> -->

      <style>
        /* MT Logo */
.fc-wrapper{
    display:flex;
    align-items:center;
}

.header-right-area.main-nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.mt-logo{
    margin-right: 110px;
    width:43px;
    height:43px;
    background:#007bff;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
    color:white;
    font-size:16px;
    font-weight:bold;
}

.mt-status-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    position:absolute;
    top: 0.05em;        
    right:4px;      
    background:orange;
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

    /* Profile Dropdown styling */
    .profile-dropdown-wrapper {
      position: relative;
      display: inline-block;
      margin-right: 110px;
    }
    .profile-dropdown-wrapper .mt-logo {
      margin-right: 0 !important;
    }
    .profile-dropdown-menu {
      display: none;
      position: absolute;
      top: 75px;
      right: 0;
      background-color: white;
      min-width: 220px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      border-radius: 12px;
      overflow: hidden;
      z-index: 1100;
      border: 1px solid rgba(0, 0, 0, 0.06);
      font-family: Arial, sans-serif;
    }
    .profile-dropdown-menu.show {
      display: block;
      animation: profileFadeIn 0.2s ease;
    }
    @keyframes profileFadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .profile-dropdown-header {
      padding: 15px 18px;
      border-bottom: 1px solid #f3f4f6;
      background-color: #f9fafb;
    }
    .profile-dropdown-header .user-name {
      font-weight: 700;
      color: #27b62e;
      font-size: 14px;
      margin-bottom: 2px;
      text-transform: none;
    }
    .profile-dropdown-header .user-email {
      color: #6b7280;
      font-size: 12px;
      word-break: break-all;
      text-transform: none;
    }
    .profile-dropdown-menu a {
      display: flex !important;
      align-items: center !important;
      gap: 10px !important;
      padding: 12px 18px !important;
      text-decoration: none !important;
      color: #374151 !important;
      font-size: 14px !important;
      font-weight: 600 !important;
      transition: background 0.2s, color 0.2s !important;
      border: none !important;
      background: none !important;
      width: 100% !important;
      box-sizing: border-box !important;
      text-transform: none !important;
    }
    .profile-dropdown-menu a:hover {
      background-color: #e8f5e9 !important;
      color: #27b62e !important;
    }
    .profile-dropdown-menu i {
      font-size: 16px;
      color: #6b7280;
      width: 20px;
      text-align: center;
    }
    .profile-dropdown-menu a:hover i {
      color: #27b62e;
    }
    .mt-logo {
      cursor: pointer;
    }
    </style>
</head>

<body>

    <div class="contact-wrapper">
        <header class="main-header-area">
            <!-- Main Header Area Start -->
            <div class="main-header">
                <div class="container container-default custom-area">
                    <div class="row">
                        <div class="col-lg-12 col-custom">
                            <div class="row align-items-center">
                                <div class="col-lg-2 col-xl-2 col-sm-6 col-6 col-custom">
                                    <div class="header-logo d-flex align-items-center">
                                        <a href="index.php">
                                            <img class="img-full" src="assets/images/logo/logo-freshco.png" alt="Header Logo">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-8 col-xl-7 position-static d-none d-lg-block col-custom">
                                    <nav class="main-nav d-flex justify-content-center">
                                        <ul class="nav">
                                            <li>
                                                <a href="index.php">
                                                    <span class="menu-text"> Home</span>
                                                    
                                                </a>
                                            
                                            </li>
                                            <li>
                                                <a href="about-us.html">
                                                    <span class="menu-text"> About</span>
                                                </a>
                                            </li>

                                           
                                             <li class="menu-item-has-children">
                                                <a href="product/product.php">
                                                    <span class="menu-text"> Products</span>
                                                </a>

                                            </li>
                                           
                                            <li>
                                               
                                            </li>
                                           
                                            <li>
                                                <a class="active" href="contact-us.php">
                                                    <span class="menu-text">Contact</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <div class="col-lg-2 col-xl-3 col-sm-6 col-6 col-custom">
                                   <div class="header-right-area main-nav">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <div class="profile-dropdown-wrapper">
                                                <div class="mt-logo">
                                                    <?php echo $logo_initials; ?>
                                                    <div class="mt-status-dot" style="background: <?php echo $status_dot_color; ?>;"></div>
                                                </div>
                                                <div class="profile-dropdown-menu">
                                                    <div class="profile-dropdown-header">
                                                        <div class="user-name">Hello, <?php echo isset($_SESSION['users']['first_name']) ? htmlspecialchars($_SESSION['users']['first_name']) : 'User'; ?></div>
                                                        <div class="user-email"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?></div>
                                                    </div>
                                                    <a href="profile.php"><i class="fa fa-cog"></i> Edit Profile</a>
                                                    <a href="product/my-orders.php"><i class="fa fa-history"></i> Order History</a>
                                                    <a href="login/logout.php" style="border-top: 1px solid #f3f4f6;"><i class="fa fa-sign-out"></i> Logout</a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-logo">
                                                <?php echo $logo_initials; ?>
                                                <div class="mt-status-dot" style="background: <?php echo $status_dot_color; ?>;"></div>
                                            </div>
                                        <?php endif; ?>
                                        <ul class="nav">
                                            <?php if (!isset($_SESSION['user_id'])): ?>
                                                <li class="login-register-wrap d-none d-xl-flex">
                                                    <span><a href="login/sign.php">Login</a></span>
                                                    <span><a href="login/register.php">Register</a></span>
                                                </li>
                                            <?php endif; ?>
                                         
                                            <li class="mobile-menu-btn d-lg-none">
                                                <a class="off-canvas-btn" href="#">
                                                    <i class="fa fa-bars"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Main Header Area End -->
            <!-- Sticky Header Start Here-->
            <div class="main-header header-sticky">
                <div class="container container-default custom-area">
                    <div class="row">
                        <div class="col-lg-12 col-custom">
                            <div class="row align-items-center">
                                <div class="col-lg-2 col-xl-2 col-sm-6 col-6 col-custom">
                                    <div class="header-logo">
                                        <a href="index.php">
                                            <img class="img-full" src="assets/images/logo/logo-freshco.png" alt="Header Logo">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-8 col-xl-7 position-static d-none d-lg-block col-custom">
                                    <nav class="main-nav d-flex justify-content-center">
                                        <ul class="nav">
                                            <li>
                                                <a href="index.php">
                                                    <span class="menu-text"> Home</span>
                                                   
                                                </a>
                                              
                                            </li>

                                            <li>
                                                <a href="about-us.html">
                                                    <span class="menu-text"> About</span>
                                                </a>
                                            </li>

                                               <li class="menu-item-has-children">
                                                <a href="product/product.html">
                                                    <span class="menu-text"> Products</span>
                                                </a>

                                            </li>

<style>
    .menu-item-has-children {
    position: relative;
}

.sub-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    width: 200px;
    padding: 10px 0;
    list-style: none;
    display: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    z-index: 999;
}

.sub-menu li a {
    display: block;
    padding: 8px 15px;
    color: #333;
    text-decoration: none;
}

.sub-menu li a:hover {
    background: #f5f5f5;
    color: #000;
}

.menu-item-has-children:hover .sub-menu {
    display: block;
}

</style>                                            

                                           
                                         
                                            <li>
                                                <a class="active" href="contact-us.php">
                                                    <span class="menu-text">Contact</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <div class="col-lg-2 col-xl-3 col-sm-6 col-6 col-custom">
                                    <div class="header-right-area main-nav">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <div class="profile-dropdown-wrapper">
                                                <div class="mt-logo">
                                                    <?php echo $logo_initials; ?>
                                                    <div class="mt-status-dot" style="background: <?php echo $status_dot_color; ?>;"></div>
                                                </div>
                                                <div class="profile-dropdown-menu">
                                                    <div class="profile-dropdown-header">
                                                        <div class="user-name">Hello, <?php echo isset($_SESSION['users']['first_name']) ? htmlspecialchars($_SESSION['users']['first_name']) : 'User'; ?></div>
                                                        <div class="user-email"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?></div>
                                                    </div>
                                                    <a href="profile.php"><i class="fa fa-cog"></i> Edit Profile</a>
                                                    <a href="product/my-orders.php"><i class="fa fa-history"></i> Order History</a>
                                                    <a href="login/logout.php" style="border-top: 1px solid #f3f4f6;"><i class="fa fa-sign-out"></i> Logout</a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-logo">
                                                <?php echo $logo_initials; ?>
                                                <div class="mt-status-dot" style="background: <?php echo $status_dot_color; ?>;"></div>
                                            </div>
                                        <?php endif; ?>
                                        <ul class="nav">
                                            <?php if (!isset($_SESSION['user_id'])): ?>
                                                <li class="login-register-wrap d-none d-xl-flex">
                                                    <span><a href="login/sign.php">Login</a></span>
                                                    <span><a href="login/register.php">Register</a></span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="mobile-menu-btn d-lg-none">
                                                <a class="off-canvas-btn" href="#">
                                                    <i class="fa fa-bars"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sticky Header End Here -->
            <!-- off-canvas menu start -->
            <aside class="off-canvas-wrapper" id="mobileMenu">
                <div class="off-canvas-overlay"></div>
                <div class="off-canvas-inner-content">
                    <div class="btn-close-off-canvas">
                        <i class="fa fa-times"></i>
                    </div>
                    <div class="off-canvas-inner">
                        <div class="search-box-offcanvas">
                            <form>
                                <input type="text" placeholder="Search product...">
                                <button class="search-btn"><i class="fa fa-search"></i></button>
                            </form>
                        </div>
                        <!-- mobile menu start -->
                        <div class="mobile-navigation">
                            <!-- mobile menu navigation start -->
                            <nav>
                                <ul class="mobile-menu">
                                    <li class="menu-item-has-children"><a href="#">Home</a>
                                        
                                    </li>
                                    
                                  
                                    <li><a href="about-us.html">About Us</a></li>
                                    <li class="menu-item-has-children"><a href="#">Shop</a>
                                        <ul class="megamenu dropdown">
                                            
                                                <ul class="dropdown">
                                                    <li><a href="shop.html">Shop Left Sidebar</a></li>
                                                   
                                                </ul>
                                            </li>
                                            
                                                <ul class="dropdown">
                                                    <li><a href="product-details.html">Single Product Details</a></li>
                                                
                                                </ul>
                                            </li>
                                           
                                        </ul>
                                    </li>
                                  
                                    <li><a href="contact-us.php">Contact</a></li>
                                </ul>
                            </nav>
                            <!-- mobile menu navigation end -->
                        </div>
                        <!-- mobile menu end -->
                        <div class="header-top-settings offcanvas-curreny-lang-support">
                            <!-- mobile menu navigation start -->
                           <nav>
                                <ul class="mobile-menu">
                                    <li class="menu-item-has-children"><a href="#">My Account</a>
                                        <ul class="dropdown">
                                            <li><a href="login/sign.html">Login</a></li>
                                            <li><a href="login/register.html">Register</a></li>
                                        </ul>
                                    </li>
                                   
                                </ul>
                            </nav>
                            <!-- mobile menu navigation end -->
                        </div>
                        <!-- offcanvas widget area start -->
                        <div class="offcanvas-widget-area">
                            <div class="top-info-wrap text-left text-black">
                                <ul>
                                    <li>
                                        <i class="fa fa-phone"></i>
                                        <a href="info@yourdomain.com">(1245) 2456 012</a>
                                    </li>
                                    <li>
                                        <i class="fa fa-envelope"></i>
                                        <a href="info@yourdomain.com">info@yourdomain.com</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="off-canvas-widget-social">
                                <a title="Facebook-f" href="#"><i class="fa fa-facebook-f"></i></a>
                                <a title="Twitter" href="#"><i class="fa fa-twitter"></i></a>
                                <a title="Linkedin" href="#"><i class="fa fa-linkedin"></i></a>
                                <a title="Youtube" href="#"><i class="fa fa-youtube"></i></a>
                                <a title="Vimeo" href="#"><i class="fa fa-vimeo"></i></a>
                            </div>
                        </div>
                        <!-- offcanvas widget area end -->
                    </div>
                </div>
            </aside>
            <!-- off-canvas menu end -->
        </header>
        <!-- Breadcrumb Area Start Here -->
        <div class="breadcrumbs-area position-relative">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <div class="breadcrumb-content position-relative section-content">
                            <h3 class="title-3">contact Us</h3>
                         
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .breadcrumbs-area {
            background-image: url("assets/images/brand-logo/conta.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 120px 0;
        }
    </style>
        <!-- Breadcrumb Area End Here -->
        <!-- Contact Us Area Start Here -->
        <div class="contact-us-area">
            <div class="container container-default-2 custom-area">
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-custom">
                        <div class="contact-info-item">
                            <div class="con-info-icon">
                                <i class="ion-ios-location-outline"></i>
                            </div>
                            <div class="con-info-txt">
                                <h4>Our Location</h4>
                                <p>(800) 123 456 789 / (800) 123 456 789  WWW.CEC.LK</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-custom">
                        <div class="contact-info-item">
                            <div class="con-info-icon">
                                <i class="ion-iphone"></i>
                            </div>
                            <div class="con-info-txt">
                                <h4>Contact us Anytime</h4>
                                <p>Mobile: +94 77 877 8731<br>Fax: +94-11-2078246</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 col-custom text-align-center">
                        <div class="contact-info-item">
                            <div class="con-info-icon">
                                <i class="ion-ios-email-outline"></i>
                            </div>
                            <div class="con-info-txt">
                                <h4>Support Overall</h4>
                                <p> info@cec.lk <br> md@cec.lk</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-custom">
                        <form method="post" action="http://whizthemes.com/mail-php/reza/obrien/mail.php" id="contact-form" accept-charset="UTF-8" class="contact-form">
                            <div class="comment-box mt-5">
                                <h5 class="text-uppercase">Get in Touch</h5>
                                <div class="row mt-3">
                                    <div class="col-md-6 col-custom">
                                        <div class="input-item mb-4">
                                            <input class="border rounded-0 w-100 input-area name" type="text" name="con_name" id="con_name" placeholder="Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-custom">
                                        <div class="input-item mb-4">
                                            <input class="border rounded-0 w-100 input-area email" type="email" name="con_email" id="con_email" placeholder="Email">
                                        </div>
                                    </div>
                                    <div class="col-12 col-custom">
                                        <div class="input-item mb-4">
                                            <input class="border rounded-0 w-100 input-area email" type="text" name="con_content" id="con_content" placeholder="Subject">
                                        </div>
                                    </div>
                                    <div class="col-12 col-custom">
                                        <div class="input-item mb-4">
                                            <textarea cols="30" rows="5" class="border rounded-0 w-100 custom-textarea input-area" name="con_message" id="con_message" placeholder="Message"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-custom mt-40">
                                        <button type="button" onclick="contactUs()" name="submit" class="btn obrien-button primary-btn rounded-0 mb-0">Send A Message</button>
                                    </div>
                                    <div id="msgdiv" style="display:none; margin-top: 18px; margin-bottom: 10px;"></div>
                                    <p class="col-12 col-custom form-message mb-0"></p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact Us Area End Here -->
        <!-- Google Maps -->
        <div class="google-map-area">
            <div id="contacts" class="map-area">
                <div id="googleMap">
                    <iframe src="https://www.google.com/maps?q=423+Nawala+Rd,+Sri+Jayawardenepura+Kotte+10100&output=embed" style="width:100%;height:100%"></iframe>
                </div>
            </div>
        </div><br>
        <!-- Google Maps End -->
        <!-- Support Area Start Here -->
        <div class="support-area">
            <div class="container container-default custom-area">
                <div class="row">
                    <div class="col-lg-12 col-custom">
                        <div class="support-wrapper d-flex">
                            <div class="support-content">
                                <h1 class="title">Contact Us</Canvas></h1>
                                <p class="desc-content">Call our sales person for more information</p>
                            </div>
                            <div class="support-button d-flex align-items-center">
                                <a class="obrien-button primary-btn" href="contact-us.php">+94 77 877 8731 </a>
                                <!--<a class="obrien-button primary-btn" href="contact-us.html">+94 77 877 8731 </a>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
        <!-- Support Area End Here -->
        <!-- Footer Area Start Here -->
        <footer class="footer-area">
            <div class="footer-widget-area">
                <div class="container container-default custom-area">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-3 col-custom">
                            <div class="single-footer-widget m-0">
                                <div class="footer-logo">
                                    <a href="index.php">
                                        <img src="assets/images/logo/logo-freshco.png" alt="Logo Image">
                                    </a>
                                </div>
                                <p class="desc-content">Fresh-Co Mart offers fresh vegetables, quality foods, and bakery items for your daily nutrition.</p>
                                <div class="social-links">
                                    <ul class="d-flex">
                                        <li>
                                            <a class="border rounded-circle" href="#" title="Facebook">
                                                <i class="fa fa-facebook-f"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="border rounded-circle" href="#" title="Twitter">
                                                <i class="fa fa-twitter"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="border rounded-circle" href="#" title="Linkedin">
                                                <i class="fa fa-linkedin"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="border rounded-circle" href="#" title="Youtube">
                                                <i class="fa fa-youtube"></i>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="border rounded-circle" href="#" title="Vimeo">
                                                <i class="fa fa-vimeo"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-2 col-custom">
                            <div class="single-footer-widget">
                                <h2 class="widget-title">Information</h2>
                                <ul class="widget-list">
                                    <li><a href="about-us.html">Our Company</a></li>
                                    <li><a href="contact-us.php">Contact Us</a></li>
                                    <li><a href="about-us.html">Our Services</a></li>
                                    <li><a href="about-us.html">Why We?</a></li>
                                    <li><a href="about-us.html">Careers</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-2 col-custom">
                            <div class="single-footer-widget">
                                <h2 class="widget-title">Quicklink</h2>
                                <ul class="widget-list">
                                    <li><a href="about-us.html">About</a></li>
                                    <li><a href="#">Blog</a></li>
                                    <li><a href="#">Shop</a></li>
                                    <li><a href="#">Cart</a></li>
                                    <li><a href="contact-us.php">Contact</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-2 col-custom">
                            <div class="single-footer-widget">
                                <h2 class="widget-title">Support</h2>
                                <ul class="widget-list">
                                    <li><a href="#">Online Support</a></li>
                                    <li><a href="#">Shipping Policy</a></li>
                                    <li><a href="#">Return Policy</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">Terms of Service</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-3 col-custom">
                            <div class="single-footer-widget">
                                <h2 class="widget-title">See Information</h2>
                                <div class="widget-body">
                                    <address>No.423, Nawala Road, Rajagiriya.Sri Lanka..<br>Phone:+94 77 877 8731 |+94-11-2078246<br>Email:  info@cec.lk | md@cec.lk</address>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-copyright-area">
                <div class="container custom-area">
                    <div class="row">
                        <div class="col-12 text-center col-custom">
                            <div class="copyright-content">
                                 <p>© 2026 CEC Fresh-Co Mart. All Rights Reserved..</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Footer Area End Here -->
    </div>

    <!-- Modal Area Start Here -->
    <div class="modal fade obrien-modal" id="exampleModalCenter" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="close close-button" data-bs-dismiss="modal" aria-label="Close">
                    <span class="close-icon" aria-hidden="true">x</span>
                </button>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 text-center">
                                <div class="product-image">
                                    <img src="assets/images/product/1.jpg" alt="Product Image">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <div class="modal-product">
                                   
                                        
                                        </form>
                                        <div class="quantity-with_btn">
                                            <div class="quantity">
                                                <div class="cart-plus-minus">
                                                    <input class="cart-plus-minus-box" value="0" type="text">
                                                    <div class="dec qtybutton">-</div>
                                                    <div class="inc qtybutton">+</div>
                                                </div>
                                            </div>
                                            <div class="add-to_cart">
                                                <a class="btn obrien-button primary-btn" href="cart.html">Add to cart</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Area End Here -->

    <!-- Scroll to Top Start -->
    <a class="scroll-to-top" href="#">
        <i class="ion-chevron-up"></i>
    </a>
    <!-- Scroll to Top End -->

    <!-- JS
============================================ -->


    <!-- jQuery JS -->
    <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
    <!-- jQuery Migrate JS -->
    <script src="assets/js/vendor/jquery-migrate-3.3.2.min.js"></script>
    <!-- Modernizer JS -->
    <script src="assets/js/vendor/modernizr-2.8.3.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="assets/js/vendor/bootstrap.bundle.min.js"></script>
    <!-- Slick Slider JS -->
    <script src="assets/js/plugins/slick.min.js"></script>
    <!-- Countdown JS -->
    <script src="assets/js/plugins/jquery.countdown.min.js"></script>
    <!-- Ajax JS -->
    <script src="assets/js/plugins/jquery.ajaxchimp.min.js"></script>
    <!-- Jquery Nice Select JS -->
    <script src="assets/js/plugins/jquery.nice-select.min.js"></script>
    <!-- Jquery Ui JS -->
    <script src="assets/js/plugins/jquery-ui.min.js"></script>
    <!-- jquery magnific popup js -->
    <script src="assets/js/plugins/jquery.magnific-popup.min.js"></script>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>

    <!-- Custom JS -->
    <script src="script.js"></script>

    <script>
    // Profile dropdown toggle
    (function() {
    	const dropdowns = document.querySelectorAll(".profile-dropdown-wrapper");
    	dropdowns.forEach(wrapper => {
    		const logo = wrapper.querySelector(".mt-logo");
    		const menu = wrapper.querySelector(".profile-dropdown-menu");
    		
    		logo.addEventListener("click", function(e) {
    			e.stopPropagation();
    			menu.classList.toggle("show");
    		});
    	});

    	// Close dropdown when clicking outside
    	document.addEventListener("click", function() {
    		document.querySelectorAll(".profile-dropdown-menu").forEach(menu => {
    			menu.classList.remove("show");
    		});
    	});
    })();
    </script>
</body>

</html>