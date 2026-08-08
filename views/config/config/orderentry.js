/* ════════════════════════════════════════════════════
    STATE & URL HELPER
════════════════════════════════════════════════════ */
let cart               = [];   
let selectedProduct    = null; 
let activeStockistId   = null;

// Fixes the 404 URL issue when editing (e.g. /OrderEntry/index/1)
function getApiUrl(endpoint) {
    const currentUrl = window.location.href;
    const baseUrl = currentUrl.split('OrderEntry')[0];
    return baseUrl + 'OrderEntry/' + endpoint;
}

// --- CART SESSION FUNCTIONS ---
function saveCartSession() {
    if (typeof existingOrderData === 'undefined' || existingOrderData === null) {
        sessionStorage.setItem('saved_sales_cart', JSON.stringify(cart));
    }
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
    if (typeof existingOrderData === 'undefined' || existingOrderData === null) {
        const state = {
            stockist_id: document.getElementById('stockist-select').value,
        };
        sessionStorage.setItem('saved_sales_form', JSON.stringify(state));
    }
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
    if (stockistSel.value || activeStockistId) fetchMedicines(this.value.trim());
});

function fetchMedicines(q) {
    const params = new URLSearchParams({
        q: q || ''
    });

    // UPDATED: Use absolute API URL helper
    fetch(getApiUrl('search_medicines') + '?' + params.toString())
        .then(r => {
            if (!r.ok) throw new Error("Network error");
            return r.json();
        })
        .then(data => renderDropdown(data))
        .catch(err => {
            console.error("Fetch Error:", err);
            renderDropdown([]);
        });
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
    searchInput.value        = item.name;
    dropdown.style.display = 'none';
    setAcError('');
    document.getElementById('qty-input').select();
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('.ac-wrap')) dropdown.style.display = 'none';
});

/* ════════════════════════════════════════════════════
    ADD TO CART (UPDATED FOR BACKEND ALLOCATION)
════════════════════════════════════════════════════ */
document.getElementById('btn-ok').addEventListener('click', addToCart);

document.getElementById('qty-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') addToCart();
});

let editingIndex = -1;  

