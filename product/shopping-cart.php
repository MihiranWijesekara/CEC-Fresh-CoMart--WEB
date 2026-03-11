<?php
 require '../connection.php';
 session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
      echo json_encode(["success" => false, "message" => "User not logged in"]);
      exit();
    }

    $session_user_id = (int)$_SESSION['user_id'];
    $cart_item_id = (int)$_POST['cart_item_id'];

    $cart_rs = Database::search("SELECT ci.id, ci.cart_id FROM cart_items ci INNER JOIN carts c ON c.id = ci.cart_id WHERE ci.id='$cart_item_id' AND c.user_id='$session_user_id' AND c.status='Active'");

    if (!$cart_rs || $cart_rs->num_rows === 0) {
      echo json_encode(["success" => false, "message" => "Cart item not found"]);
      exit();
    }

    $cart_data = $cart_rs->fetch_assoc();
    $cart_id = (int)$cart_data['cart_id'];

    Database::iud("DELETE FROM cart_items WHERE id='$cart_item_id'");

    $total_rs = Database::search("SELECT COALESCE(SUM(price), 0) AS subtotal, COUNT(id) AS total_items FROM cart_items WHERE cart_id='$cart_id'");
    $total_data = $total_rs->fetch_assoc();
    $subtotal = (float)$total_data['subtotal'];
    $itemCount = (int)$total_data['total_items'];
    $bagCharge = 10;
    $grandTotal = $itemCount > 0 ? $subtotal + $bagCharge : 0;

    echo json_encode([
      "success" => true,
      "subtotal" => $subtotal,
      "grand_total" => $grandTotal,
      "item_count" => $itemCount
    ]);
    exit();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"
    />
    <style>
      body {
        background-color: #f8f9fa;
      }
      .cart-container {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
      }
      .cart-header {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
      }
      .product-table th {
        background-color: #d9d9d9;
        padding: 12px;
        font-weight: 600;
        text-align: center;
      }
      .product-table td {
        padding: 15px;
        vertical-align: middle;
        text-align: center;
      }
      .product-img {
        width: 60px;
        height: auto;
      }
      .product-name {
        text-align: left;
      }
      .qty-control {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }
      .qty-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #ddd;
        background: white;
        cursor: pointer;
        font-weight: bold;
      }
      .qty-input {
        width: 50px;
        text-align: center;
        border: none;
        font-weight: 600;
      }
      .remove-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
      }
      .discount-text {
        color: #28a745;
        font-weight: 600;
      }
      .summary-box {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
      }
      .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
      }
      .summary-row:last-child {
        border-bottom: none;
      }
      .bag-charge {
        color: #28a745;
      }
      .info-icon {
        color: #28a745;
        font-size: 14px;
      }
      .total-box {
        background-color: #28a745;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 18px;
        font-weight: 600;
        margin-top: 15px;
      }
      .cart-name-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-top: 15px;
      }
      .save-btn {
        background-color: #e9ecef;
        border: 1px solid #ddd;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
      }
      .checkout-btn {
        width: 100%;
        background-color: #495057;
        color: white;
        padding: 15px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        margin-top: 15px;
        cursor: pointer;
      }
      .checkout-btn:hover {
        background-color: #343a40;
      }
    </style>
  </head>
  <body>
    <?php 
        $subTotal = 0;
        $itemCount = 0;
        $bagCharge = 10;
        $grandTotal = 0;

        $user_id = $_SESSION['user_id'];
        $cart_rs = Database::search("SELECT * FROM carts WHERE user_id='$user_id' AND status='Active'");
        if ($cart_rs && $cart_rs->num_rows > 0) {
          $cart_data = $cart_rs->fetch_assoc();
          $cart_id = $cart_data['id'];
          $ItemRs = Database::search("SELECT * FROM cart_items WHERE cart_id='$cart_id'");

          $total_rs = Database::search("SELECT COALESCE(SUM(price), 0) AS total_price, COUNT(id) AS total_items FROM cart_items WHERE cart_id='$cart_id'");
          $total_data = $total_rs->fetch_assoc();
          $subTotal = (float)$total_data['total_price'];
          $itemCount = (int)$total_data['total_items'];
          $grandTotal = $subTotal + $bagCharge;
        } else {
          $ItemRs = false;
          $grandTotal = $bagCharge;
        }
    ?>
   

    <div id="navbar" class="navbar-container"></div>
    <div class="container">
      <div class="row">
        <div class="col-lg-8">
          <div class="cart-container">
            <?php if ($ItemRs && $ItemRs->num_rows > 0) { ?>
            <h2 class="cart-header">Cart (<?php echo $itemCount; ?>)</h2>
            <table class="table product-table">
              <thead>
                <tr>
                  <th>Product Name</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Discount</th>
                  <th>Total</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $ItemRs->data_seek(0);
                while ($row = $ItemRs->fetch_assoc()) {
                  $cartItemId = (int)$row['id'];
                    $item_id = $row['item_id'];
                    $item_rs = Database::search("SELECT name, image_path, price FROM items WHERE id='$item_id'");
                    $item_data = $item_rs->fetch_assoc();

                    $img = htmlspecialchars($item_data['image_path']);
                    $name = htmlspecialchars($item_data['name']);
                    $onePrice = htmlspecialchars($item_data['price']);
                    $quantity = htmlspecialchars($row['quantity']);
                    $lineTotalPrice = htmlspecialchars($row['price']);
                ?>
                <tr>
                  <td class="product-name">
                    <div class="d-flex align-items-center">
                      <img
                        src="../<?php echo $img; ?>"
                        alt="Product"
                        class="product-img me-3"
                      />
                      <span><?php echo $name; ?></span>
                    </div>
                  </td>
                  <td>Rs. <?php echo $onePrice; ?></td>
                  <td>
                    <div class="qty-control">
                      <button class="qty-btn" onclick="decreaseQty(<?php echo $cartItemId; ?>, <?php echo (int)$item_id; ?>, <?php echo (int)$cart_id; ?>, <?php echo (int)$user_id; ?>)">-</button>
                      <input
                        type="text"
                        class="qty-input"
                        value=<?php echo $quantity; ?>
                        id="qtyInput-<?php echo $cartItemId; ?>"
                        data-unit-price="<?php echo (float)$item_data['price']; ?>"
                        readonly
                      />
                      <button class="qty-btn" onclick="increaseQty(<?php echo $cartItemId; ?>, <?php echo (int)$item_id; ?>, <?php echo (int)$cart_id; ?>, <?php echo (int)$user_id; ?>)">+</button>
                    </div>
                  </td>
                  <td class="discount-text">Rs. 0.00</td>
                  <td id="itemTotal-<?php echo $cartItemId; ?>">Rs. <?php echo number_format((float)$lineTotalPrice, 2); ?></td>
                  <td>
                    <button class="remove-btn" onclick="removeItem(<?php echo $cartItemId; ?>)">✕</button>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
            <?php } else { ?>
            <h2 class="cart-header">Your cart is empty.</h2>
            <?php } ?>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="cart-container"> 
            <div class="summary-box"> 
              <div class="summary-row">  
                <span>Subtotal</span> 
                <span id="subtotal">Rs. <?php echo number_format($subTotal, 2); ?></span>       
              </div>    
              <div class="summary-row">
                <span>Delivery Charges</span>
                <span>Rs. 0.00</span>
              </div>
              <div class="summary-row bag-charge">
                <span
                  >Polythene Bag Charge
                  <i class="bi bi-info-circle info-icon"></i
                ></span>
                <span>Rs. 10.00</span>
              </div>
              <div class="summary-row">
                <span>Total Discounts</span>
                <span>Rs. 0.00</span>
              </div>
            </div>

            <div class="total-box">
              <span>Total</span>
              <span id="grandTotal">Rs. <?php echo number_format($grandTotal, 2); ?></span>
            </div>

            <a href="../proceed.php" style="text-decoration: none">
              <button class="checkout-btn">Proceed to Checkout</button>
            </a>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const bagCharge = 10;

      function formatMoney(value) {
        return Number(value).toFixed(2);
      }

      function sendQtyUpdate(cartItemId, itemId, cartId, userId, quantity) {
        const f = new FormData();
        f.append("cart_item_id", cartItemId);
        f.append("item_id", itemId);
        f.append("cart_id", cartId);
        f.append("user_id", userId);
        f.append("quantity", quantity);

        var r = new XMLHttpRequest();
        r.onreadystatechange = function () {
          if (r.readyState == 4 && r.status == 200) {
            try {
              const data = JSON.parse(r.responseText);
              if (!data.success) {
                alert(data.message || "Failed to update cart quantity");
                return;
              }

              const input = document.getElementById("qtyInput-" + cartItemId);
              if (input) {
                input.value = data.quantity;
              }

              const itemTotalEl = document.getElementById("itemTotal-" + cartItemId);
              if (itemTotalEl) {
                itemTotalEl.textContent = "Rs. " + formatMoney(data.item_total);
              }

              document.getElementById("subtotal").textContent = "Rs. " + formatMoney(data.subtotal);
              document.getElementById("grandTotal").textContent = "Rs. " + formatMoney(data.grand_total);
            } catch (e) {
              console.log(r.responseText);
            }
          }
        };
        r.open("POST", "updateProductProcess.php", true);
        r.send(f);
      }

      function increaseQty(cartItemId, itemId, cartId, userId) {
        const input = document.getElementById("qtyInput-" + cartItemId);
        const nextQty = (parseInt(input.value, 10) || 0) + 1;
        sendQtyUpdate(cartItemId, itemId, cartId, userId, nextQty);
      }

      function decreaseQty(cartItemId, itemId, cartId, userId) {
        const input = document.getElementById("qtyInput-" + cartItemId);
        const currentQty = parseInt(input.value, 10) || 1;
        if (currentQty <= 1) {
          return;
        }
        sendQtyUpdate(cartItemId, itemId, cartId, userId, currentQty - 1);
      }

      function removeItem(cartItemId) {
        if (!confirm("Are you sure you want to remove Item ID " + cartItemId + "?")) {
          return;
        }
        const f = new FormData();
        f.append("cart_item_id", cartItemId);
        
        var r = new XMLHttpRequest();
        r.onreadystatechange = function () {
          if (r.readyState == 4 && r.status == 200) {
            try {
              const data = JSON.parse(r.responseText);
              if (!data.success) {
                alert(data.message || "Failed to remove item from cart");
                return;
              }
              location.reload();
            } catch (e) {
              console.log(r.responseText);
            }
          }
        };
        r.open("POST", "shopping-cart.php", true);
        r.send(f);
      }

      fetch("nav.php")
        .then((response) => response.text())
        .then((data) => {
          document.getElementById("navbar").innerHTML = data;
          // Dynamically load navBar.js after navbar is injected
          const script = document.createElement("script");
          script.src = "navBar.js";
          document.body.appendChild(script);
        });
    </script>
  </body>
</html>