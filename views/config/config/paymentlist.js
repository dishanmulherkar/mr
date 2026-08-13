document.addEventListener('DOMContentLoaded', function() {
    
    const viewToggle = document.getElementById('viewToggle');
    const billHeaders = document.getElementById('billHeaders');
    const paymentHeaders = document.getElementById('paymentHeaders');
    
    const stockistFilter = document.getElementById('stockist');
    const statusFilter = document.getElementById('statusFilter');
    const fromDateFilter = document.getElementById('from_date');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    const paymentTable = document.getElementById('paymentTable');
    const paymentCount = document.getElementById('paymentCount');

    // Update Status Dropdown Options based on the View Selected
    function updateStatusOptions() {
        if (viewToggle.value === 'bills') {
            statusFilter.innerHTML = `
                <option value="pending" selected>Pending / Unpaid Bills</option>
                <option value="paid">Paid / Cleared Bills</option>
                <option value="all">All Bills</option>
            `;
            billHeaders.style.display = 'table-header-group';
            paymentHeaders.style.display = 'none';
        } else {
            statusFilter.innerHTML = `
                <option value="pending" selected>Pending Approvals</option>
                <option value="approved">Approved Payments</option>
                <option value="rejected">Rejected Payments</option>
                <option value="all">All Payments</option>
            `;
            billHeaders.style.display = 'none';
            paymentHeaders.style.display = 'table-header-group';
        }
    }

    function fetchPayments() {
        paymentTable.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading records...</td></tr>';

        const params = new URLSearchParams({
            route: 'payment',
            action: 'fetch_list', 
            mr_id: typeof mr_id !== 'undefined' ? mr_id : 0,
            view_mode: viewToggle.value, // Tells PHP to get bills OR payments
            stockist_id: stockistFilter.value,
            status_filter: statusFilter.value,
            from_date: fromDateFilter.value
        });

        fetch(BASE_URL + 'payment/fetch_list?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(viewToggle.value === 'bills') {
                        renderBillTable(data.payments);
                    } else {
                        renderPaymentTable(data.payments);
                    }
                } else {
                    paymentTable.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">${data.msg}</td></tr>`;
                    paymentCount.textContent = 0;
                }
            })
            .catch(error => {
                console.error('Error fetching payments:', error);
                paymentTable.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Failed to connect to server.</td></tr>';
            });
    }

    // Render Logic for BILLS
    function renderBillTable(payments) {
        paymentTable.innerHTML = '';
        let count = 0;

        if (!payments || payments.length === 0) {
            paymentTable.innerHTML = '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
            paymentCount.textContent = 0;
            return;
        }

        payments.forEach(p => {
            let filterVal = statusFilter.value;
            if (filterVal === 'pending' && p.status === 'PAID') return;
            if (filterVal === 'paid' && p.status !== 'PAID') return;

            count++;
            let orderNoText = p.order_id ? `ORD0${p.order_id}` : (p.reference_no || 'N/A');
            let amount = p.pending_amount || 0;
            
            let statusClass = 'badge-pending';
            let displayStatus = p.status || 'UNPAID';
            
            if (p.status === 'PAID') statusClass = 'badge-approved';
            else if (p.status !== 'PARTIAL') statusClass = 'badge-rejected';
            
            let payButton = p.status !== 'PAID' 
                ? `<a href="${BASE_URL}payment/entry?stockist_id=${p.stockist_id}" class="btn-submit" style="padding:4px 10px; font-size:12px; text-decoration:none; display:inline-block; border-radius:4px;">Pay Now</a>`
                : `<span style="color:#28a745; font-weight:600; font-size:12px;">Cleared ✓</span>`;

            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${p.created_at ? p.created_at.split(' ')[0] : '-'}</td>
                <td><strong>${orderNoText}</strong></td>
                <td>${p.stockist_name || '-'}</td>
                <td>₹${parseFloat(p.original_amount).toFixed(2)}</td>
                <td style="color: #dc3545;"><strong>₹${parseFloat(amount).toFixed(2)}</strong></td>
                <td><span class="status-badge ${statusClass}">${displayStatus}</span></td>
                <td>${payButton}</td>
            `;
            paymentTable.appendChild(tr);
        });

        paymentCount.textContent = count;
    }

    // Render Logic for SUBMITTED PAYMENTS
    function renderPaymentTable(payments) {
        paymentTable.innerHTML = '';
        let count = 0;

        if (!payments || payments.length === 0) {
            paymentTable.innerHTML = '<tr><td colspan="7" style="text-align:center;">No records found</td></tr>';
            paymentCount.textContent = 0;
            return;
        }

        payments.forEach(p => {
            // Lowercase matching to prevent case-sensitivity bugs
            let filterVal = statusFilter.value.toLowerCase();
            let recordStatus = p.status ? p.status.toLowerCase() : 'pending';

            if (filterVal !== 'all' && recordStatus !== filterVal) return;

            count++;
            let statusClass = 'badge-pending';
            if (recordStatus === 'approved') statusClass = 'badge-approved';
            if (recordStatus === 'rejected') statusClass = 'badge-rejected';

            // Capitalize for visual display only
            let displayStatus = recordStatus.charAt(0).toUpperCase() + recordStatus.slice(1);

            let actionBtn = p.proof_image 
                ? `<a href="${BASE_URL}${p.proof_image}" target="_blank" class="btn btn-sm btn-info text-white" style="text-decoration:none; padding:4px 8px; border-radius:4px;"><i class="fa fa-eye"></i> Proof</a>` 
                : '-';

            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${p.payment_date || '-'}</td>
                <td><strong>${p.reference_no || 'N/A'}</strong></td>
                <td>${p.stockist_name || '-'}</td>
                <td style="color: #28a745;"><strong>₹${parseFloat(p.amount).toFixed(2)}</strong></td>
                <td>${p.payment_mode || 'Bank'}</td>
                <td><span class="status-badge ${statusClass}">${displayStatus}</span></td>
                <td>${actionBtn}</td>
            `;
            paymentTable.appendChild(tr);
        });

        paymentCount.textContent = count;
    }

    // Event Listeners
    viewToggle.addEventListener('change', () => {
        updateStatusOptions();
        fetchPayments();
    });

    btnSearch.addEventListener('click', fetchPayments);
    
    btnReset.addEventListener('click', () => {
        stockistFilter.value = '';
        fromDateFilter.value = '';
        updateStatusOptions();
        fetchPayments();
    });

    // Initialize Page Data on Load
    updateStatusOptions();
    fetchPayments();
});