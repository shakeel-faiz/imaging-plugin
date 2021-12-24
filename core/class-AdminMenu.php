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
        <style>
            #btnChooseDirectory {
                box-sizing: border-box;
                cursor: pointer;
                display: inline-block;
                border-width: 2px;
                border-style: solid;
                border-color: transparent;
                border-radius: 4px;
                text-decoration: none;
                text-align: center;
                min-width: 80px;
                padding: 5px 14px;
                text-transform: uppercase;
                background-color: #17A8E3;
                color: #fff;
            }
        </style>

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
                        <p class="aspimgconv-box-body-description">Please choose the folder which contains the images that you want to optimize. Aspose.Imaging Converter will automatically include the images from this folder as well as from all its subfolders.</p>
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
                        <h3 class="aspimgconv-box-title">Optimizing your images ...</h3>
                        <div class="aspimgconv-btn-div">
                            <button class="aspimgconv-btn-close">&times;</button>
                        </div>
                    </div>
                    <div class="aspimgconv-box-body">
                        <p class="aspimgconv-box-body-description">Images are being compressed and optimized, please do not close this dialog.</p>
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

        $dir = \WP_AsposeImagingConverter::get_instance()->dir();
        $stats = $dir->last_scan_results();
        $errors_count = $dir->last_scan_errors();

        ?>
        <style>
            .lsr-section * {
                box-sizing: border-box;
            }

            .lsr-section {
                border: 1px solid black;
                border-radius: 15px;
                padding: 20px 70px;
                width: 60%;
                margin: 10px auto 10px 10px;
            }

            .lsr-heading {
                color: white;
                padding: 10px 10px;
                width: 40%;
                font-weight: bold;
                background: #428ca9;
                text-align: center;
                display: inline-block;
                font-size: larger;
            }

            .lsr-result {
                display: inline-block;
                width: 55%;
                text-align: center;
                font-size: large;
                font-weight: bold;

            }

            .lsr-row {
                border: 1px solid black;
                width: 95%;
                margin-bottom: 6px;
                background-color: #ededed;
            }
        </style>

        <div class="lsr-wrapper">
            <div class="lsr-section">
                <h1>Last Scan Results</h1>
                <div class="lsr-row">
                    <div class="lsr-heading">Total Savings</div>
                    <div class="lsr-result"><?php echo $stats["human"] ?></div>
                </div>
                <div class="lsr-row">
                    <div class="lsr-heading">Percent</div>
                    <div class="lsr-result"><?php echo $stats["percent"] ?>%</div>
                </div>
                <div class="lsr-row">
                    <div class="lsr-heading">Images Optimized</div>
                    <div class="lsr-result"><?php echo $stats["optimised"] ?></div>
                </div>
                <div class="lsr-row">
                    <div class="lsr-heading">Errors</div>
                    <div class="lsr-result"><?php echo $errors_count ?></div>
                </div>
            </div>
        </div>
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
