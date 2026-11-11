<?php
require 'adminAuth.php';
require '../connection.php';
require 'orderHelpers.php';

// Query completed and cancelled orders
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.updated_at, o.order_status, o.total_amount, o.delivery_fee,
           o.purchase_method, o.payment_status, o.customer_name AS o_cname, o.customer_phone AS o_cphone,
           o.pickup_code, o.pickup_date, o.pickup_time_slot, o.delivery_notes,
           o.street_address1 AS o_addr1, o.street_address2 AS o_addr2, o.town AS o_town,
           u.first_name, u.last_name, u.street_address1, u.street_address2, u.town, u.phone_number,
           oi.quantity, oi.price AS item_price, oi.subtotal AS item_subtotal,
           i.name AS product_name, i.unit, i.category_id
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN items i ON oi.item_id = i.id
    WHERE o.order_status IN ('delivered', 'collected', 'cancelled')
    ORDER BY COALESCE(o.updated_at, o.created_at) DESC
");

// Group orders by order_id in PHP
$orders = [];
if ($orders_rs && $orders_rs->num_rows > 0) {
    while ($row = $orders_rs->fetch_assoc()) {
        $oid = (int)$row['order_id'];
        if (!isset($orders[$oid])) {
            $cust_name = !empty(trim($row['o_cname'] ?? '')) ? $row['o_cname'] : ($row['first_name'] . ' ' . $row['last_name']);
            $cust_phone = !empty(trim($row['o_cphone'] ?? '')) ? $row['o_cphone'] : ($row['phone_number'] ?? '');
            $a1 = !empty(trim($row['o_addr1'] ?? '')) ? $row['o_addr1'] : ($row['street_address1'] ?? '');
            $a2 = !empty(trim($row['o_addr2'] ?? '')) ? $row['o_addr2'] : ($row['street_address2'] ?? '');
            $tw = !empty(trim($row['o_town'] ?? '')) ? $row['o_town'] : ($row['town'] ?? '');
            $orders[$oid] = [
                'order_id'       => $oid,
                'order_number'   => $row['order_number'],
                'created_at'     => $row['created_at'],
                'updated_at'     => $row['updated_at'],
                'order_status'   => $row['order_status'],
                'total_amount'   => $row['total_amount'],
                'delivery_fee'   => $row['delivery_fee'],
                'order_method'   => fc_order_method($row['purchase_method'] ?? 'cod'),
                'payment_status' => $row['payment_status'] ?: 'unpaid',
                'pickup_code'    => $row['pickup_code'],
                'pickup_date'    => $row['pickup_date'],
                'pickup_slot'    => $row['pickup_time_slot'],
                'instructions'   => $row['delivery_notes'],
                'customer_name'  => $cust_name,
                'address'        => trim($a1 . ($a2 ? ", " . $a2 : "") . ", " . $tw, ', '),
                'phone_number'   => $cust_phone,
                'items'          => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name'  => $row['product_name'],
            'quantity'      => $row['quantity'],
            'unit'          => $row['unit'] ?? 'unit',
            'category_id'   => (int)($row['category_id'] ?? 0),
            'item_price'    => $row['item_price'],
            'item_subtotal' => $row['item_subtotal']
        ];
    }
}

$count_all = count($orders);
$count_delivered = 0;
$count_collected = 0;
$count_cancelled = 0;
foreach ($orders as $o) {
    if ($o['order_status'] === 'delivered') $count_delivered++;
    else if ($o['order_status'] === 'collected') $count_collected++;
    else if ($o['order_status'] === 'cancelled') $count_cancelled++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed &amp; Cancelled Orders - Freshcomart Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/custom-ui.css">
    
    <style>
      .main-card {
        background: #fff;
        margin: 28px auto 40px auto;
        max-width: 1340px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        padding: 24px;
      }
      .btn-expand {
          font-size: 1.1rem;
          color: #64748b;
          background: none;
          border: none;
          min-width: 40px;
          min-height: 40px;
          border-radius: 8px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          transition: transform 0.2s, background 0.2s;
      }
      .btn-expand:hover { background: #f1f5f9; }
      .btn-expand[aria-expanded="true"] { transform: rotate(180deg); }
      .fc-code-badge {
          font-family: monospace;
          font-weight: 800;
          font-size: 0.85rem;
          background: #fef3c7;
          color: #92400e;
          padding: 3px 8px;
          border-radius: 6px;
          border: 1px dashed #f59e0b;
          letter-spacing: 0.5px;
      }
      .search-box {
          position: relative;
          max-width: 440px;
      }
      .search-box i {
          position: absolute;
          left: 14px;
          top: 50%;
          transform: translateY(-50%);
          color: #94a3b8;
          font-size: 1rem;
      }
      .search-box input {
          padding-left: 40px;
          border-radius: 999px;
          border: 1px solid #cbd5e1;
      }
      .search-box input:focus {
          border-color: #16a34a;
          box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
      }
    </style>
</head>
<body class="fc-admin">
    <?php include 'navbar.php'; ?>

    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
          <h2 class="fw-bold mb-1" style="font-size:1.55rem; color:#0f172a;">Completed &amp; Cancelled Orders Archive</h2>
          <p class="text-muted mb-0" style="font-size:.9rem">Historical audit log for delivered COD orders, collected store pickups, and cancelled orders.</p>
        </div>
        <div>
          <a href="order.php" class="btn btn-outline-success btn-sm px-3 py-2 rounded-pill fw-semibold">
            <i class="bi bi-arrow-left me-1"></i>Back to Active Orders
          </a>
        </div>
      </div>

      <!-- Filter tabs & Search Bar -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 pb-2 border-bottom">
        <div class="fc-tabs mb-0" id="archiveTabs" role="tablist">
          <button type="button" class="active" data-status="all">All Archive <span class="n"><?php echo $count_all; ?></span></button>
          <button type="button" data-status="delivered"><i class="bi bi-truck me-1"></i>Delivered COD <span class="n"><?php echo $count_delivered; ?></span></button>
          <button type="button" data-status="collected"><i class="bi bi-shop me-1"></i>Collected Pickup <span class="n"><?php echo $count_collected; ?></span></button>
          <button type="button" data-status="cancelled"><i class="bi bi-x-circle me-1"></i>Cancelled <span class="n"><?php echo $count_cancelled; ?></span></button>
        </div>

        <div class="search-box flex-grow-1">
          <i class="bi bi-search"></i>
          <input type="text" id="archiveSearch" class="form-control form-control-sm py-2" placeholder="Search by Order #, Pickup Code, Name, or Phone..." onkeyup="applyFilter()">
        </div>
      </div>
      
      <div class="fc-table-wrap">
        <table class="table fc-admin-table align-middle" id="archiveTable">
          <thead>
            <tr>
              <th scope="col" style="width: 44px;"></th>
              <th scope="col">Order No</th>
              <th scope="col">Customer</th>
              <th scope="col">Method</th>
              <th scope="col">Fulfillment Info</th>
              <th scope="col">Phone</th>
              <th scope="col" class="text-end">Total</th>
              <th scope="col">Payment</th>
              <th scope="col">Date Completed</th>
              <th scope="col">Final Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if (!empty($orders)) {
                foreach ($orders as $order_id => $order) {
                    $order_no = htmlspecialchars($order['order_number'] ?: str_pad((string)$order_id, 5, '0', STR_PAD_LEFT));
                    $customer = htmlspecialchars($order['customer_name']);
                    $address = htmlspecialchars($order['address']);
                    $phone = htmlspecialchars($order['phone_number']);
                    $total = (float)$order['total_amount'];
                    $completionDate = date("Y-m-d H:i", strtotime($order['updated_at'] ?: $order['created_at']));
                    $status = $order['order_status'];
                    $method = $order['order_method'];
                    $pickupCode = htmlspecialchars($order['pickup_code'] ?? '');

                    $searchCorpus = strtolower($order_no . ' ' . $pickupCode . ' ' . $customer . ' ' . $phone . ' ' . $address . ' ' . ($order['instructions'] ?? ''));
                    ?>
                    <!-- Main Order Row -->
                    <tr class="fc-row order-row-<?php echo $order_id; ?>" 
                        data-status="<?php echo htmlspecialchars($status); ?>"
                        data-method="<?php echo htmlspecialchars($method); ?>"
                        data-search="<?php echo htmlspecialchars($searchCorpus); ?>">
                      <td>
                        <button class="btn-expand" type="button" onclick="toggleOrderDetails(<?php echo $order_id; ?>, this)" aria-expanded="false" aria-label="Toggle items">
                          <i class="bi bi-chevron-down"></i>
                        </button>
                      </td>
                      <td class="fw-bold text-dark text-nowrap">#<?php echo $order_no; ?></td>
                      <td class="fw-semibold text-dark"><?php echo $customer; ?></td>
                      <td><?php echo fc_method_badge($method); ?></td>
                      <td style="min-width:230px; max-width:320px;">
                        <?php if ($method === 'pickup') { ?>
                          <div class="mb-1">
                            <span class="text-muted small">Pickup Code:</span> 
                            <span class="fc-code-badge"><i class="bi bi-qr-code me-1"></i><?php echo $pickupCode ?: '—'; ?></span>
                          </div>
                          <div class="fc-pickup-meta text-muted small">
                            <i class="bi bi-calendar3 me-1"></i><?php echo $order['pickup_date'] ? date('d M Y', strtotime($order['pickup_date'])) : 'Today'; ?>
                            · <i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($order['pickup_slot'] ?: 'Standard Hours'); ?>
                          </div>
                        <?php } else { ?>
                          <div class="small text-truncate" title="<?php echo $address; ?>"><i class="bi bi-geo-alt text-muted me-1"></i><?php echo $address; ?></div>
                          <?php if (!empty($order['instructions'])) { ?>
                            <div class="text-muted small text-truncate" style="font-style:italic;" title="<?php echo htmlspecialchars($order['instructions']); ?>">
                              <i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($order['instructions']); ?>
                            </div>
                          <?php } ?>
                        <?php } ?>
                      </td>
                      <td class="text-nowrap"><a href="tel:<?php echo $phone; ?>" class="text-decoration-none text-dark"><i class="bi bi-telephone text-muted me-1"></i><?php echo $phone; ?></a></td>
                      <td class="fw-bold text-dark text-end text-nowrap">Rs. <?php echo number_format($total, 2); ?></td>
                      <td><?php echo fc_payment_badge($order['payment_status']); ?></td>
                      <td class="text-muted text-nowrap small"><?php echo $completionDate; ?></td>
                      <td><?php echo fc_status_badge($status, $method); ?></td>
                    </tr>
                    
                    <!-- Collapsible Order Items Details Row -->
                    <tr class="fc-detail-row order-row-<?php echo $order_id; ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-search="<?php echo htmlspecialchars($searchCorpus); ?>">
                      <td colspan="10">
                        <div id="order-items-<?php echo $order_id; ?>" class="collapse" style="background-color: #f8fafc;">
                          <div class="p-4 border-bottom">
                            <h5 class="fw-bold text-secondary mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                              <i class="bi bi-cart3 me-2"></i>Purchased Items Breakdown
                            </h5>
                            <div class="table-responsive">
                              <table class="table table-bordered table-sm align-middle text-center bg-white mb-0" style="font-size: 0.9rem; border-color: #e2e8f0;">
                                <thead class="table-light text-secondary">
                                  <tr>
                                    <th scope="col" class="py-2 text-start ps-3">Product Name</th>
                                    <th scope="col" class="py-2">Unit Price</th>
                                    <th scope="col" class="py-2">Quantity</th>
                                    <th scope="col" class="py-2 text-end pe-3">Subtotal</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($order['items'] as $item) { ?>
                                    <tr>
                                      <td class="text-start fw-semibold ps-3 py-2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                      <td>Rs. <?php echo number_format((float)$item['item_price'], 2); ?></td>
                                      <td class="fw-semibold">
                                        <?php echo htmlspecialchars((string)Database::formatQuantity($item['quantity'], $item['unit'], $item['category_id'])); ?>
                                      </td>
                                      <td class="fw-bold text-dark text-end pe-3">Rs. <?php echo number_format((float)$item['item_subtotal'], 2); ?></td>
                                    </tr>
                                  <?php } ?>
                                  <tr class="table-light fw-semibold text-secondary">
                                    <td colspan="3" class="text-end pe-3 py-2">Delivery Fee:</td>
                                    <td class="text-end pe-3 text-dark"><?php echo $method === 'pickup' ? '<span class="fc-free-badge" style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:999px;font-size:.75rem">Rs. 0.00 / Free</span>' : 'Rs. ' . number_format((float)$order['delivery_fee'], 2); ?></td>
                                  </tr>
                                  <tr class="fw-bold">
                                    <td colspan="3" class="text-end pe-3 py-2" style="background-color: #dcfce7; color:#15803d;">Grand Total:</td>
                                    <td class="text-end pe-3" style="background-color: #dcfce7; color:#15803d;">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr id="emptyRow" style="<?php echo empty($orders) ? '' : 'display:none'; ?>">
                <td colspan="10" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                  <span class="fw-semibold">No matching archived orders found.</span>
                </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentStatusFilter = 'all';

        function applyFilter() {
            const q = (document.getElementById('archiveSearch').value || '').toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('#archiveTable tr.fc-row').forEach(function (row) {
                const status = row.dataset.status;
                const matchStatus = (currentStatusFilter === 'all') || (status === currentStatusFilter);
                
                const searchCorpus = (row.dataset.search || '').toLowerCase();
                const matchSearch = !q || searchCorpus.indexOf(q) !== -1;
                
                const show = matchStatus && matchSearch;
                row.style.display = show ? '' : 'none';
                
                const detail = row.nextElementSibling;
                if (detail && detail.classList.contains('fc-detail-row')) {
                    if (!show) {
                        detail.style.display = 'none';
                    } else {
                        const collapseDiv = detail.querySelector('.collapse');
                        detail.style.display = (collapseDiv && collapseDiv.classList.contains('show')) ? '' : '';
                    }
                }
                if (show) visible++;
            });
            document.getElementById('emptyRow').style.display = visible ? 'none' : '';
        }

        document.querySelectorAll('#archiveTabs button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#archiveTabs button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentStatusFilter = btn.dataset.status;
                applyFilter();
            });
        });

        applyFilter();

        function toggleOrderDetails(orderId, button) {
            const detailsDiv = document.getElementById("order-items-" + orderId);
            if (!detailsDiv) return;
            
            const isExpanded = button.getAttribute("aria-expanded") === "true";
            if (isExpanded) {
                detailsDiv.classList.remove("show");
                button.setAttribute("aria-expanded", "false");
            } else {
                detailsDiv.classList.add("show");
                button.setAttribute("aria-expanded", "true");
            }
        }
    </script>
</body>
</html>
