<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);
// 1. Set the dynamic title for the header
$pageTitle = "Dashboard"; 

// 2. Include the top layout
include 'view/layout/header.php'; 
?>
<style>
    .btn-cancel{
        flex: 0 0 auto;
        background: var(--violet);
        color: #fff;
        border: none;
        padding: 10px 22px;
        padding-left: 22px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: opacity .2s;
        white-space: nowrap;
    }
</style>
<div class="page-content">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="12" r="6"/>
                    <circle cx="12" cy="12" r="2"/>
                </svg>
            </div>
            <div class="stat-label">Target </div>
            <div class="stat-val"><?php 
              echo number_format($target_amount, 2); 
             ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="stat-label">Total Primary Sales</div>
            <div class="stat-val"><?php  
             echo number_format($primary_sale, 2);  
             ?></div>
          </div>
          <div class="stat-card">
           <div class="stat-icon orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-label">Total Customers</div>
            <div class="stat-val"><?php  
             echo $total_customers; 
              ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg></div>
            <div class="stat-label">Total  Secondary Sales</div>
            <div class="stat-val">  <?php
             echo number_format($total_sales_amount, 2);
              ?></div>
          </div>
        </div> 
        
      </div>


<!-- Financial Year Expired  -->

<?php
$today = date('Y-m-d');
if(!empty($end_date) && $today > $end_date)
{
?>
<div id="fyPopup">
    <div class="popup-header">
        Financial Year Expired
        <span class="close-btn" onclick="document.getElementById('fyPopup').style.display='none'">&times;</span>
    </div>

    <div class="popup-body">
        <p>
            The financial year ended on
            <strong><?= date('d-m-Y', strtotime($end_date)); ?></strong>.
        </p>

        <p> activate a new financial year.</p>
    </div>
</div>
<?php
}
?>

<!-- Notification POPUp -->

<?php

if(mysqli_num_rows($notification_query) > 0){ 
    
    $seen_notification_ids = []; // Array to hold the IDs we are showing
?>

<div id="notificationModal" class="notification-modal">
    <div class="notification-box">
        
        <div class="notification-header">
            🔔 Notifications
        </div>

        <div class="notification-body">
            <?php 
            while($n = mysqli_fetch_assoc($notification_query)){ 
                // Add this notification's ID to our array
                $seen_notification_ids[] = $n['notification_id']; 
            ?>
                <div class="notification-item">
                    <h5><?= htmlspecialchars($n['title']); ?></h5>
                    <p><?= nl2br(htmlspecialchars($n['message'])); ?></p>
                    <small><?= date('d M Y ', strtotime($n['created_at'])); ?></small>
                </div>
            <?php } ?>
        </div>

        <div class="notification-footer">
            
            
            <input type="hidden" id="shownNotificationIds" value="<?= implode(',', $seen_notification_ids); ?>">
            
            <button id="closeNotification" class="btn-cancel">
                Close
            </button>
        </div>

    </div>
</div>

<?php 
} 
?>

<?php 
// 3. Include the bottom layout and scripts
include 'view/layout/footer.php'; 
?>


<script>
document.getElementById("closeNotification")?.addEventListener("click",function(){

    document.getElementById("notificationModal").style.display="none";

});

document.addEventListener("DOMContentLoaded", function () {

    const btn = document.getElementById("closeNotification");

    if(btn){

        btn.addEventListener("click", function(){

            let ids = document.getElementById("shownNotificationIds").value;

            console.log(ids); // Should print something like: 5,6,7

            fetch("<?= BASE_URL ?>dashboard/markNotificationsSeen", {
                method: "POST",
                headers:{
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body:"ids="+encodeURIComponent(ids)
            })
            .then(res => res.text())
            .then(data=>{
                console.log(data);
                document.getElementById("notificationModal").style.display="none";
            })
            .catch(err=>{
                console.log(err);
            });

        });

    }

});
</script>