<?php

namespace AsposeImagingConverter\Core;

class AdminMenu
{
    function init()
    {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    function should_add_menu()
    {
        return !(strlen(get_option("aspose-cloud-app-sid")) < 1);
    }

    function add_menu_pages()
    {
        if ($this->should_add_menu()) {
            add_menu_page(
                'Aspose Imaging Converter',
                'Aspose Imaging Converter',
                'edit_published_posts',
                'aspose_imaging_converter',
                array($this, 'render_menu_page'),
                'dashicons-admin-page',
                30
            );
        }
    }

    function render_menu_page()
    {
?>
        <h1>Aspose.Imaging Cloud Converter</h1>
        <p>Please choose your WordPress directory that contains the images.
            Aspose.Imaging Cloud Converter will optimize the images inside the
            directory and its sub-directories.</p>

        <button id="btnChooseDirectory">Choose Directory</button>

        <div id="ChooseDirModal" class="aspimgconv_Modal">
            <div class="aspimgconv_ModalOverlay"></div>
            <div class="aspimgconv_Content">
                <div class="aspimgconv-box">
                    <div class="aspimgconv-box-header">
                        <h3 class="aspimgconv-box-title">Choose Directory</h3>
                        <div class="aspimgconv-btn-div">
                            <button class="aspimgconv-btn-close">&times;</button>
                        </div>
                    </div>
                    <div class="aspimgconv-box-body">
                        <p class="aspimgconv-box-body-description">Choose which folder you wish to optimize images. Aspose.Imaging Converter will automatically include any images in subfolders of your selected folder</p>
                        <div id="aspimgconv_Tree"></div>
                    </div>
                    <div class="aspimgconv-box-footer">
                        <button class="aspimgconv-box-footer-btn" disabled>
                            <div class="aic-btn-text">Choose directory</div>
                            <div class="aic-btn-loader" style="margin: 0 50px;display:none;">
                                <div class="aic-progress-loader"></div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="ProgressModal" class="aspimgconv_Modal">
            <div class="aspimgconv_ModalOverlay"></div>
            <div class="aspimgconv_Content">
                <div class="aspimgconv-box">
                    <div class="aspimgconv-box-header">
                        <h3 class="aspimgconv-box-title">Choose Directory</h3>
                        <div class="aspimgconv-btn-div">
                            <button class="aspimgconv-btn-close">&times;</button>
                        </div>
                    </div>
                    <div class="aspimgconv-box-body">
                        <p class="aspimgconv-box-body-description">Images are being compressed and optimized, please leave this tab open until the process completes.</p>
                        <div id="aspimgconv_Progress">
                            <div class="aic-progress-block">
                                <div class="aic-progress">
                                    <div style="margin: 0 10px;">
                                        <div class="aic-progress-loader"></div>
                                    </div>
                                    <div class="aic-progress-text">
                                        <span>0%</span>
                                    </div>
                                    <div class="aic-progress-bar">
                                        <span style="width:0"></span>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align:center">
                                <div id="ProgressStateText">Optimizing images
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="aspimgconv-box-footer">
                        <button class="aspimgconv-box-footer-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <?php

        $stats = \WP_AsposeImagingConverter::get_instance()->dir()->last_scan_results();

        ?>
        <br><br>
        <b>Total Savings: </b> <?php echo $stats["human"] ?> <br>
        <b>Percent: </b> <?php echo $stats["percent"] ?> %<br>
        <b>Images Optimized: </b><?php echo $stats["optimised"] ?><br>
<?php
    }

    function enqueue_scripts()
    {
        $current_page   = '';
        $current_screen = '';

        if (function_exists('get_current_screen')) {
            $current_screen = get_current_screen();
            $current_page   = !empty($current_screen) ? $current_screen->base : $current_page;
        }

        if (strpos($current_page, "aspose_imaging_converter") === false) {
            return;
        }

        wp_register_script(
            'jqfancytreedeps',
            ASPIMGCONV_URL . 'assets/js/fancytree/lib/jquery.fancytree.ui-deps.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_register_script(
            'jqfancytree',
            ASPIMGCONV_URL . 'assets/js/fancytree/lib/jquery.fancytree.js',
            array('jqfancytreedeps'),
            '1.0',
            true
        );

        wp_register_script(
            'aspimgconv_app',
            ASPIMGCONV_URL . 'assets/js/app.js',
            array('jqfancytree'),
            '1.0',
            true
        );

        wp_enqueue_script('aspimgconv_app');

        wp_register_style(
            'jqfancytreecss',
            ASPIMGCONV_URL . 'assets/js/fancytree/css/ui.fancytree.css',
            array(),
            '1.0'
        );

        wp_enqueue_style('jqfancytreecss');

        wp_register_style(
            'aspimgconv_styles',
            ASPIMGCONV_URL . 'assets/css/styles.css',
            array(),
            '1.0'
        );

        wp_enqueue_style('aspimgconv_styles');
    }
}
