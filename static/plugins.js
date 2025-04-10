(function () {
    function maybeAddUpdateClass() {
        var update = document.querySelector(".wpemailking_proavl");

        if (update) {
            var tbody = update.closest("#the-list");
            var row = tbody.querySelector("[data-slug=\"email-king\"]");

            if (!row.classList.contains("update")) {
                row.classList.add("update");
            }
        }
    }

    maybeAddUpdateClass();

    setTimeout(maybeAddUpdateClass, 100);
    setTimeout(maybeAddUpdateClass, 750);
})();