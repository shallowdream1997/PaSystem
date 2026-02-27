<?php
require_once(dirname(__FILE__) . "/ProductSkuController.php");


$productSkuController = new ProductSkuController("test");
//$productSkuController->updatePaProductAndDetail("UpdatePaProduct.xlsx");
//$productSkuController->fixFcuSkuMapRepeatChannel();
$s = $productSkuController->getPmoData("PMO开发人员_" . date("YmdHis") . ".xlsx",["DPMO241220003","DPMO250102002"],"pro");

echo $s;