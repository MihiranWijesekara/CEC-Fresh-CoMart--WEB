

function contactUs() { 
    var contactName = document.getElementById("con_name").value;
    console.log(contactName);
    var email = document.getElementById("con_email").value;
    console.log(email);
    var subject = document.getElementById("con_content").value;
    console.log(subject);
    var message = document.getElementById("con_message").value;
    console.log(message);

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