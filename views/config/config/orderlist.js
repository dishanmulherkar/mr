// Order List Code 

document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
       ORDER LIST PAGE  (order_list.php → #stockist, #orderTable)
       ============================================================ */
    const orderTableBody = document.getElementById('orderTable');
    if (orderTableBody) {
        const stockistSel = document.getElementById('stockist');
        const fromDate     = document.getElementById('from_date');
        const btnSearch    = document.getElementById('btnSearch');
        const btnReset     = document.getElementById('btnReset');
        const orderCount   = document.getElementById('orderCount');

        const statusClass = (s) => ({
            'Pending': 'badge-pending',
            'Approved': 'badge-approved',
            'Processed': 'badge-dispatch',
            'Dispatch': 'badge-dispatch',
            'Rejected': 'badge-rejected'
        }[s] || 'badge-pending');

        // 1. Updated Action Icons: Now uses a Responsive Bootstrap Dropdown
        function actionIcons(o) {
            let menuItems = `
                <li>
                    <a class="dropdown-item" href="${BASE_URL}OrderEntry/details/${o.order_id}" title="View Order">
                        <i class="fa fa-eye text-primary me-2"></i> View
                    </a>
                </li>
                
            `;

            if (o.status === 'Pending') {
                menuItems += `
                    <li>
                        <a class="dropdown-item" href="${BASE_URL}OrderEntry/edit/${o.order_id}" title="Edit Order">
                            <i class="fa fa-pencil text-warning me-2"></i> Edit
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger del-order" href="#" data-id="${o.order_id}" title="Delete Order">
                            <i class="fa fa-trash text-danger me-2 pointer-events-none"></i> Delete
                        </a>
                    </li>
                `;
            }

             if (o.status === 'Approved' || o.status === 'Processed') {
                menuItems += `
                   <li>
                    <a class="dropdown-item" href="${BASE_URL}invoice/pdf/${o.order_id}" target="_blank" title="Download Invoice">
                        <i class="fa fa-file-text-o text-info me-2"></i> Invoice
                    </a>
                </li>
                `;
            }

            return `
                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionBtn${o.order_id}" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-cog"></i> Actions
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="actionBtn${o.order_id}">
                        ${menuItems}
                    </ul>
                </div>
            `;
        }

        function statusText(status) {
            switch (status) {
                case 'Pending':
                    return '⏳ Pen';
                case 'Approved':
                    return '✓ Appr';
                case 'Processed':
                case 'Dispatch':
                    return '🚚 Disp';
                case 'Rejected':
                    return '✕ Rej';
                default:
                    return status;
            }
        }

        function loadOrders() {
            const params = new URLSearchParams({
                stockist_id: stockistSel.value || '',
                from_date: fromDate.value || ''
            });

            orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Loading…</td></tr>`;

            fetch(`${BASE_URL}OrderEntry/list_orders?${params.toString()}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data.length) {
                        orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No orders found</td></tr>`;
                        orderCount.textContent = 0;
                        return;
                    }
                    orderTableBody.innerHTML = res.data.map(o => `
                        <tr>
                            <td>${o.order_no}<?td>
                            <td>${o.order_date}</td>
                            <td>₹${Math.round(Number(o.grand_total)).toLocaleString('en-IN')}</td>
                            <td><span class="badge ${statusClass(o.status)}">${statusText(o.status)}</span></td>
                            <td class="text-center">${actionIcons(o)}</td>
                        </tr>
                    `).join('');
                    orderCount.textContent = res.data.length;
                })
                .catch(() => {
                    orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Failed to load orders</td></tr>`;
                });
        }

        btnSearch.addEventListener('click', loadOrders);
        btnReset.addEventListener('click', () => {
            stockistSel.value = '';
            fromDate.value = '';
            // toDate.value = ''; // Uncomment if you are using toDate
            loadOrders();
        });

        // 2. Updated Event Listener for Delete Button
        orderTableBody.addEventListener('click', function (e) {
            // Use .closest() to ensure clicking the font-awesome icon inside the <a> tag still triggers the event
            const delBtn = e.target.closest('.del-order');
            
            if (delBtn) {
                e.preventDefault();
                const id = delBtn.dataset.id;
                
                if (!confirm('Are you sure you want to delete this order?')) return;
                
                fetch(`${BASE_URL}Order/delete/${id}`, { method: 'POST' })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) loadOrders();
                        else alert(res.msg || 'Delete failed');
                    });
            }
        });

        loadOrders();
    }

    /* ============================================================
       ORDER CREATE PAGE  (create.php → #stockist-select, #cart-tbody)
       ============================================================ */
    const cartTbody = document.getElementById('cart-tbody');
    if (cartTbody) {
        const stockistSel = document.getElementById('stockist-select');
        if (stockistSel) {
            stockistSel.addEventListener('change', function () {
                if (cart.length > 0 && this.value !== activeStockistId && activeStockistId !== null) {
                    cart = [];
                    renderCart();
                    saveCartSession();
                    showToast('Stockist changed. Cart has been cleared.', 'error');
                }

                activeStockistId = this.value;
                selectedProduct       = null;
                searchInput.value     = '';
                searchInput.disabled  = !this.value;
                searchInput.placeholder = this.value ? 'Search medicine…' : 'Select a stockist first…';
                dropdown.style.display = 'none';
                setAcError('');
                if (this.value) { fetchMedicines(''); searchInput.focus(); }
            });
        }
    }
});