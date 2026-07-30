<?php 
$pageTitle = "Customer";
include 'view/layout/header.php'; 
?>

<style>
  .detail-modal-overlay {
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.45);
    z-index:120;
    align-items:center;
    justify-content:center;
    padding:20px;
  }
  .detail-modal-overlay.open { display:flex; }
  .detail-modal {
    width:100%;
    max-width:420px;
    background:var(--surface);
    border-radius:18px;
    box-shadow:var(--shadow);
    overflow:hidden;
  }
  .detail-modal-header {
    padding:18px 20px;
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:var(--surface);
  }
  .detail-modal-title { font-size:16px; font-weight:700; color:var(--txt); }
  .detail-modal-close {
    width:34px; height:34px; border:none; background:var(--surface2); cursor:pointer;
    color:var(--txt-mid); font-size:20px; line-height:1; border-radius:10px;
  }
  .detail-modal-body { padding:20px; background:var(--surface); }
  .detail-modal-body p { margin:0 0 14px; font-size:14px; color:var(--txt-mid); line-height:1.6; display:flex; flex-direction:column; gap:6px; }
  .detail-modal-body p span { font-weight:700; color:var(--txt); font-size:13px; }
  .detail-input {
    width:100%;
    padding:12px 14px;
    border:1px solid var(--border);
    border-radius:12px;
    background:var(--surface2);
    color:var(--txt);
    font-size:14px;
    outline:none;
  }
  .detail-input:disabled { background:transparent; border-color:#0000001c; color:var(--txt);
    box-shadow:none;
  }
  .detail-modal-actions { display:block; justify-content:center; padding:16px 20px 20px; gap:10px; background:var(--surface); }
  .detail-modal-actions .btn-outline {
    padding:10px 16px;
    border:1px solid var(--border);
    background:var(--surface2);
    color:var(--txt);
  }
  .detail-modal-actions .btn-filled {
    padding:10px 16px;
    border-radius:var(--radius-sm);
    border:none;
    background:var(--violet);
    color:#fff;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    transition:opacity .15s;
  }
  .detail-modal-actions .btn-filled:hover { opacity:.88; }
  .detail-modal-actions .btn-outline:hover {
    background:var(--violet-lt);
    color:var(--violet);
    border-color:var(--violet);
  }

  .alert{
    padding:12px 18px;
    margin-bottom:15px;
    border-radius:8px;
    font-size:15px;
    font-weight:500;
    animation:fadeIn .3s;
}

.alert-success{
    background:#d4edda;
    color:#155724;
    border:1px solid #c3e6cb;
}

.alert-danger{
    background:#f8d7da;
    color:#721c24;
    border:1px solid #f5c6cb;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(-10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.detail-address{
    padding:10px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#f8f9fa;
    white-space:pre-line;
    min-height:70px;
}
.view-details-btn{
    padding:8px 12px;
    border:none;
    background:var(--violet);
    color:#fff;
    font-size:13px;
    font-weight:600;
    border-radius:8px;
    cursor:pointer;
    transition:opacity .15s;
}

.address-meta{
    display:flex;
    align-items:flex-start;
    gap:8px;
}

.address-text{
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    line-height:1.4;
    word-break:break-word;
}

.address-text strong{
    font-weight:600;
}
</style>

