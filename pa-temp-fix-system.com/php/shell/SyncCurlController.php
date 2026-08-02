<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class SyncCurlController
 */
class SyncCurlController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private $module = "platform-wms-application";

    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;

        $this->redis = new RedisService();
    }
    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "config":
                $this->module = "platform-config-service";
                break;
        }

        return $this;
    }
    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function commonDelete($port, $model, $id,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->delete($model, $id));
        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
    }

    public function commonFindById($port, $model, $id, $env = 'test')
    {
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/{$id}");
        $data = isset($resp['result']) ? $resp['result'] : null;
        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonFindOneByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 1;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
        } else {
            $data = DataUtils::getPageListInFirstData($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonFindByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 999;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getPageDocList($resp);
        } else {
            $data = DataUtils::getPageList($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonCreate($port, $model, $params,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->post("{$model}", $params));
        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }
    public function commonUpdate($port, $model, $params,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->put("{$model}/{$params['_id']}", $params));
        $this->log("更新{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }
    /**
     * 删除campaign广告
     */
    public function deleteCampaign(){
        $list = $this->commonFindByParams("s3023","amazon_sp_campaigns",[
            "status" => "3",
            "company" => "CR201706060001",
            "state" => "enabled",
            "limit" => 2000
        ],"pro");
        $needDeleteList = [];
        if (count($list) > 0){
            foreach ($list as $item){
                if (empty($item['campaignId'])){
                    $this->log("campaignId 为空,删除");
                    $needDeleteList[] = $item['_id'];
                    //$this->commonDelete("s3023","amazon_sp_campaigns",$item['_id'],"pro");
                }
            }
        }
        if (count($needDeleteList) > 0){
            foreach ($needDeleteList as $_id){
                $this->commonDelete("s3023","amazon_sp_campaigns",$_id,"pro");
            }
            (new RequestUtils("test"))->dingTalk("删除重复创建campaign结束");
        }else{
            (new RequestUtils("test"))->dingTalk("没有重复创建campaign可删除");
        }

    }


    public function syncProduct()
    {
        $skuIdList = [

        ];

        $fromHost = "";

        foreach ($skuIdList as $skuId) {
            //同步product-sku

            //同步product-base-info

            //同步sku-sales-status

            //同步amazon_asin

            //同步amazon_active_listing

            //同步channel_sku_image

            //同步channel_price

            //同步pid_scu_map

            //同步sku_images

            //同步sku_sale_status

            //同步sku_seller_config

            //同步sgu_sku_scu_map

            //同步sgu_sku_scu_map_by_sguId

            //同步sgu_sku_scu_channel_map

            //同步sku_price

            //同步scu_sku_map

        }
    }

    public function getPaSkuMaterial(){



        $ceBillNo = "CE202502130077";
        $parentSku = "a25010800ux1806";
//        $dpmoList = $this->commonFindByParams("s3044","pa_sku_materials",[
//            "limit" => 1000,
//            "ceBillNo" => $ceBillNo,
//            "parentSkuId" => $parentSku
//        ],"pro");
//        if (count($dpmoList) >0){
//            foreach ($dpmoList as $itm){
//                if ($itm['parentSkuId'] == ""){
//                    continue;
//                }
//                $this->commonDelete("s3044","pa_sku_materials",$itm['_id'],"pro");
//            }
//        }
//        die("111");

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/fitment.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){
                $fitmentSkuMap[$info['skuId']][] = [
                    "make" => $info['make'],
                    "model" => $info['model']
                ];
            }
        }

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/cpasin.xlsx");
        $cpSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){
                $cpSkuMap[$info['skuId']][] = $info['asin'];
            }
        }
        $dpmoList = $this->commonFindByParams("s3044","pa_sku_materials",[
            "limit" => 1000,
            "ceBillNo" => $ceBillNo,
//            "skuId" => $parentSku
        ],"pro");
        $_idList = [];
        if (count($dpmoList) > 0){
            foreach ($dpmoList as $info){
                $keywords = [
                    "Gear Shift Knob Cover",
                    "Gear Shift Knob Sticker",
                    "Gear Shift Knob Decal",
                    "Gear Shift Head Cover",
                    "Gear Shift Head Cap",
                ];
                $fitment = [];
                $cpAsin = [];
                if(isset($fitmentSkuMap[$info['skuId']])){
                    $fitment = $fitmentSkuMap[$info['skuId']];
                }
                if(isset($cpSkuMap[$info['skuId']])){
                    $cpAsin = $cpSkuMap[$info['skuId']];
                }


//                $info['keywords'] = $keywords;
                $info['cpAsin'] = $cpAsin;
                $info['fitment'] = $fitment;
                $info['modifiedBy'] = "zhouangang";
                $this->commonUpdate("s3044","pa_sku_materials",$info,"pro");

            }






//            $parentSkuInfo = $dpmoList[0];
//            $main = $this->commonFindOneByParams("s3044","pa_ce_materials",[
//                "limit" => 1,
//                "ceBillNo" => $ceBillNo
//            ],'pro');
//            if (count($main['skuIdList']) > 0){
//                $keywords = [
//                    "Gear Shift Knob Cover",
//                    "Gear Shift Knob Sticker",
//                    "Gear Shift Knob Decal",
//                    "Gear Shift Head Cover",
//                    "Gear Shift Head Cap",
//                ];
//                $fitment = [];
//                $cpAsin = [];
//
//                foreach ($main['skuIdList'] as $info){
//                    if(isset($fitmentSkuMap[$info])){
//                        $fitment = $fitmentSkuMap[$info];
//                    }
//                    if(isset($cpSkuMap[$info])){
//                        $cpAsin = $cpSkuMap[$info];
//                    }
//                    if ($info == $parentSku){
//                        $parentSkuInfo['keywords'] = $keywords;
//                        $parentSkuInfo['cpAsin'] = $cpAsin;
//                        $parentSkuInfo['fitment'] = $fitment;
//                        $parentSkuInfo['modifiedBy'] = "zhouangang";
//                        $this->commonUpdate("s3044","pa_sku_materials",$parentSkuInfo,"pro");
//                        continue;
//                    }
//
//                    $cloneInfo = $parentSkuInfo;
//                    $cloneInfo['skuId'] = $info;
//                    $cloneInfo['parentSkuId'] = $parentSkuInfo['skuId'];
//                    $cloneInfo['keywords'] = $keywords;
//                    $cloneInfo['cpAsin'] = $cpAsin;
//                    $cloneInfo['fitment'] = $fitment;
//                    unset($cloneInfo['_id']);
//                    $this->commonCreate("s3044","pa_sku_materials",$cloneInfo,"pro");
//                }
//            }

        }

    }

    public function addOptionValListData(){
        $price_base_fields = [
            [
                "feeType" => "ads",
                "feeValue" => "0.05",
                "currency" => "",
                "remark" => "广告费率",
                "categoryId" => "",
                "addCondition" => [
                    [
                        "label" => "publishDate",
                        "value" => "2024-09-01",
                        "unit" => "",
                        "expressions" => ">"
                    ]
                ]
            ],
            [
                "feeType" => "ads",
                "feeValue" => "0.05",
                "currency" => "",
                "remark" => "广告费率",
                "categoryId" => "",
                "addCondition" => [
                    [
                        "label" => "publishDate",
                        "value" => "2024-09-01",
                        "unit" => "",
                        "expressions" => ">"
                    ]
                ]
            ],
        ];
        $unsalable_base_fields = [
            [
                //绑定策略id
                "strategy_main_id" => "1747933604497825793",
                //绑定价格挡位设置id
                "margin_position_main_id" => "1747942777125593089",
                //滞销类型,1-海外仓滞销;2-中国仓滞销
                "dull_sale_type" => 1
            ]
        ];
        echo json_encode($price_base_fields,JSON_UNESCAPED_UNICODE)."\n";
        echo json_encode($unsalable_base_fields,JSON_UNESCAPED_UNICODE)."\n";

    }


    public function fixPaSkuPhotoGress(){
        $list = $this->commonFindByParams("s3015","sku_photography_progresss",[
            "ceBillNo_in" => "CE202503280130"
        ],"pro");
        foreach ($list as &$item){
            $item['createCeBillNoOn'] = "2025-03-28T16:47:58.000Z";
            $this->commonUpdate("s3015","sku_photography_progresss",$item,"pro");
        }

    }

    public function deleteCeMaterial()
    {
        foreach ([
                     "QD202411190013",
                     "QD202412030020",
                     "QD202412030021"
                 ] as $batchName) {
            $mainInfo = $this->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => $batchName], "pro");
            if ($mainInfo) {
                $list = $this->commonFindByParams("s3044", "pa_sku_materials", ["ceBillNo" => $batchName], "pro");
                if ($list) {
                    foreach ($list as $detail) {
                        $this->commonDelete("s3044", "pa_sku_materials", $detail['_id'], "pro");
                        $this->log("删除" . $detail['skuId']);
                    }
                }
                $this->commonDelete("s3044", "pa_ce_materials", $mainInfo['_id'], "pro");
                $this->log("删除" . $mainInfo['batchName'] . "完毕");
            }
        }

    }

    public function updateCeMaterialPlatform()
    {
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('pa');
        $list = $this->commonFindByParams("s3044", "pa_ce_materials", ["createdBy"=>"P3-CreateCeSkuMaterialJob"], "pro");
        $batchNameList = [];
        if ($list){
            $batchNameList = array_column($list,"batchName");
        }
        if (count($batchNameList) > 0){
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $batchNameList));
            $platformMap = [];
            if ($resp){
                foreach ($resp as $item){
                    $platformMap[$item['prePurchaseBillNo']] = $item;
                }
            }
            foreach (array_chunk($batchNameList,150) as $chunkList){
                $mainInfoList = $this->commonFindByParams("s3044", "pa_ce_materials", ["batchName_in" => implode(",",$chunkList)], "pro");
                if (count($mainInfoList) > 0) {
                    foreach ($mainInfoList as $mainInfo){
                        $canUpdate = false;
                        if (!$mainInfo['platform'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['platform'])){
                            $mainInfo['platform'] = $platformMap[$mainInfo['batchName']]['platform'];
                            $canUpdate = true;
                        }
                        if (!$mainInfo['ebayTraceMan'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['minorSalesUserName'])){
                            $mainInfo['ebayTraceMan'] = $platformMap[$mainInfo['batchName']]['minorSalesUserName'];
                            $canUpdate = true;
                        }
                        if ($canUpdate){
                            $this->commonUpdate("s3044", "pa_ce_materials", $mainInfo, "pro");
                            $this->log("更新批次的平台数据：{$mainInfo['batchName']} - {$mainInfo['platform']} - {$mainInfo['ebayTraceMan']}");
                        }

                    }
                }
            }
        }


    }


    public function getCompanyByCompanyId($userName = 'zhouangang'){


        $curlService = new CurlService();
        $resp = $curlService->pro()->s3009()->get("system-manages/getCompany", ["companyId" => "CR201706060001"]);
        $data = DataUtils::getResultData($resp);

        $channelParams = array();
        $channelArray = array();
        if ($data){
            $info = $data[0];
            $channelDetailParams = array();
            foreach ($info['regional'] as $item){
                $channelArr = explode("_",$item);
                $channelDetailParams[] = array(
                    "platform" => $channelArr[0],
                    "channel" => $item,
                    "saleStatus" => "A",
                    "type" => "",
                    "url" => "",
                    "remark" => "",
                    "isCaught" => "0"
                );
                $channelArray[] = $item;
            }

            $channelParams = array(
                "status" => "A",
                "createdBy" => $userName,
                "modifiedBy" => $userName,
                "channelSales" => $channelDetailParams,
                "origin" => "2009",
            );

        }

        $productList = $this->commonFindByParams("s3015","pa_product_details",[
            "ceBillNo_in" => "CE202412100077"
        ],"pro");
        $this->log(json_encode(array_column($productList,"skuId"),JSON_UNESCAPED_UNICODE));
        foreach ($productList as $info){
            if ($info['skuId']){
                $skuSaleStatusList = $this->commonFindOneByParams("s3015","sku-sale-statuses",["skuId" => $info['skuId']],"pro");
                if (count($skuSaleStatusList) == 0){
                    $channelParams['skuId'] = $info['skuId'];
                    $result = $curlService->pro()->s3015()->post("sku-sale-statuses/createSkuSaleStatusesEx", $channelParams);
                    $this->log(json_encode($result,JSON_UNESCAPED_UNICODE));
                }else{
                    $this->log("已有可售表");
                }

            }
        }



    }


    //重新生成tempSkuId编号 - 重复T号问题处理
    public function updatePaProductTempSkuIdNew()
    {
        //切换环境，test - 测试。pro - 生产
        $env = "pro";

        //todo 先修复空T号 以及 T号为数字，字母等无正确正则匹配的T号，重新生成T号
//        $page = 1;
//        do{
//            $teRes = $this->commonFindByParams("s3015", "pa_product_details", array(
//                "limit" => 1000,
//                "page" => $page
//            ), $env);
//            if(count($teRes) > 0){
//                foreach ($teRes as &$info){
//                    if (!isset($info['tempSkuId']) || (isset($info['tempSkuId']) && empty($info['tempSkuId'])) || (strpos($info['tempSkuId'], 'T') !== 0)){
//                        $newTempSkuId = $this->getTempSkuIdByRedis();
//                        $info['tempSkuId'] = $newTempSkuId;
//                        $info['modifiedBy'] = "T号修复(zhouangang)";
//
//                        $up = $this->commonUpdate("s3015","pa_product_details",$info,$env);
//                        if ($up) {
//                            $this->log("没有T号，生成新T号：{$newTempSkuId}");
//                        }
//                    }else{
//                        $this->log("有T号：{$info['tempSkuId']}");
//                    }
//                }
//                $page++;
//                $this->log("第 {$page} 页");
//            }else{
//                break;
//            }
//        }while(true);


        //todo 再使用mongo语句，去arc-sql 那里: https://sre-sql.ux168.cn/sqlquery/
        // 用以下查询条件，查询重复T号的数据，导出来，转xslx，放在export目录下,修改里面的内容（可能totalCount左边的列都得删除，不然会读空），修改sheet名称为Sheet1
        /*
         *
          db.pa_product_detail.aggregate([
              {
                $group: {
                  _id: {tempSkuId:"$tempSkuId"},
                  count: { $sum: 1 }
                }
              },
              {
                $match:{count:{$gt:1}}
            },
              {
                $project: {
                  _id: 0,
                  tempSkuId:"$_id.tempSkuId",
                  totalCount: "$count"
                }
              }
            ])
         */
        //$fileContent = (new ExcelUtils())->getXlsxData("../export/重复T号test.xlsx");

        $fileContent = [
            [
                "tempSkuId" => "T241223605620ux001"
            ]
        ];
        if (sizeof($fileContent) > 0) {
            foreach ($fileContent as $info){
                $oldTMapNewT = array();
                $this->log("旧T号：{$info['tempSkuId']}");
                $teRes = $this->commonFindByParams("s3015", "pa_product_details", array(
                    "orderBy" => "-_id",
                    "tempSkuId" => $info['tempSkuId'],
                    "limit" => 1000
                ), $env);
                //
                if (count($teRes) > 0) {
                    //暴力一点吧全部重写
                    foreach ($teRes as $tinfo){
                        $dteRes = $this->commonFindByParams("s3015", "pa_product_details", array(
                            "orderBy" => "_id",
                            "productName" => $tinfo['productName'],
                            "limit" => 5
                        ), $env);

                        //用productName来找到以前的T号
                        $oldTempSkuId = "";
                        if (count($dteRes) > 0) {
                            foreach ($dteRes as $dteInfo){
                                if ($dteInfo['tempSkuId'] != $info['tempSkuId']){
                                    $oldTempSkuId = $dteInfo['tempSkuId'];
                                    break;
                                }
                            }
                        }
                        //如果有旧T号
                        if (!empty($oldTempSkuId)){
                            //用旧T号查到技术维度
                            $paSkuAttributeInfo = $this->commonFindOneByParams("s3015", "pa_sku_attributes", array(
                                "tmepSkuId" => $oldTempSkuId,
                            ), $env);

                            if ($paSkuAttributeInfo) {
                                //新的T号
                                $uniqueId = $this->getTempSkuIdByRedis();
                                //替换成新的
                                $paSkuAttributeInfo['tempSkuId'] = $uniqueId;
                                //$paSkuAttributeInfo['skuId'] = $uniqueId;
                                $rs = $this->commonUpdate("s3015","pa_sku_attributes",$paSkuAttributeInfo,$env);
                                if($rs){
                                    $this->log("存在技术维度：{$oldTempSkuId}，已更改为：{$uniqueId}");
                                    $tinfo['tempSkuId'] = $uniqueId;
                                    $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);
                                }
                            }else{
                                $this->log("没有技术维度：{$oldTempSkuId}，直接更新T号");
                                //没有技术维度直接
                                //新的T号
                                $uniqueId = $this->getTempSkuIdByRedis();
                                $tinfo['tempSkuId'] = $uniqueId;
                                $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);

                            }

                        }else{
                            $this->log("{$tinfo['tempSkuId']} 查不到旧名称：{$tinfo['productName']}");
                            //新的T号
                            $uniqueId = $this->getTempSkuIdByRedis();
                            $tinfo['tempSkuId'] = $uniqueId;
                            $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);
                        }

                    }

                }

            }


        }


    }

    //生成T号
    public function getTempSkuIdByRedis(){
        // 获取当前年月日
        $currentDate = date('ymd');
        // 构建编号的前缀
        $prefix = "T{$currentDate}ux";
        // 初始化计数器，如果不存在则设置为0
        $counterKey = "counter_{$currentDate}";
        $counter = $this->redis->incr($counterKey);
        // 如果计数器为0，说明是第一次使用，设置过期时间为1天
        if ($counter == 1) {
            $this->redis->expire($counterKey, 86400); // 86400秒 = 1天
        }
        // 格式化计数器为5位数
        $counterFormatted = str_pad($counter, 5, '0', STR_PAD_LEFT);
        // 生成唯一编号
        $uniqueId = $prefix . $counterFormatted;
        return $uniqueId;
    }



    public function buildSql(){
        $excelUtils = new ExcelUtils();
        $list = [
            [
                "actualFileName" => "sasdasd",
                "key" => "121412",
                "link" => "eafggggg"
            ]
        ];

//        $list = [
//            ["sasdasd",121412,"eafggggg"]
//        ];
        $filePath = $excelUtils->downloadXlsx(["文件原名","Oss Key名","OSS链接"],$list,"oss文件");
//        $filePath = $excelUtils->downloadXlsxV2($list,"oss文件");
        $this->log($filePath);
    }

    public function writeProductBaseFba(){
        $env = "pro";
        $rs = $this->commonFindByParams("s3015", "product_fba_bases", [
            "sequenceId" => "CR201706060001",
            "productLineGroup" => "PA",
            "status_in" => "Y",
            "channel_in" => "ebay_us",
            "limit" => 2500
        ], $env);
        if($rs){
            foreach ($rs as $info){

                $curlSsl = (new CurlService())->pro();
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
                    "skuIdList" => [$info['skuId']],
                    "orderBy" => "id asc",
                    "pageNumber" => 1,
                    "entriesPerPage" => 1
                ]));
                if ($getKeyResp && count($getKeyResp) > 0){
                    $ceInfo = $getKeyResp[0];
                    $batchName = "";
                    if ($ceInfo && isset($ceInfo['ceBillNo']) && $ceInfo['ceBillNo']){
                        $batchName = $ceInfo['ceBillNo'];

                        $respssss = (new CurlService())->pro()->s3044()->post("ebay_bilino_add_rounds/setSellerIdAndAddCountByBatchName", [
                            "batchName" => $batchName,
                            "skuId" => $info['skuId'],
                            "userName" => "pa-fix-sys",
                            "source"=>"海外仓轮单"
                        ]);
                        $this->log("{$batchName} - {$info['skuId']} - 进入轮单：" . json_encode($respssss,JSON_UNESCAPED_UNICODE));
                    }
                }

            }
        }

        //https://master-angular-nodejs-poms-list-manage.ux168.cn/api/product_fba_bases/queryPage?&page=1&limit=10&sequenceId=CR201706060001&productLineGroup=PA&channel_in=ebay_us&api_key=


    }

    public function get30PpmsByTempskuid(){
        $t = [
            "T250114000359",
            "T250114000354",
            "T250114000352",
            "T250114000351",
            "T250114000350",
            "T250114000349",
            "T250114000348",
            "T250114000345",
            "T250114000344",
            "T250114000191",
        ];
        $curlSsl = (new CurlService())->pro();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
            "skuIdList" => $t,
            "attrCodeList" => [
                "custom-skuInfo-tempSkuId",
                "custom-skuInfo-consignmentPrice",
                "min_arrival_quantity",
                "custom-common-salesUserName",
                "custom-skuInfo-factoryId",
                "custom-skuInfo-supplierProductNo",
                "custom-skuInfo-outsideTitle",
                "custom-skuInfo-supplierId",
            ]
        ]));
        if ($getKeyResp){
            $tempIdsList = [
                [
                    "id" => "1879052194089963565",
                    "temp_sku_id" => "T250114000191"
                ], [
                    "id" => "1879052194089963718",
                    "temp_sku_id" => "T250114000344"
                ], [
                    "id" => "1879052194089963719",
                    "temp_sku_id" => "T250114000345"
                ], [
                    "id" => "1879052194089963722",
                    "temp_sku_id" => "T250114000348"
                ], [
                    "id" => "1879052194089963723",
                    "temp_sku_id" => "T250114000349"
                ], [
                    "id" => "1879052194089963724",
                    "temp_sku_id" => "T250114000350"
                ], [
                    "id" => "1879052194089963725",
                    "temp_sku_id" => "T250114000351"
                ], [
                    "id" => "1879052194089963726",
                    "temp_sku_id" => "T250114000352"
                ], [
                    "id" => "1879052194089963728",
                    "temp_sku_id" => "T250114000354"
                ], [
                    "id" => "1879052194089963733",
                    "temp_sku_id" => "T250114000359"
                ]
            ];
            $tempIdsIdMap = [];
            foreach ($tempIdsList as $sss){
                $tempIdsIdMap[$sss['temp_sku_id']] = $sss['id'];
            }
            $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
            $preSkuList = [];
//            foreach ($getKeyResp as $info){
//                $resp = DataUtils::getResultData($curlSsl->s3009()->post("po-composite-services/getSampleSkuInfoByConditions",[
//                    "conditionsJsonEncode" => ["titleCn" => $info['custom-skuInfo-outsideTitle']],
//                    "orderBy" => "",
//                    "page" => 1,
//                    "limit" => 2
//                ]));
//                if ($resp && count($resp) > 0 && $resp[0]['sampleSkuInfoResponse'] && $resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] && count($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos']) > 0){
//                    foreach ($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] as $i){
//                        $preSkuList[] = [
//                            "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
//                            "skuId" => $i['skuId']
//                        ];
//                    }
//                }
//            }

            $fileContent = (new ExcelUtils())->getXlsxData("../export/qd/补充的T号.xlsx");
            $titleCnMap = [];
            foreach ($fileContent as $info){
                $titleCnMap[$info['titleCn']] = $info;
            }
            foreach ($getKeyResp as $info){
                if (isset($titleCnMap[$info['custom-skuInfo-outsideTitle']])){
                    $preSkuList[] = [
                        "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
                        "skuId" => $titleCnMap[$info['custom-skuInfo-outsideTitle']]['skuId']
                    ];
                }
            }
            if ($preSkuList){
                $writeData = [
                    "prePurchaseBillNo" => "QD202504080024",
                    "ceBillNo" => "CE202505050082",
//                    "skuList" => $preSkuList,
                    "operatorName" => "zhouangang",
//                    "purchaseHandleStatus" => 70$tempIdsIdMap = {数组} [10]
                ];

                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
                if ($getKeyResp){
                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
                }
            }
        }

    }
    public function writeScmsPurchaseBillNo(){
        $curlSsl = (new CurlService())->pro();

        $pmoBillNo = "DPMO250506011";
//        $fileContent = (new ExcelUtils())->getXlsxData("../export/pmo/ppms_{$pmoBillNo}.xlsx");
//        $pmoContent = (new ExcelUtils())->getXlsxData("../export/pmo/{$pmoBillNo}.xlsx");
//        $pmoSkuMap = [];
//        if (sizeof($pmoContent) > 0){
//            $pmoSkuMap = array_column($pmoContent,null,"skuId");
//        }
//
//        if (sizeof($fileContent) > 0) {
//            $preSkuList = [];
//            foreach ($fileContent as $info){
//                $resp = DataUtils::getResultData($curlSsl->s3009()->post("po-composite-services/getSampleSkuInfoByConditions",[
//                    "conditionsJsonEncode" => ["titleCn" => $info['tempSkuId']],
//                    "orderBy" => "",
//                    "page" => 1,
//                    "limit" => 2
//                ]));
//                if ($resp && count($resp) > 0 && $resp[0]['sampleSkuInfoResponse'] && $resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] && count($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos']) > 0){
//                   foreach ($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] as $i){
//                       if (isset($pmoSkuMap[$i['skuId']])){
//                           $preSkuList[] = [
//                               "devSkuPkId" => $info['id'],
//                               "skuId" => $i['skuId']
//                           ];
//                           $this->log("{$info['id']} {$i['skuId']}");
//                           break;
//                       }
//                   }
//                }
//            }

//        $preSkuList = [];
//        $preSkuList[] = [
//            "devSkuPkId" => "1909472435430146070",
//            "skuId" => "a25050800ux2790"
//        ];
//            if (count($preSkuList) > 0){
                $writeData = [
                    "prePurchaseBillNo" => $pmoBillNo,
                    "pmoBillNo" => "PMO2025050600011",
//                    "ceBillNo" => "CE202505090158",
//                    "skuList" => $preSkuList,
                    "operatorName" => "zhouangang",
//                    "purchaseHandleStatus" => 70
                ];

                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
                if ($getKeyResp){
                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
                }
//            }

//        }


//        $ss=$curlSsl->s3009()->post("market-analysis-reports/deleteSkuIdInfoBatchForCombine",[
//           "skuIdInfoIdList" => ["681c80d451cea82da1a946d4"]
//        ]);
//        if ($ss){
//
//        }






    }

    public function saveReceiveIpCheck(){
        $curlSsl = (new CurlService())->pro();
        $info = DataUtils::getPageListInFirstData($curlSsl->s3010()->get("problem-product-infos/queryPage",[
            "skuId" => "a24080100ux0303",
            "type" => "tort",
        ]));
        if ($info){
//            $info['remark'] = "Product IP Issue";
//            $info['newBrandName'] = "K LOGO";
//            $info['url'] = "https://branddb.wipo.int/en/advancedsearch/brand/US502015086736339";
            $info['status'] = 'new';
            $result = DataUtils::getPageListInFirstData($curlSsl->s3010()->put("problem-product-infos/{$info['_id']}",$info));
            $this->log(json_encode($result,JSON_UNESCAPED_UNICODE));
            //再查
//            $infoAft = DataUtils::getPageListInFirstData($curlSsl->s3010()->get("problem-product-infos/queryPage",[
//                "skuId" => "a24080100ux0303",
//                "type" => "tort",
//            ]));
//
//            $data = [
//                "id" => $infoAft['_id'],
//                "lastModifiedOn" => $infoAft['modifiedOn'],
//                "remark" => "Product IP Issue",
//                "newBrandName" => "unbranded",
//                "url" => "/",
//                "description" => "/",
//                "userName" => "fangxiaojuan"
//            ];
//            $resp = DataUtils::getResultData($curlSsl->s3010()->post("problem-product-infos/saveReceiveIpCheck",$data));
//            if ($resp){
//                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
//            }
        }
    }


    public function updateSampleSku(){



        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/留样CP.xlsx");

        if (sizeof($fileContent) > 0) {
            $skuIdList = array_column($fileContent,"sku_id");

            $skuCPList = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $curlService = new CurlService();
                $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getSkuIdInfoByCpBillNoList", [
                    "cpBillNoListJsonEncode" => json_encode($chunk,JSON_UNESCAPED_UNICODE)
                ]);
                $list = DataUtils::getQueryList($resp);

                if (count($list) > 0){
                    foreach ($list as $info){
                        $skuCPList[] = [
                            "sequenceId" => $info['sequenceId'],
                            "skuId" => $info['skuId'],
                            "ceBillNo" => $info['ceBillNo']
                        ];
                    }
                }
            }



            $excelUtils = new ExcelUtils();
            $downloadOssLink = "sku和Cp号对应关系_" . date("YmdHis") . ".xlsx";
            $downloadOssPath = $excelUtils->downloadXlsx(["CP号", "skuId", "CE单"],$skuCPList,$downloadOssLink);



        }


    }


    /**
     * 回写CE单 到 预计采购清单
     */
    public function ceWrite(){
        $env = "pro";

        $pmoList = [
            "DPMO250114002-林泽键",
        ];
        $curlService = new CurlService();
        $list = [];
        foreach ($pmoList as $pmoBillNo){
            $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getMainSkuIdInfo", [
                "batch" => $pmoBillNo
            ]);
            if (count($resp['result'])>0){
                $pmoInfo = $resp['result'][0];
                if ($pmoInfo){
                    $resp1 = $curlService->$env()->s3009()->post("cmo-managements/masterQuery", [
                        "conditionsJsonEncode" => json_encode(["pmoBillNoList" => [$pmoInfo['pmoBillNo']]], JSON_UNESCAPED_UNICODE),
                        "entriesPerPage" => 1,
                        "orderBy" => "cmoBillNo desc",
                        "pageNumber" => 1
                    ]);
                    if ($resp1 && $resp1['result'] && $resp1['result']['cmoMasterResponse'] && $resp1['result']['cmoMasterResponse']['cmoMasters'] && count($resp1['result']['cmoMasterResponse']['cmoMasters']) > 0){
                        $cmoBillNoInfo = $resp1['result']['cmoMasterResponse']['cmoMasters'][0];
                        if ($cmoBillNoInfo){
                            $resp2 = $curlService->$env()->s3009()->get("cmo-managements/cmoMasterProgress", [
                                "cmoBillNo" => $cmoBillNoInfo['cmoBillNo'],
                            ]);
                            if ($resp2['result'] && $resp2['result']['data']){
                                foreach ( $resp2['result']['data'] as $sourceId => $ceList){
                                    foreach ($ceList as $ceBillNo => $ceProcess){
                                        if (strpos($ceBillNo, "CE") === 0) {

                                            $prePurchaseBillNo = $pmoBillNo;
                                            $position = strpos($pmoBillNo, '-');
                                            if ($position !== false) {
                                                // 从开始到 '-' 的位置截取字符串
                                                $prePurchaseBillNo = substr($pmoBillNo, 0, $position);
                                            }

                                            $list[] = [
                                                "prePurchaseBillNo" => $prePurchaseBillNo,
                                                "ceBillNo" => $ceBillNo,
                                                "operatorName" => "system(Restful_PaProductInfo)"
                                            ];
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }

                        }

                    }
                }
            }

        }
        if (count($list) > 0){
            foreach ($list as $info){
                $this->log(json_encode($info,JSON_UNESCAPED_UNICODE));

                $curlService1 = new CurlService();
                $curlService1->$env()->gateway()->getModule('pa');
                $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $info));
                if ($resp3){
                    $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
                }

            }

        }





    }

    //同步全公司年度目标看板:
    public function syncAllVerticalMonthlTargets(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $curlService = new CurlService();
        $page = 1;
        $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3044()->get("pa_all_vertical_monthly_targets/queryPage", [
                "limit" => 1000,
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            $res = $curlService->uat()->s3044()->post("pa_all_vertical_monthly_targets/createBatch",$allList);
            $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
        }

        $page = $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3047()->get("pa_all_vertical_monthly_saless/queryPage",[
                "limit" => 1000,
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            foreach ($allList as $info){
                $res = $curlService->uat()->s3047()->post("pa_all_vertical_monthly_saless",$info);
                $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
            }

        }

        $page = $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3047()->get("pa_vertical_daily_saless/queryPage",[
                "limit" => 1000,
                "year" => "2025",
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            foreach ($allList as $info){
                $res = $curlService->uat()->s3047()->post("pa_vertical_daily_saless",$info);
                $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
            }
        }

    }

    public function updateFcuProductLine(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/skufcu.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $skuIdList = array_column($fileContent, "skuId");
            $fcuIdList = array_column($fileContent, "fcuId");

            $list = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                    "skuId" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($getProductMainResp && count($getProductMainResp['data']) > 0){
                    $list = array_merge($list,$getProductMainResp['data']);
                }
            }

            $skuIdProductLineMap = [];
            if (count($list) > 0){
                $skuIdProductLineMap = array_column($list,null,"skuId");
            }


            $fculist = [];
            foreach (array_chunk($fcuIdList,200) as $chunk){
                $fcuResult = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                    "fcuId_in" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($fcuResult && count($fcuResult) > 0){
                    foreach ($fcuResult as $info){
                        $fculist[$info['fcuId']] = $info;
                    }
                }
            }


            foreach ($fileContent as $item){
                $skuId = $item['skuId'];
                if (isset($skuIdProductLineMap[$skuId])){
                    $product_operator_mainInfo_id = $skuIdProductLineMap[$skuId]['product_operator_mainInfo_id'];

                    if (isset($fculist[$item['fcuId']])){
                        $fcuInfo = $fculist[$item['fcuId']];
                        if ($fcuInfo['productLineId']){
                            continue;
                        }
                        $fcuInfo['productLineId'] = $product_operator_mainInfo_id;
                        $fcuInfo['modifiedBy'] = "zhouangang";

                        $sss = $curlService->s3044()->put("fcu_sku_maps/{$fcuInfo['_id']}", $fcuInfo);
                        $this->log("更新产品线id成功" . json_encode($sss,JSON_UNESCAPED_UNICODE));
                    }else{
                        $this->log("找不到fcu：{$fcuInfo['fcuId']}");
                    }

                }else{
                    $this->log("找不到产品线：{$skuId} - {$item['fcuId']}");
                }

                //https://master-nodejs-poms-list-nest.ux168.cn/api/fcu_sku_maps/queryPage

            }

        }


    }


    public function updateProductFba(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/fba.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            foreach ($fileContent as $info){
                $fbaInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product_fba_bases/queryPage",[
                    "skuId" => $info['sku'],
                    "channel" => $info['上架渠道'],
                    "dcId" => $info['仓库'],
                    "limit" => 1,
                ]));
                if (!empty($fbaInfo)){
                    $fbaInfo['status'] = "Y";
                    $res = DataUtils::getResultData($curlService->s3015()->put("product_fba_bases/{$fbaInfo['_id']}",$fbaInfo));
                    $this->log("更新成功".json_encode($res,JSON_UNESCAPED_UNICODE));

                }
            }


        }


    }

    public function deleteFC(){
        $chunk = [
            "FC2025031003310",
        ];
        $curlService = new CurlService();
        $curlService = $curlService->pro();
        $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage",[
            "batch_in" => implode(",",$chunk),
            "company" => "CR201706060001",
            "status" => "0",
            "limit" => 200,
        ]));
        if (!empty($list)){
            foreach ($list as $v){
                $del = DataUtils::getResultData($curlService->s3044()->delete("fcu_applys",$v['_id']));
                $this->log("删除：".json_encode($del,JSON_UNESCAPED_UNICODE));
            }
        }
    }

    public function combineFC(){
        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/FCU.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $batchList = array_unique(array_column($fileContent,"FCU"));
            $mainFCUBatch = [];
            $allFCMap = [];
            foreach (array_chunk($batchList,200) as $chunk){
                $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage",[
                    "batch_in" => implode(",",$chunk),
                    "company" => "CR201706060001",
                    "status" => "0",
                    "limit" => 200,
                ]));
                if (!empty($list)){
                    $mainFCUBatch = $list[0];
                    foreach ($list as $v){
                        $allFCMap[$v['_id']] = $v['batch'];
                    }
                }
            }

            foreach ($allFCMap as $_id => $batch) {
                if ($_id != $mainFCUBatch['_id']){
                    $fcuSkuMapList = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                        "main_id" => $_id,
                        "limit" => 10000,
                    ]));
                    if (count($fcuSkuMapList) > 0) {
                        foreach ($fcuSkuMapList as &$fcuSkuMapInfo) {
                            if ($fcuSkuMapInfo['main_id'] != $mainFCUBatch['_id']){
                                $fcuSkuMapInfo['main_id'] = $mainFCUBatch['_id'];
                                $res = DataUtils::getResultData($curlService->s3044()->put("fcu_sku_maps/{$fcuSkuMapInfo['_id']}",$fcuSkuMapInfo));
                                $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
                            }
                        }
                    }
                }
            }

        }

    }

    public function updateProductListNo(){
        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/productListNo.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $batchList = array_unique(array_column($fileContent,"productList"));
//            foreach (array_chunk($batchList,200) as $chunk){
//                $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage",[
//                    "productListNo_in" => implode(",",$chunk),
//                    "verticalDepartment" => "PA",
//                    "limit" => 200,
//                ]));
//                if (!empty($list)){
//                    foreach ($list as $v){
//                        $v['cancelDate'] = null;
//                        $v['assignedDate'] = "2025-03-06T14:00:02.000Z";
//                        $v['draftNum'] = 1;
//                        $v['supplierId'] = [4397];
//                        $res = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$v['_id']}",$v));
//                        $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
//                    }
//                }
//            }

//            foreach (array_chunk($batchList,200) as $chunk) {
//                $list1 = DataUtils::getPageList($curlService->ux168()->get("consignment_applys/queryPage", [
//                    "productListNo_in" => implode(",", $chunk),
//                    "limit" => 200,
//                ]));
//                if (!empty($list1)){
//                    foreach ($list1 as $v){
//                        $v['status'] = 2;
//                        $res = DataUtils::getResultData($curlService->ux168()->put("consignment_applys/{$v['_id']}",$v));
//                        $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
//                    }
//                }
//            }


//            foreach ($batchList as $productListNo) {
//                $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage", [
//                    "productListNo" => $productListNo,
//                    "limit" => 200,
//                ]));
//                if (!empty($list)) {
//                    foreach ($list as $v) {
//                        $v['status'] = "6";
//                        $v['supplierId'] = "4397";
//                        $res = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$v['_id']}", $v));
//                        $this->log("更新：" . json_encode($res, JSON_UNESCAPED_UNICODE));
//                    }
//                }
//            }


//            $res = DataUtils::getResultData($curlService->ux168()->get("product_development_logs/67cfe27d7601c30ae7aaee9f",[]));
//
//            if ($res){
//                //$res['supplierId'] = "[4397]";
//                $res['remark'] = "{\"draftBeginDate\":\"2025-03-06 14:00:00\",\"draftOverDate\":\"2025-03-06 21:00:00\",\"applyDate\":\"2025-03-06 14:00:02\",\"reason\":\"\"}";
//                DataUtils::getResultData($curlService->ux168()->put("product_development_logs/{$res['_id']}",$res));
//            }
            $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage",[
                "productListNo" => "QD202503040025",
                "limit" => 1,
            ]));
            if (!empty($list)) {
                foreach ($list as $res) {
//                    $res['cancelDate'] = null;
//                    $res['assignedDate'] = "2025-03-06T14:00:02.000Z";
//                    $res['draftNum'] = 1;
                    $res['draftInfos']['supplierId'] = [4397];
                    $ss = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$res['_id']}",$res));
                    if ($ss){

                    }
                }
            }


