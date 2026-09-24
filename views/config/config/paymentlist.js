document.addEventListener('DOMContentLoaded', function() {
    
    const viewToggle = document.getElementById('viewToggle');
    const stockistFilter = document.getElementById('stockist');
    const statusFilter = document.getElementById('statusFilter');
    const fromDateFilter = document.getElementById('from_date');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    
    const cardContainer = document.getElementById('paymentCardContainer');
    const paymentCount = document.getElementById('paymentCount');

    // Proof Modal Elements
    const proofModal = document.getElementById('proofModal');
    const modalProofImage = document.getElementById('modalProofImage');
    const closeProofModal = document.getElementById('closeProofModal');

    // Breakdown Modal Elements
    const breakdownModal = document.getElementById('breakdownModal');
    const closeBreakdownModal = document.getElementById('closeBreakdownModal');
    const bdBillsNo = document.getElementById('bd-bill-no');
    const bdBillsTotal = document.getElementById('bd-bill-total');
    const bdBillsPaid = document.getElementById('bd-bill-paid');
    const bdBillsPending = document.getElementById('bd-bill-pending');
    const bdTbody = document.getElementById('bd-tbody');

    // Close Modals
    if (closeProofModal) {
        closeProofModal.addEventListener('click', () => {
            proofModal.style.display = 'none';
        });
    }
    if (closeBreakdownModal) {
        closeBreakdownModal.addEventListener('click', () => {
            breakdownModal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === proofModal) proofModal.style.display = 'none';
        if (e.target === breakdownModal) breakdownModal.style.display = 'none';
    });

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

    // Render Logic for BILLS (Card View with Breakdown Button)
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
            let orderNoText = p.inward_no ? `${p.inward_no}` : 'N/A';
            let originalAmt = Math.round(p.original_amount || 0);
            let pendingAmt = Math.round(p.pending_amount || 0);
            
            let statusClass = 'badge-pending';
            let displayStatus = p.status || 'UNPAID';
            
            if (p.status === 'PAID') {
                statusClass = 'badge-approved';
                pendingAmt = 0;
            } else if (p.status !== 'PARTIAL') {
                statusClass = 'badge-rejected';
            }

            const inwardId = p.inward_id || 0;

            let card = document.createElement('div');
            card.className = 'payment-card';
            card.innerHTML = `
                <div class="card-header">
                    <strong class="btn-breakdown" data-inward-id="${inwardId}" style="cursor: pointer; color: #2563eb; text-decoration: underline;">
                        ${orderNoText}
                    </strong> 
                    <span style="font-size:12px;">${p.created_at ? p.created_at.split(' ')[0] : '-'}</span>
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
                        <strong style="color: ${pendingAmt > 0 ? '#dc3545' : '#16a34a'};">₹${pendingAmt.toFixed(2)}</strong>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn-breakdown" data-inward-id="${inwardId}" style="background-color: #2563eb; color: white; border: none; padding: 5px 12px; border-radius: 4px; font-size: 11px; cursor: pointer;">
                        <i class="fa fa-history"></i> View Breakdown
                    </button>
                </div>
            `;
            cardContainer.appendChild(card);
        });

        // Attach Click Listener for Payment History Breakdown
        document.querySelectorAll('.btn-breakdown').forEach(btn => {
            btn.addEventListener('click', function() {
                const inwardId = this.getAttribute('data-inward-id');
                openBreakdownModal(inwardId);
            });
        });

        if(count === 0) {
            cardContainer.innerHTML = '<div class="empty-state">No matching bills found.</div>';
        }
        paymentCount.textContent = count;
    }

    // Function to Fetch and Display the Payment Breakdown Modal
    function openBreakdownModal(inwardId) {
        if (!inwardId) return;

        bdTbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 15px; color:#64748b;"><i class="fa fa-spinner fa-spin"></i> Loading breakdown...</td></tr>';
        breakdownModal.style.display = 'flex';

        fetch(BASE_URL + 'payment/get_bill_breakdown?inward_id=' + inwardId)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    bdTbody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding:10px; color:red;">${res.msg}</td></tr>`;
                    return;
                }

                const b = res.bill;
                const grandTotal = parseFloat(b.grand_total) + parseFloat(b.cd_penalty_amt || 0);
                const paidAmt = parseFloat(b.paid_amt) + parseFloat(b.cd_earned_amt || 0);
                const pending = Math.max(0, grandTotal - paidAmt);

                bdBillsNo.textContent = b.inward_no;
                bdBillsTotal.textContent = '₹' + grandTotal.toFixed(2);
                bdBillsPaid.textContent = '₹' + paidAmt.toFixed(2);
                bdBillsPending.textContent = '₹' + pending.toFixed(2);

                let rowsHtml = '';

                // 1. Direct Cash Receipts
                if (res.allocations && res.allocations.length > 0) {
                    res.allocations.forEach(a => {
                        let desc = a.payment_method || 'Bank Payment';
                        if (a.bank_name) desc += ` (${a.bank_name})`;
                        rowsHtml += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 6px 8px;">${a.created_at ? a.created_at.split(' ')[0] : '-'}</td>
                                <td style="padding: 6px 8px;">
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 5px; border-radius: 4px; font-weight: 600; font-size: 10px; margin-right: 4px;">Receipt</span>
                                    ${desc}
                                </td>
                                <td style="padding: 6px 8px; text-align: right; color: #16a34a; font-weight: 600;">+ ₹${parseFloat(a.amount_allocated).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }

                // 2. Adjustments (CD, DRC, MRC, ASM)
                // 2. Adjustments (DRC, MRC, ASM Commissions & Cash Discounts)
                if (res.adjustments && res.adjustments.length > 0) {
                    res.adjustments.forEach(adj => {
                        let label = 'Commission';
                        let badgeBg = '#64748b'; // default slate gray
                        let badgeColor = '#ffffff';
                        let txType = (adj.transaction_type || '').toLowerCase();
                        let notesText = adj.notes || '';

                        // Check Transaction Type first, then inspect notes as fallback
                        if (txType === 'drc_settlement' || notesText.toLowerCase().includes('drc')) {
                            label = 'DRC Commission';
                            badgeBg = '#7c3aed'; // Purple
                            badgeColor = '#ffffff';
                        } else if (txType === 'mrc_settlement' || notesText.toLowerCase().includes('mrc')) {
                            label = 'MRC Commission';
                            badgeBg = '#0284c7'; // Sky Blue
                            badgeColor = '#ffffff';
                        } else if (txType === 'asm_settlement' || notesText.toLowerCase().includes('asm')) {
                            label = 'ASM Commission';
                            badgeBg = '#0f172a'; // Dark Navy
                            badgeColor = '#ffffff';
                        } else if (notesText.includes('CD') || txType === 'settled_to_bill') {
                            label = 'Cash Discount';
                            badgeBg = '#d97706'; // Amber / Gold
                            badgeColor = '#ffffff';
                        }

                        rowsHtml += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 6px 8px;">${adj.created_at ? adj.created_at.split(' ')[0] : '-'}</td>
                                <td style="padding: 6px 8px;">
                                    <span style="background: ${badgeBg}; color: ${badgeColor}; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px; margin-right: 5px;">
                                        ${label}
                                    </span>
                                    <span style="color: #475569;">${notesText}</span>
                                </td>
                                <td style="padding: 6px 8px; text-align: right; color: #2563eb; font-weight: 700;">
                                    + ₹${parseFloat(adj.amount).toFixed(2)}
                                </td>
                            </tr>
                        `;
                    });
                }

                if (!rowsHtml) {
                    rowsHtml = '<tr><td colspan="3" style="text-align: center; padding: 12px; color: #94a3b8;">No allocations recorded for this bill.</td></tr>';
                }

                bdTbody.innerHTML = rowsHtml;
            })
            .catch(err => {
                console.error(err);
                bdTbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:10px; color:red;">Failed to load breakdown.</td></tr>';
            });
    }

    // Render Logic for SUBMITTED PAYMENTS
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

    updateStatusOptions();
    fetchPayments();
});