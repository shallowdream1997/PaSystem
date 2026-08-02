<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';


/**
 * 测试api接口
 * Class apiTest
 */
class apiTest
{


    public $logger;


    /**
     * @var CurlService
     */
    public $envService;
    public function __construct()
    {
        $this->logger = new MyLogger("option/apiTest");
    }

    public function incr($params){
        $redis = new RedisService();
        $rank = $redis->incr($params["productListNo"]);
        $data = [
            "productListNo" => $params["productListNo"],
            "rank" => $rank,
            "supplierId" => $params['supplierId']
        ];
        return $data;
    }

}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new update();
$params = isset($data['params']) ? $data['params'] : [];
$action = $data['action'];
$return = $class->$action($params);
echo json_encode($return, JSON_UNESCAPED_UNICODE);