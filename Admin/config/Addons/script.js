const toggler = document.querySelector(".btn");

if (toggler) {
    toggler.addEventListener("click", function () {
        document.querySelector("#sidebar").classList.toggle("collapsed");

        const reportMenu = document.getElementById("reportMenu");

        if (
            reportMenu &&
            document.querySelector("#sidebar").classList.contains("collapsed")
        ) {
            bootstrap.Collapse.getOrCreateInstance(reportMenu).hide();
        }
    });
}

function openSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}