function onSignIn(googleUser) {
    var profile = googleUser.getBasicProfile();
    var id_token = googleUser.getAuthResponse().id_token;
  
    fetch('php/google_login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id_token: id_token })
    })
    .then(res => res.text())
    .then(data => {
      if (data === "success") {
        window.location.href = "dashboard.html";
      } else {
        alert("Google Login failed.");
      }
    });
  }
  