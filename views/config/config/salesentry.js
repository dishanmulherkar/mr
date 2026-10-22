document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("date-from");

    if (dateInput) {
        const today = new Date();

        // If not already set by Edit mode in PHP
        if (!dateInput.value) {
            dateInput.value = formatDate(today);
        }

        // Minimum = 1 month before today
        const minDate = new Date(today);
        minDate.setMonth(minDate.getMonth() - 1);

        // Maximum = today
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
    }
});


    function calculateTotals() {
    // 1. Calculate Base Totals from your cart/items list
    // (Replace `selectedItems` with your actual cart/items array variable)
    let totalPts = 0;
    let totalMrp = 0;

    if (Array.isArray(window.selectedItems)) {
        window.selectedItems.forEach(item => {
            const qty = parseFloat(item.qty || 0);
            const rate = parseFloat(item.pts || item.rate || 0);
            const mrp = parseFloat(item.mrp || 0);

            totalPts += qty * rate;
            totalMrp += qty * mrp;
        });
    }

    // 2. Read Discount Percentage
    const discInput = document.getElementById('discount-percent');
    let discPercent = parseFloat(discInput ? discInput.value : 0) || 0;

    if (discPercent < 0) discPercent = 0;
    if (discPercent > 100) discPercent = 100;

    // 3. Compute Discount Amount & Net Payable (Calculated on Total PTS)
    const discAmount = (totalPts * discPercent) / 100;
    const netAmount = Math.max(0, totalPts - discAmount);

    // 4. Update the DOM values
    document.getElementById('total-amount').textContent = `₹ ${totalPts.toFixed(2)}`;
    document.getElementById('total-amount-pts').textContent = `₹ ${totalMrp.toFixed(2)}`;
    document.getElementById('discount-amount').textContent = `- ₹ ${discAmount.toFixed(2)}`;
    
    const netEl = document.getElementById('net-amount');
    if (netEl) {
        netEl.textContent = `₹ ${netAmount.toFixed(2)}`;
    }

    return {
        totalPts,
        totalMrp,
        discPercent,
        discAmount,
        netAmount
    };
}
/* ════════════════════════════════════════════════════
   STATE & FLAGS
════════════════════════════════════════════════════ */
let cart            = [];   // [{ product_id, batch_id, batch, name, qty, pts, mrp, maxStock }]
let selectedProduct = null; // { id, batch_id, batch_label, name, pts, mrp, stock }
let activeStockistId = null;
let editingIndex    = -1;   // -1 = adding new, >= 0 = editing that cart row
let debouncer       = null;

// Determine if we are in Edit Mode
const isEditMode = typeof editSaleData !== 'undefined' && Boolean(editSaleData.is_edit);

/* ════════════════════════════════════════════════════
   SESSION STORAGE HELPERS (BYPASSED IN EDIT MODE)
════════════════════════════════════════════════════ */
function saveCartSession() {
    if (isEditMode) return;
    sessionStorage.setItem('saved_sales_cart', JSON.stringify(cart));
}

function loadCartSession() {
    if (isEditMode) return;
    const stored = sessionStorage.getItem('saved_sales_cart');
    if (stored) {
        try {
            cart = JSON.parse(stored);
            renderCart();
            activeStockistId = document.getElementById('stockist-select')?.value || null;
        } catch (e) {
            cart = [];
        }
    }
}

function saveFormState() {
    if (isEditMode) return;
    const state = {
        customer_id      : document.getElementById('customer-select')?.value || '',
        stockist_id      : document.getElementById('stockist-select')?.value || '',
        sale_date        : document.getElementById('date-from')?.value || '',
        sale_type        : document.querySelector('input[name="sale_type"]:checked')?.value || 'chemist',
        discount_percent : document.getElementById('discount-percent')?.value || '0'
    };
    sessionStorage.setItem('saved_sales_form', JSON.stringify(state));
}