//            $app = [
//                "productListNo" => "QD202503040025",
//                "createdBy" => "poms_limin",
//                "modifiedBy" => "poms_limin",
//                "status" => "finish",
//                "remark" => "{\"draftBeginDate\":\"2025-03-06 14:00:00\",\"draftOverDate\":\"2025-03-06 21:00:00\",\"reason\":\"\"}}",
//                "type" => "寄卖",
//                "supplierId" => "4397",
//                "draftNum" => 1,
//                "url" => "",
//            ];
//            DataUtils::getResultData($curlService->ux168()->post("product_development_logs",$app));
        }

    }

    public function fixSkuPhotoProcess(){
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $ceBillNo = "CE202503260168";

//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168459");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168514");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd816855d");
//
//        die("1111");
//        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/ux168/getCeDetailByCeBillNo",[
//           "ceBillNo" => $ceBillNo
//        ]));
//        $skuIdList = array_column($res,"skuId");

        $skuIdList = ["a25032600ux2646"];

        $ceMasterCreatedOn = "2025-03-26T15:54:35.000Z";
        $skuMap = [];
        foreach (array_chunk($skuIdList,200) as $chunk){
            $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                "limit" => 200,
                "productId" => implode(",",$chunk)
            ]));
            foreach ($list as $info){
                if ($info['status'] == "completed"){
                    $secondVeroDateTime = "";
                    foreach ($info['reviewingList'] as $val){
                        if ($val['reviewingName'] == "secondVero"){
                            $secondVeroDateTime = $val['createdOn'];
                            break;
                        }
                    }
                    $skuMap[$info['productId']] = $secondVeroDateTime;
                }

            }
        }

        $batch = [];
        foreach ($skuMap as $skuId => $dateTime){
            $ss = DataUtils::getPageListInFirstData($curlService->s3015()->get("sku_photography_progresss/queryPage",[
                "skuId" => $skuId,
                "ceBillNo_in" => $ceBillNo,
                "limit" => 1,
            ]));
            if ($ss){
                continue;
            }
            $data = [
                "skuId" => $skuId,
                "batchName" => "",
                "ceBillNo" => $ceBillNo,
                "createdBy" => "zhouangang",
                "status" => "待拍摄",
                "createCeBillNoOn" => $ceMasterCreatedOn,
                "tempSkuId" => "",
                "salesType" => "寄卖",
                "infoCompletedOn" => $dateTime,
                "isInfoDrafted" => ""
            ];
            $batch[] = $data;
        }

        if (count($batch) > 0){
            $this->log(count($batch) . "个新增");
            $curlService->s3015()->post("sku_photography_progresss/createBatch",$batch);
        }
    }

    public function fixProductOpt(){
        $fileContent = (new ExcelUtils())->getXlsxData("../export/资料图片工单.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

//        $curlService->s3044()->delete("pa_product_optimizations","65d8477cac548325e88fd2c4");
//        die(1);

        if (sizeof($fileContent) > 0) {



            foreach ($fileContent as $info){
                $dataInfo = DataUtils::getPageListInFirstDataV2($curlService->s3044()->get("pa_product_optimizations/queryPage",[
                    "limit" => 1,
                    "skuId" => $info['skuId'],
                    "applyReasons" => $info['修改来源']
                ]));

                if (empty($dataInfo)){
                    continue;
                }

                $option = "";
                $status = 0;
                $operator = "";

                if ($info['状态'] == "作废"){
                    continue;
                    $option = '执行已作废';
                    $status = 102;
                    $operator = '执行';

                    $dataInfo['statusArrays'][] = [
                        "option" => $option,
                        "status" => $status,
                        "optionBy" => $info['执行人'],
                        "optionTime" => date("Y-m-d H:i:s",time())."Z",
                    ];
                    $dataInfo['remarkArrays'][] = [
                        "option" => $option,
                        "optionBy" => $info['执行人'],
                        "optionTime" => date("Y-m-d H:i:s",time())."Z",
                        "remark" => "运营要求取消执行",
                    ];

                    $updateData = [
                        "executor" => explode(",",$info['执行人']),
                        "modifiedBy" => $info['执行人'],
                        "modifiedOn" => $dataInfo['modifiedOn'],
                        "status" => $status,
                        "deleteBy" => $info['执行人'],
                        "deleteDate" => date("Y-m-d H:i:s", time()) . "Z",
                        "deleteRemarks" => "运营要求取消执行",
                        "statusArrays" => $dataInfo['statusArrays'],
                        "remarkArrays" => $dataInfo['remarkArrays'],
                    ];

                    $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}",$updateData);



                }else if($info['状态'] == "已完成"){
                    $exectorList = explode(",",$info['执行人']);

                    $updateData = [
                        "executor" => $exectorList,
                        "modifiedBy" => $exectorList[0],
                        "modifiedOn" => $dataInfo['modifiedOn'],
                        "status" => 3,
                        "completedBy" => $exectorList[0],
                        "completedDate" => date("Y-m-d H:i:s", time()) . "Z",
                    ];

                    $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}",$updateData);

                }



            }



        }






    }


    public function skuMaterialDocCreate(){
//        $curlService = new CurlService();
//        $curlService->local()->gateway()->getModule('pa');
//
//        $info = [
//            "createBy" => "zhouangang",
//            "skuId" => "a23051500ux0518",
//            "updateType" => "UpdateAttribute",
//        ];
//
//
//        $resp3 = DataUtils::getNewResultData($curlService->getWayPost("/sms/sku/material/changed_doc/v1/initSkuMaterialChangedDoc", $info));
//        if ($resp3){
//            $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
//        }

        $curlService = (new CurlService())->pro();

        $list = [
            "g25042500ux8095",
            "g25042500ux8129",
            "g25042500ux8109",
            "g25042500ux8108",
            "g25042500ux8119",
            "g25042500ux8118",
            "g25042500ux8093",
            "g25042500ux8094",
            "g25042500ux8128",
            "s25042501ux0957",
            "s25042501ux0954",
            "s25042501ux0958",
            "s25042501ux0953",
            "s25042501ux0945",
            "s25042501ux0998",
            "s25042501ux0991",
            "s25042501ux0987",
            "s25042501ux0968",
            "s25042501ux0974",
            "s25042501ux0969",
            "s25042501ux0983",
            "s25042501ux0978",
            "s25042501ux0977",
            "s25042501ux0944",
            "s25042501ux0955",
            "s25042501ux0946",
            "s25042501ux0995",
            "s25042501ux0986",
        ];
        foreach ($list as $sku){
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                "productId" => $sku
            ]));
            if ($info){
                foreach ($info['attribute'] as &$item){
                    if ($item['channel'] == "walmart_us" && $item['label'] == "brand"){
                        $item['value'] = "NOMADIC NOOK";
                    }
                }
                $info['userName'] = "system(zhouangang)";

                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$info['_id']}",$info);
                if ($resp){

                }
            }
        }



    }

    public function getAmazonSpKeyword(){
//        $curlService = new CurlService();
//        $list = [];
//        for ($page = 1; $page < 500; $page++) {
//            $this->log("{$page}");
//            $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_sku_materials/queryPage", [
//                "limit" => 5000,
//                "createdOn_gt" => "2023-01-01",
//                "page" => $page
//            ]));
//            if ($resp) {
//                foreach ($resp as $info) {
//                    if (isset($info['fitment']) && !empty($info['fitment'])) {
//                        foreach ($info['fitment'] as $keyword) {
//                            $list[] = [
//                                "skuId" => $info['skuId'],
//                                "model" => $keyword['model']
//                            ];
//                        }
//                    }
//                }
//            } else {
//                break;
//            }
//        }
//
//        if (count($list) > 0){
////            $this->log(count($models));
////            foreach ($models as $info){
////
////            }
////
////            $curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage",[
////                "keywordText_in" => implode(",",$models)
////            ]);
//            $excelUtils = new ExcelUtils();
//            $filePath = $excelUtils->downloadXlsx(["skuId","model"],$list,"sku资料呈现热销词_".date("YmdHis").".xlsx");
//            $this->log($filePath);
//        }

        $curlService = new CurlService();

        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现热销词_20250429223726.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $keywordTexts = [];
            foreach ($fileContent as $info){
                if ($info['model']){
                    $keywordTexts[] = $info['model'];
                }
            }
            $keywordInfoList = [];
            foreach (array_chunk($keywordTexts,300) as $chunk){
                $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage",[
                    "keywordText_in" => implode(",",$chunk),
                    "columns" => "channel,keywordId,campaignId,adGroupId,state,keywordText,matchType,bid,createdOn",
                    "createdBy" => "php_restful_commonPaNewCreateKeywordsByType",
                    "state" => "enabled",
                    "limit" => 10000
                ]));
                if (count($list) > 0){
                    foreach ($list as $info){
                        $keywordInfoList[] = [
//                            "_id" => $info['_id'],
//                            "messages" => $info['messages'],
                            "channel" => $info['channel'],
                            "keywordId" => "'{$info['keywordId']}",
                            "campaignId" => "'{$info['campaignId']}",
                            "adGroupId" => "'{$info['adGroupId']}",
                            "state" => $info['state'],
                            "keywordText" => $info['keywordText'],
                            "matchType" => $info['matchType'],
                            "bid" => $info['bid'],
                            "createdOn" => $info['createdOn']
                        ];
                    }

                }
            }


            if (count($keywordInfoList) > 0) {
                foreach (array_chunk($keywordInfoList,15000) as $chunk){
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
//                        "_id",
//                        "messages",
                        "channel",
                        "keywordId",
                        "campaignId",
                        "adGroupId",
                        "state",
                        "keywordText",
                        "matchType",
                        "bid",
                        "createdOn",
                    ], $chunk, "新热词keyword投放_" . date("YmdHis") . ".xlsx");
                }

            }


        }


    }

    public function syncSkuSellerConfig(){
        $curlService = new CurlService();
        $info = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("seller-configs/queryPage",[
            "sellerId" => "amazon_ca_ifn",
        ]));
        if ($info){
            unset($info['_id']);
            $curlService->uat()->s3015()->post("seller-configs",$info);
        }


        $info1 = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("sku-seller-configs/queryPage",[
            "sellerId" => "amazon_ca_ifn",
            "skuId" => "a24101800ux0691",
        ]));
        if ($info1){
            unset($info1['_id']);
            $curlService->uat()->s3015()->post("sku-seller-configs",$info1);
        }
    }
    public function updatePaGoodsSourceManage(){
//        $curlService = new CurlService();
//        $list = [];
//        for ($page = 1; $page < 25; $page++) {
//            $this->log("{$page}");
//            $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_sku_materials/queryPage", [
//                "limit" => 5000,
//                "createdOn_gt" => "2024-06-01",
//                "page" => $page
//            ]));
//            if ($resp) {
//                foreach ($resp as $info) {
//                    if (isset($info['fitment']) && !empty($info['fitment'])) {
//                        foreach ($info['fitment'] as $keyword) {
//                            $list[] = [
//                                "skuId" => $info['skuId'],
//                                "model" => $keyword['model']
//                            ];
//                        }
//                    }
//                }
//            } else {
//                break;
//            }
//        }
//
//        if (count($list) > 0){
////            $this->log(count($models));
////            foreach ($models as $info){
////
////            }
////
////            $curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage",[
////                "keywordText_in" => implode(",",$models)
////            ]);
//            $excelUtils = new ExcelUtils();
//            $filePath = $excelUtils->downloadXlsx(["skuId","model"],$list,"sku资料呈现热销词.xlsx");
//            $this->log($filePath);
//        }

        $curlService = new CurlService();

        $curlService = $curlService->pro();

        $list = DataUtils::getPageList($curlService->s3015()->get("pa_goods_source_manages/queryPage",[
            "limit" => 1000,
            "ceBillNoOrCeBillNoNew_in" => "CE202503280130",
        ]));

        foreach ($list as $info){
            $info['ceDate'] = "2025-03-28T16:47:58.000Z";
            $curlService->s3015()->put("pa_goods_source_manages/{$info['_id']}",$info);
        }

    }
    public function updateSkuMaterial(){
        $curlService = new CurlService();
        $list = [];

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/CEB.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $item){

                $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1,
                    "ceBillNo" => $item['ce_bill_no'],
                ]));
                if (count($resp) > 0) {
                    $this->log("{$item['ce_bill_no']}");
                    $info = $resp[0];
                    if (count($info['skuIdList']) > 0){
                        $sku = $info['skuIdList'][0];
                        $productInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("product-skus/queryPage",[
                            "productId" => $sku
                        ]));

                        if ($productInfo){
                            $this->log("修改CE数据：原运营：{$info['saleName']} -> 新运营：{$productInfo['salesUserName']}");
                            $info['saleName'] = $productInfo['salesUserName'];
                            $curlService->pro()->s3044()->put("pa_ce_materials/{$info['_id']}",$info);
                        }
                    }

                }


            }
        }

