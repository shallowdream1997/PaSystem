<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");

class OptionConfigController
{
    private $log;
    private $requestUtils;

    public function __construct()
    {
        $this->log = new MyLogger("option_val_list");
        $this->requestUtils = new RequestUtils("test");
    }



    private function log(string $string = "")
    {
        $this->log->log2($string);
    }
}

$p = new OptionConfigController();
