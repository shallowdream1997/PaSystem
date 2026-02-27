<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/EnvironmentConfig.php';
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");

/**
 * 更新接口
 * Class update
 */
class update
{


    public $logger;
    /**
     * @var CurlService
     */
    public $envService;
    public function __construct()
    {
        $this->logger = new MyLogger("option/updateLog");
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
                $curlService->gateway()->getModule('wms');
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/page", [
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
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/update", $item));
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
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/update", $info));
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
                            $createResp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
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


    public function paFixProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $curlService->gateway()->getModule('pa');
            $sku30DataList = DataUtils::getNewResultData($curlService->getWayPost( $curlService->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $params['skuIdList'],
                "attrCodeList" => [
                    "custom-skuInfo-skuId",
                    "custom-common-developerUserName",
                    "custom-common-salesUserName",
                    "custom-prePurchase-prePurchaseBillNo",
                    "custom-common-categoryId",
                    "custom-common-categoryName"
                ]
            ]));

            $productLineNameSkuIdList = [];
            if ($sku30DataList){
                foreach ($sku30DataList as $item){
                    if (in_array($item['custom-skuInfo-skuId'],$params['skuIdList'])){
                        $productLineNameSkuIdList[$item['custom-common-categoryName'] . "-" . $item['custom-common-categoryId']][$item['custom-common-developerUserName']][$item['custom-common-salesUserName']][] = $item['custom-skuInfo-skuId'];
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

                $target_dir = __DIR__ . "/../export/uploads/oss/{$dataInfo['actualFileName']}";
                if (!file_exists($target_dir)) {
                    return [
                        "uploadSuccess" => false,
                        "messages" => "请重新传输文件上传,有文件丢失：{$dataInfo['actualFileName']}",
                        "link" => null,
                    ];
                }

                $resp = DataUtils::getNewResultData($curlService->gateway()->getModule('configmgmt')->getWayPost($curlService->module . "/message/template/v1/getUploadFileSignature", []));
                if ($resp) {
                    $curlService1 = $this->envService;
                    $curlService1->setHeader(array(
                        'request-trace-id: product_operation_client_' . date("Ymd_His") . '_' . rand(100000, 999999),
                        'request-trace-level: 1',
                        'Content-Type: multipart/form-data',
                    ), false);
                    $cfile = new CURLFile($target_dir, "", $target_dir);

//                    $key = "pa/oss_test/{$dataInfo['actualFileName']}";
                    $key = "dull_sale/{$dataInfo['actualFileName']}";
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
                        $getKeyResp = DataUtils::getNewResultData($curlService->gateway()->getModule("configmgmt")->getWayGet($curlService->module . "/message/template/v1/getOssUrlByKey", [
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
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $userName = $params['name'] ?? '访客'; // 默认用户名
        
        // 使用"用户名_IP"组合作为唯一key，避免同一IP不同用户互相覆盖
        $userIpKey = $userName . '_' . $ip;
        $ipKey = 'ip_' . $ip; // 用于存储该IP对应的所有用户名
        
        // 获取现有记录
        $existingData = $redisService->hGet(REDIS_USERNAME_IP_KEY . "_{$env}", $userIpKey);
        $userData = $existingData ? json_decode($existingData, true) : null;
        
        if ($userData) {
            // 更新现有记录
            $userData['last_visit'] = date('Y-m-d H:i:s');
            $userData['visit_count'] = ($userData['visit_count'] ?? 0) + 1;
        } else {
            // 创建新记录
            $userData = [
                "name" => $userName,
                "ip" => $ip,
                "last_visit" => date('Y-m-d H:i:s'),
                "first_visit" => date('Y-m-d H:i:s'),
                "visit_count" => 1
            ];
            
            // 更新IP对应的用户名列表
            $ipUsers = $redisService->hGet(REDIS_USERNAME_IP_KEY . "_{$env}", $ipKey);
            $usersList = $ipUsers ? json_decode($ipUsers, true) : [];
            if (!in_array($userName, $usersList)) {
                $usersList[] = $userName;
                $redisService->hSet(REDIS_USERNAME_IP_KEY . "_{$env}", $ipKey, json_encode($usersList, JSON_UNESCAPED_UNICODE));
            }
        }
        
        // 保存用户IP记录
        $redisService->hSet(REDIS_USERNAME_IP_KEY . "_{$env}", $userIpKey, json_encode($userData, JSON_UNESCAPED_UNICODE));
        
        // 获取所有记录用于返回
        $list = [];
        $dbDataList = $redisService->hGetAll(REDIS_USERNAME_IP_KEY . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                // 只返回用户记录，跳过IP索引记录
                if (strpos($key, 'ip_') !== 0 && strpos($key, '_') !== false) {
                    $list[] = json_decode($keyInfo, true);
                }
            }
        }
        
        return [
            "success" => true,
            "message" => "访问记录登记成功",
            "currentUser" => $userData,
            "allRecords" => $list,
            "totalVisitors" => count($list)
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
        $errors = [];
        
        if (isset($params['fcuIdList']) && $params['fcuIdList']){
            $fculist = [];
            $skulist = [];
            
            // 分批查询FCU数据
            foreach (array_chunk($params['fcuIdList'],200) as $chunk){
                try {
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
                } catch (Exception $e) {
                    $errors[] = "查询FCU数据失败: " . $e->getMessage();
                    $this->logger->log2("查询FCU数据异常: " . $e->getMessage());
                }
            }
            
            // 查询SKU对应的产品线映射
            $skuIdProductLineMap = [];
            if (count($skulist) > 0){
                $list = [];
                foreach (array_chunk($skulist,200) as $chunk){
                    try {
                        $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                            "skuId" => implode(",",$chunk),
                            "limit" => 200
                        ]));
                        
                        if ($getProductMainResp && count($getProductMainResp['data']) > 0){
                            $list = array_merge($list,$getProductMainResp['data']);
                        }
                    } catch (Exception $e) {
                        $errors[] = "查询产品线数据失败: " . $e->getMessage();
                        $this->logger->log2("查询产品线数据异常: " . $e->getMessage());
                    }
                }
                
                if (count($list) > 0){
                    $skuIdProductLineMap = array_column($list,null,"skuId");
                }
            }

            // 处理每个FCU ID
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
                        try {
                            $product_operator_mainInfo_id = $skuIdProductLineMap[$skuId]['product_operator_mainInfo_id'];

                            $fcuInfo['productLineId'] = $product_operator_mainInfo_id;
                            $fcuInfo['modifiedBy'] = "pa_system";

                            $updateResult = DataUtils::getResultData($curlService->s3044()->put("fcu_sku_maps/{$fcuInfo['_id']}", $fcuInfo));
                            
                            if ($updateResult && isset($updateResult['status']) && $updateResult['status'] === 'success') {
                                $this->logger->log2("更新产品线id成功: fcuId={$fcuId}, productLineId={$product_operator_mainInfo_id}");
                                
                                $returnMsg[] = [
                                    "messages" => "补充产品线成功",
                                    "fcuId" => $fcuId,
                                    "skuId" => $skuId,
                                    "productLine" => $skuIdProductLineMap[$skuId]['productLineName'],
                                ];
                            } else {
                                $errors[] = "更新FCU记录失败: fcuId={$fcuId}";
                                $this->logger->log2("更新FCU记录失败: " . json_encode($updateResult,JSON_UNESCAPED_UNICODE));
                                
                                $returnMsg[] = [
                                    "messages" => "补充产品线失败",
                                    "fcuId" => $fcuId,
                                    "skuId" => $skuId,
                                    "productLine" => "",
                                ];
                            }
                        } catch (Exception $e) {
                            $errors[] = "更新FCU记录异常: fcuId={$fcuId}, 错误: " . $e->getMessage();
                            $this->logger->log2("更新FCU记录异常: fcuId={$fcuId}, 错误: " . $e->getMessage());
                            
                            $returnMsg[] = [
                                "messages" => "补充产品线异常",
                                "fcuId" => $fcuId,
                                "skuId" => $skuId,
                                "productLine" => "",
                            ];
                        }
                    } else {
                        $this->logger->log2("找不到产品线：{$skuId} - {$fcuId}");
                        $returnMsg[] = [
                            "messages" => "找不到对应的产品线",
                            "fcuId" => $fcuId,
                            "skuId" => $skuId,
                            "productLine" => "",
                        ];
                    }
                } else {
                    $this->logger->log2("找不到fcu：{$fcuId}");
                    $returnMsg[] = [
                        "messages" => "找不到FCU记录",
                        "fcuId" => $fcuId,
                        "skuId" => "",
                        "productLine" => "",
                    ];
                }
            }
        } else {
            $errors[] = "FCU ID列表不能为空";
        }

        $response = [
            "env" => $env, 
            "data" => $returnMsg
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }


    public function consignmentQD($params){
        $curlService = $this->envService->gateway();
        $env = $curlService->environment;


        $createResp = [];
        if (isset($params['qdList']) && !empty($params['qdList'])){

            if(isset($params['status']) && !empty($params['status'])){

                if ($params['status'] == "to发布"){

                    $condition = [
                        "qdBillNos" => $params['qdList'],
                        "hasPublish" => $params['autoPublish'] ?? false,
                        "hasRepublish" => $params['autoRePublish'] ?? false,
                    ];
                    $curlService->getModule("pa");
                    $createResp = DataUtils::getResultData($curlService->getWayPost($curlService->module . "/scms/consignment/workflow/v1/manualExecutionPublish", $condition));
                    if ($createResp){

                        $this->logger->log2(json_encode($createResp,JSON_UNESCAPED_UNICODE));
                    }
                }


                if ($params['status'] == "to待分配"){
                    $curlService->getModule("pa");
                    $createResp = DataUtils::getResultData($curlService->getWayPost($curlService->module . "/scms/consignment/workflow/v1/autoWaitAssign", $params['qdList']));
                    if ($createResp){

                        $this->logger->log2(json_encode($createResp,JSON_UNESCAPED_UNICODE));
                    }
                }

                if($params['status'] == "to处理分配"){
                    $curlService->getModule("pa");
                    $createResp = DataUtils::getResultData($curlService->getWayPost($curlService->module . "/scms/consignment/workflow/v1/autoHandleWaitAssign", $params['qdList']));
                    if ($createResp){

                        $this->logger->log2(json_encode($createResp,JSON_UNESCAPED_UNICODE));
                    }
                }

            }


        }


        return ["env" => $env, "data" => $createResp];
    }

    public function skuPhotoFix($params){

        $curlService = $this->envService->gateway();
        $env = $curlService->environment;


        $createResp = [];
        if (isset($params['skuList']) && !empty($params['skuList'])){
            $preList = (new ProductSkuController())->getSkuPhotoProgress($params['skuList'],$env);

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


        return ["env" => $env, "data" => true];
    }


    public function fixCurrency($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $preList = [];
        if (isset($params['skuId']) && !empty($params['skuId']) &&
            isset($params['channelData']) && !empty($params['channelData'])){

            $skuId = $params['skuId'];
            $channelData = $params['channelData'];

            $list = DataUtils::getPageList( $curlService->s3015()->get("product-skus/queryPage",[
                "productId" => $skuId,
                "limit" => 1
            ]));
            if ($list && count($list) > 0){
                $productInfo = $list[0];

                foreach ($channelData as $ch => $labels){

                    $deleteMap = [];
                    foreach ($productInfo['attribute'] as $info){
                        if (in_array($info['label'],$labels) && $info['channel'] == $ch){
                            $key = $info['label'] . '|' . $info['channel'];
                            $deleteMap[$key] = true;
                        }
                    }

                    if (count($deleteMap) == 0){
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
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
//                    $resp = false;
                    if ($resp && $resp['result'] && $resp['result']['status'] == 'success'){
                        return [
                            "env" => $env,
                            "data" => [
                                "success" => true,
                                "message" => "删除属性成功"
                            ]
                        ];
                    }else{
                        return [
                            "env" => $env,
                            "data" => [
                                "success" => false,
                                "message" => "删除属性失败"
                            ]
                        ];
                    }
                }

            }else{
                return [
                    "env" => $env,
                    "data" => [
                        "success" => false,
                        "message" => "未找到sku信息"
                    ]
                ];
            }
        }
        return [
            "env" => $env,
            "data" => [
                "success" => false,
                "message" => "参数错误"
            ]
        ];
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
    case "paFixProductLine":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paFixProductLine($params);
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
    case "consignmentQD":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->consignmentQD($params);
        break;
    case "skuPhotoFix":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->skuPhotoFix($params);
        break;
    case "fixCurrency":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixCurrency($params);
        break;
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);