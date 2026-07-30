<?php 
include 'modals/AdminModel.php';
class AdminController
{
   private $model;

    public function __construct($con)
    {
        $this->model = new AdminModel($con);
    }

    public function index()
    {
        $ROW  = null;
       
        $query = $this->model->getAll();
        $states = $this->model->getStates();
        include 'view/admin/admin.php';
    }

     public function edit($id)
    {
        $ROW = $this->model->getById($id);

        $selectedStates = [];

        if($ROW)
        {
            $selectedStates = $this->model->getAdminStates($id);
        }

        $states = $this->model->getStates();

        $query = $this->model->getAll();

        include 'view/admin/admin.php';
    }

        public function store()
        {
            $data = [

                'admin_name' => $_POST['admin_name'],
                'user_name'  => $_POST['user_name'],
                'email'      => $_POST['email'],
                'mobile'     => $_POST['mobile'],
                'password'   => $_POST['password'],
                'role'       => $_POST['role'],
                'status'     => $_POST['status']

            ];

            // First insert admin
            $admin_id = $this->model->insertAdmin($data);

            // Then insert assigned states
            if ($admin_id && !empty($_POST['state_id'])) {
                $this->model->insertStates($admin_id, $_POST['state_id']);
            }

            header("Location: " . BASE_URL . "admin");
            exit;
        }

    public function update($id)
    {
        $data = [

            'admin_name' => $_POST['admin_name'],
            'user_name'  => $_POST['user_name'],
            'email'      => $_POST['email'],
            'mobile'     => $_POST['mobile'],
            'password'   => $_POST['password'],
            'role'       => $_POST['role'],
            'status'     => $_POST['status']

        ];

        $this->model->updateAdmin($id,$data);

        $this->model->deleteStates($id);

        $this->model->insertStates($id,$_POST['state_id']);

        header("Location: ".BASE_URL."admin");
        exit;
    }

  
    
   

   
    
}