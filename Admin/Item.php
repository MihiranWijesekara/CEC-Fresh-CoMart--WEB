<?php
require 'adminAuth.php';
require '../connection.php';

// Category map
$categories = [
    1 => "Vegetables",
    2 => "Fruits",
    3 => "Snacks",
    4 => "Biscuits",
    5 => "Coffee",
    6 => "Eggs",
    7 => "Water",
    8 => "Tea",
    9 => "Cheese",
    10 => "Yoghurts & Curd",
    11 => "Desserts",
    12 => "Beverages",
    13 => "Bakery",
    14 => "Meat & Seafood",
    15 => "Pantry Staples",
    16 => "Dairy Staples",
    17 => "Frozen Foods",
    18 => "Household & Cleaning",
    19 => "Personal Care & Baby Care"
];

// Fetch items
$items_rs = Database::search("SELECT * FROM `items` ORDER BY `created_at` DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
      body {
        background: #f4f6fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .main-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        padding: 32px 28px 24px 28px;
        margin: 40px auto 0 auto;
        max-width: 1200px;
      }
      .table thead th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 1.05rem;
        border-bottom: 2px solid #e3e6ed;
      }
      .table tbody tr {
        transition: background 0.2s;
      }
      .table-hover tbody tr:hover {
        background: #f1f7ff;
      }
      .table td, .table th {
        vertical-align: middle;
      }
      .table td img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e3e6ed;
      }
      .status-badge {
          cursor: pointer;
      }
      @media (max-width: 900px) {
        .main-card { padding: 12px 2px; }
        .table th, .table td { font-size: 0.95rem; }
      }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0" style="font-size:1.5rem;letter-spacing:1px;">Items List</h2>
        <a href="ItemAdd.php" class="btn btn-success px-4 py-2 rounded-pill shadow-sm">+ Add Item</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle text-center">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Image</th>
              <th scope="col">Unit</th>
              <th scope="col">Price</th>
              <th scope="col">Category</th>
              <th scope="col">Stock</th>
              <th scope="col">Created Date</th>
              <th scope="col">Status</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($items_rs && $items_rs->num_rows > 0) {
                $counter = 1;
                while ($row = $items_rs->fetch_assoc()) {
                    $id = $row['id'];
                    $name = htmlspecialchars($row['name']);
                    $img = htmlspecialchars($row['image_path']);
                    $unit = htmlspecialchars($row['unit']);
                    $price = htmlspecialchars($row['price']);
                    $cat_id = (int)$row['category_id'];
                    $category_name = isset($categories[$cat_id]) ? $categories[$cat_id] : "Other";
                    $created_at = date("Y-m-d", strtotime($row['created_at']));
                    $status = $row['status'];
                    $stock_qty = (int)$row['stock_quantity'];
                    $badgeClass = ($status === 'active') ? 'bg-success' : 'bg-secondary';
                    ?>
                    <tr id="row-<?php echo $id; ?>">
                      <th scope="row"><?php echo $counter++; ?></th>
                      <td class="fw-semibold"><?php echo $name; ?></td>
                      <td>
                          <?php 
                          $absoluteImg = "../" . $img;
                          // If it looks like a remote url in the mocked DB, let's keep it, else prefix with parent path
                          if (strpos($img, 'http') === 0) {
                              $absoluteImg = $img;
                          }
                          ?>
                          <img src="<?php echo $absoluteImg; ?>" alt="<?php echo $name; ?>" onerror="this.src='../assets/images/logo/logo-freshco.png'">
                      </td>
                      <td><?php echo $unit; ?></td>
                      <td class="fw-bold">Rs. <?php echo number_format((float)$price, 2); ?></td>
                      <td><?php echo $category_name; ?></td>
                      <td>
                          <?php if ($stock_qty === 0) { ?>
                              <span class="badge bg-danger" id="stock-badge-<?php echo $id; ?>">Out of Stock (0)</span>
                          <?php } else if ($stock_qty <= 5) { ?>
                              <span class="badge bg-warning text-dark" id="stock-badge-<?php echo $id; ?>">Low Stock (<?php echo $stock_qty; ?>)</span>
                          <?php } else { ?>
                              <span class="badge bg-light text-dark" id="stock-badge-<?php echo $id; ?>"><?php echo $stock_qty; ?></span>
                          <?php } ?>
                      </td>
                      <td class="text-muted"><?php echo $created_at; ?></td>
                      <td>
                          <span class="badge <?php echo $badgeClass; ?> status-badge" id="badge-<?php echo $id; ?>" onclick="toggleStatus(<?php echo $id; ?>)">
                              <?php echo ucfirst($status); ?>
                          </span>
                      </td>
                      <td>
                          <div class="d-flex justify-content-center gap-2">
                              <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="toggleStatus(<?php echo $id; ?>)">
                                  Toggle Status
                              </button>
                              <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openRestockModal(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', <?php echo $stock_qty; ?>)">
                                  Restock
                              </button>
                          </div>
                      </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">No items found in database.</td>
                </tr>
                <?php
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Restock Modal -->
    <div class="modal fade" id="restockModal" tabindex="-1" aria-labelledby="restockModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
          <div class="modal-header border-0 bg-light" style="border-top-left-radius: 15px; border-top-right-radius: 15px; padding: 20px;">
            <h5 class="modal-title fw-bold" id="restockModalLabel" style="color: #1e3a8a;"><i class="bi bi-box-seam me-2"></i>Restock Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 25px;">
            <p class="mb-4">Item to update: <strong id="restockItemName" style="color: #3b82f6;"></strong></p>
            <form id="restockForm" onsubmit="event.preventDefault(); submitRestock();">
              <input type="hidden" id="restockItemId">
              <div class="mb-3">
                <label for="newQuantity" class="form-label fw-bold">Stock Quantity</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-hash"></i></span>
                  <input type="number" class="form-control form-control-lg" id="newQuantity" min="0" required placeholder="Enter new quantity">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer border-0 pb-4 pe-4">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success rounded-pill px-4" onclick="submitRestock()">Save Changes</button>
          </div>
        </div>
      </div>
    </div>
    
    <script>
        function toggleStatus(itemId) {
            const badge = document.getElementById("badge-" + itemId);
            if (!badge) return;
            
            const formData = new FormData();
            formData.append("item_id", itemId);
            
            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = xhr.responseText.trim();
                    if (response === "active" || response === "inactive") {
                        badge.textContent = response.charAt(0).toUpperCase() + response.slice(1);
                        if (response === "active") {
                            badge.className = "badge bg-success status-badge";
                        } else {
                            badge.className = "badge bg-secondary status-badge";
                        }
                    } else {
                        alert("Error toggling product status: " + response);
                    }
                }
            };
            xhr.open("POST", "toggleProductStatus.php", true);
            xhr.send(formData);
        }

        let restockModalInstance = null;

        function openRestockModal(itemId, itemName, currentStock) {
            document.getElementById("restockItemId").value = itemId;
            document.getElementById("restockItemName").textContent = itemName;
            document.getElementById("newQuantity").value = currentStock;
            
            const modalEl = document.getElementById("restockModal");
            restockModalInstance = new bootstrap.Modal(modalEl);
            restockModalInstance.show();
        }

        function submitRestock() {
            const itemId = document.getElementById("restockItemId").value;
            const newQty = document.getElementById("newQuantity").value;
            
            if (newQty === "" || parseInt(newQty) < 0) {
                alert("Please enter a valid stock quantity.");
                return;
            }

            const formData = new FormData();
            formData.append("item_id", itemId);
            formData.append("stock_quantity", newQty);

            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = xhr.responseText.trim();
                    if (response === "success") {
                        // Update stock column in table dynamically without full page reload
                        const stockBadge = document.getElementById("stock-badge-" + itemId);
                        if (stockBadge) {
                            const qtyVal = parseInt(newQty);
                            stockBadge.textContent = qtyVal;
                            if (qtyVal === 0) {
                                stockBadge.className = "badge bg-danger";
                                stockBadge.textContent = "Out of Stock (0)";
                            } else if (qtyVal <= 5) {
                                stockBadge.className = "badge bg-warning text-dark";
                                stockBadge.textContent = "Low Stock (" + qtyVal + ")";
                            } else {
                                stockBadge.className = "badge bg-light text-dark";
                            }
                            
                            // Also update the onclick parameter of the Restock button in that row to match the new qty
                            const row = document.getElementById("row-" + itemId);
                            if (row) {
                                const restockBtn = row.querySelector("button.btn-primary");
                                if (restockBtn) {
                                    restockBtn.setAttribute("onclick", "openRestockModal(" + itemId + ", '" + addslashes(document.getElementById("restockItemName").textContent) + "', " + qtyVal + ")");
                                }
                            }
                        }
                        // Close modal
                        if (restockModalInstance) {
                            restockModalInstance.hide();
                        }
                    } else {
                        alert("Error updating stock quantity: " + response);
                    }
                }
            };
            xhr.open("POST", "updateStock.php", true);
            xhr.send(formData);
        }

        function addslashes(str) {
            return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
        }
    </script>
</body>
</html>