<?php
include 'modals/ProductModel.php';

class ProductController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new ProductModel($con);
    }

    public function index()
    {
        $ROW  = null;
        $list = $this->model->getAll();
        include 'view/inventory/product.php';
    }

    public function edit($id)
    {
        $ROW  = $this->model->getById($id);
        $list = $this->model->getAll();
        include 'view/inventory/product.php';
    }

    public function store()
    {
        if (mysqli_num_rows($this->model->checkDuplicate($_POST['product_name'])) > 0) {
            header("Location: " . BASE_URL . "products?duplicate=1");
            exit;
        }

        $status = in_array($_POST['status'], ['Active', 'Inactive']) ? $_POST['status'] : 'Active';
        $success = $this->model->insert($_POST, $status);

        header("Location: " . BASE_URL . "products?success=created");
        exit;
    }

    public function update($id)
    {
        if (mysqli_num_rows($this->model->checkDuplicate($_POST['product_name'], $id)) > 0) {
            header("Location: " . BASE_URL . "products?duplicate=1");
            exit;
        }

        $status = in_array($_POST['status'], ['Active', 'Inactive']) ? $_POST['status'] : 'Active';
        $success = $this->model->update($id, $_POST, $status);

        header("Location: " . BASE_URL . "products?success=updated");
        exit;
    }

    public function delete($id)
    {
        $this->model->delete($id);
        header("Location: " . BASE_URL . "products?deleted=1");
        exit;
    }


    public function import() // Remove the $file argument
{
    // Pull the file directly from the global array
    $file = $_FILES['import_file'] ?? null;

    if (!$file || empty($file['name'])) {
        header("Location: " . BASE_URL . "products?import_error=nofile");
        exit;
    }

    // Your existing logic...
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tmp = $file['tmp_name'];
    $dataRows = [];

    // ... (rest of your existing code) ...

    $result = ['imported' => $inserted, 'skipped' => $skipped];
    
    // Perform the redirect directly inside the controller
    header("Location: " . BASE_URL . "products?imported={$result['imported']}&skipped={$result['skipped']}");
    exit;
}


    private function readXLSX($file) {
         $zip = new ZipArchive();
            if ($zip->open($file) !== true) return [];

            // 1. Shared strings table
            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml) {
                $ss = simplexml_load_string($ssXml);
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $str = '';
                        foreach ($si->r as $r) $str .= (string)$r->t;
                        $sharedStrings[] = $str;
                    }
                }
            }

            // 2. Sheet data
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if (!$sheetXml) return [];

            $sheet = simplexml_load_string($sheetXml);
            $rows  = [];

            foreach ($sheet->sheetData->row as $xRow) {
                $cellRefs = [];
                foreach ($xRow->c as $c) $cellRefs[] = (string)$c['r'];
                if (empty($cellRefs)) continue;

                $maxCol = max(array_map('xlsxColIndex', $cellRefs));
                $row    = array_fill(0, $maxCol + 1, '');

                foreach ($xRow->c as $c) {
                    $idx = xlsxColIndex((string)$c['r']);
                    $val = (string)$c->v;
                    if ((string)$c['t'] === 's') $val = $sharedStrings[(int)$val] ?? '';
                    $row[$idx] = trim($val);
                }
                $rows[] = $row;
            }
            return $rows;
            }
}