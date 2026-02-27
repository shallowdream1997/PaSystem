<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';
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
            "ceBillNo_in" => "CE202509250127"
        ],"pro");
        foreach ($list as &$item){
            $item['createCeBillNoOn'] = "2025-03-28T16:47:58.000Z";
            $this->commonUpdate("s3015","sku_photography_progresss",$item,"pro");
        }

    }

    public function deleteCeMaterial()
    {
        $curlService = (new CurlService())->pro();
        $mainList = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
            "limit" => 1000,
            "batchName" => "QD202508040008",
        ]));
        if (count($mainList) > 0) {

            foreach ($mainList as $index => $main){
                if ($index == 1){
                    continue;
                }
                $curlService->s3044()->delete("pa_ce_materials/{$main['_id']}");
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
            "a09052200ux0075",
            "a09052200ux0065",
            "a09052300ux0012",
        ];
        $curlSsl = (new CurlService())->test();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
            "skuIdList" => $t,
            "attrCodeList" => [
//                "custom-skuInfo-tempSkuId",
//                "custom-skuInfo-consignmentPrice",
//                "min_arrival_quantity",
//                "custom-common-salesUserName",
//                "custom-skuInfo-factoryId",
//                "custom-skuInfo-supplierProductNo",
//                "custom-skuInfo-outsideTitle",
//                "custom-skuInfo-supplierId",
                "custom-common-salesUserName",
                "custom-skuInfo-skuId",
                "custom-common-minorSalesUserName"
            ]
        ]));
        if ($getKeyResp){
//            $tempIdsList = [
//                [
//                    "id" => "1879052194089963565",
//                    "temp_sku_id" => "T250114000191"
//                ], [
//                    "id" => "1879052194089963718",
//                    "temp_sku_id" => "T250114000344"
//                ], [
//                    "id" => "1879052194089963719",
//                    "temp_sku_id" => "T250114000345"
//                ], [
//                    "id" => "1879052194089963722",
//                    "temp_sku_id" => "T250114000348"
//                ], [
//                    "id" => "1879052194089963723",
//                    "temp_sku_id" => "T250114000349"
//                ], [
//                    "id" => "1879052194089963724",
//                    "temp_sku_id" => "T250114000350"
//                ], [
//                    "id" => "1879052194089963725",
//                    "temp_sku_id" => "T250114000351"
//                ], [
//                    "id" => "1879052194089963726",
//                    "temp_sku_id" => "T250114000352"
//                ], [
//                    "id" => "1879052194089963728",
//                    "temp_sku_id" => "T250114000354"
//                ], [
//                    "id" => "1879052194089963733",
//                    "temp_sku_id" => "T250114000359"
//                ]
//            ];
//            $tempIdsIdMap = [];
//            foreach ($tempIdsList as $sss.txt){
//                $tempIdsIdMap[$sss.txt['temp_sku_id']] = $sss.txt['id'];
//            }
//            $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
//            $preSkuList = [];
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

//            $fileContent = (new ExcelUtils())->getXlsxData("../export/qd/补充的T号.xlsx");
//            $titleCnMap = [];
//            foreach ($fileContent as $info){
//                $titleCnMap[$info['titleCn']] = $info;
//            }
//            foreach ($getKeyResp as $info){
//                if (isset($titleCnMap[$info['custom-skuInfo-outsideTitle']])){
//                    $preSkuList[] = [
//                        "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
//                        "skuId" => $titleCnMap[$info['custom-skuInfo-outsideTitle']]['skuId']
//                    ];
//                }
//            }
//            if ($preSkuList){
//                $writeData = [
//                    "prePurchaseBillNo" => "QD202504080024",
//                    "ceBillNo" => "CE202505050082",
////                    "skuList" => $preSkuList,
//                    "operatorName" => "zhouangang",
////                    "purchaseHandleStatus" => 70$tempIdsIdMap = {数组} [10]
//                ];
//
//                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
//                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
//                if ($getKeyResp){
//                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
//                }
//            }
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

        $data = [
            "prePurchaseBillNo" => "DPMO251231003",
            "ceBillNo" => "CE202601050017",
            "operatorName" => "system(PA-CE回写)"
        ];
        $curlService1 = new CurlService();
        $curlService1->$env()->gateway()->getModule('pa');
        $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $data));
        if ($resp3){
            $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
        }
        die(1111);
        $pmoList = [
            "DPMO251231005-黎乾海",
        ];
        $curlService = new CurlService();
        foreach ($pmoList as $pmoBillNo){
            $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getMainSkuIdInfo", [
                "batch" => $pmoBillNo
            ]);
            if (count($resp['result'])>0){
                $pmoInfo = $resp['result'][0];
                if ($pmoInfo){
                    $resp1 = $curlService->$env()->s3009()->post("cmo-managements/masterQuery", [
                        "conditionsJsonEncode" => json_encode(["pmoBillNoList" => [$pmoInfo['pmoBillNo']]], JSON_UNESCAPED_UNICODE),
                        "entriesPerPage" => 10,
                        "orderBy" => "cmoBillNo desc",
                        "pageNumber" => 1
                    ]);
                    if ($resp1 && $resp1['result'] && $resp1['result']['cmoMasterResponse'] && $resp1['result']['cmoMasterResponse']['cmoMasters'] && count($resp1['result']['cmoMasterResponse']['cmoMasters']) > 0){
                        foreach ($resp1['result']['cmoMasterResponse']['cmoMasters'] as $cmoBillNoInfo){
                            if ($cmoBillNoInfo){
                                $resp2 = $curlService->$env()->s3009()->get("cmo-managements/cmoMasterProgress", [
                                    "cmoBillNo" => $cmoBillNoInfo['cmoBillNo'],
                                ]);
                                $list = [];
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
                                                    "operatorName" => "system(PA-CE回写)"
                                                ];
                                                break;
                                            }
                                        }
                                        break;
                                    }
                                }

                                if (count($list) > 0){
                                    foreach ($list as $info){
                                        if ($info['ceBillNo'] == "CE202508060047"){
                                            continue;
                                        }
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
                        }
                    }
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

        $ceBillNo = "CE202509250127";

//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168459");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168514");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd816855d");
//
//        die("1111");
//        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/ux168/getCeDetailByCeBillNo",[
//           "ceBillNo" => $ceBillNo
//        ]));
//        $skuIdList = array_column($res,"skuId");

        $skuIdList = [
            "a25092500ux0903",
            "a25092500ux0904",
            "a25092500ux0902",
        ];

        $ceMasterCreatedOn = "2025-09-25T20:53:15.000Z";
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
        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/lianjie.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {

            $curlService = new CurlService();

            $curlService = $curlService->pro();

            $skuIdList = array_column($fileContent,"skuid");
            $map = [];
            foreach ($fileContent as $info){
                $map[$info['skuid']] = $info['ppmcaigoulianjie'];
            }
            $list = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $list = DataUtils::getPageList($curlService->s3015()->get("pa_goods_source_manages/queryPage",[
                    "limit" => 200,
                    "skuId_in" => implode(",",$chunk),
                ]));
                if (count($list) > 0){
                    foreach ($list as $item){
                        if (isset($map[$item['skuId']]) && $map[$item['skuId']]){
                            $item['purchaseLink'] = $map[$item['skuId']];
                            $item['modifiedBy'] = "zhouangang(修复采购链接)";
                            $curlService->s3015()->put("pa_goods_source_manages/{$item['_id']}",$item);
                        }else{
                            $this->log("{$item['skuId']}没有采购链接");
                        }
                    }
                }
            }

        }


    }
    public function updateSkuMaterial(){
        $curlService = (new CurlService())->pro();
        $list = [];

//        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/CEB.xlsx");
        $fitmentSkuMap = [];

        $fileFitContent = [
            ['ce_bill_no' => 'CE202512260065'],
        ];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $item){


                $main = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1,
                    "ceBillNo" => $item['ce_bill_no'],
                ]));
                if (count($main) > 0) {
                    $this->log("{$item['ce_bill_no']}");
                    $main['status'] = 'materialComplete';
                    $curlService->s3044()->put("pa_ce_materials/{$main['_id']}",$main);

                    if (count($main['skuIdList']) > 0){
                        foreach ($main['skuIdList'] as $sku){

                            $detail = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                "limit" => 1,
                                "page" => 1,
                                "skuId" => $sku,
                                "ceBillNo" => $main['ceBillNo'],
                            ]));
                            if ($detail){
                                $detail['status'] = "materialComplete";
                                $curlService->s3044()->put("pa_sku_materials/{$detail['_id']}",$detail);
                            }
                        }

                    }

                }


            }



        }


    }


    public function syncSkuMaterialToAudit(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('pa');

        $resp1 = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/page", [
            "pageNum" => 1,
            "pageSize" => 500,
            "applyStatus" => 30
        ]));

        $batchNameList = [];
        if ($resp1 && count($resp1['list']) > 0){
            foreach ($resp1['list'] as $info){
//                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
//                    $batchNameList[] = $info['docNumber'];
//                }
                $batchNameList[] = $info['docNumber'];
            }
        }
//        $batchNameList = [
//            "2025080700056",
//        ];
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

    public function skuMaterialSyncToProductSku(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');

        $resp1 = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/material/changed_doc/v1/page", [
            "pageNum" => 1,
            "pageSize" => 500,
            "applyStatus" => 30
        ]));

        $batchNameList = [];
        if ($resp1 && count($resp1['list']) > 0){
            foreach ($resp1['list'] as $info){
//                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
//                    $batchNameList[] = $info['docNumber'];
//                }
                $batchNameList[] = $info['docNumber'];
            }
        }
//        $batchNameList = [
//            "2025080700056",
//        ];
        if (count($batchNameList) > 0) {
            $this->log("一共：".count($batchNameList)."个单据翻译失败，");
            $this->log(json_encode($batchNameList,JSON_UNESCAPED_UNICODE));
            foreach ($batchNameList as $item){
                $postParams = [
                    "docNumbers" => [$item],
                    "operatorName" => "P3-fixTranslationFail"
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/skuMaterialSyncToProductSku", $postParams));

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
                "old"=>"dengyiyi2",
                "new"=>"dengyiyi",
            ]
        ];

//        $this->Mongo3009Sql($userList);
//        $this->Mongo3015Sql($userList);
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
//            [
//                "ku" => "pa_product",
//                "field" => "amazonTraceMan"
//            ],
//            [
//                "ku" => "pa_product",
//                "field" => "ebayTraceMan"
//            ],
//            [
//                "ku" => "pa_product",
//                "field" => "developer"
//            ],
//            [
//                "ku" => "pa_product",
//                "field" => "traceMan"
//            ],
//            [
//                "ku" => "product_base_info",
//                "field" => "developerUserName"
//            ],
//            [
//                "ku" => "product_sku",
//                "field" => "developerUserName"
//            ],
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


    public function createPmo(){
        ///scms/pmo_plan/v1/createPmo

        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');
        $ids = [
            "1995855479292080128",
            "1996202792271745024",
            "1996565180330409984",
            "1996565180775006208",
            "1996565181248962560",
            "1996565181689364480",
            "1996565182133960704",
            "1996565182670831616",
            "1996565183220285440",
            "1996565183576801280",
            "1996565183979454464",
            "1996580252532473856",
            "1996580260438736896",
            "1996580262074515456",
        ];

        foreach ($ids as $id){
//            $pmoArr = [
//                "id" => $id,
//            ];
//            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pmo_plan/v1/createPmo", $pmoArr));
//            if ($resp){
//
//            }



            $pmoArr = [
                "id" => $id,
            ];
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pmo_plan/v1/createPmo", $pmoArr));
            if ($resp){

            }
        }



    }


    public function updateZhixiao(){
        $curlService = (new CurlService())->pro();
        //$curlService->gateway();
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/滞销定价sku_1.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            $sellerIdProductIds = [];
            foreach ($fileFitContent as $info){
                $sellerIdProductIds[$info['账号']][] = $info['scuId'];
            }
            foreach ($sellerIdProductIds as $sellerId => $sellerProductIds){

                foreach (array_chunk($sellerProductIds,100) as $chunk){
                    $fbaInfo = DataUtils::getPageList($curlService->s3015()->get("channel-price-customizes/queryPage",[
                        "productId" => implode(",",$chunk),
                        "seller" => $sellerId,
                        "priceType" => "unsale",
                        "limit" => 100,
                    ]));

                    foreach ($fbaInfo as $info){
                        if ($info['status'] == "inactive"){
                            $this->log("{$info['productId']} 已经取消滞销，不需要再取消");
                            continue;
                        }
                        $info['status'] = "inactive";
                        $info['endTime'] = "2025-06-12T01:00:00.000Z";
                        $info['modifiedBy'] = "system(zhouangang)";
                        $res = DataUtils::getResultData($curlService->s3015()->put("channel-price-customizes/{$info['_id']}",$info));
                        $this->log("取消滞销价:{$info['productId']}");
                    }
                }
            }

        }


    }

    public function test(){

        $lines = [
            "导入：a25062500ux0563-车型",
            "导入：a25062300ux1376-cpAsin",
            "导入：a25062300ux1376-核心词",
            "导入：a25062300ux1376-车型",
            "更新：a25062700ux2364-车型",
            "导入：a25062700ux2364-cpAsin",
            "导入：a25062700ux2364-核心词",
            "导入：a25062700ux2364-车型",
            "保存a25062500ux1421",
        ];

        foreach ($lines as $line) {
            // 提取 "操作类型"
            $colonPos = strpos($line, '：');
            if ($colonPos !== false) {
                $action = substr($line, 0, $colonPos);
                $remaining = substr($line, $colonPos + 3); // 跳过 "：a"
            } else {
                // 处理 "保存a25062700ux2136" 这种情况
                $action = substr($line, 0, 6); // 取前6个字符（"保存"）
                $remaining = substr($line, 6); // 剩下的部分
            }

            // 提取 "a编号" 和 "后缀"
            $hyphenPos = strpos($remaining, '-');
            if ($hyphenPos !== false) {
                $aNumber = substr($remaining, 0, $hyphenPos);
                $suffix = substr($remaining, $hyphenPos + 1);
            } else {
                $aNumber = $remaining;
                $suffix = '';
            }

            echo "操作: $action, a编号: $aNumber, 后缀: $suffix\n";
        }

    }

    public function test1($a,$b,$c,$d){
        echo "{$a} {$b} {$c} {$d}";
    }


    /**
     * 修复垂直ID
     * @throws Exception
     */
    public function fixSkuVerticalId(){
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/修复SKU.xlsx");

        if (sizeof($fileFitContent) > 0) {

            $companyIdMap = [
                "PA" => "CR201706060001",
                "MRO" => "CR201706080001",
                "CSA" => "CR201706260001",
                "HG" => "CR201706060002",
                "PLG" => "CR2024052100001",
                "运营" => "CR201706080003",
            ];
            foreach ($fileFitContent as $info){
                $productInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => $info['skuid'],
                    "limit" => 1
                ]));
                if ($productInfo){
                    if (!$productInfo['verticalId']){
                        $productInfo['verticalId'] = $companyIdMap[$info['company']] ?? null;
                        $productInfo['action'] = "修复商家ID";
                        $productInfo['userName'] = "system(zhouangang)";

                        $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                        if ($resp){

                        }
                    }else{
                        $this->log("有商家ID，不需要修");
                    }

                }
            }

        }



    }



    /**
     * 修复垂直ID
     * @throws Exception
     */
    public function fixAmazonSpRuleId(){
        $curlService = (new CurlService())->pro();

        $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company_in" => "CR201706060001",
            "limit" => 500
        ]));
        if ($productInfo){
            foreach ($productInfo as &$info){
                if (isset($info['bindRule']) && count($info['bindRule']) > 0){
                    $update = false;
                    foreach ($info['bindRule'] as &$bind){
                        if ($bind['status'] == 0){
                            $startDate = '2025-06-18';
                            if (strpos($info['modifiedOn'], $startDate) === 0) {
                                $this->log("{$info['sellerId']} - {$bind['spType']} - {$info['modifiedOn']}");
                            }

                            foreach ( $bind['ruleTypeAndId'] as &$sss){
                                if ($sss['ruleType'] == 'campaignRuleBySystem' && $sss['ruleId']){
                                    $sss['ruleId'] = "";
                                    $update = true;
                                }
                            }
                        }
                    }
                    if ($update){
                        $this->log("更新");
                        $this->log(json_encode($info,JSON_UNESCAPED_UNICODE));
                        //$curlService->s3023()->put("amazon_sp_sellers/{$info['_id']}",$info);
                    }
                }
            }
        }

    }

    /**
     * 查询本身就是未设置的数据，但是依然投放了campaign的数据
     * @throws Exception
     */
    public function findCampaign(){
        $curlService = (new CurlService())->pro();

        $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company_in" => "CR201706060001",
            "limit" => 500
        ]));
        if ($productInfo){
            $export = [];
            $sameCampaign = [];
            $manualAllZero = [];
            foreach ($productInfo as &$info){
                if (isset($info['bindRule']) && count($info['bindRule']) > 0){
                    $update = false;
                    foreach ($info['bindRule'] as &$bind){
                        if ($bind['status'] == 0){

                            $targetingType = $bind['spType'];
                            if (strpos($bind['spType'],"manual") === 0){
                                $targetingType = "manual";
                                $this->log("{$info['sellerId']} - {$bind['spType']} - {$info['createdOn']} - {$info['modifiedOn']}");
                                //$
                                $manualAllZero[$info['sellerId']][$bind['spType']] = $bind['status'];

                            }
//                                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
//                                    "company" => "CR201706060001",
//                                    "channel" => $info['sellerId'],
//                                    "targetingType" => $targetingType,
//                                    "createdOn_gte" => $info['createdOn'],
//                                    "limit" => 2000
//                                ]));
//                                if (count($list) > 0){
//                                    foreach ($list as $info){
//                                        if (!isset($sameCampaign[$info['campaignName']])){
//                                            $export[] = [
////                                                "_id" => $info['_id'],
//                                                "status" => $info['status'],
//                                                "channel" => $info['channel'],
//                                                "campaignName" => $info['campaignName'],
//                                                "campaignId" => $info['campaignId'],
//                                                "targetingType" => $info['targetingType'],
//                                                "state" => $info['state'],
//                                                "createdOn" => $info['createdOn'],
//                                            ];
//                                            $sameCampaign[$info['campaignName']] = 1;
//                                            $this->log("唉有投放，错误的");
//                                        }
//                                    }
//                                }



                        }
                    }
                }
            }
//            if (count($export) > 0){
//                $excelUtils = new ExcelUtils();
//                $downloadOssLink = "未设置广告已经投放的_" . date("YmdHis") . ".xlsx";
//                $filePath = $excelUtils->downloadXlsx(["status","channel","campaignName","campaignId","targetingType","state","createdOn"],$export,$downloadOssLink);
//                $this->log($filePath);
//            }

            $this->log(json_encode($manualAllZero,JSON_UNESCAPED_UNICODE));


        }

    }


    public function downloadPaSkuPhotoProgress()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                "photoOn_lte" => "2025-06-20T23:59:59Z",
                "photoOn_gte" => "2025-05-01T00:00:00Z",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $list[] = [
                    "batchName" => $info['batchName'],
//                    "salesType" => $info['salesType'],
//                    "tempSkuId" => $info['tempSkuId'],
                    "ceBillNo" => $info['ceBillNo'],
                    "createCeBillNoOn" => $info['createCeBillNoOn'],
                    "skuId" => $info['skuId'],
                    "status" => $info['status'],
                    "photoBy" => $info['photoBy'],
                    "photoOn" => $info['photoOn'],
                ];
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
        }


    }


    public function week(){
        echo date("w",1750806000);
    }

    public function downloadPa()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                "photoOn_lte" => "2025-06-20T23:59:59Z",
                "photoOn_gte" => "2025-05-01T00:00:00Z",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $list[] = [
                    "batchName" => $info['batchName'],
//                    "salesType" => $info['salesType'],
//                    "tempSkuId" => $info['tempSkuId'],
                    "ceBillNo" => $info['ceBillNo'],
                    "createCeBillNoOn" => $info['createCeBillNoOn'],
                    "skuId" => $info['skuId'],
                    "status" => $info['status'],
                    "photoBy" => $info['photoBy'],
                    "photoOn" => $info['photoOn'],
                ];
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
        }


    }


    public function downloadPaSkuMaterialSP()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        foreach ([
                     "CE202503",
                     "CE202504",
                     "CE202505",
                     "CE202506",
                 ] as $ceBillNoLike) {
            $ceBillNoMap = [];
            $this->log($ceBillNoLike);
            $page = 1;
            do {
                $this->log($page);
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "ceBillNo_like" => $ceBillNoLike,
                    "limit" => 1000,
                    "page" => $page
                ]));
                if (count($l) == 0) {
                    break;
                }
                foreach ($l as $info) {
                    $ceBillNoMap[$info['ceBillNo']] = [
                        'developer' => $info['developer'],
                        'saleName' => $info['saleName']
                    ];
                }

                $page++;
            } while (true);


            if (count($ceBillNoMap) > 0){
                $keywordsList = [];
                $cpAsinList = [];
                $fitmentList = [];
                foreach ($ceBillNoMap as $ceBillNo => $info){
                    $this->log($ceBillNo."查询资料呈现");
                    $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                        "ceBillNo" => $ceBillNo,
                        "limit" => 1500,
                        "page" => 1
                    ]));
                    if (count($ll) == 0) {
                        break;
                    }
                    foreach ($ll as $item) {
                        foreach ($item['keywords'] as $k){
                            $keywordsList[] = [
                                'ceBillNo' => $ceBillNo,
                                'saleName' => $info['saleName'],
                                'skuId' => $item['skuId'],
                                'keywords' => $k
                            ];
                        }
                        foreach ($item['cpAsin'] as $cp){
                            $cpAsinList[] = [
                                'ceBillNo' => $ceBillNo,
                                'saleName' => $info['saleName'],
                                'skuId' => $item['skuId'],
                                'cpAsin' => $cp
                            ];
                        }
                        foreach ($item['fitment'] as $fit){
                            $fitmentList[] = [
                                'ceBillNo' => $ceBillNo,
                                'saleName' => $info['saleName'],
                                'skuId' => $item['skuId'],
                                'make' => $fit['make'],
                                'model' => $fit['model'],
                            ];
                        }
                    }
                }

                if (count($keywordsList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_keywords", $ceBillNoLike,json_encode($keywordsList,JSON_UNESCAPED_UNICODE));

                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "核心词",
                    ], $keywordsList, "{$ceBillNoLike}_核心词导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出核心词");
                }else{
                    $this->log("没有核心词");
                }

                if (count($cpAsinList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_cpasins", $ceBillNoLike,json_encode($cpAsinList,JSON_UNESCAPED_UNICODE));
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "CP ASIN",
                    ], $cpAsinList, "{$ceBillNoLike}_CP_Asin导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出CP ASIN");
                }else{
                    $this->log("没有CP ASIN");
                }

                if (count($fitmentList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_fitment", $ceBillNoLike,json_encode($fitmentList,JSON_UNESCAPED_UNICODE));
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "make",
                        "model",
                    ], $fitmentList, "{$ceBillNoLike}_fitment导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出fitment");
                }else{
                    $this->log("没有fitment");
                }

            }else{
                $this->log("{$ceBillNoLike}没有数据");
            }


        }




    }

    public function getQDDPMOBatchNameCeMaterialList()
    {
        $curlService = (new CurlService())->pro();
        $list = [];

        $page = 1;
        do {
            $this->log($page);
            $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 1000,
                "page" => $page,
                "orderBy" => "-_id"
            ]));
            if (count($l) == 0) {
                break;
            }
            foreach ($l as $info) {
                if (preg_match('/^(QD|DPMO)/', $info['batchName'])){
                    if (empty($info['saleNameList'])){
                        $list[] = $info['batchName'];
                    }
                }else{
                    $this->log("结束了");
                    break 2;
                }
            }
            $page++;
        } while (true);

        return $list;
    }

    public function updatePaSkuMaterial()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/1.xlsx");
        $batchNameList = [];
