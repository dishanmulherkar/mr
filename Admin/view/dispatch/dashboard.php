<?php 
$pageTitle = "Dispatch Orders";
include 'view/layout/mobile_header.php'; 
?>

<!-- FILTER BAR -->
<form method="GET" action="<?= BASE_URL ?>DispatchDashboard" class="mb-3">
    <div class="row g-2">
        <div class="col-7">
            <select name="filter_status" class="form-select form-select-sm shadow-sm border-0" onchange="this.form.submit()">
                <option value="Approved" <?= ($filter_status ?? '') == 'Approved' ? 'selected' : '' ?>>Pending Dispatch</option>
                <option value="Processed" <?= ($filter_status ?? '') == 'Processed' ? 'selected' : '' ?>>Already Dispatched</option>
            </select>
        </div>
        <div class="col-5">
            <input type="date" name="filter_date" class="form-control form-control-sm shadow-sm border-0" value="<?= htmlspecialchars($filter_date ?? '') ?>" onchange="this.form.submit()">
        </div>
    </div>
</form>
<!-- ORDER CARDS -->
<?php if(empty($orders)): ?>
    <div class="alert alert-info text-center mt-4 border-0 shadow-sm" style="border-radius: 12px;">
        No orders found for the selected filters.
    </div>
<?php else: ?>
    <?php foreach($orders as $order): ?>
        <div class="card mb-3 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body">
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary">#ORD-<?= $order['order_id'] ?></h6>
                    <?php if($order['status'] === 'Approved'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                    <?php else: ?>
                        <span class="badge bg-success">Dispatched</span>
                    <?php endif; ?>
                </div>
                
                <p class="mb-1 small text-muted"><i class="fa fa-user"></i> <?= htmlspecialchars($order['stockist_name']) ?></p>
                <div class="d-flex justify-content-between mb-3">
                    <span class="small text-muted"><i class="fa fa-calendar-alt"></i> <?= date('d M Y', strtotime($order['order_date'])) ?></span>
                    <span class="small text-muted fw-bold"><i class="fa fa-rupee-sign"></i> <?= number_format($order['total_amt'], 2) ?></span>
                </div>

                <!-- Conditional Action Button -->
                <?php if($order['status'] === 'Approved'): ?>
                    <button type="button" class="btn btn-success w-100 btn-sm view-order-btn" style="border-radius: 8px;" data-id="<?= $order['order_id'] ?>" data-status="pending">
                        <i class="fa fa-box-open"></i> View & Dispatch
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary w-100 btn-sm view-order-btn" style="border-radius: 8px;" data-id="<?= $order['order_id'] ?>" data-status="dispatched">
                        <i class="fa fa-eye"></i> View Details
                    </button>
                <?php endif; ?>
                
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div> <!-- End Container -->

<!-- DISPATCH MODAL -->
<div class="modal fade" id="dispatchModal" tabindex="-1" aria-labelledby="dispatchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0" style="border-radius: 15px; overflow: hidden;">
      <div class="modal-header bg-primary text-white border-0">
        <h6 class="modal-title" id="dispatchModalLabel">Order Details</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
          <p class="mb-2 bg-white p-2 rounded shadow-sm text-center"><strong>Invoice No:</strong> <span id="modalInvoiceNo" class="text-primary fw-bold">Loading...</span></p>
          
          <div id="modalProductList" class="mt-3">
              <div class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Fetching details...</div>
          </div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success btn-sm" id="confirmDispatchBtn">
            <i class="fa fa-check-circle"></i> Confirm Dispatch
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dispatchModal = new bootstrap.Modal(document.getElementById('dispatchModal'));
    let currentOrderId = null;
    let confirmBtn = document.getElementById('confirmDispatchBtn');

    document.querySelectorAll('.view-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentOrderId = this.getAttribute('data-id');
            let orderStatus = this.getAttribute('data-status');
            
            // Hide Dispatch button if already dispatched
            confirmBtn.style.display = (orderStatus === 'dispatched') ? 'none' : 'block';

            document.getElementById('modalInvoiceNo').textContent = 'Loading...';
            document.getElementById('modalProductList').innerHTML = '<div class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Fetching details...</div>';
            
            dispatchModal.show();

            fetch('<?= BASE_URL ?>DispatchDashboard/getDetails/' + currentOrderId)
                .then(response => response.json())
                .then(res => {
                    if(res.success) {
                        document.getElementById('modalInvoiceNo').textContent = res.data.inward_no;
                        
                        let html = '<ul class="list-group shadow-sm" style="border-radius: 10px;">';
                        res.data.items.forEach(item => {
                            html += `
                                <li class="list-group-item px-3 py-2 border-0 border-bottom">
                                    <div class="fw-bold text-dark" style="font-size: 14px;">${item.product_name}</div>
                                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 13px;">
                                        <span>Qty: <strong class="text-dark">${item.qty}</strong></span>
                                        <span>MRP: ₹${parseFloat(item.mrp).toFixed(2)}</span>
                                    </div>
                                </li>`;
                        });
                        html += '</ul>';
                        document.getElementById('modalProductList').innerHTML = html;
                    } else {
                        document.getElementById('modalProductList').innerHTML = '<div class="alert alert-danger text-center shadow-sm">Failed to load details.</div>';
                    }
                })
                .catch(err => {
                    document.getElementById('modalProductList').innerHTML = '<div class="alert alert-danger text-center shadow-sm">Network Error.</div>';
                });
        });
    });

    confirmBtn.addEventListener('click', function() {
        if(!currentOrderId) return;
        
        this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
        this.disabled = true;

        let formData = new FormData();
        formData.append('order_id', currentOrderId);

        fetch('<?= BASE_URL ?>DispatchDashboard/markDispatched', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if(res.success) {
                dispatchModal.hide();
                window.location.reload(); 
            } else {
                alert(res.msg || 'Failed to dispatch order.');
                this.innerHTML = '<i class="fa fa-check-circle"></i> Confirm Dispatch';
                this.disabled = false;
            }
        });
    });
});
</script>
</body>
</html>