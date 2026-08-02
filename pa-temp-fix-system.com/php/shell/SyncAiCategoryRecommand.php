<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 分类映射API
 * Class SyncAiCategoryRecommand
 */
class SyncAiCategoryRecommand
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

    public function main(){
        $this->log("start 执行SyncAiCategoryRecommand脚本");
        $curlService = (new CurlService())->test();
        $curlService->aiCategoryApi();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/AMZ_DE市场路径.xlsx");

        if (isset($fileFitContent['中文分类']) && !empty($fileFitContent["中文分类"])){
            $cnCategoryList = $fileFitContent['中文分类'];
            foreach ($cnCategoryList as $info){
                //$info['cn_category'];
            }


        }

        if (sizeof($fileFitContent) > 0) {

            foreach ($fileFitContent as $info){

            }

        }

        $resp = $curlService->post("recommend", [
            "source_categories" => [
                [
                    "category" => "Electronics",
                    "productType" => ""
                ],
                [
                    "category" => "Home & Kitchen > Bath > Bath Rugs",
                    "productType" => "Home>Rug"
                ]
            ],
            "sep" => " > ",
            "channels" => [
                "test_channel"
            ],
            "top_k" => 2
        ]);
        if ($resp){

        }



        $this->log("end 执行SyncAiCategoryRecommand脚本");

    }
}

$curlController = new SyncAiCategoryRecommand();
$curlController->main();