//        if (sizeof($fileFitContent) > 0) {
//            $batchNameList = array_unique(array_column($fileFitContent,"batchName"));
//        }else{
            $batchNameList = $this->getQDDPMOBatchNameCeMaterialList();
//        }

        if (count($batchNameList) > 0){
            $batchNameCeBillNoMap = [];
            foreach (array_chunk($batchNameList,100) as $chunkBatchNameList){
                $curlService->gateway();
                $curlService->getModule('pa');
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $chunkBatchNameList));
                if ($resp){
                    $this->log("获取数据");
                    foreach ($resp as $item){
                        $billNo = "";
                        if (isset($item['qdBillNo']) && $item['qdBillNo']) {
                            $billNo = $item['qdBillNo'];
                        }
                        if (isset($item['ceBillNo']) && $item['ceBillNo']) {
                            $billNo = $item['ceBillNo'];
                        }
                        $batchNameCeBillNoMap[$item['prePurchaseBillNo'] . $billNo] = [
                            "developerUserName" => $item['developerUserName'] ?? [],
                            "salesUserName" => $item['salesUserName'] ?? [],
                            "minorSalesUserName" => $item['minorSalesUserName'] ?? [],
                            "sourceDeveloperUserName" => $item['sourceDeveloperUserName'] ?? [],
                            "productLevelList" => $item['productLevel'] ?? [],
                            "platformList" => $item['platform'] ?? [],
                        ];
                    }
                }
            }

            $curlService = new CurlService();
            $curlService = $curlService->pro();
            foreach (array_chunk($batchNameList,300) as $chunkBatchNameList){
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => 1,
                    "batchName_in" => implode(",",$chunkBatchNameList)
                ]));
                if (count($l) == 0){
                    continue;
                }

                foreach ($l as $item){
                    $key = $item['batchName'] . $item['ceBillNo'];
                    if (isset($batchNameCeBillNoMap[$key])){
                        //存在数据
                        $this->log("有数据，更新");
                        $item['developerList'] = $batchNameCeBillNoMap[$key]['developerUserName'];
                        $item['saleNameList'] = $batchNameCeBillNoMap[$key]['salesUserName'];
                        $item['ebayTraceManList'] = $batchNameCeBillNoMap[$key]['minorSalesUserName'];
                        $item['platformList'] = $batchNameCeBillNoMap[$key]['platformList'];
                        $item['productLevelList'] = $batchNameCeBillNoMap[$key]['productLevelList'];
                        $res = $curlService->s3044()->put("pa_ce_materials/{$item['_id']}", $item);
                    }else{
                        $this->log("{$item['batchName']}没有数据");
                    }
                }

            }

        }else{
            $this->log("没有可以修改的数据");
        }
    }

    public function getCEBillNo()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/无ce单的自营sku数据_2025.xlsx");

        if(sizeof($fileFitContent) > 0){
            $list = array_unique(array_column($fileFitContent,"sku_id"));


            $curlSsl = (new CurlService())->pro();
            $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
                "skuIdList" => $list,
                "orderBy" => "",
                "pageNumber" => 1,
                "entriesPerPage" => 500
            ]));
            if ($getKeyResp && count($getKeyResp) > 0){

                $skuIdCeMap = [];
                foreach ($getKeyResp as $item){
                    $skuIdCeMap[$item['skuId']] = $item['ceBillNo'];
                }

                $curlSsl = (new CurlService())->pro();
                $getKeyResp1 = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                    "skuIdList" => $list,
                    "attrCodeList" => [
                        "custom-skuInfo-skuId",
                        "custom-prePurchase-prePurchaseBillNo"
                    ]
                ]));
                $map = [];
                if ($getKeyResp1){
                    foreach ($getKeyResp1 as $item){
                        $ceBillNo = "";
                        if (isset($skuIdCeMap[$item['custom-skuInfo-skuId']])){
                            $ceBillNo = $skuIdCeMap[$item['custom-skuInfo-skuId']];
                        }
                        if(!empty($ceBillNo)){
                            $map[$item['custom-prePurchase-prePurchaseBillNo']][$ceBillNo][] = $item['custom-skuInfo-skuId'];
                        }
                    }
                }

                if ($map){
                    foreach ($map as $prePurchaseBillNo => $ceBillNoMap){
                        foreach ($ceBillNoMap as $ceBillNo => $skuIds){
                            $this->log("{$prePurchaseBillNo} 开始回写: {$ceBillNo}". json_encode($skuIds,JSON_UNESCAPED_UNICODE));
                            $data = [
                                "prePurchaseBillNo" => $prePurchaseBillNo,
                                "ceBillNo" => $ceBillNo,
                                "operatorName" => "system(PA-CE回写)"
                            ];
                            $curlService1 = (new CurlService())->pro();
                            $curlService1->gateway()->getModule('pa');
                            $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $data));
                            if ($resp3){
                                $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
                            }
                            sleep(3);
                        }
                    }
                }



            }


        }




    }


    /**
     * 更新欧洲共享仓归属优先级配置
     * @return void
     */
    public function updateEuSharedWarehouseFlowTypePriority(){
        $curlService = new CurlService();
        $curlService = $curlService->test();
        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage",[
            "optionName" => "EuSharedWarehouseFlowTypePriority"
        ]));
        $config = [
            "DE",
            "FR",
            "ES",
            "IT",
            "NL",
            "BE",
            "PL",
            "SE"
        ];
        if ($info){
            $list = [];

            $ssss = $config;
            for ($i = 0; $i < count($config); $i++) {
                $platform = $config[$i];
                $list[$platform] = [];
                // 检查 $platform 是否在 $config 中
                // 创建一个新的数组
                $newConfig = $config; // 复制原始数组
                // 检查 $platform 是否在 $newConfig 中
                if (in_array($platform, $newConfig)) {
                    // 移除元素并将其放在数组首位
                    $newConfig = array_diff($newConfig, [$platform]); // 移除 $platform
                    array_unshift($newConfig, $platform); // 将 $platform 添加到首位
                }
                $list[$platform] = $newConfig;
            }
            $list["defult"] = $config;

            $info['optionVal'] = $list;
            $ssss[] = 'defult';
            $optionValCn = $ssss;
            $info['optionValCn'] = $optionValCn;
            $info['modifiedBy'] = "system(zhouangang)";
            $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);
        }
    }



    public function findPrePurchaseBillWithSkuForSkuMaterialInfo()
    {
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');

//        $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/scms/pre_purchase/info/v1/findPrePurchaseBillWithSkuForWaitHandleSkuMaterial", [
//            "pageSize" => 100,
//            "pageNum" => 1
//        ]));
//
//        if ($resp){
//            $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
//        }

//        $resp = DataUtils::getNewResultData($curlService->getWayGet($curlService->module . "/sms/sku/info/material/v1/createCeSkuMaterial", [
//            "operatorName" => "zhouangang"
//        ]));
//
//        if ($resp){
//
//        }
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/补充修复630数据.xlsx");
        if (sizeof($fileFitContent) > 0) {

            foreach ($fileFitContent as $info){
                $pmoArr = [
                    "qdBillNo" => $info['qdBillNo'],
                    "operatorName" => "zhouangang",
                    "purchaseHandleStatus" => 20,
                    "supplierId" => $info['supplierId']
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $pmoArr));
                if ($resp){

                }
            }

        }
    }


    public function ceMaterialObjectLog()
    {
        $curlService = (new CurlService())->pro();
        $curlLogService = (new CurlService())->pro();
        $curlLogService->gateway();
        $curlLogService->getModule('ux168log');

        $list = [];

        $page = 1;
        do {
            $this->log($page);
            $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 1000,
                "page" => $page,
                "orderBy" => "-_id"
            ]));
            if (count($l) == 0) {
                break;
            }
            foreach ($l as $info) {
                if (preg_match('/^(QD|DPMO)/', $info['batchName'])){
                    $logListResp = DataUtils::getNewResultData($curlLogService->getWayPost($curlLogService->module . "/log/v1/query",[
                        "page" => [
                            "pageNum" => 1,
                            "pageSize" => 1000,
                        ],
                        "condition" => [
                            "opId" => $info['_id'],
                            "logSource" => "pa-sku-material",
                            "logType" => "pa-sku-material"
                        ]
                    ]));
                    if ($logListResp && isset($logListResp['list']) && $logListResp['list'] && count($logListResp['list']) > 0){
                        foreach ($logListResp['list'] as $logInfo){
                            $list[] = [
                                "ceBillNo" => $info['ceBillNo'],
                                "opType" => $logInfo['opType'],
                                "opBeforeContent" => $logInfo['opBeforeContent'],
                                "opAfterContent" => $logInfo['opAfterContent'],
                                "opRemark" => $logInfo['opRemark'],
                            ];
                        }
                        $this->log("有日志：{$info['_id']}");
                    }else{
                        $this->log("没有日志：{$info['_id']}");
                    }

                }else{
                    $this->log("结束了");
                    break 2;
                }
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            $downloadOssLink = "资呈_" . date("YmdHis") . ".xlsx";
            $downloadOssPath = $excelUtils->downloadXlsx(["ceBillNo", "opType", "修改前","修改后","备注"],$list,$downloadOssLink);
            $this->log("导出内容");
        }

    }


    public function fixCeMaterial()
    {
        $curlService = (new CurlService())->pro();


        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/资呈_20250717203220.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            $list = [];
            foreach ($fileFitContent as $info){
                if ($info['修改前'] == '/' && $info['修改后'] == '/'){
                    $this->log("没有任何修改");
                    continue;
                }

                $ceBillNo = $info['ceBillNo'];
                $this->log("{$info['备注']}");

                // 提取 "操作类型"
                $colonPos = strpos($info['备注'], '：');
                if ($colonPos !== false) {
                    $action = substr( $info['备注'], 0, $colonPos);
                    $remaining = substr( $info['备注'], $colonPos + 3); // 跳过 "：a"
                } else {
                    // 处理 "保存a25062700ux2136" 这种情况
                    $action = substr( $info['备注'], 0, 6); // 取前6个字符（"保存"）
                    $remaining = substr( $info['备注'], 6); // 剩下的部分
                }

                // 提取 "a编号" 和 "后缀"
                $hyphenPos = strpos($remaining, '-');
                if ($hyphenPos !== false) {
                    $aNumber = substr($remaining, 0, $hyphenPos);
                    $suffix = substr($remaining, $hyphenPos + 1);
                } else {
                    $aNumber = $remaining;
                    $suffix = '';
                }

                $this->log("{$action}");
                $this->log("{$aNumber}");
                $this->log("{$suffix}");
                if ($action == '保存'){
                    $this->log("保存，不读");
                    continue;
                }
                if (!isset($list[$aNumber])){
                    $list[$aNumber] = [
                        "ceBillNo" => $ceBillNo
                    ];
                }
                if ($action == '导入'){
                    $this->log("导入");
                    if ($suffix == '车型'){
                        $list[$aNumber]["fitment"] = $info['修改后'];
                    }else if ($suffix == '核心词'){
                        $list[$aNumber]["keywords"] = $info['修改后'];
                    }else if ($suffix == 'cpAsin'){
                        $list[$aNumber]["cpAsin"] = $info['修改后'];
                    }
                }
                if ($action == '更新'){
                    $this->log("更新");
                    if ($suffix == '车型'){
                        $list[$aNumber]["fitment"] = $info['修改后'];
                    }else if ($suffix == '核心词'){
                        $list[$aNumber]["keywords"] = $info['修改后'];
                    }else if ($suffix == 'cpAsin'){
                        $list[$aNumber]["cpAsin"] = $info['修改后'];
                    }
                }


            }


            $exportList = [];
            foreach ($list as $skuId => $item){
                $exportList[] = [
                    "skuId" => $skuId,
                    "ceBillNo" => $item['ceBillNo'] ?? "",
                    "fitment" => $item['fitment'] ?? '[]',
                    "keywords" => $item['keywords'] ?? '[]',
                    "cpAsin" => $item['cpAsin'] ?? '[]',
                ];
            }

            if (count($exportList) > 0){

                $excelUtils = new ExcelUtils();
                $downloadOssLink = "导出资呈数据_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["skuId", "ceBillNo", "fitment","keywords","cpAsin"],$exportList,$downloadOssLink);
                $this->log("导出内容");


            }

        }

    }

    public function fixCeMaterialT()
    {
        $curlService = (new CurlService())->pro();

        foreach ([
            "CE202507180079"
                 ] as $ceBillNo) {

            $resp = DataUtils::getPageDocList(
                $curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "ceBillNo" => $ceBillNo,
                ])
            );
            if ($resp) {
                $info = $resp[0];

            } else {
                $this->log("找不到数据：{$ceBillNo}");
            }
        }

    }


    public function fixCeMaterialS()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/导出资呈数据_20250718113804.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceBillNoMap = [];
            foreach ($fileFitContent as $info){
                $ceBillNoMap[$info['ceBillNo']][] = [
                    "skuId" => $info['skuId'],
                    "fitment" => json_decode($info['fitment'],true),
                    "keywords" => json_decode($info['keywords'],true),
                    "cpAsin" => json_decode($info['cpAsin'],true),
                ];
            }

            if (count($ceBillNoMap) > 0){

                foreach ($ceBillNoMap as $ceBillNo => $list){

                    $resp = DataUtils::getPageDocList(
                        $curlService->s3044()->get("pa_ce_materials/queryPage", [
                            "ceBillNo" => $ceBillNo,
                        ])
                    );
                    if ($resp){
                        $info = $resp[0];
                        if ($info['status'] == "materialComplete"){
                            //资料发布的需要修复数据
                            if (count($list) > 0){
                                $this->log("资料发布了需要修复：{$ceBillNo}");
                                foreach ($list as $dataInfo){
                                    $respD = DataUtils::getPageDocList(
                                        $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                            "ceBillNo" => $ceBillNo,
                                            "skuId" => $dataInfo['skuId'],
                                            "limit" => 1
                                        ])
                                    );
                                    if ($respD){
                                        $detailInfo = $respD[0];
                                        $detailInfo['fitment'] = $dataInfo['fitment'];
                                        $detailInfo['keywords'] = $dataInfo['keywords'];
                                        $detailInfo['cpAsin'] = $dataInfo['cpAsin'];
                                        $detailInfo['modifiedBy'] = "system(fix-angang)";


                                        $this->log(json_encode($detailInfo,JSON_UNESCAPED_UNICODE));


                                        $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                        if ($ss){
                                            $this->log("更新完毕");
                                        }
                                    }
                                }

                            }

                        }else{
                            $this->log("资料未发布，可以不用修复：{$ceBillNo}");
                        }
                    }else{
                        $this->log("找不到数据：{$ceBillNo}");
                    }

                }


            }

        }

    }

    public function bindSgu()
    {
        $curlService = (new CurlService())->pro();
//        $curlService->gateway();
//        $curlService->getModule('pa');

        $list = [
            "a25081500ux1375",
            "a25081500ux1376"
        ];
        $curlSsl = (new CurlService())->pro();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
            "skuIdList" => $list,
            "attrCodeList" => [
                "custom-skuInfo-skuId",
                "custom-sguInfo-sguId"
            ]
        ]));
        $map  =[];
        if ($getKeyResp){
            foreach ($getKeyResp as $item){
                $map[$item['custom-skuInfo-skuId']] = $item['custom-sguInfo-sguId'];
            }

        }
        $res = DataUtils::getResultData($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
            "skuScuId_in" => implode(",",$list),
            "limit" => 200,
        ]));

        foreach ($res as $info) {


            if (isset($map[$info['skuScuId']])){

                if ($map[$info['skuScuId']] != $info['sguId']){
                    $info['sguId'] = $map[$info['skuScuId']];
                    $info['modifiedBy'] = "system(zhouangang)";
                    $curlService->s3015()->put("sgu-sku-scu-maps/{$info['_id']}", $info);
                }else{
                    $this->log("{$info['skuScuId']} sgu一样无需修复：{$map[$info['skuScuId']]} {$info['sguId']}");
                }

            }else{
                $this->log("找不到");
            }

        }
