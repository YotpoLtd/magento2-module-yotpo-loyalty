define(
    [
        'uiComponent'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Yotpo_Loyalty/summary/custom'
            },
            loadJsCustomAfterKoRender: function() {
                var adminSettings = window.valuesConfig;
                console.log(adminSettings);
                var script = document.createElement('script');
                script.src = 'https://cdn-widgetsrepository.yotpo.com/v1/loader/X-GUID-X'
                script.setAttribute('src_type', 'url')
                document.head.appendChild(script)
            }
        });
    }
);