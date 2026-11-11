<?php
require 'adminAuth.php';
require '../connection.php';
require 'orderHelpers.php';

// Query active orders (excluding delivered / collected)
$orders_rs = Database::search("
    SELECT o.id AS order_id, o.order_number, o.created_at, o.order_status, o.total_amount, o.delivery_fee,
           o.order_method, o.payment_status, o.pickup_code, o.pickup_date, o.pickup_slot, o.delivery_instructions,
           o.street_address1 AS o_addr1, o.street_address2 AS o_addr2, o.town AS o_town,
           u.first_name, u.last_name, u.street_address1, u.street_address2, u.town, u.phone_number,
           oi.quantity, oi.price AS item_price, oi.subtotal AS item_subtotal,
           i.name AS product_name
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    INNER JOIN order_items oi ON o.id = oi.order_id
    INNER JOIN items i ON oi.item_id = i.id
    WHERE o.order_status NOT IN ('delivered', 'collected') OR o.order_status IS NULL
    ORDER BY o.created_at DESC
");

// Group orders by order_id in PHP
$orders = [];
if ($orders_rs && $orders_rs->num_rows > 0) {
    while ($row = $orders_rs->fetch_assoc()) {
        $oid = $row['order_id'];
        if (!isset($orders[$oid])) {
            // Prefer the address captured on the order itself, fall back to the profile address
            $a1 = $row['o_addr1'] !== null && $row['o_addr1'] !== '' ? $row['o_addr1'] : $row['street_address1'];
            $a2 = $row['o_addr1'] !== null && $row['o_addr1'] !== '' ? $row['o_addr2'] : $row['street_address2'];
            $tw = $row['o_addr1'] !== null && $row['o_addr1'] !== '' ? $row['o_town'] : $row['town'];
            $orders[$oid] = [
                'order_id' => $row['order_id'],
                'order_number' => $row['order_number'],
                'created_at' => $row['created_at'],
                'order_status' => $row['order_status'],
                'total_amount' => $row['total_amount'],
                'delivery_fee' => $row['delivery_fee'],
                'order_method' => fc_order_method($row['order_method']),
                'payment_status' => $row['payment_status'] ?: 'Unpaid',
                'pickup_code' => $row['pickup_code'],
                'pickup_date' => $row['pickup_date'],
                'pickup_slot' => $row['pickup_slot'],
                'instructions' => $row['delivery_instructions'],
                'customer_name' => $row['first_name'] . ' ' . $row['last_name'],
                'address' => trim($a1 . ($a2 ? ", " . $a2 : "") . ", " . $tw, ', '),
                'phone_number' => $row['phone_number'],
                'items' => []
            ];
        }
        $orders[$oid]['items'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'item_price' => $row['item_price'],
            'item_subtotal' => $row['item_subtotal']
        ];
    }
}

$count_all = count($orders);
$count_pickup = 0;
foreach ($orders as $o) { if ($o['order_method'] === 'pickup') $count_pickup++; }
$count_delivery = $count_all - $count_pickup;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Orders - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
      .main-card {
        background: #fff;
        margin: 32px auto 40px auto;
        max-width: 1320px;
      }
      .btn-expand {
          font-size: 1.1rem;
          color: #64748b;
          background: none;
          border: none;
          min-width: 44px;
          min-height: 44px;
          transition: transform 0.2s;
      }
      .btn-expand[aria-expanded="true"] {
          transform: rotate(180deg);
      }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h2 class="fw-bold mb-0" style="font-size:1.5rem;">Active Orders</h2>
          <p class="text-muted mb-0" style="font-size:.9rem">Accept, prepare and hand over orders. Completed orders move to <a href="completOrder.php">Completed Orders</a>.</p>
        </div>
      </div>

      <!-- Quick filter tabs -->
      <div class="fc-tabs" id="orderTabs" role="tablist">
        <button type="button" class="active" data-filter="all">All Orders <span class="n"><?php echo $count_all; ?></span></button>
        <button type="button" data-filter="delivery"><i class="bi bi-truck"></i> Delivery / COD <span class="n"><?php echo $count_delivery; ?></span></button>
        <button type="button" data-filter="pickup"><i class="bi bi-shop"></i> Store Pickup <span class="n"><?php echo $count_pickup; ?></span></button>
      </div>

      <div class="fc-table-wrap">
        <table class="table fc-admin-table align-middle" id="ordersTable">
          <thead>
            <tr>
              <th scope="col" style="width: 50px;"></th>
              <th scope="col">Order No</th>
              <th scope="col">Customer</th>
              <th scope="col">Method</th>
              <th scope="col">Delivery / Pickup details</th>
              <th scope="col">Phone No</th>
              <th scope="col" class="text-end">Total</th>
              <th scope="col">Payment</th>
              <th scope="col">Date</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (!empty($orders)) {
                foreach ($orders as $order_id => $order) {
                    $order_no = htmlspecialchars($order['order_number'] ?: str_pad($order_id, 5, '0', STR_PAD_LEFT));
                    $customer = htmlspecialchars($order['customer_name']);
                    $address = htmlspecialchars($order['address']);
                    $phone = htmlspecialchars($order['phone_number']);
                    $total = (float)$order['total_amount'];
                    $date = date("Y-m-d H:i", strtotime($order['created_at']));
                    $status = $order['order_status'] ?: 'pending';
                    $method = $order['order_method'];
                    $next = fc_next_action($status, $method);
                    ?>
                    <!-- Main Order Row -->
                    <tr class="fc-row order-row-<?php echo $order_id; ?>" data-method="<?php echo $method; ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-order="<?php echo $order_id; ?>">
                      <td>
                        <button class="btn-expand" type="button" onclick="toggleOrderDetails(<?php echo $order_id; ?>, this)" aria-expanded="false" aria-label="Toggle items">
                          <i class="bi bi-chevron-down"></i>
                        </button>
                      </td>
                      <td class="fw-bold text-dark">#<?php echo $order_no; ?></td>
                      <td class="fw-semibold text-dark"><?php echo $customer; ?></td>
                      <td><?php echo fc_method_badge($method); ?></td>
                      <td style="min-width:220px; max-width:280px;">
                        <?php if ($method === 'pickup') { ?>
                          <div><span class="text-muted">Code:</span> <span class="fc-code"><?php echo htmlspecialchars($order['pickup_code'] ?: '—'); ?></span></div>
                          <div class="fc-pickup-meta"><i class="bi bi-clock me-1"></i><?php echo $order['pickup_date'] ? date('d M', strtotime($order['pickup_date'])) : ''; ?> · <?php echo htmlspecialchars($order['pickup_slot'] ?: ''); ?></div>
                        <?php } else { ?>
                          <?php echo $address; ?>
                        <?php } ?>
                      </td>
                      <td><?php echo $phone; ?></td>
                      <td class="fw-bold text-dark text-end text-nowrap">Rs. <?php echo number_format($total, 2); ?></td>
                      <td><span class="pay-cell"><?php echo fc_payment_badge($order['payment_status']); ?></span></td>
                      <td class="text-muted text-nowrap"><?php echo $date; ?></td>
                      <td><span class="status-cell"><?php echo fc_status_badge($status, $method); ?></span></td>
                      <td>
                          <div class="fc-act-stack order-action-container-<?php echo $order_id; ?>">
                              <?php if ($next) { ?>
                                  <button class="fc-act <?php echo $next[2]; ?>" onclick="updateOrderStatus(<?php echo $order_id; ?>, '<?php echo $next[0]; ?>')">
                                      <i class="bi <?php echo $next[3]; ?>"></i><?php echo $next[1]; ?>
                                  </button>
                              <?php } ?>
                              <?php if (strtolower($order['payment_status']) !== 'paid') { ?>
                                  <button class="fc-act pay pay-btn" onclick="markPaid(<?php echo $order_id; ?>)"><i class="bi bi-cash-coin"></i>Mark Paid</button>
                              <?php } ?>
                          </div>
                      </td>
                    </tr>
                    
                    <!-- Collapsible Order Items Details Row -->
                    <tr class="fc-detail-row order-row-<?php echo $order_id; ?>" data-method="<?php echo $method; ?>">
                      <td colspan="11">
                        <div id="order-items-<?php echo $order_id; ?>" class="collapse" style="background-color: #f8fafc;">
                          <div class="p-4 border-bottom">
                            <h5 class="fw-bold text-secondary mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                              <i class="bi bi-cart3 me-2"></i>Order Items Details
                            </h5>
                            <?php if ($method === 'delivery' && $order['instructions']) { ?>
                              <p class="mb-3" style="font-size:.88rem"><strong>Delivery instructions:</strong> <?php echo htmlspecialchars($order['instructions']); ?></p>
                            <?php } ?>
                            <div class="table-responsive">
                              <table class="table table-bordered table-sm align-middle text-center bg-white mb-0" style="font-size: 0.9rem; border-color: #e2e8f0;">
                                <thead class="table-light text-secondary">
                                  <tr>
                                    <th scope="col" class="py-2 text-start ps-3">Product Name</th>
                                    <th scope="col" class="py-2">Unit Price</th>
                                    <th scope="col" class="py-2">Quantity</th>
                                    <th scope="col" class="py-2">Subtotal</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($order['items'] as $item) { ?>
                                    <tr>
                                      <td class="text-start fw-semibold ps-3 py-2"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                      <td>Rs. <?php echo number_format($item['item_price'], 2); ?></td>
                                      <td class="fw-semibold"><?php echo $item['quantity']; ?></td>
                                      <td class="fw-bold text-dark">Rs. <?php echo number_format($item['item_subtotal'], 2); ?></td>
                                    </tr>
                                  <?php } ?>
                                  <tr class="table-light fw-semibold text-secondary">
                                    <td colspan="3" class="text-end pe-3 py-2">Delivery Fee:</td>
                                    <td class="text-dark"><?php echo $method === 'pickup' ? '<span class="fc-free-badge" style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:999px;font-size:.75rem">Rs. 0.00 / Free</span>' : 'Rs. ' . number_format($order['delivery_fee'], 2); ?></td>
                                  </tr>
                                  <tr class="fw-bold">
                                    <td colspan="3" class="text-end pe-3 py-2" style="background-color: #dcfce7; color:#15803d;">Grand Total:</td>
                                    <td style="background-color: #dcfce7; color:#15803d;">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
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
                <td colspan="11" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No active orders found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ---- labels / flow (mirrors Admin/orderHelpers.php) ----
        const LABELS = { pending: 'Pending', processing: 'Processing', ready: 'Ready for Pickup', shipped: 'Shipped', delivered: 'Delivered', collected: 'Collected' };
        function labelFor(status, method) { return (status === 'shipped' && method === 'pickup') ? 'Ready for Pickup' : (LABELS[status] || status); }
        function nextAction(status, method) {
            const p = method === 'pickup';
            if (status === 'pending') return ['processing', 'Accept', 'accept', 'bi-arrow-right-short'];
            if (status === 'processing') return p ? ['ready', 'Mark Ready', 'ready', 'bi-box-seam'] : ['shipped', 'Mark Shipped', 'ready', 'bi-truck'];
            if (status === 'ready' || status === 'shipped') return p ? ['collected', 'Mark Collected', 'done', 'bi-bag-check'] : ['delivered', 'Mark Delivered', 'done', 'bi-check-lg'];
            return null;
        }

        // ---- quick filter tabs + zebra striping ----
        let currentFilter = 'all';
        function applyFilter() {
            let visible = 0;
            document.querySelectorAll('#ordersTable tr.fc-row').forEach(function (row) {
                const show = currentFilter === 'all' || row.dataset.method === currentFilter;
                row.style.display = show ? '' : 'none';
                const detail = row.nextElementSibling;
                if (detail && detail.classList.contains('fc-detail-row')) detail.style.display = show ? '' : 'none';
                row.classList.toggle('alt', show && (visible % 2 === 1));
                if (show) visible++;
            });
            document.getElementById('emptyRow').style.display = visible ? 'none' : '';
        }
        document.querySelectorAll('#orderTabs button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#orderTabs button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
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

        function post(fields, cb) {
            const formData = new FormData();
            Object.keys(fields).forEach(k => formData.append(k, fields[k]));
            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) cb(xhr.responseText.trim());
            };
            xhr.open("POST", "updateOrderStatus.php", true);
            xhr.send(formData);
        }

        function renderActions(row, orderId) {
            const status = row.dataset.status, method = row.dataset.method;
            const box = row.querySelector('.fc-act-stack');
            let html = '';
            const n = nextAction(status, method);
            if (n) html += `<button class="fc-act ${n[2]}" onclick="updateOrderStatus(${orderId}, '${n[0]}')"><i class="bi ${n[3]}"></i>${n[1]}</button>`;
            if (row.dataset.paid !== '1' && !row.querySelector('.pay-cell .pay-paid')) {
                html += `<button class="fc-act pay pay-btn" onclick="markPaid(${orderId})"><i class="bi bi-cash-coin"></i>Mark Paid</button>`;
            }
            box.innerHTML = html;
        }

        function updateOrderStatus(orderId, nextStatus) {
            const row = document.querySelector("tr.fc-row.order-row-" + orderId);
            const method = row ? row.dataset.method : 'delivery';
            const msg = "Change Order #" + orderId + " to \"" + labelFor(nextStatus, method) + "\"?";
            if (!confirm(msg)) {
                return;
            }
            post({ order_id: orderId, status: nextStatus }, function (response) {
                if (response !== "success") {
                    alert("Error updating order status: " + response);
                    return;
                }
                if (nextStatus === 'delivered' || nextStatus === 'collected') {
                    // Completed → remove rows (they now live under Completed Orders)
                    document.querySelectorAll(".order-row-" + orderId).forEach(r => {
                        r.style.transition = "opacity 0.3s ease";
                        r.style.opacity = 0;
                        setTimeout(() => { r.remove(); applyFilter(); }, 300);
                    });
                } else {
                    row.dataset.status = nextStatus;
                    row.querySelector('.status-cell').innerHTML =
                        `<span class="fc-badge st-${nextStatus === 'shipped' && method === 'pickup' ? 'ready' : nextStatus}">${labelFor(nextStatus, method)}</span>`;
                    renderActions(row, orderId);
                }
            });
        }

        function markPaid(orderId) {
            if (!confirm("Mark Order #" + orderId + " as PAID?")) return;
            post({ order_id: orderId, payment_status: 'Paid' }, function (response) {
                if (response !== "success") { alert("Error updating payment: " + response); return; }
                const row = document.querySelector("tr.fc-row.order-row-" + orderId);
                row.querySelector('.pay-cell').innerHTML = '<span class="fc-badge pay-paid"><i class="bi bi-check-circle-fill"></i>Paid</span>';
                row.dataset.paid = '1';
                renderActions(row, orderId);
            });
        }
    </script>
</body>
</html>
