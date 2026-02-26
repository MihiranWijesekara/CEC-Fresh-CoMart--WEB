<?php
 require '../connection.php';
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Page</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/vendor/bootstrap.min.css" />
    <!-- Bootstrap 5 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <?php session_start(); $isLoggedIn = isset($_SESSION["users"]); ?>

    <style>
      body {
        background-color: #f0f2f5;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
      }

      .navbar-container {
        width: 100%;
        position: fixed;
        top: 0;
        z-index: 1000;
      }

      .main-content {
        padding: 100px 20px 40px;
        display: flex;
        justify-content: center;
      }

      .card-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 25px;
        max-width: 1200px;
      }

      .product-card {
        background-color: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        width: 220px;
        overflow: hidden;
        transition:
          transform 0.3s,
          box-shadow 0.3s;
      }

      .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
      }

      .product-card img {
        width: 80%;
        height: 150px;
        object-fit: cover;
        border-radius: 12px;
        margin: 15px auto 10px auto;
        display: block;
        border: 1px solid #eee;
      }

      .card-body {
        padding: 15px 20px;
        text-align: center;
      }

      .card-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
        margin: 10px 0 5px 0;
      }

      .card-text {
        font-size: 0.95rem;
        color: #555;
        margin: 0 0 15px 0;
      }

      .card-text span {
        display: block;
        margin: 3px 0;
      }

      .btn-add {
        background-color: #ce3333;
        color: #fff;
        border: none;
        padding: 10px 45px;
        border-radius: 25px;
        cursor: pointer;
        font-size: 15px;
        transition: all 0.3s ease;
      }

      .btn-add:hover {
        background-color: #ff6347;
        transform: scale(1.05);
      }

      .label {
        display: inline-block;
        color: #0a0a0a;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: bold;
        margin-bottom: 5px;
      }

      .highlight {
        color: #1a1919;
        padding: 5px 10px;
        border-radius: 8px;
        font-weight: bold;
        display: inline-block;
      }

      /* ================= POPUP STYLES ================= */
      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none; /* Hidden by default */
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }

      .popup {
        width: 90%;
        max-width: 500px;
        background: #e9efe8;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
      }

      .popup-header1 {
        background: #278510;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
      }

      .popup-header1 h3 {
        margin: 0;
        font-size: 1.2rem;
      }

      .close-btn {
        background: #d30909;
        color: white;
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
      }

      .popup-content {
        padding: 30px;
      }

      .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 20px;
      }

      .login-btn1,
      .signup-btn1 {
        padding: 12px 25px;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        transition: 0.3s;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
      }

      .login-btn1 {
        background: #fcfcfc;
        color: #064e06;
      }

      .signup-btn1 {
        background: #45ff2d;
        color: #095230;
      }

      .login-btn1:hover,
      .signup-btn1:hover {
        background: #0c7e08;
        transform: scale(1.05);
        color: #fff;
      }
    </style>
  </head>
  <body>
    <?php 
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

if ($category > 0) {
 
    $ItemRs = Database::search("SELECT * FROM `items` WHERE `status` = 'active' AND `category_id` = $category");
} else {
    $ItemRs = Database::search("SELECT * FROM `items` WHERE `status` = 'active'");
}
?>
    
    <div id="navbar" class="navbar-container"></div>

    <div class="main-content">
      <div class="card-container">
        <?php
        while ($row = $ItemRs->fetch_assoc()) {
          $img = htmlspecialchars($row['image_path']);
          $name = htmlspecialchars($row['name']);
          $unit = htmlspecialchars($row['unit']);
          $price = htmlspecialchars($row['price']);
          $status = htmlspecialchars($row['status']);
        ?>
        <div class="product-card">
          <img src="../<?php echo $img; ?>" alt="<?php echo $name; ?>" />
          <div class="card-body">
            <h5 class="card-title"><span class="highlight"><?php echo $name; ?></span></h5>
            <p class="card-text">
              <span class="label"><?php echo $unit; ?></span>
              <span>Rs. <?php echo $price; ?></span>
              <small>(<?php echo $status; ?>)</small>
            </p>
            <button class="btn-add" onclick="checkLogin()">ADD</button>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>

    <div class="overlay" id="popupOverlay">
      
      <div class="popup">
        <div class="popup-header1">
          <h3 class="text-white">Guest Login</h3>
          <button class="close-btn" onclick="closePopup()">X</button>
        </div>

        <div class="popup-content">
          <p>
            Thank you for choosing to shop at Fresh-CoMart Online!<br />
            Please Login or Sign Up to proceed.
          </p>

          <div class="button-group">
            <a href="../login/sign.php" class="login-btn1">Login</a>
            <a href="../login/register.php" class="signup-btn1">Sign Up</a>
          </div>
        </div>
      </div>
    </div>

    <script>
       var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

  function checkLogin() {
    if (isLoggedIn) {
      alert("Product added to cart!");
      // Here you can redirect to cart or add item
    } else {
      openPopup();
    }
  }

  function openPopup() {
    document.getElementById("popupOverlay").style.display = "flex";
  }

  function closePopup() {
    document.getElementById("popupOverlay").style.display = "none";
  }
      // Navbar Loading logic
      fetch("nav.php")
        .then((response) => response.text())
        .then((data) => {
          document.getElementById("navbar").innerHTML = data;
          const script = document.createElement("script");
          script.src = "navBar.js";
          document.body.appendChild(script);

          // Add category click listeners
          setTimeout(() => {
          const categoryLinks = document.querySelectorAll('.dropdown-content a , .mobile-category-list a');

          categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
            e.preventDefault();

            const id = this.getAttribute("data-id");
            filterCategory(id);
          });
        });
      }, 500);
      });
    </script>
  </body>
</html>
