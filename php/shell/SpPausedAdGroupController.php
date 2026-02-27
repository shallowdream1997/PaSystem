<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpPausedAdGroupController
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

    private $curlService;
    private $messages = "system(资呈修复)";

    private $redis;

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

        $this->curlService = (new CurlService())->pro();
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function dingTalk($title){
        $proCurlService = new CurlService();
        $ali = $proCurlService->pro()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => $title,
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 已经执行完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function getAmazonSpRuleRegexSystemCampaignRegex($channel,$sellerId,$spType)
    {
        $key = "{$channel}_{$sellerId}";
        $pipeRuleStr = $this->redis->hGet("amazonRuleSpSellerPipe",$key);
        if (!$pipeRuleStr){
            $info = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_sellers/queryPage",[
                "company_in" => "CR201706060001",
                "channel" => $channel,
                "sellerId" => $sellerId,
                "limit" => 1
            ]));
            if ($info){
                $data = [];
                foreach($info['bindRule'] as $item){
                    if ($item['spType'] == $spType && $item['status'] == 1 && $item['ruleTypeAndId']){
                        foreach ($item['ruleTypeAndId'] as $dIt){
                            if ($dIt['ruleType'] == "campaignRuleBySystem"){
                                $rule = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_rule_configs/queryPage",[
                                    "ruleId" => $dIt['ruleId'],
                                ]));
                                if ($rule){
                                    $data = [
                                        "ruleRegex" => $rule['ruleRegex'],
                                        "pipe" => "",
                                    ];
                                    foreach($rule['ruleFieldName'] as $dddItem){
                                        if ($dddItem['fieldType'] == "pipe"){
                                            $data['pipe'] = $dddItem['field'];
                                            break;
                                        }
                                    }
                                }
                                break 2;
                            }
                        }
                    }
                }
                if ($data){
                    $this->redis->hSet("amazonRuleSpSellerPipe",$key,json_encode($data,JSON_UNESCAPED_UNICODE));
                }
                return $data;
            }else{
                return [];
            }
        }else{
            return json_decode($pipeRuleStr,true);
        }
    }

    public function pausedAdGroup(){
        $excelUtils = new ExcelUtils();

        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "paused_adgroup_1.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            foreach ($contentList as $content){
                $sellerId = $content["账号"];
                $channelMongo = $sellerId;
                if ($sellerId == "amazon"){
                    $channelMongo = "amazon_us";
                }

                $campaignName = $content["campaign"];
                $adgroupName = $content["adgroup"];

                if (!$campaignName){
                    continue;
                }
                if (!$adgroupName){
                    continue;
                }
                $campaignNameInfo = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_campaigns/queryPage", [
                    "campaignName" => $campaignName,
                    "channel" => $channelMongo,
                    "limit" => 1
                ]));

                if ($campaignNameInfo){

                    $adGroupList = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage", [
                        "campaignId" => $campaignNameInfo['campaignId'],
                        "adGroupName" => $adgroupName,
                        "limit" => 100
                    ]));
                    if ($adGroupList){
                        foreach ($adGroupList as $adGroupInfo){
                            $this->log("updateAdGroup：{$channelMongo} - {$campaignName} - {$adgroupName} - {$adGroupInfo['state']} - {$adGroupInfo['adGroupId']} - {$adGroupInfo['defaultBid']}");
                            //暂停adGroup 后台广告
                            $pausedData = [
                                [
                                    "campaignId" => $adGroupInfo['campaignId'],
                                    "adGroupId" => $adGroupInfo['adGroupId'],
                                    "state" => "paused"
                                ]
                            ];
                            $resp = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/adGroups/putAdGroups/{$sellerId}", $pausedData));
                            if ($resp && isset($resp['data']) && count($resp['data']) > 0){

                                //暂停adGroup poms系统
                                $resp1 = $this->curlService->s3023()->post("amazon_sp_adgroups/updateAdGroups", [
                                    "id" => $adGroupInfo['_id'],
                                    "isPassNotification" => "false",
                                    "from" => "system(资呈暂停广告)",
                                    "updateParams" => [
                                        "state" => "paused",
                                        "defaultBid" => $adGroupInfo['defaultBid'],
                                        "modifiedBy" => "system(资呈暂停广告)",
                                        "status" => "2"
                                    ],
                                ]);
                                if ($resp1['result'] && isset($resp1['result']['adgroup']) && count($resp1['result']['adgroup']) > 0) {
                                    $this->log("updateAdGroup：成功：{$resp1['result']['adgroup']['channel']} - {$resp1['result']['adgroup']['adGroupName']} - {$resp1['result']['adgroup']['state']} - {$resp1['result']['adgroup']['adGroupId']} - {$resp1['result']['adgroup']['defaultBid']}");
                                }else{
                                    $this->log("更新node失败了");
                                }


                            }else{
                                $this->log("更新adGroup失败了");
                            }
                        }
                    }


                }



            }


            $this->dingTalk("【暂停广告执行提醒】");
        }

    }


    /**
     * 保留原adGroup广告组，补充投放asin广告
     * @return void
     */
    public function supplementAdGroupWithAsinAds()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "supplement_adgroup_with_asin_ads_v1.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $content) {
                $skuMaterialInfo = $this->getPaSkuMaterials($content['sku']);

                if (count($skuMaterialInfo['cpAsin']) == 0){
                    $this->log("{$content['sku']} - 资呈没有asin信息");
                    continue;
                }

                $campaignInfo = $this->getMongoCampaignInfo($content['账号'],$content['campaign']);
                if (!$campaignInfo || !$campaignInfo['campaignId']){
                    $this->log("{$content['campaign']} - campaign不存在");
                    continue;
                }

                $adGroupInfo = $this->getMongoAdGroupInfo($content['账号'],$campaignInfo['campaignId'],$content['adgroup']);
                if (!$adGroupInfo || !$adGroupInfo['adGroupId']){
                    continue;
                }

                $targetAsinMap = $this->listTargetAsin($content['账号'],$campaignInfo['campaignId'],$adGroupInfo['adGroupId']);

                $mongoTargetMap = $this->getMongoTargetAsin($content['账号'],$campaignInfo['campaignId'],$adGroupInfo['adGroupId']);
                $asins = [];
                foreach ($skuMaterialInfo['cpAsin'] as $asin){
                    if (isset($targetAsinMap[$asin]) && $targetAsinMap[$asin]){
                        if (!isset($mongoTargetMap[$asin])){
                            $this->mongoCreateTargetAsin($content['账号'],$campaignInfo['campaignId'],$adGroupInfo['adGroupId'],$asin,$targetAsinMap[$asin]);
                        }else{
                            if ($mongoTargetMap[$asin] && $mongoTargetMap[$asin]['targetId']){
                                $this->log("已存在，无需更新");
                                continue;
                            }
                            $this->log("{$content['sku']} - {$asin} - 资呈asin广告已经存在");
                            $this->mongoUpdateTarget($mongoTargetMap[$asin]['_id'],$targetAsinMap[$asin]['targetId'],$targetAsinMap[$asin]['bid']);
                        }
                        continue;
                    }
                    $asins[] = $asin;
                }

                $createTargetList = $this->buildCreateTargetAsinList($campaignInfo['campaignId'],$adGroupInfo['adGroupId'],$adGroupInfo['defaultBid'],$asins);
                if (count($createTargetList) > 0){
                    $targetIds = $this->enabledAsinSp($createTargetList,$content['账号']);

                    if($targetIds){
                        $targetAsinMap = $this->listTargetAsin($content['账号'],$campaignInfo['campaignId'],$adGroupInfo['adGroupId'],implode(",",$targetIds));


                        foreach ($asins as $asin){
                            if (!isset($mongoTargetMap[$asin])){
                                $this->mongoCreateTargetAsin($content['账号'],$campaignInfo['campaignId'],$adGroupInfo['adGroupId'],$asin,$targetAsinMap[$asin]);
                            }else{
                                $this->mongoUpdateTarget($mongoTargetMap[$asin]['_id'],$targetAsinMap[$asin]['targetId'],$targetAsinMap[$asin]['bid']);
                            }
                        }

                    }
                }else{
                    $this->log("{$content['sku']} - 资呈不需要投放asin广告");
                }

            }
        }
        $this->dingTalk("【补充asin广告提醒】");
    }


    /**
     * 关闭原adGroup广告组，重开asin广告
     * @return void
     */
    public function closeAdGroupWithOpenNewAsinAds()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "close_adgroup_with_open_new_asin_ads.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $content) {
                $skuMaterialInfo = $this->getPaSkuMaterials($content['sku']);

                if (count($skuMaterialInfo['cpAsin']) == 0){
                    $this->log("{$content['sku']} - 资呈没有asin信息");
                    continue;
                }

                $manualAsinCampaignRule = $this->getAmazonSpRuleRegexSystemCampaignRegex($content['渠道'],$content['账号'],"manual asin");

                $pipe = "";
                if ($manualAsinCampaignRule && isset($manualAsinCampaignRule['pipe']) && $manualAsinCampaignRule['pipe']){
                    $pipe = $manualAsinCampaignRule['pipe'];
                }
                $fixCampaign = $content['campaign'].$pipe."修正重开Asin";

                $campaignInfo = $this->getMongoCampaignInfo($content['账号'],$fixCampaign);
                $oldCampaignInfo = $this->getMongoCampaignInfo($content['账号'],$content['campaign']);
                $campaignId = "";
                if (!$campaignInfo || !$campaignInfo['campaignId']){
                    $this->log("{$fixCampaign} - campaign不存在");

                    $listCampaign = $this->listCampaign($content['账号'],$fixCampaign);
                    if (!$listCampaign){
                        //创建amazon ，再创建
                        $campaignId = $this->enabledCampaign($content['账号'],$fixCampaign,$oldCampaignInfo['dailyBudget'],$oldCampaignInfo['bidStrategy']);

                        if (!$campaignInfo){
                            //有amazon就创建
                            $this->mongoCreateCampaignInfo($content['账号'],$fixCampaign,$campaignId,$oldCampaignInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateCampaignInfo($campaignInfo['_id'],$campaignId);
                        }

                    }else{

                        if (!$campaignInfo){
                            //有amazon就创建
                            $this->mongoCreateCampaignInfo($content['账号'],$fixCampaign,$listCampaign['campaignId'],$oldCampaignInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateCampaignInfo($campaignInfo['_id'],$listCampaign['campaignId']);
                        }
                        $campaignId = $listCampaign['campaignId'];
                    }


                }else{
                    $campaignId = $campaignInfo['campaignId'];
                }

                if (!$campaignId){
                    $this->log("campaign创建失败");
                    continue;
                }


                $adGroupInfo = $this->getMongoAdGroupInfo($content['账号'],$campaignId,$content['adgroup']);
                $oldAdGroupInfo = $this->getMongoAdGroupInfo($content['账号'],$oldCampaignInfo['campaignId'],$content['adgroup']);

                $adGroupId = "";
                if (!$adGroupInfo || !$adGroupInfo['adGroupId']){
                    $this->log("{$content['adgroup']} - adGroup不存在");

                    $listAdGroup = $this->listAdGroup($content['账号'],$campaignId,$content['adgroup']);
                    if (!$listAdGroup){
                        $adGroupId = $this->enabledAdGroup($content['账号'],$campaignId,$content['adgroup'],$oldAdGroupInfo['defaultBid']);

                        if (!$adGroupInfo){
                            //有amazon就创建
                            $this->mongoCreateAdGroup($content['账号'],$campaignId,$adGroupId,$content['adgroup'],$oldAdGroupInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateAdGroup($adGroupInfo['_id'],$adGroupId);
                        }

                    }else{
                        if (!$adGroupInfo){
                            //有amazon就创建
                            $this->mongoCreateAdGroup($content['账号'],$campaignId,$listAdGroup['adGroupId'],$content['adgroup'],$oldAdGroupInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateAdGroup($adGroupInfo['_id'],$listAdGroup['adGroupId']);
                        }
                        $adGroupId = $listAdGroup['adGroupId'];
                    }

                }else{
                    $adGroupId = $adGroupInfo['adGroupId'];
                }

                if (!$adGroupId){
                    $this->log("adGroup创建失败");
                    continue;
                }

                //还要创建product广告，否则asin广告会创建失败

                $sku = $this->fbaNo($content['渠道'],$content['fba']);
                $productInfo = $this->getMongoProductInfo($content['账号'],$campaignId,$adGroupId,$sku);
                $oldProductInfo = $this->getMongoProductInfo($content['账号'],$oldCampaignInfo['campaignId'],$oldAdGroupInfo['adGroupId'],$sku);
                $productId = "";
                if (!$productInfo || !$productInfo['adId']){
                    $this->log("{$sku} - product不存在");

                    $listProduct = $this->listProduct($content['账号'],$campaignId,$adGroupId,$sku);
                    if (!$listProduct){
                        $productId = $this->enabledProduct($content['账号'],$campaignId,$adGroupId,$sku);

                        if (!$productInfo){
                            //有amazon就创建
                            $this->mongoCreateProduct($content['账号'],$campaignId,$adGroupId,$sku,$productId,$oldProductInfo);
                        }else{
                            $this->mongoUpdateProduct($productInfo['_id'],$productId);
                        }

                    }else{
                        if (!$productInfo){
                            //有amazon就创建
                            $this->mongoCreateProduct($content['账号'],$campaignId,$adGroupId,$sku,$listProduct['adId'],$oldProductInfo);
                        }else{
                            $this->mongoUpdateProduct($productInfo['_id'],$listProduct['adId']);
                        }
                        $productId = $listProduct['adId'];
                    }
                }else{
                    $productId = $productInfo['adId'];
                }
                if (!$productId){
                    $this->log("product创建失败");
                    continue;
                }



                $targetAsinMap = $this->listTargetAsin($content['账号'],$campaignId,$adGroupId);

                $mongoTargetMap = $this->getMongoTargetAsin($content['账号'],$campaignId,$adGroupId);
                $asins = [];
                foreach ($skuMaterialInfo['cpAsin'] as $asin){
                    if (isset($targetAsinMap[$asin]) && $targetAsinMap[$asin]){
                        if (!isset($mongoTargetMap[$asin])){
                            $this->mongoCreateTargetAsin($content['账号'],$campaignId,$adGroupId,$asin,$targetAsinMap[$asin]);
                        }else{
                            if ($mongoTargetMap[$asin] && $mongoTargetMap[$asin]['targetId']){
                                $this->log("已存在，无需更新");
                                continue;
                            }
                            $this->mongoUpdateTarget($mongoTargetMap[$asin]['_id'],$targetAsinMap[$asin]['targetId'],$targetAsinMap[$asin]['bid']);
                        }
                        continue;
                    }
                    $asins[] = $asin;
                }

                $createTargetList = $this->buildCreateTargetAsinList($campaignId,$adGroupId,$oldAdGroupInfo['defaultBid'],$asins);
                if (count($createTargetList) > 0){
                    $targetIds = $this->enabledAsinSp($createTargetList,$content['账号']);

                    if($targetIds){
                        $targetAsinMap = $this->listTargetAsin($content['账号'],$campaignId,$adGroupId,implode(",",$targetIds));

                        foreach ($asins as $asin){
                            if (!isset($mongoTargetMap[$asin])){
                                $this->mongoCreateTargetAsin($content['账号'],$campaignId,$adGroupId,$asin,$targetAsinMap[$asin]);
                            }else{
                                $this->mongoUpdateTarget($mongoTargetMap[$asin]['_id'],$targetAsinMap[$asin]['targetId'],$targetAsinMap[$asin]['bid']);
                            }
                        }

                    }
                }else{
                    $this->log("{$content['sku']} - 资呈不需要投放asin广告");
                }


            }
        }
        $this->dingTalk("【重开asin广告提醒】");
    }


    /**
     * 关闭原adGroup广告组，重开kw广告
     * @return void
     */
    public function closeAdGroupWithOpenNewKeywordAds()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "close_adgroup_with_open_new_kw_ads.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $content) {
                $skuMaterialInfo = $this->getPaSkuMaterials($content['sku']);

                $kwList = $this->buildKeyword($skuMaterialInfo);
                if (count($kwList) == 0){
                    $this->log("{$content['sku']} - 没有配置广告关键词");
                    continue;
                }

                $manualAsinCampaignRule = $this->getAmazonSpRuleRegexSystemCampaignRegex($content['渠道'],$content['账号'],"manual");

                $pipe = "";
                if ($manualAsinCampaignRule && isset($manualAsinCampaignRule['pipe']) && $manualAsinCampaignRule['pipe']){
                    $pipe = $manualAsinCampaignRule['pipe'];
                }
//                $fixCampaign = $content['campaign'].$pipe."修正重开KW";
                $fixCampaign = $content['campaign'];

                $campaignInfo = $this->getMongoCampaignInfo($content['账号'],$fixCampaign);
                $oldCampaignInfo = $this->getMongoCampaignInfo($content['账号'],$content['campaign']);
                $campaignId = "";
                if (!$campaignInfo || !$campaignInfo['campaignId']){
                    $this->log("{$fixCampaign} - campaign不存在");

                    $listCampaign = $this->listCampaign($content['账号'],$fixCampaign);
                    if (!$listCampaign){
                        //创建amazon ，再创建
                        $campaignId = $this->enabledCampaign($content['账号'],$fixCampaign,$oldCampaignInfo['dailyBudget'],$oldCampaignInfo['bidStrategy']);

                        if (!$campaignInfo){
                            //有amazon就创建
                            $this->mongoCreateCampaignInfo($content['账号'],$fixCampaign,$campaignId,$oldCampaignInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateCampaignInfo($campaignInfo['_id'],$campaignId);
                        }

                    }else{

                        if (!$campaignInfo){
                            //有amazon就创建
                            $this->mongoCreateCampaignInfo($content['账号'],$fixCampaign,$listCampaign['campaignId'],$oldCampaignInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateCampaignInfo($campaignInfo['_id'],$listCampaign['campaignId']);
                        }
                        $campaignId = $listCampaign['campaignId'];
                    }


                }else{
                    $campaignId = $campaignInfo['campaignId'];
                }

                if (!$campaignId){
                    $this->log("campaign创建失败");
                    continue;
                }


                $adGroupInfo = $this->getMongoAdGroupInfo($content['账号'],$campaignId,$content['adgroup']);
                $oldAdGroupInfo = $this->getMongoAdGroupInfo($content['账号'],$oldCampaignInfo['campaignId'],$content['adgroup']);

                $adGroupId = "";
                if (!$adGroupInfo || !$adGroupInfo['adGroupId']){
                    $this->log("{$content['adgroup']} - adGroup不存在");

                    $listAdGroup = $this->listAdGroup($content['账号'],$campaignId,$content['adgroup']);
                    if (!$listAdGroup){
                        $adGroupId = $this->enabledAdGroup($content['账号'],$campaignId,$content['adgroup'],$oldAdGroupInfo['defaultBid']);

                        if (!$adGroupInfo){
                            //有amazon就创建
                            $this->mongoCreateAdGroup($content['账号'],$campaignId,$adGroupId,$content['adgroup'],$oldAdGroupInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateAdGroup($adGroupInfo['_id'],$adGroupId);
                        }

                    }else{
                        if (!$adGroupInfo){
                            //有amazon就创建
                            $this->mongoCreateAdGroup($content['账号'],$campaignId,$listAdGroup['adGroupId'],$content['adgroup'],$oldAdGroupInfo);
                        }else{
                            //存在mongo
                            $this->mongoUpdateAdGroup($adGroupInfo['_id'],$listAdGroup['adGroupId']);
                        }
                        $adGroupId = $listAdGroup['adGroupId'];
                    }

                }else{
                    $adGroupId = $adGroupInfo['adGroupId'];
                }

                if (!$adGroupId){
                    $this->log("adGroup创建失败");
                    continue;
                }

                //还要创建product广告，否则asin广告会创建失败

                $sku = $this->fbaNo($content['渠道'],$content['fba']);
                $productInfo = $this->getMongoProductInfo($content['账号'],$campaignId,$adGroupId,$sku);
                $oldProductInfo = $this->getMongoProductInfo($content['账号'],$oldCampaignInfo['campaignId'],$oldAdGroupInfo['adGroupId'],$sku);
                $productId = "";
                if (!$productInfo || !$productInfo['adId']){
                    $this->log("{$sku} - product不存在");

                    $listProduct = $this->listProduct($content['账号'],$campaignId,$adGroupId,$sku);
                    if (!$listProduct){
                        $productId = $this->enabledProduct($content['账号'],$campaignId,$adGroupId,$sku);

                        if (!$productInfo){
                            //有amazon就创建
                            $this->mongoCreateProduct($content['账号'],$campaignId,$adGroupId,$sku,$productId,$oldProductInfo);
                        }else{
                            $this->mongoUpdateProduct($productInfo['_id'],$productId);
                        }

                    }else{
                        if (!$productInfo){
                            //有amazon就创建
                            $this->mongoCreateProduct($content['账号'],$campaignId,$adGroupId,$sku,$listProduct['adId'],$oldProductInfo);
                        }else{
                            $this->mongoUpdateProduct($productInfo['_id'],$listProduct['adId']);
                        }
                        $productId = $listProduct['adId'];
                    }
                }else{
                    $productId = $productInfo['adId'];
                }
                if (!$productId){
                    $this->log("product创建失败");
                    continue;
                }



                $kwMap = $this->listKeyword($content['账号'],$campaignId,$adGroupId);

                $mongoKwMap = $this->getMongoKeywordInfo($content['账号'],$campaignId,$adGroupId);
                $cckwList = [];
                foreach ($kwList as $kw){
                    $key = "{$kw['contentType']}_{$kw['content']}";
                    if (isset($kwMap[$key]) && $kwMap[$key]){
                        if (!isset($mongoKwMap[$key])){
                            $this->mongoCreateKeyword($content['账号'],$campaignId,$adGroupId,$kw['content'],$kw['contentType'],$kwMap[$key]['keywordId'],$oldAdGroupInfo);
                        }else{
                            if ($mongoKwMap[$key] && $mongoKwMap[$key]['keywordId']){
                                $this->log("已存在，无需更新");
                                continue;
                            }
                            $this->mongoUpdateKeyword($mongoKwMap[$key]['_id'],$kwMap[$key]['keywordId']);
                        }
                        continue;
                    }
                    $cckwList[] = $kw;
                }

                $createKeywordIdsList = $this->enabledKeyword($content['账号'],$campaignId,$adGroupId,$oldAdGroupInfo['defaultBid'],$cckwList);
                if (count($createKeywordIdsList) > 0){

                    $kwMap = $this->listKeyword($content['账号'],$campaignId,$adGroupId);

                    foreach ($cckwList as $kw) {
                        $key = "{$kw['contentType']}_{$kw['content']}";
                        if (isset($kwMap[$key]) && $kwMap[$key]) {
                            if (!isset($mongoKwMap[$key])) {
                                $this->mongoCreateKeyword($content['账号'], $campaignId, $adGroupId, $kw['content'], $kw['contentType'], $kwMap[$key]['keywordId'], $oldAdGroupInfo);
                            } else {
                                $this->mongoUpdateKeyword($mongoKwMap[$key]['_id'], $kwMap[$key]['keywordId']);
                            }
                        }
                    }

                }else{
                    $this->log("{$content['sku']} - 资呈不需要投放kw广告");
                }


            }
        }

        $this->dingTalk("【重开kw广告提醒】");
    }

    public function fbaNo($channel,$sku)
    {
        $productIdInfo = DataUtils::getPageListInFirstData($this->curlService->s3015()->get("pid-scu-maps/queryPage",[
            "productId" => $sku,
            "channel" => $channel,
            "scuIdType" => "fba",
            "scuIdStyle" => "sellerSku"
        ]));
        return $productIdInfo['scuId'];
    }


    //===================================== campaign start ===================================================
    public function getMongoCampaignInfo($sellerId,$campaign)
    {
        $key = "{$sellerId}_{$campaign}";
        $str = $this->redis->hGet("campaignSpData",$key);
        $res = [];
        if (!$str){
            $res = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
                "company" => "CR201706060001",
                "channel" => $this->specialSellerIdConver($sellerId),
                "campaignName" => $campaign,
                "limit" => 1
            ]));
            if ($res){
                $this->redis->hSet("campaignSpData",$key,json_encode($res,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $res = json_decode($str,true);
        }
        return $res;
    }
    public function mongoCreateCampaignInfo($sellerId,$fixCampaign,$campaignId,$oldCampaignInfo)
    {
        $createCampaign = [
            "company" => "CR201706060001",
            "channel" => $this->specialSellerIdConver($sellerId),
            "status" => "2",
            "messages" => $this->messages,
            "productLineId" => "",
            "campaignName" => $fixCampaign,
            "campaignId" => $campaignId,
            "targetingType" => "manual",
            "state" => "enabled",
            "dailyBudget" => $oldCampaignInfo['dailyBudget'],
            "bidStrategy" => isset($oldCampaignInfo['bidStrategy']) && $oldCampaignInfo['bidStrategy'] != "" ? $oldCampaignInfo['bidStrategy'] : "",
            "placementTop" => isset($oldCampaignInfo['placementTop']) && $oldCampaignInfo['placementTop'] != "" ? $oldCampaignInfo['placementTop'] : "",
            "placementProductPage" => isset($oldCampaignInfo['placementProductPage']) && $oldCampaignInfo['placementProductPage'] != "" ? $oldCampaignInfo['placementProductPage'] : "",
            "placementRestOfSearch" => isset($oldCampaignInfo['placementRestOfSearch']) && $oldCampaignInfo['placementRestOfSearch'] != "" ? $oldCampaignInfo['placementRestOfSearch'] : "",
            "startDate" => date("Ymd",time()),
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_campaigns/",$createCampaign));
    }
    public function mongoUpdateCampaignInfo($_id,$campaignId)
    {
        $resp1 = $this->curlService->s3023()->post("amazon_sp_campaigns/updateCampaigns",[
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "campaignId" => $campaignId,
                "messages" => $this->messages
            ],
        ]);
        if ($resp1['result'] && isset($resp1['result']['campaign']) && count($resp1['result']['campaign']) > 0){
            $this->log("updateCampaign 成功：{$resp1['result']['campaign']['channel']} - {$resp1['result']['campaign']['campaignName']}");
        }else{
            $this->log("updateCampaign 失败：{$resp1['result']['campaign']['channel']} - {$resp1['result']['campaign']['campaignName']}");
        }

    }
    public function listCampaign($sellerId,$campaignName)
    {
        $condition = ["name" => $campaignName];

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/campaigns/getCampaigns/{$sellerId}", $condition));
        $campaignNameInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $campaignNameInfo = $resp['data'][0];
        }
        return $campaignNameInfo;
    }
    
    public function enabledCampaign($sellerId,$campaignName,$budget,$bidStrategy)
    {
        $createParams = [
            "name" => $campaignName,
            "campaignType" => "sponsoredProducts",
            "state" => "enabled",
            "targetingType" => "manual",
            "startDate" => date("Ymd",time()),
            "dailyBudget" => $budget
        ];

        $bidding = [];
        $bidding["strategy"] = $bidStrategy;
        if (count($bidding) > 0) {
            $createParams["bidding"] = $bidding;
        }


        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/campaigns/postCampaigns/{$sellerId}", [$createParams]));
        if($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS"){
            //创建成功
            $amazonSpCampaignMsg = $returnMessage['data'][0];
            return $amazonSpCampaignMsg['campaignId'];
        }else{
            //这里如果失败的原因是DUPLICATE_VALUE ，则说明是campaign重复创建失败，需要递归，重新创建
            $this->log("创建campaign失败：{$sellerId} - {$campaignName}");
            return "";
        }
    }
    //===================================== campaign end ===================================================///


    //============================Target start==============================================///
    public function getMongoTargetAsin($sellerId,$campaignId,$adGroupId)
    {

        $mongoList = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_targets/queryPage",[
            "companyId" => "CR201706060001",
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
        ]));
        $mongoTargetMap = [];
        foreach ($mongoList as $mongo){
            $mongoTargetMap[$mongo['value']] = $mongo;
        }
        return $mongoTargetMap;
    }
    public function mongoCreateTargetAsin($sellerId, $campaignId, $adGroupId, $asin,$targetConfig)
    {
        $this->log("{$sellerId} - {$campaignId} - {$adGroupId} - {$asin} - create mongo");
        //需要新增mongo target
        $createMongoTarget = array(
            "companyId" => "CR201706060001",
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "targetId" => $targetConfig['targetId'],
            "channel" => $this->specialSellerIdConver($sellerId),
            "type" => "asinSameAs",
            "value" => $asin,
            "expressionType" => "manual",
            "state" => "enabled",
            "bid" => $targetConfig['bid'],
            "createdBy" => $this->messages,
            "modifiedBy" => $this->messages,
            "status" => 2,
            "targetName" => $asin
        );

        $resp1 = DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_targets/", $createMongoTarget));
        return $resp1;
    }

    public function mongoUpdateTarget($_id,$targetId,$bid)
    {

        $resp1 = $this->curlService->s3023()->post("amazon_sp_targets/updateBiddableTargets", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "targetId" => $targetId,
                "bid" => $bid,
                "modifiedBy" => $this->messages,
            ],
        ]);
        if ($resp1['result'] && isset($resp1['result']['target']) && count($resp1['result']['target']) > 0) {
            $this->log("updateBiddableTargets：成功：{$resp1['result']['target']['channel']} - {$resp1['result']['target']['value']} - {$resp1['result']['target']['type']}");
        }else{
            $this->log("updateBiddableTargets: 失败");
        }
    }

    public function listTargetAsin($sellerId,$campaignId,$adGroupId,$targetIdList = "")
    {
        $condition = ["campaignIdFilter" => $campaignId, "adGroupIdFilter" => $adGroupId];
        if($targetIdList != ""){
            $condition["targetIdFilter"] = $targetIdList;
        }

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productTargeting/getTargets/{$sellerId}", $condition));
        $targetAsinMap = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $spInfo){
                $asin = $spInfo['expression'][0]['value'];
                $targetAsinMap[$asin] = ["targetId" => $spInfo['targetId'],"bid" => $spInfo['bid']];
            }
        }
        return $targetAsinMap;
    }

    public function enabledAsinSp($createTargetList,$sellerId)
    {

        $res = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/productTargeting/postTargets/{$sellerId}",$createTargetList));
        $targetIds = [];
        if ($res && $res['data']){
            $this->log("成功");
            foreach($res['data'] as $targetInfo){
                if ($targetInfo['code'] == "SUCCESS"){
                    $this->log("成功：{$targetInfo['targetId']}");
                    $targetIds[] = $targetInfo['targetId'];
                }
            }
        }else{
            $this->log("失败");
        }
        return $targetIds;
    }

    public function buildCreateTargetAsinList($campaignId,$adGroupId,$bid,$cpAsin)
    {
        $createTargetList = [];
        foreach ($cpAsin as $asin){
            //重新投放
            $condition = array(
                "campaignId" => (int)$campaignId,
                "adGroupId" => (int)$adGroupId,
                "state" => "enabled",
                "expressionType" => "manual",
                "bid" => $bid ? $bid : null,
            );
            $expressionGroup = array(
                "value" => $asin,
                "type" => "asinSameAs"
            );
            $condition['expression'] = array($expressionGroup);
            $condition['resolvedExpression'] = array($expressionGroup);
            $createTargetList[] = $condition;
        }
        return $createTargetList;
    }
    //============================Target end==============================================///


    //============================== AdGroup start==============================================///
    public function getMongoAdGroupInfo($sellerId,$campaignId,$adGroupName)
    {

        return DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupName" => $adGroupName,
            "limit" => 1
        ]));
    }
    public function mongoCreateAdGroup($sellerId,$campaignId,$adGroupId,$adGroupName,$oldAdGroupInfo)
    {
        $createMongoAdGroup = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "adGroupName" => $adGroupName,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "state" => "enabled",
            "defaultBid" => $oldAdGroupInfo['defaultBid'],
            "createdBy" => $this->messages,
            "selectType" => $oldAdGroupInfo['selectType'] ?? []
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_adgroups/", $createMongoAdGroup));
    }

    public function mongoUpdateAdGroup($_id,$adGroupId)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_adgroups/updateAdGroups", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "state" => "enabled",
                "adGroupId" => $adGroupId,
                "modifiedBy" => $this->messages,
                "status" => "2"
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['adgroup']) && count($resp['result']['adgroup']) > 0) {
            $this->log("updateAdGroups：成功：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}");
        }else{
            $this->log("updateAdGroups：失败：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}",true);
        }
    }

    public function listAdGroup($sellerId,$campaignId,$adGroupName)
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "name" => $adGroupName,
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/adGroups/getAdGroupsExtend/{$sellerId}", $condition));
        $adGroupNameInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $adGroupNameInfo = $resp['data'][0];
        }
        return $adGroupNameInfo;
    }

    public function enabledAdGroup($sellerId,$campaignId,$adGroupName,$defaultBid)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/adGroups/postAdGroups/{$sellerId}", [[
            "name" => $adGroupName,
            "campaignId" => $campaignId,
            "defaultBid" => $defaultBid,
            "state" => "enabled"
        ]]));

        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return $returnMessage['data'][0]['adGroupId'];
        }else{
            $this->log("创建adGroup失败：{$sellerId} {$adGroupName}");
            return "";
        }
    }

    //=============================AdGroup end==============================================///


    //============================== Product start==============================================///
    public function getMongoProductInfo($sellerId,$campaignId,$adGroupId,$sku)
    {
        return DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_products/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "sku" => $sku,
            "limit" => 1
        ]));
    }
    public function mongoCreateProduct($sellerId,$campaignId,$adGroupId,$sku,$adId,$oldProductInfo)
    {
        $createMongoAdGroup = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "adId" => $adId,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "state" => "enabled",
            "sku" => $sku,
            "asin" => $oldProductInfo['asin'] ?? "",
            "fixAsinDate" => $oldProductInfo['fixAsinDate'] ?? "",
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_products/", $createMongoAdGroup));
    }

    public function mongoUpdateProduct($_id,$adId)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_products/updateProductAds", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "adId" => $adId,
                "modifiedBy" => $this->messages,
                "status" => "2"
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['product']) && count($resp['result']['product']) > 0) {
            $this->log("updateProductAds：成功：{$resp['result']['product']['channel']} - {$resp['result']['product']['sku']}");
        }else{
            $this->log("updateProductAds：失败：{$resp['result']['product']['channel']} - {$resp['result']['product']['sku']}");
        }
    }

    public function listProduct($sellerId,$campaignId,$adGroupId,$sku)
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "adGroupIdFilter" => $adGroupId
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productAds/getProductAds/{$sellerId}", $condition));
        $adInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $item) {
                if ($item['sku'] == $sku && $item['campaignId'] == $campaignId && $item['adGroupId'] == $adGroupId) {
                    $adInfo = $item;
                    break;
                }
            }
        }
        return $adInfo;
    }

    public function enabledProduct($sellerId,$campaignId,$adGroupId,$sku)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/productAds/postProductAds/{$sellerId}", [[
            "adGroupId" => $adGroupId,
            "campaignId" => $campaignId,
            "sku" => $sku,
            "state" => "enabled"
        ]]));
        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return $returnMessage['data'][0]['adId'];
        }else{
            $this->log("创建product失败：{$sellerId} {$sku}");
            return "";
        }
    }

    public function pausedProduct($sellerId,$adId,$sku)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/productAds/putProductAds/{$sellerId}", [[
            "adId" => $adId,
            "state" => "paused"
        ]]));
        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //关停成功
            return true;
        }else{
            $this->log("关停product失败：{$sellerId} {$adId} {$sku}");
            return false;
        }
    }

    //=============================Product end==============================================///


    //============================== Keyword start==============================================///
    public function getMongoKeywordInfo($sellerId,$campaignId,$adGroupId)
    {
        $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_keywords/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 500
        ]));
        $keywordMap = [];
        if ($list){
            foreach ($list as $item){
                $key = "{$item['matchType']}_{$item['keywordText']}";
                $keywordMap[$key] = $item;
            }
        }
        return $keywordMap;
    }
    public function mongoCreateKeyword($sellerId,$campaignId,$adGroupId,$kw,$ky,$keywordId,$oldAdGroupInfo)
    {
        $createMongo = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "keywordId" => $keywordId,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "keywordText" => $kw,
            "keywordType" => "expansion",
            "state" => "enabled",
            "matchType" => $ky,
            "bid" => $oldAdGroupInfo['defaultBid'] ?? "",
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_keywords/", $createMongo));
    }

    public function mongoUpdateKeyword($_id,$keywordId)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_keywords/updateBiddableKeywords", [
            "id" => $_id,
            "from" => "php restful",
            "isPassNotification" => "false",
            "updateParams" => [
                "keywordId" => $keywordId,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "messages" => $this->messages
            ]
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateBiddableKeywords：成功：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['matchType']}");
        }else{
            $this->log("updateBiddableKeywords：失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['matchType']}");
        }
    }

    public function listKeyword($sellerId,$campaignId,$adGroupId,$matchTypeFilter = "",$keywordTextFilter ="")
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "adGroupIdFilter" => $adGroupId,
            "stateFilter" => "enabled"
        ];
        if(!empty($matchTypeFilter)){
            $condition["matchTypeFilter"] = $matchTypeFilter;
        }
        if(!empty($keywordTextFilter)){
            $condition["keywordTextFilter"] = (string)$keywordTextFilter;
        }
        $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/keywords/getKeywordsExtended/{$sellerId}", $condition));
        $keywordMap = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $spInfo){
                $key = "{$spInfo['matchType']}_{$spInfo['keywordText']}";
                $keywordMap[$key] = ["keywordId" => $spInfo['keywordId'],"bid" => $spInfo['bid']];
            }
        }
        return $keywordMap;
    }

    public function enabledKeyword($sellerId,$campaignId,$adGroupId,$bid,$keywords)
    {
        //搞成批量的
        $createKeywords = [];
        foreach ($keywords as $pomsKeywordInfo){
            $createKeywords[] = [
                "campaignId" => $campaignId,
                "adGroupId" => $adGroupId,
                "bid" => $bid,
                "matchType" => $pomsKeywordInfo['contentType'],
                "keywordText" => (string)$pomsKeywordInfo['content'],
                "state" => "enabled"
            ];
        }
        $ids = [];
        if (count($createKeywords) > 0){
            $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/keywords/postKeywords/{$sellerId}", $createKeywords));
            if ($resp && $resp['data']){
                $this->log("成功");
                foreach($resp['data'] as $info){
                    if ($info['code'] == "SUCCESS"){
                        $this->log("成功：{$info['keywordId']}");
                        $ids[] = $info['keywordId'];
                    }
                }
            }else{
                $this->log("失败");
            }
        }

        return $ids;
    }


    //=================================keyword end===========================================///










    private function specialSellerIdConver($sellerId){
        return ($sellerId == 'amazon') ? 'amazon_us' : $sellerId;
    }
    private function specialSellerIdReverseConver($sellerId){
        return ($sellerId == 'amazon_us') ? 'amazon' : $sellerId;
    }

    private function getPaSkuMaterials($skuId)
    {

        $resp = DataUtils::getPageDocListInFirstDataV1($this->curlService->s3044()->get("pa_sku_materials/queryPage", [
            "limit" => 1,
            "skuId" => $skuId,
            "page" => 1
        ]));
        return $resp;
    }

    public function buildKeyword($skuMaterialInfo,$defaultBid = "",$skuId = "")
    {

        if ($skuId){
            $skuMaterialInfo = $this->getPaSkuMaterials($skuId);
        }

        $redisRuleDataStr = $this->redis->get("paPomsAmazonKeywordSpAutoRule");
        $getKeyResp = [];
        if (!$redisRuleDataStr){
            $curlService = (new CurlService())->pro();
            $getKeyResp = DataUtils::getNewResultData($curlService->gateway()->getModule("config")->getWayPost($curlService->module . "/business/config/v1/getConfigByKey", [
                "configKey" => "PA_POMS_AMAZON_KEYWORD_SP_AUTO_RULE",
            ]));
            $this->redis->set("paPomsAmazonKeywordSpAutoRule",json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
        }else{
            $getKeyResp = json_decode($redisRuleDataStr,true);
        }

        if ($getKeyResp && $getKeyResp['configValue'] && is_array($getKeyResp['configValue']) && !empty($getKeyResp['configValue'])){
            // 过滤有效的配置
            $validConfigs = array_filter($getKeyResp['configValue'], function ($item) {
                return $item['status'] === 1;
            });
            if (empty($validConfigs)) {
                $this->log("无有效的keyword组装规则");
                return [];
            }

            //根据规则和资料呈现内容，组装keyword
            $contentList = $this->generateCombinations($validConfigs, $skuMaterialInfo, $defaultBid);
            foreach ($contentList as $content){
                $this->log("{$content['contentType']} 类型： {$content['content']}");
            }
            return $contentList;
        }else {
            $this->log("配置中心不存在 keyword组装规则：PA_POMS_AMAZON_KEYWORD_SP_AUTO_RULE");
            return [];
        }
    }

    // 生成关键词随机组合
    function generateCombinations($validConfigs, $wordsCpAsinFitment,$defaultBid = "") {
        $results = [];
        $fitments = $wordsCpAsinFitment['fitment'];
        $keywords = $wordsCpAsinFitment['keywords'];

        // 用于跟踪已添加的组合，格式为 "matchType|content"
        $uniqueCombinations = [];

        // 遍历 config
        foreach ($validConfigs as $item) {
            if ($item['status'] === 1) {
                $matchType = $item['matchType'];
                $rules = $item['rule'];

                // 生成组合
                $hasFitment = !empty($fitments);
                $hasKeywords = !empty($keywords);

                // 如果 fitment 不为空
                if ($hasFitment) {
                    foreach ($fitments as $fitment) {
                        $make = $fitment['make'];
                        $model = $fitment['model'];

                        // 如果 keywords 不为空
                        if ($hasKeywords) {
                            foreach ($keywords as $keyword) {
                                // 创建内容
                                $contentParts = [];
                                foreach ($rules as $rule) {
                                    if ($rule === 'make') {
                                        $contentParts[] = $make; // 使用 make
                                    } elseif ($rule === 'model') {
                                        $contentParts[] = $model; // 使用 model
                                    } elseif ($rule === 'word') {
                                        $contentParts[] = $keyword; // 使用 keyword
                                    }
                                }

                                // 组合内容
                                $content = implode(' ', array_filter($contentParts));
                                if (!empty($content)) {
                                    $combinationKey = $matchType . $content;
                                    if (!isset($uniqueCombinations[$combinationKey])) {
                                        $results[] = [
                                            'contentType' => $matchType,
                                            'content' => $content,
                                            'contentBid' => $defaultBid // 可以根据需要填充这个字段
                                        ];
                                        $uniqueCombinations[$combinationKey] = true; // 标记为已添加
                                    }
                                }
                            }
                        } else {
                            // 仅使用 fitment
                            $contentParts = [];
                            foreach ($rules as $rule) {
                                if ($rule === 'make') {
                                    $contentParts[] = $make; // 使用 make
                                } elseif ($rule === 'model') {
                                    $contentParts[] = $model; // 使用 model
                                }
                            }

                            // 组合内容
                            $content = implode(' ', array_filter($contentParts));
                            if (!empty($content)) {
                                $combinationKey = $matchType . $content;
                                if (!isset($uniqueCombinations[$combinationKey])) {
                                    $results[] = [
                                        'contentType' => $matchType,
                                        'content' => $content,
                                        'contentBid' => $defaultBid
                                    ];
                                    $uniqueCombinations[$combinationKey] = true; // 标记为已添加
                                }
                            }
                        }
                    }
                } elseif ($hasKeywords) {
                    // 如果 fitment 为空，直接生成基于 keywords 的组合
                    foreach ($keywords as $keyword) {
                        foreach ($rules as $rule) {
                            if ($rule === 'word') {
                                $combinationKey = $matchType . $keyword;
                                if (!isset($uniqueCombinations[$combinationKey])) {
                                    $results[] = [
                                        'contentType' => $matchType,
                                        'content' => $keyword,
                                        'contentBid' => $defaultBid
                                    ];
                                    $uniqueCombinations[$combinationKey] = true; // 标记为已添加
                                }
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }


    public function listFixSpData()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/sp/";

        $exportList = [];

        try {
            $contentList = $excelUtils->getXlsxData($fileName . "all.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $item){

                $exportData = [
                    "ce" => $item['CE单号'],
                    "sku" => $item['sku'],
                    "productLevel" => $item['产品分级'],
                    "saleUser" => $item['运营人员'],
                    "channel" => $item['渠道'],
                    "sellerId" => $item['账号'],
                    "fba" => $item['fba'],
                    "fbaAsin" => $item['fba Asin'],
                    "nonFba" => $item['nonfba编号'],
                    "oldCampaign" => $item['campaign'],
                    "oldAdGroup" => $item['adgroup'],
                    "ceResult1" => $item['CE单情况'],
                    "ceResult2" => $item['能找回覆盖前数据'],
                    "ceResult3" => $item['广告类型'],
                    "ceResult4" => $item['备注'],
                    "ceResult5" => $item['是否关闭'],
                    "ceResult6" => $item['是否重新投放'],
                    "ceResult7" => $item['处理方案'],
                    "newCampaign" => "/",
                    "newAdGroup" => "/",
                ];
                $oldCampaignName = $item['campaign'];
                $newCampaignName = "";

                $oldAdGroupName = $item['adgroup'];
                $newAdGroupName = "";



                if ($item['处理方案'] == 'Asin：保留原广告组，补充Asin'){
                    $newCampaignName = $item['campaign'];
                    $newAdGroupName = $item['adgroup'];
                }else if ($item['处理方案'] == 'Asin：关闭原广告组，重新开Asin广告（按CE单剔除首个sku）'){

                    $manualAsinCampaignRule = $this->getAmazonSpRuleRegexSystemCampaignRegex($item['渠道'],$item['账号'],"manual asin");
                    $pipe = "";
                    if ($manualAsinCampaignRule && isset($manualAsinCampaignRule['pipe']) && $manualAsinCampaignRule['pipe']){
                        $pipe = $manualAsinCampaignRule['pipe'];
                    }
                    $newCampaignName = $item['campaign'].$pipe."修正重开Asin";
                    $newAdGroupName = $item['adgroup'];

                }else if ($item['处理方案'] == 'KW：关闭原广告组，重新开KW广告（按CE单剔除首个sku）'){
                    $manualAsinCampaignRule = $this->getAmazonSpRuleRegexSystemCampaignRegex($item['渠道'],$item['账号'],"manual");
                    $pipe = "";
                    if ($manualAsinCampaignRule && isset($manualAsinCampaignRule['pipe']) && $manualAsinCampaignRule['pipe']){
                        $pipe = $manualAsinCampaignRule['pipe'];
                    }
                    $newCampaignName = $item['campaign'].$pipe."修正重开KW";
                    $newAdGroupName = $item['adgroup'];
                }

                if (!$newCampaignName){
                    $exportList[] = $exportData;
                    continue;
                }
                $campaignInfo = $this->getMongoCampaignInfo($item['账号'],$newCampaignName);
                $campaignId = "";
                if ($campaignInfo && $campaignInfo['campaignId']){
                    $exportData['newCampaign'] = $newCampaignName;
                    $campaignId = $campaignInfo['campaignId'];
                }
                if (!$campaignId){
                    $this->log("无campaign广告");
                    $exportList[] = $exportData;
                    continue;
                }

                $adGroupInfo = $this->getMongoAdGroupInfo($item['账号'],$campaignId,$newAdGroupName);
                $adGroupId = "";
                if ($adGroupInfo && $adGroupInfo['adGroupId']){
                    $exportData['newAdGroup'] = $newAdGroupName;
                    $adGroupId = $adGroupInfo['adGroupId'];
                }
                $exportList[] = $exportData;


            }



            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "CE单号",
                    "sku",
                    "产品分级",
                    "运营人员",
                    "渠道",
                    "账号",
                    "fba",
                    "fba Asin",
                    "nonfba编号",
                    "原campaign",
                    "原adgroup",
                    "CE单情况",
                    "能找回覆盖前数据",
                    "广告类型",
                    "备注",
                    "是否关闭",
                    "是否重新投放",
                    "处理方案",
                    "重开campaign",
                    "重开adgroup"
                ],$exportList,"sku资料呈现广告修复后数据_".date("YmdHis").".xlsx");
                $this->log($filePath);
            }else{
                $this->log("没有数据");

            }

        }


    }
}

$con = new SpPausedAdGroupController();
//$con->pausedAdGroup();
//$con->supplementAdGroupWithAsinAds();
//$con->closeAdGroupWithOpenNewAsinAds();
$con->closeAdGroupWithOpenNewKeywordAds();


//$con->listFixSpData();
//$con->buildKeyword([],0.2,"a25081400ux0270");

//$con->listCampaign("amazon","PA_座椅套-1_auto_李运月");
//$con->listAdGroup("amazon_us_hope","426175740910397","240929hoape002395");
//$con->listProduct("amazon_ca_find","513637034927953","447462850323022","00f70d10s250414");
//$con->listKeyword("amazon_us_find","412250249469783","485308838570959");

//$con->mongoUpdateCampaignInfo("64ae1a082e75f7434dd5f551",361814965215088);