//        $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/initSkuInfo", [
//            "initSkuId" => "a25062500ux0135",
//            "operatorName" => "luowei3",
//            "productType" => "SGU",
//            "sguId" => "g25072200ux0040"
//        ]));


    }


    public function exportCEEEEEEEEEEEEEE()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/导出资呈数据_20250718113804.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceBillNoMap = [];
            foreach ($fileFitContent as $info){
                $ceBillNoMap[$info['ceBillNo']][] = [
                    "skuId" => $info['skuId'],
                    "fitment" => json_decode($info['fitment'],true),
                    "keywords" => json_decode($info['keywords'],true),
                    "cpAsin" => json_decode($info['cpAsin'],true),
                ];
            }

            if (count($ceBillNoMap) > 0){

                foreach ($ceBillNoMap as $ceBillNo => $list){

                }

            }

        }

    }




    public function fixPaSkuMaterialList()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/zicheng/资呈广告异常修复(2025.08.07)-运营确认.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceSkuMap = [];
            foreach ($fileFitContent as $sheet => $sheetList) {
                if ($sheet === '核心词'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
                        if (!empty($info['核心词'])){
                            $uniqueKey = "{$info['skuId']}{$info['核心词']}";
                            if (!isset($uniqueKeyMap[$uniqueKey])) {

                                $ceSkuMap[$info['CE#']][$info['skuId']]['keywords'][] = $info['核心词'];

                                $uniqueKeyMap[$uniqueKey] = 1;
                            }
                        }
                    }
                }else if ($sheet === '热销车型'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
                        $uniqueKey = "{$info['skuId']}{$info['make']}{$info['model']}";
                        if (!isset($uniqueKeyMap[$uniqueKey])){
                            $ceSkuMap[$info['CE#']][$info['skuId']]['fitment'][] = [
                                "make" => $info['make'],
                                "model" => $info['model'],
                            ];
                            $uniqueKeyMap[$uniqueKey] = 1;
                        }
                    }
                }else if ($sheet === 'CP asin'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
                        if (!empty($info['asin'])) {
                            $uniqueKey = "{$info['skuId']}{$info['asin']}";

                            if (!isset($uniqueKeyMap[$uniqueKey])) {
                                $ceSkuMap[$info['CE#']][$info['skuId']]['cpAsin'][] = $info['asin'];

                                $uniqueKeyMap[$uniqueKey] = 1;
                            }
                        }

                    }
                }
            }


            if ($ceSkuMap){
                $skuNumber = 0;
                $ceNumber = 0;
                foreach ($ceSkuMap as $ceBillNo => $tree1){
                    $ceNumber++;
                    foreach ($tree1 as $skuId => $tree2){
                        $skuNumber++;
                        $skuMaterialInfo = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_sku_materials/queryPage", [
                            "limit" => 1,
                            "ceBillNo" => $ceBillNo,
                            "skuId" => $skuId,
                            "page" => 1
                        ]));
                        if ($skuMaterialInfo){
                            //存在，不管之前的值是怎样的，直接覆盖
                            $skuMaterialInfo['keywords'] = $tree2['keywords']??[];
                            $skuMaterialInfo['cpAsin'] = $tree2['cpAsin']??[];
                            $skuMaterialInfo['fitment'] = $tree2['fitment']??[];
                            $skuMaterialInfo['modifiedBy'] = "system(zhouangang88)";
                            $this->log("更新sku{$ceBillNo}-{$skuId}" . json_encode($skuMaterialInfo,JSON_UNESCAPED_UNICODE));
                            $curlService->s3044()->put("pa_sku_materials/{$skuMaterialInfo['_id']}", $skuMaterialInfo);
                        }else{
                            //不存在，就要创建,先查ce资呈，看看里面的sku，看看里面的sku有没有父类
                            $this->log("{$ceBillNo}{$skuId}不存在sku资呈,等待创建");
                            $skuMaterialList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                "limit" => 1000,
                                "ceBillNo" => $ceBillNo,
                                "page" => 1,
                                "orderBy" => "-_id"
                            ]));
                            if ($skuMaterialList){
                                foreach ($skuMaterialList as $item){
                                    if (empty($item['parentSkuId'])){
                                        $syncSkuInfo = [];
                                        //是父类
                                        $syncSkuInfo = $item;
                                        $syncSkuInfo['parentSkuId'] = $item['skuId'];
                                        $syncSkuInfo['skuId'] = $skuId;
                                        $syncSkuInfo['ceBillNo'] = $ceBillNo;
                                        $syncSkuInfo['createdBy'] = "system(zhouangang)";
                                        $syncSkuInfo['keywords'] = $tree2['keywords']??[];
                                        $syncSkuInfo['cpAsin'] = $tree2['cpAsin']??[];
                                        $syncSkuInfo['fitment'] = $tree2['fitment']??[];
                                        unset($syncSkuInfo['_id']);
                                        unset($syncSkuInfo['__v']);
                                        $this->log("同步父sku到子sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo,JSON_UNESCAPED_UNICODE));
                                        $curlService->s3044()->post("pa_sku_materials", $syncSkuInfo);
                                        break;
                                    }
                                }
                            }else{
                                //ce单一个sku都没有的，直接创建吧
                                $syncSkuInfo = [
                                    "skuId" => $skuId,
                                    "ceBillNo" => $ceBillNo,
                                    "createdBy" => "system(zhouangang)",
                                    "keywords" => $tree2['keywords']??[],
                                    "fitment" => $tree2['fitment']??[],
                                    "cpAsin" => $tree2['cpAsin']??[],
                                    "basicInfo" => "",
                                    "categoryRelation" => "",
                                    "description" => "",
                                    "operationMonitor" => "",
                                    "status" => "developerComplete",
                                    "version" => null,
                                    "parentSkuId" => "",
                                    "saleBasicInfo" => "",
                                    "developerFile" => "",
                                    "saleFile" => "",
                                    "developerStartOn" => date("Y-m-d H:i:s",time()) . "Z",
                                    "developerStartBy" => "system(zhouangang)",
                                    "saleStartOn" => null,
                                    "saleStartBy" => "",
                                    "oeNumber" => [],
                                ];
                                $this->log("初始化创建sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo,JSON_UNESCAPED_UNICODE));
                                $curlService->s3044()->post("pa_sku_materials", $syncSkuInfo);

                            }


                        }

                    }
                }

                $this->log("ce单数量：{$ceNumber}");
                $this->log("sku数量：{$skuNumber}");



            }

        }



    }



    public function getRepeatSkuMaterial()
    {
        $curlService = (new CurlService())->pro();
        $ceBillNoList = [];
        $page = 1;
        do {
            $this->log($page);
            $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 1000,
                "page" => $page,
                "status" => "materialComplete",
                "modifiedOn_lte" => "2025-07-18",
                "orderBy" => "-_id"
            ]));
            if (count($l) == 0) {
                break;
            }
            foreach ($l as $info) {
                if (preg_match('/^(QD|DPMO)/', $info['batchName']) && preg_match('/^(CE)/',$info['ceBillNo'])){
                    $ceBillNoList[] = $info['ceBillNo'];
                }else{
                    $this->log("结束了");
                    break 2;
                }
            }
            $page++;
        } while (true);

        $exportList = [];
        if (count($ceBillNoList) > 0){
            $this->log(count($ceBillNoList)."个CE单");
            $cccTime = "2025-07-18T00:00:00.000Z";

            foreach (array_chunk($ceBillNoList,10) as $chunk){
                $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "limit" => 10000,
                    "ceBillNo_in" => implode(",",$chunk),
                    "status" => "materialComplete",
                    "orderBy" => "-_id"
                ]));
                if (count($ll) == 0) {
                    continue;
                }

                foreach ($ll as $info) {
                    $cc = new DateTime($cccTime);
                    $ss = new DateTime($info['publishOn']);
                    $this->log("{$info['ceBillNo']}-{$info['skuId']}" . $info['publishOn']);
                    if (!empty($info['publishOn']) && !empty($info['publishBy']) && ($ss < $cc)){

                        if (!empty($info['keywords']) || !empty($info['cpAsin']) || !empty($info['fitment'])){
                            $key = $info['ceBillNo'] . $info['skuId'];
                            $data = [
                                "ceBillNo" => $info['ceBillNo'],
                                "skuId" => $info['skuId'],
//                                "keywords" => $info['keywords'],
//                                "cpAsin" => $info['cpAsin'],
//                                "fitment" => $info['fitment'],
                                "publishOn" => $info['publishOn'],
                            ];
                            $this->redis->hSet(REDIS_MATERIAL_REPT_KEY, $key,json_encode($data,JSON_UNESCAPED_UNICODE));

                            $dataJ = [
                                "ceBillNo" => $info['ceBillNo'],
                                "skuId" => $info['skuId'],
//                                "keywords" => json_encode($info['keywords'],JSON_UNESCAPED_UNICODE),
//                                "cpAsin" => json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE),
//                                "fitment" => json_encode($info['fitment'],JSON_UNESCAPED_UNICODE),
                                "publishOn" => $info['publishOn'],
                            ];
                            $exportList[] = $dataJ;
                        }

                    }
                }

            }

        }

