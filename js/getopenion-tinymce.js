(function () {
    tinymce.create('tinymce.plugins.getopenion', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {
            ed.addButton('getopenion_ins', {
                title: 'Insert a survey',
                cmd: 'getopenion_ins',
                image: url + '/insert_icon.png'
            });

            ed.addCommand('getopenion_ins', function () {
                var height = jQuery(window).height();
                var width = jQuery(window).width();
                var win = ed.windowManager.open({
                    title: 'Insert a survey',
                    html: '<div id="getopenion_wrapper"><form><img id="loading" width="20" height="20" src="images/spinner-2x.gif" /></form></div>',
                    height: height * 0.8,
                    width: width * 0.6,
                    buttons: [{
                        text: 'Insert',
                        onclick: function (e) {
                            shortcode = '[getopenion id="' + jQuery('#getopenion_wrapper input:checked').val() + '" /]';
                            win.close();
                            ed.focus();
                            ed.execCommand('mceInsertContent', 0, shortcode);
                        }
      }, {
                        text: 'Cancel',
                        onclick: 'close'
      }]
                });
                loadSurveys();
            });
        },

        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl: function (n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'getOpenion Survey Manager',
                author: 'Pius Ladenburger',
                authorurl: 'https://pius-ladenburger.de',
                version: "0.9"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('getopenion', tinymce.plugins.getopenion);
})();