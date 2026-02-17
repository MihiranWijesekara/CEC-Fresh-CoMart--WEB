<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>CEC Fresh-Co Mart</title>
    <meta name="robots" content="noindex, follow" />
    <meta name="description" content="" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />
      <!-- Bootstrap CSS -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
      <!-- Bootstrap Icons -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link
      rel="shortcut icon"
      type="image/x-icon"
      href="../assets/images/logo/logo-freshco.png"
    />

    <style>
      body {
        font-family: Arial, sans-serif;
        background: #e0f7fa;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
      }

      .register-box {
        background: white;
        padding: 40px 30px;
        width: 500px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      }

      .register-box h2 {
        text-align: center;
        color: #00796b;
        margin-bottom: 25px;
      }

      form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px 45px;
      }

      form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
      }

      form input[type="text"],
      form input[type="email"],
      form input[type="tel"],
      form input[type="password"] {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #b2dfdb;
        box-sizing: border-box;
      }

      .terms {
        grid-column: span 2;
        display: flex;
        align-items: center;
      }

      .terms input {
        margin-right: 10px;
      }

      button {
        grid-column: span 2;
        padding: 20px;
        background: #00796b;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
      }

      button:hover {
        background: #004d40;
      }

      #message {
        grid-column: span 2;
        text-align: center;
        font-weight: bold;
        margin-top: 10px;
      }

      /* Responsive for mobile */
      @media (max-width: 500px) {
        form {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>
  <body>
    <!-- Register Box -->
    <div class="register-box">
      <!-- Small centered logo -->
      <div style="text-align: center; margin-bottom: 20px">
        <a href="../index.php">
          <img
            src="../assets/images/logo/logo-freshco.png"
            alt="Fresh Co Market Logo"
            style="width: 135px; height: auto"
          />
        </a>


      </div>

      <form id="registerForm">
        <div>
          <label>First Name</label>
          <input
            type="text"
            id="firstName"
            placeholder="Enter First Name"
            required
          />
        </div>

        <div>
          <label>Last Name</label>
          <input
            type="text"
            id="lastName"
            placeholder="Enter Last Name"
            required
          />
        </div>

        <div>
          <label>Email</label>
          <input type="email" id="email" placeholder="Enter Email" required />
        </div>

        <div>
          <label>Phone</label>
          <input
            type="tel"
            id="phone"
            placeholder="Enter Phone Number"
            required
          />
        </div>

        <div>
          <label>Password</label>
          <input
            type="password"
            id="password"
            placeholder="Enter Password"
            required
          />
        </div>

        <div>
          <label>Confirm Password</label>
          <input
            type="password"
            id="confirmPassword"
            placeholder="Confirm Password"
            required
          />
        </div>

        <div class="terms">
          <input type="checkbox" id="terms" required />
          <label for="terms">I agree to the Terms & Conditions</label>
        </div>

        <button type="button" onclick="signup()" style="margin-bottom: 18px;">Register</button>
      </form>

      <div id="msgdiv" style="display: none; margin-bottom: 10px;"></div>
      <p id="message"></p>
    </div>

    <script src="../login/auth.js"></script>
  </body>
</html>