//        $list = $this->redis->hGetAll(REDIS_MATERIAL_REPT_KEY);
//
//        $ceBillNoMap = [];
//        foreach ($list as $key => $value){
//            $data = json_decode($value,true);
//            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['keywords'] = $data['keywords'];
//            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['cpAsin'] = $data['cpAsin'];
//            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['fitment'] = $data['fitment'];
//            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['publishOn'] = $data['publishOn'];
//        }
//
//        if ($ceBillNoMap){
//            $exportList = [];
//            foreach ($ceBillNoMap as $ceBillNo => $skuMap){
//                $skuNumber = count($skuMap);
//                $sameKeywords = false;
//                $sameCpAsin = false;
//                $sameFitments = false;
//                $firstKeywords = "";
//                $firstCpAsins = "";
//                $firstFitments = "";
//
//                $index = 0;
//                $sameKeywordsNumber = 0;
//                $sameCpAsinNumber = 0;
//                $sameFitmentsNumber = 0;
//
//                $publishOn = "";
//                foreach ($skuMap as $skuId => $info){
//
//                    if ($index == 0){
//                        $publishOn = $info['publishOn'];
//                        $firstKeywords = json_encode($info['keywords'],JSON_UNESCAPED_UNICODE);
//                        $firstCpAsins = json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE);
//                        $firstFitments = json_encode($info['fitment'],JSON_UNESCAPED_UNICODE);
//                        $index++;
//                        continue;
//                    }
//
//                    if ($firstKeywords == json_encode($info['keywords'],JSON_UNESCAPED_UNICODE)){
//                        $sameKeywordsNumber++;
//                    }else{
//
//                    }
//                    if ($firstCpAsins == json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE)){
//                        $sameCpAsinNumber++;
//                    }else{
//
//                    }
//                    if ($firstFitments == json_encode($info['fitment'],JSON_UNESCAPED_UNICODE)){
//                        $sameFitmentsNumber++;
//                    }else{
//
//                    }
//
//                    $index++;
//                }
//                if ($skuNumber > 1){
//                    if ($sameKeywordsNumber == ($skuNumber - 1)){
//                        $sameKeywords = true;
//                    }
//                    if ($sameCpAsinNumber == ($skuNumber - 1)){
//                        $sameCpAsin = true;
//                    }
//                    if ($sameFitmentsNumber == ($skuNumber - 1)){
//                        $sameFitments = true;
//                    }
//                }else{
//                    $sameKeywords = true;
//                    $sameCpAsin = true;
//                    $sameFitments = true;
//                }
//
//                $exportList[] = [
//                    "ceBillNo" => $ceBillNo,
//                    "publishOn"=> $publishOn,
//                    "skuNumber" => $skuNumber,
//                    "sameKeywords" => $sameKeywords == true ? "全部一致" : "不一致",
//                    "sameCpAsin" => $sameCpAsin == true ? "全部一致" : "不一致",
//                    "sameFitments" => $sameFitments == true ? "全部一致" : "不一致",
//                ];
//
//
//            }
//        }

        if (count($exportList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","发布日期"],$exportList,"sku资料呈现资料发布后广告信息内容_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }



    }


    public function getRepeatSkuMaterialByAliSls()
    {
        $curlService = (new CurlService())->pro();

        //$list = $this->redis->hGetAll(REDIS_MATERIAL_REPT_KEY);


        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布后广告信息内容_20250731100344.xlsx");
//        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布后广告信息内容_test.xlsx");

        $ceBillNoMap = [];
        foreach ($fileContent as $info){
            $ceBillNoMap[$info['ceBillNo']][] = [
                "skuId" => $info['skuId'],
                "publishOn" => date("Y-m-d",strtotime($info['发布日期']))
            ];
        }


        $exportList = [];
        foreach ($ceBillNoMap as $ceBillNo => $skuMap) {
            $skuNumber = count($skuMap);

            foreach ($skuMap as $skuId) {

                $jixianTime = "2025-04-12T08:00:00.000Z";
                if (strtotime($skuId['publishOn']) <= strtotime($jixianTime)){
                    $this->log("{$skuId['skuId']} {$skuId['publishOn']} 超过4个月，无法获取阿里云数据");
                    continue;
                }
                $query = "{$ceBillNo} and pa_sku_materials and RequestMethod: PUT and {$skuId['skuId']} not fix-angang";
                $this->log("{$query}");
                $res = (new RequestUtils("test"))->callAliCloudSls($query);

                if ($res && $res['code'] == "200" && $res['data'] && $res['data']['logs'] && count($res['data']['logs']) > 0){
                    //按时间倒序
                    usort($res['data']['logs'], function($a, $b) {
                        return $b['__time__'] <=> $a['__time__'];
                    });
                    $nextLog = $this->findNoMaterialStatusDate($res['data']['logs'],0);
                    if ($nextLog){
                        $key = $nextLog['ceBillNo'] . $nextLog['skuId'];
                        $data1 = [
                            "ceBillNo" => $nextLog['ceBillNo'],
                            "skuId" => $nextLog['skuId'],
                            "keywords" => $nextLog['keywords'],
                            "cpAsin" => $nextLog['cpAsin'],
                            "fitment" => $nextLog['fitment']
                        ];
                        $this->redis->hSet(REDIS_MATERIAL_REPT_CORRET_KEY, $key,json_encode($data1,JSON_UNESCAPED_UNICODE));

                        $dataJ1 = [
                            "ceBillNo" => $nextLog['ceBillNo'],
                            "skuId" => $nextLog['skuId'],
                            "keywords" => json_encode($nextLog['keywords'],JSON_UNESCAPED_UNICODE),
                            "cpAsin" => json_encode($nextLog['cpAsin'],JSON_UNESCAPED_UNICODE),
                            "fitment" => json_encode($nextLog['fitment'],JSON_UNESCAPED_UNICODE),
                        ];
                        $exportList[] = $dataJ1;
                        $this->log("有日志：" .json_encode($dataJ1));
                    }


                }else{
                    $this->log("没有log日志");
                }


            }

        }

        if (count($exportList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","核心词","asin","车型"],$exportList,"sku资料呈现资料发布前广告信息内容_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }



    }


    public function findNoMaterialStatusDate($logsList,$index = 0){
        if (!isset($logsList[$index])){
            $this->log("没有日志");
            return [];
        }

        $nowLog = json_decode($logsList[$index]['FormString'], true);
        if (isset($nowLog['publishOn']) && $nowLog['publishOn'] && isset($nowLog['status']) && $nowLog['status'] == 'materialComplete'){
            $this->log("是发布完成的日志更新，跳过");
            $index++;
            return $this->findNoMaterialStatusDate($logsList,$index);
        }

        return $nowLog;
    }



    public function exportBeforeSkuMaterial()
    {
        $curlService = (new CurlService())->pro();


        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布前广告信息内容_20250731110340.xlsx");

        $exportListKeywords = [];
        $exportListAsins = [];
        $exportListFitments = [];

        foreach ($fileContent as $info){


            if ($info['核心词']){
                $keywords = json_decode($info['核心词'],true);
                foreach ($keywords as $keyword){

                    $exportListKeywords[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "keyword" => $keyword,
                    ];

                }
            }

            if ($info['asin']){
                $asins = json_decode($info['asin'],true);
                foreach ($asins as $asin){
                    $exportListAsins[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "asin" => $asin,
                    ];
                }
            }

            if ($info['车型']){
                $fitments = json_decode($info['车型'],true);
                foreach ($fitments as $fitment){
                    $exportListFitments[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "make" => $fitment['make'],
                        "model" => $fitment['model'],
                    ];
                }
            }

        }




        if (count($exportListKeywords) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","核心词"],$exportListKeywords,"sku资料呈现资料发布前广告信息核心词_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }
        if (count($exportListAsins) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","asin"],$exportListAsins,"sku资料呈现资料发布前广告信息CP_Asin_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }
        if (count($exportListFitments) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","make","model"],$exportListFitments,"sku资料呈现资料发布前广告信息车型_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }



    }




    public function fixCeMaterialSSSS()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/20250731修复资呈数据.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceBillNoSkuIdMap = [];

            if (isset($fileFitContent['核心词'])){
                foreach ($fileFitContent['核心词'] as $info){
                    $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["keywords"][] = $info['核心词'];
                }
            }

            if (isset($fileFitContent['asin'])){
                foreach ($fileFitContent['asin'] as $info){
                    $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["cpAsin"][] = $info['asin'];
                }
            }

            if (isset($fileFitContent['车型'])){
                $uniqFitmentMap = [];
                foreach ($fileFitContent['车型'] as $info){

                    $uniqFitment = md5($info['ceBillNo'] . $info['skuId'] . $info['make'] . $info['model']);
                    if (!isset($uniqFitmentMap[$uniqFitment])){
                        $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["fitment"][] = [
                            "make" => $info['make'],
                            "model" => $info['model']
                        ];

                        $uniqFitmentMap[$uniqFitment] = 1;
                    }

                }
            }

            if (count($ceBillNoSkuIdMap) > 0){

                foreach ($ceBillNoSkuIdMap as $ceBillNo => $list){

                    //资料发布的需要修复数据
                    if (count($list) > 0){
                        $this->log("资料发布了需要修复：{$ceBillNo}");

                        foreach ($list as $skuId => $dataInfo){
                            $detailInfo = DataUtils::getPageDocListInFirstDataV1(
                                $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                    "ceBillNo" => $ceBillNo,
                                    "skuId" => $skuId,
                                    "limit" => 1
                                ])
                            );
                            if ($detailInfo){
                                $detailInfo['fitment'] = $dataInfo['fitment'] ?? [];
                                $detailInfo['keywords'] = array_unique($dataInfo['keywords'] ?? []);
                                $detailInfo['cpAsin'] = array_unique($dataInfo['cpAsin'] ?? []);
                                $detailInfo['modifiedBy'] = "system(sp-fix-angang)";


                                $this->log(json_encode($detailInfo,JSON_UNESCAPED_UNICODE));


                                $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                if ($ss){
                                    $this->log("更新完毕");
                                }
                            }
                        }

                    }


                }


            }

        }

    }


    public function mergeSkuMaterialXlsx()
    {
        $curlService = (new CurlService())->pro();

        $keywords = [];
        $cpasin = [];
        $fitments = [];
        for($page = 7;$page <= 7;$page++){
            $fileFitContent = (new ExcelUtils())->getXlsxDataV2("../export/skuMaterial/zicheng/{$page}.xlsx");
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $sheet => $sheetList) {
                    if ($sheet === '核心词'){
                        $keywords = array_merge($keywords,$sheetList);
                    }else if ($sheet === '热销车型'){
                        $fitments = array_merge($fitments,$sheetList);
                    }else if ($sheet === 'CP asin'){
                        $cpasin = array_merge($cpasin,$sheetList);
                    }
                }
            }
        }

        if (count($keywords) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","核心词"],$keywords,"sku资料呈现核心词_".date("YmdHis").".xlsx");
        }
        if (count($cpasin) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","asin"],$cpasin,"sku资料呈现CP_Asin_".date("YmdHis").".xlsx");
        }
        if (count($fitments) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","make","model"],$fitments,"sku资料呈现热销车型_".date("YmdHis").".xlsx");
        }

    }

    public function fixRepeatSkuMaterial()
    {
        $ceBillNo = "CE202508040023";

        $curlService = (new CurlService())->pro();
        $list = DataUtils::getPageDocList(
            $curlService->s3044()->get("pa_sku_materials/queryPage", [
                "ceBillNo" => $ceBillNo,
                "limit" => 1000
            ])
        );
        if ($list){
            $sameSkuIdMap = [];
           foreach ($list as &$item){
               if (!isset($sameSkuIdMap[$item['skuId']])){
                   if ($item['keywords']){
                       $item['keywords'] = array_unique($item['keywords']);
                   }
                   if ($item['cpAsin']){
                       $item['cpAsin'] = array_unique($item['cpAsin']);
                   }
                   if ($item['fitment']){
                       $quchongfitment = [];
                       $uniqFitmentMap = [];
                       foreach ($item['fitment'] as $info){
                           $uniqFitment = md5($info['make'] . $info['model']);
                           if (!isset($uniqFitmentMap[$uniqFitment])){
                               $quchongfitment[] = [
                                   "make" => $info['make'],
                                   "model" => $info['model']
                               ];
                               $uniqFitmentMap[$uniqFitment] = 1;
                           }
                       }
                       if ($quchongfitment){
                           $item['fitment'] = $quchongfitment;
                       }
                   }
                   $sameSkuIdMap[$item['skuId']] = 1;
                   $this->log(json_encode($item,JSON_UNESCAPED_UNICODE));

                   $ss = $curlService->s3044()->put("pa_sku_materials/{$item['_id']}", $item);
                   if ($ss){
                       $this->log("更新完毕");
                   }
               }else{
                   $this->log("{$item['skuId']} 重复了,删掉一个");
                   $curlService->s3044()->delete("pa_sku_materials/{$item['_id']}");
               }

           }



        }


    }



    public function fixMergeADSguId()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/g号修复_1.xlsx");
        if (sizeof($fileFitContent) > 0) {


            $list = [];
            foreach ($fileFitContent as $item){
                if (!empty($item['sku_id'])){
                    $list[] = $item['sku_id'];
                }
            }
            $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $list,
                "attrCodeList" => [
                    "custom-skuInfo-skuId",
                    "custom-common-batchNo",
                    "custom-sguInfo-sguId",
                    "custom-sguInfo-groupTag",
                    "custom-sguInfo-channel",
                    "custom-skuInfo-tempSkuId"
                ]
            ]));
            $map  =[];
            if ($getKeyResp){
                foreach ($getKeyResp as $item){
                    $map[$item['custom-skuInfo-skuId']] = $item;
                }
            }

