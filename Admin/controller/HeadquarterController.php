<?php

include 'modals/HeadquarterModel.php';

class HeadquarterController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new HeadquarterModel($con);
    }

    public function index()
    {
        $ROW = null;
        $states = $this->model->getStates();
        $list   = $this->model->getAll();

        include 'view/headquarter/index.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getById($id);

        $states = $this->model->getStates();
        $list   = $this->model->getAll();
        $districts = null;
        if (isset($ROW['state']) && !empty($ROW['state'])) {
            $districts = $this->model->getDistrictsByState($ROW['state']);
        }

        include 'view/headquarter/index.php';
    }

    public function delete($id)
    {
        $this->model->delete($id);

        header("Location:".BASE_URL."headquarter");
        exit;
    }

    public function store()
{
    $this->model->insert($_POST);

    header("Location: ".BASE_URL."headquarter");
    exit;
}

public function update($id)
{
    $this->model->update($id, $_POST);

    header("Location: ".BASE_URL."headquarter?success=1");
    exit;
}
}