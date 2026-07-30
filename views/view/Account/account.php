<?php 
$pageTitle = "Customer";
include 'view/layout/header.php'; 
?>

<style>
/* ── toast ── */
#toast{
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(30px);
  padding:10px 20px;border-radius:8px;font-size:14px;font-weight:500;
  color:#fff;opacity:0;pointer-events:none;transition:all .3s ease;z-index:9999;
  white-space:nowrap;
}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
#toast.success{background:#22c55e;}
#toast.error{background:#ef4444;}

/* ── section header ── */
.settings-section h3{
  margin:0 0 16px;font-size:15px;font-weight:600;color:var(--text,#1e293b);
  padding-bottom:10px;border-bottom:1px solid #e2e8f0;
}

/* ── loading state on buttons ── */
.btn-save.loading,.btn-danger.loading{opacity:.7;pointer-events:none;}

/* ── password visibility toggle ── */
.pwd-wrap{position:relative;}
.pwd-wrap input{padding-right:38px;}
.pwd-eye{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;padding:0;color:#94a3b8;
  display:flex;align-items:center;
}
.pwd-eye:hover{color:#475569;}

/* Address Field */
.address-field{
    flex: 1 1 100%;
    width: 100%;
}

.address-field textarea{
    width: 100%;
    min-height: 120px;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
    color: var(--txt);
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all .3s ease;
    box-sizing: border-box;
}

.address-field textarea:focus{
    outline: none;
    border-color: var(--violet);
    box-shadow: 0 0 0 3px rgba(109,86,252,.15);
}

/* Mobile Responsive */
@media (max-width: 480px) {
  .topbar {
    padding: 12px 14px;
    gap: 28px;
  }
}
@media (max-width: 768px){
    .address-field textarea{
        min-height: 100px;
        font-size: 13px;
        padding: 10px 12px;
    }
}

@media (max-width: 480px){
    .address-field{
        flex: 1 1 100%;
    }

    .address-field textarea{
        min-height: 90px;
        font-size: 13px;
        border-radius: 10px;
    }
}
</style>

      <div class="page-content">
        <div class="account-grid">

          <!-- ── Profile Card ── -->
          <div>
            <div class="profile-card">
              <div class="avatar-lg"><?php echo $initials; ?></div>
              <div>
                <div class="profile-name"><?= htmlspecialchars($mr['mr_name']) ?></div>
                <div class="profile-role">MR · <?= htmlspecialchars($mr['hq_name']) ?></div>
              </div>
              <div class="profile-stat">
                <div class="pstat">

                    <div class="pstat-val"><?php echo $totalCustomer['total_customers']; ?></div>
                    <div class="pstat-label">Customers</div>
                </div>

                <div class="pstat">
                    <div class="pstat-val"><?php echo $totalStockist['total_stockists']; ?></div>
                    <div class="pstat-label">Stockists</div>
                </div>
            </div>
              <button class="btn-danger" style="width:100%;margin-top:8px"
                onclick="if(confirm('Are you sure you want to log out?')) window.location.href='login/logout'">
                Log Out
              </button>
            </div>
          </div>

          <!-- ── Settings Card ── -->
          <div class="settings-card">

            <!-- Personal Information -->
            <div class="settings-section">
              <h3>Personal Information</h3>
              <div class="form-row">
                <div class="form-field">
                  <label>Full Name</label>
                  <input type="text" id="mr_name" value="<?php echo htmlspecialchars($mr['mr_name']); ?>" placeholder="Full Name"/>
                </div>
              </div>
              <div class="form-row">
                <div class="form-field">
                  <label>Email</label>
                  <input type="email" id="email" value="<?php echo htmlspecialchars($mr['email']); ?>" placeholder="email@example.com"/>
                </div>
                <div class="form-field">
                  <label>Mobile</label>
                  <input type="tel"
                    id="mobile"
                    maxlength="10"
                    pattern="[6-9]{1}[0-9]{9}"
                    value="<?php echo htmlspecialchars($mr['mobile']); ?>"
                    placeholder="9876543210">
                </div>
              </div>
              <div class="form-row">
              <div class="form-field">
                  <label>District</label>

                  <select name="district" id="district" class="form-control">
                        <option value="">Select District</option>

                      <?php 
                      while($distri = mysqli_fetch_assoc($districts)): ?>
                          <option value="<?= htmlspecialchars($distri['district_name']); ?>"
                              <?= ($mr['district'] == $distri['district_name']) ? 'selected' : ''; ?>>
                              <?= htmlspecialchars($distri['district_name']); ?>
                          </option>
                      <?php endwhile; ?>
                  </select>
              </div>
                <div class="form-field">
                  <label>Pincode</label>
                  <input type="text" id="pincode" value="<?php echo htmlspecialchars($mr['pincode']); ?>" placeholder="Pincode" maxlength="6" oninput="this.value=this.value.replace(/[^0-9]/g,'')"/>
                </div>
              </div>
              <div class="form-row">
               <div class="form-field address-field">
                  <label for="address">Address</label>
                  <textarea name="address" id="address" placeholder="Enter Address"><?php echo htmlspecialchars($mr['address']); ?></textarea>
              </div>
              </div>
              <button class="btn-save" id="btnSaveProfile" onclick="saveProfile()">Save Changes</button>
            </div>


            <!-- Security / Password -->
            <div class="settings-section">
              <h3>Security</h3>
              <div class="form-row">
                <div class="form-field">
                  <label>Current Password</label>
                  <div class="pwd-wrap">
                    <input type="password" id="current_password" placeholder="••••••••"/>
                    <button type="button" class="pwd-eye" onclick="togglePwd('current_password',this)" tabindex="-1">
                      <?php echo eyeIcon(); ?>
                    </button>
                  </div>
                </div>
                <div class="form-field">
                  <label>New Password</label>
                  <div class="pwd-wrap">
                    <input type="password" id="new_password" placeholder="••••••••"/>
                    <button type="button" class="pwd-eye" onclick="togglePwd('new_password',this)" tabindex="-1">
                      <?php echo eyeIcon(); ?>
                    </button>
                  </div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-field">
                  <label>Confirm New Password</label>
                  <div class="pwd-wrap">
                    <input type="password" id="confirm_password" placeholder="••••••••"/>
                    <button type="button" class="pwd-eye" onclick="togglePwd('confirm_password',this)" tabindex="-1">
                      <?php echo eyeIcon(); ?>
                    </button>
                  </div>
                </div>
              </div>
              <button class="btn-save" id="btnChangePwd" onclick="changePassword()">Update Password</button>
            </div>

          </div><!-- /settings-card -->
        </div><!-- /account-grid -->
      </div><!-- /page-content -->

      <div id="toast" class="toast"></div>
<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>

// ── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show ' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = ''; }, 3200);
}

// ── Password eye toggle ───────────────────────────────────────────────────────
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.innerHTML = show ? `<?php echo eyeOffIcon(); ?>` : `<?php echo eyeIcon(); ?>`;
}

