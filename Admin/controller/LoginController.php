<?php

class LoginController {
    private $loginModel;
     public function __construct()
    {
        require_once "modals/LoginModel.php";
        $this->loginModel = new LoginModel();
    }



    public function index() 
    {
           include_once 'view/Auth/login.php';
    }

    public function authenticate()
    {
        header('Content-Type: application/json');

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

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

        $_SESSION['admin_id'] = $user['admin_id'];
        $_SESSION['admin_name'] = $user['admin_name'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['stockist_id']  =  $user['stockist_id'];

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
