<?php
require_once(dirname(__FILE__) . "/../shell/SyncCurlController.php");


$curlController = new SyncCurlController();
$curlController->deleteCampaign();
$curlController->getPaSkuMaterial();