/* ════════════════════════════════════════════════════
   TOAST & ERROR NOTIFICATIONS
════════════════════════════════════════════════════ */
function showToast(msg, type = '') {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className   = 'show ' + type;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.className = '', 2800);
}

function setAcError(msg) {
    const el = document.getElementById('ac-error');
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
}

function esc(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/* ════════════════════════════════════════════════════
   CYNORTHWYYDD CYFRIFO STOC (GET REMAINING STOCK)
════════════════════════════════════════════════════ */
function getRemainingStock(productId, batchId, totalStock, excludeIdx = -1) {
    let alreadyInCart = 0;
    let originalSavedQty = 0;

    // 1. Cyfrifo faint sydd eisoes yn y drol ar hyn o bryd
    cart.forEach((item, idx) => {
        if (idx !== excludeIdx && parseInt(item.product_id, 10) === parseInt(productId, 10)) {
            if (!batchId || !item.batch_id || parseInt(item.batch_id, 10) === parseInt(batchId, 10)) {
                alreadyInCart += parseFloat(item.qty || 0);
            }
        }
    });

    // 2. Mewn Modd Golygu (Edit Mode): ychwanegu'r swm gwreiddiol a arbedwyd yn ôl
    if (typeof isEditMode !== 'undefined' && isEditMode && typeof editSaleData !== 'undefined' && Array.isArray(editSaleData.items)) {
        const origItem = editSaleData.items.find(it => {
            const pMatch = parseInt(it.product_id ?? it.p_id, 10) === parseInt(productId, 10);
            const bMatch = !batchId || !it.batch_id || parseInt(it.batch_id, 10) === parseInt(batchId, 10);
            return pMatch && bMatch;
        });

        if (origItem) {
            originalSavedQty = parseFloat(origItem.qty || 0);
        }
    }

    // Y fformiwla derfynol: stoc y gronfa ddata + maint gwreiddiol - maint y drol
    const effectiveStock = totalStock + originalSavedQty - alreadyInCart;
    return Math.max(0, effectiveStock);
}

/* ════════════════════════════════════════════════════
   AUTOCOMPLETE
════════════════════════════════════════════════════ */
const searchInput = document.getElementById('medicine-search');
const dropdown    = document.getElementById('medicine-dropdown');
const stockistSel = document.getElementById('stockist-select');

if (stockistSel && searchInput) {
    // When stockist changes -> reset medicine field AND check cart validity
    stockistSel.addEventListener('change', function () {
        if (cart.length > 0 && this.value !== activeStockistId && activeStockistId !== null) {
            if (!confirm('Stockist changed. Current cart items will be cleared. Continue?')) {
                this.value = activeStockistId;
                return;
            }
            cart = [];
            renderCart();
            saveCartSession();
            showToast('Stockist changed. Cart has been cleared.', 'error');
        }

        activeStockistId = this.value;
        selectedProduct  = null;
        searchInput.value = '';
        searchInput.disabled = !this.value;
        searchInput.placeholder = this.value ? 'Search medicine…' : 'Select a stockist first…';
        if (dropdown) dropdown.style.display = 'none';
        setAcError('');
        if (this.value) { fetchMedicines(''); searchInput.focus(); }
    });

    searchInput.addEventListener('input', function () {
        selectedProduct = null;
        setAcError('');
        clearTimeout(debouncer);
        debouncer = setTimeout(() => fetchMedicines(this.value.trim()), 280);
    });

    searchInput.addEventListener('focus', function () {
        if (stockistSel.value) fetchMedicines(this.value.trim());
    });
}

function fetchMedicines(q) {
    const sid = stockistSel ? stockistSel.value : '';
    if (!sid) return;
    fetch(`${BASE_URL}SalesEntry/search_medicines?stockist_id=${sid}&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => renderDropdown(data))
        .catch(() => renderDropdown([]));
}

  // <div style="font-size:12px; color:var(--txt-muted);">
                            //     PTS: ₹${pts.toFixed(2)} | MRP: ₹${mrp.toFixed(2)}
                            // </div>
function renderDropdown(items) {
    if (!dropdown) return;
    dropdown.innerHTML = '';

    if (!items || !items.length) {
        dropdown.innerHTML = '<div class="ac-empty">No medicines found in this stockist\'s stock</div>';
        dropdown.style.display = 'block';
        return;
    }

    let visibleItemsCount = 0;

    items.forEach(item => {
        const prodId = parseInt(item.id || item.product_id, 10);
        const prodName = esc(item.name || item.product_name);

        // --- CASE 1: Nested structure (Product has a batches array) ---
        if (Array.isArray(item.batches)) {
            // Filter batches that still have available stock after cart deductions
            const availableBatches = [];
            let totalAvailableStock = 0;

            item.batches.forEach(batch => {
                const batchId = parseInt(batch.batch_id, 10);
                const originalStock = parseFloat(batch.stock ?? 0);
                
                // Deduct whatever is already in the cart for this exact product & batch
                const remainingStock = typeof getRemainingStock === 'function'
                    ? getRemainingStock(prodId, batchId, originalStock, editingIndex)
                    : originalStock;

                if (remainingStock > 0) {
                    availableBatches.push({
                        ...batch,
                        stock: remainingStock // Display & use reduced stock
                    });
                    totalAvailableStock += remainingStock;
                }
            });

            // If all batches for this medicine are fully consumed in cart, hide the medicine
            if (!availableBatches.length) {
                return;
            }

            visibleItemsCount++;

            const group = document.createElement('div');
            group.className = 'ac-group';

            group.innerHTML = `
                <div class="ac-group-header" style="padding:6px 10px; font-weight:600; background:rgba(0,0,0,0.03); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px;">${prodName}</span>
                    <span style="font-size:11px; color:var(--txt-muted);">Total: ${totalAvailableStock}</span>
                </div>
            `;

            availableBatches.forEach(batch => {
                const bDiv = document.createElement('div');
                bDiv.className = 'ac-item ac-batch-item';
                bDiv.style.paddingLeft = '10px';

                const stock = batch.stock;
                const cls = stock >= 20 ? 'ok' : (stock > 5 ? 'low' : 'none');
                const pts = parseFloat(batch.pts ?? 0);
                const mrp = parseFloat(batch.mrp ?? 0);
                const exp = batch.expiry_date ? ` | Exp: ${esc(batch.expiry_date)}` : '';

                bDiv.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                        <div>
                            <div style="font-size:12px; color:var(--txt-muted);">
                                ${esc(batch.batch_no)} &nbsp;/ ₹${mrp.toFixed(2)}
                            </div>
                        </div>
                        <span class="ac-badge ${cls}">Stock: ${stock}</span>
                    </div>
                `;

                bDiv.addEventListener('mousedown', e => {
                    e.preventDefault();
                    pickProduct({
                        id: prodId,
                        name: item.name || item.product_name,
                        ...batch
                    });
                });

                group.appendChild(bDiv);
            });

            dropdown.appendChild(group);
            return;
        }

        // --- CASE 2: Flat list (Single batch row per item) ---
        const batchId = item.batch_id ? parseInt(item.batch_id, 10) : null;
        const rawStock = parseFloat(item.stock ?? item.current_qty ?? 0);
        const availStock = typeof getRemainingStock === 'function'
            ? getRemainingStock(prodId, batchId, rawStock, editingIndex)
            : rawStock;

        // Hide row if stock is zero
        if (availStock <= 0) return;

        visibleItemsCount++;

        const div = document.createElement('div');
        div.className = 'ac-item';

        const cls = availStock >= 20 ? 'ok' : (availStock > 5 ? 'low' : 'none');
        const itemPts = parseFloat(item.pts ?? item.rate ?? 0);
        const itemMrp = parseFloat(item.mrp ?? 0);
        const batchInfo = item.batch_no ? ` <span style="font-size:12px; font-weight:bold; color:var(--txt-main);">[Batch: ${esc(item.batch_no)}]</span>` : '';
        const expInfo = item.expiry_date ? ` | Exp: ${esc(item.expiry_date)}` : '';

        div.innerHTML = `
            <div class="ac-name">${prodName}${batchInfo}</div>
            <div class="ac-meta">
                <span class="ac-badge ${cls}">Stock: ${availStock}</span>
                <span style="font-size:12px; color:var(--txt-muted); margin-left:8px;">
                    PTS: ₹${itemPts.toFixed(2)} | MRP: ₹${itemMrp.toFixed(2)}${expInfo}
                </span>
            </div>
        `;

        div.addEventListener('mousedown', e => {
            e.preventDefault();
            pickProduct({
                ...item,
                stock: availStock
            });
        });

        dropdown.appendChild(div);
    });

    // If every item returned had 0 remaining stock, show empty message
    if (visibleItemsCount === 0) {
        dropdown.innerHTML = '<div class="ac-empty">No medicines found in this stockist\'s stock</div>';
    }

    dropdown.style.display = 'block';
}

