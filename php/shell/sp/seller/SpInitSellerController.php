<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpInitSellerController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function initSeller(){
        $redisService = new RedisService();
        $spApi = new SpApi();

        $list = $spApi->getMongoSellerRuleList();


        $jsonStr = '[{"skuRuleId":1,"name":"S级/开发渠道","condition":[{"field":"product_level","value":"S级"},{"field":"is_dev_channel","value":true}],"oeNumber":5,"oneOeGetTopAsinNumber":20},{"skuRuleId":2,"name":"S级/非开发渠道","condition":[{"field":"product_level","value":"S级"},{"field":"is_dev_channel","value":false}],"oeNumber":2,"oneOeGetTopAsinNumber":20},{"skuRuleId":3,"name":"A级/开发渠道","condition":[{"field":"product_level","value":"A级"},{"field":"is_dev_channel","value":true}],"oeNumber":2,"oneOeGetTopAsinNumber":20},{"skuRuleId":4,"name":"A级/非开发渠道","condition":[{"field":"product_level","value":"A级"},{"field":"is_dev_channel","value":false}],"oeNumber":2,"oneOeGetTopAsinNumber":20},{"skuRuleId":5,"name":"B级/开发渠道","condition":[{"field":"product_level","value":"B级"},{"field":"is_dev_channel","value":true}],"oeNumber":1,"oneOeGetTopAsinNumber":20},{"skuRuleId":6,"name":"B级/非开发渠道","condition":[{"field":"product_level","value":"B级"},{"field":"is_dev_channel","value":false}],"oeNumber":1,"oneOeGetTopAsinNumber":20}]';
        $json = json_decode($jsonStr,true);


        $sptypelist = ["auto","manual","manual asin","manual category"];

        if ($list){
            foreach ($list as $item){

                $oeNumberRuleList = [];
                foreach ($json as $jItem){
                    foreach ($sptypelist as $sptype){

                        $oeNumberRuleList[] = [
                            "spType" => $sptype,
                            "skuRuleId"=>$jItem['skuRuleId'],
                            "oeNumber" => $jItem['oeNumber'],
                            "oneOeGetTopAsinNumber" => $jItem['oneOeGetTopAsinNumber'],
                        ];
                    }
                }
                $item['oeNumberRule'] = $oeNumberRuleList;

                $sptypemap = [];
                foreach ($item['bindRule'] as $bindRule){
                    if (!isset($sptypemap[$bindRule['spType']])){
                        $sptypemap[$bindRule['spType']] = $bindRule;
                    }
                }

                $bindRuleList = [];
                foreach ($json as $jItem){
                    foreach ($sptypemap as $sptype => $sptypeItem){

                        unset($sptypeItem['_id']);
                        $sptypeItem['skuRuleId'] = $jItem['skuRuleId'];
                        $sptypeItem['skuRuleName'] = $jItem['name'];

                        $bindRuleList[] = $sptypeItem;
                    }
                }
                $item['bindRule'] = $bindRuleList;
                $spApi->updateMongoSellerRule($item);
            }
        }



    }




}

$con = new SpInitSellerController();
$con->initSeller();