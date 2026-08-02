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

    private $module = "pa-biz-application";

    /**
     * @var CurlService
     */
    public $envService;

    public function __construct()
    {
        $this->logger = new MyLogger("option/searchLog");
    }

    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
        }

        return $this;
    }


    /**
     * 重复品豁免
     * @param $params
     * @return array
     */
    public function pageSwitchConfig($params)
    {
        $curlService = $this->envService;

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "page_switch_config",
            "limit" => 1
        ]));

        $paProductIds = $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'];

        $res = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "id_in" => implode(",", $paProductIds), "limit" => count($paProductIds), "page" => 1]
        ));
        $batchNameList = [];
        foreach ($res as $item) {
            $batchNameList[] = [
                "_id" => $item['_id'],
                "batchName" => $item['batchName'],
            ];
        }
        $this->logger->log("查询批次号数量：" . count($batchNameList) . " 个");

        return [
            "env" => $env,
            "data" => $batchNameList
        ];

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
     * 品牌
     * @param $params
     * @return array
     */
    public function paProductBrand($params)
    {

        $curlService = $this->envService;

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => $params['optionName'],
            "limit" => 1
        ]));

        return [
            "env" => $env
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

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    public function paSampleSku($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $skuIdList = $params['skuIdList'];

            $curlService->gateway();
            $this->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
                "category" => "dataTeam",
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

    public function paProductList($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {

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

    public function addBrandFor($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "pa_amazon_attribute_forbidden",
            "limit" => 1
        ]));
        $list = array_keys($info['optionVal']);

        return [
            "env" => $env,
            "data" => [
                "canSearchChannelList" => $list
            ]
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
     * 查询采购清单对应开发
     * @param $params
     * @return array
     */
    public function getPmoData($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        if (isset($params['batchList']) && $params['batchList']) {

            $productSkuController = new ProductSkuController();
            $downloadOssLink = "PMO开发人员_" . date("YmdHis") . ".xlsx";
            $link = $productSkuController->getPmoData($downloadOssLink,$params['batchList'], $env);

            if ($link){
                return [
                    "env" => $env,
                    "messages" => "下载成功",
                    "data" => [
                        "downLink" => "/export/uploads/default/" . $downloadOssLink
                    ]
                ];
            }

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

        $ipMap = [];
        $dbDataList = $redisService->hGetAll(REDIS_USERNAME_IP_KEY . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                $old = json_decode($keyInfo,true);
                $ipMap[$old['ip']] = $ipMap['name'] ?: "";
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!isset($ipMap[$ip])){
            $dbData = [
                "name" => "新用户",
                "ip" => $ip
            ];
            $redisService->hSet(REDIS_USERNAME_IP_KEY . "_{$env}", $ip,json_encode($dbData,JSON_UNESCAPED_UNICODE));
        }

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
     * 查询fcu产品线
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
                    $returnMsg[] = [
                        "messages" => "找不到产品线",
                        "fcuId" => $fcuId,
                        "skuId" => $skuId,
                        "productLine" => "",
                    ];

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

    public function textDiff($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $curlService->gateway();
        $this->getModule('pa');
        $resp = DataUtils::getNewResultData($curlService->getWayPost( "/sms/sku/material/changed_doc/v1/textDiff", $params));


        return ["env" => $env, "data" => $resp['value']];
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
    case "paProductBrandSearch":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paProductBrand($params);
        break;
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
    case "getPmoData":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->getPmoData($params);
        break;
    case "registerIp":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->registerIp($params);
        break;
    case "fixFcuProductLine":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixFcuProductLine($params);
        break;
    case "textDiff":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->textDiff($params);
        break;
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);