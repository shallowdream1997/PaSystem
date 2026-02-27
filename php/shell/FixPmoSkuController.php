<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 *
 * Class FixPmoSkuController
 */
class FixPmoSkuController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private $module = "platform-wms-application";

    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;

        $this->redis = new RedisService();
    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function updatePmoSkuIdInfoTitle(){
        $contentList = (new ExcelUtils())->getXlsxData("../export/修数据.xlsx");
        if ($contentList){
            $skuIdList = array_unique(array_column($contentList,"skuId"));
            $curlSsl = (new CurlService())->pro();
            $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $skuIdList,
                "attrCodeList" => [
                    "custom-skuInfo-skuId",
                    "custom-skuInfo-outsideTitle",
                ]
            ]));
            if ($getKeyResp) {
                $skuIdTitleMap = [];
                foreach ($getKeyResp as $item){
                    $skuIdTitleMap[$item['custom-skuInfo-skuId']] = $item['custom-skuInfo-outsideTitle'];
                }


                //修改productId的标题
                $this->fixCnTitle($skuIdList,$skuIdTitleMap);

                //打印pmo skuId_info的sql修改标题的sql语句
                $ceBillNo = "";
                $this->printSql($skuIdList,$ceBillNo);

            }

        }

    }


    private function fixCnTitle($skuIdList,$skuIdTitleMap){
        $curlService = (new CurlService())->pro();
        $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
            "productId" => implode(",",$skuIdList),
            "limit" => count($skuIdList)
        ]));
        if ($productSkuList){
            $productIdMap = array_column($productSkuList,null,"productId");
        }

        foreach ($skuIdList as $sku){
            if (isset($productIdMap[$sku])){
                $productInfo = $productIdMap[$sku];

                $cnTitle = $productInfo['cnTitle'];
                if (isset($skuIdTitleMap[$productInfo['productId']]) && $skuIdTitleMap[$productInfo['productId']]){
                    $cnTitle = $skuIdTitleMap[$productInfo['productId']];
                }

                $productInfo['cnTitle'] = $cnTitle;

                $productInfo['userName'] = "system(zhouangang)";
                $productInfo['action'] = "运维修改资料";
                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function printSql($skuIdList, $ceBillNo)
    {
        $sqlList = [];
        foreach ($skuIdList as $sku) {
            $curlService = (new CurlService())->pro();
            $info = DataUtils::getQueryListInFirstDataV3($curlService->s3009()->get("market-analysis-reports/querySkuIdInfo", [
                "skuId" => $sku,
                "ceBillNo" => $ceBillNo,
                "limit" => 1
            ]));
            if (!$info) {
                $info = DataUtils::getQueryListInFirstDataV3($curlService->s3009()->get("market-analysis-reports/querySkuIdInfo", [
                    "skuId" => $sku,
                    "limit" => 1
                ]));
            }
            $outTitle = $info['productLineName'];
            if (isset($skuIdTitleMap[$info['skuId']]) && $skuIdTitleMap[$info['skuId']]) {
                $outTitle = $skuIdTitleMap[$info['skuId']];
            }
            $field = "productLineName";
            $sql = 'db.skuId_info.updateOne({_id:ObjectId(' . "'" . $info['_id'] . "'" . ')},{$set:{' . $field . ':"' . $outTitle . '"}});';
            $sqlList[] = $sql;
        }
        foreach ($sqlList as $sql) {
            $this->log($sql);
        }
    }
}

$curlController = new FixPmoSkuController();
$curlController->updatePmoSkuIdInfoTitle();