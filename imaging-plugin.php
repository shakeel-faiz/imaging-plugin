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

if (!defined('ASPIMGCONV_DIRPATH')) {
    define('ASPIMGCONV_DIRPATH', plugin_dir_path(__FILE__));
}

add_action('plugins_loaded', array('WP_AsposeImagingConverter', 'get_instance'));

if (!class_exists('WP_AsposeImagingConverter')) {

    class WP_AsposeImagingConverter
    {
        private static $instance = null;
        private $admin;
        private $dir;

        public static function get_instance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            spl_autoload_register(array($this, 'autoload'));
            $this->init();
        }

        public function autoload($class)
        {
            // Project-specific namespace prefix.
            $prefix = 'AsposeImagingConverter\\';

            // Does the class use the namespace prefix?
            $len = strlen($prefix);
            if (0 !== strncmp($prefix, $class, $len)) {
                // No, move to the next registered autoloader.
                return;
            }

            // Get the relative class name.
            $relative_class = substr($class, $len);

            $path = explode('\\', strtolower(str_replace('_', '-', $relative_class)));
            $file = array_pop($path);
            $file = ASPIMGCONV_DIRPATH . implode('/', $path) . '/class-' . $file . '.php';

            // If the file exists, require it.
            if (file_exists($file)) {
                require $file;
            }
        }

        private function init()
        {
            $this->admin = new AsposeImagingConverter\Core\AdminMenu();
            $this->admin->init();

            $this->initDir();
        }

        private function initDir()
        {
            // We only run in admin.
            if (!is_admin()) {
                return;
            }

            $this->dir = new \AsposeImagingConverter\Core\Dir();

            add_action('wp_ajax_smush_get_directory_listX', array($this->dir, 'directory_list'));
            add_action('wp_ajax_image_listX', array($this->dir, 'image_list'));
        }
    }
}
