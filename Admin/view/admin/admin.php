<?php 
$pageTitle = "Employee";
// 3. Include the bottom layout and scripts
include 'view/layout/header.php'; 

$admin_role = $_SESSION['admin_role'] ?? 'Admin'; 
?>

<div id="container">
    <div class="detail">
        <a href="<?= BASE_URL ?>login/logout" style="float:right"><button type="button" class="btn btn-secondary btn-sm">Logout</button></a>
    </div>
    <hr style="margin-top: 10px; margin-bottom: 10px; border-top: 1px solid #333;">
    <h3>Employee</h3>
    <form method="post" action="<?= isset($ROW) ? BASE_URL.'admin/update/'.$ROW['admin_id'] : BASE_URL.'admin/store'; ?>">
        <div class="container border px-3 py-3">
            <div class="row">
                <!-- Admin Name -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>Employee Name</label>
                        <input type="text"
                                name="admin_name"
                                class="form-control"
                                value="<?= isset($ROW['admin_name']) ? $ROW['admin_name'] : ''; ?>"
                                placeholder="Example : John Doe"
                                required>
                    </div>
                </div>
                
                <!-- User Name -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>User Name</label>
                        <input type="text"
                                name="user_name"
                                class="form-control"
                                value="<?= isset($ROW['username']) ? $ROW['username'] : ''; ?>"
                                placeholder="State : Name "
                                required>
                    </div>
                </div>

                <!-- Email -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                                name="email"
                                class="form-control"
                                value="<?= isset($ROW['email']) ? $ROW['email'] : ''; ?>"
                                placeholder="Example : john.doe@example.com">
                    </div>
                </div>

                <!-- Mobile -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Mobile</label>
                        <input type="tel"
                                name="mobile"
                                class="form-control"
                                value="<?= isset($ROW['mobile']) ? $ROW['mobile'] : ''; ?>"
                                placeholder="Example : +1234567890">
                    </div>
                </div>

                <!-- Password -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <input type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                value="<?= isset($ROW['password']) ? htmlspecialchars($ROW['password']) : ''; ?>"
                                required>
                            <button class="btn btn-outline-secondary"
                                    type="button"
                                    id="togglePassword">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Role -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="">Select Role</option>
                            
                            <!-- Restricted Roles -->
                            <?php if($admin_role == 'Super Admin'): ?>
                                <option value="Admin" <?= (isset($ROW['role']) && $ROW['role']=='Admin') ? 'selected' : ''; ?>>
                                    Admin
                                </option>
                                <option value="Super Admin" <?= (isset($ROW['role']) && $ROW['role']=='Super Admin') ? 'selected' : ''; ?>>
                                    Super Admin
                                </option>
                            <?php endif; ?>
                            
                            <option value="ASM" <?= (isset($ROW['role']) && $ROW['role']=='ASM') ? 'selected' : ''; ?>>
                                ASM
                            </option>
                            <option value="Dispatch" <?= (isset($ROW['role']) && $ROW['role']=='Dispatch') ? 'selected' : ''; ?>>
                                Dispatch
                            </option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-4" id="stateDiv">
                    <div class="form-group">
                        <label>Assign States</label>
                        <select name="state_id[]" id="state_id" class="form-control" multiple>
                            <?php while($srow=mysqli_fetch_assoc($states)){ ?>
                                <option value="<?= $srow['state_id']; ?>" <?= (isset($selectedStates) && in_array($srow['state_id'],$selectedStates)) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($srow['state_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="col-lg-4" id="SuperStockistDiv">
                    <div class="form-group">
                        <label>Assign Super Stockists</label>
                        <select name="super_stockist_id" id="super_stockist_id" class="form-control">
                            <option value="">Select Super Stockist</option>
                            <?php while($srow=mysqli_fetch_assoc($super_stockists)){ ?>
                                <option value="<?= $srow['super_stockist_id']; ?>" <?= (isset($ROW['stockist_id']) && $ROW['stockist_id'] == $srow['super_stockist_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($srow['ss_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            
                <div id="commissionDiv" class="col-lg-3">
                    <div class="form-group">
                        <label>Commission</label>
                        <input type="number"
                                name="commission_rate"
                                class="form-control"
                                id="commission_rate"
                                placeholder="Enter commission"
                                step="0.01"
                                min="0"
                                value="<?php echo isset($ROW['commission_rate']) ? $ROW['commission_rate'] : ''; ?>">
                    </div>
                </div>
            
                <!-- Status -->
                <div class="col-lg-6 mt-3">
                    <div class="form-group">
                        <label>Status</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input"
                                type="radio"
                                name="status"
                                value="1"
                                id="active"
                                <?= (!isset($ROW['status']) || $ROW['status']=='Active') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">Active</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input"
                                type="radio"
                                name="status"
                                value="0"
                                id="inactive"
                                <?= (isset($ROW['status']) && $ROW['status']=='Inactive') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="inactive">Inactive</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-12">
                    <button type="submit" name="save" class="btn btn-success btn-sm" style="width:200px;">
                        Save Admin
                    </button>
                </div>
            </div>
        </div>
    </form>
            
    <div class="table_container table-responsive pt-4">
        <table class="table table-bordered table-hover" id="finanTable">
            <thead class="table-secondary">
                <!-- Heading -->
                <tr>
                    <th class="text-center">Sr. No</th>
                    <th class="text-center">Employee Name</th>
                    <th class="text-center">User Name</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Mobile</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Assigned State(s)</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>

                <!-- Column Search -->
                <tr>
                    <th></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Search Name"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Search Username"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Search Email"></th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Search Mobile"></th>
                    <th>
                        <select class="form-select form-select-sm table-filter" data-column="5">
                            <option value="">All</option>
                            <?php if($admin_role == 'Super Admin'): ?>
                                <option value="Admin">Admin</option>
                                <option value="Super Admin">Super Admin</option>
                            <?php endif; ?>
                            <option value="ASM">ASM</option>
                            <option value="Dispatch">Dispatch</option>
                        </select>
                    </th>
                    <th><input type="text" class="form-control form-control-sm" placeholder="Search State"></th>
                    <th>
                        <select class="form-select form-select-sm table-filter" data-column="7">
                            <option value="">All</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <?php
                $key = 1;
                while($row = mysqli_fetch_assoc($query))
                {
                ?>
                <tr>
                    <td class="text-center"><?= $key; ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['admin_name']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['username']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['email']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['mobile']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['role']); ?></td>
                    <td class="text-center"><?= !empty($row['state_names']) ? htmlspecialchars($row['state_names']) : '-'; ?></td>
                    <td class="text-center">
                        <?php if($row['status'] == 'Active') { ?>
                            <span class="badge bg-success">Active</span>
                        <?php } else { ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php } ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= BASE_URL ?>admin/edit/<?= $row['admin_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/delete/<?= $row['admin_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this admin?');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php
                $key++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>    

<script>
$(document).ready(function () {
    var table = $('#finanTable').DataTable({
        orderCellsTop: true,
        fixedHeader: true
    });

    // Textbox Search
    $('#finanTable thead tr:eq(1) th input').on('keyup change', function () {
        let colIndex = $(this).parent().index();
        if (table.column(colIndex).search() !== this.value) {
            table.column(colIndex).search(this.value).draw();
        }
    });

    // Dropdown Search
    $('.table-filter').on('change', function () {
        let colIndex = $(this).data('column');
        table.column(colIndex).search($(this).val()).draw();
    });

    setTimeout(function(){
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert){
            alert.style.display = 'none';
        });
    }, 5000);

    $('#state_id').select2({
        placeholder: "Select States",
        width: '100%'
    });

    function toggleState() {
        let selectedRole = $('#role').val();
        
        if (selectedRole == 'Admin' || selectedRole == 'Dispatch' || selectedRole == 'ASM') {
            $('#stateDiv').show();
            $('#state_id').prop('required', true);
            $('#SuperStockistDiv').show();
            $('#super_stockist_id').prop('required', true);
        } else {
            $('#stateDiv').hide();
            $('#state_id').prop('required', false).val(null).trigger('change');
            $('#SuperStockistDiv').hide();
            $('#super_stockist_id').prop('required', false).val(null).trigger('change');
        }
        
        if (selectedRole == 'ASM') {
             $('#commissionDiv').show();
            $('#commission_rate').prop('required', true);
        } else {
            $('#commissionDiv').hide();
            $('#commission_rate').prop('required', false).val(null).trigger('change');
        }
    }

    toggleState();

    $('#role').change(function () {
        toggleState();
    });

    document.getElementById("togglePassword").addEventListener("click", function () {
        const pass = document.getElementById("password");
        const icon = this.querySelector("i");
        
        if (pass.type === "password") {
            pass.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });
});
</script>