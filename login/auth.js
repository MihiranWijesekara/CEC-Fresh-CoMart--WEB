
function signup() {
    console.log("auth.js file worked");

    var fName = document.getElementById("firstName").value;
    console.log(fName);
    var lName = document.getElementById("lastName").value;
    console.log(lName);
    var email = document.getElementById("email").value;
    console.log(email);
    var phoneNumber = document.getElementById("phone").value;
    console.log(phoneNumber);
    var password = document.getElementById("password").value;
    console.log(password);
    var confirmPassword = document.getElementById("confirmPassword").value;
    console.log(confirmPassword); 

    var f = new FormData();
    f.append("firstName",fName);
    f.append("lastName",lName);
    f.append("email",email);
    f.append("phoneNumber",phoneNumber);
    f.append("password",password);
    f.append("confirmPassword",confirmPassword);

    var r = new XMLHttpRequest();
    r.onreadystatechange = function () {
        if (r.readyState == 4 && r.status == 200) {
            var t = r.responseText;
            var msgDiv = document.getElementById("msgdiv");
            msgDiv.style.display = "block";
            if (t == "success") {
                // Clear all input fields
                document.getElementById("registerForm").reset();
                // Show beautiful success message
                msgDiv.className = "alert alert-success";
                msgDiv.innerHTML = '<i class="bi bi-check-circle pe-3"></i>' + "Registration Successful!";
          
            } else {
                msgDiv.className = "alert alert-danger";
                msgDiv.innerHTML = '<i class="bi bi-exclamation-circle pe-3"></i>' + t;
            }
        }
    }
    r.open("POST", "../login/registerProcess.php", true);
    r.send(f);
}
