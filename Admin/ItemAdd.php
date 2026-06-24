<?php require 'adminAuth.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Product - CEC COMART</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
      body {
        background-color: #f4f6fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      .form-container {
        max-width: 650px;
        margin: 40px auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.05);
        padding: 35px;
        border: none;
      }
      .form-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #212529;
        letter-spacing: 0.5px;
      }
      .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
      }
      .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #ced4da;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
      }
      .form-control:focus, .form-select:focus {
        border-color: #27b62e;
        box-shadow: 0 0 0 0.25rem rgba(39, 182, 46, 0.15);
      }
      .btn-submit {
        background-color: #27b62e;
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: background-color 0.2s, transform 0.1s;
      }
      .btn-submit:hover {
        background-color: #1e8e24;
        color: white;
      }
      .btn-submit:active {
        transform: scale(0.98);
      }
      .btn-cancel {
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
      }
      .img-preview-box {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        background: #f8f9fa;
        margin-top: 10px;
        position: relative;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
      }
      .img-preview {
        max-width: 100%;
        max-height: 180px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      }
      .preview-placeholder {
          color: #adb5bd;
          font-size: 0.9rem;
      }
      .preview-placeholder i {
          font-size: 2.5rem;
          margin-bottom: 5px;
          display: block;
      }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container px-3">
  <div class="form-container">
    <div class="d-flex align-items-center mb-4">
        <a href="Item.php" class="btn btn-outline-secondary btn-sm rounded-circle me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Back to Items">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="form-title mb-0">Add New Product</h2>
    </div>

    <form id="productForm" enctype="multipart/form-data">

      <!-- Item Name -->
      <div class="mb-4">
        <label class="form-label" for="itemName">Item Name</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-tag text-muted"></i></span>
            <input type="text" class="form-control border-start-0" style="border-radius: 0 10px 10px 0;" name="itemName" id="itemName" placeholder="e.g. Chocolate Biscuit" required>
        </div>
      </div>

      <!-- Image -->
      <div class="mb-4">
        <label class="form-label" for="itemImage">Product Image</label>
        <input type="file" class="form-control" id="itemImage" name="itemImage" accept="image/*" required>
        
        <div class="img-preview-box">
            <div id="previewPlaceholder" class="preview-placeholder">
                <i class="bi bi-image text-muted"></i>
                No image selected. Preview will show up here.
            </div>
            <img id="imgPreview" class="img-preview" style="display:none;" alt="Product Preview">
        </div>
      </div>

      <!-- Price -->
      <div class="mb-4">
        <label class="form-label" for="price">Price (Rs.)</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-cash-coin text-muted"></i></span>
            <input type="number" class="form-control border-start-0" style="border-radius: 0 10px 10px 0;" name="price" id="price" min="0" step="0.01" placeholder="0.00" required>
        </div>
      </div>

      <!-- Unit (VALUE + TYPE COMBINED) -->
      <div class="mb-4">
        <label class="form-label">Measurement Unit</label>
        <div class="input-group">
          <!-- Enter value -->
          <input 
            type="number"
            class="form-control"
            id="unit_value"
            placeholder="Enter quantity value (e.g. 250)"
            step="0.01"
            min="0"
            required
            style="border-radius: 10px 0 0 10px;"
          >

          <!-- Select unit -->
          <select 
            class="form-select"
            id="unit_type"
            style="max-width:130px; border-radius: 0 10px 10px 0;"
            required
          >
            <option value="">Unit Type</option>
            <option value="ml">ml</option>
            <option value="L">L</option>
            <option value="g">g</option>
            <option value="kg">kg</option>
            <option value="pcs">pcs</option>
            <option value="pack">pack</option>
            <option value="box">box</option>
            <option value="bottle">bottle</option>
          </select>
        </div>

        <!-- Hidden final combined value -->
        <input type="hidden" name="unit" id="unit">
      </div>

      <!-- Category -->
      <div class="mb-4">
        <label class="form-label" for="category">Product Category</label>
        <select class="form-select" name="category" id="category" required>
          <option value="">Select Category</option>
          <option value="1">Vegetables</option>
          <option value="2">Fruits</option>
          <option value="3">Snacks</option>
          <option value="4">Biscuits</option>
          <option value="5">Coffee</option>
          <option value="6">Eggs</option>
          <option value="7">Water</option>
          <option value="8">Tea</option>
          <option value="9">Cheese</option>
          <option value="10">Yoghurts & Curd</option>
          <option value="11">Desserts</option>
          <option value="12">Beverages</option>
          <option value="13">Bakery</option>
          <option value="14">Meat & Seafood</option>
          <option value="15">Pantry Staples</option>
          <option value="16">Dairy Staples</option>
          <option value="17">Frozen Foods</option>
          <option value="18">Household & Cleaning</option>
          <option value="19">Personal Care & Baby Care</option>
        </select>
      </div>

      <!-- Status -->
      <div class="mb-4">
        <label class="form-label" for="status">Stock Status</label>
        <select class="form-select" name="status" id="status" required>
          <option value="active">Active (In Stock)</option>
          <option value="inactive">Inactive (Out of Stock)</option>
        </select>
      </div>

      <div id="msgdiv" class="mb-4" style="display:none; border-radius:10px;"></div>

      <div class="d-flex justify-content-end gap-3">
          <a href="Item.php" class="btn btn-outline-secondary btn-cancel">Cancel</a>
          <button type="button" class="btn btn-submit" onclick="productAdd()">Add Product</button> 
      </div>

    </form>
  </div>
</div>

<script>
  // Image preview
  document.getElementById("itemImage").addEventListener("change", function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById("imgPreview");
    const placeholder = document.getElementById("previewPlaceholder");

    if (file) {
      const reader = new FileReader();
      reader.onload = function (evt) {
        preview.src = evt.target.result;
        preview.style.display = "block";
        placeholder.style.display = "none";
      };
      reader.readAsDataURL(file);
    } else {
      preview.src = "";
      preview.style.display = "none";
      placeholder.style.display = "block";
    }
  });

  // Combine Unit Value + Type into one hidden field
  function combineUnit() {
    const value = document.getElementById("unit_value").value;
    const type = document.getElementById("unit_type").value;

    if (value && type) {
      document.getElementById("unit").value = value + " " + type;
    }
  }

  document.getElementById("unit_value").addEventListener("input", combineUnit);
  document.getElementById("unit_type").addEventListener("change", combineUnit);
</script>

<script src="admin.js"></script>

</body>
</html>