<div class="page-content">
        <div class="filter-bar" style="justify-content: space-between;">
          <div class="search-big"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input
            type="text"
            id="customerSearch"
            placeholder="Search by name mobile..."
            onkeyup="searchCustomers()">
        </div>
          <a href="./AddCustomer"><button class="btn-add"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add Customer</button></a> 
        </div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success" id="alertMessage">
        <?= $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger" id="alertMessage">
        <?= $_SESSION['error']; ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>      

        <div class="pharmacy-grid">
          <?php
          if (mysqli_num_rows($customers) > 0) {
              while($row = mysqli_fetch_assoc($customers)) {
          ?>
              <div class="pharm-card">
                <div class="pharm-body">
                  <div class="pharm-name">
                      <?php
                      echo htmlspecialchars($row['customer_name']);

                      $type = strtolower(trim($row['customer_type']));

                      echo ($type === "doctor") ? " (Doc)" : " (Chem)";
                      ?>
                  </div>
                 <div class="pharm-meta address-meta">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                          <circle cx="12" cy="10" r="3"/>
                      </svg>

                      <span class="address-text">
                          <strong>
                              <?= htmlspecialchars($row['district']); ?>,
                              <?= htmlspecialchars($row['state_name']); ?>
                          </strong><br>
                          <?= htmlspecialchars($row['address']); ?>
                      </span>
                  </div>
                  
                

                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <div class="pharm-meta" style="margin: 0; flex: 1;">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                      <?php echo htmlspecialchars($row['mobile']); ?>
                    </div>
                    <button
                        class="view-details-btn"
                        data-id="<?= $row['c_id']; ?>">
                        View Details
                    </button>
                  </div>
                </div>
              </div>
          <?php 
              }
          } else {
              echo "<p>No customers found.</p>";
          }
          ?>
        </div>
      </div>


      <div class="detail-modal-overlay" id="detailModal">
          <div class="detail-modal">
            <div class="detail-modal-header">
              <div class="detail-modal-title">Customer Details</div>
              <button class="detail-modal-close" id="closeDetailModal" aria-label="Close">×</button>
            </div>
            <div class="detail-modal-body" id="detailModalBody">
              <p><span>Name</span><input type="text" id="detailName" class="detail-input" disabled></p>
              <p><span>Qualification</span> <input type="text" id="detailQualification" class="detail-input" disabled></p>
              <p><span>Mobile</span><input type="tel" id="detailMobile" class="detail-input" disabled></p>
               <p><span>Address</span>
                   <div id="detailAddress" class="detail-address"></div>
                </p>
            </div>
            <div class="detail-modal-actions">
              <button class="btn-filled" id="editCustomerBtn" style="max-width:60px !important;">Edit</button>
              <button class="btn-filled" id="saveCustomerBtn" style="display:none;max-width:130px;">Save</button>
              <button class="btn-outline" id="cancelEditBtn" style="display:none;max-width:130px;">Cancel</button>
            </div>
          </div>
        </div>

                            <?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    const alert = document.getElementById("alertMessage");

    if(alert){

        setTimeout(function(){

            alert.style.transition = "0.5s";
            alert.style.opacity = "0";

            setTimeout(function(){
                alert.remove();
            },500);

        },3000);

    }

});

  function searchCustomers(){

    let input = document.getElementById("customerSearch").value.toLowerCase();

    document.querySelectorAll(".pharm-card").forEach(function(card){

        let name = card.querySelector(".pharm-name").innerText.toLowerCase();

        card.style.display =
            name.includes(input) ? "" : "none";

    });

}

  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('detailModal');
    const closeBtn = document.getElementById('closeDetailModal');
    const editBtn = document.getElementById('editCustomerBtn');
    const detailName = document.getElementById('detailName');
    const detailQualification = document.getElementById('detailQualification');
    const detailAddress = document.getElementById('detailAddress');
    const detailEmail = document.getElementById('detailEmail');
    const detailMobile = document.getElementById('detailMobile');
    const saveBtn = document.getElementById('saveCustomerBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    let currentCard = null;
    let originalData = {};
    let customerId = 0;

    function setEditMode(enabled) {
     [
          detailName,
          detailQualification,
          detailAddress,
          detailEmail,
          detailMobile
      ].forEach(input => {
          input.disabled = !enabled;
          input.style.background = enabled ? 'var(--surface2)' : 'transparent';
      });
      editBtn.style.display = enabled ? 'none' : 'inline-flex';
      saveBtn.style.display = enabled ? 'inline-flex' : 'none';
      cancelBtn.style.display = enabled ? 'inline-flex' : 'none';
    }

  $(document).on("click", ".view-details-btn", function () {
    customerId = $(this).data("id");
    $.ajax({
        type: "GET",
        url: "<?= BASE_URL ?>customer/popup/" + customerId,
        dataType: "json",
        beforeSend: function () {

            $("#detailName").val("");
            $("#detailAddress").html("");
            $("#detailEmail").val("");
            $("#detailMobile").val("");
            $("#detailQualification").val("");

        },
        success: function (response) {

    if(response.status){

        $("#detailName").val(response.data.customer_name);
        $("#detailQualification").val(response.data.qualification);
        $("#detailMobile").val(response.data.mobile);
        $("#detailEmail").val(response.data.email);

       $("#detailAddress").html(
                    "<strong>" +
                    (response.data.district || "") +
                    ", " +
                    (response.data.state_name || "") +
                    "</strong><br>" +
                    (response.data.address || "")
                );

        originalData = {
            name: response.data.customer_name,
                qualification: response.data.qualification,
                address:
                    "<strong>" +
                    (response.data.district || "") +
                    ", " +
                    (response.data.state_name || "") +
                    "</strong><br>" +
                    (response.data.address || ""),
                email: response.data.email,
                mobile: response.data.mobile
            };

        $("#detailModal").addClass("open");
        setEditMode(false);

    } else {
        alert(response.message);
    }
},
          error: function(xhr, status, error) {

          console.log("Status:", status);
          console.log("Error:", error);
          console.log("Response:", xhr.responseText);

          alert(xhr.responseText);

      }
    });

});
    closeBtn.addEventListener('click', function () {
      modal.classList.remove('open');
    });

    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        modal.classList.remove('open');
      }
    });

    // editBtn.addEventListener('click', function () {
    //   setEditMode(true);
    // });
  editBtn.addEventListener('click', function () {

  window.location.href =
    "<?= BASE_URL ?>AddCustomer/edit/" + customerId;

});
    cancelBtn.addEventListener('click', function () {
      detailName.value = originalData.name;
      $("#detailAddress").html(originalData.address);
      detailEmail.value = originalData.email;
      detailMobile.value = originalData.mobile;
      setEditMode(false);
    });

    saveBtn.addEventListener('click', function () {
      const name = detailName.value.trim();
      const address = detailAddress.value.trim();
      const email = detailEmail.value.trim();
      const mobile = detailMobile.value.trim();

      if (!name || !address || !email || !mobile) {
        alert('Please fill in all fields before saving.');
        return;
      }

      if (currentCard) {
        currentCard.querySelector('.pharm-name').textContent = name;
        const metaTexts = currentCard.querySelectorAll('.meta-text');
        if (metaTexts.length >= 3) {
          metaTexts[0].textContent = address;
          metaTexts[1].textContent = email;
          metaTexts[2].textContent = mobile;
        }
        currentCard.querySelectorAll('.view-details-btn').forEach(btn => {
          btn.dataset.name = name;
          btn.dataset.address = address;
          btn.dataset.email = email;
          btn.dataset.mobile = mobile;
        });
      }

      originalData = { name, address, email, mobile };
      setEditMode(false);
      modal.classList.remove('open');
    });
  });
  $(document).ready(function () {
 
  });
</script>