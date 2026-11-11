<?php
require_once __DIR__ . '/connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$store = require __DIR__ . '/storeConfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/sign.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_rs = Database::search("SELECT * FROM users WHERE id = '$user_id' LIMIT 1");
if (!$user_rs || $user_rs->num_rows === 0) {
    header("Location: login/sign.php");
    exit();
}

$user = $user_rs->fetch_assoc();
$first_name      = htmlspecialchars($user['first_name'] ?? '');
$last_name       = htmlspecialchars($user['last_name'] ?? '');
$email           = htmlspecialchars($user['email'] ?? '');
$phone_number    = htmlspecialchars($user['phone_number'] ?? '');
$street_address1 = htmlspecialchars($user['street_address1'] ?? '');
$street_address2 = htmlspecialchars($user['street_address2'] ?? '');
$town            = htmlspecialchars($user['town'] ?? '');
$joined_date     = date("d M Y", strtotime($user['created_at'] ?? 'now'));

$first = strtoupper(substr($user['first_name'] ?? 'F', 0, 1));
$last = strtoupper(substr($user['last_name'] ?? 'C', 0, 1));
$logo_initials = $first . $last;

// Recent orders for this customer
$orders_rs = Database::search("
    SELECT id, order_number, total_amount, order_status, purchase_method, payment_status, created_at 
    FROM orders 
    WHERE user_id = '$user_id' 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Account – Freshcomart Supermarket</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/logo-freshco.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/supermarket-ui.css">

    <style>
      .account-card {
        background: #ffffff;
        border: 1px solid var(--fc-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        overflow: hidden;
      }
      .account-header {
        background: linear-gradient(135deg, #064e3b 0%, #16a34a 100%);
        color: #ffffff;
        padding: 32px 24px;
        text-align: center;
      }
      .account-avatar {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        border: 3px solid #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 12px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
      }
      .nav-pills-custom .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 14px;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
      }
      .nav-pills-custom .nav-link:hover {
        background: #f1f5f9;
        color: #0f172a;
      }
      .nav-pills-custom .nav-link.active {
        background: #16a34a;
        color: #ffffff;
      }
    </style>
</head>
<body class="fc-body">

    <!-- Top Announcement Bar -->
    <div class="fc-topbar">
        <span>🚚 Cash on Delivery (Flat Rs. 450) &nbsp;|&nbsp; 🏬 Free Store Pickup at Main Branch (Rajagiriya)!</span>
    </div>

    <!-- Header Navigation -->
    <header class="fc-main-header">
        <div class="container-fluid px-lg-5 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="d-flex align-items-center text-decoration-none">
                    <img src="assets/images/logo/logo-freshco.png" alt="Freshcomart" height="44">
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="product/product.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-shop me-1"></i>Shop Catalog
                    </a>
                    <a href="product/shopping-cart.php" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-cart3 me-1"></i>Cart
                    </a>
                    <a href="login/logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5">
        <div class="row g-4">
            <!-- Left Sidebar Navigation -->
            <div class="col-lg-4">
                <div class="account-card mb-4">
                    <div class="account-header">
                        <div class="account-avatar"><?php echo $logo_initials; ?></div>
                        <h4 class="fw-bold mb-1"><?php echo $first_name . ' ' . $last_name; ?></h4>
                        <div class="small opacity-75"><?php echo $email; ?></div>
                        <div class="mt-2"><span class="badge bg-white text-success rounded-pill px-3 py-1 fw-bold small">Member since <?php echo $joined_date; ?></span></div>
                    </div>
                    <div class="p-3">
                        <div class="nav flex-column nav-pills nav-pills-custom" id="accountTabs" role="tablist">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab">
                                <i class="bi bi-grid-1x2"></i>Account Overview
                            </button>
                            <button class="nav-link" id="address-tab" data-bs-toggle="pill" data-bs-target="#address" type="button" role="tab">
                                <i class="bi bi-geo-alt-fill text-success"></i>Delivery Address &amp; Contact Details
                            </button>
                            <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                <i class="bi bi-shield-lock-fill"></i>Security &amp; Password
                            </button>
                            <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button" role="tab">
                                <i class="bi bi-bag-check-fill"></i>Recent Orders Log
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Fast Checkout Reminder -->
                <div class="p-3 border rounded-3 bg-white shadow-sm small text-muted">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    <strong>Fast Checkout Tip:</strong> Keeping your delivery address updated allows one-click auto-fill during Cash on Delivery checkout.
                </div>
            </div>

            <!-- Right Tab Contents -->
            <div class="col-lg-8">
                <div class="tab-content" id="accountTabsContent">
                    
                    <!-- TAB 1: Account Overview -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="account-card p-4">
                            <h4 class="fw-bold text-dark mb-3"><i class="bi bi-person-circle text-success me-2"></i>Account Overview</h4>
                            <p class="text-muted small">Manage your profile, preferred delivery address, security credentials, and view past purchases.</p>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <div class="text-muted small">Primary Contact</div>
                                        <div class="fw-bold text-dark mt-1"><?php echo $first_name . ' ' . $last_name; ?></div>
                                        <div class="small text-muted"><?php echo $phone_number ?: 'No phone provided yet'; ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <div class="text-muted small">Email Address</div>
                                        <div class="fw-bold text-dark mt-1"><?php echo $email; ?></div>
                                        <div class="small text-success"><i class="bi bi-check-circle-fill me-1"></i>Verified Account</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="text-muted small">Default Delivery Address</div>
                                            <button class="btn btn-sm btn-link text-success fw-bold p-0 text-decoration-none" onclick="document.getElementById('address-tab').click();">Edit Address</button>
                                        </div>
                                        <div class="fw-semibold text-dark mt-1">
                                            <?php echo ($street_address1 || $town) ? trim($street_address1 . ($street_address2 ? ', ' . $street_address2 : '') . ', ' . $town) : 'No address saved yet. Please update in the Address tab below.'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Delivery Address & Contact Details -->
                    <div class="tab-pane fade" id="address" role="tabpanel">
                        <div class="account-card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">
                                        <i class="bi bi-geo-alt-fill text-success me-2"></i>Delivery Address &amp; Contact Details
                                    </h4>
                                    <p class="text-muted small mb-0">These details automatically pre-populate during Cash on Delivery checkout.</p>
                                </div>
                            </div>

                            <div id="addressAlert" class="alert alert-success d-none py-2 px-3 small"></div>

                            <form id="addressForm" onsubmit="saveProfileAddress(event)">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="fName" class="form-control" value="<?php echo $first_name; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="lName" class="form-control" value="<?php echo $last_name; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Mobile Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone_number" id="phone" class="form-control" placeholder="e.g. 0771234567" value="<?php echo $phone_number; ?>" required>
                                        <div class="form-text small">Our courier will call this number prior to doorstep delivery.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Street Address Line 1 <span class="text-danger">*</span></label>
                                        <input type="text" name="street_address1" id="street1" class="form-control" placeholder="House / Flat / Building No. and Street" value="<?php echo $street_address1; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold small">Street Address Line 2 <span class="text-muted">(Optional)</span></label>
                                        <input type="text" name="street_address2" id="street2" class="form-control" placeholder="Apartment, suite, or landmark" value="<?php echo $street_address2; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Town / City <span class="text-danger">*</span></label>
                                        <input type="text" name="town" id="town" class="form-control" placeholder="e.g. Colombo, Rajagiriya, Kandy" value="<?php echo $town; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Country</label>
                                        <input type="text" class="form-control" value="Sri Lanka" disabled>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" id="saveAddressBtn" class="btn btn-success fw-bold px-4 py-2 rounded-pill">
                                            <i class="bi bi-check-lg me-1"></i>Save Delivery Details
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 3: Security & Password -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="account-card p-4">
                            <h4 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Change Password</h4>
                            <p class="text-muted small">Ensure your account is using a long, random password to stay secure.</p>

                            <div id="securityAlert" class="alert d-none py-2 px-3 small"></div>

                            <form id="securityForm" onsubmit="changeSecurityPassword(event)">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Current Password</label>
                                    <input type="password" id="currentPassword" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">New Password (at least 8 characters)</label>
                                    <input type="password" id="newPassword" class="form-control" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" class="form-control" required>
                                </div>
                                <button type="submit" id="changePasswordBtn" class="btn btn-primary fw-bold px-4 py-2 rounded-pill">
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 4: Recent Orders Log -->
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <div class="account-card p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold text-dark mb-0"><i class="bi bi-bag-check-fill text-warning me-2"></i>My Recent Orders</h4>
                                <a href="product/my-orders.php" class="btn btn-outline-success btn-sm rounded-pill px-3">View All Orders</a>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle table-hover small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order #</th>
                                            <th>Method</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th class="text-end">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($orders_rs && $orders_rs->num_rows > 0) {
                                            while ($ord = $orders_rs->fetch_assoc()) {
                                                $ordNo = $ord['order_number'] ?: str_pad((string)$ord['id'], 5, '0', STR_PAD_LEFT);
                                                $isPickup = ($ord['purchase_method'] === 'pickup');
                                                ?>
                                                <tr>
                                                    <td class="fw-bold">#<?php echo htmlspecialchars($ordNo); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $isPickup ? 'bg-warning-subtle text-dark border border-warning' : 'bg-primary-subtle text-primary border border-primary-subtle'; ?> rounded-pill px-2">
                                                            <?php echo $isPickup ? 'Store Pickup' : 'COD Delivery'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted"><?php echo date("d M Y", strtotime($ord['created_at'])); ?></td>
                                                    <td class="fw-bold text-dark">Rs. <?php echo number_format((float)$ord['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border rounded-pill px-2">
                                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ord['order_status']))); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="order-confirmation.php?order_id=<?php echo $ord['id']; ?>" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-pill">View</a>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No orders placed yet.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function saveProfileAddress(e) {
            e.preventDefault();
            var btn = document.getElementById('saveAddressBtn');
            var alertBox = document.getElementById('addressAlert');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
            alertBox.classList.add('d-none');

            var form = document.getElementById('addressForm');
            var formData = new FormData(form);

            fetch('updateProfileProcess.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.text(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Delivery Details';
                var text = data.trim();
                if (text === 'success') {
                    alertBox.className = 'alert alert-success py-2 px-3 small';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Your delivery address and contact details have been updated successfully!';
                    alertBox.classList.remove('d-none');
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 small';
                    alertBox.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>' + text;
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Delivery Details';
                alertBox.className = 'alert alert-danger py-2 px-3 small';
                alertBox.textContent = 'Connection error. Please try again.';
                alertBox.classList.remove('d-none');
            });
        }

        function changeSecurityPassword(e) {
            e.preventDefault();
            var btn = document.getElementById('changePasswordBtn');
            var alertBox = document.getElementById('securityAlert');
            var cur = document.getElementById('currentPassword').value;
            var nw = document.getElementById('newPassword').value;
            var cnf = document.getElementById('confirmPassword').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating…';
            alertBox.classList.add('d-none');

            var form = new FormData();
            form.append('firstName', document.getElementById('fName').value);
            form.append('lastName', document.getElementById('lName').value);
            form.append('phone', document.getElementById('phone').value);
            form.append('streetAddress1', document.getElementById('street1').value);
            form.append('streetAddress2', document.getElementById('street2').value);
            form.append('town', document.getElementById('town').value);
            form.append('currentPassword', cur);
            form.append('newPassword', nw);
            form.append('confirmPassword', cnf);

            fetch('profileProcess.php', {
                method: 'POST',
                body: form
            })
            .then(function(res) { return res.text(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = 'Update Password';
                var text = data.trim();
                if (text === 'success') {
                    alertBox.className = 'alert alert-success py-2 px-3 small';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Password updated successfully!';
                    alertBox.classList.remove('d-none');
                    document.getElementById('securityForm').reset();
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 small';
                    alertBox.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>' + text;
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = 'Update Password';
                alertBox.className = 'alert alert-danger py-2 px-3 small';
                alertBox.textContent = 'An error occurred. Please try again.';
                alertBox.classList.remove('d-none');
            });
        }
    </script>
</body>
</html>

