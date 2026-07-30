

    
    <style>
      .img-thumbnail {
  padding: .25rem;
  background-color: var(--bs-body-bg);
  border: 0px;
  border-radius: var(--bs-border-radius);
  max-width: 70%;
  height: auto;
  justify-content: center;
  margin-left: 25px;
}
.rounded-circle {
  border-radius: 0px !important;
}
.sidebar-logo {
  padding: 1.15rem 1.5rem;
  background-color: #fff;
}


.toggle-icon{
    transition: transform .3s ease;
}

.toggle-icon.rotate{
    transform: rotate(180deg);
}



    </style>
    
     <aside id="sidebar">
          <div class="h-100 bg-dark">
              <div class="sidebar-logo">
                <a href="<?= BASE_URL ?>">
                <img src="<?= BASE_URL ?>config/image/logo.jpg" class="img-thumbnail rounded-circle" alt="..." width="500" height="236">
                </a>
             </div>
             <hr class="sidebar-divider my-0">
             <!-- sidebar navigation -->
             <ul class="sidebar-nav">
                <?php  $admin_role =  $_SESSION['admin_role'] ?>
               
                   <ul class="nav nav-pills flex-column mt-4">
<?php if($admin_role == 'Super Admin'){ ?>
                  <!-- for Admin  -->
                      <li class="nav-item">
                           <a class="nav-link text-white d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#adminMenu"
                                 aria-expanded="false">

                                 <span>
                                   <i class="fa-solid fa-user-tie"></i>
                                    <span class="ms-2">Admin</span>
                                 </span>

                                 <i class="fa-solid fa-angle-down toggle-icon"></i>
                              </a>

                           <div class="collapse" id="adminMenu">
                              <ul class="nav flex-column ms-4 mt-2">
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>admin" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-warehouse"></i>
                                          Manage Admin
                                       </a>
                                    </li>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>permision" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-calendar-check"></i>
                                          Permision
                                       </a>
                                    </li>

                              </ul>
                           </div>
                     </li>
               <?php } ?>

                  <li class="nav-item py-2 py-sm-0">
                     <a href="<?= BASE_URL ?>supplier" class="nav-link text-white">
                     <i class="fas fa-building"></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                     Super Stockist</a> 
                  </li>


                  <li class="nav-item py-2 py-sm-0">
                     <a href="<?= BASE_URL ?>purchase" class="nav-link text-white">
                     <i class="fas fa-building"></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                     Purchase (Super Stockist)</a> 
                  </li>

                   <li class="nav-item py-2 py-sm-0">
                        <a href="<?= BASE_URL ?>headquarter" class="nav-link text-white">
                        <i class="fas fa-building"></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                        HeadQuarter</a> 
                     </li>
                       
                   <li class="nav-item py-2 py-sm-0">
                        <a href="<?= BASE_URL ?>stockist" class="nav-link text-white">
                        <i class="fa-solid fa-boxes-stacked"></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                        Stockist</a> 
                     </li>

                     <li class="nav-item py-2 py-sm-0">
                        <a href="<?= BASE_URL ?>customer" class="nav-link text-white">
                        <i class="fa-solid fa-users"></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                         Customers</a> 
                     </li>  
                     
                      <!-- for Inventory  -->
                      <li class="nav-item">
                           <a class="nav-link text-white d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#inventoryMenu"
                                 aria-expanded="false">

                                 <span>
                                    <i class="fa-solid fa-warehouse"></i>&nbsp;&nbsp;
                                    <span class="ms-2">Inventory</span>
                                 </span>

                                 <i class="fa-solid fa-angle-down toggle-icon"></i>
                              </a>

                           <div class="collapse" id="inventoryMenu">
                              <ul class="nav flex-column ms-4 mt-2">
                                 <?php if($_SESSION['admin_role'] == 'Super Admin'){ ?>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>products" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-warehouse"></i>
                                          Product Master
                                       </a>
                                    </li>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>product_batch" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-warehouse"></i>
                                          Batch Wise Product 
                                       </a>
                                    </li>
                                     <li class="nav-item">
                                       <a href="<?= BASE_URL ?>batch_status" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-calendar-check"></i>
                                          Update Batch 
                                       </a>
                                    </li>
                                 <?php } ?>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>stock_inward" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-cubes-stacked"></i>&nbsp;&nbsp;
                                          Stock InWard 
                                       </a>
                                    </li>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>stock_adjustment" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-plus-minus"></i>&nbsp;&nbsp;
                                          Stock Adjustment 
                                       </a>
                                    </li>
                              </ul>
                           </div>
                     </li> 
                     
                     <li class="nav-item">
                        <a class="nav-link text-white d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse"
                           data-bs-target="#reportMenu"
                           aria-expanded="false">
                           <span>
                              <i class="fa-solid fa-chart-line"></i>&nbsp;&nbsp;
                              <span class="ms-2">Reports</span>
                           </span>
                           <i class="fa-solid fa-angle-down toggle-icon"></i>
                        </a>
                        <div class="collapse" id="reportMenu">
                           <ul class="nav flex-column ms-4 mt-2">

                                 <li class="nav-item">
                                    <a href="<?= BASE_URL ?>stock_and_sales_report" class="nav-link text-light report-link">
                                       <i class="fa-solid fa-file-invoice-dollar"></i>&nbsp;&nbsp;
                                       Stock & Sales Report
                                    </a>
                                 </li>

                                 <li class="nav-item">
                                    <a href="<?= BASE_URL ?>hq_customer_report" class="nav-link text-light report-link">
                                       <i class="fa-solid fa-user-doctor"></i>&nbsp;&nbsp;
                                       Customer Report
                                    </a>
                                 </li>

                                 <li class="nav-item">
                                    <a href="<?= BASE_URL ?>inward_report" class="nav-link text-light report-link">
                                       <i class="fa-solid fa-file-invoice-dollar"></i>&nbsp;&nbsp;
                                       Inward Report
                                    </a>
                                 </li>
                                 <?php if($admin_role == 'Super Admin'){ ?>
                                 <li class="nav-item">
                                    <a href="<?= BASE_URL ?>State_wise_report" class="nav-link text-light report-link">
                                       <i class="fa-solid fa-file-invoice-dollar"></i>&nbsp;&nbsp;
                                       State Wise Report
                                    </a>
                                 </li>
                                 <?php } ?>
                           </ul>
                        </div>
                    </li> 

                    <!-- for Location  -->
                      <li class="nav-item">
                           <a class="nav-link text-white d-flex justify-content-between align-items-center"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#districtMenu"
                                 aria-expanded="false">

                                 <span>
                                    <i class="fa-solid fa-location-dot"></i>&nbsp;&nbsp;
                                    <span class="ms-2">Location</span>
                                 </span>

                                 <i class="fa-solid fa-angle-down toggle-icon"></i>
                              </a>

                           <div class="collapse" id="districtMenu">
                              <ul class="nav flex-column ms-4 mt-2">
                                 <?php if($admin_role == 'Super Admin'){ ?>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>state" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-warehouse"></i>&nbsp;&nbsp;
                                          State
                                       </a>
                                    </li>
                                    <?php } ?>
                                    <li class="nav-item">
                                       <a href="<?= BASE_URL ?>district" class="nav-link text-light report-link">
                                          <i class="fa-solid fa-calendar-check"></i>&nbsp;&nbsp;
                                          District
                                       </a>
                                    </li>

                              </ul>
                           </div>
                     </li>
                     <li class="nav-item py-2 py-sm-0">
                        <a href="<?= BASE_URL ?>notification" class="nav-link text-white">
                        <i class="fa-solid fa-bell"></i></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                         Notification</a> 
                     </li>  
                       <li class="nav-item py-2 py-sm-0">
                        <a href="<?= BASE_URL ?>financial" class="nav-link text-white">
                        <i class="fa-solid fa-calendar-days"></i></i><span class="fs-3 ms-3 non d-sm-inline"></span>    
                         Financial</a> 
                     </li>  
                      
                 </ul>
             </ul>
          </div>
     </aside>
     <!-- Main component -->
     <div class="main">
        <nav class="navbar  px-3 bg-dark border-bottom navbar-fixed-top">
           <!-- button for sidebar toggle -->
            <button class="btn btn-light" type="button"  data-bs-theme="light">
            <span class="navbar-toggler-icon"></span>
            </button>
            <a href="<?= BASE_URL ?>" style="text-decoration: none;">
            <h5 class="admin-txt" style="color:#fff;"> 
               <?php if($admin_role == 'Super Admin'){ ?>
               <?php echo $admin_role; ?>
               <?php }else{ ?>
               <?php 
               echo $admin_role."      "; 
               echo $_SESSION['admin_username'];
               }?> 
            </h5>
            </a>
        </nav>
       
     



   
    <!-- <script src="Addons/script.js"></script> -->

    <script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(btn){

        const icon = btn.querySelector('.toggle-icon');
        const targetSelector = btn.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);

        if (!icon || !target) return;

        target.addEventListener('show.bs.collapse', function () {
            icon.classList.remove('fa-angle-down');
            icon.classList.add('fa-angle-up');
        });

        target.addEventListener('hide.bs.collapse', function () {
            icon.classList.remove('fa-angle-up');
            icon.classList.add('fa-angle-down');
        });

    });

});
</script>


