<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");

class SpController
{
    private $log;

    private $channel;
    private $sellerId;
    private $action;
    private $phphkpro;
    private $debug;
    private $isAuto;
    private $isAsin;
    private $isCategory;
    private $isKeyword;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function dingTalk(){
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【sp广告已经投放】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} {$this->channel} {$this->sellerId} 已经投放完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    /**
     * 初始化变量
     * @param $channel 渠道
     * @param $sellerId 账号
     * @param $debug debug
     * @param $isAuto 是否auto投放
     * @param $isAsin 是否asin投放
     * @param $isCategory 是否category投放
     * @param $isKeyword 是否keyword投放
     * @return $this
     */
    public function initSPParams($channel = "", $sellerId = "", $debug = true, $isAuto = true, $isAsin = true, $isCategory = true, $isKeyword = true)
    {
        $proCurlService = new CurlService();
        $this->phphkpro = $proCurlService->pro()->phphk();

        $this->channel = $channel;
        $this->sellerId = $sellerId;
        $this->action = "createSpByPhpRestful";

        $this->isAuto = $isAuto;
        $this->isAsin = $isAsin;
        $this->isCategory = $isCategory;
        $this->isKeyword = $isKeyword;
        $this->debug = $debug; //开启广告debug
        return $this;
    }

    public function createAmazonSP()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "sp_{$this->sellerId}.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            if ($this->isAuto) {
                foreach ($contentList as $item) {
                    $this->createNewAuto($item['scuid']);
                }
            }
            if ($this->isAsin) {
                foreach ($contentList as $item) {
                    $this->createNewAsin($item['scuid']);
                }
            }
            if ($this->isCategory) {
                foreach ($contentList as $item) {
                    $this->createNewCategory($item['scuid']);
                }
            }
            if ($this->isKeyword) {
                $this->log("无投放");
            }
            $this->dingTalk();
        } else {
            $this->log("没有投放内容");
        }
    }

    private function createNewAuto($skuId)
    {
        $params = array(
            "skuId" => $skuId,
            "channel" => $this->channel,
            "sellerId" => $this->sellerId,
            "action" => $this->action,
            "targetingType" => "auto",
            "dataFrom" => "触发方法：" . __FUNCTION__,
        );

        if ($this->debug) {
            $params['debug'] = true;
        }

        $this->requestAutoAddPaCampaignNew($params);
        return $this;
    }

    private function createNewAsin($skuId)
    {
        $params = array(
            "skuId" => $skuId,
            "channel" => $this->channel,
            "sellerId" => $this->sellerId,
            "action" => $this->action,
            "targetingType" => "manual",
            "campaignType" => "asin",
            "dataFrom" => "触发方法：" . __FUNCTION__,
        );
        if ($this->debug) {
            $params['debug'] = true;
        }

        $this->requestAutoAddPaCampaignNew($params);
        return $this;
    }

    private function createNewCategory($skuId)
    {
        $params = array(
            "skuId" => $skuId,
            "channel" => $this->channel,
            "sellerId" => $this->sellerId,
            "action" => $this->action,
            "targetingType" => "manual",
            "campaignType" => "category",
            "dataFrom" => "触发方法：" . __FUNCTION__,
        );
        if ($this->debug) {
            $params['debug'] = true;
        }

        $this->requestAutoAddPaCampaignNew($params);
        return $this;
    }

    /**
     * 请求投放广告
     * @param $params
     * @return $this
     */
    private function requestAutoAddPaCampaignNew($params)
    {
        if (!isset($params['channel']) || !$params['channel']) {
            $this->log("渠道丢失，请重新检查数据");
            return $this;
        }
        if (!isset($params['sellerId']) || !$params['sellerId']) {
            $this->log("账号丢失，请重新检查数据");
            return $this;
        }
        if (!isset($params['action']) || !$params['action']) {
            $this->log("投放广告action丢失，请重新检查数据");
            return $this;
        }

        $this->log("创建sp - :{$params['skuId']}:{$params['channel']}:{$params['sellerId']}:{$params['dataFrom']}");

        $res = $this->phphkpro->post("amazonSpApi/autoAddPaCampaignNew", $params);
        if (!isset($res['result']) || !$res['result']) {
            $this->log("接口请求失败：" . json_encode($res,JSON_UNESCAPED_UNICODE));
            return $this;
        }

        if ($res['result']['messageType'] == 'success') {
            $this->check($params['skuId'], $res['result']);
        } else {
            $this->log($params['skuId'] . "::" . $res['result']['messageContent']);
        }

        return $this;
    }

    /**
     * 检查返回结果
     * @param $fba
     * @param $data
     */
    public function check($fba, $data)
    {
        $this->log($fba . "::" . json_encode($data, JSON_UNESCAPED_UNICODE));

        if (!isset($data['data']) || !$data['data']) {
            if (is_string($data['data'])){
                $this->log($data['data']);
                return;
            }
            if(is_array($data['data']) && count($data['data']) <= 0){
                $this->log($fba . ":创建失败.");
                return;
            }
        } else {
            if (isset($data['data']['campaignId']) && empty($data['data']['campaignId'])) {
                $this->log($fba . ":campaignId创建失败");
                return;
            }
            if (isset($data['data']['adGroupId']) && empty($data['data']['adGroupId'])) {
                $this->log($fba . ":adGroupId创建失败");
                return;
            }
            if (isset($data['data']['adId']) && empty($data['data']['adId'])) {
                $this->log($fba . ":adId创建失败");
            }
            if (isset($data['data']['targetId']) && empty($data['data']['targetId'])) {
                $this->log($fba . ":targetId创建失败");
            }
        }
    }

}

$con = new SpController();

//$con->dingTalk();
foreach ([
//             ["channel" => "amazon_ca", "sellerId" => "amazon_ca_luux", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_uk", "sellerId" => "amazon_uk_luux", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_uk", "sellerId" => "amazon_uk_tuto", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_us", "sellerId" => "amazon_us_ifn", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_us", "sellerId" => "amazon_us_luux", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_us", "sellerId" => "amazon_us_tuc", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_us", "sellerId" => "amazon_us_tuto", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_us", "sellerId" => "amazon_us_moto", "auto" => true, "asin" => true, "category" => true, "keyword" => false],
//             ["channel" => "amazon_jp", "sellerId" => "amazon_jp_bull", "auto" => true, "asin" => false, "category" => false, "keyword" => false],
//             ["channel" => "amazon_ca", "sellerId" => "amazon_ca_find", "auto" => false, "asin" => false, "category" => true, "keyword" => false],
//             ["channel" => "amazon_ca", "sellerId" => "amazon_ca_ac1", "auto" => false, "asin" => true, "category" => true, "keyword" => false],
             ["channel" => "amazon_us", "sellerId" => "amazon_us_ac1", "auto" => false, "asin" => false, "category" => true, "keyword" => false],
         ] as $data) {

    $con->initSPParams(
        $data['channel'],
        $data['sellerId'],
        false,
        $data['auto'],
        $data['asin'],
        $data['category'],
        $data['keyword']
    )->createAmazonSP();
}