function pickProduct(item) {
    selectedProduct = {
        id          : parseInt(item.id || item.product_id, 10),
        batch_id    : item.batch_id || null,
        batch_label : item.batch_no || item.batch_label || '',
        name        : item.name || item.product_name,
        pts         : parseFloat(item.pts ?? item.rate ?? 0),
        mrp         : parseFloat(item.mrp ?? 0),
        stock       : parseFloat(item.stock ?? item.current_qty ?? 0)
    };

    searchInput.value = selectedProduct.name;
    if (dropdown) dropdown.style.display = 'none';
    setAcError('');
    const qtyInput = document.getElementById('qty-input');
    if (qtyInput) qtyInput.select();
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('.ac-wrap') && dropdown) {
        dropdown.style.display = 'none';
    }
});

/* ════════════════════════════════════════════════════
   ADD / UPDATE CART WITH RIGID STOCK RESTRICTION
════════════════════════════════════════════════════ */
const btnOk = document.getElementById('btn-ok');
const qtyInp = document.getElementById('qty-input');

if (btnOk) btnOk.addEventListener('click', addToCart);
if (qtyInp) {
    qtyInp.addEventListener('keydown', e => {
        if (e.key === 'Enter') addToCart();
    });
}

function addToCart() {
    setAcError('');
    const qtyInput = document.getElementById('qty-input');
    const qty = parseInt(qtyInput ? qtyInput.value : 0, 10) || 0;

    if (!selectedProduct) {
        setAcError('Please select a medicine from the list.');
        if (searchInput) searchInput.focus();
        return;
    }
    if (qty <= 0) {
        setAcError('Enter a valid quantity (minimum 1).');
        return;
    }

    const availableStock = parseFloat(selectedProduct.stock || 0);

    // ── EDIT ROW MODE ─────────────────────────────────
    // ── MODD GOLYGU RHES (EDIT ROW MODE) ───────────────
    if (editingIndex >= 0) {
        const allowable = getRemainingStock(
            selectedProduct.id, 
            selectedProduct.batch_id, 
            availableStock, 
            editingIndex
        );

        if (qty > allowable) {
            setAcError(`Methu ychwanegu ${qty}. Dim ond ${allowable} sydd ar gael.`);
            return;
        }

        cart[editingIndex] = {
            product_id : selectedProduct.id,
            batch_id   : selectedProduct.batch_id,
            batch      : selectedProduct.batch_label,
            name       : selectedProduct.name,
            qty        : qty,
            pts        : parseFloat(selectedProduct.pts || 0),
            mrp        : parseFloat(selectedProduct.mrp || 0),
            maxStock   : allowable
        };

        editingIndex = -1;
        if (btnOk) btnOk.textContent = 'OK';
    }
    // ── ADD NEW / MERGE ROW MODE ──────────────────────
    else {
        const existingIdx = cart.findIndex(i => 
            i.product_id === selectedProduct.id && 
            (!selectedProduct.batch_id || i.batch_id === selectedProduct.batch_id)
        );

        const allowable = getRemainingStock(selectedProduct.id, selectedProduct.batch_id, availableStock, existingIdx);

        if (qty > allowable) {
            setAcError(`Cannot add ${qty}. Available stock is ${allowable} (Total available: ${availableStock}).`);
            return;
        }

        if (existingIdx > -1) {
            cart[existingIdx].qty += qty;
            cart[existingIdx].maxStock = availableStock;
        } else {
            cart.push({
                product_id : selectedProduct.id,
                batch_id   : selectedProduct.batch_id,
                batch      : selectedProduct.batch_label,
                name       : selectedProduct.name,
                qty        : qty,
                pts        : parseFloat(selectedProduct.pts || 0),
                mrp        : parseFloat(selectedProduct.mrp || 0),
                maxStock   : availableStock
            });
        }
    }

    renderCart();
    saveCartSession();

    if (searchInput) searchInput.value = '';
    selectedProduct = null;
    if (qtyInput) qtyInput.value = 1;
    if (searchInput) searchInput.focus();
}

