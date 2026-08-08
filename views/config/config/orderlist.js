
// Order List Code 

document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
       ORDER LIST PAGE  (order_list.php → #stockist, #orderTable)
       ============================================================ */
    const orderTableBody = document.getElementById('orderTable');
    if (orderTableBody) {
        const stockistSel = document.getElementById('stockist');
        const fromDate     = document.getElementById('from_date');
        // const toDate       = document.getElementById('to_date');
        const btnSearch    = document.getElementById('btnSearch');
        const btnReset     = document.getElementById('btnReset');
        const orderCount   = document.getElementById('orderCount');


        // const statusClass = (s) => ({
        //     'Pending':  'badge-pending',
        //     'Approved': 'badge-approved',
        //     'Rejected': 'badge-rejected'
        // }[s] || 'badge-pending');

        const statusClass = (s) => ({
    'Pending': 'badge-pending',
    'Approved': 'badge-approved',
    'Processed': 'badge-dispatch',
    'Dispatch': 'badge-dispatch',
    'Rejected': 'badge-rejected'
}[s] || 'badge-pending');

        function actionIcons(o) {
            let icons = `<a href="${BASE_URL}OrderEntry/details/${o.order_id}" title="View">👁</a>`;
            if (o.status === 'Pending') {
                icons += ` <a href="${BASE_URL}OrderEntry/edit/${o.order_id}" title="Edit">✏</a>`;
                icons += ` <a href="#" class="del-order" data-id="${o.order_id}" title="Delete">🗑</a>`;
            }
            return icons;
        }

        function statusText(status) {
            switch (status) {
                case 'Pending':
                    return '⏳ Pen';
                case 'Approved':
                    return '✓ App';
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

            console.log(stockistSel.value);

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
                            <td>${o.order_no}</td>
                            <td>${o.order_date}</td>
                            <td>₹${Number(o.total_amt).toFixed(2)}</td>
                           <td><span class="badge ${statusClass(o.status)}">${statusText(o.status)}</span></td>
                            <td>${actionIcons(o)}</td>
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
            toDate.value = '';
            loadOrders();
        });

        orderTableBody.addEventListener('click', function (e) {
            if (e.target.classList.contains('del-order')) {
                e.preventDefault();
                const id = e.target.dataset.id;
                if (!confirm('Delete this order?')) return;
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