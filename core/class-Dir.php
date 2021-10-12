<?php

namespace AsposeImagingConverter\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Dir
{
    function directory_list()
    {
        // Check For permission.
        if (!current_user_can('manage_options') || !is_user_logged_in()) {
            wp_send_json_error(__('Unauthorized', 'wp-smushit'));
        }

        // Verify nonce.
        //check_ajax_referer('smush_get_dir_list', 'list_nonce');

        $dir  = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_STRING);

        $tree = $this->get_directory_tree($dir);

        if (!is_array($tree)) {
            wp_send_json_error(__('Unauthorized', 'wp-smushit'));
        }

        wp_send_json($tree);
    }

    function get_root_path()
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

    function get_directory_tree($dir = null)
    {
        // Get the root path for a main site or subsite.
        $root     = realpath($this->get_root_path());
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

            if (count($files) !== 0 && !$this->skip_dir($post_dir)) {
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
                    if (!is_dir($file_path) && (!in_array($ext, $supported_image, true) || $this->is_media_library_file($file_path))) {
                        continue;
                    }

                    $skip_path = $this->skip_dir($file_path);

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

    function get_admin_path()
    {
        // Replace the site base URL with the absolute path to its installation directory.
        $admin_path = rtrim(str_replace(get_bloginfo('url') . '/', ABSPATH, get_admin_url()), '/');

        return $admin_path;
    }

    function skip_dir($path)
    {
        // Admin directory path.
        $admin_dir = $this->get_admin_path();

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

    function is_media_library_file($file_path)
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

    function send_error($message)
    {
        wp_send_json_error(
            array(
                'message' => sprintf('<p>%s</p>', esc_html($message)),
            )
        );
    }

    function image_list()
    {
        // Check For permission.
        if (!current_user_can('manage_options')) {
            $this->send_error(__('Unauthorized', 'wp-smushit'));
        }

        // Verify nonce.
        //check_ajax_referer('smush_get_image_list', 'image_list_nonce');

        // Check if directory path is set or not.
        if (empty($_POST['smush_path'])) { // Input var ok.
            $this->send_error(__('Empty Directory Path', 'wp-smushit'));
        }

        // FILTER_SANITIZE_URL is trimming the space if a folder contains space.
        $smush_path = filter_input(INPUT_POST, 'smush_path', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);

        try {
            // This will add the images to the database and get the file list.
            $files = $this->get_image_list($smush_path);
            //throw new \Exception("this is shakeel exception");
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }

        // If files array is empty, send a message.
        if (empty($files)) {
            $this->send_error(__('We could not find any images in the selected directory.', 'wp-smushit'));
        }

        // Send response.
        wp_send_json_success(count($files));
    }

    function get_image_list($paths = '')
    {
        $base_dir = "C:\\xampp\htdocs\wscubetech\wp-content";

        $filtered_dir = new RFIterator(new RecursiveDirectoryIterator($base_dir));

        // File Iterator.
        $iterator = new RecursiveIteratorIterator($filtered_dir, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $file) {
            error_log(print_r($file, TRUE), 3, "d:/download/a.txt");
        }
    }
}