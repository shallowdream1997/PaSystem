<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/RequestUtils.php");

class SpApi
{
    private $log;

    private $curlService;
    private $messages = "system(zhouangang)";

    private $redis;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->curlService = (new CurlService())->pro();
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
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
    public function getMongoCampaignInfoV2($campaignId)
    {
        $str = $this->redis->hGet("campaignSpCampaignIdData",$campaignId);
        $res = [];
        if (!$str){
            $res = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
                "campaignId" => $campaignId,
                "limit" => 1
            ]));
            if ($res){
                $this->redis->hSet("campaignSpCampaignIdData",$campaignId,json_encode($res,JSON_UNESCAPED_UNICODE));
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
    
    public function enabledCampaign($sellerId,$campaignName,$targetingType,$budget,$bidStrategy)
    {
        $createParams = [
            "name" => $campaignName,
            "campaignType" => "sponsoredProducts",
            "state" => "enabled",
            "targetingType" => $targetingType,
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

    public function getMongoTargetAsinV2($sellerId,$campaignId,$adGroupId)
    {

        $mongoList = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_targets/queryPage",[
            "companyId" => "CR201706060001",
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
        ]));
        return $mongoList;
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
    public function getMongoAdGroupInfo($sellerId,$campaignId = '',$adGroupName = '',$adGroupId = '')
    {
        $condition = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "limit" => 1
        ];
        if ($campaignId){
            $condition['campaignId'] = $campaignId;
        }
        if ($adGroupName){
            $condition['adGroupName'] = $adGroupName;
        }
        if ($adGroupId){
            $condition['adGroupId'] = $adGroupId;
        }
        return DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage",$condition));
    }
    public function getMongoAdGroups($adGroupIds = [])
    {
        $condition = [
            "adGroupId_in" => implode(",",$adGroupIds),
            "limit" => count($adGroupIds)
        ];
        return DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage",$condition));
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

    public function pausedAdGroup($sellerId,$adGroupId,$adGroupName)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/adGroups/putAdGroups/{$sellerId}", [[
            "adGroupId" => $adGroupId,
            "state" => "paused"
        ]]));

        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return true;
        }else{
            $this->log("关停adGroup失败：{$sellerId} {$adGroupName}");
            return false;
        }
    }

    /**
     * 删除mongo中的adGroup
     * @param $_id
     * @return array
     */
    public function deleteMongoAdGroupInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_adgroups/{$_id}",[]));
    }

    /**
     * 删除mongo中的campaign
     * @param $_id
     * @return array
     */
    public function deleteMongoCampaignInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_campaigns/{$_id}",[]));
    }

    /**
     * 删除mongo中的ads
     * @param $_id
     * @return array
     */
    public function deleteMongoProductAdsInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_products/{$_id}",[]));
    }
    /**
     * 删除mongo中的keyword
     * @param $_id
     * @return array
     */
    public function deleteMongoKeywordInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_keywords/{$_id}",[]));
    }
    /**
     * 删除mongo中的negativeKeyword
     * @param $_id
     * @return array
     */
    public function deleteMongoNegativeKeywordInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_negativeKeywords/{$_id}",[]));
    }

    /**
     * 删除mongo中的target
     * @param $_id
     * @return array
     */
    public function deleteMongoTargetInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_targets/{$_id}",[]));
    }

    /**
     * 删除mongo中的negativeTarget
     * @param $_id
     * @return array
     */
    public function deleteMongoNegativeTargetInfo($_id)
    {
        return DataUtils::getResultData($this->curlService->s3023()->delete("amazon_sp_negative_targets/{$_id}",[]));
    }



    public function archivedAdGroup($sellerId,$adGroupIds)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->deleteWithBodyData("amazon/ad/adGroups/deleteAdGroups/{$sellerId}", [
            "adGroupIdFilter" => [
                "include" => $adGroupIds
            ]
        ]));
        if ($returnMessage['status'] == 'success' && isset($returnMessage['data']) && isset($returnMessage['data']['adGroups'])) {
            $result = [];
            foreach ($returnMessage['data']['adGroups']['error'] as $item){
                $errorMsg = $item['errors'][0]['errorType'];
                $result[$item['index']] = $errorMsg;
            }
            foreach ($returnMessage['data']['adGroups']['success'] as $item){
                $result[$item['index']] = "success";
            }

            $last = [];
            foreach ($adGroupIds as $index => $adGroupId){
                $last[] = [
                    "adGroupId" => $adGroupId,
                    "msg" => $result[$index]
                ];
            }
            //创建成功
            return $last;
        }else{
            $this->log("归档adGroup失败：{$sellerId} " . json_encode($adGroupIds,JSON_UNESCAPED_UNICODE));
            return [];
        }
    }


    public function archivedKeyword($sellerId,$keywordIds)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->deleteWithBodyData("amazon/ad/keywords/deleteKeywords/{$sellerId}", [
            "keywordIdFilter" => [
                "include" => $keywordIds
            ]
        ]));
        if ($returnMessage['status'] == 'success' && isset($returnMessage['data']) && isset($returnMessage['data']['keywords'])) {
            $result = [];
            foreach ($returnMessage['data']['keywords']['error'] as $item){
                $errorMsg = $item['errors'][0]['errorType'];
                $result[$item['index']] = $errorMsg;
            }
            foreach ($returnMessage['data']['keywords']['success'] as $item){
                $result[$item['index']] = "success";
            }

            $last = [];
            foreach ($keywordIds as $index => $keywordId){
                $last[] = [
                    "keywordId" => $keywordId,
                    "msg" => $result[$index]
                ];
            }
            //创建成功
            return $last;
        }else{
            $this->log("归档keyword失败：{$sellerId} " . json_encode($keywordIds,JSON_UNESCAPED_UNICODE));
            return [];
        }
    }


    public function archivedNegativeKeyword($sellerId,$keywordIds)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->deleteWithBodyData("amazon/ad/negativeKeywords/deleteNegativeKeywords/{$sellerId}", [
            "negativeKeywordIdFilter" => [
                "include" => $keywordIds
            ]
        ]));
        if ($returnMessage['status'] == 'success' && isset($returnMessage['data']) && isset($returnMessage['data']['negativeKeywords'])) {
            $result = [];
            foreach ($returnMessage['data']['negativeKeywords']['error'] as $item){
                $errorMsg = $item['errors'][0]['errorType'];
                $result[$item['index']] = $errorMsg;
            }
            foreach ($returnMessage['data']['negativeKeywords']['success'] as $item){
                $result[$item['index']] = "success";
            }

            $last = [];
            foreach ($keywordIds as $index => $keywordId){
                $last[] = [
                    "keywordId" => $keywordId,
                    "msg" => $result[$index]
                ];
            }
            //创建成功
            return $last;
        }else{
            $this->log("归档negativeKeyword失败：{$sellerId} " . json_encode($keywordIds,JSON_UNESCAPED_UNICODE));
            return [];
        }
    }

    public function archivedTarget($sellerId,$targetIds)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->deleteWithBodyData("amazon/ad/productTargeting/deleteTargets/{$sellerId}", [
            "targetIdFilter" => [
                "include" => $targetIds
            ]
        ]));
        if ($returnMessage['status'] == 'success' && isset($returnMessage['data']) && isset($returnMessage['data']['targetingClauses'])) {
            $result = [];
            foreach ($returnMessage['data']['targetingClauses']['error'] as $item){
                $errorMsg = $item['errors'][0]['errorType'];
                $result[$item['index']] = $errorMsg;
            }
            foreach ($returnMessage['data']['targetingClauses']['success'] as $item){
                $result[$item['index']] = "success";
            }

            $last = [];
            foreach ($targetIds as $index => $targetId){
                $last[] = [
                    "targetId" => $targetId,
                    "msg" => $result[$index]
                ];
            }
            //创建成功
            return $last;
        }else{
            $this->log("归档target失败：{$sellerId} " . json_encode($targetIds,JSON_UNESCAPED_UNICODE));
            return [];
        }
    }



    //=============================AdGroup end==============================================///


    //============================== Product start==============================================///
    public function getMongoProductInfo($sellerId,$campaignId = '',$adGroupId = '',$sku = '',$limit = 1)
    {
        $condtion = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "limit" => $limit
        ];


        if ($campaignId) {
            $condtion['campaignId'] = $campaignId;
        }
        if ($adGroupId){
            $condtion['adGroupId'] = $adGroupId;
        }
        if ($sku){
            $condtion['sku'] = $sku;
        }
        return DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_products/queryPage",$condtion));
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

    public function mongoUpdateProduct($_id,$adId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_products/updateProductAds", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "adId" => $adId,
                "state" => $state,
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

    public function listProductV2($sellerId,$adIds)
    {
        $condition = [
            "adIdFilter" => $adIds,
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productAds/getProductAds/{$sellerId}", $condition));
        $adListInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $item) {
                $adListInfo[$item['adId']] = $item['state'];
            }
        }
        return $adListInfo;
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


    public function pausedProduct($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/productAds/putProductAds/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['adId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['adId']]) && $adListInfo[$item['adId']] == "SUCCESS"){
                $this->log("暂停product成功：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['success'][] = $item['adId'];
            }else{
                $this->log("暂停product失败：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['error'][] = $item['adId'];
            }
        }

        return $pausedAdIdResult;
    }


    public function pausedProductV2($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/productAds/putProductAds/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));

        $adIds = array_column($pausedArr,'adId');
        $adListInfo = $this->listProductV2($sellerId, implode(",",$adIds));
        $pausedAdIdResult = [];
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['adId']]) && $adListInfo[$item['adId']] == "paused"){
                $this->log("暂停product成功：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['success'][] = $item['adId'];
            }else{
                $this->log("暂停product失败：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['error'][] = $item['adId'];
            }
        }
        return $pausedAdIdResult;
    }

    //=============================Product end==============================================///


    //============================== Keyword start==============================================///
    public function getMongoKeywordInfo($sellerId,$campaignId,$adGroupId)
    {
        $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_keywords/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
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

    public function getMongoKeywordInfoV2($sellerId,$campaignId,$adGroupId)
    {
        $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_keywords/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
        ]));
        return $list;
    }

    public function getMongoNegativeKeywordInfoV2($sellerId,$campaignId,$adGroupId)
    {
        $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_negativeKeywords/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
        ]));
        return $list;
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


    public function listNegativeKeyword($sellerId,$campaignId,$adGroupId,$state)
    {
        $condition = [];
        if ($campaignId){
            $condition['campaignIdFilter'] = implode(",",$campaignId);
        }
        if ($adGroupId){
            $condition['adGroupIdFilter'] = implode(",",$adGroupId);
        }
        if ($state){
            $condition['stateFilter'] = $state;
        }
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/negativeKeywords/getNegativeKeywordsExtend/{$sellerId}", $condition));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
        return $list;
    }

    public function updateNegativeKeyword($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/negativeKeywords/putNegativeKeywords/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['keywordId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['keywordId']]) && $adListInfo[$item['keywordId']] == "SUCCESS"){
                $this->log("处理negativeKeyword成功：{$sellerId} {$item['keywordId']}");
                $pausedAdIdResult['success'][] = $item['keywordId'];
            }else{
                $this->log("处理negativeKeyword失败：{$sellerId} {$item['keywordId']}");
                $pausedAdIdResult['error'][] = $item['keywordId'];
            }
        }

        return $pausedAdIdResult;
    }

    public function mongoUpdateNegativeKeyword($_id,$keywordId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_negativeKeywords/updateNegativeKeywords", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "keywordId" => $keywordId,
                "state" => $state,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "messages" => $this->messages
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateNegativeKeyword：成功：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']}");
        }else{
            $this->log("updateNegativeKeyword：失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']}");
        }
    }


    public function updateNegativeTarget($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/negativeProductTargeting/putNegativeTargets/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['targetId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['targetId']]) && $adListInfo[$item['targetId']] == "SUCCESS"){
                $this->log("处理negativeTarget成功：{$sellerId} {$item['targetId']}");
                $pausedAdIdResult['success'][] = $item['targetId'];
            }else{
                $this->log("处理negativeTarget失败：{$sellerId} {$item['targetId']}");
                $pausedAdIdResult['error'][] = $item['targetId'];
            }
        }

        return $pausedAdIdResult;
    }

    public function mongoUpdateNegativeTarget($_id,$targetId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_negative_targets/updateBiddableNegativeTargets", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "targetId" => $targetId,
                "state" => $state,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "remark" => $this->messages
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['target']) && count($resp['result']['target']) > 0) {
            $this->log("mongoUpdateNegativeTarget：成功：{$resp['result']['target']['channel']} - {$resp['result']['target']['targetName']}");
        }else{
            $this->log("mongoUpdateNegativeTarget：失败：{$resp['result']['target']['channel']} - {$resp['result']['target']['targetName']}");
        }
    }

    public function listNegativeTarget($sellerId,$campaignId,$adGroupId,$targetIdList = "",$state = "")
    {
        $condition = [];
        if ($campaignId){
            $condition['campaignIdFilter'] = implode(",",$campaignId);
        }
        if ($adGroupId){
            $condition['adGroupIdFilter'] = implode(",",$adGroupId);
        }
        if ($state){
            $condition['stateFilter'] = $state;
        }
        if($targetIdList){
            $condition["targetIdFilter"] = $targetIdList;
        }

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/negativeProductTargeting/getNegativeTargets/{$sellerId}", $condition));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
        return $list;
    }

    public function specialSellerIdConver($sellerId){
        return ($sellerId == 'amazon') ? 'amazon_us' : $sellerId;
    }
    public function specialSellerIdReverseConver($sellerId){
        return ($sellerId == 'amazon_us') ? 'amazon' : $sellerId;
    }



    public function getMongoSellerRuleList()
    {
        $res = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company" => "CR201706060001",
            "limit" => 1000
        ]));
        return $res;
    }

    public function updateMongoSellerRule($info)
    {
        $res = DataUtils::getResultData($this->curlService->s3023()->put("amazon_sp_sellers/{$info['_id']}",$info));
        return $res;
    }

    public function pidScuMapAdGroupFindProductId($channel,$scuId)
    {
        return DataUtils::getPageListInFirstData($this->curlService->s3015()->get("pid-scu-maps/queryPage",[
            "scuIdType" => "nonFba",
            "scuIdStyle" => "sellerSku",
            "scuId" => $scuId,
            "channel" => $channel
        ]));
    }

    public function pidScuMapProductIdFindScuId($channel,$scuId)
    {
        $key = "{$channel}_{$scuId}";
        $str = $this->redis->hGet("fbaSystemWithSelling", $key);
        if (!$str) {
            $data = DataUtils::getPageListInFirstData($this->curlService->s3015()->get("pid-scu-maps/queryPage",[
                "scuIdType" => "fba",
                "scuIdStyle" => "systemWithSelling",
                "productId" => $scuId,
                "channel" => $channel
            ]));
            if ($data){
                $this->redis->hSet("fbaSystemWithSelling", $key, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $data = json_decode($str,true);
        }
        return $data;
    }

    public function sellerConfig($sellerId)
    {
        $key = "{$sellerId}";
        $str = $this->redis->hGet("sellerMap", $key);
        $channel = "";
        if (!$str) {
            $res = DataUtils::getPageListInFirstData($this->curlService->s3015()->get("seller-configs/queryPage", ["sellerId" => $sellerId]));
            if ($res) {
                $this->redis->hSet("sellerMap", $key, json_encode($res, JSON_UNESCAPED_UNICODE));
                $channel = $res['channel'];
            }
        } else {
            $res = json_decode($str,true);
            $channel = $res['channel'];
        }
        return $channel;
    }

    public function combineSpData($channel,$sellerId,$targetingType,$campaignType,$scu)
    {
        $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazonSpApi/combineSpData", [
            "channel" => $channel,
            "sellerId" => $sellerId,
            "targetingType" => $targetingType,
            "campaignType" => $campaignType,
            "scu" => $scu,
            "createdBy" => $this->messages,
        ]));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
    }

    public function paPlacementAmazonSp($channel,$sellerId,$type,$targetingType,$campaignType,$scuId)
    {
//        "1"=>"auto",
//            "2"=>"keyword",
//            "3"=>"asin",
//            "4"=>"category",
        $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazonSpApi/paPlacementAmazonSp", [
            "placementAction" => 1,
            "placementType" => $type,
            "targetingType" => $targetingType,
            "campaignType" => $campaignType,
            "channel" => $channel,
            "sellerId" => $sellerId,
            "scu" => $scuId,
            "createdBy" => "system(zhouangang)",
            "modifiedBy" => "system(zhouangang)"
        ]));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
    }

    public function paPlacementAmazonSpV2($channel,$sellerId,$type,$targetingType,$campaignType,$scuId,$contentType,$contentText,$contentBid)
    {
        $d = [
            "placementAction" => 1,
            "placementType" => $type,
            "targetingType" => $targetingType,
            "campaignType" => $campaignType,
            "channel" => $channel,
            "sellerId" => $sellerId,
            "scu" => $scuId,
            "contentType" => $contentType,
            "content" => $contentText,
            "contentBid" => $contentBid,
            "createdBy" => "system(zhouangang)",
            "modifiedBy" => "system(zhouangang)"
        ];
        $this->log(json_encode($d,JSON_UNESCAPED_UNICODE));
        $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazonSpApi/paPlacementAmazonSp", $d));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
            $this->log(json_encode($list,JSON_UNESCAPED_UNICODE));
        }
    }


    public function keywordTypeMap($type)
    {
        $typeMap = [
            "广泛" => "broad",
            "词组" => "phrase",
            "精准" => "exact",
            "词组否定" => "negativePhrase",
            "精准否定" => "negativeExact",
        ];
        return $typeMap[$type] ?? null;
    }

}