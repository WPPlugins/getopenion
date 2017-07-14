var cached_surveys;

function loadSurveys() {
    if (typeof cached_surveys === 'undefined') {
        jQuery.post(localized.ajax_url, {
            action: "load_surveys"
        }, function (r) {
            cached_surveys = r;
            displaySurveys(r);
            console.log(r);
        }, 'json');
    } else {
        displaySurveys(cached_surveys);
    }
}

function displaySurveys(data) {
    jQuery('#getopenion_wrapper #loading').fadeOut();
    jQuery.each(data, function (i, val) {
        if (val.type == 's') {
            jQuery('#getopenion_wrapper form').append('<label><div class="survey"><input type="radio" name="survey_id" value="' + val.ID + '" /><h2>' + val.Name + '</h2></div></label>');
        } else {
            appendFolder(val, '');
        }
    });

    function appendFolder(v, par) {
        jQuery('#getopenion_wrapper form' + par).append('<div id="folder_' + v.ID + '" class="folder"><h2>' + v.Name + '</h2><div class="children" style="display:none;"></div></div>');
        if (Object.keys(v.children).length > 0) {
            jQuery.each(v.children, function (j, me) {
                if (me.type == 's') {
                    jQuery('#getopenion_wrapper form #folder_' + v.ID + ' > .children').append('<label><div class="survey"><input type="radio" name="survey_id" value="' + me.ID + '" /><h2>' + me.Name + '</h2></div></label>');
                } else {
                    appendFolder(me, ' #folder_' + v.ID + ' > .children');
                }
            });
        }
    }

    jQuery('#getopenion_wrapper .folder > h2').click(function () {
        jQuery(this).parent().toggleClass('open').find(' > .children').slideToggle();
    });
}