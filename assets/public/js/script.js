(function () {
    'use strict';

    $.noty.defaults = {
        layout: 'topRight',
        theme: 'bootstrapTheme',
        dismissQueue: true,
        template: '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',
        animation: {
            open: { height: 'toggle' },
            close: { height: 'toggle' },
            easing: 'swing',
            speed: 200
        },
        timeout: 5000,
        force: false,
        modal: false,
        maxVisible: 5,
        killer: false,
        closeWith: ['click'],
        callback: {
            onShow: function onShow() {},
            afterShow: function afterShow() {},
            onClose: function onClose() {},
            afterClose: function afterClose() {},
            onCloseClick: function onCloseClick() {}
        },
        buttons: false // an array of buttons
    };
})();