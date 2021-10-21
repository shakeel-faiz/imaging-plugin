function DirectoryScanner(totalSteps, currentStep) {

    totalSteps = parseInt(totalSteps);
    currentStep = parseInt(currentStep);

    let cancelling = false
      , failedItems = 0
      , skippedItems = 0;

    const obj = {
        scan() {
            const remainingSteps = totalSteps - currentStep;

            if (currentStep != 0) {
                console.log("call step function")
            } else {
                jQuery.post(ajaxurl, {
                    action: 'directory_smush_start'
                }, function() {
                    step(remainingSteps).fail(this.showScanError);
                }).fail(this.showScanError);
            }

        },

        showScanError(res) {
            console.log("Name: showScanError");
        },

        getProgress() {

            if (cancelling) {
                return 0;
            }

            const remainingSteps = totalSteps - currentStep;

            let per = (totalSteps - remainingSteps) * 100 / totalSteps;
            let round = Math.round(per);

            let min = Math.min(round, 99);

            return min;
        },

        onFinishStep(progress) {
            console.log("update progress bar:" + progress);
        },
        onFinish() {
            console.log("remove progress bar dialog");
        },
    }

    const step = function(remainingSteps) {

        if (remainingSteps >= 0) {
            currentStep = totalSteps - remainingSteps;

            return jQuery.post(ajaxurl, {
                action: 'directory_smush_check_step',
                step: currentStep
            }, function(response) {

                if (typeof response.success !== 'undefined' && response.success) {
                    if (typeof response.data !== 'undefined' && typeof response.data.skipped !== 'undefined' && response.data.skipped === true) {
                        skippedItems++;
                    }

                    currentStep++;
                    remainingSteps = remainingSteps - 1;

                    obj.onFinishStep(obj.getProgress());

                    step(remainingSteps).fail(obj.showScanError);
                }

            });
        }

        return jQuery.post(ajaxurl, {
            action: 'directory_smush_finish',
            items: totalSteps - (failedItems + skippedItems),
            failed: failedItems,
            skipped: skippedItems,
        }, (response)=>obj.onFinish(response));

    }

    return obj;
}

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

            //close the dialog
            $(".aspimgconv_Modal").removeClass("aspimgconv_ModalActive");

            const selectedFolders = aspimgconv_Tree.getSelectedNodes();

            const paths = [];
            selectedFolders.forEach(function(folder) {
                paths.push(folder.key);
            });

            const param = {
                action: 'image_list',
                smush_path: paths,
                image_list_nonce: $('input[name="image_list_nonce"]').val()
            };

            $.post(ajaxurl, param, function(response) {
                let scanner = new DirectoryScanner(response.data,0);
                scanner.scan();
            });
        });

    });
    //inside this all code
}
)(jQuery);
