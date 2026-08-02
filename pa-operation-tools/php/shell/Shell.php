<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 或 web 访问时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
$productSkuController = new ProductSkuController("test");
//$productSkuController->updatePaProductAndDetail("UpdatePaProduct.xlsx");
//$productSkuController->fixFcuSkuMapRepeatChannel();
$s = $productSkuController->getPmoData("PMO开发人员_" . date("YmdHis") . ".xlsx",["DPMO241220003","DPMO250102002"],"pro");

echo $s;
}