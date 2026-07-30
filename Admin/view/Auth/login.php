<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rudradeo Pharmaceutical Login</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/login.css">
    
    <style>
        #msg{

margin:15px 0;
padding:12px;
border-radius:5px;
display:none;

}

.success{

display:block;
background:#d4edda;
color:#155724;
border:1px solid #c3e6cb;

}

.error{

display:block;
background:#f8d7da;
color:#721c24;
border:1px solid #f5c6cb;

}

#forgotPasswordModal {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.6);
    align-items:center;
    justify-content:center;
    z-index:9999;
}

#forgotPasswordModal.show {
    display:flex;
}

#forgotDialog {
    background:#fff;
    width:min(90%, 420px);
    padding:24px;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    text-align:center;
    position:relative;
}

#forgotDialog .close-btn {
    position:absolute;
    top:10px;
    right:12px;
    cursor:pointer;
    font-size:22px;
    color:#666;
}

#forgotDialog button {
    margin-top:14px;
    padding:10px 16px;
    border:none;
    border-radius:6px;
    background:#0d6efd;
    color:#fff;
    cursor:pointer;
}
    </style>

<style>
    .hscroll-line {

  right: 36%;
  height: 1px;
  position: absolute;
  overflow: hidden;
  width: 23%;
  transform: rotate(90deg);
  top: 50%;
  background: #d4d4d4;
 
}


.hscroll-line::before,
.hscroll-line::after {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	content: ""
}

.hscroll-line:before {
	background: #ffffff3b;
}

.hscroll-line::after {
	background: #000000;
	animation: move 3s infinite
}

@keyframes move {
	0% {
		transform: translate3d(-200%, 0, 0)
	}
	60% {
		transform: translate3d(100%, 0, 0)
	}
	100% {
		transform: translate3d(100%, 0, 0)
	}
}
</style>


</head>
<body>
    <div class="container">
        <!-- Left Side with Pharmaceutical Art -->
        <div class="left-side">
            <img  style="width:500px"  src="<?= BASE_URL ?>config/image/logo.jpg" alt="Pharmaceutical Illustrations" class="art-image">
        </div>

        <div>
           <span class="hscroll-line"></span>
        </div>

        <!-- Right Side with Login Form -->
        <div class="right-side">
            <div class="login-box">
                <!-- Company Logo -->

                <h2>Sign In</h2>

                <form id="loginForm" method="POST">
                    <!-- User Name Field -->
                    <div class="input-group">
                        <label for="username">User Name or Email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username or email">
                    </div>

                    <!-- Password Field -->
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password">
                            <span class="toggle-password">
                                <!-- Example SVG icon for the eye (visible) -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#757575">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <!-- Forgot Password Link -->
                    <div class="forgot-password">
                        <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                    <div id="msg"></div>
                    <!-- Login Button -->
                    <button type="submit" class="login-btn">Login</button>
                </form>

                
            </div>
        </div>
    </div>
    <div id="forgotPasswordModal">
        <div id="forgotDialog">
            <span class="close-btn" id="closeForgotModal">&times;</span>
            <h3>Forgot Password</h3>
            <p>Please Contact Rudradeo Admin Person.</p>
            <button type="button" id="closeForgotModalBtn">Close</button>
        </div>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('forgotPasswordModal');
    var link = document.getElementById('forgotPasswordLink');
    var closeBtn = document.getElementById('closeForgotModal');
    var closeBtn2 = document.getElementById('closeForgotModalBtn');

    function openModal() {
        if (modal) {
            modal.classList.add('show');
        }
    }

    function closeModal() {
        if (modal) {
            modal.classList.remove('show');
        }
    }

    if (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (closeBtn2) {
        closeBtn2.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
});

$(document).ready(function () {
    $("#loginForm").submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: "<?= BASE_URL ?>login/authenticate",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            beforeSend: function () {
                $(".login-btn").html("Please Wait...");
                $(".login-btn").prop("disabled", true);
            },
            success: function (res) {
                if (res.status) {
                    $("#msg")
                        .removeClass("success error")
                        .addClass("success")
                        .html(res.message)
                        .fadeIn();

                    setTimeout(function () {
                        window.location = "<?= BASE_URL ?>dashboard";
                    }, 1000);
                } else {
                    $("#msg")
                        .removeClass("success error")
                        .addClass("error")
                        .html(res.message)
                        .fadeIn();
                }
            },
            complete: function () {
                $(".login-btn").html("Login");
                $(".login-btn").prop("disabled", false);
            }
        });
    });
});
</script>