// ── Save Profile ──────────────────────────────────────────────────────────────
function saveProfile() {

const pincode = document.getElementById('pincode').value.trim();

if (!/^[0-9]{6}$/.test(pincode)) {
    showToast('Please enter a valid 6-digit pincode', 'error');
    return;
}

  const email  = document.getElementById('email').value.trim();
const mobile = document.getElementById('mobile').value.trim();

// Email validation
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

if (!emailPattern.test(email)) {
    alert('Please enter a valid email address');
    return;
}

// Mobile validation (10 digits only)
const mobilePattern = /^[6-9]\d{9}$/;

if (!mobilePattern.test(mobile)) {
    alert('Please enter a valid 10 digit mobile number');
    return;
}
  const btn = document.getElementById('btnSaveProfile');
  const data = new FormData();
  data.append('action',        'update_profile');
  data.append('mr_name',       document.getElementById('mr_name').value.trim());
  data.append('mobile',        document.getElementById('mobile').value.trim());
  data.append('email',         document.getElementById('email').value.trim());
  data.append('district',      document.getElementById('district').value.trim());
  data.append('pincode',       document.getElementById('pincode').value.trim());
  data.append('address',       document.getElementById('address').value.trim());

  if (!data.get('mr_name')) { showToast('Name cannot be empty.', 'error'); return; }

  btn.textContent = 'Saving…'; btn.classList.add('loading');
  fetch(window.location.href, { method:'POST', body: data })
    .then(r => r.json())
    .then(res => {
      showToast(res.message, res.success ? 'success' : 'error');
      if (res.success) {
        // Refresh displayed name/initials live
        const n = document.getElementById('mr_name').value.trim();
        const parts = n.split(' ');
        const ini = (parts[0][0]||'').toUpperCase() + (parts[1] ? parts[1][0].toUpperCase() : '');
        document.querySelectorAll('.avatar, .avatar-lg').forEach(el => el.textContent = ini);
        document.querySelector('.profile-name').textContent = n;
      }
    })
    .catch(() => showToast('Network error. Try again.', 'error'))
    .finally(() => { btn.textContent = 'Save Changes'; btn.classList.remove('loading'); });
}

// ── Change Password ───────────────────────────────────────────────────────────
function changePassword() {
  const btn = document.getElementById('btnChangePwd');
  const cur = document.getElementById('current_password').value;
  const np  = document.getElementById('new_password').value;
  const cp  = document.getElementById('confirm_password').value;

  if (!cur || !np || !cp) { showToast('Please fill all password fields.', 'error'); return; }
  if (np !== cp)          { showToast('New passwords do not match.', 'error'); return; }
  if (np.length < 6)      { showToast('New password must be at least 6 characters.', 'error'); return; }

  const data = new FormData();
  data.append('action',           'change_password');
  data.append('current_password', cur);
  data.append('new_password',     np);
  data.append('confirm_password', cp);

  btn.textContent = 'Updating…'; btn.classList.add('loading');
  fetch(window.location.href, { method:'POST', body: data })
    .then(r => r.json())
    .then(res => {
      showToast(res.message, res.success ? 'success' : 'error');
      if (res.success) {
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value     = '';
        document.getElementById('confirm_password').value = '';
      }
    })
    .catch(() => showToast('Network error. Try again.', 'error'))
    .finally(() => { btn.textContent = 'Update Password'; btn.classList.remove('loading'); });
}
</script>
<?php
// ── SVG helpers (PHP, called before HTML output) ───────────────────────────────
function eyeIcon(){
  return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
}
function eyeOffIcon(){
  return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}
?>  