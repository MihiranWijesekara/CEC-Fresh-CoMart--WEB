<?php
require_once __DIR__ . '/adminAuth.php';
require_once __DIR__ . '/../connection.php';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    Database::iud("DELETE FROM `contacts` WHERE id = '$deleteId'");
    $_SESSION['inquiry_msg'] = "Inquiry #$deleteId deleted successfully.";
    header("Location: inquiries.php");
    exit();
}

// Fetch all inquiries
$inquiries_rs = Database::search("SELECT * FROM `contacts` ORDER BY `created_at` DESC");
$count = $inquiries_rs ? (int)$inquiries_rs->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Inquiries - Freshcomart Admin</title>
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
      .msg-preview {
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    </style>
</head>
<body class="fc-admin">
    <?php include 'navbar.php'; ?>

    <div class="main-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
          <h2 class="fw-bold mb-1" style="font-size:1.55rem; color:#0f172a;">Customer Inquiries &amp; Messages</h2>
          <p class="text-muted mb-0" style="font-size:.9rem">Inquiries, questions, and feedback submitted through the storefront Contact Us form.</p>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold">
          <i class="bi bi-chat-left-text me-1"></i><?php echo $count; ?> Total Inquiries
        </span>
      </div>

      <?php if (!empty($_SESSION['inquiry_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i><?php echo htmlspecialchars($_SESSION['inquiry_msg']); unset($_SESSION['inquiry_msg']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 10px;"></button>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table fc-admin-table align-middle table-hover">
          <thead>
            <tr>
              <th scope="col" style="width: 60px;">ID</th>
              <th scope="col">Customer Name</th>
              <th scope="col">Email Address</th>
              <th scope="col">Subject</th>
              <th scope="col">Message Preview</th>
              <th scope="col">Date Submitted</th>
              <th scope="col" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($inquiries_rs && $inquiries_rs->num_rows > 0): ?>
              <?php while ($row = $inquiries_rs->fetch_assoc()): 
                $id = (int)$row['id'];
                $name = htmlspecialchars($row['name'] ?: 'Customer');
                $email = htmlspecialchars($row['email'] ?: 'No Email');
                $subject = htmlspecialchars($row['subject'] ?: 'General Inquiry');
                $message = htmlspecialchars($row['message'] ?: '');
                $date = date("d M Y, h:i A", strtotime($row['created_at']));
              ?>
                <tr>
                  <td class="fw-bold text-muted">#<?php echo $id; ?></td>
                  <td class="fw-semibold text-dark"><?php echo $name; ?></td>
                  <td>
                    <a href="mailto:<?php echo $email; ?>?subject=Re: <?php echo urlencode($subject); ?>" class="text-decoration-none text-primary small">
                      <i class="bi bi-envelope me-1"></i><?php echo $email; ?>
                    </a>
                  </td>
                  <td class="fw-semibold small text-truncate" style="max-width: 180px;"><?php echo $subject; ?></td>
                  <td>
                    <div class="text-muted small msg-preview" title="<?php echo $message; ?>">
                      <?php echo $message; ?>
                    </div>
                  </td>
                  <td class="text-muted small"><?php echo $date; ?></td>
                  <td class="text-center text-nowrap">
                    <!-- View Modal Trigger -->
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 me-1" 
                            onclick="openInquiryModal(<?php echo $id; ?>, '<?php echo addslashes($name); ?>', '<?php echo addslashes($email); ?>', '<?php echo addslashes($subject); ?>', '<?php echo addslashes(str_replace(["\r", "\n"], ' ', $message)); ?>', '<?php echo $date; ?>')">
                      <i class="bi bi-eye me-1"></i>View
                    </button>
                    <!-- Delete Button -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete inquiry #<?php echo $id; ?>?');">
                      <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1" title="Delete Inquiry">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                  <span class="fw-semibold">No customer inquiries found.</span>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Inquiry Details Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom py-3">
            <h5 class="modal-title fw-bold text-dark" id="inquiryModalLabel">
              <i class="bi bi-envelope-open text-success me-2"></i>Inquiry Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <div class="small text-muted">Sender Name &amp; Email:</div>
              <div class="fw-bold text-dark" id="modalSender"></div>
              <div class="small text-primary" id="modalEmail"></div>
            </div>
            <div class="mb-3">
              <div class="small text-muted">Subject:</div>
              <div class="fw-bold text-dark" id="modalSubject"></div>
            </div>
            <div class="mb-3">
              <div class="small text-muted">Message:</div>
              <div class="p-3 bg-light border rounded-3 text-dark small" id="modalMessage" style="white-space: pre-wrap; max-height: 250px; overflow-y: auto;"></div>
            </div>
            <div class="small text-muted" id="modalDate"></div>
          </div>
          <div class="modal-footer border-top py-2">
            <a href="#" id="modalReplyBtn" class="btn btn-success btn-sm rounded-pill px-3">
              <i class="bi bi-reply me-1"></i>Reply via Email
            </a>
            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      function openInquiryModal(id, name, email, subject, message, date) {
        document.getElementById('modalSender').textContent = name;
        document.getElementById('modalEmail').textContent = email;
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('modalDate').textContent = 'Submitted on: ' + date;
        document.getElementById('modalReplyBtn').href = 'mailto:' + encodeURIComponent(email) + '?subject=Re: ' + encodeURIComponent(subject);

        var modal = new bootstrap.Modal(document.getElementById('inquiryModal'));
        modal.show();
      }
    </script>
</body>
</html>

