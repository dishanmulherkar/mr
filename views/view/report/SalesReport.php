<?php 

$pageTitle = "Sales Report";
include 'view/layout/header.php'; 
?>
<link rel="stylesheet" href="<?= BASE_URL ?>config/config/sales-report.css">
<style>

/* ─── Responsive ─────────────────────────────────── */

@media only screen and (min-width: 1200px){
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 20%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] {min-width: 0% !important ;}
}

@media only screen and (min-width: 992px){
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 15%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] {min-width: 0%;}
}
@media only screen and (min-width: 768px){
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 20%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] {min-width: 0% !important ;}
}

@media (max-width: 600px) {
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 30%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
}


@media (min-width: 425px) and (max-width: 600px) {
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 40%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] { min-width: 60%;}
} 


 @media (min-width: 361px) and (max-width: 425px) {
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 40%; text-align: center; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] { min-width: 70%;}
} 
@media (min-width: 300px) and (max-width: 360px)  {
    .rpt-filter-bar { gap: 8px; }
    .rpt-filter-bar select,
    .rpt-filter-bar input[type="date"] {  font-size: 13px; }
    .btn-search { width: 32% !important; text-align: center; padding-left: 17px; }
    .rpt-table-wrap { overflow-x: auto; }
    .rpt-table-wrap table { min-width: 250px; }
    .rpt-filter-bar input[type="date"] { min-width: 80% !important;}
}

@media (max-width: 480px) {
  .avatar_name {
    cursor: pointer;
    border: 1px solid #b1b5ca;
    border-radius: 9px;
    padding-right: 51px;
    padding-left: 44px;
    margin-left: 60px;
    font-size: small;
    color: #767b94;
  }
}

</style>

<div class="page-content">

        <!-- ── Filters ────────────────────────────────────── -->
        <div class="rpt-filter-bar">
         <label>Start Date</label>
            <input type="date" id="start_date" value="<?= date('Y-m-01') ?>">

            <label>End Date</label>
            <input type="date" id="end_date" value="<?= date('Y-m-d') ?>">

            <button class="btn-search" onclick="loadReport()">Search</button>
        </div>

        <div class="rpt-filter-bar search" style="align-items: baseline;">
            <!-- <label>Customer</label> -->
            <input type="text"
                id="customerSearch"
                placeholder="Search Customer..."
                onkeyup="searchCustomer()" class="rpt-filter-bar search" style="width: 65%;
  padding: 6px;">

            <!-- <button class="btn-search" onclick="loadReport()">Search</button> -->

            <button class="btn-search" onclick="downloadPDFs()" style="width: 30%;">Download</button>
        </div>
        <!-- ── Report Table ───────────────────────────────── -->
        <div class="rpt-table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th class="text-center">Total Sales</th>
                <th class="text-center">Total Amount (₹)</th>
              </tr>
            </thead>
            <tbody id="rpt-tbody">
              <tr>
                <td colspan="5">
                  <div class="state-screen">
                    <div class="state-icon">📋</div>
                    <div class="state-msg">Select a stockist and date</div>
                    Choose filters above and hit Search to load the report.
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot id="rpt-tfoot"></tfoot>
          </table>
        </div>

      </div><!-- /.page-content -->

            <?php 
include 'view/layout/footer.php'; 
?>

<script>
const mr_id = <?= $mr_id ?>;   </script>


<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script> -->
<script src="<?= BASE_URL ?>config/config/sales-report.js"></script>