// alert("dishan");
function searchCustomer() {

    const input = document.getElementById("customerSearch");
    const filter = input.value.toUpperCase();

    const table = document.querySelector(".rpt-table-wrap table");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {

        // Skip footer row
        if (tr[i].parentNode.tagName === "TFOOT") continue;

        const td = tr[i].getElementsByTagName("td")[1]; // Customer column

        if (td) {
            const txt = td.textContent || td.innerText;

            tr[i].style.display =
                txt.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
}

function downloadPDFs()
{
    const start_date = document.getElementById('start_date').value;
    const end_date   = document.getElementById('end_date').value;
    const customer   = document.getElementById('customerSearch').value;

    window.open(
        "SalesReport/pdfSalesReport"
        + "?start_date=" + encodeURIComponent(start_date)
        + "&end_date=" + encodeURIComponent(end_date)
        + "&customer=" + encodeURIComponent(customer),
        "_blank"
    );
}

function loadReport()
{
    const start_date = document.getElementById('start_date').value;
    const end_date   = document.getElementById('end_date').value;

    if (!start_date || !end_date)
    {
        alert('Please select date range.');
        return;
    }

    setTableState('loading');
    // hideSummary();

    fetch(
        `SalesReport/getSalesReport?start_date=${encodeURIComponent(start_date)}&end_date=${encodeURIComponent(end_date)}&mr_id=${mr_id}`
    )
    .then(r => r.json())
    .then(data => {
        if (!data.success)
        {
            setTableState('error', data.message);
            return;
        }

        renderTable(data);
    })
    .catch(() => {
        setTableState('error', 'Network error.');
    });
}

/* ── Render ─────────────────────────────────────────── */
function renderTable(response) {

    const tbody = document.getElementById('rpt-tbody');
    const tfoot = document.getElementById('rpt-tfoot');

    tbody.innerHTML = '';
    tfoot.innerHTML = '';

    const rows = response.data || [];

    if (rows.length === 0) {
        setTableState('empty');
        return;
    }

    let html = '';
    let sn = 1;

    rows.forEach(row => {

        html += `
        <tr>
            <td>${sn++}</td>
            <td>${esc(row.customer_name)}</td>
            <td>${esc(row.total_sales)}</td>
            <td class="num">₹ ${parseFloat(row.total_amt).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    tfoot.innerHTML = `
        <tr class="tfoot-total">
        <td>  </td>
        <td>  </td>
            <td colspan=""><strong>Grand Total</strong></td>
           
          <td class="num">₹ ${parseFloat(response.grand_amt).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}</td>
        </tr>
    `;
}

/* ── State helpers ──────────────────────────────────── */
function setTableState(state, msg = '') {
    const tbody = document.getElementById('rpt-tbody');
    const tfoot = document.getElementById('rpt-tfoot');
    tfoot.innerHTML = '';

    const screens = {
        loading: `<div class="state-screen"><span class="spinner"></span> Loading report…</div>`,
        empty  : `<div class="state-screen">
                    <div class="state-icon">🔍</div>
                    <div class="state-msg">No sales found</div>
                    No records for this stockist on the selected date.
                  </div>`,
        error  : `<div class="state-screen">
                    <div class="state-icon">⚠️</div>
                    <div class="state-msg">Something went wrong</div>
                    ${esc(msg)}
                  </div>`,
    };

    tbody.innerHTML = `<tr><td colspan="8">${screens[state] || ''}</td></tr>`;
}


function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}