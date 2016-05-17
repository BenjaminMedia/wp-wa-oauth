function bp_wa_oauth_trigger_login(authDestination) {

    var loginUri = '/wp-json/bp-wa-oauth/v1/oauth/login';

    if (typeof authDestination === 'undefined') {
        authDestination = document.location.href;
    }

    window.location = loginUri + '?redirectUri=' + encodeURIComponent(authDestination);
}

window.addEventListener('click', function (event) {

    var triggerClass = 'bp-wa-oauth-login';

    if (event.target.className.indexOf(triggerClass) > -1) {
        if (typeof event.target.dataset.bpWaOauthRedirect !== 'undefined') {
            bp_wa_oauth_trigger_login(event.target.dataset.bpWaOauthRedirect);
        } else {
            bp_wa_oauth_trigger_login();
        }
    }
});