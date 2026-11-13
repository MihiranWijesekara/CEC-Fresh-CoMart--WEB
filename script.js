/**
 * Global Storefront Utilities & Checkout/Contact Actions (Firebase Edition)
 */

var fbServiceCache = null;
function loadGlobalFirebaseService() {
  if (!fbServiceCache) {
    var base = typeof window.FC_BASE === "string" ? window.FC_BASE : "";
    var url = new URL(base + "assets/js/firebase-service.js", window.location.href).href;
    fbServiceCache = import(url);
  }
  return fbServiceCache;
}

async function contactUs() {
  var contactName = document.getElementById("con_name").value;
  var email = document.getElementById("con_email").value;
  var subject = document.getElementById("con_content").value;
  var message = document.getElementById("con_message").value;
  var msgDiv = document.getElementById("msgdiv");

  try {
    var fb = await loadGlobalFirebaseService();
    var res = await fb.submitContactInquiry({
      contactName: contactName,
      email: email,
      subject: subject,
      message: message
    });

    msgDiv.style.display = "block";
    if (res.ok) {
      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Contact Message Sent Successfully!';
      document.getElementById("contactForm").reset();
      setTimeout(function () {
        msgDiv.style.display = "none";
      }, 3000);
    } else {
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + (res.message || "Could not send message.");
    }
  } catch (e) {
    msgDiv.style.display = "block";
    msgDiv.className = "alert alert-danger";
    msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>Error sending message. Please try again.';
  }
}

async function placeOrder() {
  var isPickup = (window.orderMethod === 'pickup');
  var customerName = (document.getElementById("customerName") ? document.getElementById("customerName").value : "").trim();
  var customerPhone = (document.getElementById("customerPhone") ? document.getElementById("customerPhone").value : "").trim();

  if (!customerName) {
    alert("Please enter your full name.");
    return;
  }
  if (!customerPhone) {
    alert("Please enter your phone number.");
    return;
  }

  var streetAddress1 = document.getElementById("streetAddress1") ? document.getElementById("streetAddress1").value : "";
  var streetAddress2 = document.getElementById("streetAddress2") ? document.getElementById("streetAddress2").value : "";
  var town = document.getElementById("town") ? document.getElementById("town").value : "";

  var pickupDateEl = document.getElementById("pickupDate");
  var pickupSlotEl = document.querySelector('input[name="pickupSlot"]:checked');
  var deliveryNotesEl = document.getElementById("deliveryNotes");
  var pickupNotesEl = document.getElementById("pickupNotes");

  if (isPickup) {
    if (!pickupSlotEl) {
      alert("Please choose a pickup time slot.");
      return;
    }
    if (!pickupDateEl || !pickupDateEl.value) {
      alert("Please select a pickup date.");
      return;
    }
  } else {
    if (!streetAddress1.trim()) {
      alert("Please enter your street address.");
      return;
    }
    if (!town.trim()) {
      alert("Please enter your town/city.");
      return;
    }
  }

  var placeBtn = document.querySelector('button[onclick="placeOrder()"]');
  if (placeBtn) {
    placeBtn.disabled = true;
    placeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Placing Order…';
  }

  try {
    var fb = await loadGlobalFirebaseService();
    var user = await fb.waitForAuthUser();
    if (!user) {
      window.location.href = "login/sign.html";
      return;
    }

    var notes = isPickup
      ? (pickupNotesEl ? pickupNotesEl.value : "")
      : (deliveryNotesEl ? deliveryNotesEl.value : "");

    var res = await fb.placeOrderTransaction(user.uid, {
      purchase_method: isPickup ? "pickup" : "cod",
      customer_name: customerName,
      customer_phone: customerPhone,
      streetAddress1: streetAddress1,
      streetAddress2: streetAddress2,
      town: town,
      delivery_notes: notes,
      pickup_date: isPickup && pickupDateEl ? pickupDateEl.value : "",
      pickup_time_slot: isPickup && pickupSlotEl ? pickupSlotEl.value : ""
    });

    var msgDiv = document.getElementById("msgdiv");
    msgDiv.style.display = "block";

    if (res.ok) {
      msgDiv.className = "alert alert-success";
      msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Order placed successfully! Redirecting…';
      setTimeout(function () {
        window.location.href = "order-confirmation.html?order_id=" + encodeURIComponent(res.orderId);
      }, 900);
    } else {
      if (placeBtn) {
        placeBtn.disabled = false;
        placeBtn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order';
      }
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + (res.message || "Could not place order.");
    }
  } catch (err) {
    if (placeBtn) {
      placeBtn.disabled = false;
      placeBtn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order';
    }
    var msgDiv = document.getElementById("msgdiv");
    if (msgDiv) {
      msgDiv.style.display = "block";
      msgDiv.className = "alert alert-danger";
      msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + (err.message || "Error placing order.");
    }
  }
}

function showToast(message, type = 'success') {
  var container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '10px';
    document.body.appendChild(container);
  }

  var toast = document.createElement('div');
  toast.style.background = type === 'success' ? '#27b62e' : '#dc3545';
  toast.style.color = '#fff';
  toast.style.padding = '12px 24px';
  toast.style.borderRadius = '8px';
  toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
  toast.style.fontFamily = 'sans-serif';
  toast.style.fontWeight = '600';
  toast.style.fontSize = '14px';
  toast.style.display = 'flex';
  toast.style.alignItems = 'center';
  toast.style.gap = '8px';
  toast.style.opacity = '0';
  toast.style.transform = 'translateY(-20px)';
  toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';

  toast.innerHTML = (type === 'success' ? '✓ ' : '⚠ ') + message;

  container.appendChild(toast);

  setTimeout(function () {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  }, 10);

  setTimeout(function () {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-20px)';
    setTimeout(function () {
      toast.remove();
    }, 300);
  }, 3000);
}

window.contactUs = contactUs;
window.placeOrder = placeOrder;
window.showToast = showToast;
