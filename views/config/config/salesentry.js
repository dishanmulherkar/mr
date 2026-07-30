
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("date-from");

    const today = new Date();

    // Default value = Today
    dateInput.value = formatDate(today);

    // Minimum = 1 month before today
    const minDate = new Date(today);
    minDate.setMonth(minDate.getMonth() - 1);

    // Maximum = 7 days after today
    const maxDate = new Date(today);
    maxDate.setDate(maxDate.getDate());

    dateInput.min = formatDate(minDate);
    dateInput.max = formatDate(maxDate);

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
});

/* ════════════════════════════════════════════════════
   STATE
════════════════════════════════════════════════════ */
let cart            = [];   // [{ product_id, name, qty, pts, maxStock }]
let selectedProduct = null; // { id, name, pts, stock }
let activeStockistId = null;
// --- ADD THESE TWO FUNCTIONS ---
function saveCartSession() {
    sessionStorage.setItem('saved_sales_cart', JSON.stringify(cart));
}

function loadCartSession() {
    const stored = sessionStorage.getItem('saved_sales_cart');
    if (stored) {
        cart = JSON.parse(stored);
        renderCart();
        activeStockistId = document.getElementById('stockist-select').value;
    }
}
// -------------------------------

// --- ADD THIS RIGHT BELOW YOUR CART SESSION FUNCTIONS ---
function saveFormState() {
    const state = {
        customer_id: document.getElementById('customer-select').value,
        stockist_id: document.getElementById('stockist-select').value,
        sale_date: document.getElementById('date-from').value,
        sale_type: document.querySelector('input[name="sale_type"]:checked')?.value || 'chemist'
    };
    sessionStorage.setItem('saved_sales_form', JSON.stringify(state));
}
/* ════════════════════════════════════════════════════
   TOAST
════════════════════════════════════════════════════ */
function showToast(msg, type = '') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className   = 'show ' + type;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.className = '', 2800);
}

/* ════════════════════════════════════════════════════
   AUTOCOMPLETE
════════════════════════════════════════════════════ */
const searchInput = document.getElementById('medicine-search');
const dropdown    = document.getElementById('medicine-dropdown');
const stockistSel = document.getElementById('stockist-select');
let   debouncer   = null;

// When stockist changes → reset medicine field
stockistSel.addEventListener('change', function () {
    selectedProduct           = null;
    searchInput.value         = '';
    searchInput.disabled      = !this.value;
    searchInput.placeholder   = this.value ? 'Search medicine…' : 'Select a stockist first…';
    dropdown.style.display    = 'none';
    setAcError('');
    if (this.value) { fetchMedicines(''); searchInput.focus(); }
});

// Typing → debounced search
searchInput.addEventListener('input', function () {
    selectedProduct = null;
    setAcError('');
    clearTimeout(debouncer);
    debouncer = setTimeout(() => fetchMedicines(this.value.trim()), 280);
});

// Focus → show list
searchInput.addEventListener('focus', function () {
    if (stockistSel.value) fetchMedicines(this.value.trim());
});

