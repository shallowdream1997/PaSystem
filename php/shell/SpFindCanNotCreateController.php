<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpFindCanNotCreateController
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
            $contentList = $excelUtils->getXlsxData($fileName . "查询已上架fba未开广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_sellers/queryPage", [
                "company_in" => "CR201706060001",
                "columns" => "sellerId,isIndependenceSeller",
                "limit" => 500
            ]));
            $sellerIdMaps = [];
            if (count($list) > 0) {
                foreach ($list as $info){
                    if ($info['isIndependenceSeller'] == 1){
                        $sellerIdMaps[$info['sellerId']] = 1;
                    }
                }
            }

            $exportDataList = [];
            foreach ($contentList as $info){
                if (isset($sellerIdMaps[$info['sellerid']])){
                    $this->log("{$info['sellerid']} {$info['skuid']}");

                    $exportList = [
                        "skuid" => $info['skuid'],
                        "channel" => $info['channel'],
                        "sellerid" => $info['sellerid'],
                        "campaignName" => "",
                        "isCampaignSp" => "",
                        "adGroupName" => "",
                        "isAdGroupSp" => "",
                        "productName" => "",
                        "isProductSp" => ""
                    ];

                    $productIdInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("pid-scu-maps/queryPage",[
                        "scuId" => $info['skuid'],
                        "channel" => $info['channel'],
                        "scuIdType" => "fba",
                        "scuIdStyle" => "systemWithSelling"
                    ]));
                    $nonFbaInfo = [];
                    if ($productIdInfo){
                        $pInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("product-skus/queryPage",[
                            "productId" => $productIdInfo['productId'],
                            "limit" => 1,
                        ]));
                        if ($pInfo['verticalId'] != "CR201706060001"){
                            $this->log("{$info['skuid']} 不是PA的");
                            continue;
                        }
                        $nonFbaInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("pid-scu-maps/queryPage",[
                            "productId" => $productIdInfo['productId'],
                            "channel" => $info['channel'],
                            "scuIdType" => "nonFba",
                            "scuIdStyle" => "sellerSku"
                        ]));
                    }

                    $fbaInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("pid-scu-maps/queryPage",[
                        "productId" => $info['skuid'],
                        "channel" => $info['channel'],
                        "scuIdType" => "fba",
                        "scuIdStyle" => "sellerSku"
                    ]));


                    $productName = "";
                    if ($fbaInfo){
                        $productName = $fbaInfo['scuId'];
                        $exportList['productName'] = $productName;
                    }
                    $adGroupName = "";
                    if ($nonFbaInfo){
                        $adGroupName = $nonFbaInfo['scuId'];
                        $exportList['adGroupName'] = $adGroupName;
                    }

                    $autoCampaignId = "";
                    if ($adGroupName) {
                        $adGroupList = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_adgroups/queryPage", [
                            "adGroupName" => $adGroupName,
                            "channel" => $info['sellerid'],
                            "limit" => 100
                        ]));
                        $campaignIds = array_unique(array_column($adGroupList, "campaignId"));
                        $campaignList = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_campaigns/queryPage", [
                            "campaignId_in" => implode(",", $campaignIds),
                            "channel" => $info['sellerid'],
                            "limit" => 100
                        ]));
                        foreach ($campaignList as $campaign) {
                            if ($campaign['targetingType'] == "auto") {
                                $autoCampaignId = $campaign['campaignId'];
                                $exportList['campaignName'] = $campaign['campaignName'];
                                $exportList['isCampaignSp'] = "有";
                                $exportList['isAdGroupSp'] = "有";
                                $this->log("有auto campaign和adgroup：{$info['skuid']}");
                                break;
                            }
                        }
                    }else{
                        $this->log("找不到adGroupName");
                    }

                    if (!$autoCampaignId){
                        //没有auto的campaignId
                        $this->log("没有auto campaign和adgroup：{$info['skuid']}");
                        $exportList['isCampaignSp'] = "无";
                        $exportList['isAdGroupSp'] = "无";
                        $exportList['isProductSp'] = "无";
                    }

                    if ($productName && $autoCampaignId){
                        $productList = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_products/queryPage", [
                            "campaignId" => $autoCampaignId,
                            "channel" => $info['sellerid'],
                            "sku" => $productName,
                            "limit" => 100
                        ]));
                        if (count($productList) == 0){
                            $this->log("没有productName：{$info['skuid']}");
                            $exportList['isProductSp'] = "无";
                        }else{
                            $this->log("有productName：{$info['skuid']}");
                            $exportList['isProductSp'] = "有";
                        }
                    }

                    $exportDataList[] = $exportList;
                }else{
                    $this->log("非PA账号且可能是老账号：{$info['sellerid']}");
                }

            }

            if (count($exportDataList)>0){
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "skuid",
                    "channel",
                    "sellerid",
                    "campaignName",
                    "isCampaignSp",
                    "adGroupName",
                    "isAdGroupSp",
                    "productName",
                    "isProductSp",
                ], $exportDataList, "20250605PA_fba上架自动投放auto广告情况_" . date("YmdHis") . ".xlsx");
            }else{
                $this->log("没有导出数据");
            }
        }

    }


}

$con = new SpFindCanNotCreateController();
$con->enabled();