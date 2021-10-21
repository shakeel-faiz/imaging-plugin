(function($) {
    $(function() {

        ajaxSettings = {
            type: 'GET',
            url: ajaxurl,
            data: {
                action: 'smush_get_directory_listX',
                list_nonce: $('input[name="list_nonce"]').val(),
            },
            cache: false,
        };

        createTree = $.ui.fancytree.createTree;

        window.aspimgconv_Tree = createTree('#aspimgconv_Tree', {
            autoCollapse: true,
            // Automatically collapse all siblings, when a node is expanded
            clickFolderMode: 3,
            // 1:activate, 2:expand, 3:activate and expand, 4:activate (dblclick expands)
            checkbox: true,
            // Show checkboxes
            debugLevel: 0,
            // 0:quiet, 1:errors, 2:warnings, 3:infos, 4:debug
            selectMode: 3,
            // 1:single, 2:multi, 3:multi-hier
            tabindex: '0',
            // Whole tree behaves as one single control
            keyboard: true,
            // Support keyboard navigation
            quicksearch: true,
            // Navigate to next node by typing the first letters
            source: ajaxSettings,
            lazyLoad: (event,data)=>{
                data.result = new Promise(function(resolve, reject) {
                    ajaxSettings.data.dir = data.node.key;
                    $.ajax(ajaxSettings).done((response)=>resolve(response)).fail(reject);
                }
                );
            }
            ,
            loadChildren: (event,data)=>data.node.fixSelection3AfterClick(),
            // Apply parent's state to new child nodes:
            activate: function(event, data) {
                $("#statusLine").text(event.type + ": " + data.node);
            },
            select: function(event, data) {
                $("#statusLine").text(event.type + ": " + data.node.isSelected() + " " + data.node);
            }
        });

        $(".aspimgconv-box-footer-btn").on('click', function() {
            console.log("I am clicked.");

            //close the dialog
            $(".aspimgconv_Modal").removeClass("aspimgconv_ModalActive");

            const selectedFolders = aspimgconv_Tree.getSelectedNodes();

            const paths = [];
            selectedFolders.forEach(function(folder) {
                paths.push(folder.key);
            });

            const param = {
                action: 'image_listX',
                smush_path: paths,
                image_list_nonce: $('input[name="image_list_nonce"]').val()
            };

            $.post(ajaxurl, param, function(response) {
                console.log("eeeee");
            });
        });

    });
    //inside this all code
}
)(jQuery);
