<?php

namespace AsposeImagingConverter\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Dir
{
    public static $table_exist;
    public $stats = null;

    public function init()
    {
        // We only run in admin.
        if (!is_admin()) {
            return;
        }

        /**
         * Handle Ajax request 'aiconv_get_directory_list'.
         *
         * This needs to be before self::should_continue so that the request from network admin is processed.
         */
        if (defined('DOING_AJAX') && DOING_AJAX) {

            add_action('wp_ajax_aiconv_get_directory_list', array($this, 'directory_list'));

            // Scan the given directory path for the list of images.
            add_action('wp_ajax_aiconv_image_list', array($this, 'image_list'));

            /**
             * Scanner ajax actions.
             *
             * @since 2.8.1
             */
            add_action('wp_ajax_directory_aiconv_start', array($this, 'directory_aiconv_start'));
            add_action('wp_ajax_directory_aiconv_check_step', array($this, 'directory_aiconv_check_step'));
            add_action('wp_ajax_directory_aiconv_finish', array($this, 'directory_aiconv_finish'));
        }
    }

    private function optimise_image($id, $path)
    {
        global $wpdb;

        $result = AsposeImagingCloudMethods::Process($path);

        if (!isset($result["success"])) {
            $error_msg = "Success not set inside the returned result";
        } elseif (!$result["success"]) {
            $error_msg = $result["errorMsg"];
        }

        if (!empty($error_msg)) {
            // Store the error in DB. All good, Update the stats.
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}asposeimagingconverter_dir_images SET error=%s WHERE id=%d LIMIT 1",
                    $error_msg,
                    $id
                )
            ); // Db call ok; no-cache ok.

            wp_send_json_error(
                array(
                    'error' => $error_msg,
                    'image' => array(
                        'id' => $id,
                    ),
                )
            );
        }

        // All good, Update the stats.
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}asposeimagingconverter_dir_images SET error=NULL, image_size=%d, orig_size=%d, file_time=%d WHERE id=%d LIMIT 1",
                $result['sizeAfter'],
                $result['sizeBefore'],
                @filectime($path), // Get file time.
                $id
            )
        ); // Db call ok; no-cache ok.

    }

    public function directory_aiconv_start()
    {
        wp_send_json_success();
    }

    public function directory_aiconv_check_step()
    {
        $urls = $this->get_scanned_images();
        $current_step = absint($_POST['step']); // Input var ok.

        if (isset($urls[$current_step])) {
            $this->optimise_image((int) $urls[$current_step]['id'], $urls[$current_step]['path']);
        }

        wp_send_json_success();
    }

    public function directory_aiconv_finish()
    {
        wp_send_json_success();
    }

    function directory_list()
    {
        // Check For permission.
        if (!current_user_can('manage_options') || !is_user_logged_in()) {
            wp_send_json_error(__('Unauthorized', 'wp-aiconvit'));
        }

        $dir  = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_STRING);

        $tree = $this->get_directory_tree($dir);

        if (!is_array($tree)) {
            wp_send_json_error(__('Unauthorized', 'wp-aiconvit'));
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

    private function is_image_from_extension($path)
    {
        $supported_image = array('gif', 'jpg', 'jpeg', 'png');
        $ext             = strtolower(pathinfo($path, PATHINFO_EXTENSION)); // Using strtolower to overcome case sensitive.

        if (in_array($ext, $supported_image, true)) {
            return true;
        }

        return false;
    }

    private function is_image($path)
    {
        // Check if the path is valid.
        if (!file_exists($path) || !$this->is_image_from_extension($path)) {
            return false;
        }

        if (false !== stripos($path, 'phar://')) {
            return false;
        }

        $a = @getimagesize($path);

        // If a is not set.
        if (!$a || empty($a)) {
            return false;
        }

        $image_type = $a[2];

        if (in_array($image_type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
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
            $this->send_error(__('Unauthorized', 'wp-aiconvit'));
        }

        // Check if directory path is set or not.
        if (empty($_POST['aiconv_path'])) { // Input var ok.
            $this->send_error(__('Empty Directory Path', 'wp-aiconvit'));
        }

        // FILTER_SANITIZE_URL is trimming the space if a folder contains space.
        $aiconv_path = filter_input(INPUT_POST, 'aiconv_path', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);

        try {
            // This will add the images to the database and get the file list.
            $files = $this->get_image_list($aiconv_path);
            //throw new \Exception("this is shakeel exception");
        } catch (\Exception $e) {
            $this->send_error($e->getMessage());
        }

        // If files array is empty, send a message.
        if (empty($files)) {
            $this->send_error(__('We could not find any images in the selected directory.', 'wp-aiconvit'));
        }

        // Send response.
        wp_send_json_success(count($files));
    }

    private function store_images($values, $images)
    {
        global $wpdb;

        $query = $this->build_query($values, $images);
        $wpdb->query($query); // Db call ok; no-cache ok.

        $updateImageSizeAndErrorsToNull = "UPDATE {$wpdb->prefix}AsposeImagingConverter_dir_images SET image_size=null, error=null where last_scan=(select max(last_scan) from {$wpdb->prefix}AsposeImagingConverter_dir_images)";
        $wpdb->query($updateImageSizeAndErrorsToNull); // Db call ok; no-cache ok.
    }

    private function build_query($values, $images)
    {
        if (empty($images) || empty($values)) {
            return false;
        }

        global $wpdb;
        $values = implode(',', $values);

        // Replace with image path and respective parameters.
        $query = "INSERT INTO {$wpdb->prefix}AsposeImagingConverter_dir_images (path, path_hash, orig_size, file_time, last_scan) VALUES $values ON DUPLICATE KEY UPDATE image_size = IF( file_time < VALUES(file_time), NULL, image_size ), file_time = IF( file_time < VALUES(file_time), VALUES(file_time), file_time ), last_scan = VALUES( last_scan )";
        $query = $wpdb->prepare($query, $images); // Db call ok; no-cache ok.

        return $query;
    }

    public function get_scanned_images()
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT id, path, orig_size FROM {$wpdb->prefix}AsposeImagingConverter_dir_images WHERE last_scan = (SELECT MAX(last_scan) FROM {$wpdb->prefix}AsposeImagingConverter_dir_images )  GROUP BY id ORDER BY id", ARRAY_A); // Db call ok; no-cache ok.

        // Return image ids.
        if (is_wp_error($results)) {
            error_log(sprintf('WP aiconv Query Error in %s at %s: %s', __FILE__, __LINE__, $results->get_error_message()));
            $results = array();
        }

        return $results;
    }

    /**
     *  @param string|array $paths  Path where to look for images, or selected images.
     */
    private function get_image_list($paths = '')
    {
        // Error with directory tree.
        if (!is_array($paths)) {
            $this->send_error(__('There was a problem getting the selected directories', 'wp-aiconvit'));
        }

        $count     = 0;
        $images    = array();
        $values    = array();
        $timestamp = gmdate('Y-m-d H:i:s');

        // Temporary increase the limit.
        wp_raise_memory_limit('image');

        foreach ($paths as $relative_path) {
            // Make the path absolute.
            $path = trim($this->get_root_path() . '/' . $relative_path);
            $path = str_replace('\\', '/', $path);

            // Prevent phar deserialization vulnerability.
            if (stripos($path, 'phar://') !== false) {
                continue;
            }

            /**
             * Path is an image.
             *
             * @TODO: The is_dir() check fails directories with spaces.
             */
            if (!is_dir($path) && !$this->is_media_library_file($path) && !strpos($path, '.bak')) {

                if (!$this->is_image($path)) {
                    continue;
                }

                // Image already added. Skip.
                if (in_array($path, $images, true)) {
                    continue;
                }

                $images[] = $path;
                $images[] = md5($path);
                $images[] = @filesize($path);  // Get the file size.
                $images[] = @filectime($path); // Get the file modification time.
                $images[] = $timestamp;
                $values[] = '(%s, %s, %d, %d, %s)';
                $count++;

                // Store the images in db at an interval of 5k.
                if ($count >= 5000) {
                    $count = 0;
                    $this->store_images($values, $images);
                    $images = $values = array();
                }

                continue;
            }

            /**
             * Path is a directory.
             */
            $base_dir = realpath(rawurldecode($path));

            if (!$base_dir) {
                $this->send_error(__('Unauthorized', 'wp-aiconvit'));
            }

            $filtered_dir = new Iterator(new RecursiveDirectoryIterator($base_dir));

            // File Iterator.
            $iterator = new RecursiveIteratorIterator($filtered_dir, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($iterator as $file) {

                // Used in place of Skip Dots, For php 5.2 compatibility.
                if (basename($file) === '..' || basename($file) === '.') {
                    continue;
                }

                // Not a file. Skip.
                if (!$file->isFile()) {
                    continue;
                }

                $file_path = $file->getPathname();
                $file_path = str_replace('\\', '/', $file_path);

                if ($this->is_image($file_path) && !$this->is_media_library_file($file_path) && strpos($file, '.bak') === false) {
                    /** To be stored in DB, Part of code inspired from Ewwww Optimiser  */
                    $images[] = $file_path;
                    $images[] = md5($file_path);
                    $images[] = $file->getSize();
                    $images[] = @filectime($file_path); // Get the file modification time.
                    $images[] = $timestamp;
                    $values[] = '(%s, %s, %d, %d, %s)';
                    $count++;
                }

                // Store the images in db at an interval of 5k.
                if ($count >= 5000) {
                    $count = 0;
                    $this->store_images($values, $images);
                    $images = $values = array();
                }
            }
        }

        if (empty($images) || 0 === $count) {
            return array();
        }

        // Update rest of the images.
        $this->store_images($values, $images);

        // Get the image ids.
        return $this->get_scanned_images();
    }

    public function create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->base_prefix}AsposeImagingConverter_dir_images (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			path text NOT NULL,
			path_hash CHAR(32),
			resize varchar(55),
			lossy varchar(55),
			error varchar(55) DEFAULT NULL,
			image_size int(10) unsigned,
			orig_size int(10) unsigned,
			file_time int(10) unsigned,
			last_scan timestamp DEFAULT '0000-00-00 00:00:00',
			meta text,
			UNIQUE KEY id (id),
			UNIQUE KEY path_hash (path_hash),
			KEY image_size (image_size)
		) $charset_collate;";

        // Include the upgrade library to initialize a table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Set flag.
        self::$table_exist = true;
    }

    public function last_scan_results()
    {
        global $wpdb;
        $optimised = 0;

        $results = $wpdb->get_results(
            "select path, image_size, orig_size from {$wpdb->prefix}AsposeImagingConverter_dir_images where last_scan = (SELECT max(last_scan) FROM {$wpdb->prefix}AsposeImagingConverter_dir_images ) and image_size is not null ORDER BY id;",
            ARRAY_A
        ); // Db call ok; no-cache ok.

        $images    = array();
        $images  = array_merge($images, $results);

        if (empty($images)) {
            $s = [
                "human" => "0 B",
                "percent" => 0,
                "optimised" => 0
            ];

            return $s;
        }

        // Iterate over stats, return count and savings.
        if (!empty($images)) {
            // Init the stats array.
            $this->stats = array(
                'path'       => '',
                'image_size' => 0,
                'orig_size'  => 0,
            );

            foreach ($images as $im) {
                foreach ($im as $key => $val) {
                    if ('path' === $key) {
                        $this->optimised_images[$val] = $im;
                        continue;
                    }
                    $this->stats[$key] += (int) $val;
                }
                $optimised++;
            }
        }

        // Get the savings in bytes and percent.
        if (!empty($this->stats) && !empty($this->stats['orig_size'])) {
            $this->stats['bytes']   = ($this->stats['orig_size'] > $this->stats['image_size']) ? $this->stats['orig_size'] - $this->stats['image_size'] : 0;
            $this->stats['percent'] = number_format_i18n((($this->stats['bytes'] / $this->stats['orig_size']) * 100), 1);
            // Convert to human readable form.
            $decimal = ($this->stats['bytes'] < 1024) ? 0 : 1;
            $this->stats['human'] = size_format($this->stats['bytes'], $decimal);
        }

        $this->stats['total']     = count($images);
        $this->stats['optimised'] = $optimised;

        return $this->stats;
    }

    public function last_scan_errors()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "select path from {$wpdb->prefix}AsposeImagingConverter_dir_images where last_scan = (SELECT max(last_scan) FROM {$wpdb->prefix}AsposeImagingConverter_dir_images ) and error is not null ORDER BY id;",
            ARRAY_A
        ); // Db call ok; no-cache ok.

        return count($results);
    }
}
