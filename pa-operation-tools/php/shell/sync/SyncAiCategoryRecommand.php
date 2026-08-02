<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");

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
        $this->log->log($message);
    }

    public function main(){
        $this->log("start 执行SyncAiCategoryRecommand脚本");
        $curlService = (new CurlService())->test();
        $curlService->aiCategoryApi();

        $fileFitContent = (new ExcelUtils())->getXlsxData(PA_EXPORT_PATH . "/AMZ_DE市场路径.xlsx");

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

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
$curlController = new SyncAiCategoryRecommand();
$curlController->main();
}