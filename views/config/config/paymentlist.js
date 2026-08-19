document.addEventListener('DOMContentLoaded', function() {
    
    const viewToggle = document.getElementById('viewToggle');
    const stockistFilter = document.getElementById('stockist');
    const statusFilter = document.getElementById('statusFilter');
    const fromDateFilter = document.getElementById('from_date');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    
    // Container reference
    const cardContainer = document.getElementById('paymentCardContainer');
    const paymentCount = document.getElementById('paymentCount');

    // Modal Elements (Make sure these are in your PHP HTML)
    const proofModal = document.getElementById('proofModal');
    const modalProofImage = document.getElementById('modalProofImage');
    const closeProofModal = document.getElementById('closeProofModal');

    // Close Modal Events
    if (closeProofModal) {
        closeProofModal.addEventListener('click', () => {
            proofModal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === proofModal) {
            proofModal.style.display = 'none';
        }
    });

    // Update Status Dropdown Options based on the View Selected
    function updateStatusOptions() {
        if (viewToggle.value === 'bills') {
            statusFilter.innerHTML = `
                <option value="pending" selected>Unpaid Bills</option>
                <option value="paid">Paid Bills</option>
                <option value="all">All Bills</option>
            `;
        } else {
            statusFilter.innerHTML = `
                <option value="pending" selected>Pending Approvals</option>
                <option value="approved">Approved Payments</option>
                <option value="rejected">Rejected Payments</option>
                <option value="all">All Payments</option>
            `;
        }
    }

    function fetchPayments() {
        cardContainer.innerHTML = '<div class="empty-state"><i class="fa fa-spinner fa-spin"></i> Loading records...</div>';

        const params = new URLSearchParams({
            route: 'payment',
            action: 'fetch_list', 
            mr_id: typeof mr_id !== 'undefined' ? mr_id : 0,
            view_mode: viewToggle.value, 
            stockist_id: stockistFilter.value,
            status_filter: statusFilter.value,
            from_date: fromDateFilter.value
        });

        fetch(BASE_URL + 'payment/fetch_list?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(viewToggle.value === 'bills') {
                        renderBillCards(data.payments);
                    } else {
                        renderPaymentCards(data.payments);
                    }
                } else {
                    cardContainer.innerHTML = `<div class="empty-state" style="color:red;">${data.msg}</div>`;
                    paymentCount.textContent = 0;
                }
            })
            .catch(error => {
                console.error('Error fetching payments:', error);
                cardContainer.innerHTML = '<div class="empty-state" style="color:red;">Failed to connect to server.</div>';
            });
    }

    
    // Render Logic for BILLS (Card View)
    function renderBillCards(payments) {
        cardContainer.innerHTML = '';
        let count = 0;

        if (!payments || payments.length === 0) {
            cardContainer.innerHTML = '<div class="empty-state">No bills found for the selected filters.</div>';
            paymentCount.textContent = 0;
            return;
        }

        payments.forEach(p => {
            let filterVal = statusFilter.value;
            if (filterVal === 'pending' && p.status === 'PAID') return;
            if (filterVal === 'paid' && p.status !== 'PAID') return;

            count++;
            let orderNoText = p.inward_no ? `${p.inward_no}` : (p.inward_no || 'N/A');
            
            // FIX: Ensure amounts are mathematically rounded integers
            let originalAmt = Math.round(p.original_amount || 0);
            let pendingAmt = Math.round(p.pending_amount || 0);
            
            let statusClass = 'badge-pending';
            let displayStatus = p.status || 'UNPAID';
            
            if (p.status === 'PAID') {
                statusClass = 'badge-approved';
                pendingAmt = 0; // FIX: Force pending to 0 if the bill is marked PAID
            } else if (p.status !== 'PARTIAL') {
                statusClass = 'badge-rejected';
            }

            let card = document.createElement('div');
            card.className = 'payment-card';
            card.innerHTML = `
                <div class="card-header">
                    <strong>${orderNoText}</strong> <span style= "font-size:12px;">${p.created_at ? p.created_at.split(' ')[0] : '-'}</span>
                    <span class="status-badge ${statusClass}">${displayStatus}</span>
                </div>
                <div class="card-body">
                    <div class="card-row">
                        <span>Stockist</span>
                        <span style="text-align: right; max-width: 60%;">${p.stockist_name || '-'}</span>
                    </div>
                    <div class="card-row">
                        <span>Bill Amount</span>
                        <span>₹${originalAmt.toFixed(2)}</span>
                    </div>
                    <div class="card-row amount-highlight">
                        <span>Pending Balance</span>
                        <strong style="color: #dc3545;">₹${pendingAmt.toFixed(2)}</strong>
                    </div>
                </div>
            `;
            cardContainer.appendChild(card);
        });

        if(count === 0) {
            cardContainer.innerHTML = '<div class="empty-state">No matching bills found.</div>';
        }
        paymentCount.textContent = count;
    }

    // Render Logic for SUBMITTED PAYMENTS (Card View)
    function renderPaymentCards(payments) {
        cardContainer.innerHTML = '';
        let count = 0;

        if (!payments || payments.length === 0) {
            cardContainer.innerHTML = '<div class="empty-state">No payments found for the selected filters.</div>';
            paymentCount.textContent = 0;
            return;
        }

        payments.forEach(p => {
            let filterVal = statusFilter.value.toLowerCase();
            let recordStatus = p.status ? p.status.toLowerCase() : 'pending';

            if (filterVal !== 'all' && recordStatus !== filterVal) return;

            count++;
            let statusClass = 'badge-pending';
            if (recordStatus === 'approved') statusClass = 'badge-approved';
            if (recordStatus === 'rejected') statusClass = 'badge-rejected';

            let displayStatus = recordStatus.charAt(0).toUpperCase() + recordStatus.slice(1);

            // UPDATED: Changed from an <a> tag to a <button> that triggers the popup
            let actionBtn = p.proof_image 
                ? `<button type="button" class="view-proof-btn" data-img="${BASE_URL}../${p.proof_image}" style="background-color: #17a2b8; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;"><i class="fa fa-eye"></i> View Proof</button>` 
                : `<span style="color:#aaa; font-size:13px;">No Proof</span>`;

            let card = document.createElement('div');
            card.className = 'payment-card';
            card.innerHTML = `
                <div class="card-header">
                    <strong>Ref: ${p.reference_no || 'N/A'}</strong>
                    <span class="status-badge ${statusClass}">${displayStatus}</span>
                </div>
                <div class="card-body">
                    <div class="card-row">
                        <span>Pay Date</span>
                        <span>${p.payment_date || '-'}</span>
                    </div>
                    <div class="card-row">
                        <span>Stockist</span>
                        <span style="text-align: right; max-width: 60%;">${p.stockist_name || '-'}</span>
                    </div>
                    <div class="card-row">
                        <span>Pay Mode</span>
                        <span>${p.payment_mode || 'Bank'}</span>
                    </div>
                    <div class="card-row amount-highlight">
                        <span>Amount Paid</span>
                        <strong style="color: #28a745;">₹${parseFloat(p.amount).toFixed(2)}</strong>
                    </div>
                </div>
                <div class="card-footer">
                    ${actionBtn}
                </div>
            `;
            cardContainer.appendChild(card);
        });

        // NEW: Attach click events to all dynamically created View Proof buttons
        document.querySelectorAll('.view-proof-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const imgSrc = this.getAttribute('data-img');
                if (modalProofImage && proofModal) {
                    modalProofImage.src = imgSrc;
                    proofModal.style.display = 'flex';
                }
            });
        });

        if(count === 0) {
            cardContainer.innerHTML = '<div class="empty-state">No matching payments found.</div>';
        }
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