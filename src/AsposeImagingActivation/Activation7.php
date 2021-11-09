<?php

namespace AsposeImagingActivation;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class Activation7
{

    public static function register()
    {
        if (array_key_exists("token", $_REQUEST) && get_option("aspose-cloud-activation-secret")) {
            $i = new Activation7();
            add_action("init", array($i, "callback"));
        }
    }

    public function getToken()
    {
        if ($this->token !== null) {
            return $this->token;
        }

        try {
            $this->token = (new Parser())->parse($_REQUEST["token"]);
        } catch (\Exception $x) {
            return null;
        }

        if (!($this->token->hasClaim("iss")) || $this->token->getClaim("iss") !== "https://activator.marketplace.aspose.cloud/") {
            return null;
        }

        $signer = new Sha256();
        $key = new Key(get_option("aspose-cloud-activation-secret"));
        if (!$this->token->verify($signer, $key)) {
            update_option("aspose-cloud-activation-secret", null);
            wp_die("Unable to verify token signature.");
        }

        return $this->token;
    }

    public function callback()
    {
        if (!($this->getToken())) {
            return;
        }

        if (!($this->getToken()->hasClaim("aspose-cloud-app-sid")) || !($this->getToken()->hasClaim("aspose-cloud-app-key"))) {
            wp_die("The token has some invalid data");
        }

        update_option("aspose-cloud-app-key", $this->getToken()->getClaim("aspose-cloud-app-key"));
        update_option("aspose-cloud-app-sid", $this->getToken()->getClaim("aspose-cloud-app-sid"));
        update_option("aspose-cloud-activation-secret", null);

        $location = admin_url("admin.php?page=aspose_imaging_converter");

        if (wp_redirect($location)) {
            exit;
        }
    }

    private $token = null;
}
