var WP_AIConv = WP_AIConv || {};
window.WP_AIConv = WP_AIConv;

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
                    action: 'directory_aiconv_start'
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

            let min = Math.min(round, 100);

            return min;
        },

        onFinishStep(progress) {
            jQuery('.aic-progress-block .aic-progress-text span').text(progress + '%');
            jQuery('.aic-progress-block .aic-progress-bar span').width(progress + '%');

            jQuery('#ProgressStateText').html(currentStep - failedItems + '/' + totalSteps + ' images optimized');
        },
        onFinish() {
            jQuery('#ProgressStateText').html('Completed.');
            setTimeout(()=>window.location.reload(true), 3000);
        },
    }

    const step = function(remainingSteps) {

        if (remainingSteps > 0) {
            currentStep = totalSteps - remainingSteps;

            return jQuery.post(ajaxurl, {
                action: 'directory_aiconv_check_step',
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
                } else if (typeof response.data.error !== 'undefined') {
                    failedItems++;
                    currentStep++;
                    remainingSteps = remainingSteps - 1;
                    obj.onFinishStep(obj.getProgress());
                    step(remainingSteps).fail(obj.showScanError);
                }

            });
        }

        return jQuery.post(ajaxurl, {
            action: 'directory_aiconv_finish',
            items: totalSteps - (failedItems + skippedItems),
            failed: failedItems,
            skipped: skippedItems,
        }, (response)=>obj.onFinish(response));

    }

    return obj;
}

jQuery(function($) {

    WP_AIConv.directory = {
        tree: [],
        chooseDirectoryHasBeenClicked: false,

        init() {
            const self = this;

            $("#btnChooseDirectory").on('click', function(e) {
                $("#ChooseDirModal").addClass("aspimgconv_ModalActive");
                self.initFileTree();
            });

            $("#ChooseDirModal .aspimgconv-box-footer-btn").on('click', function() {

                //If directory has been choosen, don't hide the modal box
                self.chooseDirectoryHasBeenClicked = true;

                //hide footer button text and display loader
                //disable the button as well
                $("#ChooseDirModal .aic-btn-text").hide();
                $("#ChooseDirModal .aic-btn-loader").show();
                $('#ChooseDirModal .aspimgconv-box-footer-btn').prop('disabled', true);

                const selectedFolders = self.tree.getSelectedNodes();

                const paths = [];
                selectedFolders.forEach(function(folder) {
                    paths.push(folder.key);
                });

                const param = {
                    action: 'aiconv_image_list',
                    aiconv_path: paths,
                };

                $.post(ajaxurl, param, function(response) {
                    //close the ChooseDir dialog
                    $("#ChooseDirModal").removeClass("aspimgconv_ModalActive");

                    if (response.success) {
                        //Show the Progress dialog
                        $("#ProgressModal").addClass("aspimgconv_ModalActive");

                        let scanner = new DirectoryScanner(response.data,0);
                        scanner.scan();
                    } else {
                        alert(response.data.message);
                        setTimeout(()=>window.location.reload(true), 10);
                    }

                });
            });

            $("#ChooseDirModal .aspimgconv-btn-close").on('click', function() {
                //If directory has been choosen, don't hide the modal box
                if (!self.chooseDirectoryHasBeenClicked) {
                    $("#ChooseDirModal").removeClass("aspimgconv_ModalActive");
                }
            });

            //Reload on cancel
            $("#ProgressModal .aspimgconv-box-footer-btn, #ProgressModal .aspimgconv-btn-close").on('click', function() {
                $('#ProgressStateText').html('Cancelling...');
                window.location.reload(true);
            });
        },

        initFileTree() {
            const self = this;
            const aiconvButton = $('#ChooseDirModal .aspimgconv-box-footer-btn');

            ajaxSettings = {
                type: 'GET',
                url: ajaxurl,
                data: {
                    action: 'aiconv_get_directory_list',
                },
                cache: false,
            };

            // Object already defined.
            if (Object.entries(self.tree).length > 0) {
                return;
            }

            createTree = $.ui.fancytree.createTree;

            self.tree = createTree('#aspimgconv_Tree', {
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
                    aiconvButton.prop('disabled', !+self.tree.getSelectedNodes().length);
                }
            });
        }
    };

    WP_AIConv.directory.init();
});