//        foreach ($ceMap as $item => $ss){
//            $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_ce_materials/queryPage", [
//                "limit" => 1,
//                "ceBillNo" => $ss['ceBillNo'],
//            ]));
//            if (count($resp) > 0) {
//                $info = $resp[0];
//                $info['skuIdList'] = $ss['skuIdList'];
//                $curlService->pro()->s3044()->put("pa_ce_materials/{$info['_id']}",$info);
//            }else{
//
//
//            }
//
//
//
//
//        }



    }


    public function syncSkuMaterialToAudit(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('pa');

        $resp1 = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/page", [
            "pageNum" => 1,
            "pageSize" => 200,
            "applyStatus" => 20
        ]));

        $batchNameList = [];
        if ($resp1 && count($resp1['list']) > 0){
            foreach ($resp1['list'] as $info){
                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
                    $batchNameList[] = $info['docNumber'];
                }
            }
        }
        $batchNameList = [
            "2025042100020",
            "2025042100019",
            "2025042100018",
            "2025042100017",
        ];
        if (count($batchNameList) > 0) {
            $this->log("一共：".count($batchNameList)."个单据翻译失败，");
            $this->log(json_encode($batchNameList,JSON_UNESCAPED_UNICODE));
            foreach ($batchNameList as $item){
                $postParams = [
                    "docNumbers" => [$item],
                    "operatorName" => "P3-fixTranslationFail"
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/syncSkuMaterialToAudit", $postParams));

                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
            }

        }
    }

    public function copyNewChannel(){
        $curlService = (new CurlService())->pro();


        $oldChannel = "amazon_de";
        $newChannel = "amazon_nl";

        $list = [];
        for ($page = 1;$page < 10;$page ++){
            $resp1 = DataUtils::getPageList($curlService->s3015()->get("channel-amazon-attributes/queryPage", [
                "limit" => 5000,
                "page" => $page,
                "channel" => $oldChannel
            ]));
            if (count($resp1) > 0){
                $list = array_merge($list,$resp1);
            }else{
                break;
            }
        }
        if (count($list) > 0) {
            //$curlService = (new CurlService())->test();
            foreach ($list as $info){
                unset($info['_id']);
                $info['channel'] = $newChannel;
                $curlService->s3015()->post("channel-amazon-attributes", $info);
            }

        }
    }


    public function syncPaSkuMaterial(){
        $curlService = (new CurlService())->pro();


        $skuIdList = ["a25031700ux0462"];

        $list = [];

        $resp1 = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
            "limit" => 5000,
            "page" => 1,
            "skuId_in" => implode(",",$skuIdList)
        ]));
        if (count($resp1) > 0){
            $list = array_merge($list,$resp1);
        }

        if (count($list) > 0) {
            $curlService = (new CurlService())->test();
            $testList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage",[
                "limit" => 5000,
                "page" => 1,
                "skuId_in" => implode(",",$skuIdList)
            ]));
            $skuIdMap = array_column($testList,null,"skuId");

            foreach ($list as $info){
                if (isset($skuIdMap[$info['skuId']])){
                    $curlService->s3044()->delete("pa_sku_materials/{$skuIdMap[$info['skuId']]['_id']}");
                }
                $curlService->s3044()->post("pa_sku_materials", $info);
            }

        }

    }


    public function fix(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/pmo.xlsx");

        $fileFitContent1 = (new ExcelUtils())->getXlsxData("../export/DPMO250424008-黎乾海.xlsx");

        $fitmentSkuMap = [];
        $map = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){
                $map[$info['outside_title']] = $info['id'];
            }
        }

        $skuList = [];
        if (sizeof($fileFitContent1) > 0){
            foreach ($fileFitContent1 as $info){
                if (isset($map[$info['productLineName']])){
                    $skuList[] = [
                        "devSkuPkId" => $map[$info['productLineName']],
                        "skuId" => $info['skuId']
                    ];
                }
                $map2[$info['productLineName']] = $info['skuId'];
            }
        }


        $pmoArr = [
            "prePurchaseBillNo" => "DPMO250424008",
            "skuList" => $skuList,
//            "pmoBillNo" => "PMO2025042500159",
            "operatorName" => "zhouangang",
            "purchaseHandleStatus" => 70
        ];
        $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $pmoArr));
        if ($resp){

        }



    }


    public function syncDevSkuInfoToProductSku(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');
        $skuIdList = [
            "a25051800ux0237",
            "a25051800ux0238",
            "a25051800ux0239",
            "a25051800ux0240",
            "a25051800ux0242",
            "a25051800ux0243",
            "a25051800ux0244",
            "a25051800ux0245",
            "a25051800ux0246",
            "a25051800ux0247",
            "a25051800ux0248",
            "a25051800ux0249",
            "a25051800ux0250",
            "a25051800ux0251",
            "a25051800ux0252",
            "a25051800ux0253",
            "a25051800ux0254",
            "a25051800ux0255",
            "a25051800ux0256",
            "a25051800ux0257",
            "a25051800ux0258",
            "a25051800ux0259",
            "a25051800ux0260",
            "a25051800ux0261",
            "a25051800ux0262",
            "a25051800ux0263",
            "a25051800ux0264",
            "a25051800ux0265",
            "a25051800ux0266",
            "a25051800ux0267"
        ];
        foreach ($skuIdList as $sku){
            $pmoArr = [
                "initSkuId" => $sku,
                "operatorName" => "system(zhouangang)",
                "prePurchaseBillNo" => "QD202505130005"
            ];
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/syncDevSkuInfoToProductSku", $pmoArr));
            if ($resp){

            }
        }

    }


    public function updateSalesUserNameCancel2(){
        //清掉小语种2的
        $env = "pro";
        $userList = [
            [
                "old"=>"huangziming2",
                "new"=>"huangziming",
            ],
            [
                "old"=>"dengyanhui2",
                "new"=>"dengyanhui",
            ],
            [
                "old"=>"dengsimin2",
                "new"=>"dengsimin",
            ],
            [
                "old"=>"chenwenzhen2",
                "new"=>"chenwenzhen",
            ],
            [
                "old"=>"weishunyun2",
                "new"=>"weishunyun",
            ],
            [
                "old"=>"yangjinling2",
                "new"=>"yangjinling",
            ],
            [
                "old"=>"zhoujianheng2",
                "new"=>"zhoujianheng",
            ],
            [
                "old"=>"liangdanhua2",
                "new"=>"liangdanhua",
            ],
            [
                "old"=>"litingjie2",
                "new"=>"litingjie",
            ],
            [
                "old"=>"liuxiaoyu2",
                "new"=>"liuxiaoyu",
            ],
            [
                "old"=>"chenlishan2",
                "new"=>"chenlishan",
            ],
            [
                "old"=>"guoziying2",
                "new"=>"guoziying",
            ],
            [
                "old"=>"zhuyuqing2",
                "new"=>"zhuyuqing",
            ],
            [
                "old"=>"zhenghailing2",
                "new"=>"zhenghailing",
            ],
            [
                "old"=>"huangxiaojiong2",
                "new"=>"huangxiaojiong",
            ]
        ];

        $this->Mongo3009Sql($userList);
        $this->Mongo3015Sql($userList);
        $this->Mongo3044Sql($userList);






    }


    public function Mongo3009Sql($userList){

        $dbList = [
            [
                "ku" => "product_operator_line",
                "field" => "operatorName"
            ],
            [
                "ku" => "product_operator_line",
                "field" => "developer"
            ],
            [
                "ku" => "product_operator_line",
                "field" => "userName"
            ],
            [
                "ku" => "product_operator_main_info",
                "field" => "developer"
            ],
            [
                "ku" => "product_operator_main_info",
                "field" => "traceMan"
            ],
            [
                "ku" => "skuId_info",
                "field" => "developer"
            ],
            [
                "ku" => "skuId_info",
                "field" => "traceman"
            ],
            [
                "ku" => "skuId_info_main_table",
                "field" => "traceman"
            ],
        ];
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }

    public function Mongo3015Sql($userList){

        $dbList = [
            [
                "ku" => "translation_management",
                "field" => "submitUserName"
            ],
            [
                "ku" => "translation_management",
                "field" => "applyUserName"
            ],
            [
                "ku" => "translation_management",
                "field" => "importUserName"
            ],
            [
                "ku" => "pa_sku_info",
                "field" => "ebaySalesUser"
            ],
            [
                "ku" => "pa_sku_info",
                "field" => "developerUserName"
            ],
            [
                "ku" => "pa_sku_info",
                "field" => "amazonSalesUser"
            ],
            [
                "ku" => "product_base_info",
                "field" => "salesUserName"
            ],
            [
                "ku" => "product_sku",
                "field" => "salesUserName"
            ],
        ];
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }

    public function Mongo3044Sql($userList){

        $dbList = [
            [
                "ku" => "pa_ce_material",
                "field" => "ebayTraceMan"
            ],
            [
                "ku" => "pa_ce_material",
                "field" => "developer"
            ],
            [
                "ku" => "pa_ce_material",
                "field" => "saleName"
            ]
        ];
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }




}

