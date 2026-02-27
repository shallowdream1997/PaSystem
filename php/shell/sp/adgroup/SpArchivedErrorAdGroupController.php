<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpArchivedErrorAdGroupController
{
    private $log;

    private $redis;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    private function isCategory($campaignName) {
        // 转换为小写以便统一处理英文关键词
        $lowerName = strtolower($campaignName);

        // 检查是否包含 cate（这样也涵盖了 category）
        if (strpos($lowerName, 'cate') !== false) {
            return true;
        }

        // 检查是否包含中文“分类”
        if (strpos($campaignName, '分类') !== false) {
            return true;
        }

        return false;
    }


    private function isAsin($campaignName) {
        // 转换为小写以便统一处理英文关键词
        $lowerName = strtolower($campaignName);

        // 检查是否包含 cate（这样也涵盖了 category）
        if (strpos($lowerName, 'asin') !== false) {
            return true;
        }

        // 检查是否包含中文“分类”
        if (strpos($campaignName, '分类') !== false) {
            return true;
        }

        return false;
    }



    public function archivedErrorAdGroup(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/要归档的adgroupid广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $content){
                if (!$content["adgroupid"]){
                    continue;
                }
                $adGroupId = $content["adgroupid"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];


                $a = $this->redis->hGet("adGroupAdGroupId", $adGroupId);
                $adGroupInfo = [];
                if (!$a){
                    $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '',$adGroupId);
                    $this->redis->hSet("adGroupAdGroupId", $adGroupId, json_encode($adGroupInfo,JSON_UNESCAPED_UNICODE));
                }else{
                    $adGroupInfo = json_decode($a,true);
                }
                if ($adGroupInfo){
                    $adGroupName = $adGroupInfo['adGroupName'];
                    $this->log("{$sellerId} {$adGroupInfo['campaignId']} {$adGroupName}");

                    $a1 = $this->redis->hGet("productAdGroupId", $adGroupId);
                    $a2 = $this->redis->hGet("keywordAdGroupId", $adGroupId);
                    $a3 = $this->redis->hGet("negativeKeywordAdGroupId", $adGroupId);
                    $a4 = $this->redis->hGet("targetAdGroupId", $adGroupId);



                    //找到product
                    $products = [];
                    if (!$a1){
                        $products = $spApi->getMongoProductInfo($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId'],'',500);
                        $this->redis->hSet("productAdGroupId", $adGroupId, json_encode($products,JSON_UNESCAPED_UNICODE));
                    }else{
                        $products = json_decode($a1,true);
                    }
                    $this->log("product数量：" . count($products));
                    foreach ($products as $product){
                        //该删还是要删
                        $spApi->deleteMongoProductAdsInfo($product['_id']);
                        if (!$product['adId']){
                            $this->log("sku：{$product['sku']},没有adId");
                            continue;
                        }
                        $this->log("sku：{$product['sku']},adId：{$product['adId']}");
                    }

                    //找到keyword
                    $keywords = [];
                    if (!$a2){
                        $keywords = $spApi->getMongoKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("keywordAdGroupId", $adGroupId, json_encode($keywords,JSON_UNESCAPED_UNICODE));
                    }else{
                        $keywords = json_decode($a2,true);
                    }
                    $this->log("keyword数量：" . count($keywords));
                    foreach ($keywords as $keyword){
                        //该删还是要删
                        $spApi->deleteMongoKeywordInfo($keyword['_id']);
                        if (!$keyword['keywordId']){
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                    }


                    //找到negativeKeyword
                    $negativeKeywords = [];
                    if (!$a3){
                        $negativeKeywords = $spApi->getMongoNegativeKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("negativeKeywordAdGroupId", $adGroupId, json_encode($negativeKeywords,JSON_UNESCAPED_UNICODE));
                    }else{
                        $negativeKeywords = json_decode($a3,true);
                    }
                    $this->log("否定keyword数量：" . count($negativeKeywords));
                    foreach ($negativeKeywords as $keyword){
                        //该删还是要删
                        $spApi->deleteMongoNegativeKeywordInfo($keyword['_id']);
                        if (!$keyword['keywordId']){
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                    }

                    //找到target
                    $targets = [];
                    if (!$a4){
                        $targets = $spApi->getMongoTargetAsinV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("targetAdGroupId", $adGroupId, json_encode($targets,JSON_UNESCAPED_UNICODE));
                    }else{
                        $targets = json_decode($a4,true);
                    }
                    $this->log("target数量：" . count($targets));
                    foreach ($targets as $target){
                        //该删还是要删
                        $spApi->deleteMongoTargetInfo($target['_id']);
                        if (!$target['targetId']){
                            $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                            continue;
                        }
                        $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");
                    }

                    $spApi->deleteMongoAdGroupInfo($adGroupInfo['_id']);





                }else{
                    $this->log("没有adGroupInfo");
                }

            }

            $adGroupIds = [];
            foreach ($contentList as $content){
                if ($content['adgroupid']){
                    $adGroupIds[$content['seller_id']][] = $content['adgroupid'];
                }
            }
            if ($adGroupIds){
                $exportList = [];
                foreach ($adGroupIds as $sellerId=>$asgids){

                    foreach (array_chunk($asgids,100) as $adGroupIdsChunk){
                        $spApi = new SpApi();
                        $last = $spApi->archivedAdGroup($sellerId,$adGroupIdsChunk);
                        foreach ($last as $i){

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "adGroupId" => "'" . $i['adGroupId'],
                                "msg" => $i['msg'],
                            ];


                        }
                    }
                }



                if (count($exportList) > 0){
                    $excelUtils = new ExcelUtils("sp/");
                    $filePath = $excelUtils->downloadXlsx([
                        "sellerId",
                        "adGroupId",
                        "msg",
                    ], $exportList, "归档adGroupId结果_" . date("YmdHis") . ".xlsx");
                }


            }


        }

    }

    public function archivedErrorKeyword($channel){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/3号到4号的所有广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $masssdadads = [];
            foreach ($contentList as $content){
                if ($content['channel'] == $channel){
                    $masssdadads[] = $content;
                }
            }

            $keywordIds = [];
            $enabledKeywords = [];
            foreach ($masssdadads as $content){
                if (!$content["adgroupid"]){
                    continue;
                }
                $adGroupId = $content["adgroupid"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];


                $a = $this->redis->hGet("adGroupAdGroupId", $adGroupId);
                $adGroupInfo = [];
                if (!$a){
                    $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '',$adGroupId);
                    $this->redis->hSet("adGroupAdGroupId", $adGroupId, json_encode($adGroupInfo,JSON_UNESCAPED_UNICODE));
                }else{
                    $adGroupInfo = json_decode($a,true);
                }
                if ($adGroupInfo){
                    $adGroupName = $adGroupInfo['adGroupName'];
                    $this->log("{$sellerId} {$adGroupInfo['campaignId']} {$adGroupName}");

                    $a2 = $this->redis->hGet("keywordAdGroupId", $adGroupId);

                    //找到keyword
                    $keywords = [];
                    if (!$a2){
                        $keywords = $spApi->getMongoKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("keywordAdGroupId", $adGroupId, json_encode($keywords,JSON_UNESCAPED_UNICODE));
                    }else{
                        $keywords = json_decode($a2,true);
                    }
                    $this->log("keyword数量：" . count($keywords));
                    foreach ($keywords as $keyword){
                        if (in_array($keyword['createdBy'],[
                            "pa-nsq",
//                            "system(zhouangang)"
                        ])){
                            //只归档自动投放的那些错误数据，部分数据创建是人工投放的
                            //该删还是要删
                            $spApi->deleteMongoKeywordInfo($keyword['_id']);
                            if (!$keyword['keywordId']){
                                $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                                continue;
                            }
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                            $keywordIds[$sellerId][] = $keyword['keywordId'];
                        }else{
                            $enabledKeywords[] = [
                                "sellerId" => $sellerId,
                                "keywordId" => "'{$keyword['keywordId']}",
                                "keywordText" => $keyword['keywordText'],
                                "matchType" => $keyword['matchType'],
                                "createdBy" => $keyword['createdBy'],
                            ];
                        }

                    }



                }else{
                    $this->log("没有adGroupInfo");
                }

            }


            if ($keywordIds){
                $exportList = [];
                foreach ($keywordIds as $sellerId=>$asgids){

                    foreach (array_chunk($asgids,100) as $adGroupIdsChunk){
                        $spApi = new SpApi();
                        $last = $spApi->archivedKeyword($sellerId,$adGroupIdsChunk);
                        foreach ($last as $i){
                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "keywordId" => "'" . $i['keywordId'],
                                "msg" => $i['msg'],
                            ];
                        }
                    }
                }



                if (count($exportList) > 0){
                    $excelUtils = new ExcelUtils("sp/");
                    $filePath = $excelUtils->downloadXlsx([
                        "sellerId",
                        "keywordId",
                        "msg",
                    ], $exportList, "归档keywordId结果{$channel}_" . date("YmdHis") . ".xlsx");
                }


            }

            if ($enabledKeywords){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "sellerId",
                    "keywordId",
                    "keywordText",
                    "matchType",
                    "createdBy",
                ], $enabledKeywords, "好像是人工投放的keyword合集{$channel}_" . date("YmdHis") . ".xlsx");
            }

            (new RequestUtils("test"))->dingTalk("归档keyword{$channel}结束");

        }

    }


    public function reloadEnabledAdGroup($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/要归档的adgroupid广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $placement = [];
            foreach ($contentList as $content){
                if ($content['channel'] === $channel){
                    $placement[$content['channel']][$content['seller_id']][] = $content['scu_id'];
                }
            }

            foreach ($placement as $channel => $data){
                foreach ($data as $sellerId=>$scuIds){
                    $scuIds = array_unique($scuIds);
                    $this->log("channel: {$channel}，sellerId：{$sellerId}，scuIds：" . count($scuIds) . "个");

                    foreach ($scuIds as $scuId){
                        //auto
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 1,'auto','auto',$scuId);
                        //keyword
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 2,'manual','keyword',$scuId);
                        //asin
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 3,'manual','asin',$scuId);
                        //category
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 4,'manual','category',$scuId);
                    }
                }


            }
        }

    }


    public function reloadEnabledKeyword($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/3号到4号的所有广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $placement = [];
            foreach ($contentList as $content){
                if ($content['channel'] === $channel){
                    $placement[$content['channel']][$content['seller_id']][] = $content['scu_id'];
                }
            }

            foreach ($placement as $channel => $data){
                foreach ($data as $sellerId=>$scuIds){
                    $scuIds = array_unique($scuIds);
                    $this->log("channel: {$channel}，sellerId：{$sellerId}，scuIds：" . count($scuIds) . "个");

                    foreach ($scuIds as $scuId){
                        //keyword
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 2,'manual','keyword',$scuId);
                    }
                }


            }
            (new RequestUtils("test"))->dingTalk("重新投放keyword{$channel}结束");
        }

    }


    /**
     * 归档错误广告 - auto版
     * ①直接对adgroupid广告下的keyword，删除
     * ②查看有无target-asin或category，如果有则删除
     * ③把上述的数据分别迁移到相对于的campaign广告下的adgroupid
     * @return void
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function archivedErrorAdGroupV2($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/8-19号错误的自动化广告_auto广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $masssdadads = [];
            foreach ($contentList as $content) {
                if ($content['channel'] == $channel) {
                    $masssdadads[] = $content;
                }
            }

            $canArchivedAdGroupData = [];
            $mustArchivedKeywordIds = [];
            $mustArchivedNegativeKeywordIds = [];
            $mustArchivedTargetIds = [];

            $mustDeleteKeywordIds = [];
            $mustDeleteTargetIds = [];
            $mustDeleteNegativeKeywordIds = [];

            foreach ($masssdadads as $content) {
                if (!$content["adgroup_id"]) {
                    continue;
                }
                $adGroupId = $content["adgroup_id"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];
                $scuIdsKeywordAsinCategory['keyword'] = [];
                $scuIdsKeywordAsinCategory['negativeKeyword'] = [];
                $scuIdsKeywordAsinCategory['asinCategorySameAs'] = [];
                $scuIdsKeywordAsinCategory['asinSameAs'] = [];
                $scuIdsKeywordAsinCategory['scu'] = $content['scu'];
                $scuIdsKeywordAsinCategory['sellerId'] = $sellerId;
                $scuIdsKeywordAsinCategory['channel'] = $channel;
                $a = $this->redis->hGet("adGroupAdGroupId", $adGroupId);
                $adGroupInfo = [];
                if (!$a) {
                    $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '', $adGroupId);
                    $this->redis->hSet("adGroupAdGroupId", $adGroupId, json_encode($adGroupInfo, JSON_UNESCAPED_UNICODE));
                } else {
                    $adGroupInfo = json_decode($a, true);
                }

                if ($adGroupInfo) {
                    $adGroupName = $adGroupInfo['adGroupName'];
                    $this->log("{$sellerId} {$adGroupInfo['campaignId']} {$adGroupName}");


                    $a2 = $this->redis->hGet("keywordAdGroupId", $adGroupId);
                    $a3 = $this->redis->hGet("negativeKeywordAdGroupId", $adGroupId);
                    $a4 = $this->redis->hGet("targetAdGroupId", $adGroupId);


                    //找到keyword
                    $keywords = [];
                    if (!$a2) {
                        $keywords = $spApi->getMongoKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("keywordAdGroupId", $adGroupId, json_encode($keywords, JSON_UNESCAPED_UNICODE));
                    } else {
                        $keywords = json_decode($a2, true);
                    }


                    //找到negativeKeyword
                    $negativeKeywords = [];
                    if (!$a3) {
                        $negativeKeywords = $spApi->getMongoNegativeKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("negativeKeywordAdGroupId", $adGroupId, json_encode($negativeKeywords, JSON_UNESCAPED_UNICODE));
                    } else {
                        $negativeKeywords = json_decode($a3, true);
                    }


                    //找到target
                    $targets = [];
                    if (!$a4) {
                        $targets = $spApi->getMongoTargetAsinV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("targetAdGroupId", $adGroupId, json_encode($targets, JSON_UNESCAPED_UNICODE));
                    } else {
                        $targets = json_decode($a4, true);
                    }


                    $this->log("keyword数量：" . count($keywords));
                    foreach ($keywords as $keyword) {
                        $scuIdsKeywordAsinCategory['keyword'][] = [
                            'matchType' => $keyword['matchType'],
                            'keywordText' => $keyword['keywordText'],
                            'bid' => $keyword['bid'],
                        ];
                        $mustDeleteKeywordIds[] = $keyword['_id'];
                        if (!$keyword['keywordId']) {
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                        $mustArchivedKeywordIds[$sellerId][] = $keyword['keywordId'];
                    }


                    $this->log("否negativeKeyword数量：" . count($negativeKeywords));

                    foreach ($negativeKeywords as $keyword) {
                        $scuIdsKeywordAsinCategory['negativeKeyword'][] = [
                            'matchType' => $keyword['matchType'],
                            'keywordText' => $keyword['keywordText']
                        ];
                        $mustDeleteNegativeKeywordIds[] = $keyword['_id'];
                        if (!$keyword['keywordId']) {
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");

                        $mustArchivedNegativeKeywordIds[$sellerId][] = $keyword['keywordId'];
                    }


                    $this->log("target数量：" . count($targets));
                    foreach ($targets as $target) {
                        if ($target['type'] == "asinCategorySameAs") {
                            $mustDeleteTargetIds[] = $target['_id'];
                            $scuIdsKeywordAsinCategory['asinCategorySameAs'][] = [
                                'type' => $target['type'],
                                'value' => $target['value'],
                                'bid' => $target['bid'],
                                'targetName' => $target['targetName'],
                            ];
                            if (!$target['targetId']) {
                                $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                                continue;
                            }
                            $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");
                            $mustArchivedTargetIds[$sellerId][] = $target['targetId'];
                        } else if ($target['type'] == "asinSameAs") {
                            $mustDeleteTargetIds[] = $target['_id'];
                            $scuIdsKeywordAsinCategory['asinSameAs'][] = [
                                'type' => $target['type'],
                                'value' => $target['value'],
                                'bid' => $target['bid'],
                                'targetName' => $target['targetName'],
                            ];
                            if (!$target['targetId']) {
                                $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                                continue;
                            }
                            $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");

                            $mustArchivedTargetIds[$sellerId][] = $target['targetId'];
                        }

                    }


                } else {
                    $this->log("没有adGroupInfo");

                }

                $canArchivedAdGroupData[] = $scuIdsKeywordAsinCategory;

            }


            if ($canArchivedAdGroupData) {
                //这里面凡是有数据的，都删除归档，要迁移到同scu的其他,先放在这里

                foreach ($canArchivedAdGroupData as $item) {
                    $channel = $item['channel'];
                    $scu = $item['scu'];
                    $sellerId = $item['sellerId'];
                    if ($item['keyword']) {
                        $d = $this->redis->hGet("KeywordPlacement_{$sellerId}", $scu);
                        $keywordData = [];
                        if (!empty($d)) {
                            $keywordData = json_decode($d, true);
                        }
                        $keywordData = array_merge($keywordData, $item['keyword']);

                        if (count($keywordData) > 0 && DataUtils::hasDuplicates($keywordData)) {
                            $this->log("有重复的keyword");
                            $keywordData = DataUtils::clearRepeatData($keywordData);
                        }
                        $this->redis->hSet("KeywordPlacement_{$sellerId}", $scu, json_encode($keywordData, JSON_UNESCAPED_UNICODE));
                    }


                    if ($item['negativeKeyword']) {
                        $d = $this->redis->hGet("NegativeKeywordPlacement_{$sellerId}", $scu);
                        $negativeKeywordData = [];
                        if (!empty($d)) {
                            $negativeKeywordData = json_decode($d, true);
                        }
                        $negativeKeywordData = array_merge($negativeKeywordData, $item['negativeKeyword']);
                        if (count($negativeKeywordData) > 0 && DataUtils::hasDuplicates($negativeKeywordData)) {
                            $this->log("有重复的negativeKeyword");
                            $negativeKeywordData = DataUtils::clearRepeatData($negativeKeywordData);
                        }
                        $this->redis->hSet("NegativeKeywordPlacement_{$sellerId}", $scu, json_encode($negativeKeywordData, JSON_UNESCAPED_UNICODE));
                    }


                    if ($item['asinCategorySameAs']) {
                        $d = $this->redis->hGet("AsinCategorySameAsPlacement_{$sellerId}", $scu);
                        $asinCategorySameAsData = [];
                        if (!empty($d)) {
                            $asinCategorySameAsData = json_decode($d, true);
                        }
                        $asinCategorySameAsData = array_merge($asinCategorySameAsData, $item['asinCategorySameAs']);
                        if (count($asinCategorySameAsData) > 0 && DataUtils::hasDuplicates($asinCategorySameAsData)) {
                            $this->log("有重复的asinCategorySameAs");
                            $asinCategorySameAsData = DataUtils::clearRepeatData($asinCategorySameAsData);
                        }
                        $this->redis->hSet("AsinCategorySameAsPlacement_{$sellerId}", $scu, json_encode($asinCategorySameAsData, JSON_UNESCAPED_UNICODE));
                    }

                    if ($item['asinSameAs']) {
                        $d = $this->redis->hGet("AsinSameAsPlacement_{$sellerId}", $scu);
                        $asinSameAsData = [];
                        if (!empty($d)) {
                            $asinSameAsData = json_decode($d, true);
                        }
                        $asinSameAsData = array_merge($asinSameAsData, $item['asinSameAs']);
                        if (count($asinSameAsData) > 0 && DataUtils::hasDuplicates($asinSameAsData)) {
                            $this->log("有重复的asinSameAs");
                            $asinSameAsData = DataUtils::clearRepeatData($asinSameAsData);
                        }
                        $this->redis->hSet("AsinSameAsPlacement_{$sellerId}", $scu, json_encode($asinSameAsData, JSON_UNESCAPED_UNICODE));
                    }
                }

                $handle = false;
                if (!$handle) {
                    exit("不处理");
                }
                exit("处理");
                $exportList = [];
                $exportList = $this->archivedSP($mustArchivedKeywordIds, "keyword", $exportList);
                $exportList = $this->archivedSP($mustArchivedNegativeKeywordIds, "negativeKeyword", $exportList);
                $exportList = $this->archivedSP($mustArchivedTargetIds, "target", $exportList);
                if ($exportList) {
                    $this->log("开始导出");

                    $excelUtils = new ExcelUtils("sp/");
                    $filePath = $excelUtils->downloadXlsx([
                        "sellerId",
                        "type",
                        "spId",
                        "msg"
                    ], $exportList, "归档结果_{$channel}_" . date("YmdHis") . ".xlsx");

                }

                //删除mongo数据
                $this->log("开始删除mongo数据");
                $this->mongoDeleteSP($mustDeleteKeywordIds, "keyword");
                $this->mongoDeleteSP($mustDeleteNegativeKeywordIds, "negativeKeyword");
                $this->mongoDeleteSP($mustDeleteTargetIds, "target");


                (new RequestUtils("test"))->dingTalk("归档Auto广告的错误的数据{$channel}结束");
            }


        }

    }


    /**
     * 归档错误广告 - keyword广告版
     * @param $channel
     * @return void
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function archivedErrorAdGroupV3($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/8-19号错误的自动化广告_keyword广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $masssdadads = [];
            foreach ($contentList as $content) {
                if ($content['channel'] == $channel) {
                    $masssdadads[] = $content;
                }
            }

            $canArchivedAdGroupData = [];
            $mustArchivedKeywordIds = [];
            $mustArchivedNegativeKeywordIds = [];
            $mustArchivedTargetIds = [];

            $mustDeleteKeywordIds = [];
            $mustDeleteTargetIds = [];
            $mustDeleteNegativeKeywordIds = [];

            foreach ($masssdadads as $content) {
                if (!$content["adgroup_id"]) {
                    continue;
                }
                $adGroupId = $content["adgroup_id"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];
                $scuIdsKeywordAsinCategory['keyword'] = [];
                $scuIdsKeywordAsinCategory['negativeKeyword'] = [];
                $scuIdsKeywordAsinCategory['asinCategorySameAs'] = [];
                $scuIdsKeywordAsinCategory['asinSameAs'] = [];
                $scuIdsKeywordAsinCategory['scu'] = $content['scu'];
                $scuIdsKeywordAsinCategory['sellerId'] = $sellerId;
                $scuIdsKeywordAsinCategory['channel'] = $channel;
                $a = $this->redis->hGet("adGroupAdGroupId", $adGroupId);
                $adGroupInfo = [];
                if (!$a) {
                    $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '', $adGroupId);
                    $this->redis->hSet("adGroupAdGroupId", $adGroupId, json_encode($adGroupInfo, JSON_UNESCAPED_UNICODE));
                } else {
                    $adGroupInfo = json_decode($a, true);
                }

                if ($adGroupInfo) {
                    $adGroupName = $adGroupInfo['adGroupName'];
                    $this->log("{$sellerId} {$adGroupInfo['campaignId']} {$adGroupName}");


                    $a2 = $this->redis->hGet("keywordAdGroupId", $adGroupId);
                    $a3 = $this->redis->hGet("negativeKeywordAdGroupId", $adGroupId);
                    $a4 = $this->redis->hGet("targetAdGroupId", $adGroupId);


                    //找到keyword
                    $keywords = [];
                    if (!$a2) {
                        $keywords = $spApi->getMongoKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("keywordAdGroupId", $adGroupId, json_encode($keywords, JSON_UNESCAPED_UNICODE));
                    } else {
                        $keywords = json_decode($a2, true);
                    }


                    //找到negativeKeyword
                    $negativeKeywords = [];
                    if (!$a3) {
                        $negativeKeywords = $spApi->getMongoNegativeKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("negativeKeywordAdGroupId", $adGroupId, json_encode($negativeKeywords, JSON_UNESCAPED_UNICODE));
                    } else {
                        $negativeKeywords = json_decode($a3, true);
                    }


                    //找到target
                    $targets = [];
                    if (!$a4) {
                        $targets = $spApi->getMongoTargetAsinV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("targetAdGroupId", $adGroupId, json_encode($targets, JSON_UNESCAPED_UNICODE));
                    } else {
                        $targets = json_decode($a4, true);
                    }


                    $this->log("keyword数量：" . count($keywords));
                    foreach ($keywords as $keyword) {
                        $scuIdsKeywordAsinCategory['keyword'][] = [
                            'matchType' => $keyword['matchType'],
                            'keywordText' => $keyword['keywordText'],
                            'bid' => $keyword['bid'],
                        ];
                        $mustDeleteKeywordIds[] = $keyword['_id'];
                        if (!$keyword['keywordId']) {
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                        $mustArchivedKeywordIds[$sellerId][] = $keyword['keywordId'];
                    }


                    $this->log("否negativeKeyword数量：" . count($negativeKeywords));

                    foreach ($negativeKeywords as $keyword) {
                        $scuIdsKeywordAsinCategory['negativeKeyword'][] = [
                            'matchType' => $keyword['matchType'],
                            'keywordText' => $keyword['keywordText']
                        ];
                        $mustDeleteNegativeKeywordIds[] = $keyword['_id'];
                        if (!$keyword['keywordId']) {
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");

                        $mustArchivedNegativeKeywordIds[$sellerId][] = $keyword['keywordId'];
                    }


                    $this->log("target数量：" . count($targets));
                    foreach ($targets as $target) {
                        if ($target['type'] == "asinCategorySameAs") {
                            $mustDeleteTargetIds[] = $target['_id'];
                            $scuIdsKeywordAsinCategory['asinCategorySameAs'][] = [
                                'type' => $target['type'],
                                'value' => $target['value'],
                                'bid' => $target['bid'],
                                'targetName' => $target['targetName'],
                            ];
                            if (!$target['targetId']) {
                                $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                                continue;
                            }
                            $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");
                            $mustArchivedTargetIds[$sellerId][] = $target['targetId'];
                        } else if ($target['type'] == "asinSameAs") {
                            $mustDeleteTargetIds[] = $target['_id'];
                            $scuIdsKeywordAsinCategory['asinSameAs'][] = [
                                'type' => $target['type'],
                                'value' => $target['value'],
                                'bid' => $target['bid'],
                                'targetName' => $target['targetName'],
                            ];
                            if (!$target['targetId']) {
                                $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                                continue;
                            }
                            $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");

                            $mustArchivedTargetIds[$sellerId][] = $target['targetId'];
                        }

                    }


                } else {
                    $this->log("没有adGroupInfo");

                }

                $canArchivedAdGroupData[] = $scuIdsKeywordAsinCategory;

            }


            if ($canArchivedAdGroupData) {
                //这里面凡是有数据的，都删除归档，要迁移到同scu的其他,先放在这里

                foreach ($canArchivedAdGroupData as $item) {
                    $channel = $item['channel'];
                    $scu = $item['scu'];
                    $sellerId = $item['sellerId'];
                    if ($item['keyword']) {
                        $d = $this->redis->hGet("KeywordPlacement_{$sellerId}", $scu);
                        $keywordData = [];
                        if (!empty($d)) {
                            $keywordData = json_decode($d, true);
                        }
                        $keywordData = array_merge($keywordData, $item['keyword']);

                        if (count($keywordData) > 0 && DataUtils::hasDuplicates($keywordData)) {
                            $this->log("有重复的keyword");
                            $keywordData = DataUtils::clearRepeatData($keywordData);
                        }
                        $this->redis->hSet("KeywordPlacement_{$sellerId}", $scu, json_encode($keywordData, JSON_UNESCAPED_UNICODE));
                    }


                    if ($item['negativeKeyword']) {
                        $d = $this->redis->hGet("NegativeKeywordPlacement_{$sellerId}", $scu);
                        $negativeKeywordData = [];
                        if (!empty($d)) {
                            $negativeKeywordData = json_decode($d, true);
                        }
                        $negativeKeywordData = array_merge($negativeKeywordData, $item['negativeKeyword']);
                        if (count($negativeKeywordData) > 0 && DataUtils::hasDuplicates($negativeKeywordData)) {
                            $this->log("有重复的negativeKeyword");
                            $negativeKeywordData = DataUtils::clearRepeatData($negativeKeywordData);
                        }
                        $this->redis->hSet("NegativeKeywordPlacement_{$sellerId}", $scu, json_encode($negativeKeywordData, JSON_UNESCAPED_UNICODE));
                    }


                    if ($item['asinCategorySameAs']) {
                        $d = $this->redis->hGet("AsinCategorySameAsPlacement_{$sellerId}", $scu);
                        $asinCategorySameAsData = [];
                        if (!empty($d)) {
                            $asinCategorySameAsData = json_decode($d, true);
                        }
                        $asinCategorySameAsData = array_merge($asinCategorySameAsData, $item['asinCategorySameAs']);
                        if (count($asinCategorySameAsData) > 0 && DataUtils::hasDuplicates($asinCategorySameAsData)) {
                            $this->log("有重复的asinCategorySameAs");
                            $asinCategorySameAsData = DataUtils::clearRepeatData($asinCategorySameAsData);
                        }
                        $this->redis->hSet("AsinCategorySameAsPlacement_{$sellerId}", $scu, json_encode($asinCategorySameAsData, JSON_UNESCAPED_UNICODE));
                    }

                    if ($item['asinSameAs']) {
                        $d = $this->redis->hGet("AsinSameAsPlacement_{$sellerId}", $scu);
                        $asinSameAsData = [];
                        if (!empty($d)) {
                            $asinSameAsData = json_decode($d, true);
                        }
                        $asinSameAsData = array_merge($asinSameAsData, $item['asinSameAs']);
                        if (count($asinSameAsData) > 0 && DataUtils::hasDuplicates($asinSameAsData)) {
                            $this->log("有重复的asinSameAs");
                            $asinSameAsData = DataUtils::clearRepeatData($asinSameAsData);
                        }
                        $this->redis->hSet("AsinSameAsPlacement_{$sellerId}", $scu, json_encode($asinSameAsData, JSON_UNESCAPED_UNICODE));
                    }
                }

                $handle = false;
                if (!$handle) {
                    exit("不处理");
                }
                exit("处理");
                $exportList = [];
                $exportList = $this->archivedSP($mustArchivedKeywordIds, "keyword", $exportList);
                $exportList = $this->archivedSP($mustArchivedNegativeKeywordIds, "negativeKeyword", $exportList);
                $exportList = $this->archivedSP($mustArchivedTargetIds, "target", $exportList);
                if ($exportList) {
                    $this->log("开始导出");

                    $excelUtils = new ExcelUtils("sp/");
                    $filePath = $excelUtils->downloadXlsx([
                        "sellerId",
                        "type",
                        "spId",
                        "msg"
                    ], $exportList, "归档结果_{$channel}_" . date("YmdHis") . ".xlsx");

                }

                //删除mongo数据
                $this->log("开始删除mongo数据");
                $this->mongoDeleteSP($mustDeleteKeywordIds, "keyword");
                $this->mongoDeleteSP($mustDeleteNegativeKeywordIds, "negativeKeyword");
                $this->mongoDeleteSP($mustDeleteTargetIds, "target");


                (new RequestUtils("test"))->dingTalk("归档Auto广告的错误的数据{$channel}结束");
            }


        }

    }

    public function archivedSP($ids, $type, $exportList = [])
    {
        switch ($type) {
            case "adGroup":
                $this->log("开始归档adGroup");
                foreach ($ids as $sellerId => $asgids) {

                    foreach (array_chunk($asgids, 100) as $idsChunk) {
                        $spApi = new SpApi();
                        $last = $spApi->archivedAdGroup($sellerId, $idsChunk);
                        foreach ($last as $i) {

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "type" => $type,
                                "spId" => "'" . $i['adGroupId'],
                                "msg" => $i['msg'],
                            ];

                        }
                    }
                }
                break;
            case "keyword":
                $this->log("开始归档keyword");
                foreach ($ids as $sellerId => $asgids) {

                    foreach (array_chunk($asgids, 100) as $idsChunk) {
                        $spApi = new SpApi();
                        $last = $spApi->archivedKeyword($sellerId, $idsChunk);
                        foreach ($last as $i) {

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "type" => $type,
                                "spId" => "'" . $i['keywordId'],
                                "msg" => $i['msg'],
                            ];
                        }
                    }
                }
                break;
            case "negativeKeyword":
                $this->log("开始归档negativeKeyword");

                foreach ($ids as $sellerId => $asgids) {

                    foreach (array_chunk($asgids, 100) as $idsChunk) {
                        $spApi = new SpApi();
                        $last = $spApi->archivedNegativeKeyword($sellerId, $idsChunk);
                        foreach ($last as $i) {

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "type" => $type,
                                "spId" => "'" . $i['keywordId'],
                                "msg" => $i['msg'],
                            ];
                        }
                    }
                }
                break;
            case "target":
                $this->log("开始归档target");
                foreach ($ids as $sellerId => $asgids) {

                    foreach (array_chunk($asgids, 100) as $idsChunk) {
                        $spApi = new SpApi();
                        $last = $spApi->archivedTarget($sellerId, $idsChunk);
                        foreach ($last as $i) {

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "type" => $type,
                                "spId" => "'" . $i['targetId'],
                                "msg" => $i['msg'],
                            ];
                        }
                    }
                }
                break;
        }

        return $exportList;
    }

    public function mongoDeleteSP($ids,$type)
    {
        switch ($type) {
            case "adGroup":
                $spApi = new SpApi();
                foreach ($ids as $id){
                    $spApi->deleteMongoAdGroupInfo($id);
                }
                break;
            case "keyword":
                $spApi = new SpApi();
                foreach ($ids as $id){
                    $spApi->deleteMongoKeywordInfo($id);
                }
                break;
            case "negativeKeyword":
                $spApi = new SpApi();
                foreach ($ids as $id){
                    $spApi->deleteMongoNegativeKeywordInfo($id);
                }
                break;
            case "target":
                $spApi = new SpApi();
                foreach ($ids as $id){
                    $spApi->deleteMongoTargetInfo($id);
                }
                break;
        }
    }

}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
if (isset($params['channel']) && trim($params['channel'] != '')) {
    $channel = $params['channel'];
}
$con = new SpArchivedErrorAdGroupController();
//$con->archivedErrorAdGroup();



//$con->archivedErrorKeyword($channel);
//$con->reloadEnabledKeyword($channel);
//$con->reloadEnabledAdGroup($channel);

$con->archivedErrorAdGroupV2($channel);