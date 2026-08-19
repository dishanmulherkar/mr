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

        $email=$_POST['email'];
        $password=$_POST['password'];

        $user=$this->model->getUserByEmail($email);

        if($user && $password==$user['password'])
        {
            $_SESSION['mr_id']=$user['m_id'];
            $_SESSION['mr_name']=$user['hq_name'];
            $_SESSION['status']=$user['status'];
            $_SESSION['state_id']= $user['state']; 
            $_SESSION['hq_id']= $user['hq_id']; 

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