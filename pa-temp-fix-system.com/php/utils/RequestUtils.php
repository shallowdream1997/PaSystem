<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredChorm.php");
require_once(dirname(__FILE__) ."/../../php/utils/ProductUtils.php");


class RequestUtils
{
    /**
     * @var CurlService
     */
    private $curlService;

    public function __construct($port){
        $this->curlService = new CurlService();
        $this->curlService->$port();
    }
    // ===================================== pa_product 表的增删改查 基本接口 start =====================================

    public function getPaProductPageList($params)
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_products/queryPage", $params));
    }

    public function readPaProductInfo(string $id)
    {
        return DataUtils::getQueryList($this->curlService->s3015()->get("pa_products/{$id}"));
    }

    public function updatePaProductInfo($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_products/{$params['_id']}",$params));
    }

    public function deletePaProduct($id)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_products/{$id}"));
    }

    /**
     * 根据批次号获取主表和明细表的数据
     * @param $batchName
     * @return array
     */
    public function getPaProductInfoByBatchName($batchName)
    {
        $paProductIdInfo = DataUtils::getArrHeadData($this->getPaProductPageList(["batchName"=>$batchName]));
        $detailList = [];
        if (DataUtils::checkArrFilesIsExist($paProductIdInfo,'_id')){
            $detailList = $this->getPaProductDetailPageList(["paProductId"=>$paProductIdInfo['_id'],"limit" => 1000]);
        }
        return compact(['paProductIdInfo','detailList']);
    }

    /**
     * 根据批次号获取主表和明细表的数据(多个)
     * @param $batchNameList
     * @return array
     */
    public function getPaProductInfoByBatchNameList($batchNameList)
    {
        //拿pa_product主表
        $paProductIdInfoList = [];
        foreach (array_chunk($batchNameList, 150) as $chunk) {
            $paProductIdInfoList = array_merge($paProductIdInfoList, $this->getPaProductPageList(["batchName_in" => implode(",", $chunk), "limit" => 150]));
        }
        $detailIdList = array_unique(array_column($paProductIdInfoList,'_id'));

        //拿pa_product_detail明细表
        $paProductDetailList = [];
        foreach (array_chunk($detailIdList,150) as $dChunk){
            $page = 1;
            do {
                $detailList = $this->getPaProductDetailPageList(["paProductId_in" => implode(",",$dChunk), "limit" => 2000, "page" => $page]);
                if (count($detailList) == 0) {
                    break;
                }
                $paProductDetailList = array_merge($paProductDetailList, $detailList);
                $page++;
            } while (true);
        }

        //主表Id 分组
        $grouped = [];
        foreach ($paProductDetailList as $detail) {
            $groupKey = $detail['paProductId'];
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [];
            }
            $grouped[$groupKey][] = $detail;
        }
        //组合数据，返回主表和明细数据集合
        $paProductIdCollectorList = [];
        foreach ($paProductIdInfoList as $batchNameInfo) {
            $detailId = $batchNameInfo['_id'];
            $paProductIdCollector = [];
            $paProductIdCollector['paProductInfo'] = $batchNameInfo;
            $paProductIdCollector['paProductDetailList'] = [];
            if (DataUtils::checkArrFilesIsExist($grouped,$detailId)){
                $paProductIdCollector['paProductDetailList'] = $grouped[$detailId];
            }
            $paProductIdCollectorList[$batchNameInfo['batchName']] = $paProductIdCollector;
        }

        return $paProductIdCollectorList;
    }

    // ===================================== pa_product 表的增删改查 基本接口 end =====================================

    // ===================================== pa_product_detail 表的增删改查 基本接口 start =====================================
    public function getPaProductDetailPageList($params)
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_product_details/queryPage", $params));
    }
    public function readPaProductDetailInfo(string $id)
    {
        return DataUtils::getQueryList($this->curlService->s3015()->get("pa_product_details/{$id}"));
    }

    public function updatePaProductDetailInfo($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_product_details/{$params['_id']}",$params));
    }

    public function deletePaProductDetail($id)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_product_details/{$id}"));
    }
    public function createPaProductDetail($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->post("pa_product_details",$params));
    }

    public function updateBrandByPaProduct($paProductDetailList, $salesBrand, $reason)
    {
        return DataUtils::getResultData($res = $this->curlService->s3044()->post("pa_product_brand_score_bases/updateBrandByPaProduct", [
            "paProductDetailList" => $paProductDetailList,
            "newSalesBrand" => $salesBrand,
            "updateSalesBrandReason" => $reason,
            "userName" => "system"
        ]));
    }
    // ===================================== pa_product_detail 表的增删改查 基本接口 end =====================================

    // ===================================== option_val_list 表的 基本接口 start =====================================
    public function getOptionValListByName($optionName)
    {
        if (empty($optionName)){
            return [];
        }
        return DataUtils::getPageListInFirstData($this->curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => $optionName,
            "limit" => 1
        ]));
    }


    public function updateOptionValListInfo($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("option-val-lists/{$params['_id']}",$params));
    }

    public function deleteOptionValListInfo($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("option-val-lists/{$params['_id']}"));
    }
    public function createOptionValListInfo($params)
    {
        return DataUtils::getResultData($this->curlService->s3015()->post("option-val-lists",$params));
    }
    // ===================================== option_val_list 表的 基本接口 end =====================================



    // ===================================== system-manages 接口 start =====================================

    /**
     *
     * @param $userName
     * @return array
     */
    public function getUserSheetByUserName($userName)
    {
        $redisService = new RedisService();
        $get = $redisService->hGet(REDIS_USER_NAME_KEY, $userName);
        $userInfo = [];
        if (empty($get)) {
            //缓存不存在就读表并写入redis
            $resp = DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("system-manages/getUserSheetByUserName", ["userName" => $userName, "status" => "启用"])));
            if (!empty($resp)){
                if ($resp['status'] == '启用'){
                    $userInfo = [
                        "userName" => $resp['userName'],
                        "cName" => $resp['cName'],
                        "verticalName" => $resp['verticalName'],
                        "companySequenceId" => $resp['companySequenceId'],
                        "verticalSequenceId" => $resp['verticalSequenceId'],
                        "dingUserId" => $resp['dingUserId'],
                    ];
                    $redisService->hSet(REDIS_USER_NAME_KEY, $userName, json_encode($userInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 12);
                }
            }

        } else {
            $userInfo = json_decode($get, true);
        }
        return $userInfo;
    }

    /**
     * 根据垂直Id 获取 所有的用户信息，并且初始化写入用户redis缓存
     * @param string $companySequenceId 垂直Id
     * @return CurlService|RequestUtils
     */
    public function getEmployeeByCompanySequenceId(string $companySequenceId)
    {
        $resp = DataUtils::getResultData($this->curlService->s3009()->get("system-manages/getEmployee",["companySequenceId" => $companySequenceId]));
        $redisService = new RedisService();
        foreach ($resp as $info){
            if ($info['status'] == '启用'){
                $userInfo = [
                    "userName" => $info['userName'],
                    "cName" => $info['cName'],
                    "verticalName" => $info['verticalName'],
                    "companySequenceId" => $info['companySequenceId'],
                    "verticalSequenceId" => $info['verticalSequenceId'],
                    "dingUserId" => $info['dingUserId'],
                    "mobilePhone" => $info['mobilePhone'],
                ];
                $redisService->hSet(REDIS_USER_NAME_KEY, $info['userName'], json_encode($userInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 72);
            }
        }
        return $this;
    }

    public function returnEmployeeByCompanySequenceId()
    {

        $redisService = new RedisService();
        $get = $redisService->hGetAll(REDIS_USER_NAME_KEY);

        $return = [];
        if ($get){
            foreach ($get as $userName => $json){
                $info = json_decode($json,true);
                $return[$userName] = $info;
            }
        }

        return $return;
    }

    // ===================================== system-manages 接口 end =====================================


    public function getProductSkuList(array $skuIdList)
    {
        $skuIdListInfoArray = [];
        if (!is_array($skuIdList)){
            return [];
        }
        foreach (array_chunk($skuIdList, 150) as $chunk) {
            $skuIdListInfoArray = array_merge($skuIdListInfoArray, DataUtils::getPageList($this->curlService->s3015()->get("product-skus/queryPage", [
                "productId" => implode(",", $chunk),
                "limit" => count($chunk)
            ])));
        }
        return $skuIdListInfoArray;
    }

    public function updateProductSku($info)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("product-skus/{$info['_id']}",$info));
    }
    public function updateProductSkuApi($info)
    {
        $info['userName'] = "pa-temp-sys";
        $info['action'] = "运维修改资料";
        return DataUtils::getResultData($this->curlService->s3015()->post("product-skus/updateProductSku?_id={$info['_id']}",$info));
    }

    public function deleteProductSku($id)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("product-skus/{$id}"));
    }

    public function createProductSku($info): string
    {
        return DataUtils::getCreateReturnId($this->curlService->s3015()->post("product-skus",$info));
    }
    //

    public function getProductBaseInfoList(array $skuIdList)
    {
        $skuIdListInfoArray = [];
        if (!is_array($skuIdList)){
            return [];
        }
        foreach (array_chunk($skuIdList, 150) as $chunk) {
            $skuIdListInfoArray = array_merge($skuIdListInfoArray, DataUtils::getPageList($this->curlService->s3015()->get("product_base_infos/queryPage", [
                "productId_in" => implode(",", $chunk),
                "limit" => count($chunk)
            ])));
        }
        return $skuIdListInfoArray;
    }
    public function updateProductBaseInfo($info)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("product_base_infos/{$info['_id']}",$info));
    }

    public function deleteProductBaseInfo($id)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("product_base_infos/{$id}"));
    }

    public function createProductBaseInfo($info): string
    {
        return DataUtils::getCreateReturnId($this->curlService->s3015()->post("product_base_infos",$info));
    }
    ///

    /**
     * version2 末级分类Id获取 一级分类，分类全路径id，分类全路径
     * @param int $categoryId 末级分类Id
     * @return array
     */
    public function getCategoryIdInfoV1(int $categoryId)
    {
        return DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("development-directions/getAllCategory",["id"=>$categoryId])));
    }

    /**
     * version2 末级分类Id获取 一级分类，分类全路径id，分类全路径
     * @param int $categoryId 末级分类Id
     * @return array
     */
    public function getCategoryIdInfoV2(int $categoryId)
    {
        $redisService = new RedisService();
        $get = $redisService->hGet(REDIS_CATEGORY_ID_KEY, $categoryId);
        $categoryIdInfo = [];
        if (empty($get)) {
            $categoryInfo = DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("development-directions/getSupplierCategoryPathByCategoryIdList", ["id" => $categoryId])));
            $cnCategoryNameFirst = "";
            $categoryIds = "";
            $cnCategoryFullPath = "";
            if (DataUtils::checkArrFilesIsExist($categoryInfo, 'supplierCategories')) {
                usort($categoryInfo['supplierCategories'], function ($a, $b) {
                    return $a['categoryLevel'] - $b['categoryLevel'];
                });
                $cnCategoryNameFirst = $categoryInfo['supplierCategories'][0]['categoryNameCn'];
                $categoryIds = implode(",", array_column($categoryInfo['supplierCategories'], 'id'));
                $cnCategoryFullPath = implode(" -> ", array_column($categoryInfo['supplierCategories'], 'categoryNameCn'));
            }
            $categoryIdInfo = compact(['categoryId','cnCategoryNameFirst', 'categoryIds', 'cnCategoryFullPath']);
            $redisService->hSet(REDIS_CATEGORY_ID_KEY, $categoryId, json_encode($categoryIdInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 1);
        }else{
            $categoryIdInfo = json_decode($get, true);
        }

        return $categoryIdInfo;
    }


    //

    public function getPaCeMaterialByCeBillNoList($ceBillNoList)
    {
        $list = [];
        foreach (array_chunk($ceBillNoList, 100) as $chunk) {
            $list = array_merge($list, DataUtils::getPageDocList($this->curlService->s3044()->get("pa_ce_materials/queryPage", ["ceBillNo_in" => implode(",", $chunk), "limit" => count($chunk)])));
        }
        return $list;
    }

    public function updatePaCeMaterial($id, $params)
    {
        return DataUtils::getResultData($this->curlService->s3044()->put("pa_ce_materials/{$id}", $params));
    }

    public function getTranslationMain($params)
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("translation_managements/queryPage",$params));
    }

    public function updateTranslationMain($info)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("translation_managements/{$info['_id']}",$info));
    }

    public function getTranslationSku($params)
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("translation_management_skus/queryPage",$params));
    }

    public function updateTranslationSku($info)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("translation_management_skus/{$info['_id']}",$info));
    }

    public function getAllPaProductBrandBillNoRules()
    {
        return DataUtils::getPageList($this->curlService->s3044()->get("pa_product_brand_bilino_rules/queryPage",["limit"=>10000,"status"=>10]));
    }

    public function updatePaProductBrandBillNoRules($info)
    {
        return DataUtils::getResultData($this->curlService->s3044()->get("pa_product_brand_bilino_rules/{$info['_id']}",$info));
    }

    public function getAllPaProductBrandScoreBase()
    {
        return DataUtils::getPageList($this->curlService->s3044()->get("pa_product_brand_score_bases/queryPage",["limit"=>10000,"status"=>10]));
    }

    public function updatePaProductBrandScoreBase($info)
    {
        return DataUtils::getResultData($this->curlService->s3044()->get("pa_product_brand_score_bases/{$info['_id']}",$info));
    }

    public function getPaSkuAttributePageList($params)
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_sku_attributes/queryPage", $params));
    }
    public function updatePaSkuAttribute($info)
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_sku_attributes/{$info['_id']}",$info));
    }

    public function deletePaSkuAttribute($id)
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_sku_attributes/{$id}"));
    }

    /**
     * 获取工厂信息
     * @param $supplierId
     * @param $factoryFullName
     * @return array|mixed
     */
    public function getFactoryInfoByFactoryFullName($supplierId,$factoryFullName){
         $res = DataUtils::getResultData($this->curlService->s3009()->post("consignment-bills/factoryInfo",[
            'conditionsJsonEncode' => json_encode(["supplierId"=>$supplierId,"factoryFullName"=>$factoryFullName,"factoryStatus"=>"active"]),
            'orderBy' => "",
            'pageNumber' => 1,
            'entriesPerPage' => 10,
        ]));
        $factoryInfo = [];
        if ($res['factoryInfoResponse'] && isset($res['factoryInfoResponse']['factoryInfos']) && count($res['factoryInfoResponse']['factoryInfos']) > 0){
            $factoryInfo = $res['factoryInfoResponse']['factoryInfos'][0];
        }
        return $factoryInfo;
    }

    /**
     * 生成批次号
     * @param $type
     * @return array
     */
    public function getSequenceId($type){
        return DataUtils::getResultData($this->curlService->s3009()->get("commons/getSequenceId",[
            "userName" => "zhouangang",
            "date" => date("Ymd",time()),
            "type" => $type,
        ]));
    }

    /**
     * 钉钉通知
     * @param string $title
     * @param array $msg
     * @return $this
     */
    public function dingTalk($title = "",$msg = array()){
        $ali = $this->curlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());

        $msgArray = [
            [
                "key" => "通知日期",
                "value" => "{$datetime}"
            ]
        ];
        if (!empty($msg)){
            $msgArray = array_merge($msgArray,$msg);
        }
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => $title,
            'msg' => $msgArray
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }
}

//$request = new RequestUtils("test");

//$request->returnEmployeeByCompanySequenceId();
//if (DataUtils::checkArrFilesIsExist($data, "attribute")) {
//    $info = [
//        [
//            "channel" => "local",
//            "label" => "来货brand类型",
//            "value" => "uxcell",
//        ],
//        [
//            "channel" => "local",
//            "label" => "Business Type",
//            "value" => "产品目录",
//        ],
//    ];
//    ProductUtils::editProductAttributeByArr($data['attribute'], $info);
//
//
//}



