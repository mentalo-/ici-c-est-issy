    
    // Affiche le loading
    function showLoading(msgText) {
        // console.log('showLoading');
        if( typeof msgText == 'undefined' ) msgText = 'Chargement';
        $.mobile.loading( "show", {
            text: msgText,
            textVisible: true, //textVisible, // If true, the text value will be used under the spinner
            theme: "a", //theme,
            textonly: false, //textonly, // If true, the "spinner" image will be hidden when the message is shown
            html: "", //html // replace the entirety of the loader's inner html
        });
    } // end showLoading()
    
    // Cache le loading
    function hideLoading() {
        // console.log('hideLoading');
        $.mobile.loading( "hide" );
    } // end hideLoading()
    
    /*
    // $( document ).on( "click", ".show-page-loading-msg", function() {
    $(document).on("pagebeforechange", function() {
        // console.log("pagebeforechange");
        // showLoading();
    })
    .on( "pagebeforehide", function() {
        // console.log("pagebeforehide");
        hideLoading();
    })
    ;
    */