function fetchMedicines(q) {
    const sid = stockistSel.value;
    if (!sid) return;
    fetch(`SalesEntry/search_medicines?stockist_id=${sid}&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => renderDropdown(data))
        .catch(() => renderDropdown([]));
}

function renderDropdown(items) {
    dropdown.innerHTML = '';

    if (!items.length) {
        dropdown.innerHTML =
            '<div class="ac-empty">No medicines found in this stockist\'s stock</div>';
        dropdown.style.display = 'block';
        return;
    }

    items.forEach(item => {

        const div = document.createElement('div');
        div.className = 'ac-item';

        const cls =
            item.stock >= 20 ? 'ok' :
            item.stock > 5 ? 'low' :
            'none';

        div.innerHTML = `
            <div class="ac-name">
                ${esc(item.name)}
            </div>

            <div class="ac-meta">
                <span class="ac-badge">
                    Batch: ${esc(item.batch_label)}
                </span>

                <span class="ac-badge ${cls}">
                    Stock: ${item.stock}
                </span>
            </div>
        `;

        div.addEventListener('mousedown', e => {
            e.preventDefault();
            pickProduct(item);
        });

        dropdown.appendChild(div);
    });

    dropdown.style.display = 'block';
}

function pickProduct(item) {
    selectedProduct        = item;
    searchInput.value      = item.name;
    dropdown.style.display = 'none';
    setAcError('');
    document.getElementById('qty-input').select();
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('.ac-wrap')) dropdown.style.display = 'none';
});

/* ════════════════════════════════════════════════════
   ADD TO CART
════════════════════════════════════════════════════ */
document.getElementById('btn-ok').addEventListener('click', addToCart);

document.getElementById('qty-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') addToCart();
});
let editingIndex = -1;  // -1 = adding new, ≥0 = editing that cart row
function addToCart() {
    setAcError('');
    const qty = parseInt(document.getElementById('qty-input').value) || 0;

    if (!selectedProduct) {
        setAcError('Please select a medicine from the list.');
        searchInput.focus();
        return;
    }
    if (qty <= 0) {
        setAcError('Enter a valid quantity (minimum 1).');
        return;
    }
    if (qty > selectedProduct.stock) {
        setAcError(`Insufficient stock — available: ${selectedProduct.stock}`);
        return;
    }

    if (editingIndex >= 0) {
        // ── EDIT MODE: replace the existing row ──────────────
        cart[editingIndex] = {
            product_id : selectedProduct.id,
            batch_id   : selectedProduct.batch_id,
            batch      : selectedProduct.batch_label,
            name       : selectedProduct.name,
            qty,
            pts        : parseFloat(selectedProduct.pts),
            maxStock   : selectedProduct.stock,
        };
        editingIndex = -1;
        document.getElementById('btn-ok').textContent = 'OK';
    } else {
        // ── ADD MODE: check duplicate then push ──────────────
        const existing = cart.find(i =>
                i.product_id === selectedProduct.id &&
                i.batch_id === selectedProduct.batch_id
            );
        if (existing) {
            const newQty = existing.qty + qty;
            if (newQty > existing.maxStock) {
                setAcError(`Total qty (${newQty}) exceeds available stock (${existing.maxStock}).`);
                return;
            }
            existing.qty = newQty;
        } else {
           cart.push({
                product_id : selectedProduct.id,
                batch_id   : selectedProduct.batch_id,
                batch      : selectedProduct.batch_label,
                name       : selectedProduct.name,
                qty,
                pts        : parseFloat(selectedProduct.pts),
                maxStock   : selectedProduct.stock,
            });
        }
    }

    renderCart();
    saveCartSession();
    searchInput.value = '';
    selectedProduct   = null;
    document.getElementById('qty-input').value = 1;
    searchInput.focus();
}

/* ════════════════════════════════════════════════════
   RENDER CART
════════════════════════════════════════════════════ */
function renderCart() {
    const tbody = document.getElementById('cart-tbody');
    tbody.innerHTML = '';

    if (!cart.length) {
        tbody.innerHTML = '<tr id="empty-row"><td colspan="6" style="text-align:center;color:var(--txt-muted);padding:22px 0;">No items added yet</td></tr>';
        document.getElementById('total-amount').textContent    = '₹ 0.00';
        document.getElementById('btn-submit').disabled         = true;
        return;
    }

    let total = 0;
    cart.forEach((item, idx) => {
        const amt = item.qty * item.pts;
        total    += amt;
        const tr  = document.createElement('tr');
        tr.innerHTML = `
        <td>${idx + 1}</td>
        <td>
    ${esc(item.name)}
    <br>
    <small>${esc(item.batch)}</small>
</td>
        <td>${item.qty}</td>
        <td>
        <button class="btn-remove" onclick="startEdit(${idx})" title="Edit" style="color:var(--violet)">✎</button>
        </td>
        <td>
        <button class="btn-remove" onclick="removeItem(${idx})" title="Remove">✕</button>
        </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('total-amount').textContent =
    '₹ ' + total.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('btn-submit').disabled      = false;
}

function removeItem(idx) {
    cart.splice(idx, 1);
    renderCart();
    saveCartSession();
}

/* ════════════════════════════════════════════════════
   SUBMIT
════════════════════════════════════════════════════ */
document.getElementById('btn-submit').addEventListener('click', function () {
    const customer_id = document.getElementById('customer-select').value;
    const stockist_id = document.getElementById('stockist-select').value;
    const sale_type   = document.querySelector('input[name="sale_type"]:checked').value;
    const sale_date   = document.getElementById('date-from').value;

    if (!customer_id) {
        showToast('Please select a customer.', 'error');
        return;
    }

    if (!stockist_id) {
        showToast('Please select a stockist.', 'error');
        return;
    }

    if (!sale_date) {
        showToast('Please select a sale date.', 'error');
        return;
    }

    if (!cart.length) {
        showToast('Cart is empty.', 'error');
        return;
    }

 const total_amt = cart.reduce(
    (sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.pts)),
    0
);

    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Saving…';

    fetch( 'SalesEntry/saveSale', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        customer_id,
        stockist_id,
        sale_date,
        type: sale_type,
        total_amt: total_amt.toFixed(2),
        items: JSON.stringify(
            cart.map(i => ({
                product_id: i.product_id,
                batch_id: i.batch_id,
                qty: i.qty,
                pts: i.pts
            }))
        )
    })
})
.then(async response => {

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.msg || "Server Error");
    }

    return data;
})
.then(data => {

    if (data.success)
    {
        showToast(
            'Sale saved! Entry #' + data.entry_id,
            'success'
        );

        cart = [];
        renderCart();

        sessionStorage.removeItem('saved_sales_cart');
        sessionStorage.removeItem('saved_sales_form');

        document.getElementById('customer-select').value = '';
        document.getElementById('stockist-select').value = '';

        document.getElementById('date-from').value =
            new Date().toISOString().split('T')[0];

        stockistSel.dispatchEvent(new Event('change'));
    }
    else
    {
        showToast(data.msg, 'error');
    }
})
.catch(error => {

    showToast(error.message, 'error');

})
.finally(() => {

    btn.disabled = false;
    btn.textContent = 'Submit';

});
});
/* ════════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════════ */
function setAcError(msg) {
    const el       = document.getElementById('ac-error');
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
}

