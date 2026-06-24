<?php
session_start();
if (isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_is_admin']) && (int)$_SESSION['admin_is_admin'] === 1) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - CEC Fresh Co-Mart</title>
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #09130d 0%, #050b07 50%, #03140a 100%);
            --accent-green: #10b981;
            --accent-green-hover: #059669;
            --accent-glow: rgba(16, 185, 129, 0.25);
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-border-focus: rgba(16, 185, 129, 0.5);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Decorative background glow circles */
        .glow-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            opacity: 0.15;
            pointer-events: none;
        }
        .glow-1 {
            width: 400px;
            height: 400px;
            background: var(--accent-green);
            top: -100px;
            left: -100px;
        }
        .glow-2 {
            width: 300px;
            height: 300px;
            background: #3b82f6;
            bottom: -50px;
            right: -50px;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            z-index: 10;
            position: relative;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            border-color: rgba(16, 185, 129, 0.2);
            box-shadow: 0 25px 50px rgba(16, 185, 129, 0.1);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 18px;
            color: var(--accent-green);
            font-size: 2rem;
            margin-bottom: 16px;
            box-shadow: 0 0 20px var(--accent-glow);
            animation: pulse-glow 3s infinite alternate;
        }

        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
                border-color: rgba(16, 185, 129, 0.3);
            }
            100% {
                box-shadow: 0 0 25px rgba(16, 185, 129, 0.5);
                border-color: rgba(16, 185, 129, 0.6);
            }
        }

        .logo-title {
            font-weight: 700;
            font-size: 1.6rem;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            background: linear-gradient(to right, #ffffff, #a7f3d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #d1d5db;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .input-group-custom:focus-within {
            border-color: var(--glass-border-focus);
            box-shadow: 0 0 0 4px var(--accent-glow);
            background: rgba(0, 0, 0, 0.3);
        }

        .input-icon-left {
            padding-left: 18px;
            color: var(--text-muted);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .form-control-custom {
            background: transparent !important;
            border: none !important;
            color: var(--text-main) !important;
            padding: 14px 18px;
            font-size: 1rem;
            font-weight: 400;
            width: 100%;
            outline: none;
            box-shadow: none !important;
        }

        .form-control-custom::placeholder {
            color: #6b7280;
        }

        .password-toggle-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding-right: 18px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .password-toggle-btn:hover {
            color: var(--text-main);
        }

        .btn-submit {
            background: var(--accent-green);
            color: #030712;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--accent-green-hover);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
            color: #030712;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-custom {
            display: none;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        .portal-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.8rem;
            color: #4b5563;
        }
        
        .portal-footer a {
            color: var(--accent-green);
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .portal-footer a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>

    <!-- Decorative lights -->
    <div class="glow-circle glow-1"></div>
    <div class="glow-circle glow-2"></div>

    <div class="login-card">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="logo-title">CEC Admin Portal</h1>
            <p class="logo-subtitle">Please enter your security access key</p>
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert-custom">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span id="errorMessage">Invalid security access key.</span>
        </div>

        <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
            <div class="mb-4">
                <label for="adminPassword" class="form-label">Security Password</label>
                <div class="input-group-custom">
                    <span class="input-icon-left">
                        <i class="bi bi-key-fill"></i>
                    </span>
                    <input type="password" id="adminPassword" class="form-control-custom" placeholder="••••" required autocomplete="current-password" autofocus>
                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()">
                        <i id="toggleIcon" class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit w-100">
                <span id="btnText">Authenticate</span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="portal-footer">
            <p>&copy; <?php echo date("Y"); ?> CEC Fresh Co-Mart. <br><a href="../index.php"><i class="bi bi-arrow-left me-1"></i> Back to Main Site</a></p>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById("adminPassword");
            const toggleIcon = document.getElementById("toggleIcon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("bi-eye-fill");
                toggleIcon.classList.add("bi-eye-slash-fill");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("bi-eye-slash-fill");
                toggleIcon.classList.add("bi-eye-fill");
            }
        }

        function handleAdminLogin(event) {
            event.preventDefault();
            
            const password = document.getElementById("adminPassword").value;
            const errorAlert = document.getElementById("errorAlert");
            const errorMessage = document.getElementById("errorMessage");
            const submitBtn = document.getElementById("submitBtn");
            const btnText = document.getElementById("btnText");
            const btnSpinner = document.getElementById("btnSpinner");

            // UI feedback
            errorAlert.style.display = "none";
            submitBtn.disabled = true;
            btnText.textContent = "Verifying...";
            btnSpinner.classList.remove("d-none");

            // Form data
            const formData = new FormData();
            formData.append("password", password);

            // AJAX request
            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    submitBtn.disabled = false;
                    btnSpinner.classList.add("d-none");
                    btnText.textContent = "Authenticate";

                    if (xhr.status === 200) {
                        const response = xhr.responseText.trim();
                        if (response === "success") {
                            // Show success state on button
                            submitBtn.style.backgroundColor = "#059669";
                            btnText.textContent = "Access Granted";
                            
                            // Redirect to dashboard
                            setTimeout(function() {
                                window.location.href = "dashboard.php";
                            }, 1000);
                        } else {
                            // Show error
                            errorMessage.textContent = response;
                            errorAlert.style.display = "flex";
                            // Animation trigger
                            errorAlert.style.animation = 'none';
                            errorAlert.offsetHeight; /* trigger reflow */
                            errorAlert.style.animation = null;
                        }
                    } else {
                        errorMessage.textContent = "An error occurred. Please try again.";
                        errorAlert.style.display = "flex";
                    }
                }
            };
            xhr.open("POST", "adminLoginProcess.php", true);
            xhr.send(formData);
        }
    </script>
</body>
</html>
