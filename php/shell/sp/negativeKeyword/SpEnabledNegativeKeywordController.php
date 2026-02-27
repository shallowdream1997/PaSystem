<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpEnabledNegativeKeywordController
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
            'title' => "【否定词广告写入开启完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 否定词广告写入开启完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function enabledNegativeKeywords(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("/xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/export/sp/negativeKeyword/11-25开广告keyword.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $sellerIdAdId = [];
            foreach ($contentList as $item){
                if (!empty($item['keywordid'])){
                    $sellerId = $spApi->specialSellerIdReverseConver($item['channel']);
                    $sellerIdAdId[$sellerId][] = $item['keywordid'];
                }
            }
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
                $sellerAdList = $redisService->hGetAll("spNegativeKeyword_{$sellerId}");
                $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                $lastIds = [];
                $idWithAdId = [];
                foreach ($adIds as $adId){
                    if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]){
                        $lastIds[] = $adId;
                    }
                    $idWithAdId[] = [
                        "keywordId" => $adId,
                        "state" => "enabled"
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
                            $this->log("{$sellerId} 开启成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $keywordId){
                                if (isset($sellerAdList[$keywordId]) && $sellerAdList[$keywordId]){
                                    $_id = $sellerAdList[$keywordId];
                                    $spApi->mongoUpdateNegativeKeyword($_id, $keywordId, "enabled");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 开启失败: " . count($pausedAdIdResult['error']) . "个");
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

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "keywordId",
                ], $exportList, "开启失败的keywordId_" . date("YmdHis") . ".xlsx");
            }

        }
        $this->dingTalk();
    }



}

$con = new SpEnabledNegativeKeywordController();
$con->enabledNegativeKeywords();