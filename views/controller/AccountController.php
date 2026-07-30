<?php

require_once 'modals/AccountModel.php';

class AccountController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new AccountModel($con);
    }

    public function index()
    {
        $mr_id = $_SESSION['mr_id'];

        if($_SERVER['REQUEST_METHOD']=='POST')
        {
            $action=$_POST['action'] ?? '';

            if($action=='update_profile')
            {
                $this->updateProfile($mr_id);
            }

            if($action=='change_password')
            {
                $this->changePassword($mr_id);
            }
        }
        $mr=$this->model->getMR($mr_id);

        $districts=$this->model->getDistricts($mr['state']);
        $totalStockist=$this->model->totalStockist($mr_id);
        $totalCustomer=$this->model->totalCustomer($mr_id);
        $initials = $this->initials($mr['mr_name']);


        include 'view/Account/account.php';
    }

    private function updateProfile($mr_id)
    {
        $data=[];

        $data['mr_name']=trim($_POST['mr_name']);
        $data['mobile']=trim($_POST['mobile']);
        $data['email']=trim($_POST['email']);
        $data['district']=$_POST['district'];
        $data['pincode']=trim($_POST['pincode']);
        $data['address']=trim($_POST['address']);

        if($this->model->updateProfile($mr_id,$data))
        {
            echo json_encode([
                'success'=>true,
                'message'=>'Profile updated successfully.'
            ]);
        }
        else
        {
            echo json_encode([
                'success'=>false,
                'message'=>'Unable to update profile.'
            ]);
        }

        exit;
    }

    private function changePassword($mr_id)
    {
        $current=$_POST['current_password'];
        $new=$_POST['new_password'];

        if(!$this->model->verifyPassword($mr_id,$current))
        {
            echo json_encode([
                'success'=>false,
                'message'=>'Current password is incorrect.'
            ]);
            exit;
        }

        if($this->model->changePassword($mr_id,$new))
        {
            echo json_encode([
                'success'=>true,
                'message'=>'Password updated successfully.'
            ]);
        }
        else
        {
            echo json_encode([
                'success'=>false,
                'message'=>'Unable to update password.'
            ]);
        }

        exit;
    }

    public function initials($name)
    {
        $parts=explode(' ',trim($name));

        $initials=strtoupper(substr($parts[0],0,1));

        if(isset($parts[1]))
        {
            $initials.=strtoupper(substr($parts[1],0,1));
        }

        return $initials;
    }

}