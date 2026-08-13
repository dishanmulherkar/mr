document.addEventListener('DOMContentLoaded', function() {
    
    const stockistFilter = document.getElementById('stockistFilter');
    const typeFilter = document.getElementById('typeFilter');
    const dateFilter = document.getElementById('dateFilter');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    const tableBody = document.getElementById('salesTableBody');

    function fetchSales() {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading records...</td></tr>';

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
                    renderTable(data.sales);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">${data.msg}</td></tr>`;
                }
            })
            .catch(error => {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Network Error</td></tr>';
            });
    }

    function renderTable(sales) {
        tableBody.innerHTML = '';

        if (!sales || sales.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No sales found.</td></tr>';
            return;
        }

        sales.forEach(s => {
            let tr = document.createElement('tr');
            
            // The PDF Download Link triggers the 'invoice' function in your controller
            let downloadBtn = `<a href="${BASE_URL}sales/invoice/${s.sale_id}" target="_blank" class="btn-download"><i class="fa fa-file-pdf"></i> Invoice</a>`;

            tr.innerHTML = `
                <td>${s.sale_date}</td>
                <td>${s.stockist_name || '-'}</td>
                <td>${s.customer_name || '-'}</td>
                <td><span style="text-transform: capitalize;">${s.customer_type}</span></td>
                <td><strong>₹${parseFloat(s.total_amt).toFixed(2)}</strong></td>
                <td>${downloadBtn}</td>
            `;
            tableBody.appendChild(tr);
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