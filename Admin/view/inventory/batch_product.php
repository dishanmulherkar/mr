  <?php 
// 3. Include the bottom layout and scripts
$pageTitle = "Product Batch";
include 'view/layout/header.php'; 
?>
             <div id="container">
                    <!-- Logout -->
                    <div class="detail">
                        <a href="<?= BASE_URL ?>login/logout">
                            <button type="button" class="btn btn-secondary btn-sm">Logout</button>
                        </a>
                    </div>
                    <hr style="margin-top:10px; margin-bottom:10px; border-top:1px solid #333;">

                    <!-- ===  Flash Messages  === -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Product saved successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-info">Product deleted successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">Something went wrong. Please try again.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['duplicate'])): ?>
                        <div class="alert alert-warning">Product name already exists!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['toggled'])): ?>
                        <div class="alert alert-info">Product status updated.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['imported'])): ?>
                        <div class="alert alert-success">
                            Import complete &mdash;
                            <strong><?php echo intval($_GET['imported']); ?></strong> product(s) added,
                            <strong><?php echo intval($_GET['skipped']); ?></strong> skipped (duplicates / invalid rows).
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['import_error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_GET['import_error'] === 'nofile'
                                ? 'No file selected.'
                                : 'Invalid file type — please upload a <strong>.csv</strong> or <strong>.xlsx</strong> file.'; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Section header with Import button -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="mb-0"><?php echo $ROW ? 'Edit' : 'Add'; ?> Batch Product</h3>
                        <button
                            class="btn btn-success btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#importModal">
                            Import
                        </button>
                    </div>

                    <!-- =====================  FORM  ===================== -->
                    <form method="post" action="<?= $ROW ? BASE_URL.'product_batch/update/'.$ROW['batch_id'] : BASE_URL.'product_batch/store' ?>">

                        <div class="container border px-3 py-3">
                            <div class="row">

                                <!-- Product Name -->
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Product</label>
                                        <select name="product_id" class="form-control" required>
                                            <option value="">Select Product</option>
                                            <?php while($p = mysqli_fetch_assoc($products)): ?>
                                                <option value="<?= $p['p_id'] ?>" <?= ($ROW && $ROW['product_id'] == $p['p_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['product_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- batch -->
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Batch No</label>
                                        <input type="text" name="batch_no" class="form-control"
                                            placeholder="e.g. A001"
                                            value="<?php echo $ROW ? htmlspecialchars($ROW['batch_no']) : ''; ?>">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="Active"
                                                <?php if ($ROW && $ROW['status'] === 'Active') echo 'selected'; ?>>
                                                Active
                                            </option>
                                            <option value="Inactive"
                                                <?php if ($ROW && $ROW['status'] === 'Inactive') echo 'selected'; ?>>
                                                Inactive
                                            </option>
                                        </select>
                                    </div>
                                </div>

                            </div><!-- /row -->

                            <!-- Prices Box -->
                            <div class="box box-primary mt-2">
                                <div class="box-header with-border">
                                    <!-- <h5 class="box-title">Pricing</h5> -->
                                </div>
                                <div class="box-body">
                                    <div class="row">

                                        <!-- PTR -->
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label>PTS <small class="text-muted">(Price to Stockist)</small></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">₹</span>
                                                    </div>
                                                    <input type="number"
                                                        name="pts"
                                                        class="form-control"
                                                        step="0.01"
                                                        required
                                                        value="<?= $ROW['pts'] ?? '' ?>">
                                                </div>
                                            </div>
                                        </div>

                                    </div><!-- /row -->
                                </div><!-- /box-body -->
                            </div>
                            <!-- /box -->

                        </div><!-- /border -->

                        <!-- Buttons -->
                        <div class="row">
                            <div class="py-2 px-3">
                                <button type="submit" name="save" class="btn btn-success btn-sm" style="width:130px;">
                                    <?php echo $ROW ? 'Update' : 'Save'; ?>
                                </button>
                                <?php if ($ROW): ?>
                                    <a href="product_batch" class="btn btn-secondary btn-sm ml-2" style="width:130px;">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </form>
                    <!-- =================  /FORM  ================= -->

                    <!-- =================  TABLE  ================= -->
                    <div class="table_container table-responsive pt-4">
                        <table class="table table-hover" id="productTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="text-center" style="width:50px;">Sr.</th>
                                    <th>Product Name</th>
                                    <th class="text-center">Batch No</th>
                                    <th class="text-center">PTS</th>
                                    <th class="text-center">State</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Created</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $key  = 1;
                                while ($row = mysqli_fetch_assoc($list)):
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $key; ?></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>

                                    <td><?= htmlspecialchars($row['batch_no']) ?></td>

                                    <td class="text-center">
                                        <?= number_format($row['pts'],2) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= htmlspecialchars($row['state_name']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-status badge-<?php if ($row['status'] == '1'){echo 'Active'; } else {Echo 'Inactive';} ?>">
                                            <?php if ($row['status'] == '1'){echo 'Active'; } else {Echo 'Inactive';} ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php echo date('d-m-Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <!-- Edit -->
                                        <a href="<?= BASE_URL ?>product_batch/edit/<?php echo $row['batch_id']; ?>"
                                           class="btn btn-warning btn-sm ml-1" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php $key++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- ===============  /TABLE  =============== -->

                </div><!-- /#container -->


   <!-- =================  IMPORT MODAL  ================= -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog"
     aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="fa-solid fa-file-excel mr-1" style="color:#1D6F42;"></i>
                    Bulk Import Products
                </h5>
                
            </div>

            <form method="post" action="<?= BASE_URL ?>product_batch/import" enctype="multipart/form-data">
                <div class="modal-body">

                    <!-- Column guide -->
                    <div class="alert alert-light border" style="font-size:13px; margin-bottom:15px;">
                        <strong><i class="fa-solid fa-circle-info mr-1"></i> Column Order</strong>
                        <small class="text-muted">(row 1 = header, skipped automatically)</small>
                        <table class="table table-bordered table-sm mt-2 mb-1">
                            <thead class="thead-light">
                                <tr>
                                    <th>A</th>
                                    <th>B</th>
                                    <th>C</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>product_name</td>
                                    <td>Batch</td>
                                    <td>pts</td>
                                </tr>
                            </tbody>
                        </table>
                        <small class="text-muted">
                            &bull; <em>status</em> column is optional — defaults to <strong>Active</strong><br>
                            &bull; Rows with duplicate product names are automatically skipped
                        </small>
                    </div>

                    <!-- File chooser -->
                    <div class="form-group">
                        <label><strong>Choose File</strong>
                            <small class="text-muted">&nbsp;Accepted: .xlsx &nbsp;|&nbsp; .csv</small>
                        </label>
                        <input type="file" name="import_file" class="form-control-file"
                               accept=".xlsx,.csv" required>
                    </div>

                    <!-- Sample download -->
                    <a href="<?= BASE_URL ?>product_batch/downloadSample" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-download mr-1"></i> Download Sample CSV
                    </a>

                </div><!-- /modal-body -->

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            data-dismiss="modal"
                            onclick="$('#importModal').modal('hide');">
                        Cancel
                    </button>
                    <button type="submit" name="import" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-upload mr-1"></i> Import Now
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
<!-- ===============  /IMPORT MODAL  =============== -->


        <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>
                <script>
    // DataTable init
    new DataTable('#productTable');

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.display = 'none';
        });
    }, 5000);
</script>
