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