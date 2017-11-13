function bp_wa_oauth_trigger_login(redirectOnComplete) {

    const loginUri = '/wp-json/bp-wa-oauth/v1/oauth/login';

    if (typeof redirectOnComplete === 'undefined') {
        redirectOnComplete = document.location.href;
    }

    window.location = loginUri + '?redirectUri=' + encodeURIComponent(redirectOnComplete);
}

function bp_wa_oauth_trigger_logout(redirectOnComplete) {

    const loginUri = '/wp-json/bp-wa-oauth/v1/oauth/logout';

    if (typeof redirectOnComplete === 'undefined') {
        redirectOnComplete = document.location.href;
    }

    window.location = loginUri + '?redirectUri=' + encodeURIComponent(redirectOnComplete);
}

window.addEventListener('click', function (event) {

    var loginTriggerClass = 'bp-wa-oauth-login';
    var logoutTriggerClass = 'bp-wa-oauth-logout';

    if (event.target.className.indexOf(loginTriggerClass) > -1 || event.target.parentElement.className.indexOf(loginTriggerClass) > -1) {
        if (typeof event.target.dataset.bpWaOauthRedirect !== 'undefined') {
            bp_wa_oauth_trigger_login(event.target.dataset.bpWaOauthRedirect);
        } else {
            bp_wa_oauth_trigger_login();
        }
    }

    if (event.target.className.indexOf(logoutTriggerClass) > -1 || event.target.parentElement.className.indexOf(logoutTriggerClass) > -1) {
        if (typeof event.target.dataset.bpWaOauthRedirect !== 'undefined') {
            bp_wa_oauth_trigger_logout(event.target.dataset.bpWaOauthRedirect);
        } else {
            bp_wa_oauth_trigger_logout();
        }
    }
});

var request = new XMLHttpRequest();
request.open('GET', settings.api_endpoint + '/oauth/by_session', true);
request.withCredentials = true;

request.onload = function () {
    if (request.status >= 200 && request.status < 400) {
        // Success!
        var data = JSON.parse(request.responseText);
        if(data && !settings.loggedIn) {
            bp_wa_oauth_trigger_login();
        }
    } else if(settings.loggedIn) {
        var logoutRequest = new XMLHttpRequest();
        logoutRequest.open('POST', settings.ajaxurl, true);
        logoutRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        logoutRequest.onload = function () {
            if(logoutRequest.status >= 200 && logoutRequest.status < 400) {
                var response = JSON.parse(logoutRequest.responseText);
                if(response.hasOwnProperty('refresh') && response.refresh) {
                    window.location.reload(true);
                }
            }
        };
        logoutRequest.send('action=wp_wa_oauth_logout');
    }
};
request.send();