//            $ss = [];
//            foreach ($fileFitContent as $info){
//                if (isset($map[$info['sku_id']])){
//                    $skuAttrData = $map[$info['sku_id']];
//                    if (!isset($ss[$skuAttrData['custom-sguInfo-sguId']])){
//                        if (!empty($skuAttrData['custom-sguInfo-sguId'])){
//                            $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
//                                "limit" => 1,
//                                "productId" => $skuAttrData['custom-sguInfo-sguId']
//                            ]));
//                            if (!$sssinof){
//                                $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
//                                $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
//                                    "initSkuId" => $info['sku_id'],
//                                    "operatorName" => "system(修复sgu初始化)",
//                                    "productType" => "SGU",
//                                    "sguId" => $skuAttrData['custom-sguInfo-sguId']
//                                ]));
//                                $ss[$skuAttrData['custom-sguInfo-sguId']] = 1;
//
//                            }else{
//                                $ss[$skuAttrData['custom-sguInfo-sguId']] = 1;
//                            }
//                        }
//
//
//                    }
//                }
//            }

            $qdScmsPrePurchaseMap = [];
            $fix30List = [];
            $hasSguInit = [];
            foreach ($fileFitContent as $info){
                if (empty($info['sku_id'])){
                    $this->log("{$info['temp_sku_id']}没有sku_id");
                    continue;
                }
                if (isset($map[$info['sku_id']])){
                    $skuAttrData = $map[$info['sku_id']];

                    if (!empty($skuAttrData['custom-sguInfo-groupTag'])){


                        $sguKey = "sgu_init_{$info['batch_no']}_{$skuAttrData['custom-sguInfo-groupTag']}";
                        $this->log("{$sguKey}");
                        $sguId = $this->redis->hGet("sgu_fix_redis", $sguKey);
                        if (!$sguId){
                            //当前key重新创建sgu
                            $sguId = $this->createSguInfo();

                            $this->log("{$info['sku_id']} 绑定 {$sguId}");

                            $this->redis->hSet("sgu_fix_redis", $sguKey, $sguId);
                            //$qdScmsPrePurchaseMap[$sguKey] = $sguId;
                        }

                        if (!empty($sguId)){

                            $this->log("{$sguKey} 生成 {$sguId}");

                            $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
                                "skuScuId" => $info['sku_id'],
                                "limit" => 1,
                            ]));
                            if ($sguInfo){

                                $sguInfo['sguId'] = $sguId;
                                $sguInfo['modifiedBy'] = "system(zhouangang)";
                                $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);

                            }else{
                                //创建

                                $channelList = explode(",",$skuAttrData['custom-sguInfo-channel']);
                                $channelListData = [];
                                foreach ($channelList as $ch){
                                    $channelListData[] = [
                                        "groupName" => "",
                                        "groupAttrName" => [],
                                        "groupAttrValue" => [],
                                        "channel" => $ch,
                                        "modifiedBy" => "system(zhouangang)",
                                        "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
                                    ];
                                }
                                $create = [
                                    "createdBy" => "system(zhouangang)",
                                    "modifiedBy" => "system(zhouangang)",
                                    "skuScuId" => $info['sku_id'],
                                    "sguId" => $sguId,
                                    "remark" => "sgu自动绑定和初始化",
                                    "channel" => $channelListData,
                                    "createdOn" => date("Y-m-d H:i:s",time ())."Z",
                                    "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
                                ];
                                $curlService->s3015()->post("/sgu-sku-scu-maps",$create);

                            }

                            $fix30List[] = [
                                "tempSkuId" => $skuAttrData['custom-skuInfo-tempSkuId'],
                                "skuAttrList" => [
                                    [
                                        "name" => "custom-sguInfo-sguId",
                                        "value" => $sguId
                                    ]
                                ]
                            ];

                            if(!isset($hasSguInit[$sguId])){
                                $this->log("{$sguId} 开始初始化");
                                $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                                    "limit" => 1,
                                    "productId" => $sguId
                                ]));
                                if (!$sssinof){
                                    $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                                    $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
                                        "initSkuId" => $info['sku_id'],
                                        "operatorName" => "system(修复sgu初始化)",
                                        "productType" => "SGU",
                                        "sguId" => $sguId
                                    ]));
                                    $hasSguInit[$sguId] = 1;
                                }else{
                                    $hasSguInit[$sguId] = 1;
                                }
                                $this->log("{$sguId} 结束初始化");
                            }



                        }else{
                            $this->log("{$sguKey} 生成g号失败");
                        }



                    }else{
                        $this->log("没有绑定g号，不用初始化");
                    }

                }




            }



            //回写3.0
            if ($fix30List){
                foreach (array_chunk($fix30List,200) as $chunkFix30List){
                    $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
                    $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
                        "operator" => "zhouangang",
                        "skuList" => $chunkFix30List
                    ]));
                }


            }


        }





    }


    public function fixMergeADV2SguId()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/g号修复_2.xlsx");
        if (sizeof($fileFitContent) > 0) {


            $listMap = [];
            foreach ($fileFitContent as $item){
                if (!empty($item['sku_id'])){
                    $listMap[$item['original_product_dev_main_id']][] = $item['sku_id'];
                }
            }
            foreach ($listMap as $mainid => $list){
                $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                    "skuIdList" => $list,
                    "attrCodeList" => [
                        "custom-skuInfo-skuId",
                        "custom-skuInfo-tempSkuId",
                        "custom-sguInfo-channel"
                    ]
                ]));
                $map  =[];
                if ($getKeyResp){
                    foreach ($getKeyResp as $item){
                        $map[$item['custom-skuInfo-skuId']] = $item;
                    }
                }

                foreach ($list as $sku){
                    if (isset($map[$sku])){
                        $skuAttrData = $map[$sku];


                            $sguKey = "sgu_init_{$mainid}";
                            $this->log("{$sguKey}");
                            $sguId = $this->redis->hGet("sgu_fix_redis", $sguKey);
                            if (!$sguId){
                                //当前key重新创建sgu
                                $sguId = $this->createSguInfo();

                                $this->log("{$sku} 绑定 {$sguId}");

                                $this->redis->hSet("sgu_fix_redis", $sguKey, $sguId);
                                //$qdScmsPrePurchaseMap[$sguKey] = $sguId;
                            }

                            if (!empty($sguId)){

                                $this->log("{$sguKey} 生成 {$sguId}");

                                $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
                                    "skuScuId" => $sku,
                                    "limit" => 1,
                                ]));
                                if ($sguInfo){

                                    $sguInfo['sguId'] = $sguId;
                                    $sguInfo['modifiedBy'] = "system(zhouangang)";
                                    $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);

                                }else{
                                    //创建

                                    $channelList = explode(",",$skuAttrData['custom-sguInfo-channel']);
                                    $channelListData = [];
                                    foreach ($channelList as $ch){
                                        $channelListData[] = [
                                            "groupName" => "",
                                            "groupAttrName" => [],
                                            "groupAttrValue" => [],
                                            "channel" => $ch,
                                            "modifiedBy" => "system(zhouangang)",
                                            "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
                                        ];
                                    }
                                    $create = [
                                        "createdBy" => "system(zhouangang)",
                                        "modifiedBy" => "system(zhouangang)",
                                        "skuScuId" => $sku,
                                        "sguId" => $sguId,
                                        "remark" => "sgu自动绑定和初始化",
                                        "channel" => $channelListData,
                                        "createdOn" => date("Y-m-d H:i:s",time ())."Z",
                                        "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
                                    ];
                                    $curlService->s3015()->post("/sgu-sku-scu-maps",$create);

                                }

                                $fix30List[] = [
                                    "tempSkuId" => $skuAttrData['custom-skuInfo-tempSkuId'],
                                    "skuAttrList" => [
                                        [
                                            "name" => "custom-sguInfo-sguId",
                                            "value" => $sguId
                                        ]
                                    ]
                                ];

                                if(!isset($hasSguInit[$sguId])){
                                    $this->log("{$sguId} 开始初始化");
                                    $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                                        "limit" => 1,
                                        "productId" => $sguId
                                    ]));
                                    if (!$sssinof){
                                        $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                                        $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
                                            "initSkuId" => $sku,
                                            "operatorName" => "system(修复sgu初始化)",
                                            "productType" => "SGU",
                                            "sguId" => $sguId
                                        ]));
                                        $hasSguInit[$sguId] = 1;
                                    }else{
                                        $hasSguInit[$sguId] = 1;
                                    }
                                    $this->log("{$sguId} 结束初始化");
                                }



                            }else{
                                $this->log("{$sguKey} 生成g号失败");
                            }


                    }
                }






            }






            //回写3.0
            if ($fix30List){
                foreach (array_chunk($fix30List,200) as $chunkFix30List){
                    $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
                    $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
                        "operator" => "zhouangang",
                        "skuList" => $chunkFix30List
                    ]));
                }


            }


        }

    }



    public function createSguInfo()
    {
        $curlService = (new CurlService())->pro();
        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/inventory/createSguInfo",['createdBy' => "system(zhouangang)"]));
        if ($res){
            return $res;
        }
        return "";
    }



    public function fixProductSku(){
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/需要删除来货的.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {

            $curlService = (new CurlService())->pro();

            $list = array_unique(array_column($fileFitContent,"skuId"));


            $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                "productId" => implode(",",$list),
                "limit" => 500
            ]));
            $map = [];
            if ($infoList){
                foreach ($infoList as $info){
                    $map[$info['productId']] = $info;
                }
            }

            foreach ($fileFitContent as $info){

                if (isset($map[$info['skuId']])){
                    $deleteAttributeArray = [];
                    $channel = $info['channel'];

                    foreach ($info as $key => $value){
                        //判断$key的开头是delete
                        if (strpos($key,"delete") === 0){
                            $deleteAttributeArray[] = [
                                "channel" => $channel,
                                "label" => $value,
                            ];
                        }
                    }
                    if (empty($deleteAttributeArray)){
                        $this->log("没有需要删除的attribute");
                        continue;
                    }
                    $productInfo = $map[$info['skuId']];
                    ProductUtils::deleteProductAttributeByArr($productInfo['attribute'], $deleteAttributeArray);

                    $productInfo['action'] = "system(删除错误的attribute)251212";
                    $productInfo['userName'] = "system(zhouangang)";

                    $this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                    if ($resp){

                    }
                }
            }
        }


    }


    public function fixTranslationManagementCategory()
    {
        $curlService = (new CurlService())->pro();

        $list = DataUtils::getPageList($curlService->s3015()->get("translation_management_categorys/queryPage",[
            "exampleCategory" => 1342943031,
            "exampleChannel" => "amazon_uk",
            "orderBy" => "-modifiedOn",
            "limit" => 500
        ]));

        $data = [
            "exampleChannel" => "amazon_uk",
            "exampleCategory" => "1342943031",
            "exampleCategoryName" => "Electronics & Photo > Car & Vehicle Electronics > Car Electronics > Reversing Cameras",
            "correspondData" => [],
            "createdBy" => "system(zhouangang)",
            "modifiedBy" => "system(zhouangang)",
            "createdOn" => date("Y-m-d H:i:s",time())."Z",
            "modifiedOn" => date("Y-m-d H:i:s",time())."Z"
        ];
        $correspondData = [];

        $sameChannelData = [];
        foreach ($list as $info){
            if ($info['correspondData']){
                foreach ($info['correspondData'] as $tree){
                    if (!isset($sameChannelData[$tree['channel']])){
                        $sameChannelData[$tree['channel']] = 1;
                        $correspondData[] = $tree;
                    }
                }
                $curlService->s3015()->delete("translation_management_categorys/{$info['_id']}");
            }
        }
        $data['correspondData'] = $correspondData;

        $this->log(json_encode($data,JSON_UNESCAPED_UNICODE));
        $curlService->s3015()->post("translation_management_categorys",$data);
    }


    public function fixDengyiyi(){
        $pmo = [
            "DPMO250626003",
            "DPMO250626004",
            "DPMO250627004",
            "DPMO250707006",
            "DPMO250707011",
            "DPMO250707012",
            "DPMO250707013",
            "DPMO250721007",
            "DPMO250729010",
            "DPMO250819009",
        ];
        $curlService = (new CurlService())->pro();
        $list = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage",[
            "batchName_in" => implode(",",$pmo),
            "limit" => 500
        ]));
        if ($list){
            foreach ($list as $info){
                if ($info['ebayTraceMan'] == 'dengyiyi2'){
                    $info['ebayTraceMan'] = 'dengyiyi';
                    foreach ($info['ebayTraceManList'] as &$item){
                        if ($item == 'dengyiyi2'){
                            $item = 'dengyiyi';
                        }
                    }
                    $this->log(json_encode($info,JSON_UNESCAPED_UNICODE));
                    $curlService->s3044()->put("pa_ce_materials/{$info['_id']}",$info);
                }
            }
        }
    }



    public function getssss()
    {
        $curlService = (new CurlService())->pro();
        $list = [
            "689ef98a7fbdf42093ce5d30",
            "689ef98a7363a82099cc33cf",
            "68a2e30bb78a492b51f5f1a3",
            "68a2e30b7306c02b458956f7",
            "68abcf6e5e49d202bb5568ce",
            "68abcf6f719bc05600b18a1d",
            "68abcf6f38d8aa2886ba6eb1",
            "68abcf70f01bcc2893eea895",
            "68abcf71468a8228805a44ac",
            "68abcf74c54f2755ebc0960b",
            "68abcf754d445b28a6a3caff",
            "68abcf75468a8228805a44df",
            "68abcf765e49d202bb5569b1",
            "68abcf7640e74455f1f31c8f",
            "68abcf76468a8228805a44e6",
            "68abcf76c4758902b30a8433",
            "68abcf77ff4d5402c5c4f004",
            "68abcf779135e4560fec01ec",
            "68abcf77c54f2755ebc09614",
            "68abcf77f01bcc2893eea8e8",
            "68abcf77c4758902b30a8449",
            "68abcf77719bc05600b18a3d",
            "68abcf7940e74455f1f31caf",
            "68abcf7940e74455f1f31cb7",
            "68abcf795e49d202bb5569e1",
            "68abcf799135e4560fec0206",
            "68abcf799135e4560fec020d",
            "68abcf799135e4560fec0217",
            "68abcf7a5e49d202bb5569ec",
            "68abcf7ac4758902b30a8476",
            "68abcf7bc4758902b30a84ac",
            "68abcf7b468a8228805a451e",
            "68abcf7c5e49d202bb556a6b",
            "68abcf7cf01bcc2893eea91e",
            "68abcf7c468a8228805a4529",
            "68abcf7cff4d5402c5c4f12a",
            "68abcf7c468a8228805a4530",
            "68abcf7d40e74455f1f31cf8",
            "68abcf7dc4758902b30a8500",
            "68abcf7d5e49d202bb556aef",
            "68abcf7d40e74455f1f31d00",
            "68abcf7dc54f2755ebc0965f",
            "68abcf7d5e49d202bb556afa",
            "68abcf7d40e74455f1f31d0a",
            "68abcf7eff4d5402c5c4f155",
            "68abcf7e5e49d202bb556b07",
            "68abcf7e4d445b28a6a3cb8f",
            "68abcf7e5e49d202bb556b12",
            "68abcf7e39fee202d4c117be",
            "68abcf7eff4d5402c5c4f169",
            "68abcf7fc4758902b30a8539",
            "68abcf7f39fee202d4c117d7",
            "68abcf7fc4758902b30a8557",
            "68abcf7f719bc05600b18a8b",
            "68abcf7ff01bcc2893eea990",
            "68abcf809135e4560fec0283",
            "68abcf805e49d202bb556c0c",
            "68abcf8040e74455f1f31d26",
            "68abcf804d445b28a6a3cbc0",
            "68abcf80c54f2755ebc096af",
            "68abcf815e49d202bb556c3d",
            "68abcf81468a8228805a456e",
            "68abcf8138d8aa2886ba6f7a",
            "68abcf819135e4560fec029f",
            "68abcf825e49d202bb556c50",
            "68abcf82468a8228805a4585",
            "68ad1c05468a82288066e613",
            "68ad1c05468a82288066e61a",
            "68ad1c06c54f2755ebceb7d3",
            "68ad1c0639fee202d4d9b98b",
            "68ad1c0638d8aa2886c906cc",
            "68ad1c0638d8aa2886c906d3",
            "68ad1c0639fee202d4d9b999",
            "68ad1c06719bc05600bf9312",
            "68ad1c069135e4560ff8ebaf",
            "68ad1c073d113423e613a0a5",
            "68ad1c079135e4560ff8ebb6",
            "68ad1c07468a82288066e625",
            "68ad77c2cc0c3d3d5b75813f",
            "68ad778883de315576fe6832",
            "68ad7787dce11b279223296e",
            "68ad77a4cc0c3d3d5b757f57",
            "68ad778e057d753d8386a7c2",
            "68ad77b0cc0c3d3d5b758005",
            "68ad77aeb0cd5d3d660bac50",
            "68ad77c2057d753d8386acb4",
            "68ad77c2dce11b2792232ec9",
            "68ad7787b0cd5d3d660ba7b7",
            "68ad778a07f7a755663c7c64",
            "68ad77a45ae386557c752450",
            "68ad778d5ae386557c752245",
            "68ad77ae07f7a755663c80d0",
            "68ad77ae514005556c02037c",
            "68ad77c25ae386557c7527ae",
            "68ad77c783de315576fe74f5",
            "68ad778a5ae386557c7521da",
            "68ad7787057d753d8386a6b3",
            "68ad77a683de315576fe6a47",
            "68ad77a3dce11b2792232cd9",
            "68ad77aecc0c3d3d5b757fae",
            "68ad77b1057d753d8386ab2a",
            "68ad77c583de315576fe7371",
            "68ad77c4cc0c3d3d5b75819f",
            "68ad779f65126027a6c60d2d",
            "68ad77a307f7a755663c7fe4",
            "68ad77b907f7a755663c824c",
            "68ad77b1514005556c0203b2",
            "68ad77b065126027a6c60f95",
            "68ad77c63701cb279a60bdef",
            "68ad77c4b2c74a27b53aadae",
            "68ad2ca338d8aa2886c9d0ce",
            "68ad2ca4cfcce7046cdaeabc",
            "68ad2ca4c4758902b32644aa",
            "68ad2ca4c4758902b32644ad",
            "68ad2ca5c54f2755ebcfb56b",
            "68ad2ca5ff4d5402c5df3eab",
            "68ad2ca5c4758902b32644bd",
            "68ad2ca6c54f2755ebcfb575",
            "68ad2ca64d445b28a6b1c7d3",
            "68ad2ca63d113423e6149df9",
            "68ad2ca6cfcce7046cdaeacc",
            "68ad2ca640e74455f100c291",
            "68ad2ca6719bc05600c0a072",
            "68ad2ca6cfcce7046cdaead1",
            "68ad2ca7719bc05600c0a076",
            "68ad2ca7ff4d5402c5df3ee3",
            "68ad2ca7468a82288067c77f",
            "68ad2ca240e74455f100c254",
            "68ad2ca24d445b28a6b1c7ad",
            "68ad2ca239fee202d4dbe1f7",
            "68ad2ca33d113423e6149d6c",
            "68ad2ca340e74455f100c27e",
            "68ad2ca39135e4560ff9d7e9",
            "68ad2ca84d445b28a6b1c7da",
            "68ad2ca8c54f2755ebcfb57e",
            "68ad2ca8cfcce7046cdaeaed",
            "68ad2ca89135e4560ff9d823",
            "68ad2ca9c4758902b32644e9",
            "68ad2ca9468a82288067c79a",
            "68ad2ca73d113423e6149dfe",
            "68ad2ca7ff4d5402c5df3eec",
            "68ad2ca7c54f2755ebcfb57a",
            "68ad2ca83d113423e6149e01",
            "68ad2ca8468a82288067c783",
            "68ad2ca8cfcce7046cdaeae9",
            "68ad7907dce11b27922339a8",
            "68ad793565126027a6c62096",
            "68ad79353701cb279a60ccd6",
            "68ad791a514005556c0242b7",
            "68ad792e0c81493d7485f026",
            "68ad791383de315576feac6c",
            "68ad7913057d753d8386b846",
            "68ad7908cc0c3d3d5b758beb",
            "68ad7b60057d753d8386cf75",
            "68ad7b5d0c81493d748604f5",
        ];

        $exportList = [];
        foreach ($list as $id){
            $info = DataUtils::getResultData($curlService->s3015()->get("/sgu-sku-scu-maps/{$id}",[]));
            if ($info){
                $hasGroupName = false;
                foreach ($info['channel'] as $channel){
                    if ($channel['groupName']){
                        $hasGroupName = true;
                    }
                }
                $res = (new RequestUtils("test"))->callAliCloudSls2($info['skuScuId']);
                $oldSguId = "";
                $oldSkuId = "";
                if ($res && $res['data'] && count($res['data']) > 0){
                    $logs = $res['data'][0]['FormString'];
                    $data = json_decode($logs,true);
                    $oldSkuId = $data['skuScuId'];
                    $oldSguId = $data['sguId'];
                }
                $exportList[] = [
                    "sku_id" => $info['skuScuId'],
                    "sgu_id" => $info['sguId'],
                    "old_sku_id" => $oldSkuId,
                    "old_sgu_id" => $oldSguId,
                    "hasGroupName" => $hasGroupName ? "有":"无",
                    "createTime" => $info['createdOn'],
                    "modifiedOn" => $info['modifiedOn'],
                ];
            }
        }

        if ($exportList){
            $excelUtils = new ExcelUtils();
            $downloadOssLink = "g号修复的被更新的_" . date("YmdHis") . ".xlsx";
            $downloadOssPath = $excelUtils->downloadXlsx(["sku_id", "sgu_id", "旧A号","旧G号","有分组的","创建日期","修改日期"],$exportList,$downloadOssLink);

        }

    }


    public function fallBack30()
    {


        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/g号修复的被更新的_20250826180811.xlsx");
        if (sizeof($fileFitContent) > 0) {

            $list = [];
            foreach ($fileFitContent as $item){
                if (!empty($item['sku_id'] && $item['有分组的'] == '有')){
                    $list[] = $item['sku_id'];
                }
            }
            $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $list,
                "attrCodeList" => [
                    "custom-skuInfo-skuId",
                    "custom-common-batchNo",
                    "custom-sguInfo-sguId",
                    "custom-sguInfo-groupTag",
                    "custom-sguInfo-channel",
                    "custom-skuInfo-tempSkuId"
                ]
            ]));
            $map  =[];
            if ($getKeyResp){
                foreach ($getKeyResp as $item){
                    $map[$item['custom-skuInfo-skuId']] = $item;
                }
            }

            $fix30List = [];

            foreach ($fileFitContent as $item){
                if (!empty($item['sku_id'] && $item['有分组的'] == '有')){
                    if (!isset($map[$item['sku_id']])){
                        continue;
                    }
                    $tempSkuId = $map[$item['sku_id']]['custom-skuInfo-tempSkuId'];
                    $sguId = $item['旧G号'];

                    $curlService = (new CurlService())->pro();
                    $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
                        "skuScuId" => $item['sku_id'],
                        "limit" => 1,
                    ]));
                    if ($sguInfo){
                        $sguInfo['sguId'] = $sguId;
                        $sguInfo['modifiedBy'] = "system(zhouangang)";
                        $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);
                    }



                    $fix30List[] = [
                        "tempSkuId" => $tempSkuId,
                        "skuAttrList" => [
                            [
                                "name" => "custom-sguInfo-sguId",
                                "value" => $sguId
                            ]
                        ]
                    ];



                }
            }

