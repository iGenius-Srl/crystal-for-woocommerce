jQuery(document).ready(function($) {
    var isFromSignup = getParameterByName('from_signup');
    var isSavedOptions = getParameterByName('saved_options');
    if(isFromSignup || isFromSignup !== null) {
        $('#cfw-login')[0].click();
    }
    if(isSavedOptions || isSavedOptions !== null) {
        var closePopupTimeout = setTimeout(function() {
            clearTimeout(closePopupTimeout);
            if (window.opener) {
                window.close()
            }
        }, 3000);
    }
})

/**
* Returns the query parameter filtereb by name
**/
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
