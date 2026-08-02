<?php
require_once(dirname(__FILE__) . "/../requiredfile/requiredfile.php");

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 或 web 访问时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
$curlController = new SyncCurlController();
$curlController->getPaSkuMaterial();
//$curlController->deleteCampaign();
}