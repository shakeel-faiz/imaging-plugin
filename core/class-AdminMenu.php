<?php

namespace AsposeImagingConverter\Core;

class AdminMenu
{
    function init()
    {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    function add_menu_pages()
    {
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

    function render_menu_page()
    {
?>
        <h1>Fancy Tree Activated in WordPress Plugin</h1>

        <div class="aspimgconv_Modal aspimgconv_ModalActive">
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
                        <p class="aspimgconv-box-body-description">Choose which folder you wish to smush. Smush will automatically include any images in subfolders of your selected folder</p>
                        <div id="aspimgconv_Tree"></div>
                    </div>
                    <div class="aspimgconv-box-footer">
                        <button class="aspimgconv-box-footer-btn">Choose directory</button>
                    </div>
                </div>
            </div>
        </div>
        <?php wp_nonce_field('smush_get_dir_list', 'list_nonce'); ?>
        <?php wp_nonce_field('smush_get_image_list', 'image_list_nonce'); ?>
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