function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/* ════════════════════════════════════════════════════
   Edit Medicine
════════════════════════════════════════════════════ */

function startEdit(idx) {
    const item = cart[idx];
    editingIndex = idx;

    selectedProduct = {
        id          : item.product_id,
        batch_id    : item.batch_id,
        batch_label : item.batch,
        name        : item.name,
        pts         : item.pts,
        stock       : item.maxStock,
    };

    searchInput.value = item.name;
    document.getElementById('qty-input').value = item.qty;

    document.getElementById('btn-ok').textContent = 'Update';

    dropdown.style.display = 'none';
    setAcError('');
    searchInput.focus();
}

/* ════════════════════════════════════════════════════
   Customer Select  
════════════════════════════════════════════════════ */
$(document).ready(function(){
    $('#customer-select').select2({
        placeholder: 'Search Customer',
        width: '40%'
    });

    // 1. Attach listeners to save form state whenever the user changes anything
    $('#customer-select, #stockist-select, #date-from').on('change', saveFormState);
    
    $('input[name="sale_type"]').on('change', function () {
        saveFormState();
        let type = $(this).val() === 'chemist' ? 'Chemist' : 'Doctor';
        loadCustomers(type); 
    });

    // 2. Check for saved form state on load
    let savedCustomerId = null;
    const storedForm = sessionStorage.getItem('saved_sales_form');
    
    if (storedForm) {
        const formState = JSON.parse(storedForm);
        
        // Restore Date
        if (formState.sale_date) {
            $('#date-from').val(formState.sale_date);
        }
        
        // Restore Type (Radio Button)
        if (formState.sale_type) {
            $(`input[name="sale_type"][value="${formState.sale_type}"]`).prop('checked', true);
        }

        // Restore Stockist (This triggers the change event, which unlocks the medicine search!)
       // Restore Stockist (Using native dispatch so the medicine search unlocks!)
        if (formState.stockist_id) {
            const stockistEl = document.getElementById('stockist-select');
            stockistEl.value = formState.stockist_id;
            stockistEl.dispatchEvent(new Event('change')); 
        }

        // Store the customer ID to pass into the AJAX loader
        savedCustomerId = formState.customer_id; 
    }

    // 3. Load Customers (Passing in the saved ID so it selects it after loading)
    let initialType = $('input[name="sale_type"]:checked').val() === 'chemist' ? 'Chemist' : 'Doctor';
    loadCustomers(initialType, savedCustomerId);

    // 4. Finally, restore the cart
    loadCartSession();
});

// const mr_id = <?php echo $mr_id; ?>;

// Update the function signature to accept the saved ID
function loadCustomers(type = '', savedCustomerId = null) {
    $.ajax({
        url: 'SalesEntry/get_customer',
        type: 'GET',
        data: {
            mr_id: mr_id,
            type: type
        },
        dataType: 'json',
        success: function(res) {
            let html = '<option value="">Select Customer</option>';

            $.each(res, function(i, row){
                html += `<option value="${row.c_id}">${row.customer_name}</option>`;
            });

            // Put the HTML in the select box
            $('#customer-select').html(html);

            // If we have a saved ID, set it now
            if (savedCustomerId) {
                $('#customer-select').val(savedCustomerId);
            }

            // Refresh the Select2 UI without triggering an infinite save loop
            $('#customer-select').trigger('change.select2');
            saveFormState();
        }
    });
}

// When stockist changes → reset medicine field AND check cart validity
stockistSel.addEventListener('change', function () {
    
    // --- NEW LOGIC: Prevent mixing stockists ---
    // If cart has items, and the stockist changed to a different one, clear the cart!
    if (cart.length > 0 && this.value !== activeStockistId && activeStockistId !== null) {
        cart = [];
        renderCart();
        saveCartSession();
        showToast('Stockist changed. Cart has been cleared.', 'error');
    }
    
    // Update the tracker to the newly selected stockist
    activeStockistId = this.value;
    // -------------------------------------------

    selectedProduct           = null;
    searchInput.value         = '';
    searchInput.disabled      = !this.value;
    searchInput.placeholder   = this.value ? 'Search medicine…' : 'Select a stockist first…';
    dropdown.style.display    = 'none';
    setAcError('');
    if (this.value) { fetchMedicines(''); searchInput.focus(); }
});