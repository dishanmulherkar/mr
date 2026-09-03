<?php

include 'modals/LoginModel.php';

class LoginController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new LoginModel($con);
    }

    public function index()
    {
        // Check if cookies exist and pass them to the view
        $rem_email = isset($_COOKIE['rudradeo_user']) ? $_COOKIE['rudradeo_user'] : '';
        $rem_pass  = isset($_COOKIE['rudradeo_pass']) ? $_COOKIE['rudradeo_pass'] : '';
        
        include 'view/login/index.php';
    }

    public function authenticate()
    {
        header('Content-Type: application/json');

        if($_SERVER['REQUEST_METHOD']!='POST')
        {
            echo json_encode([
                'status'=>'error',
                'message'=>'Invalid Request'
            ]);
            exit;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        $user = $this->model->getUserByEmail($email);

        if($user && $password == $user['password'])
        {
            $_SESSION['mr_id'] = $user['m_id'];
            $_SESSION['mr_name'] = $user['hq_name'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['state_id'] = $user['state']; 
            $_SESSION['hq_id'] = $user['hq_id']; 

            // Handle Remember Me Cookies
            if ($remember) {
                // Set cookies for 30 days
                setcookie('rudradeo_user', $email, time() + (86400 * 30), "/");
                setcookie('rudradeo_pass', $password, time() + (86400 * 30), "/");
            } else {
                // Destroy cookies if checkbox is not checked
                setcookie('rudradeo_user', '', time() - 3600, "/");
                setcookie('rudradeo_pass', '', time() - 3600, "/");
            }

            echo json_encode([
                'status'=>'success',
                'message'=>'Login Successful',
                'redirect'=>'dashboard'
            ]);
        }
        else
        {
            echo json_encode([
                'status'=>'error',
                'message'=>'Invalid Email or Password'
            ]);
        }
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