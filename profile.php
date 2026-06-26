<?php
  require_once 'connection.php';
  session_start();
  
  if (!isset($_SESSION['user_id'])) {
      header("Location: login/sign.php");
      exit();
  }
  
  $user_id = $_SESSION['user_id'];
  $user_rs = Database::search("SELECT * FROM users WHERE id = '$user_id'");
  if ($user_rs && $user_row = $user_rs->fetch_assoc()) {
      $first_name = htmlspecialchars($user_row['first_name']);
      $last_name = htmlspecialchars($user_row['last_name']);
      $email = htmlspecialchars($user_row['email']);
      $phone_number = htmlspecialchars($user_row['phone_number']);
  } else {
      header("Location: login/sign.php");
      exit();
  }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Profile - CEC Fresh-Co Mart</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/logo-freshco.png" />
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
    <!-- Custom navbar CSS since we are including proceedNavbar.php -->
    <style>
      :root {
        --primary-green: #27b62e;
        --primary-green-hover: #1e8e24;
        --bg-gray: #f4f7f5;
        --text-dark: #1f2937;
        --text-muted: #6b7280;
        --card-bg: #ffffff;
      }

      body {
        background-color: var(--bg-gray);
        font-family: 'Outfit', sans-serif;
        color: var(--text-dark);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      .navbar-placeholder {
        height: 80px; /* offset for fixed navbar */
      }

      .main-container {
        flex: 1;
        padding: 40px 15px;
      }

      .profile-card {
        background: var(--card-bg);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.03);
        overflow: hidden;
      }

      .profile-card-header {
        background: linear-gradient(135deg, var(--primary-green) 0%, #1e8e24 100%);
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
      }

      .profile-avatar-large {
        width: 90px;
        height: 90px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 3px solid white;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
        font-weight: 800;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      }

      .profile-card-body {
        padding: 40px;
      }

      .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-green-hover);
        border-bottom: 2px solid #eefdf0;
        padding-bottom: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .form-control {
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
      }

      .form-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 4px rgba(39, 182, 46, 0.15);
        color: var(--text-dark);
      }

      .form-control:disabled {
        background-color: #f9fafb;
        border-color: #f3f4f6;
        color: #9ca3af;
      }

      .form-label {
        font-weight: 600;
        font-size: 0.88rem;
        color: #4b5563;
        margin-bottom: 8px;
      }

      .btn-primary {
        background-color: var(--primary-green);
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(39, 182, 46, 0.2);
      }

      .btn-primary:hover {
        background-color: var(--primary-green-hover);
        box-shadow: 0 6px 20px rgba(39, 182, 46, 0.35);
        transform: translateY(-1px);
      }

      .btn-primary:active {
        transform: translateY(0);
      }

      .alert {
        border-radius: 14px;
        border: none;
        padding: 16px 20px;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
      }

      .alert-success {
        background-color: #eefdf0;
        color: #1e8e24;
      }

      .alert-danger {
        background-color: #ffebee;
        color: #c62828;
      }

      footer {
        background-color: #212529;
        color: white;
        text-align: center;
        padding: 20px 0;
        font-size: 0.9rem;
        margin-top: auto;
      }

      /* Fix navbar alignment override */
      .navbar {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1050;
      }
    </style>
  </head>
  <body>
    <!-- Navbar -->
    <?php include 'proceedNavbar.php'; ?>
    <div class="navbar-placeholder"></div>

    <div class="container main-container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="profile-card">
            <div class="profile-card-header">
              <div class="profile-avatar-large" id="profileAvatar">
                <?php echo $logo_initials; ?>
              </div>
              <h3 class="fw-bold mb-1" id="profileHeaderName"><?php echo $first_name . ' ' . $last_name; ?></h3>
              <p class="mb-0 text-white-50 small">Customer Account</p>
            </div>
            
            <div class="profile-card-body">
              <!-- Message Alert -->
              <div id="alertBox" class="alert" style="display: none;"></div>

              <form id="profileForm" novalidate>
                <!-- Section: Personal Details -->
                <div class="form-section-title">
                  <i class="bi bi-person-bounding-box"></i> Personal Details
                </div>
                
                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="firstName" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="firstName" value="<?php echo $first_name; ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="lastName" value="<?php echo $last_name; ?>" required />
                  </div>
                </div>

                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" value="<?php echo $email; ?>" disabled />
                    <div class="form-text text-muted mt-2 small"><i class="bi bi-info-circle me-1"></i>Email address cannot be changed.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" value="<?php echo $phone_number; ?>" required />
                  </div>
                </div>

                <!-- Section: Security & Password -->
                <div class="form-section-title mt-5">
                  <i class="bi bi-shield-lock-fill"></i> Change Password
                </div>
                
                <div class="mb-4">
                  <label for="currentPassword" class="form-label">Current Password</label>
                  <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password to make changes" />
                </div>

                <div class="row mb-4">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="newPassword" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="newPassword" placeholder="Enter new password" />
                  </div>
                  <div class="col-md-6">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password" />
                  </div>
                </div>

                <div class="text-end mt-5">
                  <button type="button" class="btn btn-primary btn-lg" onclick="saveProfile()" id="saveBtn">
                    <span id="btnText"><i class="bi bi-check2-circle me-2"></i>Save Changes</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;"></span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer>© 2026 CEC Fresh-Co Mart. All Rights Reserved.</footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      function showMsg(message, isSuccess) {
          const alertBox = document.getElementById("alertBox");
          alertBox.style.display = "flex";
          if (isSuccess) {
              alertBox.className = "alert alert-success";
              alertBox.innerHTML = '<i class="bi bi-check-circle-fill fs-5"></i><span>' + message + '</span>';
          } else {
              alertBox.className = "alert alert-danger";
              alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5"></i><span>' + message + '</span>';
          }
          alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function saveProfile() {
          const fName = document.getElementById("firstName").value.trim();
          const lName = document.getElementById("lastName").value.trim();
          const phone = document.getElementById("phone").value.trim();
          const currentPassword = document.getElementById("currentPassword").value;
          const newPassword = document.getElementById("newPassword").value;
          const confirmPassword = document.getElementById("confirmPassword").value;

          const alertBox = document.getElementById("alertBox");
          alertBox.style.display = "none";

          if (!fName || !lName || !phone) {
              showMsg("First name, Last name, and Phone number are required.", false);
              return;
          }

          // If new password is provided, validate current password and confirmation
          if (newPassword || confirmPassword || currentPassword) {
              if (!currentPassword) {
                  showMsg("Please enter your current password to make security changes.", false);
                  return;
              }
              if (!newPassword) {
                  showMsg("Please enter a new password.", false);
                  return;
              }
              if (newPassword.length < 8) {
                  showMsg("New password must be at least 8 characters long.", false);
                  return;
              }
              if (newPassword !== confirmPassword) {
                  showMsg("New password and confirmation do not match.", false);
                  return;
              }
          }

          // UI animation state
          const saveBtn = document.getElementById("saveBtn");
          const btnText = document.getElementById("btnText");
          const btnSpinner = document.getElementById("btnSpinner");
          
          saveBtn.disabled = true;
          btnText.style.display = "none";
          btnSpinner.style.display = "inline-block";

          const f = new FormData();
          f.append("firstName", fName);
          f.append("lastName", lName);
          f.append("phone", phone);
          f.append("currentPassword", currentPassword);
          f.append("newPassword", newPassword);
          f.append("confirmPassword", confirmPassword);

          const r = new XMLHttpRequest();
          r.onreadystatechange = function() {
              if (r.readyState === 4) {
                  saveBtn.disabled = false;
                  btnText.style.display = "inline-block";
                  btnSpinner.style.display = "none";
                  
                  if (r.status === 200) {
                      const t = r.responseText.trim();
                      if (t === "success") {
                          showMsg("Profile details updated successfully!", true);
                          
                          // Update dynamic UI values
                          document.getElementById("profileHeaderName").innerText = fName + " " + lName;
                          
                          // Dynamically calculate initials
                          const firstInitial = fName.charAt(0).toUpperCase();
                          const lastInitial = lName.charAt(0).toUpperCase();
                          const newInitials = firstInitial + lastInitial;
                          
                          document.getElementById("profileAvatar").innerText = newInitials;
                          
                          // Clear password fields
                          document.getElementById("currentPassword").value = "";
                          document.getElementById("newPassword").value = "";
                          document.getElementById("confirmPassword").value = "";
                          
                          // Reload page after a delay to update navbar initials
                          setTimeout(function() {
                              location.reload();
                          }, 1000);
                      } else {
                          showMsg(t, false);
                      }
                  } else {
                      showMsg("An error occurred. Please try again.", false);
                  }
              }
          };
          r.open("POST", "profileProcess.php", true);
          r.send(f);
      }
    </script>
  </body>
</html>