//            if ($fix30List){
//                foreach (array_chunk($fix30List,200) as $chunkFix30List){
//                    $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
//                    $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
//                        "operator" => "zhouangang",
//                        "skuList" => $chunkFix30List
//                    ]));
//                }
//            }


        }





    }


    public function searchLossSku()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/22-26生成的sku数据.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $skuIdList = array_column($fileFitContent,"productid");

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

            $sampleMap = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $curlServiceS = (new CurlService())->pro();
                $curlServiceS->gateway()->getModule('wms');
                $resp = DataUtils::getNewResultData($curlServiceS->getWayPost($curlServiceS->module . "/receive/sample/expect/v1/page", [
                    "skuIdIn" => $chunk,
                    "vertical" => "PA",
                    "pageSize" => 500,
                    "pageNum" => 1,
                ]));
                if ($resp['list']){
                    foreach ($resp['list'] as $item){
                        $sampleMap[$item['skuId']][$item['category']] = $item;
                    }
                }

            }


            $export = [];
            foreach ($fileFitContent as $item){
                $data = $item;
                if (isset($skuIdProductLineMap[$item['productid']])){
                    $data['noProductLineId'] = "有";
                }else{
                    $data['noProductLineId'] = "无";
                }

                if (!isset($sampleMap[$item['productid']])){
                    $data['noSampleBg'] = "无";
                    $data['noSampleDt'] = "无";
                }else{
                    if (isset($sampleMap[$item['productid']]['bg'])){
                        $data['noSampleBg'] = "有";
                    }else{
                        $data['noSampleBg'] = "无";
                    }

                    if (isset($sampleMap[$item['productid']]['dataTeam'])){
                        $data['noSampleDt'] = "有";
                    }else{
                        $data['noSampleDt'] = "无";
                    }
                }

                $export[]=$data;

            }

            if (count($export) > 0){
                $excelUtils = new ExcelUtils();
                $downloadOssLink = "22-26sku后续问题HHHHHHHH_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["productid",	"producttype",	"status",	"createdon","有无产品线","有无bg留样","有无资料留样"],$export,$downloadOssLink);

            }


        }

    }


    public function fixLossSku()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/22-26sku后续问题_20250901183523.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $skuIdList = [];
            foreach ($fileFitContent as $item){
                if ($item['producttype'] == 'SKU' && $item['有无产品线'] == "无"){
                    $skuIdList[] = $item['productid'];
                }
            }
            $productLineNameSkuIdList = [];
            foreach (array_chunk($skuIdList,200) as $chunk){

                $productList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                    "limit" => 200,
                    "productId" => implode(",",$chunk)
                ]));
                foreach ($productList as $info){
                    $cnCategoryList = explode(" -> ",$info['cn_Category']);
                    $endCategoryName = end($cnCategoryList);

                    $productLineNameSkuIdList[$endCategoryName . "-" . $info['category']][$info['developerUserName']][$info['salesUserName']][] = $info['productId'];
                }
            }


            foreach ($productLineNameSkuIdList as $aProductLineName => $firstObj){
                foreach ($firstObj as $developName => $secondObj){
                    foreach ($secondObj as $salesUserName => $skuIdList){
                        $this->log("{$aProductLineName} - {$developName} - {$salesUserName} ：". json_encode($skuIdList,JSON_UNESCAPED_UNICODE));


                        if (count($skuIdList) > 0){
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

                            $resp = $curlService->s3009()->get("product-operation-lines/getProductOperatorMainInfoByProductLineName",[
                                "productLineName" => $aProductLineName
                            ]);
                            if (empty($resp['result'])){
                                $uuid = DataUtils::buildGenerateUuidLike();
                                $this->log("生成product_line_id：{$uuid}");
                                //echo $uuid;
                                //没有产品线，创建产品线
                                $createProductMainResp = $curlService->s3009()->post("product-operation-lines/createProductOperatorMainInfo", [
                                    "modifiedBy" => "pa_fix_system",
                                    "createdBy" => "pa_fix_system",
                                    "traceMan" => $salesUserName,
                                    "developer" => $developName,
                                    "product_line_id" => "PA_NEW_" . $uuid,
                                    "productLineName" => $aProductLineName,
                                    "companySequenceId" => "CR201706060001",
                                ]);
                                if ($createProductMainResp){

                                    foreach ($skuIdList as $skuId){

                                        if (isset($skuIdProductLineMap[$skuId])){
                                            //先删除
//                                                $delResp = $curlService->s3009()->post("product-operation-lines/removeSkuIdBySkuId", [
//                                                    "skuIdArray" => $skuId
//                                                ]);
//                                                $this->logger->log2("已删除：".json_encode($delResp,JSON_UNESCAPED_UNICODE));

                                            //更新
                                            $skuData = $skuIdProductLineMap[$skuId];
                                            $skuData['developer'] = $developName;
                                            $skuData['traceMan'] = $salesUserName;
                                            $skuData['operatorName'] = $salesUserName;
                                            $skuData['userName'] = $salesUserName;
                                            $curlService->s3009()->put("product-operation-lines/{$skuData['_id']}",$skuData);
                                            continue;
                                        }else{
                                            $mainInfo = $createProductMainResp['result'];
                                            $curlService->s3009()->post("product-operation-lines", [
                                                "companySequenceId" => $mainInfo['companySequenceId'],
                                                "productLineName" => $mainInfo['productLineName'],
                                                "product_line_id" => $mainInfo['product_line_id'],
                                                "sign" => "NP",
                                                "developer" => $developName,
                                                "traceMan" => $salesUserName,
                                                "createdBy" => $developName,
                                                "modifiedBy" => $developName,
                                                "createdOn" => date("Y-m-d H:i:s",time())."Z",
                                                "verticalName" => "PA",
                                                "operatorName" => $salesUserName,
                                                "skuId" => $skuId,
                                                "userName" => $salesUserName,
                                                "product_operator_mainInfo_id" => $mainInfo['_id'],
                                                "batch" => "",
                                                "factoryId" => "",
                                                "supplyType" => null,
                                                "styleId" => ""
                                            ]);
                                        }

                                    }
                                }

                            }else{
                                $mainInfo = $resp['result'][0];
                                foreach ($skuIdList as $skuId){

                                    if (isset($skuIdProductLineMap[$skuId])){
                                        $skuData = $skuIdProductLineMap[$skuId];
                                        $skuData['developer'] = $developName;
                                        $skuData['traceMan'] = $salesUserName;
                                        $skuData['operatorName'] = $salesUserName;
                                        $skuData['userName'] = $salesUserName;
                                        $curlService->s3009()->put("product-operation-lines/{$skuData['_id']}",$skuData);
                                    }else{
                                        $skuData = [
                                            "companySequenceId" => $mainInfo['companySequenceId'],
                                            "productLineName" => $mainInfo['productLineName'],
                                            "product_line_id" => $mainInfo['product_line_id'],
                                            "sign" => "NP",
                                            "developer" => $developName,
                                            "traceMan" => $salesUserName,
                                            "createdBy" => $developName,
                                            "modifiedBy" => $developName,
                                            "createdOn" => date("Y-m-d H:i:s",time())."Z",
                                            "verticalName" => "PA",
                                            "operatorName" => $salesUserName,
                                            "skuId" => $skuId,
                                            "userName" => $salesUserName,
                                            "product_operator_mainInfo_id" => $mainInfo['_id'],
                                            "batch" => "",
                                            "factoryId" => "",
                                            "supplyType" => null,
                                            "styleId" => ""
                                        ];

                                        $curlService->s3009()->post("product-operation-lines", $skuData);
                                    }

                                }
                            }
                        }

                    }
                }
            }





        }

    }



    public function fixLossSkuV2()
    {

        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/修成30.xlsx");
        if (sizeof($fileFitContent) > 0) {
            //$skuIdList = array_column($fileFitContent,"productid");
            $skuIdList = [];
            foreach ($fileFitContent as $item){
                if ($item['是否留样'] == '修成30'){
                    $skuIdList[] = $item['productid'];
                }
            }

            if (count($skuIdList) > 0){
                $sampleMap = [];
                foreach (array_chunk($skuIdList,200) as $chunk){
                    $curlServiceS = (new CurlService())->pro();
                    $curlServiceS->gateway()->getModule('wms');
                    $resp = DataUtils::getNewResultData($curlServiceS->getWayPost($curlServiceS->module . "/receive/sample/expect/v1/page", [
                        "skuIdIn" => $chunk,
                        "vertical" => "PA",
                        "pageSize" => 500,
                        "pageNum" => 1,
                    ]));
                    if ($resp['list']){
                        foreach ($resp['list'] as $item){
                            $sampleMap[$item['skuId']][$item['category']] = $item;
                        }
                    }

                }


                $needSampleSkuIdList = [];
                foreach ($skuIdList as $skuId) {
                    if (!isset($sampleMap[$skuId])){
                        $needSampleSkuIdList[] = [
                            "category" => "dataTeam",
                            "createBy" => "pa-fix-system",
                            "remark" => "",
                            "skuId" => $skuId,
                            "vertical" => "PA",
                            "state" => 30
                        ];
                        $needSampleSkuIdList[] = [
                            "category" => "bg",
                            "createBy" => "pa-fix-system",
                            "remark" => "",
                            "skuId" => $skuId,
                            "vertical" => "PA",
                            "state" => 30
                        ];
                    }else{

                        if (!isset($sampleMap[$skuId]['bg'])){
                            $needSampleSkuIdList[] = [
                                "category" => "bg",
                                "createBy" => "pa-fix-system",
                                "remark" => "",
                                "skuId" => $skuId,
                                "vertical" => "PA",
                                "state" => 30
                            ];
                        }

                        if (!isset($sampleMap[$skuId]['dataTeam'])){
                            $needSampleSkuIdList[] = [
                                "category" => "dataTeam",
                                "createBy" => "pa-fix-system",
                                "remark" => "",
                                "skuId" => $skuId,
                                "vertical" => "PA",
                                "state" => 30
                            ];
                        }

                    }

                }
                if (count($needSampleSkuIdList) > 0) {
                    $curlServiceSS = (new CurlService())->pro();
                    $curlServiceSS->gateway()->getModule('wms');
                    $createResp = DataUtils::getNewResultData($curlServiceSS->getWayPost($curlServiceSS->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
                    if ($createResp && $createResp['value']) {
                        $this->log("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
                    } else {
                        $this->log("留样打标失败");
                    }
                } else {
                    $this->log("预计留样的数据都已存在，无需留样");
                }


            }



        }






    }


    public function fixEbayTranslationMainSku(){
        $curlService = (new CurlService())->pro();

//        foreach (
//            [
//                "2025 W43 PA for EU_AUTOFIND ES",
//                "2025 W36 PA Part to U sku FR",
//                "2025 W36 PA Motoforti sku FR",
//                "2025 W36 PA luuxhaha sku FR",
//                "2025 W36 PA Infincar sku FR",
//                "2025 W36 PA SOPRO sku FR",
//                "2025 W36 PA X AUTOHAUX sku FR",
//                "2025 W36 PA Tuckbold sku FR",
//                "2025 W36 PA RATCHROLL sku FR 人工",
//                "2025 W43 PA for EU_AUTOFIND ES",
//                "2025 W13 PA for EU_HEROCAR ES",
//            ] as $title
//        ) {
//            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
//                "limit" => 1,
//                "page" => 1,
//                "title" => $title,
//            ]));
//            if ($mainInfo['status'] != "5") {
//                $mainInfo['transfer'] = "2";
//                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
//                $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));
//
//            }
//        }


        foreach (
            [
                "2025 W49 MRO EU4 AI翻译 SKU IT 182",
                "2025 W49 MRO EU4 AI翻译 SKU IT 212"
            ] as $title
        ) {
            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_management_ebays/queryPage", [
                "limit" => 1,
                "page" => 1,
                "batch_title" => $title,
            ]));
            if ($mainInfo['status'] != "5") {
                $mainInfo['status'] = "1";
                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_management_ebays/{$mainInfo['_id']}", $mainInfo));
                $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_ebay_skus/queryPage", [
                    "limit" => 1000,
                    "translationMainId" => $mainInfo['_id']
                ]));
                if ($detailList) {
                    foreach ($detailList as $detail) {
                        if ($detail['status'] != "5") {
                            $detail['status'] = "1";
                            DataUtils::getResultData($curlService->s3015()->put("translation_management_ebay_skus/{$detail['_id']}", $detail));
                        }
                    }
                }

            }

        }



    }
    public function downloadChannelAmazonCategory()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getResultData($curlService->s3015()->get("channel-amazon-categories/queryPage", [
                "channel" => "amazon_jp",
                "columns"=>"channel,categoryId,categoryName,leafCategory,browsePathId,browsePathName",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $list[] = [
                    "channel" => $info['channel'],
                    "categoryId" => $info['categoryId'],
                    "categoryName" => $info['categoryName'],
                    "leafCategory" => $info['leafCategory'],
                    "browsePathId" => $info['browsePathId'],
                    "browsePathName" => $info['browsePathName'],
                ];
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();

            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "categoryId",
                "categoryName",
                "leafCategory",
                "browsePathId",
                "browsePathName",
            ], $list, "JP_amazon_category_" . date("YmdHis") . ".xlsx");


        }else{
            $this->log("没有导出");
        }


    }



    public function deleteSpmoDetails()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getPageList($curlService->s3044()->get("pa_spmo_details/queryPage", [
                "batchNo" => "20251013_张桂源_001",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $createDate = new DateTime($info['createdOn']);
                $today = new DateTime('now');
                $today->setTime(0, 0, 0); // 设置时间为 00:00:00 以便比较日期

                if ($createDate->format('Y-m-d') === $today->format('Y-m-d')) {
                    // 如果等于今天，则进入 if 语句块
                    $this->log("createOn 是今天的日期" . $info['skuId']);
                    $curlService->s3044()->delete("pa_spmo_details/{$info['_id']}");
                }

            }

            $page++;
        } while (true);



    }


    public function getProductSku()
    {
        $curlService = new CurlService();
        $curlService = $curlService->test();

        $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
            "verticalId" => "CR201706060001",
            "productType" => "SKU",
            "columns" => "productId",
            "limit" => 5000,
        ]));
        $this->log(json_encode(array_column($list, "productId"), JSON_UNESCAPED_UNICODE));

    }

    public function deleteTranslationManagementEbaySku()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

//        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/deleteEbaySku.xlsx");
//
//        if (sizeof($fileFitContent) > 0) {
//
//
//            $channelSkuIds = [];
//            foreach ($fileFitContent as $info){
//                $channelSkuIds[$info['channel']][] = $info['skuId'];
//            }
//
//            foreach ($channelSkuIds as $channel => $skuIds){
//
//                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_ebay_skus/queryPage", [
//                    "skuId_in" => implode(",", $skuIds),
//                    "status_in" => "0,1",
//                    "channel" => $channel,
//                    "columns" => "skuId",
//                    "limit" => 1000,
//                    "page" => 1
//                ]));
//                if ($detailList) {
//                    foreach ($detailList as $detail) {
//                        $info = DataUtils::getResultData($curlService->s3015()->delete("translation_management_ebay_skus/{$detail['_id']}"));
//                        $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));
//                    }
//                }
//
//
//            }
//
//        }

        $ids = DataUtils::getResultData($curlService->s3015()->get("translation_management_ebay_skus/distinct", [
            "uxField" => "translationMainId",
        ]));
        if (count($ids) > 0){
            $mainIds = DataUtils::getResultData($curlService->s3015()->get("translation_management_ebays/distinct", [
                "uxField" => "_id",
            ]));
            $unsetIds = [];
            foreach ($ids as $id){
                if (!in_array($id,$mainIds)){
                    $this->log("主表不存在: {$id}");
                    $unsetIds[] = $id;
                }else{
                    $this->log("主表存在");
                }
            }
            $this->log(json_encode($unsetIds, JSON_UNESCAPED_UNICODE));
        }



    }


    public function exportAmazonUsAttribute(){
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/需导Amazon _us部分资料.xlsx");


        if (sizeof($fileFitContent) > 0){
            $skuIdList = array_column($fileFitContent, "SKUID");
            $exportList = [];
            foreach (array_chunk($skuIdList, 200) as $skuIds){

                $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => implode(",", $skuIds),
                    "columns" => "productId,title,description,weight_net,attribute",
                    "limit" => 200
                ]));
                foreach ($list as $info){
                    $jinzhong = ($info['weight_net'] ?? 0) * 1000;

                    $data = [];
                    $data['skuId'] = $info['productId'];
                    $data['jinzhong'] = $jinzhong;
                    foreach ($info['attribute'] as $attr){
                        if (in_array($attr['label'], [
                            "title",
                            "description",
                            "Bullet_1",
                            "Bullet_2",
                            "Bullet_3",
                            "Bullet_4",
                            "Bullet_5",
                            "item_length_width/length",
                            "item_length_width/width",
                            "item_height",
                            "Color",
                            "material"
                        ]) && $attr['channel'] == "amazon_us"){
                            $data[$attr['label']] = $attr['value'];
                        }
                    }
                    if (!isset($data['title']) || !$data['title']){
                        $data['title'] = $info['title'];
                    }
                    if (!isset($data['description']) || !$data['description']){
                        $data['description'] = $info['description'];
                    }
                    //字段重新排序
                    $lastData = [];
                    foreach (['skuId','title','description','Bullet_1','Bullet_2','Bullet_3','Bullet_4','Bullet_5','item_length_width/length','item_length_width/width','item_height','Color','material','jinzhong'] as $field){
                        $lastData[$field] = $data[$field] ?? "";
                    }
                    $exportList[] = $lastData;
                    $this->redis->hSet("exportAmazonUsAttr", $data['skuId'], json_encode($lastData, JSON_UNESCAPED_UNICODE));
                }

            }

        }

        if (sizeof($exportList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx([
                "SKUID",
                "标题",
                "描述",
                "Bullet_1",
                "Bullet_2",
                "Bullet_3",
                "Bullet_4",
                "Bullet_5",
                "item_length_width/length",
                "item_length_width/width",
                "item_height",
                "Color",
                "material",
                "净重(g)"
            ], $exportList, "AmazonUS属性和净重_" . date("YmdHis") . ".xlsx");
        }



    }

    public function exportBusinessModules()
    {

        $curlService = (new CurlService())->pro();

        $list = DataUtils::getPageList($curlService->ux168()->get("business_modules/queryPage", [
            "vertical" => "PA",
            "activeStatus"=>1,
            "limit" => 1000,
            "page" => 1
        ]));
        if (sizeof($list) > 0){

            $exportList = [];
            foreach ($list as $info){
                $exportList[] = [
                    "groupId" => $info['groupId'],
                    "supplierId" => $info['supplierId'],
                ];
            }


            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx([
                "groupId",
                "supplierId",
            ], $exportList, "test寄卖商PA_" . date("YmdHis") . ".xlsx");
        }


    }



    public function syncBusinessModulesToTest()
    {

        $curlService = (new CurlService())->pro();

        $list = DataUtils::getPageList($curlService->ux168()->get("business_modules/queryPage", [
            "vertical" => "PA",
            "activeStatus"=>1,
            "limit" => 1000,
            "page" => 1
        ]));
        if (sizeof($list) > 0){

            $proListMap = [];
            foreach ($list as $info){
                $proListMap[$info['groupId'].$info['supplierId']] = $info;
            }

            $curlServicet = (new CurlService())->uat();
            $testList = DataUtils::getPageList($curlServicet->ux168()->get("business_modules/queryPage", [
                "vertical" => "PA",
                "activeStatus"=>1,
                "limit" => 1000,
                "page" => 1
            ]));
            if (sizeof($testList) > 0){
                $testListMap = [];
                foreach ($testList as $info){
                    $testListMap[$info['groupId'].$info['supplierId']] = $info;
                }

                foreach ($proListMap as $key => $info){
                    if (!isset($testListMap[$key])){
                        $curlServicet->ux168()->post("business_modules", $info);
                    }else{
                        $curlServicet->ux168()->delete("business_modules/{$testListMap[$key]['_id']}");
                        $curlServicet->ux168()->post("business_modules", $info);
                    }


                }

            }




        }


    }


    public function fixProductSkuCurrent()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/product_sku存在币种属性值为false的.xlsx");


        if (sizeof($fileFitContent) > 0) {
            $skuIdList = array_unique(array_column($fileFitContent, "productid"));

            $channelSkuMap = [];
            foreach ($fileFitContent as $info){
                $channelSkuMap[$info['productid']] = $info['channel'];
            }
//            $skuIdList = [
//
//                "a20112600ux0155",
//
//                "a20112600ux0156",
//
//            ];
            $curlService = (new CurlService())->pro();

            $productIdMap = [];
            foreach (array_chunk($skuIdList,100) as $chunk){
                $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => implode(",",$chunk),
                    "limit" => count($chunk)
                ]));
                if ($productSkuList){
                    foreach ($productSkuList as $info){
                        $productIdMap[$info['productId']] = $info;
                    }
                }
            }


            $export = [];
            foreach ($skuIdList as $sku){
                if (isset($productIdMap[$sku])){
                    $productInfo = $productIdMap[$sku];

                    $deleteMap = [];
                    foreach ($productInfo['attribute'] as $info){
                        if (in_array($info['label'],[
                            'MSRPWithTax_currency',
                                'MSRP_currency',
//                                'MSRP',
//                                'MSRPWithTax'
                            ]) && isset($channelSkuMap[$productInfo['productId']]) && $info['channel'] == $channelSkuMap[$productInfo['productId']]){
                            $this->log($sku . "渠道：{$info['channel']} {$info['label']} 值为: " . $info['value']);
                            $export[] = [
                                "skuId" => $sku,
                                "channel" => $info['channel'],
                                "MSRPWithTax_currency" => $info['value']
                            ];
                            $key = $info['label'] . '|' . $info['channel'];
                            $deleteMap[$key] = true;
                        }
                    }

                    if (count($deleteMap) == 0){
                        $this->log($sku . "不存在币种属性值的");
                        continue;
                    }
                    $filtered = [];
                    foreach ($productInfo['attribute'] as $item) {
                        $currentKey = $item['label'] . '|' . $item['channel'];
                        // 不在删除列表中的元素保留
                        if (!isset($deleteMap[$currentKey])) {
                            $filtered[] = $item;
                        }
                    }
                    $productInfo['attribute'] = $filtered;

                    $productInfo['userName'] = "system(zhouangang)";
                    $productInfo['action'] = "修复币种不一导致上架失败问题";
                    //$this->log(json_encode($productInfo['attribute'],JSON_UNESCAPED_UNICODE));
                    $this->log($sku . "该删");
                    //$this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                    $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
                }
            }

