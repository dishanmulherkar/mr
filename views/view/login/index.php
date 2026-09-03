<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Rudradeo - Login</title>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/style.css">
<style>
  .login-msg{
    display:none;
    padding:12px;
    margin-bottom:15px;
    border-radius:8px;
    text-align:center;
    font-size:14px;
    font-weight:500;
  }
  .login-msg.error{
    background:#fdeaea;
    color:#c62828;
    border:1px solid #f5c2c7;
  }
  .login-msg.success{
    background:#e8f5e9;
    color:#2e7d32;
    border:1px solid #c3e6cb;
  }
  .remember-row {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
    color: #555;
  }
  .remember-row input {
    margin-right: 8px;
  }
</style>
</head>
<body>
<div id="page-login" class="page active">
  <div class="login-card">
    <div class="logo-row">
       <img src="<?= BASE_URL ?>config/img/logo.jpg" alt="rudradeo-logo" width="150" height="auto" />
    </div>
    <h1>Welcome to Rudradeo</h1>
    <p class="sub">Sign in to manage your Sales Data</p>

    <form id="loginForm" method="POST">
    <div class="fgroup">
      <label for="lemail">User ID</label>
      <div class="inp-wrap">
        <span class="iico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 7l10 7 10-7"/></svg></span>
        <!-- Pre-fill from PHP variables if they exist -->
        <input type="text" name="email" id="lemail" placeholder="Hq@rudradeo" value="<?= isset($rem_email) ? htmlspecialchars($rem_email) : '' ?>"/>
      </div>
    </div>
    <div class="fgroup">
      <label for="lpass">Password</label>
      <div class="inp-wrap">
        <span class="iico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <!-- Pre-fill from PHP variables if they exist -->
        <input type="password" name="password" id="lpass" placeholder="Enter your password" value="<?= isset($rem_pass) ? htmlspecialchars($rem_pass) : '' ?>"/>
        <button class="tpass" type="button" onclick="togglePass()" aria-label="Toggle password">
          <svg id="eyeico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>
    
    <!-- Remember Me Checkbox -->
    <div class="remember-row">
      <input type="checkbox" name="remember" id="remember" <?= isset($rem_email) ? 'checked' : '' ?> />
      <label for="remember" style="margin: 0; cursor: pointer;">Remember Me</label>
    </div>

    <div id="loginMsg" class="login-msg" style="display:none;"></div>

    <button class="btn-primary" type="submit">Sign In</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>config/config/script.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#loginForm").submit(function(e){
    e.preventDefault();

    $.ajax({
        url:"<?= BASE_URL ?>login/authenticate",
        type:"POST",
        data:$(this).serialize(),
        dataType:"json",
        success:function(res){
            if(res.status == "success") {
                location.href = res.redirect;
            } else {
                let msgDiv = $('#loginMsg');
                msgDiv.removeClass('success').addClass('error').text(res.message).show();
            }
        }
    });
});
</script>
</body>
</html>