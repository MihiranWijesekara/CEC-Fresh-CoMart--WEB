/**
 * Customer & Admin Authentication Controller (Firebase Auth + Cloud Firestore)
 */

let fbServicePromise = null;
function loadFirebaseService() {
  if (!fbServicePromise) {
    const url = new URL("../assets/js/firebase-service.js", window.location.href).href;
    fbServicePromise = import(url);
  }
  return fbServicePromise;
}

async function signup() {
  const fName = document.getElementById("firstName").value;
  const lName = document.getElementById("lastName").value;
  const email = document.getElementById("email").value;
  const phoneNumber = document.getElementById("phone").value;
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;
  const termsEl = document.getElementById("terms");
  const msgDiv = document.getElementById("msgdiv");

  if (termsEl && !termsEl.checked) {
    msgDiv.style.display = "block";
    msgDiv.className = "alert alert-danger";
    msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>Please agree to the Terms & Conditions.';
    return;
  }

  try {
    const fb = await loadFirebaseService();
    const res = await fb.registerCustomer({
      firstName: fName,
      lastName: lName,
      email: email,
      phoneNumber: phoneNumber,
      password: password,
      confirmPassword: confirmPassword
    });

    msgDiv.style.display = "block";
    if (res.ok) {
      document.getElementById("registerForm").reset();
      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Registration Successful!';
      setTimeout(function () {
        window.location.href = "sign.html";
      }, 1200);
    } else {
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + res.message;
    }
  } catch (err) {
    msgDiv.style.display = "block";
    msgDiv.className = "alert alert-danger";
    msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>Registration failed. Please try again.';
  }
}

async function signIn() {
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const msgDiv = document.getElementById("msgdiv");

  try {
    const fb = await loadFirebaseService();
    const res = await fb.loginCustomer(email, password);

    msgDiv.style.display = "block";
    if (res.ok) {
      document.getElementById("loginForm").reset();
      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Login Successful!';
      setTimeout(function () {
        if (res.isAdmin) {
          window.location.href = "../Admin/dashboard.html";
        } else {
          window.location.href = "../product/product.html";
        }
      }, 1000);
    } else {
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + res.message;
    }
  } catch (err) {
    msgDiv.style.display = "block";
    msgDiv.className = "alert alert-danger";
    msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>Login failed. Please try again.';
  }
}

window.signup = signup;
window.signIn = signIn;
