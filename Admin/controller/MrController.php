<?php

include 'modals/MrModel.php';

class MrController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new MrModel($con);
    }

    public function index()
    {
        $ROW = null;
        $states = $this->model->getStates();
        $list   = $this->model->getAll();
        $hq  = $this->model->getHQ();

        include 'view/Mr/index.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getById($id);

        $states = $this->model->getStates();
        $list   = $this->model->getAll();
        $hq  = $this->model->getHQ();
        $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }

        include 'view/Mr/index.php';
    }

    public function delete($id)
    {
        $this->model->delete($id);

        header("Location:".BASE_URL."mr");
        exit;
    }

    public function store()
{
    $this->model->insert($_POST);

    header("Location: ".BASE_URL."mr");
    exit;
}

public function update($id)
{
    $this->model->update($id, $_POST);

    header("Location: ".BASE_URL."mr?success=1");
    exit;
}
}