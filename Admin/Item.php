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
                      <td class="text-muted"><?php echo $created_at; ?></td>
                      <td>
                          <span class="badge <?php echo $badgeClass; ?> status-badge" id="badge-<?php echo $id; ?>" onclick="toggleStatus(<?php echo $id; ?>)">
                              <?php echo ucfirst($status); ?>
                          </span>
                      </td>
                      <td>
                          <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="toggleStatus(<?php echo $id; ?>)">
                              Toggle Status
                          </button>
                      </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">No items found in database.</td>
                </tr>
                <?php
            }
            ?>
          </tbody>
        </table>
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
    </script>
</body>
</html>