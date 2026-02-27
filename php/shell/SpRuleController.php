<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpRuleController
{
    private $log;

    /**
     * @var CurlService
     */
    private $curlService;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->curlService = (new CurlService())->pro();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function buildAmazonSpRuleRegex($channel,$sellerId,$spType)
    {
        $result = DataUtils::getResultData($this->curlService->phphk()->get("amazonSpApi/buildAmazonSpRuleRegex",[
            "company" => "CR201706060001",
            "channel" => $channel,
            "sellerId" => $sellerId,
            "spType" => $spType,
        ]));
        if ($result && $result['data']){
            $regexArr = $result['data'];
            $data = [];
            if ($regexArr[$spType]){
                $data = [
                    "campaignNameRule" => $regexArr[$spType]['campaignRuleBySystem'],
                    "adGroupNameRule" => $regexArr[$spType]['adGroupRule'],
                    "percentType" => $regexArr[$spType]['bidRule']['percentType'],
                    "bidStrategyType" => $regexArr[$spType]['bidRule']['bidStrategyType'],
                    "bidType" => $regexArr[$spType]['bidRule']['bidType'],
                    "dailyBudget" => $regexArr[$spType]['bidRule']['dailyBudget'],
                    "defaultBid" => $regexArr[$spType]['bidRule']['defaultBid'],
                    "minBid" => $regexArr[$spType]['bidRule']['minBid'],
                    "maxBid" => $regexArr[$spType]['bidRule']['maxBid'],
                    "adGroupCount" => $regexArr[$spType]['bidRule']['adGroupCount'],
                ];
            }
            $this->log(json_encode($data,JSON_UNESCAPED_UNICODE));
            return $data;
        }else{
            return [];
        }
    }

    public function getAmazonSpRuleRegexSystemCampaignRegex($channel,$sellerId,$spType)
    {
        $info = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company_in" => "CR201706060001",
            "channel" => $channel,
            "sellerId" => $sellerId,
            "limit" => 1
        ]));
        if ($info){
            foreach($info['bindRule'] as $item){
                if ($item['spType'] == $spType && $item['status'] == 1 && $item['ruleTypeAndId']){
                    foreach ($item['ruleTypeAndId'] as $dIt){
                        if ($dIt['ruleType'] == "campaignRuleBySystem"){
                            $rule = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_rule_configs/queryPage",[
                                "ruleId" => $dIt['ruleId'],
                            ]));
                            if ($rule){
                                $data = [
                                    "ruleRegex" => $rule['ruleRegex'],
                                    "pipe" => "",
                                ];
                                foreach($rule['ruleFieldName'] as $dddItem){
                                    if ($dddItem['fieldType'] == "pipe"){
                                        $data['pipe'] = $dddItem['field'];
                                        break;
                                    }
                                }
                            }
                            break 2;
                        }
                    }
                }
            }
            return $data;
        }else{
            return [];
        }
    }

    public function buildCampaignName()
    {

    }
}
