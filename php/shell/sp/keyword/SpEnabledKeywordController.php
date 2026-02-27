<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpEnabledKeywordController
{
    private $log;

    private $redis;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }
    public function reloadEnabledKeyword($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/OE广告投放.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {


            $productIdChannelScuIdsMap = [];

            $placement = [];
            foreach ($contentList as $content){
                if ($content['channel'] === $channel){
                    $fbaData = $spApi->pidScuMapProductIdFindScuId($content['channel'],$content['product_id']);
                    if (!$fbaData || !$fbaData['scuId']){
                        $this->log("product_id: {$content['product_id']}，不存在fba");
                        continue;
                    }
                    $placement[$content['channel']][$content['seller_id']][] = $fbaData['scuId'];

                    $productIdChannelScuIdsMap["{$content['channel']}_{$fbaData['scuId']}"] = [
                        "oeNumberList" => explode("/",$content['运营OE Number']),
                        "keywordTypeCn" => $content['keyword广告类型'],
                        "bid" => $content['bid'],
                    ];
                }
            }


            foreach ($placement as $channel => $data){
                foreach ($data as $sellerId=>$scuIds){
                    $scuIds = array_unique($scuIds);
                    $this->log("channel: {$channel}，sellerId：{$sellerId}，scuIds：" . count($scuIds) . "个");

                    foreach ($scuIds as $scuId){
                        if (!isset($productIdChannelScuIdsMap["{$channel}_{$scuId}"])){
                            $this->log("product_id: {$scuId}，不存在OE");
                            continue;
                        }
                        $oeNumberData = $productIdChannelScuIdsMap["{$channel}_{$scuId}"];
                        $keywordType = $spApi->keywordTypeMap($oeNumberData['keywordTypeCn']);
                        if (!$keywordType){
                            $this->log("product_id: {$scuId}，不存在广告投放类型，{$oeNumberData['keywordTypeCn']}");
                            continue;
                        }
                        foreach ($oeNumberData['oeNumberList'] as $oeNumber){
                            $spApi->paPlacementAmazonSpV2($channel, $sellerId, 2,'manual','keyword',$scuId,$keywordType,$oeNumber,$oeNumberData['bid']);
                        }
                    }


                }


            }
            (new RequestUtils("test"))->dingTalk("重新投放keyword{$channel}结束");
        }

    }


}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
if (isset($params['channel']) && trim($params['channel'] != '')) {
    $channel = $params['channel'];
}
$con = new SpEnabledKeywordController();
$con->reloadEnabledKeyword($channel);