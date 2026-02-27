<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';

/**
 * 优化后的 SyncCurl 控制器
 */
class SyncCurlV2Controller
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
        $this->curl = new CurlService();
        $this->redis = new RedisService();
    }

    /**
     * 日志记录
     *
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    /**
     * 通用删除操作
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param mixed $id ID
     * @param string $env 环境
     */
    public function commonDelete($port, $model, $id, $env = "test")
    {
        $resp = DataUtils::getResultData($this->curl->$env()->$port()->delete($model, $id));
        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 通过 ID 查询
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param mixed $id ID
     * @param string $env 环境
     * @return mixed
     */
    public function commonFindById($port, $model, $id, $env = 'test')
    {
        $resp = $this->curl->$env()->$port()->get("{$model}/{$id}");
        $data = $resp['result'] ?? null;
        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通过参数查询单条记录
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param array $params 参数
     * @param string $env 环境
     * @return array
     */
    public function commonFindOneByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 1;
        }
        $resp = $this->curl->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
        } else {
            $data = DataUtils::getPageListInFirstData($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通过参数查询多条记录
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param array $params 参数
     * @param string $env 环境
     * @return array
     */
    public function commonFindByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 999;
        }
        $resp = $this->curl->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getPageDocList($resp);
        } else {
            $data = DataUtils::getPageList($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通用创建操作
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param array $params 参数
     * @param string $env 环境
     * @return mixed
     */
    public function commonCreate($port, $model, $params, $env = "test")
    {
        $resp = DataUtils::getResultData($this->curl->$env()->$port()->post("{$model}", $params));
        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }

    /**
     * 通用更新操作
     *
     * @param string $port 端口
     * @param string $model 模型
     * @param array $params 参数
     * @param string $env 环境
     * @return mixed
     */
    public function commonUpdate($port, $model, $params, $env = "test")
    {
        $resp = DataUtils::getResultData($this->curl->$env()->$port()->put("{$model}/{$params['_id']}", $params));
        $this->log("更新{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }

    // 其他方法保持不变，但可以进一步优化和重构
}