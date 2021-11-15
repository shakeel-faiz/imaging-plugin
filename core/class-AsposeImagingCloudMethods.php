<?php

namespace AsposeImagingConverter\Core;

use \Aspose\Imaging\Configuration;
use \Aspose\Imaging\ImagingApi;
use \Aspose\Imaging\Model\Requests\CreateConvertedImageRequest;

class AsposeImagingCloudMethods
{
    public static function Process($path)
    {
        $data["success"] = false;

        $clientId = get_option("aspose-cloud-app-sid");
        $clientSecret = get_option("aspose-cloud-app-key");

        $config = new Configuration();
        $config->setBaseUrl($config->getBaseUrl());
        $config->setClientId($clientId);
        $config->setClientSecret($clientSecret);

        try {
            $api = new ImagingApi($config);
        } catch (\Exception $ex) {
            $data["errorMsg"] = $ex->getMessage();
            return $data;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $format = $ext;

        $sizeBefore = filesize($path);
        $imageData = file_get_contents($path);

        try {
            $req = new CreateConvertedImageRequest($imageData, $format);
            $resp = $api->createConvertedImage($req);
        } catch (\Exception $ex) {
            $data["errorMsg"] = $ex->getMessage();
            return $data;
        }

        $data["success"] = true;
        $data["sizeBefore"] = $sizeBefore;
        $sizeAfter = $resp->getSize();
        $cont = $resp->getContents();

        if ($sizeAfter < $sizeBefore) {
            $tempfile = $path . '.tmp';

            // Add the file as tmp.
            file_put_contents($tempfile, $cont);

            // Replace the file.
            $success = @rename($tempfile, $path);

            // If tempfile still exists, unlink it.
            if (file_exists($tempfile)) {
                @unlink($tempfile);
            }

            // If file renaming failed.
            if (!$success) {
                @copy($tempfile, $path);
                @unlink($tempfile);
            }

            $data["sizeAfter"] = $sizeAfter;
        } else {
            $data["sizeAfter"] = $sizeBefore;
        }

        return $data;
    }
}
