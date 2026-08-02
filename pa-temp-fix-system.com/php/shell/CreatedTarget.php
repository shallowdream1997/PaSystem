<?php
//require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
//require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
//require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
//require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");
//
///**
// * 仅限于用创建target广告数据
// * Class SyncCurlController
// */
//class CreatedTarget
//{
//    /**
//     * @var CurlService
//     */
//    public CurlService $curl;
//    private MyLogger $log;
//    private $module = "platform-wms-application";
//
//    private RedisService $redis;
//    public function __construct()
//    {
//        $this->log = new MyLogger("sp/sp");
//
//        $curlService = new CurlService();
//        $this->curl = $curlService;
//        $this->redis = new RedisService();
//    }
//
//    /**
//     * 日志记录
//     * @param string $message 日志内容
//     */
//    private function log($message = "")
//    {
//        $this->log->log2($message);
//    }
//
//    public function commonDelete($port, $model, $id,$env = "test")
//    {
//        $curlService = new CurlService();
//        $resp = DataUtils::getResultData($curlService->$env()->$port()->delete($model, $id));
//        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
//    }
//
//    public function commonFindById($port, $model, $id, $env = 'test')
//    {
//        $curlService = new CurlService();
//        $resp = $curlService->$env()->$port()->get("{$model}/{$id}");
//        $data = isset($resp['result']) ? $resp['result'] : null;
//        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
//        return $data;
//    }
//
//    public function commonFindOneByParams($port, $model, $params, $env = 'test')
//    {
//        if (!isset($params['limit'])) {
//            $params['limit'] = 1;
//        }
//        $curlService = new CurlService();
//        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
//        $data = [];
//        if ($port == 's3044') {
//            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
//        } else {
//            $data = DataUtils::getPageListInFirstData($resp);
//        }
//        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
//        return $data;
//    }
//
//    public function commonFindByParams($port, $model, $params, $env = 'test')
//    {
//        if (!isset($params['limit'])) {
//            $params['limit'] = 999;
//        }
//        $curlService = new CurlService();
//        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
//        $data = [];
//        if ($port == 's3044') {
//            $data = DataUtils::getPageDocList($resp);
//        } else {
//            $data = DataUtils::getPageList($resp);
//        }
//        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
//        return $data;
//    }
//
//    public function commonCreate($port, $model, $params)
//    {
//        $curlService = new CurlService();
//        $resp = DataUtils::getResultData($curlService->test()->$port()->post("{$model}", $params));
//        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
//        return $resp;
//    }
//    public function commonUpdate($port, $model, $params,$env = "test")
//    {
//        $curlService = new CurlService();
//        $resp = DataUtils::getResultData($curlService->$env()->$port()->put("{$model}/{$params['_id']}", $params));
//        $this->log("更新{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
//        return $resp;
//    }
//
//
//    public function createTarget(){
//        $env = "pro";
//
//
//        $curlService = new CurlService();
////        $resp = $curlService->$env()->s3023()->get("amazon_sp_campaigns/queryPage", [
////            "batch" => ""
////        ]);
//        $channel = "amazon_ca";
//
//        $fileContent = (new ExcelUtils())->getXlsxData("../export/sp/sp_create_{$channel}_target.xlsx");
//
//        if (sizeof($fileContent) > 0) {
//            $skuChannelSellerIdAsin = [];
//            foreach ($fileContent as $info){
//                $skuChannelSellerIdAsin[$info['sellerid']][] = [
//                    "sku_id" => $info['sku_id'],
//                    "asin" => $info['Asin'],
//                ];
//            }
//
//            foreach ($skuChannelSellerIdAsin as $sellerId => $array){
//
//                $skuIdList = array_column($array,"sku_id");
//
//                foreach (array_chunk($skuIdList,200) as $chunk){
//                    $rlist1 = DataUtils::getPageList($curlService->$env()->s3015()->get("pid-scu-maps/queryPage", [
//                        "productId" => implode(",", $chunk),
//                        "channel" => $channel,
//                        "scuIdType" => "fba",
//                        "scuIdStyle" => "systemWithSelling",
//                        "columns" => "productId,infoId,scuId",
//                        "limit" => 200,
//                        "page" => 1
//                    ]));
//                    if (count($rlist1)){
//
//                    }
//
//                }
//
//            }
//
//
//        }
//
//
//    }
//
//
//    /**
//     *  构建sp广告规则名称
//     */
//    private function buildSpName($regexString,$params){
//        $regexNameArray = array();
//        preg_match_all("/{(.+?)}/s",$regexString,$regexNameArray);
//        if (count($regexNameArray[0]) > 0) {
//            $regexStringIsEmpty = false;
//            for ($i = 0; $i < count($regexNameArray[0]); $i++) {
//                $regexName = $regexNameArray[0][$i];
//                $replace = $this->_regexToName($regexName, $params);
//                $regexString = str_replace($regexName, $replace, $regexString);
//                if (($regexName == "{MROSALESMANINDEX}" || $regexName == "{MROSELLERNAME}") && empty($replace)) {
//                    $regexStringIsEmpty = true;
//                }
//                if (($regexName == "{PLGSALESMANINDEX}" || $regexName == "{PLGSELLERNAME}") && empty($replace)) {
//                    $regexStringIsEmpty = true;
//                }
//            }
//            if ($regexStringIsEmpty) {
//                $regexString = "";
//            }
//            //TODO 分组序号-自增1，2，3，4...是根据campaign名称读amazon_sp_campaign表和adgroup表来判断的，所以分组序号的逻辑放最后写
//            $isHasGroupIndex = preg_match_all("/{GROUPINDEX}/s",$regexString);
//            if ($isHasGroupIndex > 0 && isset($params['sellerId']) && isset($params['targetingType'])){
//                $regexString = $this->buildCampaignName(
//                    $regexString,
//                    $params['sellerId'],
//                    $params['targetingType'],
//                    isset($params['adGroupName'])?$params['adGroupName']:null
//                );
//            }
//        }
//        return $regexString;
//    }
//
//    /**
//     * 获取动态元素值的实际名称
//     * @param $dynamic 动态元素值
//     * @param $params 获取动态元素值的前提参数:我需要获取分组名称，必须要skuId获取其分组名称
//     * @return int|string
//     */
//    private function _regexToName($dynamic, $params)
//    {
//        $name = "";
//        switch ($dynamic) {
//            case "{GROUPNAME}"://分组名称
//                $name = $this->buildGroupName($params);
//                break;
//            case "{GROUPINDEX}"://分组序号,这个我觉得还是放后面判断自增的时候替换
//                $name = "{GROUPINDEX}";
//                break;
//            case "{SALESMAN}"://销售中文名称
//                $name = $this->buildSalesMan($params);
//                break;
//            case "{SALESMANINDEX}"://销售虚拟编号
//                $name = $this->buildSalesManVirtualNumber($params);
//                break;
//            case "{SKUID}"://skuId
//                $name = $this->buildSkuId($params);
//                break;
//            case "{NONFBA}"://nonFba
//                $name = $this->buildProductNonFba($params);
//                break;
//            case "{CE}"://ce单
//                $name = $this->buildCeBillNo($params);
//                break;
//            case "{SGU}"://sgu号
//                $name = $this->buildSgu($params);
//                break;
//            case "{MROSALESMANINDEX}"://mro销售虚拟编号
//                $name = $this->buildMroSalesManIndex($params, "sub_account_name");
//                break;
//            case "{PLGSALESMANINDEX}"://plg销售虚拟编号
//                $name = $this->buildMroSalesManIndex($params, "sub_account_name");
//                break;
//            case "{PRODUCTLINE}"://产品线
//                $name = $this->buildProductLine($params);
//                break;
//            case "{MROSELLERNAME}"://mro帐号简称
//                $name = $this->buildMroSalesManIndex($params, "sellerId_short_name");
//                break;
//            case "{PLGSELLERNAME}"://plg帐号简称
//                $name = $this->buildMroSalesManIndex($params, "sellerId_short_name");
//                break;
//            default:
//                break;
//        }
//        return $name;
//    }
//    //分组序号写入，完善campaign最后的命名
//    private function buildCampaignName($tempCampaignName,$sellerId,$targetingType,$adGroupName = null){
//        //step 13.当存在多个adgroup时，同分类分组每40个adgroup组合成一个campaign，campaign的命名规则根据账号决定
//        $adGroupCount = 40;
//        $num = 1;
//        $campaign = "";
//        while ($adGroupCount >= 40) {
//            //命名
//            $campaign = str_replace("{GROUPINDEX}",$num,$tempCampaignName);
//            //先判断是否已经存在campaign，不存在则直接返回
//            $campaignResult = $this->getCampaignName($campaign, $sellerId, $targetingType);
//            $this->log("--查询campaignName:".json_encode($campaignResult,JSON_UNESCAPED_UNICODE));
//            if (count($campaignResult) == 0) {
//                break;
//            }
//            //修复bug，原则上来说一个campaignName 只有一个campaignId,一个campaignId是必须是非归档的
//            $campaignId = "";
//            foreach ($campaignResult as $item){
//                if(isset($item['campaignId']) && !empty($item['campaignId'])){
//                    if ($item['state'] != "archived"){
//                        $campaignId = $item['campaignId'];
//                    }
//                }
//            }
//            //$campaignList = end($campaignResult);
//            //$campaignId = $campaignList['campaignId'];
//            if ($campaignId == ""){
//                //如果没有campaignId，则使用这个campaign
//                break;
//            }
//            //PA要判断adgroup 是否存在,存在campaign则判断是否存在adGroup，不存在或数量不到40个则直接返回
//            if ($companyId == 'CR201706060001' && $adGroupName){
//                //特殊判断，adgroup名称需要看有没有存在某个campaign广告下面
//                $adgroupResult = $this->_amazonSpAdGroupIsExist($campaignId, $adGroupName);
//                if (count($adgroupResult) > 0){
//                    //如果有这个adgroup在campaign下，则就用这个campaign
//                    break;
//                }
//            }
//
//            $adGroupCount = $this->_amazonAdGroupCount($campaignId);
//            $this->loggerAndDump("已存在{$adGroupCount}个adGroup");
//            $num++;
//        }
//        return $campaign;
//    }
//
//    public function getCampaignName($campaignName,$sellerId,$targetType,$env = "pro"){
//        $resp = (new CurlService())->$env()->s3023()->get("amazon_sp_campaigns/queryPage", [
//            "campaignName" => $campaignName,
//            "channel" => ($sellerId == 'amazon') ? 'amazon_us' : $sellerId,
//            "targetingType" => $targetType
//        ]);
//        if ($resp){
//
//        }
//    }
//}
//
//$curlController = new CreatedTarget();
//$curlController->createTarget();