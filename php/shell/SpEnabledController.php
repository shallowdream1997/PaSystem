<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpEnabledController
{
    private $log;
    /**
     * @var string[]
     */
    private array $typeMap;
    /**
     * @var string[]
     */
    private array $campaignTypeMap;
    /**
     * @var string[]
     */
    private array $actionMap;

    public function __construct()
    {
        $this->log = new MyLogger("sp");

        $this->typeMap = [
            "1" => "auto",
            "2" => "manual",
            "3" => "manual",
            "4" => "manual",
        ];
        $this->campaignTypeMap = [
            "1"=>"auto",
            "2"=>"keyword",
            "3"=>"asin",
            "4"=>"category",
        ];
        $this->actionMap = [
            "1" => "系统自动化投放",
            "2" => "补充投放",
            "3" => "自定义投放",
        ];
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function dingTalk(){
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【keyword广告写入投放完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 已经投放完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function enabled(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "sku清单明细.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $channel = "";
            $sellerId = "";
            foreach ($contentList as $info){
                if (isset($info['渠道']) && $info['渠道']){
                    $channel = $info['渠道'];
                }
                if (isset($info['账号']) && $info['账号']){
                    $sellerId = $info['账号'];
                }
                if (empty($channel) || empty($sellerId)){
                    $this->log("缺少账号数据");
                    continue;
                }

                $list = DataUtils::getPageList($curlService->pro()->s3015()->get("pid-scu-maps/queryPage", [
                    "productId" => $info['sku_id'],
                    "channel" => $channel,
                    "scuIdType" => "fba",
                    "scuIdStyle" => "systemWithSelling",
                    "limit" => 1
                ]));
                if (count($list) > 0){
                    $fba = $list[0];

                    $resp = DataUtils::getResultData($curlService->pro()->phphk()->post("amazonSpApi/paPlacementAmazonSp", [
                        "placementAction" => 1,
                        "placementType" => 1,
                        "targetingType" => "auto",
                        "campaignType" => "auto",
                        "channel" => $channel,
                        "sellerId" => $sellerId,
                        "scu" => $fba['scuId'],
                        "createdBy" => "system(zhouangang)",
                        "modifiedBy" => "system(zhouangang)",
//                        "debug" => true
                    ]));
                    if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                        $this->log(json_encode($resp['data'],JSON_UNESCAPED_UNICODE));
                        if (isset($resp['data']['messageType']) && $resp['data']['messageType'] == "success"){
                            $this->log("{$channel} {$sellerId} {$fba['scuId']}   ========> " . json_encode($resp['data']['success'],JSON_UNESCAPED_UNICODE));
                        }
                    }
                }else{
                    $this->log("{$info['skuId']} 没有fba上架账号");
                }

            }
            $this->dingTalk();
        }

    }


    public function enabledKeyword(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "马文锋keyword.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $resultList = [];
            foreach ($contentList as $info){
                if (!isset($info['channel']) || !$info['channel']){
                    $this->log("没有渠道");
                    continue;
                }
                if (!isset($info['seller_id']) || !$info['seller_id']){
                    $this->log("没有账号");
                    continue;
                }
                if (!isset($info['campaign_name']) || !$info['campaign_name']){
                    $this->log("没有campaignName");
                    continue;
                }
                if (!isset($info['adgroup_name']) || !$info['adgroup_name']){
                    $this->log("没有adgroupName");
                    continue;
                }
                if (!isset($info['budget']) || !$info['budget']){
                    $this->log("没有budget");
                    continue;
                }
                if (!isset($info['bid_strategy']) || !$info['bid_strategy']){
                    $this->log("没有bid策略");
                    continue;
                }
                if (!isset($info['bid']) || !$info['bid']){
                    $this->log("没有bid");
                    continue;
                }
                if (!isset($info['content_type']) || !$info['content_type']){
                    $this->log("没有content_type");
                    continue;
                }
                if (!isset($info['content']) || !$info['content']){
                    $this->log("没有content");
                    continue;
                }

                if (!isset($info['type']) || !$info['type']){
                    $this->log("没有type");
                    continue;
                }
                if (!isset($info['action']) || !$info['action']){
                    $this->log("没有action");
                    continue;
                }

                if (!isset($this->typeMap[$info['type']]) || !$this->typeMap[$info['type']]){
                    $this->log("没有targetingType类型");
                    continue;
                }
                if (!isset($this->campaignTypeMap[$info['type']]) || !$this->campaignTypeMap[$info['type']]){
                    $this->log("没有campaignType类型");
                    continue;
                }

                if ($info['action'] == 1 && $info['type'] == 1){
                    $this->action1type1($info,$info['action'],$info['type']);
                }

                $requestBody = [
                    "placementAction" => $info['action'],
                    "placementType" => $info['type'],
                    "targetingType" => $this->typeMap[$info['type']],
                    "campaignType" => $this->campaignTypeMap[$info['type']],
                    "channel" => $info['channel'],
                    "sellerId" => $info['seller_id'],
                    "campaignId" => null,
                    "campaignName" => $info['campaign_name'],
                    "dailyBudget" => $info['budget'],
                    "bidStrategy" => $info['bid_strategy'],
                    "adGroupId" => null,
                    "adGroupName" => $info['adgroup_name'],
                    "defaultBid" => $info['bid'],
                    "contentType" => $info['content_type'],
                    "content" => $info['content'],
                    "contentBid" => $info['content_bid'],
                    "productName" => null,
                    "createdBy" => "system(zhouangang)",
                    "modifiedBy" => "system(zhouangang)",
//                        "debug" => true
                ];
                $resp = DataUtils::getResultData($curlService->pro()->phphk()->post("amazonSpApi/paPlacementAmazonSp", $requestBody));
                if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                    if (isset($resp['messageType']) && $resp['messageType'] == "success"){
                        $this->log("{$info['channel']} {$info['seller_id']} {$info['campaign_name']}   ========> " . json_encode($resp['data']['success'],JSON_UNESCAPED_UNICODE));
                        $resultList[] = [
                            "campaign_name" => $info['campaign_name'],
                            "campaign_id" => $resp['data']['success']['campaignId']['campaignId'],
                            "adgroup_name" => $info['adgroup_name'],
                            "adgroup_id" => $resp['data']['success']['adGroupId']['adGroupId'],
                        ];
                    }else{
                        $this->log("{$info['campaign_name']} = 失败".json_encode($resp['data'],JSON_UNESCAPED_UNICODE));
                    }
                }


            }


            if ($resultList){
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["campaign_name","campaign_id","adgroup_name","adgroup_id"],$resultList,"马文锋keyword投放结果_".date("YmdHis").".xlsx");
            }
        }

    }

    public function action1type1($info,$action,$type){
        $curlService = new CurlService();
        $requestBody = [
            "placementAction" => $action,
            "placementType" => $type,
            "targetingType" => $this->typeMap[$type] ?? null,
            "campaignType" => $this->campaignTypeMap[$type] ?? null,
            "channel" => $info['channel'] ?? null,
            "sellerId" => $info['seller_id'] ?? null,
            "campaignName" => $info['campaign_name'] ?? null,
            "dailyBudget" => $info['budget'] ?? null,
            "bidStrategy" => $info['bid_strategy'] ?? null,
            "adGroupName" => $info['adgroup_name'] ?? null,
            "defaultBid" => $info['bid'] ?? null,
            "contentType" => $info['content_type'] ?? null,
            "content" => $info['content'] ?? null,
            "contentBid" => $info['content_bid'] ?? null,
            "productName" => $info['productName'] ?? null,
            "createdBy" => "system(zhouangang)",
            "modifiedBy" => "system(zhouangang)",
        ];
        $resp = DataUtils::getResultData($curlService->pro()->phphk()->post("amazonSpApi/paPlacementAmazonSp", $requestBody));
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            if (isset($resp['messageType']) && $resp['messageType'] == "success"){

            }else{
                $this->log("{$info['campaign_name']} = 失败".json_encode($resp['data'],JSON_UNESCAPED_UNICODE));
            }
        }

    }


}

$con = new SpEnabledController();
//$con->enabled();
$con->enabledKeyword();
//$con->buildFixSql();