function startEdit(idx) {
    const item = cart[idx];
    editingIndex = idx;

    selectedProduct = {
        id          : item.product_id,
        batch_id    : item.batch_id,
        batch_label : item.batch,
        name        : item.name,
        pts         : item.pts,
        mrp         : item.mrp,
        stock       : item.maxStock
    };

    if (searchInput) searchInput.value = item.name;
    const qtyInput = document.getElementById('qty-input');
    if (qtyInput) qtyInput.value = item.qty;

    if (btnOk) btnOk.textContent = 'Update';
    if (dropdown) dropdown.style.display = 'none';
    setAcError('');
    if (qtyInput) qtyInput.focus();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    if (editingIndex === idx) {
        editingIndex = -1;
        if (btnOk) btnOk.textContent = 'OK';
        if (searchInput) searchInput.value = '';
        selectedProduct = null;
    }
    renderCart();
    saveCartSession();
}

/* ════════════════════════════════════════════════════
   RENDER CART & DUAL TOTALS
════════════════════════════════════════════════════ */
function renderCart() {
    const tbody = document.getElementById('cart-tbody');
    const totalAmountEl = document.getElementById('total-amount');
    const totalAmountPtsEl = document.getElementById('total-amount-pts');
    const discountAmountEl = document.getElementById('discount-amount');
    const netAmountEl = document.getElementById('net-amount');
    const discountInput = document.getElementById('discount-percent');
    const btnSubmit = document.getElementById('btn-submit');

    if (!tbody) return;
    tbody.innerHTML = '';

    if (!cart.length) {
        tbody.innerHTML = '<tr id="empty-row"><td colspan="6" style="text-align:center;color:var(--txt-muted);padding:22px 0;">No items added yet</td></tr>';
        if (totalAmountEl) totalAmountEl.textContent = '₹ 0.00';
        if (totalAmountPtsEl) totalAmountPtsEl.textContent = '₹ 0.00';
        if (discountAmountEl) discountAmountEl.textContent = '- ₹ 0.00';
        if (netAmountEl) netAmountEl.textContent = '₹ 0.00';
        if (btnSubmit) btnSubmit.disabled = true;
        return;
    }

    let totalPts = 0;
    let totalMrp = 0;

    cart.forEach((item, idx) => {
        const qty      = parseFloat(item.qty) || 0;
        const safe_pts = Number(item.pts ?? item.rate) || 0;
        const safe_mrp = Number(item.mrp) || 0;

        const amt     = qty * safe_pts;
        const mrp_amt = qty * safe_mrp;

        totalPts += amt;
        totalMrp += mrp_amt;

        const batchDisplay = item.batch_no || item.batch;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${idx + 1}</td>
            <td>
                <strong>${esc(item.name || item.product_name)}</strong>
                ${batchDisplay ? `<br><small style="color:var(--txt-muted)">Batch: ${esc(batchDisplay)}</small>` : ''}
            </td>
            <td>${qty}</td>
            <td>₹${amt.toFixed(2)}</td>
            <td>
                <button type="button" class="btn-remove" onclick="startEdit(${idx})" title="Edit" style="color:var(--violet)">✎</button>
                <button type="button" class="btn-remove" onclick="removeItem(${idx})" title="Remove">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // ── Discount & Net Calculation ──
    let discPercent = parseFloat(discountInput ? discountInput.value : 0) || 0;
    if (discPercent < 0) discPercent = 0;
    if (discPercent > 100) discPercent = 100;

    // Calculate discount against total PTS
    const discountAmt = (totalMrp * discPercent) / 100;
    const netPayable  = Math.max(0, totalMrp - discountAmt);

    // ── Update Elements ──
    if (totalAmountEl) {
        totalAmountEl.textContent = '₹ ' + totalPts.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (totalAmountPtsEl) {
        totalAmountPtsEl.textContent = '₹ ' + totalMrp.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (discountAmountEl) {
        discountAmountEl.textContent = '- ₹ ' + discountAmt.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (netAmountEl) {
        netAmountEl.textContent = '₹ ' + netPayable.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    if (btnSubmit) btnSubmit.disabled = false;
}

/* ════════════════════════════════════════════════════
   SUBMIT / UPDATE HANDLER
════════════════════════════════════════════════════ */
const btnSubmit = document.getElementById('btn-submit');
if (btnSubmit) {
    btnSubmit.addEventListener('click', function () {
        const customer_id = document.getElementById('customer-select')?.value;
        const stockist_id = document.getElementById('stockist-select')?.value;
        const sale_type   = document.querySelector('input[name="sale_type"]:checked')?.value || 'chemist';
        const sale_date   = document.getElementById('date-from')?.value;
        const sale_id     = parseInt(document.getElementById('sale_id')?.value || 0, 10);

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
            (sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.pts || 0)),
            0
        );

        const total_mrp_amt = cart.reduce(
            (sum, item) => sum + (parseFloat(item.qty) * parseFloat(item.mrp || 0)),
            0
        );

        // ── Calculate Discount & Net Amount ──────────────────
        const discInput = document.getElementById('discount-percent');
        let discount_percent = parseFloat(discInput ? discInput.value : 0) || 0;
        if (discount_percent < 0) discount_percent = 0;
        if (discount_percent > 100) discount_percent = 100;

        const discount_amt = (total_mrp_amt * discount_percent) / 100;
        const net_amt = Math.max(0, total_mrp_amt - discount_amt);

        const btn = this;
        btn.disabled = true;
        btn.textContent = isEditMode ? 'Updating…' : 'Saving…';

        const targetUrl = isEditMode ? `${BASE_URL}SalesEntry/updateSale` : `${BASE_URL}SalesEntry/saveSale`;

        const payload = new URLSearchParams({
            customer_id: customer_id,
            stockist_id: stockist_id,
            sale_date: sale_date,
            type: sale_type,
            total_amt: total_amt.toFixed(2),
            mrp_total: total_mrp_amt.toFixed(2),
            discount_percent: discount_percent.toFixed(2),
            discount_amt: discount_amt.toFixed(2),
            net_amt: net_amt.toFixed(2),
            items: JSON.stringify(
                cart.map(i => ({
                    product_id: i.product_id,
                    batch_id: i.batch_id,
                    qty: i.qty,
                    pts: i.pts,
                    mrp: i.mrp
                }))
            )
        });

        if (isEditMode) {
            payload.append('s_id', sale_id);
        }

        fetch(targetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: payload
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
                showToast(data.msg || (isEditMode ? 'Sale updated successfully!' : 'Sale saved! Entry #' + data.entry_id), 'success');

                if (!isEditMode) {
                    cart = [];
                    
                    // Reset discount input on successful save
                    if (discInput) discInput.value = 0;

                    renderCart();

                    sessionStorage.removeItem('saved_sales_cart');
                    sessionStorage.removeItem('saved_sales_form');

                    const custSel = document.getElementById('customer-select');
                    const stockSel = document.getElementById('stockist-select');
                    const dateInput = document.getElementById('date-from');

                    if (custSel) custSel.value = '';
                    if (stockSel) stockSel.value = '';
                    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

                    if (stockSel) stockSel.dispatchEvent(new Event('change'));
                } else {
                    setTimeout(() => {
                        window.location.href = `${BASE_URL}SalesEntry`;
                    }, 1000);
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
            btn.textContent = isEditMode ? 'Update Sale' : 'Submit';
        });
    });
}

/* ════════════════════════════════════════════════════
   CUSTOMER LOADER & INIT
════════════════════════════════════════════════════ */
function loadCustomers(type = '', savedCustomerId = null) {
    $.ajax({
        url: `${BASE_URL}SalesEntry/get_customer`,
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

            $('#customer-select').html(html);

            if (savedCustomerId) {
                $('#customer-select').val(savedCustomerId);
            }

            $('#customer-select').trigger('change.select2');
            saveFormState();
        }
    });
}

$(document).ready(function () {$('#customer-select').select2({
        placeholder: 'Search Customer',
        width: '40%'
    });

    $('#customer-select, #stockist-select, #date-from').on('change', saveFormState);

    // Re-render cart when discount input changes
    const discInput = document.getElementById('discount-percent');
    if (discInput) {
        discInput.addEventListener('input', function () {
            renderCart();
            saveFormState();
        });
    }

    $('input[name="sale_type"]').on('change', function () {
        saveFormState();
        let type = $(this).val() === 'chemist' ? 'Chemist' : 'Doctor';
        loadCustomers(type);
    });

    // ── EDIT MODE INITIALIZATION ───────────────────────
    if (isEditMode) {
        activeStockistId = String(editSaleData.stockist_id);

        // 1. Populate Sale ID if element exists
        const saleIdInput = document.getElementById('sale_id');
        if (saleIdInput) {
            saleIdInput.value = editSaleData.sale_id;
        }

        // 2. Populate Stockist Dropdown
        const stockEl = document.getElementById('stockist-select');
        if (stockEl && editSaleData.stockist_id) {
            stockEl.value = editSaleData.stockist_id;
        }

        // 3. Populate Sale Date
        if (editSaleData.sale_date) {
            $('#date-from').val(editSaleData.sale_date);
        }

        // 4. Populate Sale Type Radio
        if (editSaleData.sale_type) {
            $(`input[name="sale_type"][value="${editSaleData.sale_type.toLowerCase()}"]`).prop('checked', true);
        }

        // 5. Populate Discount Percentage
        if (discInput && editSaleData.disc !== null && editSaleData.disc !== undefined) {
            discInput.value = parseFloat(editSaleData.disc);
        }

        // 6. Populate Cart Items
        if (editSaleData.items && editSaleData.items.length) {
            cart = editSaleData.items.map(it => {
                const itemQty = parseFloat(it.qty) || 0;
                const itemPts = parseFloat(it.pts ?? it.rate ?? 0);
                let itemMrp   = parseFloat(it.mrp ?? 0);

                if (itemMrp <= 0 && editSaleData.mrp_total > 0 && editSaleData.items.length === 1) {
                    itemMrp = editSaleData.mrp_total / itemQty;
                } else if (itemMrp <= 0) {
                    itemMrp = itemPts;
                }

                return {
                    product_id : parseInt(it.product_id ?? it.p_id, 10),
                    batch_id   : it.batch_id || null,
                    batch      : it.batch_no || it.batch || '',
                    name       : it.product_name || it.name || ('Item #' + (it.product_id ?? it.p_id)),
                    qty        : itemQty,
                    pts        : itemPts,
                    mrp        : itemMrp,
                    maxStock   : parseFloat(it.available_stock ?? itemQty)
                };
            });
        }

        // Render Cart with updated items and discount
        renderCart();

        if (searchInput) {
            searchInput.disabled = false;
            searchInput.placeholder = 'Search medicine…';
        }

        let initType = (editSaleData.sale_type || '').toLowerCase() === 'doctor' ? 'Doctor' : 'Chemist';
        loadCustomers(initType, editSaleData.customer_id);

    } 
    // ── INSERT MODE INITIALIZATION ─────────────────────
    else {
        let savedCustomerId = null;
        const storedForm = sessionStorage.getItem('saved_sales_form');

        if (storedForm) {
            const formState = JSON.parse(storedForm);

            if (formState.sale_date) {
                $('#date-from').val(formState.sale_date);
            }

            if (formState.sale_type) {
                $(`input[name="sale_type"][value="${formState.sale_type}"]`).prop('checked', true);
            }

            if (formState.stockist_id) {
                const stockistEl = document.getElementById('stockist-select');
                if (stockistEl) {
                    stockistEl.value = formState.stockist_id;
                    stockistEl.dispatchEvent(new Event('change'));
                }
            }

            if (formState.discount_percent && discInput) {
                discInput.value = formState.discount_percent;
            }

            savedCustomerId = formState.customer_id;
        }

        let initialType = $('input[name="sale_type"]:checked').val() === 'chemist' ? 'Chemist' : 'Doctor';
        loadCustomers(initialType, savedCustomerId);
        loadCartSession();
    }
});
/* ============================================================
   ORDER LIST PAGE  (order_list.php → #stockist, #orderTable)
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    const orderTableBody = document.getElementById('orderTable');
    if (orderTableBody) {
        const listStockistSel = document.getElementById('stockist');
        const fromDate        = document.getElementById('from_date');
        const toDate          = document.getElementById('to_date');
        const btnSearch       = document.getElementById('btnSearch');
        const btnReset        = document.getElementById('btnReset');
        const orderCount      = document.getElementById('orderCount');

        const statusClass = (s) => ({
            'Pending':  'badge-pending',
            'Approved': 'badge-approved',
            'Rejected': 'badge-rejected'
        }[s] || 'badge-pending');

        function actionIcons(o) {
            let icons = `<a href="${BASE_URL}Order/details/${o.order_id}" title="View">👁</a>`;
            if (o.status === 'Pending') {
                icons += ` <a href="${BASE_URL}Order/edit/${o.order_id}" title="Edit">✏</a>`;
                icons += ` <a href="#" class="del-order" data-id="${o.order_id}" title="Delete">🗑</a>`;
            }
            return icons;
        }

        function loadOrders() {
            const params = new URLSearchParams({
                stockist_id: listStockistSel ? listStockistSel.value : '',
                from_date: fromDate ? fromDate.value : '',
                to_date: toDate ? toDate.value : ''
            });

            orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Loading…</td></tr>`;

            fetch(`${BASE_URL}Order/list_orders?${params.toString()}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.data.length) {
                        orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No orders found</td></tr>`;
                        if (orderCount) orderCount.textContent = 0;
                        return;
                    }
                    orderTableBody.innerHTML = res.data.map(o => `
                        <tr>
                            <td>${o.order_no}</td>
                            <td>${o.order_date}</td>
                            <td>${o.stockist_name}</td>
                            <td>${o.total_qty}</td>
                            <td>₹${Number(o.total_amt).toFixed(2)}</td>
                            <td><span class="badge ${statusClass(o.status)}">${o.status}</span></td>
                            <td>${actionIcons(o)}</td>
                        </tr>
                    `).join('');
                    if (orderCount) orderCount.textContent = res.data.length;
                })
                .catch(() => {
                    orderTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Failed to load orders</td></tr>`;
                });
        }

        if (btnSearch) btnSearch.addEventListener('click', loadOrders);
        if (btnReset) {
            btnReset.addEventListener('click', () => {
                if (listStockistSel) listStockistSel.value = '';
                if (fromDate) fromDate.value = '';
                if (toDate) toDate.value = '';
                loadOrders();
            });
        }

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
});