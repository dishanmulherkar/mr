<?php 
include 'modals/DistrictModel.php';
class DistrictController
{
   private $model;

    public function __construct($con)
    {
        $this->model = new DistrictModel($con);
    }

    public function index()
    {
        $ROW = null;
        $states = $this->model->getStates();
        $list   = $this->model->getAll();
        include 'view/Location/district.php';
    }

    // New: Fetch data to populate the form
    public function edit($id)
    {
        $ROW = mysqli_fetch_assoc($this->model->edit($id)); // Fetch the specific row
        $states = $this->model->getStates();
        $list   = $this->model->getAll();
        include 'view/Location/district.php';
    }

    // New: Handle the form update
    public function update($id)
    {
        // Pass empty string for status since district table doesn't use it
        $this->model->update($id, $_POST, ''); 
        header('Location: ' . BASE_URL . 'district');
        exit;
    }

    public function store()
    {
        // Pass empty string for status
        $this->model->insert($_POST, '');
        header('Location: ' . BASE_URL . 'district');
        exit;
    }

    public function getDistricts()
    {
        // Receive state_id and the district name to be selected (if in edit mode)
        $state_id = $_POST['state_id'];
        $selected_district = isset($_POST['selected_district']) ? $_POST['selected_district'] : '';

        $districts = $this->model->getByState($state_id);

        echo '<option value="">Select District</option>';

        while($row = mysqli_fetch_assoc($districts))
        {
            // Check if this row matches the saved district name
            $selected = ($row['district_name'] == $selected_district) ? 'selected' : '';

            echo '<option value="'.$row['district_name'].'" '.$selected.'>';
            echo $row['district_name'];
            echo '</option>';
        }
    }

    public function import()
    {
        $file = $_FILES['excel'] ?? null;
        $state_id = isset($_POST['state_id']) ? (int)$_POST['state_id'] : 0;



//         echo "<pre>";
// print_r($_FILES);
// print_r($_POST);
// exit;
        if (!$file || empty($file['name']) || $state_id <= 0) {
            header("Location: " . BASE_URL . "district?import_error=invalid---there is empty file");
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

        }
        elseif ($ext == 'xls') {
                header("Location: " . BASE_URL . "district?import_error=Please save the Excel file as XLSX or CSV.");
                exit;
            }else {

            header("Location: " . BASE_URL . "district?import_error=invalid");
            exit;
        }

        $inserted = 0;
        $skipped  = 0;

        foreach ($dataRows as $r) {

            $district_name = trim($r[0] ?? '');

            if (
                empty($district_name) ||
                $this->model->isDistrictDuplicate($district_name, $state_id)
            ) {
                $skipped++;
                continue;
            }

            if ($this->model->insertDistrict($district_name, $state_id)) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        header("Location: " . BASE_URL . "district?imported=$inserted&skipped=$skipped");
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

}
