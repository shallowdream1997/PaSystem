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
        $ali = $this->curlService->phpali();

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
        $res = $ali->post("dingding/sendOaNotice",$postData);
        echo json_encode($res,JSON_UNESCAPED_UNICODE);
        return $this;
    }





    public function callAliCloudSls($query)
    {
        $url = 'https://sls.console.aliyun.com/console/logstoreindex/getLogs.json';

        // 请求参数
        $params = [
            'LogStoreName' => 'api_nodejs_access_log_new',
            'ProjectName' => 'aliyun-hn1-all-log',
            'from' => '1735660800',
            'query' => $query,
            'to' => '1753864059',
            'Page' => '1',
            'Size' => '50'
        ];

        // 请求头
        $headers = [
            'accept: application/json',
            'accept-language: zh-CN,zh;q=0.9,sq;q=0.8',
            'b3: f4072aec94ad53dd7229b6d375de51d5-1b89b783630afd49-1',
            'bx-v: 2.5.31',
            'content-type: application/x-www-form-urlencoded',
            'eagleeye-pappname: gaddp9ap8q@fcf4dd25082bab4',
            'eagleeye-sessionid: 2hmq7dLzp9kfp72IFoC6u7zsvbs3',
            'eagleeye-traceid: 8b2258b3175386405949710582bab4',
            'origin: https://sls.console.aliyun.com',
            'priority: u=1, i',
            'referer: https://sls.console.aliyun.com/lognext/project/aliyun-hn1-all-log/logsearch/api_nodejs_access_log_new?slsRegion=cn-shenzhen',
            'sec-ch-ua: "Not)A;Brand";v="8", "Chromium";v="138", "Google Chrome";v="138"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Linux"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'traceparent: 00-f4072aec94ad53dd7229b6d375de51d5-1b89b783630afd49-01',
            'uber-trace-id: f4072aec94ad53dd7229b6d375de51d5:1b89b783630afd49:0:1',
            'x-csrf-token: a5f524a0',
            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36'
        ];

        // Cookie
        $cookie = 'cna=NaHqIBsy20oCAQ6Rk5/H14em; aliyun_lang=zh; aliyun_site=CN; currentRegionId=cn-shenzhen; partitioned_cookie_flag=doubleRemove; login_aliyunid_csrf=_csrf_tk_1690853846731161; login_aliyunid="16363354792682515 @ 1196618798442729"; login_aliyunid_ticket=3RctYK12wchS7wBJzHKJeoTE.1118adP66EaBV2wXHmLGo5HmBbeCddso2W64wecaE8uJKdsUxSPnmucnLggwNNvLc5NvT7PHDSDRzdLFGkecKPD3WByMaHMiLAL19jCEmxiDwawXtT5FsjCyWEvkuYXeHMt6k9eeCSUsFd5RZfeuirayMKw9y7MsyauQw8PCVLSKdcEsQNg.1QDta7oYmBf6NwDefpx5LayWgyVhf4HcVZB95cJmzFHqf4CpHbZKuuAcYhhmN1ES3; login_aliyunid_sc=3R5H3e3HY2c8gwLZuY5GmS7K.1113ekvjnsx5gZUThVshcSQ1yqap8hMSxkWnAPQ3CRQYXZ51rBYZWnT9sminrntEJzYT6g.2mWNaj2b9G8jZia5ELgwmREr1DE4QXi3E8WwFRagQzQnAmo2VrwAS9z7F3MYzgrh8W; login_aliyunid_pk=1196618798442729; login_current_pk=292058973591134045; tfstk=g2KmydxLXy21jOrboAjjlEvxqEg82is6bCEO6GCZz_57DGCvQ1AMZd1AXx9NjAAvnrQ9QjOMj_RPMIpthRWNadWOMFHfsO5ysGpYktLMqIOR3ZuX6AxkNQOG5x1O_1A9QEH-vDpXhGsNjXnKv7rpZsOGQs74zAWOKtkRuPssYuSZ9Xn8y87jMGR9EuNAaL55IorVgCWP49X1bt7NbYfPI9aagCSZEYXABOyV_ZPPz9CP_GSw_gPPNOjN3CSZEL55QXV8u6Kw83lMb3V6TI4QZObcTK5uxL9f3mjqALEw4nXfohyFilWJqtbcTKxntf5dKexlJEkqu9jGNKtRomEffLLHsFREwrC2-9AcrUhgg1tkUn8wFSDPxLYJJZYoglfJD__B0Mm_2GvJqwLGj0w5geTJg3S_pzsvJpK18no0O_QCIQbpoXqDTglLzy7bsl6rB3z_5ZW5E6LT0LPK-076hYDuJd_VF91KEY4_UZW5EQHoEyLduT6Ey; isg=BEREZjBp8L4NCkQeC74F4ljVFcs2XWjHxRtnHl7zwIqvibdTsGQ0V-1vySFRkaAf';

        // 初始化cURL
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 执行请求
        $response = curl_exec($ch);

        // 检查是否有错误
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // 关闭cURL资源
        curl_close($ch);
        //头信息
        $body = json_decode($response,true);
        // 返回响应
        return $body;
    }

    public function callAliCloudSls2($skuId)
    {
        $url = 'https://sls.console.aliyun.com/console/logs/getLogs.json';

        $headers = [
            'accept: application/json',
            'accept-language: zh-CN,zh;q=0.9,sq;q=0.8',
            'b3: dd648db6ae4bbc9386fdd20aa677ceb8-81eb8bcb27a884c4-1',
            'bx-v: 2.5.31',
            'content-type: application/x-www-form-urlencoded',
            'eagleeye-pappname: gaddp9ap8q@fcf4dd25082bab4',
            'eagleeye-sessionid: qCmjOe5ksO9cXnzj28RszI60yzk8',
            'eagleeye-traceid: 8823dac0175620201024011852bab4',
            'origin: https://sls.console.aliyun.com',
            'priority: u=1, i',
            'referer: https://sls.console.aliyun.com/lognext/project/aliyun-hn1-all-log/logsearch/api_nodejs_access_log_new?encode=base64&queryString=UE1PMjAyNTA4MTUwMDAzMyBhbmQgUmVxdWVzdE1ldGhvZDogUFVUIA%3D%3D&filterInfo=eyJmamNvZGUiOiIoKSIsImZxIjoiIn0%3D&queryTimeType=99&startTime=1735660800&endTime=1755573379&slsRegion=cn-shenzhen',
            'sec-ch-ua: "Not;A=Brand";v="99", "Google Chrome";v="139", "Chromium";v="139"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Linux"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'traceparent: 00-dd648db6ae4bbc9386fdd20aa677ceb8-81eb8bcb27a884c4-01',
            'uber-trace-id: dd648db6ae4bbc9386fdd20aa677ceb8:81eb8bcb27a884c4:0:1',
            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'x-csrf-token: 60969255'
        ];

        $cookies = 'cna=NaHqIBsy20oCAQ6Rk5/H14em; aliyun_site=CN; currentRegionId=cn-shenzhen; aliyun_lang=zh; aliyun_country=CN; partitioned_cookie_flag=doubleRemove; login_aliyunid_csrf=_csrf_tk_1591156189759269; login_aliyunid="16363354792682515 @ 1196618798442729"; login_aliyunid_ticket=3RctYK12wchS7wBJzHKJeoTE.1118RhrCpYNsxuC1jA5mCp9fpUsGyPTa6KCx8gcZx57sbo8BAWigHJpBfWE2mz3pG8DFUYisrp1CTRFzHi7ZeHKekjAVTWMBBMSPT5iHARoMJDLugHePWHWnAHba3J5tLc1bs18Cg6RnAF9mgYCzLsJ2akSDArcn4ZwZ8DTZSV9pRxaDAbt.2mWNaj2hkxo1R9sNHmqU87DtogKHx1c8JuH92EWhPoMeEE8MBezPXwFjEfz2dearG5; login_aliyunid_sc=3R5H3e3HY2c8gwLZuY5GmS7K.1113fs9jHcTwCZEPVPgLPybMTwyy3yGe49rMuhUz6hYSbK8xTPGdPhhi4k85GfE2Mg3Dr9.2mWNaj2rY8QYqPma4ZVq7MNGgPAz5s3L7x3WkBD74HVH346tFF1XQtBstvaisU6LFZ; login_aliyunid_pk=1196618798442729; login_current_pk=292058973591134045; tfstk=glcszH9hdzqejKM8CS8FNeVZhCPXxeJea2bjr4EV_tFtAonno-ha_qrxcDm7HFmqQICxAklabVP9lmi-YAS4053YHDnZQfr46STjbV6a3VhqhWEEcSPqWmofSXi5g1-MIqCbgSKy4QRrSFV0M3uB_nYfSrz1WtI4HM3Lgr_D79FnSVVc-wSvUv3GlHRRlsFxDWELrrNYWlFv9kE3ltCtDledvzauMonxDy3LlzaAHSEYJWE3kSExDSLQ9k4bMonYMegKTTjQudax5UZ7yoYvLLp4XsCxOPwpLKqdGJ4VaIrrWk1AMl1LQudzAsCxOP3jG2GP9IE-EDHKBY_OT-P3ADzsmQfg6-Mx_Wh9vIFxpcl7ljYRhowjsWZIVQC0-Ans9-GeoQgU1riuPmOln22YPcVt6HS_GWau3xiwxsEZF2nQUXSFa7HoAjw8wgofauZBGt_QEsaQ4eTCntj89HTYkIAX8RU3WQ8BRM60By4QReTCnhwT-PKDResad; isg=BPr6zoHCRtikhcp8MQhT4FLLSykcq36FDBxivwTMRg6Y9oExBzwClM5BR4Mr5_Yd';

        $postData = [
            'ProjectName' => 'aliyun-hn1-all-log',
            'LogStoreName' => 'api_nodejs_access_log_new',
            'from' => '1753977600',
            'query' => "sgu-sku-scu-maps and RequestMethod: POST and {$skuId} not getSguSkuGroupData  | with_pack_meta",
            'to' => '1756202010',
            'Page' => '1',
            'Size' => '100',
            'Reverse' => 'true',
            'pSql' => 'false',
            'fullComplete' => 'false',
            'schemaFree' => 'false',
            'needHighlight' => 'true'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // 执行请求
        $response = curl_exec($ch);

        // 检查是否有错误
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // 关闭cURL资源
        curl_close($ch);
        //头信息
        $body = json_decode($response,true);
        // 返回响应
        return $body;
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



