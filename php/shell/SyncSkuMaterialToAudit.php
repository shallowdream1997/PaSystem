<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class SyncCurlController
 */
class SyncSkuMaterialToAudit
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;

    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("pa_biz_application");

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

    public function syncSkuMaterialToAudit(){
        $this->log("start 执行syncSkuMaterialToAudit脚本");
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');

        $batchNameList = [];
        $pageNum = 1;
        do{
            $resp1 = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/material/changed_doc/v1/page", [
                "pageNum" => $pageNum,
                "pageSize" => 50,
                "applyStatus" => 30
            ]));
            if ($resp1 && count($resp1['list']) > 0){
                foreach ($resp1['list'] as $info){
//                    if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
//                        $batchNameList[] = $info['docNumber'];
//                    }
                    $batchNameList[] = $info['docNumber'];
                }
            }else{
                break;
            }
            $pageNum++;
        }while(true);

        if (count($batchNameList) > 0) {
            $this->log("一共：".count($batchNameList)."个单据翻译失败，");
            $this->log(json_encode($batchNameList,JSON_UNESCAPED_UNICODE));
            foreach ($batchNameList as $item){
                $postParams = [
                    "docNumbers" => [$item],
                    "operatorName" => "P3-fixTranslationFail"
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/material/changed_doc/v1/syncSkuMaterialToAudit", $postParams));

                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
            }
        }
        $this->log("end 执行syncSkuMaterialToAudit脚本");

    }
}

$curlController = new SyncSkuMaterialToAudit();
$curlController->syncSkuMaterialToAudit();