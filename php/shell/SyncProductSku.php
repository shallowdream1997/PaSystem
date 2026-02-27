<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 同步sku的
 */
class SyncProductSku
{
    private MyLogger $log;

    private RedisService $redis;

    /**
     * @var CurlService
     */
    public CurlService $fromCurlService;
    /**
     * @var CurlService
     */
    public CurlService $toCurlService;

    public function __construct()
    {
        $this->log = new MyLogger("pa_biz_application");

        $this->redis = new RedisService();

        $this->fromCurlService = (new CurlService())->pro();
        $this->toCurlService = (new CurlService())->test();

    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function commonFromQueryPage($module,$port,$condition){
        if (!isset($condition['limit'])){
            $condition['limit'] = 999;
        }
        return DataUtils::getPageList($this->fromCurlService->$port()->get("{$module}/queryPage",$condition));
    }

    public function commonToQueryPage($module,$port,$condition){
        if (!isset($condition['limit'])){
            $condition['limit'] = 999;
        }
        return DataUtils::getPageList($this->toCurlService->$port()->get("{$module}/queryPage",$condition));
    }

    public function commonToDeleteId($module,$port,$id){
        return DataUtils::getResultData($this->toCurlService->$port()->delete($module,$id));
    }
    public function commonToCreate($module,$port,$data){
        return DataUtils::getResultData($this->toCurlService->$port()->post($module,$data));
    }


    public function commonFromQueryDocPage($module,$port,$condition){
        if (!isset($condition['limit'])){
            $condition['limit'] = 999;
        }
        return DataUtils::getPageDocList($this->fromCurlService->$port()->get("{$module}/queryPage",$condition));
    }

    public function commonToQueryDocPage($module,$port,$condition){
        if (!isset($condition['limit'])){
            $condition['limit'] = 999;
        }
        return DataUtils::getPageDocList($this->toCurlService->$port()->get("{$module}/queryPage",$condition));
    }


    public function Sync3015($skuList){
        $this->log("start 执行SyncProductSku脚本");

//        $skuList = [
//            "a25010200ux0001"
//        ];

        //$fileFitContent = (new ExcelUtils())->getXlsxData("../export/syncSku.xlsx");

        if (!empty($skuList)){
            $skuList = explode(",",$skuList);
            $sync = [
                "s3015" => [
                    ["amazon_asins", "skuId"],
                    ["amazon-active-listings", "skuId"],
                    ["walmart-active-listing-news", "skuId"],
                    ["ebay-active-listings", "skuId"],
                    ["channel_sku_images", "skuId"],
                    ["channel-prices", "productId"],
                    ["product-skus", "productId"],
                    ["product_base_infos", "productId"],
                    ["pid-scu-maps", "productId"],
                    ["sku-images", "skuId"],
                    ["sku-sale-statuses", "skuId"],
                    ["sku-seller-configs", "skuId"],
                    ["sgu-sku-scu-maps", "skuScuId"],
                    ["sgu-sku-scu-maps", "sguId"],
                    ["sgu_sku_scu_channel_maps", "skuScuId"],
                    ["sku_prices", "skuId"],
                    ["scu-sku-maps", "skuIdListName"],
                    ["pa_sku_infos", "skuId"],
                    ["product_fba_bases", "skuId"],
                ],
            ];
            foreach ($skuList as $sku) {
                foreach ($sync as $port => $m){
                    foreach ($m as $item){
                        $condition = [
                            $item[1] => $sku
                        ];
                        $this->log("{$port} - {$item[0]}");
                        $fromData = $this->commonFromQueryPage($item[0],$port,$condition);
                        $this->log("返回结果：".json_encode($fromData,JSON_UNESCAPED_UNICODE));
                        if (count($fromData) > 0){
                            $toData = $this->commonToQueryPage($item[0],$port,$condition);
                            if (count($toData) > 0){
                                foreach ($toData as $toDatum){
                                    //物理删除
                                    $this->commonToDeleteId($item[0],$port,$toDatum['_id']);
                                }
                            }
                        }

                        if (count($fromData) > 0){
                            foreach ($fromData as $fromDatum) {
                                //直接创建
                                $this->commonToCreate($item[0],$port,$fromDatum);
                            }
                        }else{
                            $this->log("没有sku可同步");
                        }
                    }
                }
            }
        }else{
            $this->log("没有sku可同步");
        }

        $this->log("end 执行SyncProductSku脚本");
    }

    public function Sync3044($skuList){
        $this->log("start 执行SyncProductSku Sync3044脚本");

        if (!empty($skuList)){
            $skuList = explode(",",$skuList);
            $sync = [
                "s3044" => [
//                    ["fcu_sku_maps","fcuId"],
                    ["pa_ce_materials","skuIdList"],
                    ["pa_sku_materials","skuId"],
                ]
            ];
            foreach ($skuList as $sku) {
                foreach ($sync as $port => $m){
                    foreach ($m as $item){
                        $condition = [
                            $item[1] => $sku
                        ];
                        $this->log("{$port} - {$item[0]}");
                        $fromData = $this->commonFromQueryDocPage($item[0],$port,$condition);
                        $this->log("返回结果：".json_encode($fromData,JSON_UNESCAPED_UNICODE));
                        if (count($fromData) > 0){
                            $toData = $this->commonToQueryDocPage($item[0],$port,$condition);
                            if (count($toData) > 0){
                                foreach ($toData as $toDatum){
                                    //物理删除
                                    $this->commonToDeleteId($item[0],$port,$toDatum['_id']);
                                }
                            }
                        }

                        if (count($fromData) > 0){
                            foreach ($fromData as $fromDatum) {
                                //直接创建
                                $this->commonToCreate($item[0],$port,$fromDatum);
                            }
                        }else{
                            $this->log("没有sku可同步");
                        }
                    }
                }
            }
        }else{
            $this->log("没有sku可同步");
        }

        $this->log("end 执行SyncProductSku Sync3044脚本");
    }
}



$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$skuIdList = "";
if (isset($params['skuIdList']) && trim($params['skuIdList'] != '')) {
    $skuIdList = $params['skuIdList'];
}
$curlController = new SyncProductSku();
$curlController->Sync3015($skuIdList);
//$curlController->Sync3044($skuIdList);