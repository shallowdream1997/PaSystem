<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedNKeywordAndNTargetByAdGroupController
{
    private $log;

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
            'title' => "【否定词广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 否定词广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function exportNegativeKeywordsAndNegativeTargets(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("/xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/export/sp/待处理_否定信息清单.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $sellerIdAdId = [];
            foreach ($contentList as $item){
                if (!empty($item['adgroupid'])){
                    $sellerId = $spApi->specialSellerIdReverseConver($item['sellerid']);
                    $sellerIdAdId[$sellerId][] = [
                        "adgroupid" => $item['adgroupid'],
                        "query" => $item['query'],
                    ];
                }
            }
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adGroupIdList){
                $sellerAdList = $redisService->hGetAll("spAdGroup_{$sellerId}");
                $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                $lastIds = [];
                $idWithAdId = [];
                foreach ($adGroupIdList as $adGroup){
                    if (!isset($sellerAdList[$adGroup['adgroupid']]) || !$sellerAdList[$adGroup['adgroupid']]){
                        $lastIds[] = $adGroup['adgroupid'];
                    }
                    $idWithAdId[] = [
                        "adGroupId" => $adGroup['adgroupid'],
                    ];
                }


                foreach (array_chunk($lastIds,200) as $chunk){
                    $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_adgroups/queryPage", [
                        "channel" => $spApi->specialSellerIdConver($sellerId),
                        "adGroupId_in" => implode(',', $chunk),
                        "limit" => 200
                    ]));
                    if (count($list) > 0){
                        foreach ($list as &$info){
                            $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                            $redisService->hSet("spAdGroup_{$seller}",$info['adGroupId'],$info['_id']);
                            $sellerAdList[$info['adGroupId']] = $info['_id'];
                        }
                    }
                }


                $negativeKeywordList = [];
                $negativeTargetList = [];
                if (count($idWithAdId) > 0){
                    foreach (array_chunk($idWithAdId,200) as $chunk){
                        $this->log(json_encode($chunk, JSON_UNESCAPED_UNICODE));
                        $adGroupIds = array_column($chunk,"adGroupId");
                        $negativeKeywordList = array_merge($negativeKeywordList,$spApi->listNegativeKeyword($sellerId,null,$adGroupIds,null));
                        $negativeTargetList = array_merge($negativeTargetList,$spApi->listNegativeTarget($sellerId,null,$adGroupIds));
                    }
                }

                foreach ($adGroupIdList as $adGroup){
                    foreach ($negativeKeywordList as $negativeKeyword){
                        if ($negativeKeyword['adGroupId'] == $adGroup['adgroupid'] && $negativeKeyword['keywordText'] == $adGroup['query']){
                            $d = [
                                "seller_id" => $sellerId,
                                "campaignId" => "'{$negativeKeyword['campaignId']}",
                                "adGroupId" => "'{$negativeKeyword['adGroupId']}",
                                "matchType" => "{$negativeKeyword['matchType']}",
                                "spId" => "'{$negativeKeyword['keywordId']}",
                                "text" => "{$negativeKeyword['keywordText']}",
                                "state" => "{$negativeKeyword['state']}",
                                "sp_type" => "negativeKeyword",
                            ];
                            $exportList[] = $d;
                            $redisService->hSet("spAdGroupNK_{$sellerId}",$negativeKeyword['keywordId'],json_encode($d, JSON_UNESCAPED_UNICODE));
                        }
                    }
                    foreach ($negativeTargetList as $negativeTarget){
                        if ($negativeTarget['adGroupId'] == $adGroup['adgroupid']){
                            foreach ($negativeTarget['expression'] as $expression){
                                if ($expression['value'] == $adGroup['query']){
                                    $d = [
                                        "seller_id" => $sellerId,
                                        "campaignId" => "'{$negativeTarget['campaignId']}",
                                        "adGroupId" => "'{$negativeTarget['adGroupId']}",
                                        "matchType" => "{$expression['type']}",
                                        "spId" => "{$negativeTarget['targetId']}",
                                        "text" => "{$expression['value']}",
                                        "state" => "{$negativeTarget['state']}",
                                        "sp_type" => "negativeTarget",
                                    ];
                                    $exportList[] = $d;
                                    $redisService->hSet("spAdGroupNT_{$sellerId}",$negativeTarget['targetId'],json_encode($d, JSON_UNESCAPED_UNICODE));
                                }
                            }
                        }
                    }
                }
            }





            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "campaignId",
                    "adGroupId",
                    "matchType",
                    "spId",
                    "text",
                    "state",
                    "sp_type",
                ], $exportList, "待关停否定词和否定target_" . date("YmdHis") . ".xlsx");
            }

        }
        $this->dingTalk();
    }


    public function pausedNegativeKeywordsAndNegativeTargets(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("/xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/export/sp/待关停否定词和否定target_20251121195516.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $sellerIdAdId = [];
            foreach ($contentList as $item) {
                if (!empty($item['spId'])) {
                    $sellerId = $spApi->specialSellerIdReverseConver($item['seller_id']);
                    $sellerIdAdId[$item['sp_type']][$sellerId][] = trim($item['spId'],"'");
                }
            }
            $exportList = [];
            foreach ($sellerIdAdId as $spType => $sellerIdList) {
                if ($spType == "negativeKeyword") {

                    foreach ($sellerIdList as $sellerId => $spIdList) {

                        $sellerAdList = $redisService->hGetAll("spNegativeKeyword_{$sellerId}");
                        $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                        $lastIds = [];
                        $idWithAdId = [];
                        foreach ($spIdList as $adId){
                            if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]){
                                $lastIds[] = $adId;
                            }
                            $idWithAdId[] = [
                                "keywordId" => $adId,
                                "state" => "paused"
                            ];
                        }


                        foreach (array_chunk($lastIds,200) as $chunk){
                            $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negativeKeywords/queryPage", [
                                "channel" => $spApi->specialSellerIdConver($sellerId),
                                "keywordId_in" => implode(',', $chunk),
                                "limit" => 200
                            ]));
                            if (count($list) > 0){
                                foreach ($list as &$info){
                                    $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                    $redisService->hSet("spNegativeKeyword_{$seller}",$info['keywordId'],$info['_id']);
                                    $sellerAdList[$info['keywordId']] = $info['_id'];
                                }
                            }
                        }


                        if (count($idWithAdId) > 0){
                            foreach (array_chunk($idWithAdId,200) as $chunk){
                                $this->log(json_encode($chunk, JSON_UNESCAPED_UNICODE));
                                $pausedAdIdResult = $spApi->updateNegativeKeyword($sellerId,$chunk);
                                if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0){
                                    //成功的adId；
                                    $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                                    foreach ($pausedAdIdResult['success'] as $keywordId){
                                        if (isset($sellerAdList[$keywordId]) && $sellerAdList[$keywordId]){
                                            $_id = $sellerAdList[$keywordId];
                                            $spApi->mongoUpdateNegativeKeyword($_id, $keywordId, "paused");
                                        }
                                    }
                                }
                                if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                                    //失败的adId
                                    $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                                    foreach ($pausedAdIdResult['error'] as $keywordId){
                                        $exportList[] = [
                                            "sellerId" => $sellerId,
                                            "keywordId" => "'" . $keywordId,
                                        ];
                                    }
                                }


                            }
                        }



                    }

                }


            }



            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "keywordId",
                ], $exportList, "关停失败的keywordId_" . date("YmdHis") . ".xlsx");
            }
        }

    }



}

$con = new SpPausedNKeywordAndNTargetByAdGroupController();
//$con->exportNegativeKeywordsAndNegativeTargets();
$con->pausedNegativeKeywordsAndNegativeTargets();