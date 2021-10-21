<?php

namespace AsposeImagingConverter\Core;

class Installer
{
    public static function AsposeImagingConverter_activated()
    {
        if ( ! class_exists( '\\AsposeImagingConverter\\Core\\Dir' ) ) {
			require_once __DIR__ . '/class-dir.php';
		}

        $dir = new Dir();
        $dir->create_table();

    }
}