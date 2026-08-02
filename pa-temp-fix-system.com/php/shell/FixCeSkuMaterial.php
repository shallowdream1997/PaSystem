<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 修复sku资呈的fitment和核心词
 * Class SyncAiCategoryRecommand
 */
class FixCeSkuMaterial
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
        $this->log("start 执行FixCeSkuMaterial脚本");
        $curlService = (new CurlService())->pro();
        $curlService->s3044();

        $ceBillNo = "CE202504140132";
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/{$ceBillNo}.xlsx");

        $keywords = [];
        $fitment = [];
        $asinList = [];
        if (isset($fileFitContent['核心词']) && !empty($fileFitContent["核心词"])){
            foreach ($fileFitContent['核心词'] as $info) {
                $keywords[$info['skuId']][] = $info['核心词'];
            }
        }
        if (isset($fileFitContent['热销车型']) && !empty($fileFitContent["热销车型"])){
            foreach ($fileFitContent['热销车型'] as $info){
                $fitment[$info['skuId']][] = [
                    "make" => $info['make'],
                    "model" => $info['model']
                ];
            }
        }
        if (isset($fileFitContent['CP asin']) && !empty($fileFitContent["CP asin"])){
            foreach ($fileFitContent['CP asin'] as $info) {
                $asinList[$info['skuId']][] = $info['asin'];
            }
        }
        $list = DataUtils::getPageDocList($curlService->get("pa_sku_materials/queryPage",[
            "ceBillNo" => $ceBillNo,
            "limit" => 500
        ]));
        foreach ($list as &$item){
            if (isset($keywords[$item['skuId']]) && !empty($keywords[$item['skuId']])){
                $item['keywords'] = $keywords[$item['skuId']];
            }
            if (isset($fitment[$item['skuId']]) && !empty($fitment[$item['skuId']])){
                $item['fitment'] = $fitment[$item['skuId']];
            }
            if (isset($asinList[$item['skuId']]) && !empty($asinList[$item['skuId']])){
                $item['cpAsin'] = $asinList[$item['skuId']];
            }
            $item['modifiedBy'] = "System(zhouangang)";
            $curlService->put("pa_sku_materials/{$item['_id']}", $item);
        }

        $this->log("end 执行FixCeSkuMaterial脚本");
    }
}

$curlController = new FixCeSkuMaterial();
$curlController->main();