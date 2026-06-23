function contactUs() { 
    var contactName = document.getElementById("con_name").value;
    var email = document.getElementById("con_email").value;
    var subject = document.getElementById("con_content").value;
    var message = document.getElementById("con_message").value;

    var f = new FormData();
    f.append("contactName",contactName);
    f.append("email",email);
    f.append("subject",subject);
    f.append("message",message);
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
                setTimeout(function() {
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
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        var userIdMsg = '';
        if (typeof sessionUserId !== 'undefined' && sessionUserId) {
          userIdMsg = '\nSession User ID: ' + sessionUserId;
        }
        alert('Added ' + itemQty + ' item(s) to cart!\nProduct ID: ' + selectedProduct +
          '\nQuantity: ' + itemQty +
          '\nTotal price: ' + totalPrice +
          userIdMsg +
          '\nServer response: ' + xhr.responseText);
      } else {
        alert('Error adding to cart.');
        console.error('Error adding to cart:', xhr.statusText);
      }
      closeAddMoreModal();
    }
  };
  xhr.send(formData);
}


function placeOrder() {
    var streetAddress1 = document.getElementById("streetAddress1").value;
    var streetAddress2 = document.getElementById("streetAddress2").value;
    var town = document.getElementById("town").value;

    var user_id = window.sessionUserId || null;
    var total_amount = window.totalAmount || null;
    var delivery_fee = window.deliveryFee || null;
    var orderItems = window.orderItems || null;

    var f = new FormData();
    f.append('streetAddress1', streetAddress1);
    f.append('streetAddress2', streetAddress2);
    f.append('town', town);
    f.append("user_id", user_id);
    f.append("total_amount", total_amount);
    f.append("delivery_fee", delivery_fee);

    // Using [] syntax so PHP treats these as arrays automatically
    if (orderItems && Array.isArray(orderItems)) {
        orderItems.forEach(function(item) {
            f.append('item_id[]', item.item_id);
            f.append('quantity[]', item.quantity);
            f.append('subtotal[]', item.subtotal);
            f.append('item_price[]', item.item_price);
        });
    }

    var r = new XMLHttpRequest();
    r.onreadystatechange = function () {
        if (r.readyState == 4 && r.status == 200) {
            var t = r.responseText.trim();
            var msgDiv = document.getElementById("msgdiv");
            msgDiv.style.display = "block";
            
            if (t == "success") {
                msgDiv.className = "alert alert-success";
                msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>Order placed successfully! Redirecting...';
                setTimeout(function() {
                    window.location.href = "product/product.php";
                }, 2000);
            } else {
                msgDiv.className = "alert alert-danger";
                msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + t;
            }
        }
    }
    r.open("POST", "proceedProcess.php", true);
    r.send(f);
}