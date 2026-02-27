<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");


class SpSyncPomsController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    /**
     * 调用 MongoDB 查询 API
     *
     * @return array|false 返回解码后的 JSON 响应，或 false 表示失败
     */
    private function callMongoApi()
    {
        $url = 'http://api-management-service.all-test.svc.cluster.local:9021/mongo_query/v1/pageList';

        // 构建请求体
        $payload = [
            "collectionName" => "amazon_ad_campaigns_list",
            "aggCondition" => [
                "aggregates" => [
                    [
                        "aggregates" => [
                            [
                                "fieldName" => "sellerId",
                                "link"      => "AND",
                                "option"    => "eq",
                                "value"     => "amazon_jp_pat"
                            ]
                        ]
                    ]
                ]
            ],
            "queryFiledList" => ["_id", "channel", "sellerId", "campaign"],
            "pageNum"        => 1,
            "pageSize"       => 20,
            "sortReqList"    => [
                [
                    "fileName"   => "_id",
                    "sortType"   => "asc"
                ]
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 初始化 cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: bearer b1cdd4ce-c4a2-467b-b3f7-7adab852924f',
            'Content-Type: application/json',
            'Accept: */*',
            'Host: api-management-service.all-test.svc.cluster.local:9021',
            'Connection: keep-alive'
        ]);

        $this->log("Sending request to MongoDB API: " . $jsonPayload);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->log("cURL error: " . $error);
            return false;
        }

        if ($httpCode >= 400) {
            $this->log("HTTP error: " . $httpCode . ", response: " . $response);
            return false;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("JSON decode error: " . json_last_error_msg());
            return false;
        }

        $this->log("MongoDB API call succeeded, fetched " . (isset($decoded['data']['list']) ? count($decoded['data']['list']) : 'unknown') . " records.");
        return $decoded;
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function syncToPoms()
    {
        $this->log("Starting syncToPoms process...");

        $result = $this->callMongoApi();

        if ($result === false) {
            $this->log("Failed to fetch data from MongoDB API.");
            return;
        }

        if ($result['state']['code'] == 2000000 && isset($result['data']['list']) && count($result['data']['list']) > 0){
            foreach ($result['data']['list'] as $item){
                $condition = [];
                $condition['channel'] = $item['sellerId'];

                if(isset($item['campaign'])){

                }
            }
        }

        // TODO: 在这里处理 $result 中的数据，例如同步到 POMS 系统
        // 示例：
        // foreach ($result['data']['list'] as $item) {
        //     // 同步逻辑
        // }

        $this->log("syncToPoms completed successfully.");
    }
}
$con = new SpSyncPomsController();
$con->syncToPoms();
