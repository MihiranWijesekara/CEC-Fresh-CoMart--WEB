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
function isBulkItem($catId, $unitStr) {
    $bulkCategories = [1, 2, 14, 15, 16];
    if (!in_array((int)$catId, $bulkCategories)) {
        return false;
    }
    $unitLower = strtolower(trim($unitStr));
    return preg_match('/(kg|g|l|ml)$/i', $unitLower);
}

function getUnitMetric($unitStr) {
    if (preg_match('/(kg|g|l|ml)$/i', strtolower(trim($unitStr)), $matches)) {
        return $matches[1];
    }
    return 'units';
}

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
                    $stock_qty = (float)$row['stock_quantity'];
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
                          <?php
                          $isBulk = isBulkItem($cat_id, $unit);
                          $metric = getUnitMetric($unit);
                          $stock_qty_formatted = ($stock_qty == (int)$stock_qty) ? (int)$stock_qty : number_format($stock_qty, 2);
                          
                          if ($stock_qty <= 0) {
                              ?>
                              <span class="badge bg-danger" id="stock-badge-<?php echo $id; ?>">Out of Stock (0<?php echo $isBulk ? ' ' . $metric : ''; ?>)</span>
                          <?php } else if ($stock_qty <= 5) { ?>
                              <span class="badge bg-warning text-dark" id="stock-badge-<?php echo $id; ?>">Low Stock (<?php echo $stock_qty_formatted . ($isBulk ? ' ' . $metric : ''); ?>)</span>
                          <?php } else { ?>
                              <span class="badge bg-light text-dark" id="stock-badge-<?php echo $id; ?>"><?php echo $stock_qty_formatted . ($isBulk ? ' ' . $metric : ''); ?></span>
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
                              <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openRestockModal(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', <?php echo $stock_qty; ?>, '<?php echo $unit; ?>', <?php echo $isBulk ? 'true' : 'false'; ?>, '<?php echo $metric; ?>')">
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
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:18px; border:none; box-shadow: 0 12px 36px rgba(0,0,0,0.18);">
          <div class="modal-header border-0 bg-light" style="border-top-left-radius: 18px; border-top-right-radius: 18px; padding: 22px 28px;">
            <h5 class="modal-title fw-bold" id="restockModalLabel" style="color: #27b62e; font-size:1.4rem;"><i class="bi bi-box-seam-fill me-2"></i>Restock & Adjust Inventory</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="padding: 30px 35px;">
            <div class="row mb-4 align-items-center">
              <div class="col-md-8">
                <span class="text-muted d-block uppercase small fw-semibold" style="letter-spacing: 0.5px;">Product Inventory Unit</span>
                <h4 class="mb-0 fw-bold" id="restockItemName" style="color: #1e293b;"></h4>
              </div>
              <div class="col-md-4 text-md-end">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold" style="font-size: 0.95rem;">
                  Current: <span id="currentStockDisplay">0</span>
                </span>
              </div>
            </div>

            <form id="restockForm" onsubmit="event.preventDefault(); submitRestock();">
              <input type="hidden" id="restockItemId">
              
              <!-- Transaction Type Button Group -->
              <div class="mb-4">
                <label class="form-label fw-bold text-secondary mb-2" style="font-size:0.9rem;">Transaction Type</label>
                <div class="btn-group w-100" role="group" aria-label="Transaction Type Selection">
                  <input type="radio" class="btn-check" name="txType" id="txTypeIn" value="in" autocomplete="off" checked>
                  <label class="btn btn-outline-success py-2 fw-semibold" for="txTypeIn"><i class="bi bi-plus-circle me-1"></i>Add Stock (+)</label>
                  
                  <input type="radio" class="btn-check" name="txType" id="txTypeOut" value="out" autocomplete="off">
                  <label class="btn btn-outline-danger py-2 fw-semibold" for="txTypeOut"><i class="bi bi-dash-circle me-1"></i>Remove Stock (-)</label>
                  
                  <input type="radio" class="btn-check" name="txType" id="txTypeAdj" value="adjustment" autocomplete="off">
                  <label class="btn btn-outline-primary py-2 fw-semibold" for="txTypeAdj"><i class="bi bi-sliders me-1"></i>Manual Set (=)</label>
                </div>
              </div>

              <div class="row g-3 mb-4">
                <!-- Restock Qty Input -->
                <div class="col-md-6">
                  <label for="restockQuantity" class="form-label fw-bold text-secondary" style="font-size:0.9rem;">Adjustment Quantity</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                    <input type="number" step="any" class="form-control" id="restockQuantity" min="0.001" value="10" required placeholder="Qty">
                    <select class="form-select fs-6 fw-semibold" id="restockUnit" style="max-width: 100px; display: none;"></select>
                    <span class="input-group-text" id="restockUnitStatic" style="font-size:0.95rem; font-weight:600;">units</span>
                  </div>
                </div>

                <!-- Restock Reason Select -->
                <div class="col-md-6">
                  <label for="restockReason" class="form-label fw-bold text-secondary" style="font-size:0.9rem;">Adjustment Reason</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                    <select class="form-select" id="restockReason" required></select>
                  </div>
                </div>
              </div>

              <!-- Real-time math preview -->
              <div class="alert alert-info border-0 p-3 mb-4 d-flex align-items-center justify-content-between" style="border-radius:12px; background-color: #f0fdf4; border: 1px solid #dcfce7 !important;">
                <div class="d-flex align-items-center gap-2 text-dark font-monospace" style="font-size: 1.05rem;">
                  <span>Current: <strong id="currentStockVal">0</strong></span>
                  <span id="mathOperator" class="fw-bold text-primary">+</span>
                  <span>Change: <strong id="changeStockVal">10</strong></span>
                </div>
                <div class="text-end">
                  <span class="text-secondary small fw-semibold d-block">Calculated Balance</span>
                  <span class="fs-4 fw-bold" id="newStockVal" style="color: #27b62e;">10</span>
                </div>
              </div>
            </form>

            <!-- Audit Trail section -->
            <div class="mt-4 pt-2">
              <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Stock Adjustments (Audit Log)</h6>
              <div class="table-responsive" style="max-height: 180px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size: 0.85rem;">
                  <thead class="table-light">
                    <tr>
                      <th scope="col" style="padding: 10px;">Date & Time</th>
                      <th scope="col" style="padding: 10px;">Action Type</th>
                      <th scope="col" style="padding: 10px;">Change Qty</th>
                      <th scope="col" style="padding: 10px;">New Balance</th>
                      <th scope="col" style="padding: 10px; text-align: left;">Audit Note / Reason</th>
                    </tr>
                  </thead>
                  <tbody id="historyLogBody">
                    <tr>
                      <td colspan="5" class="text-center py-3 text-muted">Loading logs...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pb-4 pe-4 pt-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success rounded-pill px-4" onclick="submitRestock()" style="background-color: #27b62e; border-color: #27b62e;">Save Changes</button>
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
        let currentStockGlobal = 0;
        let isBulkGlobal = false;
        let metricGlobal = 'units';
        let dbUnitGlobal = '1 units';

        function parseUnitToGrams(unitStr) {
            unitStr = unitStr.toLowerCase().trim();
            const match = unitStr.match(/^([\d\.]+)\s*(kg|g|l|ml)?$/);
            if (!match) {
                return { val: 1, metric: 'units', baseVal: 1 };
            }
            const val = parseFloat(match[1]);
            const metric = match[2];
            let baseVal = val;
            if (metric === 'kg') {
                baseVal = val * 1000;
            } else if (metric === 'g') {
                baseVal = val;
            } else if (metric === 'l') {
                baseVal = val * 1000;
            } else if (metric === 'ml') {
                baseVal = val;
            }
            return { val: val, metric: metric, baseVal: baseVal };
        }

        function formatQtyWithUnit(qtyVal, unitStr) {
            qtyVal = parseFloat(qtyVal);
            const parsed = parseUnitToGrams(unitStr);
            if (parsed.metric === 'units' || !isBulkGlobal) {
                return (qtyVal % 1 === 0 ? parseInt(qtyVal) : qtyVal.toFixed(2)) + ' units';
            }
            const totalBase = qtyVal * parsed.baseVal;
            if (parsed.metric === 'kg' || parsed.metric === 'g') {
                if (totalBase >= 1000) {
                    return (totalBase / 1000).toFixed((totalBase / 1000) % 1 === 0 ? 0 : 2) + ' kg';
                } else {
                    return totalBase.toFixed(totalBase % 1 === 0 ? 0 : 1) + ' g';
                }
            } else if (parsed.metric === 'l' || parsed.metric === 'ml') {
                if (totalBase >= 1000) {
                    return (totalBase / 1000).toFixed((totalBase / 1000) % 1 === 0 ? 0 : 2) + ' L';
                } else {
                    return totalBase.toFixed(totalBase % 1 === 0 ? 0 : 1) + ' ml';
                }
            }
            return qtyVal + ' units';
        }

        function openRestockModal(itemId, itemName, currentStock, unit, isBulk, metric) {
            document.getElementById("restockItemId").value = itemId;
            document.getElementById("restockItemName").textContent = itemName + " (" + unit + ")";
            currentStockGlobal = parseFloat(currentStock);
            isBulkGlobal = isBulk === true || isBulk === 'true';
            metricGlobal = metric || 'units';
            dbUnitGlobal = unit;

            document.getElementById("currentStockDisplay").textContent = formatQtyWithUnit(currentStockGlobal, dbUnitGlobal);
            
            const unitSelect = document.getElementById("restockUnit");
            const unitStatic = document.getElementById("restockUnitStatic");
            
            if (isBulkGlobal) {
                unitStatic.style.display = 'none';
                unitSelect.style.display = 'inline-block';
                unitSelect.innerHTML = '';
                
                if (metricGlobal === 'kg' || metricGlobal === 'g') {
                    const optKg = document.createElement("option");
                    optKg.value = 'kg';
                    optKg.textContent = 'kg';
                    const optG = document.createElement("option");
                    optG.value = 'g';
                    optG.textContent = 'g';
                    unitSelect.appendChild(optKg);
                    unitSelect.appendChild(optG);
                } else if (metricGlobal === 'l' || metricGlobal === 'ml') {
                    const optL = document.createElement("option");
                    optL.value = 'l';
                    optL.textContent = 'L';
                    const optMl = document.createElement("option");
                    optMl.value = 'ml';
                    optMl.textContent = 'ml';
                    unitSelect.appendChild(optL);
                    unitSelect.appendChild(optMl);
                }
            } else {
                unitSelect.style.display = 'none';
                unitStatic.style.display = 'inline-block';
                unitStatic.textContent = 'units';
            }

            // Reset form fields
            document.getElementById("txTypeIn").checked = true;
            document.getElementById("restockQuantity").value = isBulkGlobal ? "10" : "10";
            
            // Load reasons list & calculation preview
            updateReasonsList();
            updateCalculationPreview();
            
            // Load history
            loadStockHistory(itemId);
            
            const modalEl = document.getElementById("restockModal");
            restockModalInstance = new bootstrap.Modal(modalEl);
            restockModalInstance.show();
        }

        const reasonsMap = {
            in: [
                { value: "Supplier Delivery", text: "Supplier Delivery" },
                { value: "Customer Return", text: "Customer Return" },
                { value: "Inventory Correction", text: "Inventory Correction" }
            ],
            out: [
                { value: "Damaged Items", text: "Damaged Items" },
                { value: "Expired Stock", text: "Expired Stock" },
                { value: "Theft/Loss", text: "Theft / Loss" },
                { value: "Inventory Correction", text: "Inventory Correction" }
            ],
            adjustment: [
                { value: "Annual Stock Audit", text: "Annual Stock Audit" },
                { value: "Manager Correction", text: "Manager Correction" },
                { value: "Opening Balance", text: "Opening Balance" }
            ]
        };

        function updateReasonsList() {
            const txType = document.querySelector('input[name="txType"]:checked').value;
            const reasonSelect = document.getElementById("restockReason");
            reasonSelect.innerHTML = "";
            
            const options = reasonsMap[txType] || [];
            options.forEach(opt => {
                const option = document.createElement("option");
                option.value = opt.value;
                option.textContent = opt.text;
                reasonSelect.appendChild(option);
            });
        }

        function updateCalculationPreview() {
            const txType = document.querySelector('input[name="txType"]:checked').value;
            const qtyInput = document.getElementById("restockQuantity");
            let inputQty = parseFloat(qtyInput.value);
            
            if (isNaN(inputQty) || inputQty < 0) {
                inputQty = 0;
            }
            
            let changeInDbUnits = inputQty;
            if (isBulkGlobal) {
                const uiUnit = document.getElementById("restockUnit").value;
                const uiMultiplier = (uiUnit === 'kg' || uiUnit === 'l') ? 1000 : 1;
                const inputBaseVal = inputQty * uiMultiplier;
                const dbUnitBaseVal = parseUnitToGrams(dbUnitGlobal).baseVal;
                changeInDbUnits = inputBaseVal / dbUnitBaseVal;
            }
            
            let operator = "+";
            let newStock = currentStockGlobal;
            
            if (txType === "in") {
                operator = "+";
                newStock = currentStockGlobal + changeInDbUnits;
            } else if (txType === "out") {
                operator = "-";
                newStock = currentStockGlobal - changeInDbUnits;
                if (newStock < 0) {
                    newStock = 0;
                }
            } else if (txType === "adjustment") {
                operator = "➜";
                newStock = changeInDbUnits;
            }
            
            document.getElementById("currentStockVal").textContent = formatQtyWithUnit(currentStockGlobal, dbUnitGlobal);
            document.getElementById("mathOperator").textContent = operator;
            
            let changeDisplay = inputQty + " " + (isBulkGlobal ? document.getElementById("restockUnit").value : "units");
            document.getElementById("changeStockVal").textContent = changeDisplay;
            
            const newStockEl = document.getElementById("newStockVal");
            newStockEl.textContent = formatQtyWithUnit(newStock, dbUnitGlobal);
            
            if (newStock === 0) {
                newStockEl.className = "fs-4 fw-bold text-danger";
            } else if (newStock <= 5) {
                newStockEl.className = "fs-4 fw-bold text-warning";
            } else {
                newStockEl.className = "fs-4 fw-bold text-success";
            }
        }

        // Add event listeners to input elements inside modal
        document.addEventListener('DOMContentLoaded', function() {
            // Radio change listeners
            document.querySelectorAll('input[name="txType"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateReasonsList();
                    updateCalculationPreview();
                });
            });
            // Input change listener
            const qtyInput = document.getElementById("restockQuantity");
            if (qtyInput) {
                qtyInput.addEventListener('input', updateCalculationPreview);
                qtyInput.addEventListener('change', updateCalculationPreview);
            }
            const unitSelect = document.getElementById("restockUnit");
            if (unitSelect) {
                unitSelect.addEventListener('change', updateCalculationPreview);
            }
        });

        function loadStockHistory(itemId) {
            const logBody = document.getElementById("historyLogBody");
            logBody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Loading logs...</td></tr>';
            
            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.status === "success") {
                            const list = data.history || [];
                            if (list.length === 0) {
                                logBody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No stock history recorded yet.</td></tr>';
                                return;
                            }
                            
                            logBody.innerHTML = "";
                            list.forEach(tx => {
                                const tr = document.createElement("tr");
                                
                                let badgeClass = "bg-light text-dark";
                                let prefix = "";
                                if (tx.transaction_type === "in") {
                                    badgeClass = "bg-success text-success bg-opacity-10 border border-success border-opacity-10";
                                    prefix = "+";
                                } else if (tx.transaction_type === "out") {
                                    badgeClass = "bg-danger text-danger bg-opacity-10 border border-danger border-opacity-10";
                                    prefix = "-";
                                } else if (tx.transaction_type === "adjustment") {
                                    badgeClass = "bg-primary text-primary bg-opacity-10 border border-primary border-opacity-10";
                                    prefix = "➜";
                                }
                                
                                tr.innerHTML = `
                                    <td class="text-muted" style="padding: 8px;">${tx.created_at}</td>
                                    <td style="padding: 8px;"><span class="badge ${badgeClass} px-2 py-1">${tx.transaction_type.toUpperCase()}</span></td>
                                    <td class="fw-semibold" style="padding: 8px;">${prefix}${formatQtyWithUnit(tx.quantity, dbUnitGlobal)}</td>
                                    <td class="fw-bold" style="padding: 8px;">${formatQtyWithUnit(tx.new_stock, dbUnitGlobal)}</td>
                                    <td class="text-start text-truncate" style="padding: 8px; max-width: 250px;" title="${tx.reason}">${tx.reason}</td>
                                `;
                                logBody.appendChild(tr);
                            });
                        } else {
                            logBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Error: ${data.message}</td></tr>`;
                        }
                    } catch (e) {
                        logBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error parsing transaction logs.</td></tr>';
                    }
                }
            };
            xhr.open("GET", "getStockHistory.php?item_id=" + itemId, true);
            xhr.send();
        }

        function submitRestock() {
            const itemId = document.getElementById("restockItemId").value;
            const txType = document.querySelector('input[name="txType"]:checked').value;
            const inputQty = parseFloat(document.getElementById("restockQuantity").value);
            const baseReason = document.getElementById("restockReason").value;
            
            if (isNaN(inputQty) || inputQty <= 0) {
                alert("Please enter a valid quantity.");
                return;
            }
            
            let changeInDbUnits = inputQty;
            let formattedReason = baseReason;
            if (isBulkGlobal) {
                const uiUnit = document.getElementById("restockUnit").value;
                const uiMultiplier = (uiUnit === 'kg' || uiUnit === 'l') ? 1000 : 1;
                const inputBaseVal = inputQty * uiMultiplier;
                const dbUnitBaseVal = parseUnitToGrams(dbUnitGlobal).baseVal;
                changeInDbUnits = inputBaseVal / dbUnitBaseVal;
                formattedReason = baseReason + " (" + inputQty + " " + uiUnit + ")";
            } else {
                formattedReason = baseReason + " (" + inputQty + " units)";
            }
            
            if (txType === "out" && changeInDbUnits > currentStockGlobal) {
                alert("Error: Cannot remove more stock than is currently available (" + formatQtyWithUnit(currentStockGlobal, dbUnitGlobal) + ").");
                return;
            }
            
            const formData = new FormData();
            formData.append("item_id", itemId);
            formData.append("transaction_type", txType);
            formData.append("quantity", changeInDbUnits);
            formData.append("reason", formattedReason);
            
            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = xhr.responseText.trim();
                    if (response === "success") {
                        // Calculate final stock value
                        let finalStock = currentStockGlobal;
                        if (txType === "in") finalStock = currentStockGlobal + changeInDbUnits;
                        else if (txType === "out") finalStock = currentStockGlobal - changeInDbUnits;
                        else if (txType === "adjustment") finalStock = changeInDbUnits;
                        
                        // Update stock column in table dynamically
                        const stockBadge = document.getElementById("stock-badge-" + itemId);
                        if (stockBadge) {
                            const formattedFinal = (finalStock == parseInt(finalStock)) ? parseInt(finalStock) : finalStock.toFixed(2);
                            const finalDisplay = formattedFinal + (isBulkGlobal ? " " + metricGlobal : "");
                            
                            if (finalStock <= 0) {
                                stockBadge.className = "badge bg-danger";
                                stockBadge.textContent = "Out of Stock (0" + (isBulkGlobal ? " " + metricGlobal : "") + ")";
                            } else if (finalStock <= 5) {
                                stockBadge.className = "badge bg-warning text-dark";
                                stockBadge.textContent = "Low Stock (" + finalDisplay + ")";
                            } else {
                                stockBadge.className = "badge bg-light text-dark";
                                stockBadge.textContent = finalDisplay;
                            }
                            
                            // Update the onclick parameter of the Restock button in that row to match the new qty
                            const row = document.getElementById("row-" + itemId);
                            if (row) {
                                const restockBtn = row.querySelector("button.btn-primary");
                                if (restockBtn) {
                                    const unitCell = row.cells[3].textContent.trim();
                                    restockBtn.setAttribute("onclick", "openRestockModal(" + itemId + ", '" + addslashes(row.cells[1].textContent.trim()) + "', " + finalStock + ", '" + unitCell + "', " + (isBulkGlobal ? 'true' : 'false') + ", '" + metricGlobal + "')");
                                }
                            }
                        }
                        
                        // Close modal
                        if (restockModalInstance) {
                            restockModalInstance.hide();
                        }
                    } else {
                        alert("Error updating stock: " + response);
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