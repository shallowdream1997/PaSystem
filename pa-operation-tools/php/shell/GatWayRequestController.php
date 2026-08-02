<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");

class GatWayRequestController
{
    private $log;
    private $curlService;

    private $module = "platform-wms-application";

    public function __construct($port = 'test')
    {
        $this->log = new MyLogger("get_way_log");
        $this->curlService = new CurlService();
        $this->curlService->$port();

        $this->curlService->gateway();
    }

    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
        }

        return $this;
    }


    /**
     * sku留样 - 打标识
     */
    public function getReceiveSampleExpectPage()
    {
        $skuIdList = [
            "a24051400ux0093",
        ];
        $requestUtils = new RequestUtils("pro");

        $resp = DataUtils::getNewResultData($this->getModule('wms')->curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
            "skuIdIn" => $skuIdList,
            "vertical" => "PA",
            "category" => "dataTeam",
            "pageSize" => 500,
            "pageNum" => 1,
        ]));
        print_r($resp);
        $hasSampleSkuIdList = [];
        if (DataUtils::checkArrFilesIsExist($resp, 'list')) {
            $hasSampleSkuIdList = array_column($resp['list'], 'skuId');
            $this->log->log("部分sku：" . implode(",", $hasSampleSkuIdList) . " 均已经留样，过滤....");
        }
        $skuIdList = array_diff($skuIdList, $hasSampleSkuIdList);

        $needSampleSkuIdList = [];
        foreach ($skuIdList as $skuId) {
            $needSampleSkuIdList[] = [
                "category" => "dataTeam",
                "createBy" => "zhouangang",
                "remark" => "",
                "skuId" => $skuId,
                "vertical" => "PA"
            ];
        }
        if (count($needSampleSkuIdList) > 0) {
            $createResp = DataUtils::getNewResultData($this->getModule('wms')->curlService->getWayPost($this->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
            if ($createResp && $createResp['value']) {
                $this->log->log("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
            } else {
                $this->log->log("留样打标失败");
            }
        } else {
            $this->log->log("预计留样的数据都已存在，无需留样");
        }

    }

}

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
$getWay = new GatWayRequestController('pro');
$getWay->getReceiveSampleExpectPage();
}