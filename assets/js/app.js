(function ($) {
    $(function () {

        ajaxSettings = {
            type: 'GET',
            url: ajaxurl,
            data: {
                action: 'smush_get_directory_list',
                list_nonce: $('input[name="list_nonce"]').val(),
            },
            cache: false,
        };

        $("#tree").fancytree({
            checkbox: true,
            selectMode: 3,
            source: ajaxSettings,
            lazyLoad: (event, data) => {
                data.result = new Promise(function (resolve, reject) {
                    ajaxSettings.data.dir = data.node.key;
                    $.ajax(ajaxSettings)
                        .done((response) => resolve(response))
                        .fail(reject);
                });
            },
            activate: function (event, data) {
                $("#statusLine").text(event.type + ": " + data.node);
            },
            select: function (event, data) {
                $("#statusLine").text(event.type + ": " + data.node.isSelected() + " " + data.node);
            }
        });

    });//inside this all code
})(jQuery);