jQuery(document).ready(function () {
    jQuery.post(localized.ajax_url, {
        token: localized.token,
        action: "connect_getopenion"
    }, function (r) {
        if (r == "success") {
            window.location.href = localized.page;
        } else {
            jQuery(".loader.center").text(localized.error);
        }
    });
});