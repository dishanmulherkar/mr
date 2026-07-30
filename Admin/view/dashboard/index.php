<?php 
// 1. Set the dynamic title for the header
$pageTitle = "Dashboard"; 

// 2. Include the top layout
include 'view/layout/header.php'; 

?>
<div>
<h3>Dashboard Overview</h3>
 <a href="<?= BASE_URL ?>login/logout" style="float:right; margin-top: -30px;">
                            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                        </a>
</div>

<div class="row mt-4">
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Stockists</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                           
                            <?php echo $TotalStockists['total_stockists']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa-solid fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Products</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $TotalProducts['total_products']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa-solid fa-box fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Customers</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $TotalCustomers['total_customers']; ?>
                            
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa-solid fa fa-user fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total States</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $TotalStates['total_states']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa-solid fa-city fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
     <div class="col-lg-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total HeadQuarters</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $TotalHeadQuarters['total_headquarters']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa-solid fa-building fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>