//            if (sizeof($export) > 0){
//                $excelUtils = new ExcelUtils();
//                $filePath = $excelUtils->downloadXlsx([
//                    "skuId",
//                    "channel",
//                    "MSRPWithTax_currency"
//                ], $export, "币种修复_" . date("YmdHis") . ".xlsx");
//            }


        }


    }



    public function fastProductSkuCurrent()
    {
        $curlService = (new CurlService())->test();

        $skuIdList = [
            "a23100800ux0272"
        ];
        $targetChannel = ["amazon_us","amazon_it"];
        $targetLabel = [
            'MSRPWithTax_currency',
            'MSRP_currency',
        ];
        $productIdMap = [];
        foreach (array_chunk($skuIdList,100) as $chunk){
            $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                "productId" => implode(",",$chunk),
                "limit" => count($chunk)
            ]));
            if ($productSkuList){
                foreach ($productSkuList as $info){
                    $productIdMap[$info['productId']] = $info;
                }
            }
        }


        $export = [];
        foreach ($skuIdList as $sku){
            if (isset($productIdMap[$sku])){
                $productInfo = $productIdMap[$sku];


                foreach ($targetChannel as $ch){
                    foreach ($targetLabel as $lb){

                        $found = false;
                        // 遍历现有属性，查找匹配的channel和label
                        foreach ($productInfo['attribute'] as &$info) {
                            if ($info['channel'] === $ch && $info['label'] === $lb) {
                                // 找到匹配项，更新value
                                $oldValue = $info['value'];
                                $info['value'] = "GBP";
                                $this->log($sku . " 渠道：{$info['channel']} {$info['label']} 值已更新 - 旧值: {$oldValue}, 新值: GBP");
                                $found = true;
                            }
                        }

                        // 如果没有找到匹配项，新增属性
                        if (!$found) {
                            $newAttribute = [
                                'channel' => $ch,
                                'label' => $lb,
                                'value' => "GBP"
                            ];
                            $productInfo['attribute'][] = $newAttribute;
                            $this->log($sku . " 新增属性 - 渠道：{$ch} 标签：{$lb} 值：GBP");
                        }


                    }
                }

                $productInfo['userName'] = "system(zhouangang)";
                $productInfo['action'] = "测试币种，增加不一样的币种";
                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
            }
        }
    }



    public function updatePaSkuMaterialV2()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/111111.xlsx");

        if (count($fileFitContent) > 0){
            $ceBillNoSalesMap = [];
            foreach ($fileFitContent as $info){
                $ceBillNoSalesMap[$info['CE/QD单号']] = [
                    "old" => $info['产品运营(调整前)'],
                    "new" => $info['产品运营(调整后)'],
                ];
            }

            foreach ($ceBillNoSalesMap as $ceBillNo => $salesName){
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1,
                    "page" => 1,
                    "batchName" => $ceBillNo
                ]));
                if (count($l) == 0){
                    continue;
                }

                foreach ($l as $item){
                    if (isset($ceBillNoSalesMap[$item['batchName']])){
                        if ($item['saleName'] == $ceBillNoSalesMap[$item['batchName']]['old']){
                            $item['saleName'] = $ceBillNoSalesMap[$item['batchName']]['new'];
                        }
                        if ($item['ebayTraceMan'] == $ceBillNoSalesMap[$item['batchName']]['old']){
                            $item['ebayTraceMan'] = $ceBillNoSalesMap[$item['batchName']]['new'];
                        }


                        // 根据条件替换
                        if (isset($item['saleNameList']) && is_array($item['saleNameList'])) {
                            foreach ($item['saleNameList'] as $key => $value) {
                                if ($value == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                    $item['saleNameList'][$key] = $ceBillNoSalesMap[$item['batchName']]['new'];
                                }
                            }
                            $item['saleNameList'] = array_unique($item['saleNameList']);
                        }
                        if (isset($item['ebayTraceManList']) && is_array($item['ebayTraceManList'])) {
                            foreach ($item['ebayTraceManList'] as $key => $value) {
                                if ($value == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                    $item['ebayTraceManList'][$key] = $ceBillNoSalesMap[$item['batchName']]['new'];
                                }
                            }
                            $item['ebayTraceManList'] = array_unique($item['ebayTraceManList']);
                        }
                        $curlService->s3044()->put("pa_ce_materials/{$item['_id']}",$item);

                        $this->log("修改{$item['ceBillNo']}的负责人为：{$item['saleName']}");
                        $this->log(json_encode($item['saleNameList'],JSON_UNESCAPED_UNICODE));
                        $this->log(json_encode($item['ebayTraceManList'],JSON_UNESCAPED_UNICODE));
                    }else{
                        $this->log("{$item['batchName']}没有数据");
                    }
                }

            }

        }else{
            $this->log("没有可以修改的数据");
        }
    }


    public function consignmentQD($params){
        $curlService = new CurlService();
        $curlService = $curlService->gateway();
        $env = $curlService->environment;
        $params['qdList'] = [
            "QD202510270009",
            "QD202511060015",
            "QD202511060013",
            "QD202511060012",
            "QD202511060006"
        ];

        $curlService->getModule("pa");
        $createResp = DataUtils::getResultData($curlService->getWayPost($curlService->module . "/scms/consignment/workflow/v1/autoHandleWaitAssign", $params['qdList']));
        if ($createResp){

            $this->log(json_encode($createResp,JSON_UNESCAPED_UNICODE));
        }


    }


    public function fixProductSkuCategory(){
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/2006个SGU需要帮忙导入中文分类.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {


            $request = new RequestUtils('pro');
            $categoryIdInfo = $request->getCategoryIdInfoV2(30980);
            $curlService = (new CurlService())->pro();

            $list = array_unique(array_column($fileFitContent,"SGU"));
            $map = [];
            foreach (array_chunk($list,120) as $chunkList){
                $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => implode(",",$chunkList),
                    "limit" => 120
                ]));
                if ($infoList){
                    foreach ($infoList as $info){
                        $map[$info['productId']] = $info;
                    }
                }
            }


            foreach ($fileFitContent as $info){

                if (isset($map[$info['SGU']])){
                    $productInfo = $map[$info['SGU']];

                    $productInfo['category'] = $categoryIdInfo['categoryId'];
                    $productInfo['categoryPaths'] = $categoryIdInfo['categoryIds'];
                    $productInfo['cn_Category'] = $categoryIdInfo['cnCategoryFullPath'];


                    $productInfo['action'] = "业务需要导入中文分类";
                    $productInfo['userName'] = "system(zhouangang)";

                    $this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                    if ($resp){

                    }
                }
            }
        }


    }


    public function fallBackQD()
    {

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/要改的单.xlsx");
        if (sizeof($fileFitContent) > 0) {

            $list = [];
            foreach ($fileFitContent as $item){
                if (!empty($item['产品序号'])){
                    $list[] = $item['产品序号'];
                }
            }
//            $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
//            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
//                "skuIdList" => $list,
//                "attrCodeList" => [
//                    "custom-skuInfo-supplierId",
//                    "custom-skuInfo-factoryId",
//                    "custom-skuInfo-supplierType",
//                    "custom-prePurchase-prePurchaseMainId",
//                    "custom-prePurchase-prePurchaseBillNo"
//                ]
//            ]));
//            $map  =[];
//            if ($getKeyResp){
//                foreach ($getKeyResp as $item){
//                    $map[$item['custom-skuInfo-skuId']] = $item;
//                }
//            }

            $fix30List = [];

            foreach ($fileFitContent as $item){

                $fix30List[] = [
                    "tempSkuId" => $item['产品序号'],
                    "skuAttrList" => [
                        [
                            "name" => "custom-skuInfo-supplierId",
                            "value" => 0
                        ],
                        [
                            "name" => "custom-skuInfo-factoryId",
                            "value" => 0
                        ],
                        [
                            "name" => "custom-skuInfo-supplierType",
                            "value" => "consignment"
                        ],
                        [
                            "name" => "custom-prePurchase-prePurchaseMainId",
                            "value" => "1988592639574650880"
                        ],
                        [
                            "name" => "custom-prePurchase-prePurchaseBillNo",
                            "value" => "QD202511120017"
                        ],
                        [
                            "name" => "custom-common-qdBillNo",
                            "value" => "QD202511120017"
                        ]
                    ]
                ];

            }

            if ($fix30List){
                foreach (array_chunk($fix30List,200) as $chunkFix30List){
                    $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
                    $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
                        "operator" => "zhouangang",
                        "skuList" => $chunkFix30List
                    ]));
                }
            }


        }





    }


    public function fixTranslationManagement()
    {
        $curlService = (new CurlService())->pro();


        foreach ([
                     [
                         "titleList" => [
                             "2025 W49 MRO pat 人工翻译 SKU JP 0",
                             "(新) 2025 W49 MRO ux AI翻译 SKU JP 3",
                             "(新) 2025 W49 MRO ux AI翻译 SKU JP 23",
                             "(新) 2025 W49 MRO ux AI翻译 SKU JP 61",
                             "(新) 2025 W49 MRO ux AI翻译 SKU JP 46"
                         ],
                         "status" => "4",
                         "applyName" => "huangannan",
                         "applyTime" => "2025-12-22 12:30:00Z"],
                     [
                         "titleList" => [
                             "2025 W45 MRO EU4 AI翻译 SKU FR 65",
                             "2025 W46 MRO PAT AI翻译 SKU DE 31",
                         ],
                         "status" => "4",
                         "applyName" => "shaoanlin",
                         "applyTime" => "2025-12-26 12:30:00Z"
                     ]
                 ] as $info) {

            $status = $info['status'];
            $applyName = $info['applyName'];
            $applyTime = $info['applyTime'];
            $skuIdList = [];


            foreach ($info['titleList'] as $title){


                $params = [
                    "title" => $title,
                ];

                if (DataUtils::checkArrFilesIsExist($params, "title")) {

                    if (!empty($skuIdList)) {
                        $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
                            "limit" => 100,
                            "page" => 1,
                            "title_in" => $params['title'],
                        ]));
                        if ($mainInfo['status'] != "5") {
                            foreach (array_chunk($skuIdList, 200) as $chunk) {
                                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_skus/queryPage", [
                                    "limit" => 1000,
                                    "skuId_in" => implode(",", $chunk),
                                    "translationMainId" => $mainInfo['_id']
                                ]));
                                if ($detailList) {
                                    foreach ($detailList as $detail) {
                                        if ($detail['status'] != "5") {
                                            $detail['status'] = $status;

                                            DataUtils::getResultData($curlService->s3015()->put("translation_management_skus/{$detail['_id']}", $detail));
                                        }
                                    }
                                }
                            }


                            foreach ($mainInfo['skuIdList'] as &$detailInfo) {
                                if (in_array($detailInfo['skuId'],$skuIdList)){
                                    $detailInfo['status'] = $status;
                                }
                            }

                            $mainInfo['status'] = $status;
                            if ($status == '4' && !empty($applyName) && !empty($applyTime)) {
                                //翻译完成的需要审核人
                                $mainInfo['applyUserName'] = $applyName;
                                $mainInfo['applyTime'] = $applyTime;
                            }

                            $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
                            $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));


                        }

                    } else {
                        //全sku的逻辑

                        $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
                            "limit" => 100,
                            "page" => 1,
                            "title_in" => $params['title'],
                        ]));
                        if ($mainInfo['status'] != "5") {
                            $mainInfo['status'] = $status;
                            foreach ($mainInfo['skuIdList'] as &$detailInfo) {
                                $detailInfo['status'] = $status;
                            }
                            if ($status == '4' && !empty($applyName) && !empty($applyTime)) {
                                //翻译完成的需要审核人
                                $mainInfo['applyUserName'] = $applyName;
                                $mainInfo['applyTime'] = $applyTime;
                            }
                            $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
                            $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

                            $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_skus/queryPage", [
                                "limit" => 1000,
                                "translationMainId" => $mainInfo['_id']
                            ]));
                            if ($detailList) {
                                foreach ($detailList as $detail) {
                                    if ($detail['status'] != "5") {
                                        $detail['status'] = $status;

                                        DataUtils::getResultData($curlService->s3015()->put("translation_management_skus/{$detail['_id']}", $detail));
                                    }
                                }
                            }

                        }



                    }
                } else {

                }



            }


        }



    }

    public function getSkuPhotoProgress()
    {
        $c = new ProductSkuController();
        $curlService = (new CurlService())->pro();
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/未写入的图片拍摄.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $skuIdList = array_column($fileFitContent,"productid");
            $preList = $c->getSkuPhotoProgress($skuIdList,"pro");
            $batch = [];
            foreach ($preList as $info){
                if ($info['isExist'] == "可修补"){
                    $batch[] = $info;
                }
            }
            if (count($batch) > 0){
                $curlService->s3015()->post("sku_photography_progresss/createBatch",$batch);
            }
        }
    }



    public function findPaCeSkuMaterialStatusNotSync()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();


        $ceMap = [];
        $page = 1;
        do {
            $this->log($page);
            $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 1000,
                "page" => $page,
//                "status" => "developerComplete",
                "orderBy" => "-_id"
            ]));
            if (count($l) == 0) {
                break;
            }
            foreach ($l as $info) {
                if (preg_match('/^(QD|DPMO)/', $info['batchName'])){
                    $ceMap[$info['ceBillNo']] = $info['status'];
                }else{
                    $this->log("结束了");
                    break 2;
                }
            }
            $page++;
        } while (true);


        if (count($ceMap) > 0){
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $ceList = array_keys($ceMap);

            $ceSkuMap = [];
            foreach (array_chunk($ceList,200) as $chunkBatchNameList){
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "limit" => 1000,
                    "page" => 1,
                    "ceBillNo_in" => implode(",",$chunkBatchNameList)
                ]));
                if (count($l) == 0){
                    continue;
                }
                foreach ($l as $item) {
                    $ceSkuMap[$item['ceBillNo']][$item['status']] = 1;
                }
            }

            $list = [];
            foreach ($ceMap as $ceBillNo => $status){
                if (isset($ceSkuMap[$ceBillNo])){

                    $ceSkuStatus = implode(",",array_keys($ceSkuMap[$ceBillNo]));
                    if ($ceSkuStatus != $status){
                        $list[] = [
                            "ceBillNo" => $ceBillNo,
                            "mainStatus" => $status,
                            "detailStatus" => implode(",",array_keys($ceSkuMap[$ceBillNo]))
                        ];
                    }
                }
            }

            if ($list){
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "ceBillNo",
                    "主状态",
                    "明细状态",
                ], $list, "CE单状态不一致的数据_" . date("YmdHis") . ".xlsx");
            }


        }else{
            $this->log("没有可以修改的数据");
        }
    }

    public function deleteProductSku()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $list = [

            "a25120900ux0039",
            "a25120900ux0040",
            "a25120900ux0041",
            "a25120900ux0042",
            "a25120900ux0044",
            "a25120900ux0045",
            "a25120900ux0047",
            "a25120900ux0048",
            "a25120900ux0050",
            "a25120900ux0051",
            "a25120900ux0053",
            "a25120900ux0054",
            "a25120900ux0056",
            "a25120900ux0057",
            "a25120900ux0059",
            "a25120900ux0060",
            "a25120900ux0062",
            "a25120900ux0063",
            "a25120900ux0065",
            "a25120900ux0066",
            "a25120900ux0068",
            "a25120900ux0069",
            "a25120900ux0071",
            "a25120900ux0072",
            "a25120900ux0074",
            "a25120900ux0075",
            "a25120900ux0076",
            "a25120900ux0078",
            "a25120900ux0080",
            "a25120900ux0081",

        ];

        $dataLIst = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
            "limit" => 1000,
            "page" => 1,
            "productId" => implode(",",$list)
        ]));
        if($dataLIst){
            foreach ($dataLIst as $info){
                $curlService->s3015()->delete("product-skus/{$info['_id']}");
            }
        }

        $dataLIst1 = DataUtils::getPageList($curlService->s3015()->get("product_base_infos/queryPage",[
            "limit" => 1000,
            "page" => 1,
            "productId_in" => implode(",",$list)
        ]));
        if($dataLIst1){
            foreach ($dataLIst1 as $info){
                $curlService->s3015()->delete("product_base_infos/{$info['_id']}");
            }
        }

        $dataLIst12 = DataUtils::getQueryList($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
            "skuScuId_in" => implode(",",$list),
            "limit" => 1000,
        ]));
        if ($dataLIst12){
            foreach ($dataLIst12 as $sguInfo){
                $curlService->s3015()->delete("sgu-sku-scu-maps/{$sguInfo['_id']}");
            }
        }

    }



    public function createSkuConsignmentCe(){

        $list = [
            "QD202512170017"
        ];
        foreach ($list as $qdBillNo){

            $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/createConsignmentCeBillByQdBillNo", [
                "isCreateNewCeBillNo" => false,
                "isDelOldCeBillNo" => true,
                "qdBillNo"=>$qdBillNo,
                "updateBy" => "system(zhouangang)"
            ]));
            $map  =[];
            if ($getKeyResp){
                $this->log(json_encode($getKeyResp));
            }
        }

    }


    public function deleteCeSku(){

        $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
        $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/deleteCeDetailByCeBillNo", [
            "ceBillNo" => "CE202512220038",
            "operatorName" => "system(zhouangang)",
            "reason"=>"删除重复CE单"
        ]));
        $map  =[];
        if ($getKeyResp){
            $this->log(json_encode($getKeyResp));
        }

        $skuList = [
            "a25122200ux0258",
            "a25122200ux0261",
            "a25122200ux0264",
            "a25122200ux0267",
            "a25122200ux0270",
            "a25122200ux0273",
            "a25122200ux0276",
            "a25122200ux0279",
            "a25122200ux0282",
            "a25122200ux0285",
            "a25122200ux0288",
            "a25122200ux0291",
            "a25122200ux0294",
            "a25122200ux0297",
            "a25122200ux0300",
            "a25122200ux0303",
            "a25122200ux0306",
            "a25122200ux0309",
            "a25122200ux0312",
            "a25122200ux0315",
            "a25122200ux0318",
            "a25122200ux0321"
        ];

        $curlService = (new CurlService())->pro();
        $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
            "productId" => implode(",",$skuList),
            "limit" => 500
        ]));
        $map = [];
        if ($infoList){
            foreach ($infoList as $info){
                $map[$info['productId']] = $info;
            }
        }

        foreach ($skuList as $sku){

            if (isset($map[$sku])){
                $productInfo = [
                    "status" => "retired",
                    "userName" => "system(zhouangang)",
                    "action" => "system(删除重复CE号sku)260106",
                    "modifiedOn" => $map[$sku]['modifiedOn'],
                    "modifiedBy" => "system(zhouangang)",
                    "_id" => $map[$sku]['_id'],
                ];
                $this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                if ($resp){

                }
            }
        }

    }

    public function testDing()
    {
        (new RequestUtils("pro"))->dingTalk("测试钉钉通知是否正常");
    }



    public function downloadPaSkuMaterialSpData()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();


        $list = [];
        $page = 1;
        do {
            $this->log($page);
            $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                "limit" => 1500,
                "status_in" => "new,developerComplete,saleComplete,materialComplete",
                "page" => $page
            ]));
            if (count($ll) == 0) {
                break;
            }
            foreach ($ll as $info){
                if (!isset($list[$info['skuId']]) && (!empty($info['keywords']) || !empty($info['cpAsin']) || !empty($info['fitment']))){
                    $list[$info['skuId']] = [
                        "skuId" => $info['skuId'],
                        "keywords" => $info['keywords'],
                        "cpAsin" => $info['cpAsin'],
                        "fitment" => $info['fitment'],
                    ];
                }
            }
            $page++;
        } while (true);


        if (count($list) > 0){

            $exportList1 = [];
            $exportList2 = [];
            foreach ($list as $sku => $info){
                $fitmentDataList = [];

                if (!empty($info['fitment'])){
                    foreach ($info['fitment'] as $fitment){
                        $fitmentData = "{$fitment['make']} {$fitment['model']}";
                        $fitmentDataList[] = $fitmentData;
                    }
                }
                $cpAsinList = [];
                if (!empty($info['cpAsin'])){
                    $cpAsinList = implode("/",$info['cpAsin']);
                }
                $keywordsList = [];
                if (!empty($info['keywords'])){
                    $keywordsList = $info['keywords'];
                }
                $exportList1[] = [
                    "skuid" => $sku,
                    "fitment" => implode("\n",$fitmentDataList),
                    "cpAsin" => $cpAsinList,
                ];

                foreach ($keywordsList as $keywords){
                    $exportList2[] = [
                        "skuid" => $sku,
                        "keywords" => $keywords,
                    ];
                }


            }

            if (count($exportList1) > 0){

                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "skuid",
                    "热销车型",
                    "CPasin",
                ], $exportList1, "sku热销车型和cpasin迁移数据导出_" . date("YmdHis") . ".xlsx");
                $this->log("sku热销车型和cpasin迁移数据导出_");
            }else{
                $this->log("没有sku热销车型和cpasin迁移数据导出_");
            }


            if (count($exportList2) > 0){

                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "skuid",
                    "keyword"
                ], $exportList2, "sku-keyword迁移数据导出_" . date("YmdHis") . ".xlsx");
                $this->log("sku-keyword迁移数据导出_");
            }else{
                $this->log("没有sku-keyword迁移数据导出_");
            }

        }







    }



    public function initQdActionLog()
    {
        $curlPaService = (new CurlService())->test()->getModule("pa")->gateway();
        $curlLogService = (new CurlService())->test()->getModule('ux168log')->gateway();

        $list = [];

        $page = 1;
        do{
            $qdlist = DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignmentqdlist/v1/qdPageList", [
                "pageNum" => $page,
                "pageSize" => 100,
//                "qdBillNoList" => ["QD202601050001"]
            ]));
            if ($qdlist && isset($qdlist['list']) && $qdlist['list'] && count($qdlist['list']) > 0){
                $list = array_merge($list,array_column($qdlist['list'],'consignmentQdId'));
            }else{
                break;
            }
            $page++;
        }while(true);

        if ($list){
            //这里可以读表搞一个映射出来


            foreach ($list as $qdId){
                //记录日志步骤
                //1. 首次发布; 读取第一个publish_record_id 的数据; 但是要根据寄卖商类型，达标供应商(自动发布的) 和 货源供应商(指定发布)
                //操作了清单发布
                $log = [];
                $detailResp = DataUtils::getNewResultData($curlPaService->getWayFormDataPost($curlPaService->module . "/scms/consignmentqdlist/v1/getQdDetail",[
                    "consignmentQdId" => $qdId
                ]));


                if ($detailResp){

                    $logListResp = DataUtils::getNewResultData($curlLogService->getWayPost($curlLogService->module . "/log/v1/query",[
                        "page" => [
                            "pageNum" => 1,
                            "pageSize" => 100,
                        ],
                        "condition" => [
                            "opId" => $qdId,
                            "logSource" => "pa-scms-service",
                            "logType" => "consignment_qd_action"
                        ]
                    ]));
                    $logActionList = [];
                    $existPeoplePublishMap = [];
                    if ($logListResp && isset($logListResp['list']) && $logListResp['list'] && count($logListResp['list']) > 0){
                        //$this->log("有日志：{$info['_id']}");
                        $logActionList = DataUtils::parseAndTransformQdLogList($logListResp['list']);
                        foreach ($logActionList as &$item){
                            $item['consignmentQdId'] = $qdId;
                            $item['qdBillNo'] = $detailResp['qdBillNo'];
                            if ($item['action'] == "清单发布"){
                                $existPeoplePublishMap[$item['afterConsignmentQdPublishRecordId']] = 1;
                            }
                        }
                    }


                    if ($detailResp['consignmentPublishRecordDetailBOList']){
                        $publishCountMap = [];
                        foreach ($detailResp['consignmentPublishRecordDetailBOList'] as $index => $detail){
                            $publishCountMap[$index] = $detail;
                            if($index == 0){
                                $log[] = [
                                    "consignmentQdId" => $qdId,
                                    "qdBillNo" => $detailResp['qdBillNo'],
                                    "beforeConsignmentQdPublishRecordId" => null,
                                    "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                    "beforeBidBillNo" => null,
                                    "afterBidBillNo" => $detail['bidBillNo'],
                                    "beforeGroupId" => null,
                                    "beforeSupplierId" => null,
                                    "afterGroupId" => null,
                                    "afterSupplierId" => null,
                                    "action" => "首次发布",
                                    "remark" => $detail['createBy'] === 'ConsignmentWorkFlow' ? "清单自动发布" : "操作了清单发布",
                                    "createTime" => $detail['createTime'],
                                    "createBy" => $detail['createBy'],
                                ];
                            }else{
                                //>0 有多个清单轮次，这个轮次可以
                                //拿到上一个轮次的数据
                                if (isset($existPeoplePublishMap[$detail['consignmentQdPublishRecordId']])){
                                    //有人工发布的就不需要认为是重新发布

                                }else{
                                    $beforeDetail = $publishCountMap[$index-1];
                                    $log[] = [
                                        "consignmentQdId" => $qdId,
                                        "qdBillNo" => $detailResp['qdBillNo'],
                                        "beforeConsignmentQdPublishRecordId" => $beforeDetail['consignmentQdPublishRecordId'],
                                        "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                        "beforeBidBillNo" => $beforeDetail['bidBillNo'],
                                        "afterBidBillNo" => $detail['bidBillNo'],
                                        "beforeGroupId" => null,
                                        "beforeSupplierId" => null,
                                        "afterGroupId" => null,
                                        "afterSupplierId" => null,
                                        "action" => "重新发布",
                                        "remark" => $detail['createBy'] === 'ConsignmentWorkFlow' ? "因寄卖商未参与竞标且满足重新发布条件，清单自动发布" : "操作了重新发布",
                                        "createTime" => $detail['createTime'],
                                        "createBy" => $detail['createBy'],
                                    ];
                                }

                            }



                            if (count($detail['supplierQdApplyRecordDetailBOList']) > 0){
                                usort($detail['supplierQdApplyRecordDetailBOList'], function ($a, $b) {
                                    return $b['totalScore'] <=> $a['totalScore'];
                                });
                                $groupId = $detail['supplierQdApplyRecordDetailBOList'][0]['groupId'];
                                $supplierId = $detail['supplierQdApplyRecordDetailBOList'][0]['supplierId'];

                                $log[] = [
                                    "consignmentQdId" => $qdId,
                                    "qdBillNo" => $detailResp['qdBillNo'],
                                    "beforeConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                    "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                    "beforeBidBillNo" => $detail['bidBillNo'],
                                    "afterBidBillNo" => $detail['bidBillNo'],
                                    "beforeGroupId" => null,
                                    "beforeSupplierId" => null,
                                    "afterGroupId" => $groupId,
                                    "afterSupplierId" => $supplierId,
                                    "action" => "自动分配",
                                    "remark" => "执行自动分配任务",
                                    "createTime" => date('Y-m-d H:i:s', strtotime('+1 minute', strtotime($detail['createTime']))),
                                    "createBy" => "ConsignmentWorkFlow",
                                ];
                            }
                        }

                        foreach ($logActionList as $logAction){
                            $log[] = $logAction;
                        }

                        $log = DataUtils::removeDuplicateRepublishLogs($log);
                        $log = DataUtils::refineLogActionListV2($log);
                    }


                    if ($log){



                        $this->log(json_encode($log,JSON_UNESCAPED_UNICODE));

                        DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignment/workflow/v1/batchInsertLog",$log));
                    }


                }




            }


        }


    }


    public function deleltePlatformFees(){

//        http://master-angular-nodejs-poms-list-manage.ux168.cn:60015/api/channel-platform-fees/queryPage

        $curlService = (new CurlService())->pro();
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/US定价参数修改 20260206.xlsx");



        $list = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){


                $data = DataUtils::getResultData($curlService->s3015()->get("channel-platform-fees/{$info['_id']}",[]));
                //$this->redis->hSet("channelPlatformFeeBak", $info['_id'], json_encode($data,JSON_UNESCAPED_UNICODE));

                $this->log(json_encode($data));

//                $da = $this->redis->hGet("channelPlatformFeeBak", $info['_id']);
//
//                if ($da){
//                    $data = json_decode($da,true);
//                    if ($data){
//                        $curlService->s3015()->post("channel-platform-fees",$data);
//                    }
//
//                }

            }
        }

        //$this->log(json_encode($list,JSON_UNESCAPED_UNICODE));

    }

    /**
     */
    public function updateSkuSellerConfig()
    {
        $c = (new CurlService())->pro();

        $skus = [
            "a26012900ux1714",
            "a26012900ux1715",
            "a26012900ux1717",
            "a26012900ux1720",
            "a26012900ux1723"
        ];
        $originSku = "a23022300ux0090";

        $channelAndSellerMap = ["amazon_us" => "amazon","amazon_uk"=>"amazon_uk2","amazon_ca"=>"amazon_ca2","amazon_au"=>"amazon_au"];


        $channels = array_keys($channelAndSellerMap);
        $list = DataUtils::getPageList($c->s3015()->get("sku-seller-configs/queryPage", [
            "skuId" => implode(",", $skus),
            "channel" => implode(",", $channels),
            "limit" => 1000
        ]));

        if ($list){

            $skuChannelMap = [];
            foreach ($list as $item){
                $skuChannelMap[$item['skuId']][$item['channel']] = $item;
            }
            $orign = DataUtils::getPageList($c->s3015()->get("sku-seller-configs/queryPage", [
                "skuId" => $originSku,
                "channel" => implode(",", $channels),
                "limit" => 1000
            ]));
            $map = [];
            if ($orign){
                foreach ($orign as $item){
                    $map[$item['skuId']][$item['channel']] = $item;
                }
            }

            foreach ($skuChannelMap as $skuId => $channelMap){
                foreach ($channelMap as $channel => $item){
                    if (isset($map[$originSku][$channel])){
                        $cankao = $map[$originSku][$channel];

                        $cankao['_id'] = $item['_id'];
                        $cankao['skuId'] = $skuId;
                        $cankao['brand'] = "X AUTOHAUX";
                        $cankao['createdOn'] = $item['createdOn'];
                        $cankao['modifiedOn'] = $item['modifiedOn'];
                        $cankao['createdBy'] = $item['createdBy'];
                        $cankao['modifiedBy'] = 'system(zhouangang)';

                        //先删后增
                        $this->log(json_encode($cankao,JSON_UNESCAPED_UNICODE));


                        $c->s3015()->delete("sku-seller-configs/{$item['_id']}");
                        $c->s3015()->post("sku-seller-configs", $cankao);
                    }
                }
            }





        }

    }


}

