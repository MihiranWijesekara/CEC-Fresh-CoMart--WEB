function productAdd() {
    var itemName = document.getElementById("itemName").value;
    console.log(itemName);
    var itemImageInput = document.getElementById("itemImage");
    console.log(itemImageInput.files.length);
    var price = document.getElementById("price").value;
    console.log(price);
    var unit_value = document.getElementById("unit_value").value;
    console.log(unit_value);
    var unitType = document.getElementById("unit_type").value;
    console.log(unitType);
    var category = document.getElementById("category").value;
    console.log(category);
    var status = document.getElementById("status").value;
    console.log(status);

    var f = new FormData();
    f.append("itemName", itemName);
    if (itemImageInput.files.length > 0) {
        f.append("pi", itemImageInput.files[0]);
    }
    f.append("price", price);
    f.append("unit", unit_value + " " + unitType);
    f.append("category", category);
    f.append("status", status);

    var r = new XMLHttpRequest();
    r.onreadystatechange = function () {
        if (r.readyState == 4) {
            var t = r.responseText;
            var msgDiv = document.getElementById("msgdiv");
            msgDiv.style.display = "block";
            if (r.status == 200 && t == "success") {
                document.getElementById("productForm").reset();
                msgDiv.className = "alert alert-success";
                msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>' + "Product Added Successfully!";
            } else {
                msgDiv.className = "alert alert-danger";
                msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + t;
            }
        }
    };
    r.open("POST", "productAddProcess.php", true);
    r.send(f);
}
