<?php 
include 'modals/NotificationModel.php';
class NotificationController
{
   private $model;

    public function __construct($con)
    {
        $this->model = new NotificationModel($con);
        date_default_timezone_set('Asia/Kolkata');
    }

    public function index()
    {
        $ROW  = null;
        $hq = $this->model->getHQ();
        $states = $this->model->getStates();
        $query = $this->model->getAll();
        $controller = $this;
        include 'view/Notification/notification.php';
    }


    public function edit($id)
    {
        $ROW = $this->model->getById($id);
        $state_id = $ROW['state_id'];
        $hq = $this->model->getHQByState($state_id);
        $states = $this->model->getStates();
        $query = $this->model->getAll();
        $controller = $this;
        include 'view/Notification/notification.php';
    }

    public function getHQByState()
    {
        if (!isset($_POST['state_id'])) {
            exit;
        }

        $state_id = (int)$_POST['state_id'];

        $selected_ids = [];

        if (!empty($_POST['selected_ids'])) {
            $selected_ids = explode(',', $_POST['selected_ids']);
            $selected_ids = array_map('trim', $selected_ids);
        }

        $hq = $this->model->getHQByState($state_id);

        while($row = mysqli_fetch_assoc($hq))
        {
            $selected = in_array($row['m_id'], $selected_ids) ? 'selected' : '';

            echo '<option value="'.$row['m_id'].'" '.$selected.'>';
            echo htmlspecialchars($row['hq_name']);
            echo '</option>';
        }

        exit;
    }

    public function store()
    {
        $this->model->create($_POST);
        header("Location: " . BASE_URL . "notification?success=1");
        exit;
    }

    public function update($id)
    {
        $this->model->update($id, $_POST);
        header("Location: " . BASE_URL . "notification?success=1");
        exit;
    }

    public function getNames($ids)
    {
        return $this->model->getHqNames($ids);
    }
    
     public function delete($id)
    {
        $this->model->delete($id);

        header("Location: " . BASE_URL . "notification?success=1");
        exit;
    }
    
    
}