(function() {

    function fix_frame_height() {
        var height = document.getElementById("wpwrap").offsetHeight;
        var iframe = document.querySelector("#email-king-app-frame");
    
        iframe.style.height = height + "px";
    }
    
    ["resize", "load"].forEach(function(event){
        window.addEventListener( event, fix_frame_height, { passive: true } );
    });

})();