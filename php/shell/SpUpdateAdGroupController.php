<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

class SpUpdateAdGroupController
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

    public function dingTalk($sellerId){
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【sp广告重启完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} {$sellerId} 已经重启adgroup完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function updateAdGroup($sellerId){
        $excelUtils = new ExcelUtils();
        $curlService = new CurlService();
        $fileName = "../export/sp/";
        try {
            $contentList = $excelUtils->getXlsxData($fileName . "sp_reload_adgroup_{$sellerId}.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $adgroupIds = array_column($contentList,"adgroupid");

            //$adgroupIds = ["520064669922127"];
            foreach (array_chunk($adgroupIds,1000) as $chunkAdgroupIds){
                //$redisService->hSet("sp_update_adgroup_reload", $sellerId, json_encode($chunkAdgroupIds, JSON_UNESCAPED_UNICODE));
                $updateAdgroupList = DataUtils::getPageList($curlService->pro()->s3023()->post("amazon_sp_adgroups/queryPagePost", [
                    "adGroupId_in" => implode(',', $chunkAdgroupIds),
                    "limit" => 1000
                ]));
                if (count($updateAdgroupList) > 0){
                    foreach ($updateAdgroupList as &$adGroupInfo){
                        $adGroupInfo['state'] = "enabled";
                        //查询notification里面当前_id主键待处理的广告数据,可能会有多个数据
                        $oldNotificationDataMap = $this->getNotificationDataElemMatch($adGroupInfo['_id']);
                        //对即将要更新的fromId里面，找出是否有符合当前更新条件的数据，如果有符合就不需要更新了，如果没有符合的就插入一次新的更新数据
                        $isUpdate = true;
                        if (count($oldNotificationDataMap) > 0){
                            foreach ($oldNotificationDataMap as $old){
                                if ($old['name'] == $adGroupInfo['adGroupName'] &&
                                    $old['state'] == $adGroupInfo['state'] &&
                                    $old['defaultBid'] == $adGroupInfo['defaultBid']){
                                    //如果有一模一样的更新，就不用再次插入了
                                    $isUpdate = false;
                                    break;
                                }
                            }
                        }

                        if (!$isUpdate){
                            $this->log("已有数据不必再插入");
                            continue;
                        }

                        $this->updateAdGroups($adGroupInfo['_id'], array(
                            "status" => "1",
                            "messages" => "运维重启",
                            "state" => $adGroupInfo['state'],
                            "defaultBid" => $adGroupInfo['defaultBid'],
                            "modifiedBy" => "pa_fix_system",
                            "lastUpdatedDate" => date("Y-m-d H:i:s",time())."Z",
                        ));
                    }
                }


            }

            $this->dingTalk($sellerId);
        }

    }
    /**
     * 获取notification里面fromId 的数据
     * @param $_id
     * @return array
     */
    public function getNotificationDataElemMatch($_id){
        $oldNotificationDataArr = array();

        $curlService = new CurlService();
        $resp = $curlService->pro()->s3023()->post("amazon_sp_notifications/getNotificationDataElemMatch", [
            'fromId' => $_id
        ]);
        if (isset($resp['result']) && count($resp['result']) > 0){
            $oldNotificationDataArr = $resp['result'];
        }
        $oldNotificationDataMap = array();
        if (count($oldNotificationDataArr) > 0){
            //存在符合fromId条件的数据，遍历出来，筛选出fromId对应的所有的要更新的数据
            foreach ($oldNotificationDataArr as $oldNotificationData){
                $fromIdMap = array_column($oldNotificationData['data'],null,'fromId');
                if (isset($fromIdMap[$_id])){
                    $oldNotificationDataMap[] = $fromIdMap[$_id];
                }
            }
        }
        return $oldNotificationDataMap;
    }

    /**
     * 更新adgroup数据，这里更新的有adgroup数据，并且记录adgroup_log日志，并且新增notification脚本内容
     * @param $_id
     * @param $updateParams
     */
    private function updateAdGroups($_id, $updateParams){
        $curlService = new CurlService();
        $resp = $curlService->pro()->s3023()->post("amazon_sp_adgroups/updateAdGroups", [
            "id" => $_id,
            "isPassNotification" => "true",
            "from" => "CLIENT_SP_RELOAD_ADGROUP",
            "updateParams" => $updateParams,
        ]);
        if ($resp['result'] && isset($resp['result']['adgroup']) && count($resp['result']['adgroup']) > 0) {
            $this->log("updateAdGroups：成功插入notification：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}");
        }else{
            $this->log("updateAdGroups：插入notification失败：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}",true);
        }
        return $this;
    }

}

$con = new SpUpdateAdGroupController();
$sellerId = $argv['1'];
//foreach ([
//             "amazon_ca_ac1",
//             "amazon_ca_hero",
//             "amazon_ca_hope",
//             "amazon_ca_luux",
//             "amazon_ca_moto",
//             "amazon_ca_rrc",
//             "amazon_ca_sopro",
//             "amazon_us_ac1",
//             "amazon_us_hope",
//             "amazon_us_luux",
//             "amazon_us_moto",
//             "amazon_us_rrc",
//             "amazon_us_sopro",
//         ] as $sellerId) {
//}
$con->updateAdGroup($sellerId);