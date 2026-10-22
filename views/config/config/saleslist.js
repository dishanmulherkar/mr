document.addEventListener('DOMContentLoaded', function() {
    
    const stockistFilter = document.getElementById('stockistFilter');
    const typeFilter = document.getElementById('typeFilter');
    const dateFilter = document.getElementById('dateFilter');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    
    // Updated container reference
    const listContainer = document.getElementById('salesListContainer');

    // Helper: Action Menu Generator (Edit, Delete, Download)
    function salesActionMenu(s) {
        return `
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionBtn${s.s_id}" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-cog"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="actionBtn${s.s_id}">
                    <li>
                        <a class="dropdown-item" href="${BASE_URL}invoice/sales_pdf/${s.s_id}" target="_blank">
                            <i class="fa fa-download text-primary me-2"></i> Download PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="${BASE_URL}SalesEntry/edit/${s.s_id}">
                            <i class="fa fa-pencil text-warning me-2"></i> Edit
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger btn-delete-sale" href="javascript:void(0);" data-id="${s.s_id}">
                            <i class="fa fa-trash text-danger me-2"></i> Delete
                        </a>
                    </li>
                </ul>
            </div>
        `;
    }

    function fetchSales() {
        listContainer.innerHTML = '<div style="text-align:center; padding:20px; width:100%;">Loading records...</div>';

        const params = new URLSearchParams({
            mr_id: mr_id,
            stockist_id: stockistFilter.value,
            sale_type: typeFilter.value,
            sale_date: dateFilter.value
        });

        fetch(BASE_URL + 'SalesEntry/fetch_list?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderCards(data.sales);
                } else {
                    listContainer.innerHTML = `<div style="text-align:center; padding:20px; color:red; width:100%;">${data.msg || 'No records found'}</div>`;
                }
            })
            .catch(error => {
                listContainer.innerHTML = '<div style="text-align:center; padding:20px; color:red; width:100%;">Network Error</div>';
            });
    }

    function renderCards(sales) {
        listContainer.innerHTML = '';

        if (!sales || sales.length === 0) {
            listContainer.innerHTML = '<div style="text-align:center; padding:20px; width:100%;">No sales found.</div>';
            return;
        }

        sales.forEach(s => {
            let card = document.createElement('div');
            card.className = 'sale-card';
            
            // Format Type to just "Chem" or "Doc"
            let cType = s.customer_type ? s.customer_type.toLowerCase() : '';
            let typeShort = cType === 'chemist' ? 'Chem' : (cType === 'doctor' ? 'Doc' : cType);

            // Date formatting
            let dateOnly = s.sale_date ? s.sale_date.split(' ')[0] : '';

            // Build the compact 2-row card HTML with actions dropdown
            card.innerHTML = `
                <!-- Row 1: Customer Name + Type Badge | Amount -->
                <div class="card-row d-flex justify-content-between align-items-center">
                    <div class="customer-info">
                        <strong>${s.customer_name || '-'}</strong> 
                        <span class="badge badge-type bg-light text-dark border ms-1">${typeShort}</span>
                    </div>
                    <div class="text-amount fw-bold">₹${parseFloat(s.total_amt).toFixed(2)}</div>
                </div>
                
                <!-- Row 2: Stockist | Date & Actions Settings -->
                <div class="card-row d-flex justify-content-between align-items-center mt-2">
                    <div class="stockist-info text-muted small">
                        ${s.stockist_name || '-'}
                    </div>
                    <div class="date-action-wrap d-flex align-items-center gap-2">
                        <span class="sale-card-date small text-muted">${dateOnly}</span>
                        ${salesActionMenu(s)}
                    </div>
                </div>
            `;
            
            listContainer.appendChild(card);
        });
    }

    // Delegated click handler for Delete confirmation & action
    listContainer.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-delete-sale');
        if (!deleteBtn) return;

        const saleId = deleteBtn.getAttribute('data-id');
        
        if (confirm('Are you sure you want to delete this sale entry?')) {
            fetch(`${BASE_URL}SalesEntry/delete/${saleId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    fetchSales(); // Refresh the list
                } else {
                    alert(res.msg || 'Unable to delete record.');
                }
            })
            .catch(() => alert('Network error while deleting entry.'));
        }
    });

    btnSearch.addEventListener('click', fetchSales);
    
    btnReset.addEventListener('click', () => {
        stockistFilter.value = '';
        typeFilter.value = '';
        dateFilter.value = '';
        fetchSales();
    });

    fetchSales(); // Load on page load
});