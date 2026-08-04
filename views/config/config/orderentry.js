/* ════════════════════════════════════════════════════
   STATE
════════════════════════════════════════════════════ */
let cart                = [];   // [{ product_id, name, qty, pts, tax, discount, maxStock }]
let selectedProduct     = null; // { id, name, pts, tax, discount, stock }
let activeStockistId    = null;

// --- CART SESSION FUNCTIONS ---
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

// --- FORM STATE ---
function saveFormState() {
    const state = {
        stockist_id: document.getElementById('stockist-select').value,
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
    if (cart.length > 0 && this.value !== activeStockistId && activeStockistId !== null) {
        cart = [];
        renderCart();
        saveCartSession();
        showToast('Stockist changed. Cart has been cleared.', 'error');
    }
    
    activeStockistId = this.value;

    selectedProduct             = null;
    searchInput.value           = '';
    searchInput.disabled        = !this.value;
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
    const params = new URLSearchParams({
        q: q || ''
    });

    fetch(`OrderEntry/search_medicines?${params.toString()}`)
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
    selectedProduct          = item;
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

    const itemData = {
        product_id : selectedProduct.id,
        name       : selectedProduct.name,
        qty,
        pts        : parseFloat(selectedProduct.pts || 0),
        tax        : parseFloat(selectedProduct.tax ?? selectedProduct.sale_tax ?? 0),
        discount   : parseFloat(selectedProduct.discount ?? 16.66),
        maxStock   : selectedProduct.stock,
    };

    if (editingIndex >= 0) {
        cart[editingIndex] = itemData;
        editingIndex = -1;
        document.getElementById('btn-ok').textContent = 'OK';
    } else {
        const existing = cart.find(i => i.product_id === selectedProduct.id);
        if (existing) {
            const newQty = existing.qty + qty;
            if (newQty > existing.maxStock) {
                setAcError(`Total qty (${newQty}) exceeds available stock (${existing.maxStock}).`);
                return;
            }
            existing.qty = newQty;
        } else {
            cart.push(itemData);
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
        tbody.innerHTML = '<tr id="empty-row"><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:22px 0;">No items added yet</td></tr>';
        document.getElementById('total-amount').textContent = '₹ 0.00';
        if (document.getElementById('gst-amount')) {
            document.getElementById('gst-amount').textContent = '₹ 0.00';
        }
        if (document.getElementById('sub-total')) {
            document.getElementById('sub-total').textContent = '₹ 0.00';
        }
        document.getElementById('btn-submit').disabled = true;
        return;
    }

    let subTotal = 0;
    let totalTax = 0;

    cart.forEach((item, idx) => {
        const qty      = parseFloat(item.qty) || 0;
        const pts      = parseFloat(item.pts) || 0;
        const taxRate  = parseFloat(item.tax) || 0;         
        const discRate = parseFloat(item.discount ?? 16.66); 

        // Net PTS after discount, Taxable Amount, and Tax calculation
        const netPts     = pts * (1 - discRate / 100);
        const taxableAmt = qty * netPts;
        const taxAmt     = taxableAmt * (taxRate / 100);
        const itemTotal  = taxableAmt + taxAmt;

        subTotal += taxableAmt;
        totalTax += taxAmt;
//  <br><small style="color:var(--txt-muted);">PID: ${item.product_id} | Rate: ₹ ${pts.toFixed(2)} | Disc: ${discRate}% | GST: ${taxRate}%</small>
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td>${idx + 1}</td>
        <td>
            ${esc(item.name)}
           
        </td>
        <td>${qty}</td>
        <td>₹ ${taxableAmt.toFixed(2)}</td>
        <td>
            <button class="btn-remove" onclick="startEdit(${idx})" title="Edit" style="color:var(--violet)">✎</button>
            <button class="btn-remove" onclick="removeItem(${idx})" title="Remove">✕</button>
        </td>`;
        tbody.appendChild(tr);
    });

    const grandTotal = subTotal + totalTax;

    if (document.getElementById('sub-total')) {
        document.getElementById('sub-total').textContent = '₹ ' + subTotal.toFixed(2);
    }
    if (document.getElementById('gst-amount')) {
        document.getElementById('gst-amount').textContent = '₹ ' + totalTax.toFixed(2);
    }
    document.getElementById('total-amount').textContent = '₹ ' + grandTotal.toFixed(2);
    document.getElementById('btn-submit').disabled = false;
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
    const stockist_id = document.getElementById('stockist-select').value;

    if (!cart.length) {
        showToast('Cart is empty.', 'error');
        return;
    }

    // Calculate full net total including tax for backend summary
    const net_total = cart.reduce((sum, item) => {
        const qty      = parseFloat(item.qty) || 0;
        const pts      = parseFloat(item.pts) || 0;
        const taxRate  = parseFloat(item.tax) || 0;
        const discRate = parseFloat(item.discount ?? 16.66);
        
        const netPts     = pts * (1 - discRate / 100);
        const taxableAmt = qty * netPts;
        const taxAmt     = taxableAmt * (taxRate / 100);
        return sum + taxableAmt + taxAmt;
    }, 0);

    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Saving…';

    fetch('OrderEntry/saveorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            stockist_id,
            total_amt: net_total.toFixed(2),
            items: JSON.stringify(
                cart.map(i => {
                    const qty      = parseFloat(i.qty) || 0;
                    const pts      = parseFloat(i.pts) || 0;
                    const taxRate  = parseFloat(i.tax) || 0;
                    const discRate = parseFloat(i.discount ?? 16.66);

                    const netPts     = pts * (1 - discRate / 100);
                    const taxableAmt = qty * netPts;
                    const taxAmt     = taxableAmt * (taxRate / 100);
                    const itemTotal  = taxableAmt + taxAmt;

                    return {
                        product_id : i.product_id,
                        qty        : i.qty,
                        rate       : i.pts,
                        discount   : discRate,
                        gst        : taxRate,
                        amt        : taxableAmt.toFixed(2),
                        net_total  : itemTotal.toFixed(2)
                    };
                })
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
        if (data.success) {
            showToast('Sale saved! Entry #' + data.entry_id, 'success');

            cart = [];
            renderCart();

            sessionStorage.removeItem('saved_sales_cart');
            sessionStorage.removeItem('saved_sales_form');

            document.getElementById('stockist-select').value = '';
            stockistSel.dispatchEvent(new Event('change'));
        } else {
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
   EDIT MEDICINE
════════════════════════════════════════════════════ */
function startEdit(idx) {
    const item = cart[idx];
    editingIndex = idx;

    selectedProduct = {
        id       : item.product_id,
        name     : item.name,
        pts      : item.pts,
        tax      : item.tax,
        discount : item.discount,
        stock    : item.maxStock,
    };

    searchInput.value = item.name;
    document.getElementById('qty-input').value = item.qty;
    document.getElementById('btn-ok').textContent = 'Update';

    dropdown.style.display = 'none';
    setAcError('');
    searchInput.focus();
}

/* ════════════════════════════════════════════════════
   INITIALIZATION
════════════════════════════════════════════════════ */
$(document).ready(function(){
    const storedForm = sessionStorage.getItem('saved_sales_form');
    
    if (storedForm) {
        const formState = JSON.parse(storedForm);
        if (formState.stockist_id) {
            const stockistEl = document.getElementById('stockist-select');
            stockistEl.value = formState.stockist_id;
            stockistEl.dispatchEvent(new Event('change')); 
        }
    }

    loadCartSession();
});