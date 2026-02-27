<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';
/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class DelEbayBillRoundController
 */
class DelEbayBillRoundController
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


    public function delEbayBillRound()
    {
        //63c25f6d37a4d53bdfed515f
        $page = 1;
        do{
            $list = DataUtils::getPageList($this->curl->pro()->s3044()->get('ebay_bilino_add_rounds/queryPage',[
                'page' => $page,
                'limit' => 100,
//            'skuId' => 'a23041000ux0041',
                'columns' => 'remark,modifiedOn',
//                'modifiedOn_lte' => '2025-01-01'
            ]));
            if (count($list['data']) == 0){
                break;
            }
            foreach ($list['data'] as $item){
                $oldRemark = $item['remark'];
                $item['remark'] = deduplicateMessagesSimple($oldRemark);
                if (count($item['remark']) != count($oldRemark)){
                    $result = DataUtils::getResultData($this->curl->pro()->s3044()->put("ebay_bilino_add_rounds/{$item['_id']}",$item));
                }else{
                    $this->log("无修改");
                }
            }
            $page++;
        }while(true);


    }





}

/**
 * 简洁版本：使用数组索引保持顺序
 */
function deduplicateMessagesSimple($messages) {
    $seenMessages = []; // 记录已处理的message
    $latestMessages = []; // 存储每个message的最新记录
    $result = [];

    // 从后往前遍历，确保先找到最新的记录
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        $message = $messages[$i];
        $currentMessage = $message['message'];

        if (!isset($latestMessages[$currentMessage])) {
            $latestMessages[$currentMessage] = $message;
        }
    }

    // 按原始顺序添加记录
    foreach ($messages as $message) {
        $currentMessage = $message['message'];

        if (!in_array($currentMessage, $seenMessages)) {
            $result[] = $latestMessages[$currentMessage];
            $seenMessages[] = $currentMessage;
        }
    }

    return $result;
}

$curlController = new DelEbayBillRoundController();
$curlController->delEbayBillRound();