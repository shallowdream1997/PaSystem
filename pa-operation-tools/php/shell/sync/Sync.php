<?php
require_once(dirname(__FILE__) ."/../../../php/requiredfile/requiredfile.php");

class Sync
{
    private $log;
    private $requestUtils;

    public function __construct()
    {
        $this->log = new MyLogger("sync");
        $this->requestUtils = new RequestUtils("uat");
    }
    private function log(string $string = "")
    {
        $this->log->log($string);
    }



    /**
     * 同步生产环境option-val-list -> sit环境
     * @return $this
     */
    public function syncOptionValListInfoToTest()
    {
        $optionNameList = [
            "campaign_salesman_index"
        ];

        $proRequestUtils = new RequestUtils("pro");
        foreach ($optionNameList as $optionName){
            $proInfo = $proRequestUtils->getOptionValListByName($optionName);
            if (!empty($proInfo)){
                $testInfo = $this->requestUtils->getOptionValListByName($optionName);
                if (!empty($testInfo)){
                    //
                    $this->requestUtils->deleteOptionValListInfo($testInfo);
                    $this->log("delete test {$optionName}");
                    $this->requestUtils->createOptionValListInfo($proInfo);
                    $this->log("create test {$optionName}");
                }else{
                    $this->requestUtils->createOptionValListInfo($proInfo);
                    $this->log("create test {$optionName}");
                }
            }
        }
        return $this;
    }

    /**
     * 同步pa_product_brand_bilino_rule表 到test环境 (开发清单分配规则)
     * @return $this
     */
    public function syncPaProductBrandBiliNoRules()
    {
        $proCurlService = new CurlService();
        $pro = $proCurlService->pro();
        $proDataList = DataUtils::getPageList($pro->s3044()->get("pa_product_brand_bilino_rules/queryPage", ["limit" => 10000, "status" => 10]));
        if (count($proDataList['data']) == 0) {
            $this->log("没有数据可以同步");
            return $this;
        }
        $testCurlService = new CurlService();
        $test = $testCurlService->test();
        foreach ($proDataList['data'] as $info) {
            $test->s3044()->post("pa_product_brand_bilino_rules", $info);
            $this->log("完成：{$info['cnCategory']}");
        }
        return $this;
    }



}

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
echo date("Y-m-d H:i:s",time());
}