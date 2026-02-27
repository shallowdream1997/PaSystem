<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 修复查询PA资呈广告数据
 * Class SyncCurlController
 */
class FixPaSkuMaterialSpDataController
{
    /**
     * @var CurlService
     */
    public CurlService $curlService;
    private MyLogger $log;
    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("fix_pa_sku_material_sp_data/data");

        $curlService = (new CurlService())->pro();
        $this->curlService = $curlService;


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

    public function findPaSkuMaterialSpData()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/findSkuMaterial.xlsx");
        if (sizeof($fileFitContent) > 0) {

            //(new RequestUtils("pro"))->getEmployeeByCompanySequenceId(CR201706080001);
            //(new RequestUtils("pro"))->getEmployeeByCompanySequenceId(CR201706080003);
            //(new RequestUtils("pro"))->getEmployeeByCompanySequenceId(CR201706060001);

            $redisUserNameMap = $this->redis->hGetAll(REDIS_USER_NAME_KEY);
            $userNameMap = [];
            foreach ($redisUserNameMap as $key => $value){
                $item = json_decode($value,true);
                $userNameMap[$key] = $item['cName'];
            }


            $ceSkuMap = [];
//            $ceBillNoList = array_column($fileFitContent,"CE单号");
//            foreach (array_chunk($ceBillNoList,10) as $chunk){
//                $resp = DataUtils::getPageDocList($this->curlService->s3044()->get("pa_sku_materials/queryPage", [
//                    "ceBillNo_in"=>implode(",",$chunk),
//                    "limit" => 10000,
//                ]));
//                if ($resp) {
//                    foreach ($resp as $item){
//                        $isFirstSku = "否";
//                        if (empty($item['parentSkuId'])){
//                            //空的就是父，属于第一个sku
//                            $isFirstSku = "是";
//                        }
//                        $ceSkuMap[$item['ceBillNo']][$item['skuId']]['isCeFirstSku'] = $isFirstSku;
//                        $this->redis->hSet("ceSkuMaterialMapIsCeFirstSku", "{$item['ceBillNo']}_{$item['skuId']}",json_encode([
//                            "ceBillNo" => $item['ceBillNo'],
//                            "skuId" => $item['skuId'],
//                            "isCeFirstSku" => $isFirstSku,
//                        ],JSON_UNESCAPED_UNICODE));
//                    }
//                }
//            }

            $strCeSkuMaterialMapIsCeFirstSku = $this->redis->hGetAll("ceSkuMaterialMapIsCeFirstSku");
            $skuIdList = [];
            foreach ($strCeSkuMaterialMapIsCeFirstSku as $key => $value){
                $item = json_decode($value,true);
                $skuIdList[] = $item['skuId'];
                $ceSkuMap[$item['ceBillNo']][$item['skuId']]['isCeFirstSku'] = $item['isCeFirstSku'];
            }


//            $this->saveCeSkuMaterialMapAllBasicInfo($skuIdList,$userNameMap);
//
//            $this->saveCeSkuMaterialMapInProductSku($skuIdList,$userNameMap);
//
//            $this->saveCeSkuMaterialMapInFba($skuIdList);

//            $this->saveCeSkuMaterialMapInFbaSeller($skuIdList);
//
//            $this->saveCeSkuMaterialMapInNonFba($skuIdList);
//
//            $this->saveCeSkuMaterialMapInFbaAsinListing($skuIdList);
//
//            $this->saveCeSkuMaterialMapInFbaAsinAdGroup($skuIdList);


            $this->log("开始");
            $this->exportExcelData();


        }

    }

    private function exportExcelData()
    {
        $strCeSkuMaterialMapIsCeFirstSku = $this->redis->hGetAll("ceSkuMaterialMapIsCeFirstSku");


        $lastExportDataList = [];
        foreach ($strCeSkuMaterialMapIsCeFirstSku as $key => $value){
            $tree = json_decode($value,true);

            $exportData = [];
            //CE
            $exportData["ceBillNo"] = $tree['ceBillNo'];
            //sku
            $exportData["sku"] = $tree['skuId'];


            $productInfoStr = $this->redis->hGet("ceSkuMaterialMapInProductSku", $tree['skuId']);
            $productInfo = [];
            if ($productInfoStr){
                $productInfo = json_decode($productInfoStr, true);
            }
            //中文分类全路径
            $exportData["categoryFullPath"] = $productInfo['categoryFullPath'] ?? "";
            //是否同CE单首个sku
            $exportData["isCeFirstSku"] = $tree['isCeFirstSku'];

            $ppm3InfoStr = $this->redis->hGet("ceSkuMaterialMapAllBasicInfo", $tree['skuId']);
            $ppm3Info = [];
            if ($ppm3InfoStr){
                $ppm3Info = json_decode($ppm3InfoStr, true);
            }
            //产品分级
            $exportData["product_level"] = $ppm3Info['product_level'] ?? "";

            //运营人员
            $exportData["salesUserName"] = $productInfo['salesUserName'] ?? "";

            //sku的FBA
            $aFbaStr = $this->redis->hGet("ceSkuMaterialMapInFba", $tree['skuId']);
            $aFba = [];
            if ($aFbaStr){
                $aFba = json_decode($aFbaStr,true);
            }

            //sku的NonFba
            $aNonFbaStr = $this->redis->hGet("ceSkuMaterialMapInNonFba", $tree['skuId']);
            $aNonFbaMap = [];
            if ($aNonFbaStr){
                $aNonFba = json_decode($aNonFbaStr,true);
                foreach ($aNonFba as $channel => $aNonFbaItem){
                    $aNonFbaMap[$tree['skuId'].$channel] = $aNonFbaItem;
                }
            }


            if ($aFba){
                //有FBA
                foreach ($aFba as $channel => $aFbaItem){
                    $fbaExportData = $exportData;
                    $key = $aFbaItem.$channel;
                    $sellerStr = $this->redis->hGet("ceSkuMaterialMapInFbaSeller", $key);
                    $sellerId = "";
                    if ($sellerStr){
                        $sellerInfo = json_decode($sellerStr,true);
                        $sellerId = $sellerInfo['sellerId']??"";
                    }


                    //统计上架的a号
                    $amazonActiveListingStr = $this->redis->hGet("ceSkuMaterialMapInFbaAsinListing", $key);
                    $fbaAsin = "";
                    if ($amazonActiveListingStr){
                        $amazonActiveListing = json_decode($amazonActiveListingStr,true);
                    }
                    if ($amazonActiveListing && count($amazonActiveListing)>0){
                        //有上架，获取asin
                        $fbaAsin = $amazonActiveListing[0]['asin'];
                    }

                    //nonFba
                    $key2 = $tree['skuId'].$channel;

                    $fbaExportData['channel'] = $channel;
                    $fbaExportData['sellerId'] = $sellerId;
                    $fbaExportData['fba'] = $aFbaItem;
                    $fbaExportData['fbaAsin'] = $fbaAsin;
                    $fbaExportData['nonFba'] = $aNonFbaMap[$key2]??"";


                    //广告类型
                    $adKey = $sellerId.$fbaExportData['nonFba'];
                    if (in_array($sellerId,["amazon", "amazon_uk2"])) {
                        //老账号用的是A号
                        $adKey = $sellerId.$tree['skuId'];
                    }
                    $adTypeStr = $this->redis->hGet("ceSkuMaterialMapInFbaAsinAdGroup", $adKey);
                    if ($adTypeStr){
                        //有adGroup广告
                        $adGroupList = json_decode($adTypeStr,true);
                        if ($adGroupList){
                            foreach ($adGroupList as $adGroupItem){

                                $lastExportData = $fbaExportData;

                                $lastExportData['targetingType'] = $adGroupItem['targetingType'];
                                $lastExportData['campaign'] = $adGroupItem['campaignName'];
                                $lastExportData['adgroup'] = $adGroupItem['adGroupName'];


                                $lastExportDataList[] = $lastExportData;
                            }
                        }
                    }else{
                        //没有adGroup
                        $fbaExportData['targetingType'] = "";
                        $fbaExportData['campaign'] = "";
                        $fbaExportData['adgroup'] = "";

                        $lastExportDataList[] = $fbaExportData;
                    }


                }
            }else{
                //没有FBA
                $exportData['channel'] = "";
                $exportData['sellerId'] = "";
                $exportData['fba'] = "";
                $exportData['fbaAsin'] = "";
                $exportData['nonFba'] = "";
                $exportData['targetingType'] = "";
                $exportData['campaign'] = "";
                $exportData['adgroup'] = "";
                $lastExportDataList[] = $exportData;
            }



        }

        if (count($lastExportDataList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx([
                "CE单号",
                "sku",
                "中文分类全路径",
                "是否同CE单首个sku",
                "产品分级",
                "运营人员",
                "渠道",
                "账号",
                "fba",
                "fba Asin",
                "nonfba编号",
                "广告类型",
                "campaign",
                "adgroup"
            ], $lastExportDataList, "资呈提取清单_" . date("YmdHis") . ".xlsx");
            $this->log("导出文件数据");
        }

    }

    private function saveCeSkuMaterialMapAllBasicInfo($skuIdList,$userNameMap)
    {
        if (count($skuIdList)>0){
            $curlSsl = (new CurlService())->pro();
            foreach (array_chunk($skuIdList,100) as $chunk){
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                    "skuIdList" => $chunk,
                    "attrCodeList" => [
                        "custom-skuInfo-skuId",
                        "custom-common-developerUserName",
                        "custom-common-sourceDeveloperUserName",
                        "custom-common-salesUserName",
                        "custom-common-minorSalesUserName",
                        "custom-common-categoryId",
                        "custom-common-categoryName",
                        "custom-common-categoryFullId",
                        "custom-common-categoryFullPath",
                        "channel",
                        "product_level",
                    ]
                ]));
                if (count($getKeyResp) > 0){
                    foreach ($getKeyResp as $item1){
                        $info = [
                            "skuId" => $item1['custom-skuInfo-skuId'],
                            "developerUserName" => $userNameMap[$item1['custom-common-developerUserName']] ?? $item1['custom-common-developerUserName'],
                            "sourceDeveloperUserName" => $userNameMap[$item1['custom-common-sourceDeveloperUserName']] ?? $item1['custom-common-sourceDeveloperUserName'],
                            "salesUserName" => $userNameMap[$item1['custom-common-salesUserName']] ?? $item1['custom-common-salesUserName'],
                            "minorSalesUserName" => $userNameMap[$item1['custom-common-minorSalesUserName']] ?? $item1['custom-common-minorSalesUserName'],
                            "categoryId" => $item1['custom-common-categoryId'],
                            "categoryName" => $item1['custom-common-categoryName'],
                            "categoryFullId" => $item1['custom-common-categoryFullId'],
                            "categoryFullPath" => $item1['custom-common-categoryFullPath'],
                            "channel" => $item1['channel'],
                            "product_level" => $item1['product_level'],
                        ];
                        $this->redis->hSet("ceSkuMaterialMapAllBasicInfo", "{$item1['custom-skuInfo-skuId']}",json_encode($info,JSON_UNESCAPED_UNICODE));
                    }
                }

            }

        }
    }

    private function saveCeSkuMaterialMapInProductSku($skuIdList,$userNameMap)
    {
        if (count($skuIdList)>0){
            foreach (array_chunk($skuIdList,100) as $chunk){
                $list = DataUtils::getPageList($this->curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => implode(",",$chunk),
                    "columns" => "productId,developerUserName,salesUserName,category,categoryPaths,cn_Category,status",
                    "limit" => 100
                ]));
                if ($list){
                    foreach ($list as $item2){
                        $info = [
                            "skuId" => $item2['productId'],
                            "developerUserName" => $userNameMap[$item2['developerUserName']] ?? $item2['developerUserName'],
                            "salesUserName" => $userNameMap[$item2['salesUserName']] ?? $item2['salesUserName'],
                            "categoryId" => $item2['category'],
                            "categoryFullId" => $item2['categoryPaths'],
                            "categoryFullPath" => $item2['cn_Category'],
                            "status" => $item2['status'],
                        ];
                        $this->redis->hSet("ceSkuMaterialMapInProductSku", "{$item2['productId']}",json_encode($info,JSON_UNESCAPED_UNICODE));
                    }

                }
            }

        }
    }

    private function saveCeSkuMaterialMapInFba($skuIdList)
    {
        if (count($skuIdList)>0){
            $fbaList = [];
            foreach (array_chunk($skuIdList,100) as $chunk){
                $list = DataUtils::getPageList($this->curlService->s3015()->get("pid-scu-maps/queryPage",[
                    "productId" => implode(",",$chunk),
                    "scuIdType" => "fba",
                    "scuIdStyle" => "systemWithSelling",
                    "limit" => 2000
                ]));
                if ($list){
                    foreach ($list as $item2){
                        $fbaList[$item2['productId']][$item2['channel'][0]] = $item2['scuId'];
                    }
                }
            }

            foreach ($fbaList as $productId => $map){
                $this->redis->hSet("ceSkuMaterialMapInFba", "{$productId}",json_encode($map,JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function saveCeSkuMaterialMapInFbaSeller($skuIdList)
    {
        if (count($skuIdList)>0) {
            $redisGet = $this->redis->hGetAll("ceSkuMaterialMapInFba");
            $fbaList = [];
            foreach ($redisGet as $skuId => $value) {
                $fbaInfo = json_decode($value, true);
                foreach ($fbaInfo as $channel => $fba) {
                    $fbaList[] = $fba;
                }
            }
            if (count($fbaList) > 0) {
                $fbaList = array_unique($fbaList);

                $fbaActiveListing = [];
                foreach (array_chunk($fbaList, 200) as $chunk) {
                    $list = DataUtils::getPageList($this->curlService->s3015()->get("sku-seller-configs/queryPage", [
                        "skuId" => implode(",", $chunk),
                        "columns" => "skuId,channel,sellerId",
                        "limit" => 1000
                    ]));
                    if ($list) {
                        foreach ($list as $item2){
                            $key = $item2['skuId'] . $item2['channel'];
                            $this->redis->hSet("ceSkuMaterialMapInFbaSeller", "{$key}",json_encode($item2,JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            }
        }


    }

    private function saveCeSkuMaterialMapInNonFba($skuIdList)
    {
        if (count($skuIdList)>0){
            $nonFbaList = [];
            foreach (array_chunk($skuIdList,100) as $chunk){
                $list = DataUtils::getPageList($this->curlService->s3015()->get("pid-scu-maps/queryPage",[
                    "productId" => implode(",",$chunk),
                    "scuIdType" => "nonFba",
                    "scuIdStyle" => "sellerSku",
                    "limit" => 2000
                ]));
                if ($list){
                    foreach ($list as $item2){
                        $nonFbaList[$item2['productId']][$item2['channel'][0]] = $item2['scuId'];
                    }
                }
            }

            foreach ($nonFbaList as $productId => $map){
                $this->redis->hSet("ceSkuMaterialMapInNonFba", "{$productId}",json_encode($map,JSON_UNESCAPED_UNICODE));
            }

        }
    }

    private function saveCeSkuMaterialMapInFbaAsinListing($skuIdList)
    {
        if (count($skuIdList)>0){
            $redisGet = $this->redis->hGetAll("ceSkuMaterialMapInFba");
            $fbaList = [];
            foreach ($redisGet as $skuId => $value){
                $fbaInfo = json_decode($value,true);
                foreach($fbaInfo as $channel => $fba){
                    $fbaList[] = $fba;
                }
            }
            if (count($fbaList)>0){
                $fbaList = array_unique($fbaList);
                $fbaActiveListing = [];
                foreach (array_chunk($fbaList,200) as $chunk){

                    $list = DataUtils::getQueryList($this->curlService->s3015()->get("amazon-active-listings/query",[
                        "skuId" => implode(",",$chunk),
                        "listingType" => "fba"
                    ]));

                    if ($list){
                        $fbaActiveListing = array_merge($fbaActiveListing,$list);
                    }
                }
                $fbaActiveListingMap = [];
                foreach ($fbaActiveListing as $item3){
                    $fbaActiveListingMap[$item3['skuId'].$item3['channel']][] = [
                        "skuId" => $item3['skuId'],
                        "channel" => $item3['channel'],
                        "sellerId" => $item3['sellerId'],
                        "asin" => $item3['asin']
                    ];
                }

                foreach ($redisGet as $skuId => $value){
                    $fbaInfo = json_decode($value,true);
                    $aSkuFbaActiveListingList = [];
                    foreach($fbaInfo as $channel => $fba){
                        $k = $fba.$channel;
                        if (isset($fbaActiveListingMap[$k])){
                            //存在上架fba信息
                            $this->redis->hSet("ceSkuMaterialMapInFbaAsinListing", "{$k}",json_encode($fbaActiveListingMap[$k],JSON_UNESCAPED_UNICODE));
                        }else{
                            $this->redis->hSet("ceSkuMaterialMapInFbaAsinListing", "{$k}",json_encode([],JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            }
        }
    }

    private function saveCeSkuMaterialMapInFbaAsinAdGroup($skuIdList)
    {
        if (count($skuIdList)>0){
            //fba上架，才有广告投放，所以fba asin 遍历
            $fbaAsinListRedis = $this->redis->hGetAll("ceSkuMaterialMapInFbaAsinListing");
            $nonFbaListRedis = $this->redis->hGetAll("ceSkuMaterialMapInNonFba");


            $nonFbaSellerMap = [];
            foreach ($nonFbaListRedis as $skuId => $value){
                $nonFbaInfo = json_decode($value,true);
                foreach ($nonFbaInfo as $channel => $nonFba){
                    $nonFbaSellerMap[$skuId][$channel] = $nonFba;
                }
            }


            $needSearchAdGroupName = [];
            foreach ($fbaAsinListRedis as $a => $value) {
                $fbaActList = json_decode($value, true);
                if (count($fbaActList) > 0) {
                    foreach ($fbaActList as $item4) {
                        if (in_array($item4['sellerId'],["amazon", "amazon_uk2"])) {
                            $needSearchAdGroupName[] = $a;
                        }else{
                            //新账号，adgroup用sku的nonfba编号
                            if (isset($nonFbaSellerMap[$a]) && isset($nonFbaSellerMap[$a][$item4['channel']])){
                                $needSearchAdGroupName[] = $nonFbaSellerMap[$a][$item4['channel']];
                            }
                        }
                    }
                }else{
                    $this->log("没有fba上架，不开广告");
                }
            }

            if (count($needSearchAdGroupName) > 0){

                $adMap = [];
                $campaignIds = [];
                foreach (array_chunk($needSearchAdGroupName,100) as $chunk){
                    $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage",[
                        "adGroupName_in" => implode(",",$chunk),
                        "state" => "enabled",
                        "columns" => "channel,campaignId,adGroupId,adGroupName,state",
                        "limit" => 2000
                    ]));
                    if ($list){
                        foreach ($list as $it){
                            $sellerId = $it['channel'];
                            if ($it['channel'] == 'amazon_us'){
                                $sellerId = "amazon";
                            }
                            $key = $sellerId . $it['adGroupName'];
                            $adMap[$key][] = [
                                "sellerId" => $sellerId,
                                "campaignId" => $it['campaignId'],
                                "adGroupId" => $it['adGroupId'],
                                "adGroupName" => $it['adGroupName'],
                                "adGroupState" => $it['state'],
                            ];
                            $campaignIds[] = $it['campaignId'];
                        }
                    }
                }
                $campaignIds = array_unique($campaignIds);
                $campaignIdMap = [];
                if ($campaignIds){
                    foreach (array_chunk($campaignIds,100) as $chunk){
                        $list1 = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
                            "company" => "CR201706060001",
                            "campaignId_in" => implode(",",$chunk),
//                                "state" => "enabled",
                            "columns" => "campaignId,campaignName,channel,targetingType,state",
                            "limit" => 200
                        ]));
                        if ($list1){
                            foreach ($list1 as $it){
                                $campaignIdMap[$it['campaignId']] = [
                                    "channel" => $it['channel'],
                                    "state" => $it['state'],
                                    "campaignName" => $it['campaignName'],
                                    "targetingType" => $it['targetingType'],
                                ];
                            }
                        }
                    }
                }

                foreach ($adMap as $key => $ddd){
                    $campaignInfoList = [];
                    foreach ($ddd as &$dd){
                        if (isset($campaignIdMap[$dd['campaignId']])){
                            $campaignInfo = $campaignIdMap[$dd['campaignId']];
                            $dd['campaignName'] = $campaignInfo['campaignName'];
                            $dd['targetingType'] = $campaignInfo['targetingType'];
                            $dd['campaignState'] = $campaignInfo['state'];
                            $campaignInfoList[] = $dd;
                        }
                    }
                    $this->redis->hSet("ceSkuMaterialMapInFbaAsinAdGroup", "{$key}",json_encode($campaignInfoList,JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }
}

$curlController = new FixPaSkuMaterialSpDataController();
$curlController->findPaSkuMaterialSpData();
