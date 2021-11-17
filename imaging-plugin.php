<?php
/*
Plugin Name: Aspose.Imaging Converter
Plugin URI:
Description: Aspose.Imaging Converter Description
Version: 1.0
Author: aspose.cloud Marketplace
Author URI: https://www.aspose.cloud/
*/

define("ASPIMGCONV_PLUGIN_FILE", __FILE__);
require_once __DIR__ . "/vendor/autoload.php";

if (!defined('ASPIMGCONV_URL')) {
    define('ASPIMGCONV_URL', plugin_dir_url(__FILE__));
}

if (!defined('ASPIMGCONV_DIRPATH')) {
    define('ASPIMGCONV_DIRPATH', plugin_dir_path(__FILE__));
}

require_once ASPIMGCONV_DIRPATH . 'core/class-installer.php';
register_activation_hook(__FILE__, array('AsposeImagingConverter\\Core\\Installer', 'AsposeImagingConverter_activated'));

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

            $this->dir = new \AsposeImagingConverter\Core\Dir();
            $this->dir->init();
        }

        public function dir()
        {
            return $this->dir;
        }
    }
}

register_deactivation_hook(ASPIMGCONV_PLUGIN_FILE, function () {
    update_option("aspose-cloud-app-sid", null);
    update_option("aspose-cloud-app-key", null);
});

\AsposeImagingActivation\ActivationNotice::register();

if (PHP_MAJOR_VERSION <= 7) {
    \AsposeImagingActivation\Activation7::register();
}