async function addToCart() {
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

    // Calculate total desired quantity if updating existing item
    let checkQty = qty;
    const existing = cart.find(i => i.product_id === selectedProduct.id);
    
    if (existing && editingIndex < 0) {
        checkQty += existing.qty; // Add to existing quantity if not in edit mode
    }

    if (checkQty > selectedProduct.stock) {
        setAcError(`Insufficient stock — available: ${selectedProduct.stock}`);
        return;
    }

    const btnOk = document.getElementById('btn-ok');
    btnOk.disabled = true;
    btnOk.textContent = 'Calculating...';

    try {
        // Fetch exact batch allocation and rates from the backend
        const formData = new URLSearchParams();
        formData.append('product_id', selectedProduct.id);
        formData.append('qty', checkQty);

        const response = await fetch(getApiUrl('previewAllocation'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        
        const data = await response.json();

        if (!data.success) {
            setAcError(data.msg || 'Stock allocation failed');
            return;
        }

        // Sum up exact amounts from the allocated batches returned by backend
        let exact_amt = 0;
        let exact_tax = 0;
        
        data.data.allocated_batches.forEach(batch => {
            exact_amt += batch.amt;
            exact_tax += (batch.net_total - batch.amt);
        });

        // Create the cart item
        const itemData = {
            product_id: selectedProduct.id,
            name: selectedProduct.name,
            qty: checkQty, // Use the full calculated quantity
            exact_amt: exact_amt,
            exact_tax: exact_tax,
            exact_net: exact_amt + exact_tax,
            maxStock: selectedProduct.stock
        };

        if (editingIndex >= 0) {
            cart[editingIndex] = itemData; // Update existing
            editingIndex = -1;
        } else {
            if (existing) {
                // Update the already existing item in cart
                existing.qty = itemData.qty;
                existing.exact_amt = itemData.exact_amt;
                existing.exact_tax = itemData.exact_tax;
                existing.exact_net = itemData.exact_net;
            } else {
                cart.push(itemData); // Add new
            }
        }

        renderCart();
        saveCartSession();
        searchInput.value = '';
        selectedProduct = null;
        document.getElementById('qty-input').value = 1;
        searchInput.focus();

    } catch (e) {
        setAcError('Server error while calculating rates.');
    } finally {
        btnOk.disabled = false;
        btnOk.textContent = 'OK';
    }
}

/* ════════════════════════════════════════════════════
    RENDER CART (UPDATED TO USE PRE-CALCULATED AMOUNTS)
════════════════════════════════════════════════════ */
function renderCart() {
    const tbody = document.getElementById('cart-tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (!Array.isArray(cart)) {
        cart = [];
    }
    cart = cart.filter(item => item !== null && typeof item === 'object' && (item.product_id || item.id));

    if (!cart.length) {
        tbody.innerHTML = '<tr id="empty-row"><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:22px 0;">No items added yet</td></tr>';
        document.getElementById('total-amount').textContent = '₹ 0.00';
        if (document.getElementById('gst-amount')) document.getElementById('gst-amount').textContent = '₹ 0.00';
        if (document.getElementById('sub-total')) document.getElementById('sub-total').textContent = '₹ 0.00';
        document.getElementById('btn-submit').disabled = true;
        return;
    }

    let subTotal = 0;
    let totalTax = 0;

    cart.forEach((item, idx) => {
        // We now rely on exact_amt and exact_tax calculated by the backend!
        const taxableAmt = parseFloat(item.exact_amt) || 0;
        const taxAmt     = parseFloat(item.exact_tax) || 0;

        subTotal += taxableAmt;
        totalTax += taxAmt;

        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td>${idx + 1}</td>
        <td>${typeof esc === 'function' ? esc(item.name || 'Product') : (item.name || 'Product')}</td>
        <td>${item.qty}</td>
        <td>₹ ${taxableAmt.toFixed(2)}</td>
        <td>
            <button class="btn-remove" onclick="startEdit(${idx})" title="Edit" style="color:var(--violet)">✎</button>
            <button class="btn-remove" onclick="removeItem(${idx})" title="Remove">✕</button>
        </td>`;
        tbody.appendChild(tr);
    });

    const grandTotal = subTotal + totalTax;

    if (document.getElementById('sub-total')) document.getElementById('sub-total').textContent = '₹ ' + subTotal.toFixed(2);
    if (document.getElementById('gst-amount')) document.getElementById('gst-amount').textContent = '₹ ' + totalTax.toFixed(2);
    
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
    
    const hiddenStockist = document.getElementById('hidden-stockist');
    const stockist_id = hiddenStockist ? hiddenStockist.value : document.getElementById('stockist-select').value;
    
    const order_idEl = document.getElementById('order_id');
    const order_dateEl = document.getElementById('order_date');
    const order_id = order_idEl ? order_idEl.value : 0;
    const sale_date = order_dateEl ? order_dateEl.value : new Date().toISOString().split('T')[0];

    if (!cart.length) {
        showToast('Cart is empty.', 'error');
        return;
    }

  const net_total = cart.reduce((sum, item) => {
        // Use the exact same pre-calculated values as renderCart()
        const taxableAmt = parseFloat(item.exact_amt) || 0;
        const taxAmt     = parseFloat(item.exact_tax) || 0;
        
        return sum + taxableAmt + taxAmt;
    }, 0);

    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Saving…';

const isEdit = order_id && parseInt(order_id) > 0;

fetch(getApiUrl(isEdit ? 'updateOrder' : 'saveOrder'), {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
        order_id: order_id,
        sale_date: sale_date,
        stockist_id: stockist_id,
        total_amt: net_total.toFixed(2),
        items: JSON.stringify(
            cart.map(i => ({
                product_id: i.product_id,
                batch_id: i.batch_id || 0,
                qty: i.qty
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
        if (data.success) {
            showToast(order_id > 0 ? 'Order updated successfully!' : 'Order saved! Entry #' + data.entry_id, 'success');
            
            if (order_id > 0) {
                setTimeout(() => {
                    window.location.href = getApiUrl('view');
                }, 1500);
            } else {
                cart = [];
                renderCart();
                sessionStorage.removeItem('saved_sales_cart');
                sessionStorage.removeItem('saved_sales_form');
                document.getElementById('stockist-select').value = '';
                stockistSel.dispatchEvent(new Event('change'));
            }
        } else {
            showToast(data.msg, 'error');
        }
    })
    .catch(error => {
        showToast(error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = order_id > 0 ? 'Update Order' : 'Submit';
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
    EDIT MEDICINE (SIMPLIFIED)
════════════════════════════════════════════════════ */
function startEdit(idx) {
    const item = cart[idx];
    editingIndex = idx;

    selectedProduct = {
        id: item.product_id,
        name: item.name,
        stock: item.maxStock,
        // No need for pts/tax/discount locally anymore, backend handles it
    };

    searchInput.value = item.name;
    document.getElementById('qty-input').value = item.qty;
    document.getElementById('btn-ok').textContent = 'Update';

    dropdown.style.display = 'none';
    setAcError('');
    searchInput.focus();
}

/* ════════════════════════════════════════════════════
    INITIALIZATION (UPDATED DATA PARSING)
════════════════════════════════════════════════════ */
$(document).ready(function(){
    if (typeof existingOrderData !== 'undefined' && existingOrderData !== null) {
        
        cart = existingOrderData.items.map(item => ({
            product_id: parseInt(item.product_id),
            name: item.name,
            qty: parseInt(item.qty),
            // Map existing DB values properly to our exact amounts
            exact_amt: parseFloat(item.amt),
            exact_tax: parseFloat(item.net_total) - parseFloat(item.amt),
            exact_net: parseFloat(item.net_total),
            maxStock: 99999 
        }));
        
        activeStockistId = existingOrderData.stockist_id;
        
        const searchInputEl = document.getElementById('medicine-search');
        if (searchInputEl) {
            searchInputEl.disabled = false;
            searchInputEl.placeholder = 'Search medicine…';
        }
        
        renderCart();

    } else {
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
    }
});