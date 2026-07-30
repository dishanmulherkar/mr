        <div class="topbar">
            <button class="hamburger" onclick="openSidebar()" aria-label="Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
            </button>
            <div><div class="topbar-title"><?php echo $pageTitle;   ?></div></div>
            <div class="topbar-spacer"></div>
            
            
            <!-- <div class="avatar avatar_name" style="cursor:pointer"></div> -->

       <div href="account.php" class="user-pill">
         
          <div class="user-info">
            <div class="uname"><?php echo $_SESSION['mr_name']; ?></div>
            <div class="urole">Rudradeo</div>
          </div>
    </div>


        </div>