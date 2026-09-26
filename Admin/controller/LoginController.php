<?php

class LoginController {
    private $loginModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once "modals/LoginModel.php";
        $this->loginModel = new LoginModel();
    }

    public function index() 
    {
        if (!empty($_SESSION['admin_id'])) {
            header("Location: " . BASE_URL . "dashboard");
            exit;
        }

        include_once 'view/Auth/login.php';
    }

    public function authenticate()
    {
        header('Content-Type: application/json');

        $username    = trim($_POST['username'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $remember_me = isset($_POST['remember_me']);

        if ($username == "" || $password == "") {
            echo json_encode([
                "status" => false,
                "message" => "Username and Password are required."
            ]);
            exit;
        }

        $user = $this->loginModel->login($username);

        if (!$user) {
            echo json_encode([
                "status" => false,
                "message" => "User not found."
            ]);
            exit;
        }

        if ($password !== $user['password']) {
            echo json_encode([
                "status" => false,
                "message" => "Incorrect Password."
            ]);
            exit;
        }

        // Handle Remember Me (Stores for 30 days)
        if ($remember_me) {
            setcookie('remember_username', $username, time() + (86400 * 30), "/");
            setcookie('remember_password', $password, time() + (86400 * 30), "/");
        } else {
            // Delete cookies if unchecked
            setcookie('remember_username', '', time() - 3600, "/");
            setcookie('remember_password', '', time() - 3600, "/");
        }

        $_SESSION['admin_id']       = $user['admin_id'];
        $_SESSION['admin_name']     = $user['admin_name'];
        $_SESSION['admin_role']     = $user['role'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['stockist_id']    = $user['stockist_id'];

        echo json_encode([
            "status" => true,
            "message" => "Login Successful"
        ]);
    }

    public function logout()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();

        header("Location: " . BASE_URL . "login");
        exit;
    }
}