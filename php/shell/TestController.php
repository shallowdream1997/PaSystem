<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class TestController
 */
class TestController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private RedisService $redis;
    
    public function __construct()
    {
        $this->log = new MyLogger("Test/test");
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

    public function downloadPaSkuPhotoProgress()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
            "limit" => 10,
            "page" => 1
        ]));
        $list = [];
        foreach ($ss['data'] as $info) {
            $list[] = [
                "batchName" => $info['batchName'],
                "ceBillNo" => $info['ceBillNo'],
                "createCeBillNoOn" => $info['createCeBillNoOn'],
                "skuId" => $info['skuId'],
                "status" => $info['status'],
                "photoBy" => $info['photoBy'],
                "photoOn" => $info['photoOn'],
            ];
        }

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
        }

    }
}

$c = new TestController();
$c->downloadPaSkuPhotoProgress();