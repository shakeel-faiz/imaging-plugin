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

function aspimgconv_init()
{
    // We only run in admin.
    if (!is_admin()) {
        return;
    }

    add_action('wp_ajax_smush_get_directory_listX', 'aspimgconv_directory_list');
    add_action('wp_ajax_image_listX', 'aspimgconv_image_list');
}

aspimgconv_init();

function aspimgconv_directory_list()
{
    // Check For permission.
    if (!current_user_can('manage_options') || !is_user_logged_in()) {
        wp_send_json_error(__('Unauthorized', 'wp-smushit'));
    }

    // Verify nonce.
    //check_ajax_referer('smush_get_dir_list', 'list_nonce');

    $dir  = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_STRING);

    $tree = aspimgconv_get_directory_tree($dir);

    if (!is_array($tree)) {
        wp_send_json_error(__('Unauthorized', 'wp-smushit'));
    }

    wp_send_json($tree);
}

function aspimgconv_get_root_path()
{
    // If main site.
    if (is_main_site()) {
        /**
         * Sometimes content directories may reside outside
         * the installation sub directory. We need to make sure
         * we are selecting the root directory, not installation
         * directory.
         *
         * @see https://xnau.com/finding-the-wordpress-root-path-for-an-alternate-directory-structure/
         * @see https://app.asana.com/0/14491813218786/487682361460247/f
         */
        $content_path = explode('/', wp_normalize_path(WP_CONTENT_DIR));
        // Get root path and explod.
        $root_path = explode('/', get_home_path());

        // Find the length of the shortest one.
        $end         = min(count($content_path), count($root_path));
        $i           = 0;
        $common_path = array();
        // Add the component if they are the same in both paths.
        while ($content_path[$i] === $root_path[$i] && $i < $end) {
            $common_path[] = $content_path[$i];
            $i++;
        }

        return implode('/', $common_path);
    }

    $up = wp_upload_dir();
    return $up['basedir'];
}

function aspimgconv_get_directory_tree($dir = null)
{
    // Get the root path for a main site or subsite.
    $root     = realpath(aspimgconv_get_root_path());
    $post_dir = strlen($dir) >= 1 ? path_join($root, $dir) : $root . $dir;

    // If the final path doesn't contains the root path, bail out.
    if (!$root || false === $post_dir || 0 !== strpos($post_dir, $root)) {
        return false;
    }

    $supported_image = array(
        'gif',
        'jpg',
        'jpeg',
        'png',
    );

    if (file_exists($post_dir) && is_dir($post_dir)) {
        $files = scandir($post_dir);
        // Exclude hidden files.
        if (!empty($files)) {
            $files = preg_grep('/^([^.])/', $files);
        }
        $return_dir = substr($post_dir, strlen($root));

        natcasesort($files);

        if (count($files) !== 0 && !aspimgconv_skip_dir($post_dir)) {
            $tree = array();

            foreach ($files as $file) {
                $html_rel  = htmlentities(ltrim(path_join($return_dir, $file), '/'));
                $html_name = htmlentities($file);
                $ext       = preg_replace('/^.*\./', '', $file);

                $file_path = path_join($post_dir, $file);
                if (!file_exists($file_path) || '.' === $file || '..' === $file) {
                    continue;
                }

                // Skip unsupported files and files that are already in the media library.
                if (!is_dir($file_path) && (!in_array($ext, $supported_image, true) || aspimgconv_is_media_library_file($file_path))) {
                    continue;
                }

                $skip_path = aspimgconv_skip_dir($file_path);

                $tree[] = array(
                    'title'        => $html_name,
                    'key'          => $html_rel,
                    'folder'       => is_dir($file_path),
                    'lazy'         => !$skip_path,
                    'checkbox'     => true,
                    'unselectable' => $skip_path, // Skip Uploads folder - Media Files.
                );
            }

            return $tree;
        }
    }

    return array();
}

function aspimgconv_get_admin_path()
{
    // Replace the site base URL with the absolute path to its installation directory.
    $admin_path = rtrim(str_replace(get_bloginfo('url') . '/', ABSPATH, get_admin_url()), '/');

    return $admin_path;
}

function aspimgconv_skip_dir($path)
{
    // Admin directory path.
    $admin_dir = aspimgconv_get_admin_path();

    // Includes directory path.
    $includes_dir = ABSPATH . WPINC;

    // Upload directory.
    $upload_dir = wp_upload_dir();
    $base_dir   = $upload_dir['basedir'];

    $skip = false;

    if ((false !== strpos($path, $admin_dir)) || false !== strpos($path, $includes_dir)) {
        $skip = true;
    }

    return $skip;
}

function aspimgconv_is_media_library_file($file_path)
{
    $upload_dir  = wp_upload_dir();
    $upload_path = $upload_dir['path'];

    // Get the base path of file.
    $base_dir = dirname($file_path);
    if ($base_dir === $upload_path) {
        return true;
    }

    return false;
}

function aspimgconv_send_error($message)
{
    wp_send_json_error(
        array(
            'message' => sprintf('<p>%s</p>', esc_html($message)),
        )
    );
}

function aspimgconv_image_list()
{
    // Check For permission.
    if (!current_user_can('manage_options')) {
        aspimgconv_send_error(__('Unauthorized', 'wp-smushit'));
    }

    // Verify nonce.
    //check_ajax_referer('smush_get_image_list', 'image_list_nonce');

    // Check if directory path is set or not.
    if (empty($_POST['smush_path'])) { // Input var ok.
        aspimgconv_send_error(__('Empty Directory Path', 'wp-smushit'));
    }

    // FILTER_SANITIZE_URL is trimming the space if a folder contains space.
    $smush_path = filter_input(INPUT_POST, 'smush_path', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);

    try {
        // This will add the images to the database and get the file list.
        $files = aspimgconv_get_image_list($smush_path);
        //throw new Exception("this is shakeel exception");
    } catch (Exception $e) {
        aspimgconv_send_error($e->getMessage());
    }

    // If files array is empty, send a message.
    if (empty($files)) {
        aspimgconv_send_error(__('We could not find any images in the selected directory.', 'wp-smushit'));
    }

    // Send response.
    wp_send_json_success(count($files));
}

class aspimgconv_Iterator extends RecursiveFilterIterator
{
    /**
     * Accept method.
     *
     * @return bool
     */
    public function accept()
    {
        $path = $this->current()->getPathname();
        return true;
    }
}

function aspimgconv_get_image_list($paths = '')
{
    $base_dir = "C:\\xampp\htdocs\wscubetech\wp-content";

    $filtered_dir = new aspimgconv_Iterator(new RecursiveDirectoryIterator($base_dir));

    // File Iterator.
    $iterator = new RecursiveIteratorIterator($filtered_dir, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $file) {
        error_log(print_r($file, TRUE), 3, "d:/download/a.txt");
    }
}
