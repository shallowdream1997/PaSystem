<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpUpdateAdGroupController
{
    private $log;

    private $channel;
    private $sellerId;
    private $action;
    private $phphkpro;
    private $debug;
    private $isAuto;
    private $isAsin;
    private $isCategory;
    private $isKeyword;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
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
            'title' => "【keyword广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 已经暂停keyword完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function pausedKeyword(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "关停keyword_2.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $keywordIds = [];
            foreach ($contentList as $item){
                $keywordId = ltrim($item['keywordId'], "'");
                if (!empty($keywordId)){
                    $keywordIds[] = $keywordId;
                }
            }
            $keywordIds = array_unique($keywordIds);

            //$adgroupIds = ["520064669922127"];
            foreach (array_chunk($keywordIds,200) as $chunk){
                //$redisService->hSet("sp_update_adgroup_reload", $sellerId, json_encode($chunkAdgroupIds, JSON_UNESCAPED_UNICODE));
                $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage", [
                    "keywordId_in" => implode(',', $chunk),
                    "limit" => 1000
                ]));
                if (count($list) > 0){
                    foreach ($list as &$info){
                        $info['state'] = "paused";
                        //查询notification里面当前_id主键待处理的广告数据,可能会有多个数据
                        $oldNotificationDataMap = $this->getNotificationDataElemMatch($info['_id']);
                        //对即将要更新的fromId里面，找出是否有符合当前更新条件的数据，如果有符合就不需要更新了，如果没有符合的就插入一次新的更新数据
                        $isUpdate = true;
                        if (count($oldNotificationDataMap) > 0){
                            foreach ($oldNotificationDataMap as $old){
                                if ($old['keywordId'] == $info['keywordId'] && $old['state'] == $info['state']){
                                    //如果有一模一样的更新，就不用再次插入了
                                    $isUpdate = false;
                                    break;
                                }
                            }
                        }

                        if (!$isUpdate){
                            $this->log("已有数据不必再插入");
                            continue;
                        }

                        $this->updateKeyword($info['_id'], array(
                            "status" => "1",
                            "messages" => "运维关停",
                            "state" => $info['state'],
                            "modifiedBy" => "system(zhouangang)",
                            "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                        ));
                    }
                }

            }

            $this->dingTalk();
        }

    }
    /**
     * 获取notification里面fromId 的数据
     * @param $_id
     * @return array
     */
    public function getNotificationDataElemMatch($_id){
        $oldNotificationDataArr = array();

        $curlService = new CurlService();
        $resp = $curlService->pro()->s3023()->post("amazon_sp_notifications/getNotificationDataElemMatch", [
            'fromId' => $_id
        ]);
        if (isset($resp['result']) && count($resp['result']) > 0){
            $oldNotificationDataArr = $resp['result'];
        }
        $oldNotificationDataMap = array();
        if (count($oldNotificationDataArr) > 0){
            //存在符合fromId条件的数据，遍历出来，筛选出fromId对应的所有的要更新的数据
            foreach ($oldNotificationDataArr as $oldNotificationData){
                $fromIdMap = array_column($oldNotificationData['data'],null,'fromId');
                if (isset($fromIdMap[$_id])){
                    $oldNotificationDataMap[] = $fromIdMap[$_id];
                }
            }
        }
        return $oldNotificationDataMap;
    }

    /**
     * 更新adgroup数据，这里更新的有adgroup数据，并且记录adgroup_log日志，并且新增notification脚本内容
     * @param $_id
     * @param $updateParams
     */
    private function updateKeyword($_id, $updateParams){
        $curlService = new CurlService();
        $resp = $curlService->pro()->s3023()->post("amazon_sp_keywords/updateBiddableKeywords", [
            "id" => $_id,
            "isPassNotification" => "true",
            "from" => "FIX_PAUSED_KEYWORD",
            "updateParams" => $updateParams,
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateKeyword：成功插入notification：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['state']} - {$resp['result']['keyword']['matchType']}");
        }else{
            $this->log("updateKeyword：插入notification失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['state']} - {$resp['result']['keyword']['matchType']}");
        }
        return $this;
    }


    /**
     * 更新adgroup数据，这里更新的有adgroup数据，并且记录adgroup_log日志，并且新增notification脚本内容
     * @param $_id
     * @param $updateParams
     */
    private function updateProduct($_id, $updateParams){
        $curlService = new CurlService();
        $resp = $curlService->pro()->s3023()->post("amazon_sp_keywords/updateBiddableKeywords", [
            "id" => $_id,
            "isPassNotification" => "true",
            "from" => "FIX_PAUSED_KEYWORD",
            "updateParams" => $updateParams,
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateKeyword：成功插入notification：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['state']} - {$resp['result']['keyword']['matchType']}");
        }else{
            $this->log("updateKeyword：插入notification失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['state']} - {$resp['result']['keyword']['matchType']}");
        }
        return $this;
    }


    public function pausedKeywordNotification(){

        $curlService = new CurlService();
        $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_notifications/queryPage", [
            "action" => "updateBiddableKeywords",
            "from" => "FIX_PAUSED_KEYWORD",
            "orderBy" => "createdOn",
            "limit" => 5000,
            "page" => 1
        ]));
        if (count($list) > 0){
            foreach ($list as &$info){
                $channel = $info['channel'];
                $sellerId = $channel;
                if ($channel == "amazon_us"){
                    $sellerId = "amazon";
                }
                if(!empty($info['data'])){
                    $keywordInfo = $info['data'][0];

                    $createParams = [
                        "adGroupId" => $keywordInfo["adGroupId"],
                        "campaignId" => $keywordInfo["campaignId"],
                        "keywordId" => $keywordInfo["keywordId"],
                        "state" => $keywordInfo["state"],
                        "bid" => $keywordInfo["bid"],
                    ];
                    //创建 createProductAds 数据
                    $res = DataUtils::getResultData($curlService->pro()->phphk()->put("amazon/ad/keywords/putKeywords/{$sellerId}",[$createParams]));
                    if ($res["status"] == "success" && isset($res['data']) && !empty($res['data'])) {
                        $this->log("!!!!!!!!!!!!!!!!!!!");
                        $resp = $curlService->pro()->s3023()->post("amazon_sp_keywords/updateBiddableKeywords", [
                            "id" => $keywordInfo['fromId'],
                            "isPassNotification" => "false",
                            "from" => "FIX_PAUSED_KEYWORD",
                            "updateParams" => [
                                "state" => $createParams['state'],
                                "status" => "2"
                            ],
                        ]);
                        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
                            $this->log("updateKeyword：成功：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['state']} - {$resp['result']['keyword']['keywordId']}");


                            $notificationId = $info['_id'];
                            $curlService->pro()->s3023()->delete("amazon_sp_notifications/{$notificationId}");
                            $this->log("删除notificationId：{$notificationId}");
                        }else{
                            $this->log("更新node失败了");
                        }

                    }else{
                        $this->log("调用接口:amazon/ad/keywords/putKeywords/{$sellerId}失败");
                    }
                }

            }
        }



        $this->dingTalk();


    }


    public function getAmazonSpKeywordList(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "关停keyword_3.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $exportList = [];
            foreach ($contentList as $info){
                $channel = $info['channel'];
                $sellerId = $channel;
                if ($channel == "amazon_us"){
                    $sellerId = "amazon";
                }
                $campaignId = ltrim($info['campaignId'], "'");
                $adGroupId = ltrim($info['adGroupId'], "'");

                $resp = DataUtils::getResultData($curlService->pro()->phphk()->post("amazon/ad/keywords/getKeywordsExtended/{$sellerId}", [
                    "campaignIdFilter" => $campaignId,
                    "adGroupIdFilter" => $adGroupId,
                    "stateFilter" => "enabled",
                    "keywordTextFilter" => $info['keywordText'],
                ]));
                if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                    foreach ($resp['data'] as $spInfo){
                        if ($spInfo['keywordText'] == $info['keywordText']){
                            $exportList[] = [
                                "_id" => $info['_id'],
                                "channel" => $channel,
                                "campaignId" => "'{$spInfo['campaignId']}",
                                "adGroupId" => "'{$spInfo['adGroupId']}",
                                "keywordId" => "'{$spInfo['keywordId']}",
                                "keywordText" => $spInfo['keywordText'],
                                "matchType" => $spInfo['matchType'],
                                "bid" => $spInfo['bid'],
                                "state" => $spInfo['state'],
                            ];
                        }
                    }
                }

            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "_id",
                    "channel",
                    "campaignId",
                    "adGroupId",
                    "keywordId",
                    "keywordText",
                    "matchType",
                    "bid",
                    "state"
                ], $exportList, "热词keyword投放_" . date("YmdHis") . ".xlsx");
            }
            $this->dingTalk();
        }

    }

    public function pausedAmazonSpKeywordList(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "关停keyword_4.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $info){
                $channel = $info['channel'];
                $sellerId = $channel;
                if ($channel == "amazon_us"){
                    $sellerId = "amazon";
                }
                $campaignId = ltrim($info['campaignId'], "'");
                $adGroupId = ltrim($info['adGroupId'], "'");
                $keywordId = ltrim($info['keywordId'], "'");

                $resp = DataUtils::getResultData($curlService->pro()->phphk()->post("amazon/ad/keywords/getKeywordsExtended/{$sellerId}", [
                    "campaignIdFilter" => $campaignId,
                    "adGroupIdFilter" => $adGroupId,
                    "keywordIdFilter" => $keywordId,
                    "stateFilter" => "enabled",
//                    "keywordTextFilter" => $info['keywordText'],
                ]));
                if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                    foreach ($resp['data'] as $spInfo){
                        if ($spInfo['keywordText'] == $info['keywordText']){
                            $uresp = DataUtils::getResultData($curlService->pro()->phphk()->put("amazon/ad/keywords/putKeywords/{$sellerId}", [[
                                "campaignId" => $spInfo['campaignId'],
                                "adGroupId" => $spInfo['adGroupId'],
                                "keywordId" => $spInfo['keywordId'],
                                "bid" => $spInfo['bid'],
                                "state" => "paused",
                            ]]));

                        }
                    }
                }

            }

            $this->dingTalk();
        }

    }


    public function deleteKeyword()
    {
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "关停keyword_5.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $item) {
                //$redisService->hSet("sp_update_adgroup_reload", $sellerId, json_encode($chunkAdgroupIds, JSON_UNESCAPED_UNICODE));
                $list = DataUtils::getResultData($curlService->pro()->s3023()->delete("amazon_sp_keywords/{$item['_id']}"));
                if (count($list) > 0) {

                }
            }
            $this->dingTalk();
        }
    }


    public function updateMongoAdGroupByAmazonAdGroup(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();

        $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_adgroups/queryPage", [
            "state" => "asmodifyBid",
            "limit" => 1000
        ]));
        if (count($list) > 0){
            foreach ($list as &$info){
                $sellerId = $info['channel'];
                if ($sellerId == "amazon_us"){
                    $sellerId = "amazon";
                }
                $resp = DataUtils::getResultData($curlService->pro()->phphk()->get("amazon/ad/adGroups/getAdGroupsExtend/{$sellerId}", [
                    "campaignIdFilter" => $info['campaignId'],
                    "adGroupIdFilter" => $info['adGroupId'],
                ]));
                if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                    foreach ($resp['data'] as $spInfo){
                        if ($spInfo['adGroupId'] == $info['adGroupId']){
                            $info['state'] = $spInfo['state'];
                            $info['defaultBid'] = $spInfo['defaultBid'];
                            $info['modifiedBy'] = "system(修复asmodifyBid的问题广告)";

                            $resp1 = $curlService->pro()->s3023()->post("amazon_sp_adgroups/updateAdGroups", [
                                "id" => $info['_id'],
                                "isPassNotification" => "false",
                                "from" => "system(修复asmodifyBid的问题广告)",
                                "updateParams" => [
                                    "state" => $spInfo['state'],
                                    "defaultBid" => $spInfo['defaultBid'],
                                    "modifiedBy" => "system(修复asmodifyBid的问题广告)",
                                    "status" => "2"
                                ],
                            ]);
                            if ($resp1['result'] && isset($resp1['result']['adgroup']) && count($resp1['result']['adgroup']) > 0) {
                                $this->log("updateAdGroup：成功：{$resp1['result']['adgroup']['channel']} - {$resp1['result']['adgroup']['adGroupName']} - {$resp1['result']['adgroup']['state']} - {$resp1['result']['adgroup']['adGroupId']} - {$resp1['result']['adgroup']['defaultBid']}");
                            }else{
                                $this->log("更新node失败了");
                            }

                        }
                    }
                }
            }

        }
    }

    public function updateMongoTargetByAmazonTarget(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();

        $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_targets/queryPage", [
            "state" => "asmodifyBid",
            "limit" => 1000
        ]));
        if (count($list) > 0){
            foreach ($list as &$info){
                $sellerId = $info['channel'];
                if ($sellerId == "amazon_us"){
                    $sellerId = "amazon";
                }
                $resp = DataUtils::getResultData($curlService->pro()->phphk()->get("amazon/ad/productTargeting/getTargets/{$sellerId}", [
                    "targetIdFilter" => $info['targetId']
                ]));
                if ($resp && isset($resp['data']) && count($resp['data']) > 0){
                    foreach ($resp['data'] as $spInfo){
                        if ($spInfo['targetId'] == $info['targetId']){
                            $resp1 = $curlService->pro()->s3023()->post("amazon_sp_targets/updateBiddableTargets", [
                                "id" => $info['_id'],
                                "isPassNotification" => "false",
                                "from" => "system(修复asmodifyBid的问题广告)",
                                "updateParams" => [
                                    "state" => $spInfo['state'],
                                    "bid" => $spInfo['bid'],
                                    "modifiedBy" => "system(修复asmodifyBid的问题广告)",
                                    "status" => "2"
                                ],
                            ]);
                            if ($resp1['result'] && isset($resp1['result']['target']) && count($resp1['result']['target']) > 0) {
                                $this->log("updateTarget：成功：{$resp1['result']['target']['channel']} - {$resp1['result']['target']['value']} - {$resp1['result']['target']['state']} - {$resp1['result']['target']['type']} - {$resp1['result']['target']['bid']}");
                            }else{
                                $this->log("更新node失败了");
                            }

                        }
                    }
                }
            }

        }
    }



    public function pausedProducts(){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "关停products.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $adIds = [];
            foreach ($contentList as $item){
                $adId = ltrim($item['adId'], "'");
                if (!empty($adId)){
                    $adIds[] = $adId;
                }
            }
            $adIds = array_unique($adIds);

            foreach (array_chunk($adIds,200) as $chunk){
                $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_products/queryPage", [
                    "adId_in" => implode(',', $chunk),
                    "limit" => 1000
                ]));
                if (count($list) > 0){
                    foreach ($list as &$info){
                        $info['state'] = "paused";
                        //查询notification里面当前_id主键待处理的广告数据,可能会有多个数据
                        $oldNotificationDataMap = $this->getNotificationDataElemMatch($info['_id']);
                        //对即将要更新的fromId里面，找出是否有符合当前更新条件的数据，如果有符合就不需要更新了，如果没有符合的就插入一次新的更新数据
                        $isUpdate = true;
                        if (count($oldNotificationDataMap) > 0){
                            foreach ($oldNotificationDataMap as $old){
                                if ($old['keywordId'] == $info['keywordId'] && $old['state'] == $info['state']){
                                    //如果有一模一样的更新，就不用再次插入了
                                    $isUpdate = false;
                                    break;
                                }
                            }
                        }

                        if (!$isUpdate){
                            $this->log("已有数据不必再插入");
                            continue;
                        }

                        $this->updateKeyword($info['_id'], array(
                            "status" => "1",
                            "messages" => "运维关停",
                            "state" => $info['state'],
                            "modifiedBy" => "system(zhouangang)",
                            "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                        ));
                    }
                }

            }

            $this->dingTalk();
        }

    }



}

$con = new SpUpdateAdGroupController();
//$con->pausedKeyword();
//$con->pausedKeywordNotification();
//$con->getAmazonSpKeywordList();
//$con->pausedAmazonSpKeywordList();
//$con->deleteKeyword();
//$con->updateMongoAdGroupByAmazonAdGroup();
//$con->updateMongoTargetByAmazonTarget();
$con->pausedProducts();