<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/EnvironmentConfig.php';
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';

/**
 * 更新接口
 * Class update
 */
class update
{


    public $logger;

    private $module = "pa-biz-application";

    /**
     * @var CurlService
     */
    public $envService;
    public function __construct()
    {
        $this->logger = new MyLogger("option/updateLog");
    }

    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "pomsgoods":
                $this->module = "platform-pomsgoods-service";
                break;
            case "config":
                $this->module = "platform-config-mgmt-application";
                break;
        }

        return $this;
    }

    /**
     * 重复品豁免
     * @param $params
     * @return bool
     */
    public function pageSwitchConfig($params)
    {
        $curlService = $this->envService;

        $env = $curlService->environment;

        $batchNameList = $params['batchNameList'];
        $batchNameList = array_unique($batchNameList);

        $oldList = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "limit" => count($batchNameList),
                "page" => 1,
                "batchName_in" => implode(",", $batchNameList)
            ]
        ));
        $ids = [];
        if (!empty($oldList)) {
            $ids = array_column($oldList, "_id");
        }
        if (count($ids) > 0) {
            $this->logger->log("准备修改：" . json_encode($batchNameList, JSON_UNESCAPED_UNICODE));
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "page_switch_config",
                "limit" => 1
            ]));
            if ($info) {
                $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_merge($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'], $ids);
                $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_unique($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds']);

                $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * 翻译
     * @param $params
     * @return array
     */
    public function fixTranslationManagements($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $status = $params['status'];
        $applyName = $params['applyName'];
        $applyTime = $params['applyTime'];
        $skuIdList = isset($params['skuIdList']) && !empty($params['skuIdList']) ? $params['skuIdList'] : [];
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
                    $this->logger->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));


                }
                return true;
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
                    $this->logger->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

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


                return true;
            }
        } else {
            return false;
        }

    }

    /**
     * 资料呈现
     * @param $params
     * @return array
     */
    public function fixCeMaterials($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $_id = $params['_id'];
        $status = $params['status'];
        if (empty($_id) && empty($status)) {
            return false;
        }

        $res = DataUtils::getResultData($curlService->s3044()->get("pa_ce_materials/{$_id}"));

        if ($res && $res['status'] == 'success' && DataUtils::checkArrFilesIsExist($res, 'data')) {
            $mainInfo = $res['data'];
            $mainInfo['status'] = $status;

            $updateRes = DataUtils::getResultData($curlService->s3044()->put("pa_ce_materials/{$mainInfo['_id']}", $mainInfo));
            if ($updateRes && $updateRes['status'] == 'success' && DataUtils::checkArrFilesIsExist($updateRes, 'data')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }


    /**
     * 上架前海外仓移库申请
     * @param $params
     * @return array|false
     */
    public function paFbaChannelSellerConfig($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        if (isset($params['channel']) && $params['channel'] && isset($params['stocksList']) && $params['stocksList']) {
            $channel = $params['channel'];
            $stocksList = $params['stocksList'];

            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "pa_fba_channel_seller_config",
                "limit" => 1
            ]));
            if (!empty($info)) {

                if (isset($info['optionVal']['amazon'][$channel]) && !empty($info['optionVal']['amazon'][$channel])) {
                    $info['optionVal']['amazon'][$channel] = array_merge($info['optionVal']['amazon'][$channel], $stocksList);
                } else {
                    $info['optionVal']['amazon'][$channel] = array_merge([], $stocksList);
                }
                $info['optionVal']['amazon'][$channel] = array_unique($info['optionVal']['amazon'][$channel]);

                $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);

                return true;
            }
            return false;
        } else {
            return false;
        }

    }

    public function paSampleSku($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        if (isset($params['addskuIdList']) && $params['addskuIdList']) {
            $addskuIdList = $params['addskuIdList'];

            foreach (array_chunk($addskuIdList,500) as $skuIdList){
                $curlService->gateway();
                $this->getModule('wms');
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                    "skuIdIn" => $skuIdList,
                    "vertical" => "PA",
                    "category" => "dataTeam",
                    "pageSize" => 500,
                    "pageNum" => 1,
                ]));
                $hasSampleSkuIdList = [];
                $updateSampleSkuIdList = [];

                if (isset($params['onlyUpdateTime']) && $params['onlyUpdateTime']){
                    if (DataUtils::checkArrFilesIsExist($resp, 'list')) {
                        foreach ($resp['list'] as &$item) {
                            $item['updateBy'] = "pa-fix-system";
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/update", $item));
                            if ($createResp && $createResp['value']) {
                                $this->logger->log("更新日期成功...");
                            } else {
                                $this->logger->log("更新日期失败");
                            }
                        }
                    }
                }else{

                    if (DataUtils::checkArrFilesIsExist($resp, 'list')) {
                        foreach ($resp['list'] as $item){
                            if ($item['state'] == 10){
                                $hasSampleSkuIdList[] = $item['skuId'];
                            }else{
                                $updateSampleSkuIdList[] = $item;
                            }
                        }
                        //$hasSampleSkuIdList = array_column($resp['list'], 'skuId');
                        $this->logger->log("部分sku：" . implode(",", $hasSampleSkuIdList) . " 均已经留样，过滤....");
                    }
                    $skuIdList = array_diff($skuIdList, array_column($updateSampleSkuIdList,"skuId"));
                    $skuIdList = array_diff($skuIdList,$hasSampleSkuIdList);
                    if (count($updateSampleSkuIdList) > 0){
                        foreach ($updateSampleSkuIdList as $info){

                            $info['state'] = 10;
                            $info['updateBy'] = "pa-fix-system";
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/update", $info));
                            if ($createResp && $createResp['value']) {
                                $this->logger->log("留样打标更新成功...");
                            } else {
                                $this->logger->log("留样打标失败");
                            }
                        }
                    }
                    if (count($skuIdList) > 0){
                        $needSampleSkuIdList = [];
                        foreach ($skuIdList as $skuId) {
                            $needSampleSkuIdList[] = [
                                "category" => "dataTeam",
                                "createBy" => "pa-fix-system",
                                "remark" => "",
                                "skuId" => $skuId,
                                "vertical" => "PA",
                                "state" => 10
                            ];
                        }
                        if (count($needSampleSkuIdList) > 0) {
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
                            if ($createResp && $createResp['value']) {
                                $this->logger->log("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
                            } else {
                                $this->logger->log("留样打标失败");
                            }
                        } else {
                            $this->logger->log("预计留样的数据都已存在，无需留样");
                        }
                    }

                }

            }
            return true;
        }else{
            return false;
        }

    }

    public function paProductList($params){
        $curlService = $this->envService;

        if (isset($params['excel']) && $params['excel']) {
            $env = $curlService->environment;
            $requestUtils = new RequestUtils($env);
            $productSkuController = new ProductSkuController($env);

            //获取批次号
            if (!$params['excel']['batchName']){
                return [
                    "updateSuccess" => false,
                    "messages" => "批次号不存在"
                ];
            }
            //读取品牌配置化
            $brandMap = $productSkuController->getBrandAttributeByPaPomsSkuBrandInitConfig();

            $paProductIdCollectorList = $requestUtils->getPaProductInfoByBatchNameList([$params['excel']['batchName']]);
            if (count($paProductIdCollectorList) > 0) {

                if (!DataUtils::checkArrFilesIsExist($paProductIdCollectorList, $params['excel']['batchName'])) {
                    $this->logger->log("{$params['excel']['batchName']} 不存在清单列表");
                    return [
                        "updateSuccess" => false,
                        "messages" => "{$params['excel']['batchName']} 不存在清单列表"
                    ];
                }
                $batchInfo = $paProductIdCollectorList[$params['excel']['batchName']];
                $updatePPMainBoolean = $productSkuController->updatePPMain($batchInfo['paProductInfo'], $params['excel'],false);
                if (!$updatePPMainBoolean){
                    return [
                        "updateSuccess" => false,
                        "messages" => "开发清单主表更新失败"
                    ];
                }

                $updatePPDetailBoolean = $productSkuController->updatePPDetail($batchInfo['paProductDetailList'], $params['excel'],$brandMap);
                if (!$updatePPDetailBoolean || !$updatePPDetailBoolean['code']){
                    return [
                        "updateSuccess" => false,
                        "messages" => "开发清单明细或sku资料更新失败：" . json_encode($updatePPDetailBoolean['messages'],JSON_UNESCAPED_UNICODE)
                    ];
                }

            }

            return [
                "updateSuccess" => true,
                "messages" => "{$params['excel']['batchName']} 修改成功"
            ];

        }else{
            return [
                "updateSuccess" => false,
                "messages" => "{$params['excel']['batchName']} 修改失败"
            ];
        }
    }

    public function paFixProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $prePurchaseBillNoList = [];
        if (isset($params['prePurchaseBillNoList']) && $params['prePurchaseBillNoList']) {
            $prePurchaseBillNoList = $params['prePurchaseBillNoList'];
        }else{
            return [
                "updateSuccess" => true,
                "messages" => "请先填写预计采购清单编号,此数据是为了获取sku的产品线信息"
            ];
        }

        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $curlService->gateway();
            $this->getModule('pa');
            $prePurchaseList = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $prePurchaseBillNoList));
            $productLineNameSkuIdList = [];
            if (count($prePurchaseList) > 0) {
                foreach ($prePurchaseList as $mainList) {
                    if ($mainList['detail']){
                        foreach ($mainList['detail'] as $detailList){
                            if (in_array($detailList['skuId'],$params['skuIdList'])){
                                $productLineNameSkuIdList[$detailList['categoryName'] . "-" . $detailList['categoryId']][$detailList['developerUserName']][$detailList['salesUserName']][] = $detailList['skuId'];
                            }
                        }
                    }
                }
            }
            if (count($productLineNameSkuIdList) > 0) {
                foreach ($productLineNameSkuIdList as $aProductLineName => $firstObj){
                    foreach ($firstObj as $developName => $secondObj){
                        foreach ($secondObj as $salesUserName => $skuIdList){
                            $this->logger->log2("{$aProductLineName} - {$developName} - {$salesUserName} ：". json_encode($skuIdList,JSON_UNESCAPED_UNICODE));


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
                                    $this->logger->log2("生成product_line_id：{$uuid}");
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
                                            //先删除
//                                            $delResp = $curlService->s3009()->post("product-operation-lines/removeSkuIdBySkuId", [
//                                                "skuIdArray" => $skuId
//                                            ]);
//                                            $this->logger->log2("已删除：".json_encode($delResp,JSON_UNESCAPED_UNICODE));
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
        } else {
            return [
                "updateSuccess" => false,
                "messages" => "要投入产品线的sku不能为空"
            ];
        }

        return [
            "updateSuccess" => true,
            "messages" => "sku和产品线已添加成功"
        ];
    }


    /**
     * 品牌前面增加for
     * @param $params
     * @return bool
     */
    public function addBrandFor($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        $params['channelList'] = [
            "amazon_us",
            "amazon_uk",
            "amazon_au",
            "amazon_ca",
            "amazon_es",
            "amazon_it",
            "amazon_fr",
            "amazon_de",
            "amazon_jp",
            "ebay_de",
            "ebay_fr",
            "ebay_es",
            "ebay_it",
            "ebay_us",
            "ebay_ca",
            "ebay_uk",
            "ebay_au",
            "walmart_us",
            "walmart_ca",
            "walmart_dsv",
            "wish_us",
            "aliexpress_us",
            "ebay_hk"
        ];
        if (isset($params['fieldsList']) && $params['fieldsList']) {
            $channelList = $params['channelList'];
            $fieldsList = $params['fieldsList'];

            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "pa_amazon_attribute_forbidden",
                "limit" => 1
            ]));
            if (!empty($info)) {
                $this->logger->log2("原本内容：" . json_encode($info, JSON_UNESCAPED_UNICODE));
                if (count($info['optionVal']) > 0) {
                    foreach ($info['optionVal'] as $channel => &$channelInfo) {
                        if (in_array($channel, $channelList)) {
                            foreach ($channelInfo['make'] as &$makeInfo) {
                                if ($makeInfo['type'] == "2") {
                                    foreach ($fieldsList as $field) {
                                        if (!in_array($field, $makeInfo['words'])) {
                                            array_unshift($makeInfo['words'], $field);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);
                    $this->logger->log2("修改后内容：" . json_encode($info, JSON_UNESCAPED_UNICODE));
                }
                return true;
            }
            return false;
        } else {
            return false;
        }
    }


    /**
     * 上传oss
     * @param $params
     * @return array
     */
    public function uploadOss($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        $dateTime = date("Y-m-d H:i:s",time());
        if (isset($params['fileCollect']) && $params['fileCollect']) {
            $successUploadOss = [];
            foreach ($params['fileCollect'] as $dataInfo){

                $target_dir = __DIR__ . "/../export/uploads/oss/{$dataInfo['fileName']}";
                if (!file_exists($target_dir)) {
                    return [
                        "uploadSuccess" => false,
                        "messages" => "请重新传输文件上传,有文件丢失：{$dataInfo['actualFileName']}",
                        "link" => null,
                    ];
                }

                $resp = DataUtils::getNewResultData($curlService->gateway()->getModule('config')->getWayPost($curlService->module . "/message/template/v1/getUploadFileSignature", []));
                if ($resp) {
                    $curlService1 = $this->envService;
                    $curlService1->setHeader(array(
                        'request-trace-id: product_operation_client_' . date("Ymd_His") . '_' . rand(100000, 999999),
                        'request-trace-level: 1',
                        'Content-Type: multipart/form-data',
                    ), false);
                    $cfile = new CURLFile($target_dir, "", $target_dir);

                    $key = "pa/oss_test/{$dataInfo['fileName']}";
                    $uploadOssResp = $curlService1->upload($resp['url'], "", [
                        "OSSAccessKeyId" => $resp['ossAccessKeyId'],
                        "policy" => $resp['policy'],
                        "Signature" => $resp['signature'],
                        "expiresTime" => $resp['expiresTime'],
                        "key" => $key,
                        "success_action_status" => 200,
                        "file" => $cfile
                    ]);
                    if ($uploadOssResp && $uploadOssResp['httpCode'] === 200) {
                        $this->logger->log2("上传文件到oss成功");
                        $getKeyResp = DataUtils::getNewResultData($curlService->gateway()->getModule("config")->getWayGet($curlService->module . "/message/template/v1/getOssUrlByKey", [
                            "key" => $key
                        ]));
                        $this->logger->log2("返回oss文件链接：{$getKeyResp['value']}");
                        if ($getKeyResp) {
                            $redisService = new RedisService();

                            $dbData = [
                                "actualFileName" => $dataInfo['actualFileName'],
                                "key" => $key,
                                "link" => $getKeyResp['value'],
                                "dateTime" => $dateTime
                            ];

                            $redisService->hSet(REDIS_OSS_FILE_NAME_KEY . "_{$env}", $key,json_encode($dbData,JSON_UNESCAPED_UNICODE));
                            unlink($target_dir);

                            $successUploadOss[] = $dbData;
                        }
                    }
                }
            }

            if (count($successUploadOss) > 0){
                return [
                    "uploadSuccess" => true,
                    "messages" => "上传oss成功",
                    "linkList" => $successUploadOss,
                ];
            }
        } else {
            return [
                "uploadSuccess" => false,
                "messages" => "请重新传输文件上传",
                "link" => null,
            ];
        }

        return [
            "uploadSuccess" => false,
            "messages" => "请重新传输文件上传",
            "link" => null,
        ];
    }

    /**
     * 登记个人IP
     * @param $params
     * @return array
     */
    public function registerIp($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;


        $redisService = new RedisService();

        $ip = $_SERVER['REMOTE_ADDR'];;
        $dbData = [
            "name" => $params['userName'],
            "ip" => $ip
        ];
        $redisService->hSet(REDIS_USERNAME_IP_KEY . "_{$env}", $ip,json_encode($dbData,JSON_UNESCAPED_UNICODE));

        $list = [];
        $dbDataList = $redisService->hGetAll(REDIS_USERNAME_IP_KEY . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                $list[] = json_decode($keyInfo,true);
            }
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }


    /**
     * 补充fcu产品线
     * @param $params
     * @return array
     */
    public function fixFcuProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $returnMsg = [];
        if (isset($params['fcuIdList']) && $params['fcuIdList']){
            $fculist = [];
            $skulist = [];
            foreach (array_chunk($params['fcuIdList'],200) as $chunk){
                $fcuResult = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                    "fcuId_in" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($fcuResult && count($fcuResult) > 0){
                    foreach ($fcuResult as $info){
                        $firstSkuId = current($info['skuId']);
                        $skulist[] = $firstSkuId;
                        $fculist[$info['fcuId']] = $info;
                    }
                }
            }
            $skuIdProductLineMap = [];
            if (count($skulist) > 0){
                $list = [];
                foreach (array_chunk($skulist,200) as $chunk){
                    $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                        "skuId" => implode(",",$chunk),
                        "limit" => 200
                    ]));
                    if ($getProductMainResp && count($getProductMainResp['data']) > 0){
                        $list = array_merge($list,$getProductMainResp['data']);
                    }
                }
                if (count($list) > 0){
                    $skuIdProductLineMap = array_column($list,null,"skuId");
                }
            }

            foreach ($params['fcuIdList'] as $fcuId){
                if (isset($fculist[$fcuId])){
                    $fcuInfo = $fculist[$fcuId];
                    $skuId = current($fcuInfo['skuId']);
                    if ($fcuInfo['productLineId']){
                        $returnMsg[] = [
                            "messages" => "已存在，无需补充",
                            "fcuId" => $fcuId,
                            "skuId" => $skuId,
                            "productLine" => $fcuInfo['productLineId'],
                        ];
                        continue;
                    }
                    if (isset($skuIdProductLineMap[$skuId])){
                        $product_operator_mainInfo_id = $skuIdProductLineMap[$skuId]['product_operator_mainInfo_id'];

                        $fcuInfo['productLineId'] = $product_operator_mainInfo_id;
                        $fcuInfo['modifiedBy'] = "pa_system";

                        $sss = DataUtils::getResultData($curlService->s3044()->put("fcu_sku_maps/{$fcuInfo['_id']}", $fcuInfo));
                        $this->logger->log2("更新产品线id成功" . json_encode($sss,JSON_UNESCAPED_UNICODE));


                        $returnMsg[] = [
                            "messages" => "补充产品线成功",
                            "fcuId" => $fcuId,
                            "skuId" => $skuId,
                            "productLine" => $skuIdProductLineMap[$skuId]['productLineName'],
                        ];
                    }else{
                        $this->logger->log2("找不到产品线：{$skuId} - {$fcuId}");
                        $returnMsg[] = [
                            "messages" => "找不到产品线",
                            "fcuId" => $fcuId,
                            "skuId" => $skuId,
                            "productLine" => "",
                        ];
                    }
                }else{
                    $this->logger->log2("找不到fcu：{$fcuId}");
                    $returnMsg[] = [
                        "messages" => "找不到fcu",
                        "fcuId" => $fcuId,
                        "skuId" => "",
                        "productLine" => "",
                    ];
                }
            }
        }

        return ["env" => $env, "data" => $returnMsg];
    }

}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new update();
$return = [];
$class->envService = (new EnvironmentConfig($data['action']))->getCurlService();

switch ($data['action']) {
    case "pageSwitchConfig":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->pageSwitchConfig($params);
        break;
    case "fixTranslationManagements":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixTranslationManagements($params);
        break;
    case "fixCeMaterials":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixCeMaterials($params);
        break;
    case "paFbaChannelSellerConfig":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paFbaChannelSellerConfig($params);
        break;
    case "paSampleSku":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paSampleSku($params);
        break;
    case "paProductList":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paProductList($params);
        break;
    case "paFixProductLine":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paFixProductLine($params);
        break;
    case "addBrandFor":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->addBrandFor($params);
        break;
    case "uploadOss":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->uploadOss($params);
        break;
    case "registerIp":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->registerIp($params);
        break;
    case "fixFcuProductLine":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixFcuProductLine($params);
        break;
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);