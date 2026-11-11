function contactUs() {
  var contactName = document.getElementById("con_name").value;
  var email = document.getElementById("con_email").value;
  var subject = document.getElementById("con_content").value;
  var message = document.getElementById("con_message").value;

  var f = new FormData();
  f.append("contactName", contactName);
  f.append("email", email);
  f.append("subject", subject);
  f.append("message", message);
  var r = new XMLHttpRequest();
  r.onreadystatechange = function () {
    if (r.readyState == 4 && r.status == 200) {
      var t = r.responseText;
      var msgDiv = document.getElementById("msgdiv");
      msgDiv.style.display = "block";
      if (t.trim() === "success") {
        // Show beautiful success message
        msgDiv.className = "alert alert-success";
        msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Contact Message Sent Successfully!';
        // Clear all input fields
        document.getElementById("contactForm").reset();
        // Auto-hide message after 3 seconds
        setTimeout(function () {
          msgDiv.style.display = "none";
        }, 3000);
      } else {
        msgDiv.className = "alert alert-danger";
        msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + t;
      }
    }
  }
  r.open("POST", "contact-usProcess.php", true);
  r.send(f);

}

var selectedProduct = null;
var selectedPrice = 0;
var itemQty = 1;

function checkLogin(productId, price) {
  if (isLoggedIn) {
    selectedProduct = productId;
    selectedPrice = parseFloat(price);
    itemQty = 1;
    document.getElementById('itemQty').innerText = itemQty;
    document.getElementById('addMoreModal').style.display = 'flex';
  } else {
    openPopup();
  }
}

function changeQty(delta) {
  itemQty += delta;
  if (itemQty < 1) itemQty = 1;
  document.getElementById('itemQty').innerText = itemQty;
}

function confirmAddToCart() {
  var totalPrice = itemQty * selectedPrice;
  var formData = new FormData();
  formData.append('item_id', selectedProduct);
  formData.append('quantity', itemQty);
  formData.append('price', totalPrice);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', './productProcess.php', true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      var resp = xhr.responseText.trim();
      if (xhr.status === 200 && /^Item/.test(resp)) {
        showToast(resp, 'success');
        if (typeof window.refreshMiniCart === 'function') window.refreshMiniCart(false);
      } else {
        showToast(resp || 'Error adding to cart.', 'error');
      }
      closeAddMoreModal();
    }
  };
  xhr.send(formData);
}


function placeOrder() {
  // Purchase method (set by proceed.php): 'cod' (default) or 'pickup'
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

  var streetAddress1 = document.getElementById("streetAddress1").value;
  var streetAddress2 = document.getElementById("streetAddress2").value;
  var town = document.getElementById("town").value;

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
    if (window.storeInfo) {
      streetAddress1 = window.storeInfo.address;
      streetAddress2 = "";
      town = window.storeInfo.town;
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

  var user_id = window.sessionUserId || null;
  var total_amount = window.totalAmount || null;
  var delivery_fee = isPickup ? 0 : (window.deliveryFee || null);
  var orderItems = window.orderItems || null;
  var csrfToken = window.csrfToken || '';

  var f = new FormData();
  f.append('customer_name', customerName);
  f.append('customer_phone', customerPhone);
  f.append('streetAddress1', streetAddress1);
  f.append('streetAddress2', streetAddress2);
  f.append('town', town);
  f.append("user_id", user_id);
  f.append("total_amount", total_amount);
  f.append("delivery_fee", delivery_fee);
  f.append("csrf_token", csrfToken);

  // Purchase method & notes
  f.append("purchase_method", isPickup ? "pickup" : "cod");
  f.append("order_method", isPickup ? "pickup" : "cod");
  var payEl = document.querySelector('input[name="payment"]:checked');
  f.append("payment_method", payEl ? payEl.value : (isPickup ? "store" : "cod"));
  f.append("delivery_notes", (isPickup ? (pickupNotesEl ? pickupNotesEl.value : "") : (deliveryNotesEl ? deliveryNotesEl.value : "")));
  f.append("instructions", (isPickup ? (pickupNotesEl ? pickupNotesEl.value : "") : (deliveryNotesEl ? deliveryNotesEl.value : "")));

  if (isPickup) {
    f.append("pickup_date", pickupDateEl ? pickupDateEl.value : "");
    f.append("pickup_time_slot", pickupSlotEl ? pickupSlotEl.value : "");
    f.append("pickup_slot", pickupSlotEl ? pickupSlotEl.value : "");
  }

  // Using [] syntax so PHP treats these as arrays automatically
  if (orderItems && Array.isArray(orderItems)) {
    orderItems.forEach(function (item) {
      f.append('item_id[]', item.item_id);
      f.append('quantity[]', item.quantity);
      f.append('subtotal[]', item.subtotal);
      f.append('item_price[]', item.item_price);
    });
  }

  var placeBtn = document.querySelector('button[onclick="placeOrder()"]');
  if (placeBtn) { placeBtn.disabled = true; placeBtn.textContent = 'Placing Order…'; }

  var r = new XMLHttpRequest();
  r.onreadystatechange = function () {
    if (r.readyState == 4 && r.status == 200) {
      var t = r.responseText.trim();
      var msgDiv = document.getElementById("msgdiv");
      msgDiv.style.display = "block";

      if (t == "success" || t.indexOf("success:") === 0) {
        msgDiv.className = "alert alert-success";
        msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Order placed successfully! Redirecting…';
        var redirectUrl = "order-confirmation.php";
        if (t.indexOf("success:") === 0) {
          var oid = t.split(":")[1];
          redirectUrl = "order-confirmation.php?order_id=" + encodeURIComponent(oid);
        }
        setTimeout(function () {
          window.location.href = redirectUrl;
        }, 1200);
      } else {
        if (placeBtn) { placeBtn.disabled = false; placeBtn.textContent = 'Place Order'; }
        msgDiv.className = "alert alert-danger";
        msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + t;
      }
    }
  }
  r.open("POST", "proceedProcess.php", true);
  r.send(f);
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