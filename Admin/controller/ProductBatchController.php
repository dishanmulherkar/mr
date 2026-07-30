<?php
include 'modals/ProductbatchModel.php';

class BatchProductController
{
    private $model;

    public function __construct($con)
    {
        $this->model = new BatchProductModel($con);
    }

    // --- Standard Controller Methods ---

    public function index()
    {
        $ROW = null;
        $products = $this->model->getProductList();
        $list = $this->model->getAll();
        include 'view/inventory/batch_product.php';
    }

    public function edit($id)
    {
        $ROW = $this->model->getById($id);
        $products = $this->model->getProductList();
        $list = $this->model->getAll();
        include 'view/inventory/batch_product.php';
    }

    public function store()
    {
        $this->model->insert($_POST);
        header("Location: " . BASE_URL . "product_batch?success=1");
        exit;
    }

    public function update($id)
    {
        $this->model->update($id, $_POST);
        header("Location: " . BASE_URL . "product_batch?success=1");
        exit;
    }

    public function toggle($id)
    {
        $this->model->toggleStatus($id);
        header("Location: " . BASE_URL . "product_batch?toggled=1");
        exit;
    }

    public function delete($id)
    {
        $this->model->delete($id);
        header("Location: " . BASE_URL . "product_batch?deleted=1");
        exit;
    }

    // --- Helper Methods for Import ---

    public function import()
    {
        $file = $_FILES['import_file'] ?? null;

        if (!$file || empty($file['name'])) {
            header("Location: " . BASE_URL . "product_batch?import_error=nofile");
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dataRows = [];

        if ($ext == 'csv') {

            $fh = fopen($file['tmp_name'], 'r');
            fgetcsv($fh); // Skip Header

            while (($r = fgetcsv($fh)) !== false) {
                $dataRows[] = $r;
            }

            fclose($fh);

        } elseif ($ext == 'xlsx') {

            $dataRows = $this->readXLSX($file['tmp_name']);
            array_shift($dataRows); // Skip Header

        } else {

            header("Location: " . BASE_URL . "product_batch?import_error=invalid");
            exit;
        }

        $inserted = 0;
        $skipped  = 0;

        foreach ($dataRows as $r) {

            $product_code = trim($r[0] ?? '');
            $product_name = trim($r[1] ?? ''); // For information only (ignored)
            $batch_no     = trim($r[2] ?? '');
            $pts          = floatval($r[3] ?? 0);
            $state_name   = trim($r[4] ?? '');
            $status       = trim($r[5] ?? '1');

            // Get Product ID
            $product_id = $this->model->getProductIdByCode($product_code);


            // Get State ID
            $state_id = $this->model->getStateIdByName($state_name);

            if (
                !$product_id ||
                !$state_id ||
                empty($batch_no) ||
                $this->model->isBatchDuplicate($product_id, $state_id, $batch_no)
            ) {
                $skipped++;
                continue;
            }

            if (
                $this->model->insertBatch(
                    $product_id,
                    $state_id,
                    $batch_no,
                    $pts,
                    $status
                )
            ) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        header("Location: " . BASE_URL . "product_batch?imported=$inserted&skipped=$skipped");
        exit;
    }

    private function xlsxColIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($cellRef), $m);
        $n = 0;
        foreach (str_split($m[1]) as $ch) $n = $n * 26 + ord($ch) - 64;
        return $n - 1;
    }

    private function readXLSX(string $file): array
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) return [];

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

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetXml) return [];

        $sheet = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($sheet->sheetData->row as $xRow) {
            $cellRefs = [];
            foreach ($xRow->c as $c) $cellRefs[] = (string)$c['r'];
            if (empty($cellRefs)) continue;

            // CORRECTED: Call method using [$this, 'methodName']
            $maxCol = max(array_map([$this, 'xlsxColIndex'], $cellRefs));
            $row = array_fill(0, $maxCol + 1, '');

            foreach ($xRow->c as $c) {
                // CORRECTED: Call using $this->methodName()
                $idx = $this->xlsxColIndex((string)$c['r']);
                $val = (string)$c->v;
                if ((string)$c['t'] === 's') $val = $sharedStrings[(int)$val] ?? '';
                $row[$idx] = trim($val);
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public function downloadSample()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_batch_sample.csv"');

        $fp = fopen('php://output', 'w');

        // Header
        fputcsv($fp, [
            'Product Code',
            'Product Name',
            'Batch No',
            'PTS',
            'State',
            'Status'
        ]);

        // Sample Rows
        fputcsv($fp, ['P001', 'Paracetamol 650mg Tab', 'A001', '120', 'Gujarat', 'Active']);
        fputcsv($fp, ['P001', 'Paracetamol 650mg Tab', 'A002', '125', 'Bihar', 'Active']);
        fputcsv($fp, ['P002', 'Amoxicillin 500mg Cap', 'B001', '95', 'Maharashtra', 'Active']);
        fputcsv($fp, ['P003', 'Azithromycin 500mg Tab', 'C001', '180', 'Nepal', 'Active']);
        fputcsv($fp, ['P004', 'Vitamin C 500mg Tab', 'D001', '60', 'Gujarat', 'Inactive']);

        fclose($fp);
        exit;
    }
}