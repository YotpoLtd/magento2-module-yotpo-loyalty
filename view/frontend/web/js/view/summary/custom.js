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
                var guidId = window.valuesConfig;
                var instanceId = window.swellInstanceId;
                var url = 'https://cdn-widgetsrepository.yotpo.com/v1/loader/' + guidId;
                var script = document.createElement('script');
                script.src = url
                script.setAttribute('src_type', 'url')
                document.head.appendChild(script)
                jQuery('.yotpo-widget-instance').attr('data-yotpo-instance-id', instanceId);
            }
        });
    }
);