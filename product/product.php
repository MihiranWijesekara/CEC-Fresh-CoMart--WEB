<?php
require '../connection.php';
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fresh Products - CEC Fresh-Co Mart</title>
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      :root {
        --primary-green: #27b62e;
        --primary-green-hover: #1e8e24;
        --bg-gray: #f4f7f5;
        --text-dark: #1f2937;
        --text-muted: #6b7280;
        --card-bg: #ffffff;
        --badge-in-stock-bg: #e8f5e9;
        --badge-in-stock-color: #2e7d32;
        --badge-out-of-stock-bg: #ffebee;
        --badge-out-of-stock-color: #c62828;
      }

      body {
        background-color: var(--bg-gray);
        font-family: 'Outfit', sans-serif;
        color: var(--text-dark);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      .navbar-container {
        width: 100%;
        position: fixed;
        top: 0;
        z-index: 1000;
      }

      .main-content {
        padding: 120px 20px 60px;
        display: flex;
        justify-content: center;
        flex: 1;
      }

      .card-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 30px;
        width: 100%;
        max-width: 1200px;
      }

      /* Premium Product Card */
      .product-card {
        background-color: var(--card-bg);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
        position: relative;
        border: 1px solid rgba(0, 0, 0, 0.02);
      }

      .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(39, 182, 46, 0.12);
      }

      /* Product Image Container */
      .product-img-wrapper {
        width: 100%;
        height: 180px;
        overflow: hidden;
        background-color: #fcfcfc;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 15px;
      }

      .product-card img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        transition: transform 0.5s ease;
      }

      .product-card:hover img {
        transform: scale(1.08);
      }

      .card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex: 1;
        text-align: center;
      }

      .card-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
      }

      .card-meta {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 20px;
        align-items: center;
      }

      /* Pill Badges */
      .badge-unit {
        background-color: #f3f4f6;
        color: #4b5563;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        display: inline-block;
      }

      .badge-status {
        font-size: 0.8rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 4px;
      }

      .badge-status.in-stock {
        background-color: var(--badge-in-stock-bg);
        color: var(--badge-in-stock-color);
      }

      .badge-status.out-of-stock {
        background-color: var(--badge-out-of-stock-bg);
        color: var(--badge-out-of-stock-color);
      }

      .price-tag {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--primary-green-hover);
        margin-top: auto;
        margin-bottom: 15px;
      }

      /* Premium ADD Button */
      .btn-add {
        background-color: var(--primary-green);
        color: #ffffff;
        border: none;
        padding: 10px 24px;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 700;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(39, 182, 46, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
      }

      .btn-add:hover {
        background-color: var(--primary-green-hover);
        box-shadow: 0 6px 20px rgba(39, 182, 46, 0.4);
        transform: translateY(-1px);
        color: #ffffff;
      }

      .btn-add:active {
        transform: translateY(0);
      }

      .btn-add:disabled {
        background-color: #e5e7eb !important;
        color: #9ca3af !important;
        box-shadow: none !important;
        cursor: not-allowed !important;
        transform: none !important;
        opacity: 0.8;
      }

      /* Modals (Glassmorphism backdrop & clean cards) */
      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(17, 24, 39, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }

      .popup {
        width: 90%;
        max-width: 420px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.8);
        animation: scaleIn 0.3s ease;
      }

      @keyframes scaleIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
      }

      .popup-header1 {
        background: var(--primary-green);
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
      }

      .popup-header1 h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
      }

      .close-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
      }

      .close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
      }

      .popup-content {
        padding: 30px;
        color: var(--text-dark);
      }

      .popup-content p {
        line-height: 1.6;
        font-size: 1rem;
        color: var(--text-muted);
        margin-bottom: 25px;
      }

      .button-group {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 15px;
      }

      .popup-btn-primary,
      .popup-btn-secondary {
        padding: 12px 24px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
      }

      .popup-btn-primary {
        background: var(--primary-green);
        color: white;
        box-shadow: 0 4px 10px rgba(39, 182, 46, 0.2);
      }

      .popup-btn-primary:hover {
        background: var(--primary-green-hover);
        box-shadow: 0 6px 15px rgba(39, 182, 46, 0.35);
        transform: translateY(-1px);
        color: white;
      }

      .popup-btn-secondary {
        background: #f3f4f6;
        color: #4b5563;
      }

      .popup-btn-secondary:hover {
        background: #e5e7eb;
        transform: translateY(-1px);
      }

      /* Qty adjustment buttons */
      .qty-adjust-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid #e5e7eb;
        background: white;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
      }

      .qty-adjust-btn:hover {
        background: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
      }

      footer {
        background-color: #212529;
        color: white;
        text-align: center;
        padding: 20px 0;
        font-size: 0.9rem;
      }
    </style>
  </head>
  <body>
    <?php 
        $category = isset($_GET['category']) ? intval($_GET['category']) : 0;

        if ($category > 0) {
            $ItemRs = Database::search("SELECT * FROM `items` WHERE `category_id` = $category");
        } else {
            $ItemRs = Database::search("SELECT * FROM `items`");
        }
    ?>
    
    <!-- Navbar -->
    <div id="navbar" class="navbar-container"></div>

    <!-- Product Grid -->
    <div class="main-content">
      <div class="card-container">
        <?php
        if ($ItemRs && $ItemRs->num_rows > 0) {
          while ($row = $ItemRs->fetch_assoc()) {
            $img = htmlspecialchars($row['image_path']);
            $name = htmlspecialchars($row['name']);
            $unit = htmlspecialchars($row['unit']);
            $price = htmlspecialchars($row['price']);
            $status = htmlspecialchars($row['status']);
            $id = htmlspecialchars($row['id']);
            $isActive = ($status === 'active');
            $stock_qty = isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 0;
            $isInStock = ($isActive && $stock_qty > 0);
          ?>
          <div class="product-card">
            <div class="product-img-wrapper">
              <img src="../<?php echo $img; ?>" alt="<?php echo $name; ?>" />
            </div>
            <div class="card-body">
              <h5 class="card-title"><?php echo $name; ?></h5>
              
              <div class="card-meta">
                <span class="badge-unit"><?php echo $unit; ?></span>
                <?php if ($isInStock) { ?>
                  <span class="badge-status in-stock"><i class="bi bi-check2-circle me-1"></i>In Stock</span>
                <?php } else { ?>
                  <span class="badge-status out-of-stock"><i class="bi bi-x-circle me-1"></i>Out of Stock</span>
                <?php } ?>
              </div>
              
              <div class="price-tag">Rs. <?php echo number_format((float)$price, 2); ?></div>
              
              <button class="btn-add" onclick="checkLogin('<?php echo $id; ?>', '<?php echo $price; ?>')" <?php if (!$isInStock) echo 'disabled'; ?>>
                <i class="bi bi-cart-plus-fill"></i> Add to Cart
              </button>
            </div>
          </div>
          <?php }
        } else { ?>
          <div class="text-center py-5 w-100 grid-column-span-all">
            <p class="text-muted">No products found in this category.</p>
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Guest Login Modal -->
    <div class="overlay" id="popupOverlay">
      <div class="popup">
        <div class="popup-header1">
          <h3>Guest Login</h3>
          <button class="close-btn" onclick="closePopup()">×</button>
        </div>
        <div class="popup-content">
          <p>
            Thank you for choosing to shop at Fresh-Co Mart Online!<br />
            Please Login or Sign Up to proceed.
          </p>
          <div class="button-group">
            <a href="../login/sign.php" class="popup-btn-primary">Login</a>
            <a href="../login/register.php" class="popup-btn-secondary">Sign Up</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Add More Items Modal -->
    <div class="overlay" id="addMoreModal" style="display:none;">
      <div class="popup" style="max-width:350px;">
        <div class="popup-header1">
          <h3>Add More?</h3>
          <button class="close-btn" onclick="closeAddMoreModal()">×</button>
        </div>
        <div class="popup-content text-center">
          <p class="mb-3 text-dark fw-semibold">How many items would you like to add?</p>
          <div class="d-flex justify-content-center align-items-center gap-3 my-4">
            <button class="qty-adjust-btn" onclick="changeQty(-1)">-</button>
            <span id="itemQty" class="fs-4 fw-bold" style="min-width:30px; display:inline-block;">1</span>
            <button class="qty-adjust-btn" onclick="changeQty(1)">+</button>
          </div>
          <div class="button-group">
            <button class="popup-btn-primary" onclick="confirmAddToCart()">Add to Cart</button>
            <button class="popup-btn-secondary" onclick="closeAddMoreModal()">Cancel</button>
          </div>
        </div>
      </div>
    </div>
     
    <!-- Footer -->
    <footer>© 2026 CEC Fresh-Co Mart. All Rights Reserved.</footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../script.js"></script>

    <script>
      var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
      
      // Share isLoggedIn state globally with script.js
      window.isLoggedIn = isLoggedIn;

      function checkLogin(productId, price) {
        if (isLoggedIn) {
          // Store details in shared global scope for script.js to access
          window.selectedProduct = productId;
          window.selectedPrice = parseFloat(price);
          window.itemQty = 1;
          
          document.getElementById('itemQty').innerText = window.itemQty;
          document.getElementById('addMoreModal').style.display = 'flex';
        } else {
          openPopup();
        }
      }

      function closeAddMoreModal() {
        document.getElementById('addMoreModal').style.display = 'none';
      }

      function openPopup() {
        document.getElementById("popupOverlay").style.display = "flex";
      }

      function closePopup() {
        document.getElementById("popupOverlay").style.display = "none";
      }

      function changeQty(delta) {
        if (typeof window.itemQty === 'undefined') {
          window.itemQty = 1;
        }
        window.itemQty += delta;
        if (window.itemQty < 1) window.itemQty = 1;
        document.getElementById('itemQty').innerText = window.itemQty;
      }
      
      // Load user navigation
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

      // Filter by category helper function
      function filterCategory(catId) {
        window.location.href = "product.php?category=" + catId;
      }
    </script>
  </body>
</html>
