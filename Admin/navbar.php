<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .navbar-custom {
                background: #f8fafc !important;
                border-radius: 0 0 18px 18px;
                box-shadow: 0 4px 18px rgba(0,0,0,0.07);
                padding-top: 8px;
                padding-bottom: 8px;
            }
            .navbar-brand {
                letter-spacing: 1.5px !important;
                font-weight: 700 !important;
                font-size: 1.7rem !important;
            }
            .navbar-nav .nav-link {
                color: #222 !important;
                font-weight: 500;
                font-size: 1.08rem;
                border-radius: 8px;
                padding: 7px 22px !important;
                transition: background 0.18s, color 0.18s;
            }
            .navbar-nav .nav-link.active, .navbar-nav .nav-link:focus, .navbar-nav .nav-link:hover {
                background: #e6f2ff !important;
                color: #0d6efd !important;
            }
            @media (max-width: 900px) {
                .navbar-brand { font-size: 1.2rem !important; }
                .navbar-nav .nav-link { font-size: 1rem; padding: 7px 10px !important; }
            }
        </style>
<body>
        <nav class="navbar navbar-expand-lg navbar-custom">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">CEC COMART</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto gap-4">
                        <li class="nav-item">
                            <a class="nav-link" href="Item.php">Items</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="completOrder.php">Completed Orders</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="summary.php">Summary</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

      <!-- Bootstrap 5 JS Bundle (with Popper) -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>