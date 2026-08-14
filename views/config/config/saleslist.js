document.addEventListener('DOMContentLoaded', function() {
    
    const stockistFilter = document.getElementById('stockistFilter');
    const typeFilter = document.getElementById('typeFilter');
    const dateFilter = document.getElementById('dateFilter');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    
    // Updated container reference
    const listContainer = document.getElementById('salesListContainer');

    function fetchSales() {
        listContainer.innerHTML = '<div style="text-align:center; padding:20px; width:100%;">Loading records...</div>';

        const params = new URLSearchParams({
            mr_id: mr_id,
            stockist_id: stockistFilter.value,
            sale_type: typeFilter.value,
            sale_date: dateFilter.value
        });

        // Call your controller endpoint
        fetch(BASE_URL + 'SalesEntry/fetch_list?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderCards(data.sales);
                } else {
                    listContainer.innerHTML = `<div style="text-align:center; padding:20px; color:red; width:100%;">${data.msg}</div>`;
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
            
            // Tiny Download Arrow Button
            let downloadBtn = `<a href="${BASE_URL}invoice/sales_pdf/${s.s_id}" target="_blank" class="btn-download-icon" title="Download"><i class="fa fa-download"></i></a>`;
                                
            // Build the compact 2-row card HTML
            card.innerHTML = `
                <!-- Row 1: Customer Name + Type Badge | Amount -->
                <div class="card-row">
                    <div class="customer-info">
                        ${s.customer_name || '-'} 
                        <span class="badge-type">${typeShort}</span>
                    </div>
                    <div class="text-amount">₹${parseFloat(s.total_amt).toFixed(2)}</div>
                </div>
                
                <!-- Row 2: Stockist | Date & Download -->
                <div class="card-row">
                    <div class="stockist-info">
                        ${s.stockist_name || '-'}
                    </div>
                    <div class="date-action-wrap">
                        <span class="sale-card-date">${dateOnly}</span>
                        ${downloadBtn}
                    </div>
                </div>
            `;
            
            listContainer.appendChild(card);
        });
    }

    btnSearch.addEventListener('click', fetchSales);
    
    btnReset.addEventListener('click', () => {
        stockistFilter.value = '';
        typeFilter.value = '';
        dateFilter.value = '';
        fetchSales();
    });

    fetchSales(); // Load on page load
});