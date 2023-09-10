define(["uiComponent"], function(Component) {
    "use strict";
    return Component.extend({
        defaults: {
            template: "Yotpo_Loyalty/summary/checkout_widget"
        },
        canLoad: function() {
            return (
                window.swellIsEnabled &&
                window.swellGuid &&
                window.swellInstanceId
            );
        },
        loadJsCustomAfterKoRender: function() {
            var script = document.createElement("script");
            script.src =
                "https://cdn-widgetsrepository.yotpo.com/v1/loader/" +
                window.swellGuid;
            script.setAttribute("src_type", "url");
            document.head.appendChild(script);
            jQuery(".yotpo-widget-instance").attr(
                "data-yotpo-instance-id",
                window.swellInstanceId
            );
        }
    });
});
