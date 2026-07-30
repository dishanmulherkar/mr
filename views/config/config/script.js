/* ── Router ── */
const pages = ['login','dashboard','pharmacies','inventory','account'];
const navMap = {dashboard:'nav-dashboard',pharmacies:'nav-pharmacies',inventory:'nav-inventory',account:'nav-account'};

function goTo(id){
  pages.forEach(p=>{
    const el = document.getElementById('page-'+p);
    if(el) el.classList.toggle('active', p===id);
  });
  // Move sidebar into the active app page
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if(id!=='login'){
    const shell = document.querySelector('#page-'+id+' .app-shell');
    if(shell && !shell.contains(sidebar)){
      shell.prepend(sidebar);
    }
  }
  // Update nav active states
  Object.values(navMap).forEach(n=>{
    const el = document.getElementById(n);
    if(el) el.classList.remove('active');
  });
  if(navMap[id]){
    const active = document.getElementById(navMap[id]);
    if(active) active.classList.add('active');
  }
  closeSidebar();
  window.scrollTo(0,0);
}

function openSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}

/* ── Password toggle ── */
let passVis=false;
function togglePass(){
  passVis=!passVis;
  document.getElementById('lpass').type=passVis?'text':'password';
  document.getElementById('eyeico').innerHTML=passVis
    ?`<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
      <line x1="1" y1="1" x2="23" y2="23"/>`
    :`<path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>`;
}

/* ── Sales Chart ── */
function buildChart(){
  const data=[
    {d:'Customers', v:130},
    {d:'Sales', v:12214}
  ];
  const max=Math.max(...data.map(d=>d.v));
  const wrap=document.getElementById('salesChart');
  if(!wrap)return;
  wrap.innerHTML='';
  data.forEach(item=>{
    const pct=Math.round((item.v/max)*100);
    const col=document.createElement('div');
    col.className='bar-col';
    col.innerHTML=`<div class="bar${item.d==='Sales'?' active':''}" style="height:${pct}%"></div><div class="bar-label">${item.d}</div><div class="bar-label">${item.v.toLocaleString()}</div>`;
    wrap.appendChild(col);
  });
}

function bindStatCardRedirects(){
  document.querySelectorAll('.stat-card').forEach(card=>{
    card.addEventListener('click', function(event){
      if (event.target.closest('a')) return;
      const link = card.querySelector('.stat-link a');
      if (link) window.location.href = link.href;
    });
  });
}

/* Init */
buildChart();
bindStatCardRedirects();
// Ensure sidebar is in the first app page shell initially
const dashShell = document.querySelector('#page-dashboard .app-shell');
if(dashShell) dashShell.prepend(document.getElementById('sidebar'));