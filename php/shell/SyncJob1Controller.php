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
class SyncJob1Controller
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;

        $this->redis = new RedisService();
    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function requestPortfolio()
    {
        $this->log("开始处理");
        try {
            $curlService = $this->curl->test();

            $res = DataUtils::getResultData($curlService->phphk()->post("/amazon/ad/portfolios/listPortfolios/amazon_us/amazon", [
                "stateFilter" => [
                    "include" => [
                        "ENABLED"
                    ]
                ],
                "portfolioIdFilter" => [
                    "include" => [
                        "114211076737061",
                        "90833883167643"
                    ]
                ]
            ]));
            if ($res){
                //刷新token成功
                $this->log("请求成功");
            }


        } catch (Exception $e) {
            $this->log($e->getMessage());
        }

        $this->log("处理完成");

    }

}