$curlController = new SyncCurlController();
//$curlController->fix();
//$curlController->syncSkuMaterialToAudit();
//$curlController->fixPaSkuPhotoGress();
//$curlController->updateSkuMaterial();
//$curlController->syncPaSkuMaterial();
//$curlController->copyNewChannel();
//$curlController->updatePaGoodsSourceManage();
//$curlController->getAmazonSpKeyword();
//$curlController->syncSkuSellerConfig();
//$curlController->skuMaterialDocCreate();
//$curlController->fixProductOpt();
//$curlController->fixSkuPhotoProcess();
//$curlController->updateProductListNo();
//$curlController->deleteFC();
//$curlController->combineFC();
//$curlController->updateProductFba();
//$curlController->updateFcuProductLine();
//$curlController->getPaSkuMaterial();
//$curlController->syncAllVerticalMonthlTargets();
//$curlController->ceWrite();
//$curlController->updateCeMaterialPlatform();
//$curlController->updatePaProductTempSkuIdNew();
//$curlController->writeProductBaseFba();
//$curlController->writeScmsPurchaseBillNo();
//$curlController->get30PpmsByTempskuid();
$curlController->updateSalesUserNameCancel2();
//$curlController->syncDevSkuInfoToProductSku();
//$curlController->saveReceiveIpCheck();
//$curlController->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => "20201221 - 李锦烽 - 1"]);
//$curlController->deleteCampaign();