$curlController = new SyncCurlController();
//$curlController->updateSkuSellerConfig();
//$curlController->deleltePlatformFees();
//$curlController->initQdActionLog();
//$curlController->testDing();
//$curlController->downloadPaSkuMaterialSpData();
//$curlController->createSkuConsignmentCe();
//$curlController->deleteCeSku();
//$curlController->deleteProductSku();
//$curlController->findPaCeSkuMaterialStatusNotSync();
//$curlController->getSkuPhotoProgress();
//$curlController->fallBackQD();
//$curlController->fixProductSkuCategory();
//$curlController->consignmentQD(null);
//$curlController->fixProductSkuCurrent();
//$curlController->fastProductSkuCurrent();
//$curlController->exportAmazonUsAttribute();
//$curlController->syncBusinessModulesToTest();
//$curlController->exportBusinessModules();
//$curlController->deleteTranslationManagementEbaySku();
//$curlController->getProductSku();
//$curlController->deleteSpmoDetails();
//$curlController->downloadChannelAmazonCategory();
//$curlController->fixEbayTranslationMainSku();
//$curlController->fixLossSkuV2();
//$curlController->fixLossSku();
//$curlController->searchLossSku();
//$curlController->fallBack30();
//$curlController->getssss();
//$curlController->fixDengyiyi();
//$curlController->fixTranslationManagementCategory();
//$curlController->fixProductSku();
//$curlController->fixMergeADV2SguId();
//$curlController->fixMergeADSguId();
//$curlController->createSguInfo();
//$curlController->fixRepeatSkuMaterial();
//$curlController->fixRepeatSkuMaterial();
//$curlController->mergeSkuMaterialXlsx();
//$curlController->fixCeMaterialSSSS();
//$curlController->exportBeforeSkuMaterial();
//$curlController->getRepeatSkuMaterialByAliSls();
//$curlController->getRepeatSkuMaterial();
//$curlController->fixTranslationManagement();
//$curlController->fixPaSkuMaterialList();
//$curlController->bindSgu();
//$curlController->fixCeMaterialS();
//$curlController->fixCeMaterial();
//$curlController->ceMaterialObjectLog();
//$curlController->findPrePurchaseBillWithSkuForSkuMaterialInfo();
//$curlController->updateEuSharedWarehouseFlowTypePriority();
//$curlController->getCEBillNo();
//$curlController->updatePaSkuMaterial();
$curlController->updatePaSkuMaterialV2();
//$curlController->downloadPaSkuMaterialSP();
//$curlController->test();
//$curlController->fix();
//$curlController->syncSkuMaterialToAudit();
//$curlController->fixPaSkuPhotoGress();
//$curlController->updateSkuMaterial();
//CE但资料同步
//$curlController->deleteCeMaterial();
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
//$curlController->updateSalesUserNameCancel2();
//$curlController->syncDevSkuInfoToProductSku();
//$curlController->saveReceiveIpCheck();
//$curlController->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => "20201221 - 李锦烽 - 1"]);
//$curlController->deleteCampaign();
//$curlController->createPmo();

//$curlController->updateZhixiao();
//$curlController->fixSkuVerticalId();
//$curlController->fixAmazonSpRuleId();
//$curlController->findCampaign();
//$curlController->downloadPaSkuPhotoProgress();
//$curlController->week();
//$curlController->test();