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
        $this->log->log($string);
    }
}

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
$p = new OptionConfigController();
}
