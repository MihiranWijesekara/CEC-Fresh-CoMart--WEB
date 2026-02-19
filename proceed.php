<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout - Fresh-Co Mart</title>

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />

    <style>
      body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
      }

      .navbar-container {
        width: 100%;
        /* position: fixed; */
        /* top: 0; */
        z-index: 1000;
      }

      /* Breadcrumb */
      .breadcrumbs-area {
        background-color: #fff;
        padding: 20px 45px;
        margin-bottom: 20px;
      }
      .breadcrumbs-area .breadcrumb-content h3.title-3 {
        font-size: 48px;
        color: white;
        font-weight: bold;
      }

      /* Checkout Section */
      .checkout-area {
        padding: 20px 45px;
      }
      .card {
        border-radius: 10px;
      }

      /* Payment option */
      .payment-label {
        display: block;
        padding: 10px 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        cursor: pointer;
        background-color: #f9f9f9;
        margin-bottom: 10px;
        transition: all 0.3s ease;
      }
      .payment-label input {
        margin-right: 10px;
      }
      .payment-label:hover {
        background-color: #e9e9e9;
      }
      .payment-label.selected {
        border-color: green;
        background-color: #d4f7d4;
      }

      footer {
        background-color: #212529;
        color: white;
        text-align: center;
        padding: 15px 0;
        margin-top: 40px;
      }
    </style>
  </head>
  <body>
    <!-- Navbar -->
    <div id="navbar" class="navbar-container"></div>

    <!-- Breadcrumb -->
    <div
      class="breadcrumbs-area"
      style="
        background-image: url(&quot;assets/images/brand-logo/su.jpg&quot;);
        background-size: cover;
        background-position: center;
        padding: 120px 0;
      "
    >
      <div class="container text-center">
        <div class="breadcrumb-content">
          <h3 class="title-3"><b>Proceed Checkout</b></h3>
        </div>
      </div>
    </div>

    <!-- Checkout -->
    <div class="checkout-area">
      <div class="container">
        <div class="row g-4">
          <!-- Billing Details -->
          <div class="col-lg-7">
            <h4>Billing Details</h4>
            <br />
            <form id="checkoutForm" action="place.html">
              <div class="row g-3">
                <div class="col-md-6">
                  <label>First Name</label>
                  <input type="text" class="form-control" required />
                </div>
                <div class="col-md-6">
                  <label>Last Name</label>
                  <input type="text" class="form-control" required />
                </div>
              </div>

              <div class="mb-3">
                <label>Street Address</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="House number and street name"
                  required
                />
              </div>

              <div class="mb-3">
                <label>Address</label>
                <input
                  type="text"
                  class="form-control"
                  placeholder="Apartment, suite, unit etc. (optional)"
                />
              </div>

              <div class="mb-3">
                <label>Town</label>
                <input
                  type="text"
                  class="form-control"
                  value="Colombo 1"
                  required
                />
              </div>

              <div class="mb-3">
                <label>Phone</label>
                <input type="tel" class="form-control" required />
              </div>

              <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" required />
              </div>

              <div class="mb-3 form-check">
                <input
                  type="checkbox"
                  class="form-check-input"
                  id="shipDifferent"
                  onclick="toggleAddress()"
                />
                <label class="form-check-label" for="shipDifferent"
                  >Ship to a different address?</label
                >
              </div>

              <div id="shippingAddress" style="display: none">
                <div class="mb-3">
                  <label>Shipping Address *</label>
                  <input type="text" class="form-control" />
                </div>
              </div>

              <div class="mb-3">
                <label>Order notes (optional)</label>
                <textarea
                  class="form-control"
                  rows="3"
                  placeholder="Notes about your order"
                ></textarea>
              </div>
            </form>
          </div>

          <!-- Order Summary -->
          <div class="col-lg-5">
            <div class="card p-4">
              <h4>Your Order</h4>
              <table class="table">
                <tr>
                  <td>Anchovy (Halmessa) 1kg × 1</td>
                  <td class="text-end">Rs.1,000.00</td>
                </tr>
                <tr>
                  <td>Subtotal</td>
                  <td class="text-end">Rs.1,000.00</td>
                </tr>
                <tr>
                  <td>Delivery Charge</td>
                  <td class="text-end">Rs.450.00</td>
                </tr>
                <tr class="fw-bold">
                  <td>Total</td>
                  <td class="text-end">Rs.1,450.00</td>
                </tr>
              </table>

              <div class="mb-3 payment-option">
                <label class="payment-label selected">
                  <input type="radio" name="payment" value="cod" checked /> Cash
                  on Delivery
                </label>
              </div>

              <button
                type="submit"
                form="checkoutForm"
                class="btn btn-success w-100"
              >
                Place Order
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer>© 2026 CEC Fresh-Co Mart. All Rights Reserved.</footer>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      function toggleAddress() {
        const check = document.getElementById("shipDifferent");
        const box = document.getElementById("shippingAddress");
        box.style.display = check.checked ? "block" : "none";
      }

      // Highlight selected payment
      const labels = document.querySelectorAll(".payment-label");
      labels.forEach((label) => {
        label.addEventListener("click", () => {
          labels.forEach((l) => l.classList.remove("selected"));
          label.classList.add("selected");
          label.querySelector("input").checked = true;
        });
      });

      fetch("product/nav.html")
        .then((response) => response.text())
        .then((data) => {
          let fixedData = data
            .replace(/src="\.\.\/assets\/images\/logo\/logo-freshco\.png"/g, 'src="assets/images/logo/logo-freshco.png"')
            .replace(/href="\.\.\/login\/sign\.php"/g, 'href="login/sign.php"')
            .replace(/href="\.\.\/login\/register\.php"/g, 'href="login/register.php"')
            .replace(/href="\.\.\/product\/shopping-cart\.html"/g, 'href="product/shopping-cart.html"');

          document.getElementById("navbar").innerHTML = fixedData;
          const script = document.createElement("script");
          script.src = "product/navBar.js";
          document.body.appendChild(script);
        });
    </script>
  </body>
</html>
