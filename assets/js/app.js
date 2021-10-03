(function ($) {
    $(function () {

        $("#tree").fancytree({
            checkbox: true,
            selectMode: 3,
            source: {
                url: "https://cdn.rawgit.com/mar10/fancytree/72e03685/demo/ajax-tree-products.json"
            },
            lazyLoad: function (event, data) {
                //data.result = { url: "https://cdn.rawgit.com/mar10/fancytree/72e03685/demo/ajax-sub2.json" };

                data.result = new Promise(function (resolve, reject) {
                    var myajax = {
                        type: 'GET',
                        cache: false,
                        url: "https://cdn.rawgit.com/mar10/fancytree/72e03685/demo/ajax-sub2.json"
                    };

                    $.ajax(myajax).
                        done(response => resolve(response)).
                        fail(reject);
                });

                data.result.then(function () {
                    console.log("I am called");
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