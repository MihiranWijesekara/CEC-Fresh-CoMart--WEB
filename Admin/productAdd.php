<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Add Product</title>

<link
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
  rel="stylesheet"
/>

<style>
  body {
    background: #f5f5f5;
    font-family: Arial, sans-serif;
  }
  .container {
    max-width: 600px;
    margin: 40px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    padding: 32px;
  }
  h2 {
    text-align: center;
    margin-bottom: 28px;
  }
  .form-label {
    font-weight: 600;
  }
  .form-select,
  .form-control {
    border-radius: 8px;
  }
  .btn-primary {
    width: 100%;
    border-radius: 8px;
  }
  .img-preview {
    display: block;
    margin: 10px 0 20px 0;
    max-width: 180px;
    max-height: 180px;
    border-radius: 8px;
    border: 1px solid #ddd;
  }
</style>
</head>

<body>

<div class="container">
  <h2>Add New Product</h2>

  <form id="productForm" enctype="multipart/form-data">

    <!-- Item Name -->
    <div class="mb-3">
      <label class="form-label">Item Name</label>
      <input type="text" class="form-control" name="itemName" id="itemName" required>
    </div>

    <!-- Image -->
    <div class="mb-3">
      <label class="form-label">Image</label>
      <input type="file" class="form-control" id="itemImage" name="itemImage" accept="image/*" required>
      <img id="imgPreview" class="img-preview" style="display:none;">
    </div>

    <!-- Price -->
    <div class="mb-3">
      <label class="form-label">Price</label>
      <input type="number" class="form-control" name="price" id="price" min="0" step="0.01" required>
    </div>

    <!-- Unit (VALUE + TYPE COMBINED) -->
    <div class="mb-3">
      <label class="form-label">Unit</label>

      <div class="input-group">
        <!-- Enter value -->
        <input 
          type="number"
          class="form-control"
          id="unit_value"
          placeholder="Enter value (e.g. 180)"
          step="0.01"
          min="0"
          required
        >

        <!-- Select unit -->
        <select 
          class="form-select"
          id="unit_type"
          style="max-width:120px;"
          required
        >
          <option value="">Unit</option>
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
    <div class="mb-3">
      <label class="form-label">Category</label>
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
      </select>
    </div>

    <!-- Status -->
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select class="form-select" name="status" id="status" required>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <div id="msgdiv" style="display:none;"></div>

    <button type="button" class="btn btn-primary" onclick="productAdd()">Add Product</button> 

  </form>
</div>

<script>

  // Image preview
  document.getElementById("itemImage").addEventListener("change", function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById("imgPreview");

    if (file) {
      const reader = new FileReader();
      reader.onload = function (evt) {
        preview.src = evt.target.result;
        preview.style.display = "block";
      };
      reader.readAsDataURL(file);
    } else {
      preview.src = "";
      preview.style.display = "none";
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
