<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items</title>
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
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Image</th>
              <th scope="col">Unit</th>
              <th scope="col">Price</th>
              <th scope="col">Category</th>
              <th scope="col">Updated Date</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope="row">1</th>
              <td>chocolate Biscuit</td>
              <td><img src="https://essstr.blob.core.windows.net/essimg/ItemAsset/Pic125102_20250301164303.jpg" alt="chocolate Biscuit"></td>
              <td>365 g</td>
              <td>350.0</td>
              <td>Biscuit</td>
              <td>2024-01-01</td>
              <td><span class="badge bg-success">Active</span></td>
            </tr>
             <tr>
              <th scope="row">2</th>
              <td>chocolate Biscuit</td>
              <td><img src="https://essstr.blob.core.windows.net/essimg/ItemAsset/Pic125102_20250301164303.jpg" alt="chocolate Biscuit"></td>
              <td>365 g</td>
              <td>350.0</td>
              <td>Biscuit</td>
              <td>2024-01-01</td>
              <td><span class="badge bg-success">Active</span></td>
            </tr>
             <tr>
              <th scope="row">3</th>
              <td>chocolate Biscuit</td>
              <td><img src="https://essstr.blob.core.windows.net/essimg/ItemAsset/Pic125102_20250301164303.jpg" alt="chocolate Biscuit"></td>
              <td>365 g</td>
              <td>350.0</td>
              <td>Biscuit</td>
              <td>2024-01-01</td>
              <td><span class="badge bg-success">Active</span></td>
            </tr>
             <tr>
              <th scope="row">4</th>
              <td>chocolate Biscuit</td>
              <td><img src="https://essstr.blob.core.windows.net/essimg/ItemAsset/Pic125102_20250301164303.jpg" alt="chocolate Biscuit"></td>
              <td>365 g</td>
              <td>350.0</td>
              <td>Biscuit</td>
              <td>2024-01-01</td>
              <td><span class="badge bg-success">Active</span></td>
            </tr>
             <tr>
              <th scope="row">5</th>
              <td>chocolate Biscuit</td>
              <td><img src="https://essstr.blob.core.windows.net/essimg/ItemAsset/Pic125102_20250301164303.jpg" alt="chocolate Biscuit"></td>
              <td>365 g</td>
              <td>350.0</td>
              <td>Biscuit</td>
              <td>2024-01-01</td>
              <td><span class="badge bg-success">Active</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
</body>
</html>