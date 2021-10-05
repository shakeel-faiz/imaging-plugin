<?php
/*
Plugin Name: Aspose.Imaging Converter
Plugin URI:
Description: Aspose.Imaging Converter Description
Version: 1.0
Author: aspose.cloud Marketplace
Author URI: https://www.aspose.cloud/
*/

if (!defined('ASPIMGCONV_URL')) {
    define('ASPIMGCONV_URL', plugin_dir_url(__FILE__));
}

add_action('admin_menu', 'aspimgconv_add_menu_pages');

function aspimgconv_add_menu_pages()
{
    add_menu_page(
        'Aspose Imaging Converter',
        'Aspose Imaging Converter',
        'edit_published_posts',
        'aspose_imaging_converter',
        'aspimgconv_render_menu_page',
        'dashicons-admin-page',
        30
    );
}

function aspimgconv_render_menu_page()
{
?>
    <h1>Fancy Tree Activated in WordPress Plugin</h1>

    <style>
        .aspimgconv_Modal {
            box-sizing: border-box;
            position: fixed;
            top: 32px;
            left: 160px;
            width: calc(100% - 160px);
            height: calc(100vh - 32px);
            background-color: rgb(83 72 72 / 88%);
            display: none;
            flex-direction: column;
            align-items: center;
            padding: 30px 0;
            overflow-x: hidden;
            overflow-y: auto;
            z-index: 13;
        }

        .aspimgconv_Modal * {
            box-sizing: border-box;
        }

        .aspimgconv_Modal.aspimgconv_ModalActive {
            display: flex;
        }

        .aspimgconv_Content {
            box-sizing: border-box;
            max-width: 660px;
            width: 100%;
            padding: 10px 30px 50px;
            animation: aspimgconv_ScaleIn 0.5s ease-in forwards;
        }

        @keyframes aspimgconv_ScaleIn {
            0% {
                opacity: 0;
            }

            25% {
                opacity: 0;
                transform: translate3d(0, 10px, 0) scale(0.9);
            }

            100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
        }

        .aspimgconv-box {
            background-color: white;
        }

        .aspimgconv-box-header {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            background-color: white;
            border-bottom: 1px solid #e6e6e6;
        }

        .aspimgconv-box-title {
            padding: 0px 30px;
        }

        .aspimgconv-btn-div {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 0px 10px;
        }

        .aspimgconv-btn-close {
            font-size: 30px;
            outline: none;
            border: none;
            cursor: pointer;
        }

        .aspimgconv-box-body {
            padding: 30px;
        }

        .aspimgconv-box-body-description {
            margin: 0 0 20px;
            font-size: 15px;
        }
    </style>

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
            </div>
        </div>
    </div>
    <?php wp_nonce_field('smush_get_dir_list', 'list_nonce'); ?>
<?php
}

add_action('admin_enqueue_scripts', 'aspimgconv_enqueue_scripts');

function aspimgconv_enqueue_scripts()
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
        ASPIMGCONV_URL . '/assets/js/fancytree/lib/jquery.fancytree.ui-deps.js',
        array('jquery'),
        '1.0',
        true
    );

    wp_register_script(
        'jqfancytree',
        ASPIMGCONV_URL . '/assets/js/fancytree/lib/jquery.fancytree.js',
        array('jqfancytreedeps'),
        '1.0',
        true
    );

    wp_register_script(
        'aspimgconv_app',
        ASPIMGCONV_URL . '/assets/js/app.js',
        array('jqfancytree'),
        '1.0',
        true
    );

    wp_enqueue_script('aspimgconv_app');

    wp_register_style(
        'jqfancytreecss',
        ASPIMGCONV_URL . '/assets/js/fancytree/css/ui.fancytree.css',
        array(),
        '1.0'
    );

    wp_enqueue_style('jqfancytreecss');
}
