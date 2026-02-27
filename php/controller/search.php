<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/EnvironmentConfig.php';
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';

/**
 * 查询接口
 * Class search
 */
class search
{

    public $logger;
    /**
     * @var CurlService
     */
    public $envService;

    public function __construct()
    {
        $this->logger = new MyLogger("option/searchLog");
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
        $list = [];
        if (isset($params['title']) && $params['title']) {
            $list = DataUtils::getPageList($curlService->s3015()->get("translation_managements/queryPage", [
                "limit" => 100,
                "page" => 1,
                "title_in" => $params['title'],
            ]));
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }


    /**
     * CE资料呈现
     * @param $params
     * @return array
     */
    public function fixCeMaterials($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['title']) && $params['title']) {

            $res = $curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 100,
                "page" => 1,
                "ceBillNo_in" => $params['title'],
            ]);
            $list = DataUtils::getPageDocList($res);

        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    /**
     * 上架前海外仓移库申请
     * @param $params
     * @return array
     */
    public function paFbaChannelSellerConfig($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $list = [];
        if (isset($params['action']) && $params['action']){
            switch ($params['action']){
                case "searchAllConfig":
                    $list = $this->getPaFbaChannelSellerConfig($curlService);
                    break;
                case "searchChannelStock":
                    $list = $this->paFbaChannelStocks($curlService,$params);
                    break;
            }

        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    private function getPaFbaChannelSellerConfig($curlService)
    {
        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "pa_fba_channel_seller_config",
            "limit" => 1
        ]));
        $list = [];
        foreach ($info['optionVal']['amazon'] as $channel => $stocks){
            $list[] = [
                "channel" => $channel,
                "nowStocks" => $stocks
            ];
        }
        return $list;
    }

    public function paFbaChannelStocks($curlService,$params)
    {
        $stocks = [];
        if (isset($params['channel']) && $params['channel']){
            $list = DataUtils::getPageList($curlService->s3015()->get("seller-channel-platforms/queryPage", [
                "channel" => $params['channel'],
                "company" => "PA",
                "limit" => 1000,
                "columns" => "channel,listingType,company"
            ]));
            foreach ($list as $item){
                if ($item['listingType']){
                    foreach ($item['listingType'] as $listingType){
                        if ($listingType['listingType'] == "fba"){
                            $stocks[] = [
                                "id" => $listingType['mainStock'],
                                "name" => $listingType['mainStock']
                            ];
                            break;
                        }
                    }
                }
            }
        }
        return $stocks;
    }

    public function paSampleSku($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $skuIdList = $params['skuIdList'];

            $curlService->gateway();
            $curlService->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
//                "category" => "dataTeam",
                "pageSize" => 500,
                "pageNum" => 1,
            ]));
            $list = $resp['list'];
        }
        return [
            "env" => $env,
            "data" => $list
        ];

    }



    public function paFixProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $skuIdList = $params['skuIdList'];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $createProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryUserBySkuId", [
                    "skuId" => implode(",",$chunk)
                ]));
                if ($createProductMainResp){
                    $list = array_merge($list,$createProductMainResp);
                }
            }
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }


    public function uploadOss($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $dateTime = date("Y-m-d H:i:s",time());
        if (isset($params['searchData']) && $params['searchData']){
            $redisService = new RedisService();
            $dbData = $redisService->hGetAll(REDIS_OSS_FILE_NAME_KEY . "_{$env}");
            $dbDataList = [];
            if ($dbData){
                foreach ($dbData as $key => $keyInfo){
                    $dbDataList[] = json_decode($keyInfo,true);
                }
            }

            if (isset($params['isExport']) && $params['isExport']){
                $excelUtils = new ExcelUtils();
                $downloadOssLink = "上传oss文件_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["文件原名", "Oss Key名", "OSS链接","上传日期"],$dbDataList,$downloadOssLink);

            }
           // /export/uploads/default/上传oss文件_20241226082547.xlsx
            return [
                "env" => $env,
                "uploadSuccess" => true,
                "messages" => "扫描成功",
                "linkList" => $dbDataList,
                "downloadOssPath" => $downloadOssPath,
                "downloadOssPathUrl" => "/export/uploads/default/" . $downloadOssLink,
            ];
        }

        return [
            "env" => $env,
            "data" => []
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
        $list = [];
        $dbDataList = $redisService->hGetAll(REDIS_USERNAME_IP_KEY . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                // 包含所有用户记录：新格式(用户名_IP)和旧格式(IP)
                if ((strpos($key, '_') !== false && strpos($key, 'ip_') !== 0) || 
                    (strpos($key, '_') === false && filter_var($key, FILTER_VALIDATE_IP))) {
                    $record = json_decode($keyInfo, true);
                    if ($record) {
                        // 兼容旧数据格式，补充缺失字段
                        if (!isset($record['first_visit'])) {
                            $record['first_visit'] = date('Y-m-d H:i:s');
                        }
                        if (!isset($record['last_visit'])) {
                            $record['last_visit'] = $record['first_visit'];
                        }
                        if (!isset($record['visit_count'])) {
                            $record['visit_count'] = 1;
                        }
                        // 如果是旧格式数据，key就是IP地址
                        if (!isset($record['name']) && strpos($key, '_') === false) {
                            $record['name'] = '未知用户';
                        }
                        if (!isset($record['ip'])) {
                            $record['ip'] = $key;
                        }
                        $list[] = $record;
                    }
                }
            }
        }

        $response = [
            "success" => true,
            "env" => $env,
            "allRecords" => $list,
            "totalVisitors" => count($list)
        ];

        // 获取当前用户信息
        if (isset($params['getUserInfo']) && $params['getUserInfo']) {
            $visitorIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userKey = REDIS_USERNAME_IP_KEY . "_{$env}";
            $userData = $redisService->hGet($userKey, $visitorIp);
            
            if ($userData) {
                $userInfo = json_decode($userData, true);
                if ($userInfo) {
                    $response['current_user'] = [
                        'name' => $userInfo['name'] ?? '未知用户',
                        'ip' => $userInfo['ip'] ?? $visitorIp,
                        'last_visit' => $userInfo['last_visit'] ?? date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        // 处理访问记录请求
        if (isset($params['recordVisit']) && $params['recordVisit']) {
            $visitorIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $params['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $currentTime = date('Y-m-d H:i:s');
            $isNewRecord = false;

            // 更新用户的访问统计（使用hash结构存储）
            $userStatsKey = REDIS_USERNAME_IP_KEY . "_{$env}_stats";
            $userData = $redisService->hGet($userStatsKey, $visitorIp);
            
            if ($userData) {
                $userInfo = json_decode($userData, true);
            } else {
                $userInfo = [
                    'ip' => $visitorIp,
                    'first_visit' => $currentTime,
                    'visit_count' => 0,
                    'last_visit' => $currentTime,
                    'last_user_agent' => substr($userAgent, 0, 100)
                ];
                $isNewRecord = true;
            }

            // 更新访问次数和最后访问时间
            $userInfo['visit_count'] = ($userInfo['visit_count'] ?? 0) + 1;
            $userInfo['last_visit'] = $currentTime;
            $userInfo['last_user_agent'] = substr($userAgent, 0, 100);

            // 保存用户统计数据
            $redisService->hSet($userStatsKey, $visitorIp, json_encode($userInfo, JSON_UNESCAPED_UNICODE));

            // 获取最近访问记录（从统计数据中提取）
            $recentVisits = $this->getRecentVisits($redisService, $userStatsKey);
            
            // 获取本页访问次数
            $thisPageVisits = $userInfo['visit_count'];

            $response['recent_visits'] = $recentVisits;
            $response['this_page_visits'] = $thisPageVisits;
            $response['new_visit_recorded'] = $isNewRecord;
        }

        return $response;
    }

    /**
     * 获取最近访问记录
     */
    private function getRecentVisits($redisService, $userStatsKey) {
        $recentVisits = [];
        $allUsers = $redisService->hGetAll($userStatsKey);
        
        // 转换并排序
        $userList = [];
        foreach ($allUsers as $ip => $userData) {
            $userInfo = json_decode($userData, true);
            if ($userInfo) {
                $userList[] = [
                    'ip' => $ip,
                    'time' => $userInfo['last_visit'],
                    'userAgent' => $userInfo['last_user_agent'] ?? '',
                    'count' => $userInfo['visit_count'] ?? 1,
                    'isNew' => false  // 默认不是新记录
                ];
            }
        }
        
        // 按时间倒序排列，取最近20条
        usort($userList, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($userList, 0, 20);
    }
    
    /**
     * 获取客户端真实IP地址
     * @param $params
     * @return array
     */
    public function getClientIp($params)
    {
        $ip = $this->getRealClientIp();
        
        return [
            "success" => true,
            "ip" => $ip,
            "message" => "IP地址获取成功"
        ];
    }
    
    /**
     * 获取真实的客户端IP地址
     * @return string
     */
    private function getRealClientIp()
    {
        // 检查各种可能的IP头信息
        $ipKeys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP', 
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // 处理逗号分隔的IP列表（代理情况）
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // 验证IP地址格式
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // 如果都没找到有效的公网IP，返回REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * 查询fcu产品线
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
                    } else {
                        $returnMsg[] = [
                            "messages" => "需要补充产品线",
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




    /**
     * 寄卖新QD单
     * @param $params
     * @return array
     */
    public function consignmentQD($params){
        $curlService = $this->envService;
        $env = $curlService->environment;



        return ["env" => $env, "data" => []];
    }

    /**
     * sku修补拍摄工单
     * @param $params
     * @return array
     */
    public function skuPhotoFix($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $preList = [];
        if (isset($params['skuList']) && !empty($params['skuList'])){
            $preList = (new ProductSkuController())->getSkuPhotoProgress($params['skuList'],$env);
        }
        return ["env" => $env, "data" => $preList];
    }


    public function fixCurrency($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $preList = [];
        if (isset($params['skuIdList']) && !empty($params['skuIdList']) &&
            isset($params['channels']) && !empty($params['channels'])){

            $skuIdList = $params['skuIdList'];
            $channels = $params['channels'];

            foreach (array_chunk($skuIdList,150) as $chunk){
                $skuSellerConfigList = DataUtils::getPageList( $curlService->s3015()->get("sku-seller-configs/queryPage",[
                    "skuId" => implode(",",$chunk),
                    "channel" => implode(",",$channels),
                    "limit" => 1000
                ]));
                $channelSkuMap = [];
                foreach ($skuSellerConfigList as $skuSellerConfigInfo){
                    $channelSkuMap[$skuSellerConfigInfo['skuId']][$skuSellerConfigInfo['channel']] = $skuSellerConfigInfo['currency'];
                }

                $list = DataUtils::getPageList( $curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => implode(",",$chunk),
                    "limit" => 150
                ]));
                if ($list && count($list) > 0){
                    foreach ($list as $productInfo){
                        $currencyCh = [];
                        if (isset($channelSkuMap[$productInfo['productId']])){
                            $currencyCh = $channelSkuMap[$productInfo['productId']];
                        }
                        $deleteMap = [];
                        foreach ($productInfo['attribute'] as $info){
                            if (in_array($info['label'],[
                                    'MSRPWithTax_currency',
                                    'MSRP_currency',
                                ]) && in_array($info['channel'],$channels)){

                                $realCurrency = "";
                                if (isset($currencyCh[$info['channel']])){
                                    $realCurrency = $currencyCh[$info['channel']];
                                }
                                $canDelete = false;
                                if ($realCurrency != $info['value']){
                                    $canDelete = true;
                                }

                                //双方币种不一致的情况下，需要删除
                                $key = $info['label'] . '|' . $info['channel'];
                                $deleteMap[$key] = true;

                                $preList[] = [
                                    "skuId" => $productInfo['productId'],
                                    "channel" => $info['channel'],
                                    "realCurrency" => $realCurrency ?? "暂无币种信息",
                                    "label" => $info['label'],
                                    "value" => $info['value'],
                                    "canDelete" => $canDelete,
                                ];
                            }
                        }

                        foreach ($channels as $channel){


                            $realCurrency = "";
                            if (isset($currencyCh[$info['channel']])){
                                $realCurrency = $currencyCh[$channel];
                            }

                            foreach ([
                                         'MSRPWithTax_currency',
                                         'MSRP_currency',
                                     ] as $label){

                                $key = $label . '|' . $channel;
                                if (!isset($deleteMap[$key])){
                                    $preList[] = [
                                        "skuId" => $productInfo['productId'],
                                        "channel" => $channel,
                                        "realCurrency" => $realCurrency ? $realCurrency : "暂无币种信息",
                                        "label" => $label,
                                        "value" => "没有该属性",
                                        "canDelete" => false,
                                    ];
                                }
                            }
                        }

                    }
                }
            }
            return ["env" => $env, "data" => $preList];

        }
        return ["env" => $env, "data" => $preList];
    }

}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new search();
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
    case "getClientIp":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->getClientIp($params);
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