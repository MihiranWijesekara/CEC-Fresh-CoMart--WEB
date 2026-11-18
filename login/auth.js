/**
 * Customer & Admin Authentication Controller (Firebase Auth + Cloud Firestore)
 * Supports post-login redirect (?redirect=... / fc_post_login_redirect)
 * and automatic completion of pending guest cart actions (fc_pending_cart_action).
 */

let fbServicePromise = null;
function loadFirebaseService() {
  if (!fbServicePromise) {
    const url = new URL("../assets/js/firebase-service.js", window.location.href).href;
    fbServicePromise = import(url);
  }
  return fbServicePromise;
}

function escHtml(str) {
  const d = document.createElement("div");
  d.textContent = str == null ? "" : String(str);
  return d.innerHTML;
}

function getSafeRedirectUrl(rawUrl) {
  if (!rawUrl || typeof rawUrl !== "string") return null;
  const trimmed = rawUrl.trim();
  if (!trimmed) return null;
  // Block external protocol redirects or javascript:
  if (/^(https?:|javascript:|data:|vbscript:)/i.test(trimmed)) {
    try {
      const parsed = new URL(trimmed, window.location.origin);
      if (parsed.origin !== window.location.origin) return null;
      if (/\/(sign|register|logout)\.(html|php)/i.test(parsed.pathname)) return null;
      return parsed.pathname + parsed.search + parsed.hash;
    } catch (_) {
      return null;
    }
  }
  if (/\/(sign|register|logout)\.(html|php)/i.test(trimmed)) return null;
  return trimmed;
}

function resolvePostLoginDestination(isAdmin, fallbackCustomerUrl = "../product/product.html") {
  const params = new URLSearchParams(window.location.search);
  const queryRedirect = getSafeRedirectUrl(params.get("redirect"));

  let storedRedirect = null;
  try {
    storedRedirect = getSafeRedirectUrl(sessionStorage.getItem("fc_post_login_redirect"));
    sessionStorage.removeItem("fc_post_login_redirect");
  } catch (_) {}

  const target = queryRedirect || storedRedirect;
  if (target) return target;
  if (isAdmin) return "../Admin/dashboard.html";
  return fallbackCustomerUrl;
}

async function processPendingCartAction(fb, uid) {
  if (!uid) return null;
  try {
    const raw = sessionStorage.getItem("fc_pending_cart_action");
    if (!raw) return null;
    sessionStorage.removeItem("fc_pending_cart_action");
    const pending = JSON.parse(raw);
    if (!pending || !pending.itemId) return null;

    const qty = Number(pending.quantity) > 0 ? Number(pending.quantity) : 1;
    const cartRes = await fb.addOrUpdateCartItem(uid, String(pending.itemId), qty);
    if (cartRes && cartRes.ok) {
      sessionStorage.setItem(
        "fc_just_added_toast",
        cartRes.message || "Item added to your cart! You can continue shopping or proceed to checkout."
      );
    }
    return getSafeRedirectUrl(pending.returnUrl);
  } catch (e) {
    console.warn("Could not process pending cart action after login:", e);
    return null;
  }
}

function initAuthPageBanners() {
  try {
    const params = new URLSearchParams(window.location.search);
    const redirectParam = params.get("redirect");
    const hasPendingCart = Boolean(sessionStorage.getItem("fc_pending_cart_action"));
    const hasStoredRedirect = Boolean(sessionStorage.getItem("fc_post_login_redirect"));

    // Preserve ?redirect= on links between sign.html and register.html
    if (redirectParam) {
      document.querySelectorAll('a[href^="register.html"], a[href^="sign.html"]').forEach((link) => {
        const baseHref = link.getAttribute("href").split("?")[0];
        link.setAttribute("href", `${baseHref}?redirect=${encodeURIComponent(redirectParam)}`);
      });
    }

    const bannerEl = document.getElementById("authRedirectBanner");
    if (bannerEl && (redirectParam || hasPendingCart || hasStoredRedirect)) {
      bannerEl.style.display = "block";
      bannerEl.innerHTML = hasPendingCart
        ? '<i class="bi bi-cart-check-fill me-2"></i><strong>Almost there!</strong> Please sign in to add your selected product to the cart and continue shopping.'
        : '<i class="bi bi-info-circle-fill me-2"></i>Please sign in to your Freshcomart account to continue.';
    }
  } catch (_) {}
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initAuthPageBanners);
} else {
  initAuthPageBanners();
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
      const pendingReturnUrl = res.user ? await processPendingCartAction(fb, res.user.uid) : null;
      const params = new URLSearchParams(window.location.search);
      const hasRedirect = Boolean(
        params.get("redirect") ||
        pendingReturnUrl ||
        sessionStorage.getItem("fc_post_login_redirect")
      );

      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML =
        '<i class="bi bi-check-circle pe-3"></i>Registration Successful! ' +
        (hasRedirect && res.user ? "Redirecting you back to your purchase..." : "Redirecting...");

      setTimeout(function () {
        if (res.user && hasRedirect) {
          const dest = pendingReturnUrl || resolvePostLoginDestination(false, "../product/product.html");
          window.location.href = dest;
        } else {
          const redir = params.get("redirect");
          window.location.href = redir ? `sign.html?redirect=${encodeURIComponent(redir)}` : "sign.html";
        }
      }, 900);
    } else {
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + escHtml(res.message);
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
      const pendingReturnUrl = res.user ? await processPendingCartAction(fb, res.user.uid) : null;
      const dest = pendingReturnUrl || resolvePostLoginDestination(Boolean(res.isAdmin), "../product/product.html");

      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Login Successful! Redirecting...';
      setTimeout(function () {
        window.location.href = dest;
      }, 750);
    } else {
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + escHtml(res.message);
    }
  } catch (err) {
    msgDiv.style.display = "block";
    msgDiv.className = "alert alert-danger";
    msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>Login failed. Please try again.';
  }
}

window.signup = signup;
window.signIn = signIn;
