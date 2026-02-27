<?php
require_once(dirname(__FILE__) ."/../../php/class/Logger.php");

class CurlService
{
    private $header = array();

    private $port = null;
    public $environment = 'test';

    private $s3015 = null;
    private $s3047 = null;
    private $s3044 = null;
    private $s3009 = null;
    private $s3023 = null;
    private $s3013 = null;
    private $phphk = null;
    private $phpali = null;
    private $ux168 = null;
    private $s3010 = null;
    private $s3035 = null;
    private $s3016 = null;
    private $gateway = null;
    private $aiCategoryApi = null;

    public $module = "pa-biz-application";

    private $log;
    /**
     * @var mixed
     */
    private $ucToken;

    public function __construct() {
        $this->setHeader();
        $this->log = new MyLogger("curl/request");

    }

    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "pomsgoods":
                $this->module = "platform-pomsgoods-service";
                break;
            case "configmgmt":
                $this->module = "platform-config-mgmt-application";
                break;
            case "config":
                $this->module = "platform-config-service";
                break;
            case "ux168log":
                $this->module = "ux168-log-service";
                break;
            case "pa_service":
                $this->module = "pa-biz-service";
                break;
        }

        return $this;
    }

    /**
     * 头请求设置 - 默认
     * @param array $header
     * @param bool $isMerge
     * @return $this
     */
    public function setHeader($header = array(),$isMerge = true): CurlService
    {

        $initHeader = array(
            'request-trace-id: product_operation_client_' . date("Ymd_His") . '_' . rand(100000, 999999),
            'request-trace-level: 1',
            'Content-Type: application/json; charset=UTF-8',
            'Expect:',
        );
        if (count($header) > 0) {
            if ($isMerge){
                $initHeader = array_merge($initHeader, $header);
            }else{
                $initHeader = $header;
            }
        }
        $this->header = $initHeader;
        return $this;
    }

    /**
     * 设置test环境
     * @return $this
     */
    public function test(): CurlService
    {
        $this->environment = 'test';
        return $this;
    }

    /**
     * 设置local本机环境
     * @return $this
     */
    public function local(): CurlService
    {
        $this->environment = 'local';
        return $this;
    }

    /**
     * 设置uat环境
     * @return $this
     */
    public function uat(): CurlService
    {
        $this->environment = 'uat';
        return $this;
    }

    /**
     * 设置生产环境
     * @return $this
     */
    public function pro(): CurlService
    {
        $this->environment = 'pro';
        return $this;
    }

    /**
     * 自定义设置环境
     * @param string $environment
     * @return $this
     */
    public function setEnvironment($environment = 'test'): CurlService
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * product_operation_listing_management_nodejs_app
     * @return $this
     */
    public function s3015(): CurlService
    {
        $this->port = "s3015";
        $this->setBaseComponentByEnv();
        return $this;
    }

    /**
     * poms_big_data
     * @return $this
     */
    public function s3047(): CurlService
    {
        $this->port = "s3047";
        $this->setBaseComponentByEnv();
        return $this;
    }

    /**
     * poms_listing_nestjs
     * @return $this
     */
    public function s3044(): CurlService
    {
        $this->port = "s3044";
        $this->setBaseComponentByEnv();
        return $this;
    }
    /**
     * product_operation_nodejs_app
     * @return $this
     */
    public function s3009(): CurlService
    {
        $this->port = "s3009";
        $this->setBaseComponentByEnv();
        return $this;
    }
    /**
     * product_operation_sold_nodejs_app
     * @return $this
     */
    public function s3023(): CurlService
    {
        $this->port = "s3023";
        $this->setBaseComponentByEnv();
        return $this;
    }

    /**
     * ux168_nodes_js
     * @return $this
     */
    public function s3013(): CurlService
    {
        $this->port = "s3013";
        $this->setBaseComponentByEnv();
        return $this;
    }


    /**
     * product_operation_php_restful 广州服务
     * @return $this
     */
    public function phpali(): CurlService
    {
        $this->port = "phpali";
        $this->setBaseComponentByEnv();
        return $this;
    }
    /**
     * product_operation_php_restful 香港服务
     * @return $this
     */
    public function phphk(): CurlService
    {
        $this->port = "phphk";
        $this->setBaseComponentByEnv();
        return $this;
    }
    /**
     * ux168_nodejs_app
     * @return $this
     */
    public function ux168(): CurlService
    {
        $this->port = "ux168";
        $this->setBaseComponentByEnv();
        return $this;
    }
    /**
     * cets_nodejs_app
     * @return $this
     */
    public function s3010(): CurlService
    {
        $this->port = "s3010";
        $this->setBaseComponentByEnv();
        return $this;
    }

    public function s3016(): CurlService
    {
        $this->port = "s3016";
        $this->setBaseComponentByEnv();
        return $this;
    }

    public function aiCategoryApi(){
        $this->port = "aiCategoryApi";
        $this->setBaseComponentByEnv();
        return $this;
    }

    public function getUcToken($ucToken)
    {
        $this->ucToken = $ucToken;
        return $this;
    }

    public function gateway(): CurlService
    {
        $this->port = "gateway";

        $requestKey = "";
        if ($this->environment == 'test'){
            $requestKey = "bearer 8585fe5e-c604-43b7-83f7-2360dd986c5e";
        }elseif ($this->environment == 'uat'){
            $requestKey = "bearer b96217aa-ed4f-4fcd-9d66-bbe2728f600e";
        }elseif ($this->environment == 'pro'){
            $requestKey = "bearer dd63d1ec-3b31-4a15-a05a-1ea5daa5aeb0";
//            $requestKey = "bearer 551df679-9eb1-44f0-a092-2f65af010ba3";
        }
        if ($this->ucToken != null){
            $requestKey = "bearer " . $this->ucToken;
        }
        $this->setHeader(['Authorization: ' . $requestKey]);

        $this->setBaseComponentByEnv();
        return $this;
    }




    /**
     * get请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function get($module,$params = array()): ?array
    {
        if (!empty($params)){
            $module = "{$module}?".http_build_query($params);
        }
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestMethod($this->port,$module);
        }
        return $resp;
    }

    /**
     * post请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function post($module,$params = array()): ?array
    {
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestMethod($this->port,$module,$params,"POST");
        }
        return $resp;
    }

    /**
     * upload请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function upload($url,$module,$params = array()): ?array
    {
        $resp = $this->curlUploadMethod($url,$module,$params);
        return $resp;
    }

    /**
     * 新架构post请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function getWayPost($module,$params = array()): ?array
    {
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestMethod($this->port,$module,$params,"POST",true);
        }
        return $resp;
    }

    /**
     * 新架构post form-data请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function getWayFormDataPost($module,$params = array()): ?array
    {
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestFormData($this->port,$module,$params,"POST",true);
        }
        return $resp;
    }

    /**
     * 新架构get请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function getWayGet($module,$params = array()): ?array
    {
        $resp = null;
        if ($this->port != null){
            if (!empty($params)){
                $module = "{$module}?".http_build_query($params);
            }
            $resp = $this->curlRequestMethod($this->port,$module,$params,"GET",true);
        }
        return $resp;
    }
    /**
     * put请求
     * @param string $module 模块
     * @param array $params 参数
     * @return array|null
     */
    public function put($module,$params = array()): ?array
    {
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestMethod($this->port,$module,$params,"PUT");
        }
        return $resp;
    }

    /**
     * delete请求
     * @param string $module 模块
     * @param string $id 删除主键_id
     * @return array|null
     */
    public function delete($module,$id = ""): ?array
    {
        $resp = null;
        if ($this->port != null){
            if (!empty($id)){
                $module = "{$module}/{$id}";
            }
            $resp = $this->curlRequestMethod($this->port,$module,[],"DELETE");
        }
        return $resp;
    }

    /**
     * delete请求(包含了body请求)
     * @param string $module 模块
     * @param string $id 删除主键_id
     * @return array|null
     */
    public function deleteWithBodyData($module,$params = []): ?array
    {
        $resp = null;
        if ($this->port != null){
            $resp = $this->curlRequestMethod($this->port,$module,$params,"DELETE");
        }
        return $resp;
    }



    /**
     * 端口设置 入口 - local/test/uat/pro
     * @return $this
     */
    public function setBaseComponentByEnv(): CurlService
    {
        switch ($this->environment) {
            case "pro":
                $this->setMasterBaseComponent();
                break;
            case "test":
                $this->setTestBaseComponent();
                break;
            case "uat":
                $this->setUatBaseComponent();
                break;
            case "local":
                $this->setLocalBaseComponent();
                break;
        }
        return $this;
    }


    /**
     * 设置local 本机环境端口
     * @return $this
     */
    public function setLocalBaseComponent(): CurlService
    {
        switch ($this->port) {
            case "s3015":
                $this->s3015 = "http://172.16.29.23:3015";
                break;
            case "s3047":
                $this->s3047 = "http://172.16.29.23:3047";
                break;
            case "s3044":
                $this->s3044 = "http://172.16.29.23:3044";
                break;
            case "s3009":
                $this->s3009 = "http://172.16.29.23:3009";
                break;
            case "s3023":
                $this->s3023 = "http://172.16.29.23:3023";
                break;
            case "s3013":
                $this->s3013 = "http://172.16.29.23:3013";
                break;
            case "phphk":
            case "phpali":
                $this->phpali = "http://172.16.29.23:8000";
                $this->phphk = "http://172.16.29.23:8000";
                break;
            case "ux168":
                $this->ux168 = "http://172.16.29.23:3013";
                break;
            case "s3010":
                $this->s3010 = "http://172.16.29.23:3010";
                break;
            case "s3016":
                $this->s3016 = "http://172.16.29.23:3016";
                break;
            case "gateway":
                $this->gateway = "http://localhost:9021";
                break;
        }
        return $this;
    }

    /**
     * 设置sit环境端口
     * @return $this
     */
    public function setTestBaseComponent(): CurlService
    {
        switch ($this->port) {
            case "s3015":
                $this->s3015 = "http://172.16.10.62:30015";
                break;
            case "s3047":
                $this->s3047 = "http://172.16.10.62:30047";
                break;
            case "s3044":
                $this->s3044 = "http://172.16.10.62:30044";
                break;
            case "s3009":
                $this->s3009 = "http://172.16.10.62:30009";
                break;
            case "s3023":
                $this->s3023 = "http://172.16.10.62:30023";
                break;
            case "s3013":
                $this->s3013 = "http://172.16.10.62:30013";
                break;
            case "phphk":
            case "phpali":
                $this->phpali = "http://172.16.10.40:8000";
                $this->phphk = "http://172.16.10.40:8000";
                break;
            case "ux168":
                $this->ux168 = "http://172.16.10.62:30013";
                break;
            case "s3010":
                $this->s3010 = "http://172.16.10.62:30010";
                break;
            case "s3016":
                $this->s3016 = "http://172.16.10.62:30016";
                break;
            case "gateway":
                $this->gateway = "https://gateway-test.ux168.cn";
                break;
            case "aiCategoryApi":
                $this->aiCategoryApi = "http://172.16.75.238:12121";
        }
        return $this;
    }

    /**
     * 设置uat环境端口
     * @return $this
     */
    public function setUatBaseComponent(): CurlService
    {
        switch ($this->port) {
            case "s3015":
                $this->s3015 = "http://172.16.11.221:30015";
                break;
            case "s3047":
                $this->s3047 = "http://172.16.11.221:30047";
                break;
            case "s3044":
                $this->s3044 = "http://172.16.11.221:30044";
                break;
            case "s3009":
                $this->s3009 = "http://172.16.11.221:30009";
                break;
            case "s3023":
                $this->s3023 = "http://172.16.11.221:30023";
                break;
            case "s3013":
                $this->s3013 = "http://172.16.11.221:30013";
                break;
            case "phphk":
            case "phpali":
                $this->phpali = "http://172.16.10.66:8000";
                $this->phphk = "http://172.16.10.66:8000";
                break;
            case "ux168":
                $this->ux168 = "http://172.16.11.221:30013";
                break;
            case "s3010":
                $this->s3010 = "http://172.16.11.221:30010";
                break;
            case "s3016":
                $this->s3016 = "http://172.16.11.221:30016";
                break;
            case "gateway":
                $this->gateway = "https://gateway-uat.ux168.cn";
                break;
        }
        return $this;
    }

    /**
     * 设置生产环境端口
     * @return $this
     */
    public function setMasterBaseComponent(): CurlService
    {
        switch ($this->port) {
            case "s3015":
                $this->s3015 = "https://master-script-nodejs-poms-list-manage.ux168.cn";
                break;
            case "s3047":
                $this->s3047 = "https://master-nodejs-poms-big-data-nest.ux168.cn";
                break;
            case "s3044":
                $this->s3044 = "https://master-nodejs-poms-list-nest.ux168.cn";
                break;
            case "s3009":
                $this->s3009 = "https://master-nodejs-poms.ux168.cn";
                break;
            case "s3023":
                $this->s3023 = "https://master-nodejs-poms-sold.ux168.cn";
                break;
            case "s3013":
                $this->s3013 = "http://master.nodejs.168.ux168.cn:60013/";
                break;
            case "phphk":
                $this->phphk = "https://hk-alivpc-slim-poms.ux168.cn";
                break;
            case "phpali":
                $this->phpali = "https://alivpc-slim-poms.ux168.cn";
                break;
            case "ux168":
                $this->ux168 = "https://master-nodejs-168.ux168.cn";
                break;
            case "s3010":
                $this->s3010 = "https://master-nodejs-cets.ux168.cn";
                break;
            case "s3035":
                $this->s3035 = "https://master-nodejs-poms-log.ux168.cn";
                break;
            case "s3016":
                $this->s3016 = "http://master.nodejs.poms.qms.ux168.cn:60016";
                break;
            case "gateway":
                $this->gateway = "https://gateway.ux168.cn";
                break;
            case "aiCategoryApi":
                $this->aiCategoryApi = "https://ai-category-recommend.ux168.cn";
        }
        return $this;
    }


    /**
     * curl请求接口
     * @param $port 端口
     * @param $module 模块
     * @param array $params 参数
     * @param string $method 请求类型：GET/POST/PUT/DELETE
     * @param int $timeout 请求重试时长
     * @param int $tryTimes 失败重试次数
     * @return array
     */
    private function curlRequestMethod($port, $module, $params = array(), $method = "GET", $isNew = false, $timeout = 30, $tryTimes = 1): array
    {
        if (stripos($module, "/") !== 0 && !empty($module)) {
            $module = "/" . $module;
        }
        if (!$isNew){
            $url = $this->$port . '/api' . $module;
        }else{
            $url = $this->$port . $module;
        }

        $result = $httpCode = $headerResponse = $body = "";
        $t = 1;
        do{
            try{
                $connection = curl_init();
                curl_setopt($connection, CURLOPT_URL, $url);
                curl_setopt($connection,CURLOPT_HTTPHEADER, $this->header);//头请求
                curl_setopt($connection,CURLOPT_HEADER, true);//响应头请求
                curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);//跳过证书的验证
                curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, false);//跳过证书的验证
                curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);//把curl_exec()结果转化为字串，而不是直接输出
                curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);
                // 设置自定义Referer
                curl_setopt($connection, CURLOPT_REFERER, 'https://poms-ssl.ux168.cn/');
                // 设置自定义User-Agent
                curl_setopt($connection, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36');
                // 设置服务器代理
//                curl_setopt($connection, CURLOPT_HTTPHEADER, array(
//                    'X-Forwarded-For: 172.16.29.3' // 伪造的IP地址
//                ));
                $method = strtoupper($method);
                switch ($method){
                    case "POST":
                        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                        break;
                    case "PUT":
                        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "PUT");
                        curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                        break;
                    case "DELETE":
                        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "DELETE");
                        curl_setopt($connection, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                        break;
                    case "UPLOAD":
                        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
                        break;
                }
                $this->log->log("请求: {$method}：{$url}");
                $this->log->log("参数：".json_encode($params,JSON_UNESCAPED_UNICODE));

                $result = curl_exec($connection);

                //头信息
                $httpCode = intval(curl_getinfo($connection, CURLINFO_HTTP_CODE));
                $headerSize = curl_getinfo($connection, CURLINFO_HEADER_SIZE);
                $headerResponse = substr($result, 0, $headerSize);
                $body = json_decode(substr($result, $headerSize),true);

                if(!in_array($httpCode, array(401,404,429,)) && ($httpCode<200 || 300<$httpCode)){
                    throw new \Exception("http {$httpCode}");
                }else{
                    break;
                }
            }catch (\Exception $e){
                if($t<$tryTimes){ sleep(3); }
            }
        }while($t++<$tryTimes);
        curl_close($connection);
        return array(
            "httpCode" => $httpCode,
            "header" => $headerResponse,
            "result" => $body,
        );
    }

    /**
     * curl请求接口
     * @param $port 端口
     * @param $module 模块
     * @param array $params 参数
     * @param int $timeout 请求重试时长
     * @param int $tryTimes 失败重试次数
     * @return array
     */
    private function curlUploadMethod($port, $module, $params = array(), $timeout = 30, $tryTimes = 1): array
    {
        if (stripos($module, "/") !== 0 && !empty($module)) {
            $module = "/" . $module;
        }
        $url = $port . $module;


        $result = $httpCode = $headerResponse = $body = "";
        $t = 1;
        do{
            try{
                $connection = curl_init();
                curl_setopt($connection, CURLOPT_URL, $url);
                curl_setopt($connection,CURLOPT_HTTPHEADER, $this->header);//头请求
                curl_setopt($connection,CURLOPT_HEADER, true);//响应头请求
                curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);//跳过证书的验证
                curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, false);//跳过证书的验证
                curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);//把curl_exec()结果转化为字串，而不是直接输出
                curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);
                // 设置自定义Referer
                curl_setopt($connection, CURLOPT_REFERER, 'https://poms-ssl.ux168.cn/');
                // 设置自定义User-Agent
                curl_setopt($connection, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36');
                // 设置服务器代理
//                curl_setopt($connection, CURLOPT_HTTPHEADER, array(
//                    'X-Forwarded-For: 172.16.29.3' // 伪造的IP地址
//                ));
                $method = strtoupper($method);

                curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
                $this->log->log("请求: {$method}：{$url}");
                $this->log->log("参数：".json_encode($params,JSON_UNESCAPED_UNICODE));

                $result = curl_exec($connection);

                //头信息
                $httpCode = intval(curl_getinfo($connection, CURLINFO_HTTP_CODE));
                $headerSize = curl_getinfo($connection, CURLINFO_HEADER_SIZE);
                $headerResponse = substr($result, 0, $headerSize);
                $body = json_decode(substr($result, $headerSize),true);

                if(!in_array($httpCode, array(401,404,429,)) && ($httpCode<200 || 300<$httpCode)){
                    throw new \Exception("http {$httpCode}");
                }else{
                    break;
                }
            }catch (\Exception $e){
                if($t<$tryTimes){ sleep(3); }
            }
        }while($t++<$tryTimes);
        curl_close($connection);
        return array(
            "httpCode" => $httpCode,
            "header" => $headerResponse,
            "result" => $body,
        );
    }


    /**
     * 使用 multipart/form-data 方式发起 HTTP 请求（适用于文件上传或表单提交）
     *
     * @param string $port      对象属性名，如 'baseUrl'，值为完整主机地址（如 https://example.com）
     * @param string $module    API 路径，如 '/upload'
     * @param array  $params    表单数据，支持普通字段和文件（使用 CURLFile 或 '@' 前缀）
     * @param string $method    请求方法，默认 POST
     * @param bool   $isNew     是否不加 /api 前缀
     * @param int    $timeout   超时时间（秒）
     * @param int    $tryTimes  重试次数
     * @return array            返回 [httpCode, header, result]
     */
    private function curlRequestFormData(
        string $port,
        string $module,
        array $params = [],
        string $method = "POST",
        bool $isNew = false,
        int $timeout = 30,
        int $tryTimes = 1
    ): array {
        // 标准化 module 路径
        if (stripos($module, "/") !== 0 && !empty($module)) {
            $module = "/" . $module;
        }

        // 构建 URL
        if (!$isNew) {
            $url = $this->$port . '/api' . $module;
        } else {
            $url = $this->$port . $module;
        }

        $result = $httpCode = $headerResponse = $body = "";
        $t = 1;

        do {
            try {
                $connection = curl_init();
                curl_setopt($connection, CURLOPT_URL, $url);

                // 注意：不要手动设置 Content-Type！cURL 会自动设置 multipart/form-data 和 boundary
                // 所以这里只保留其他自定义头（如 Authorization 等），但移除可能存在的 Content-Type
                $headers = $this->header ?? [];
                // 过滤掉 Content-Type，避免干扰 multipart
                $filteredHeaders = array_filter($headers, function ($header) {
                    return stripos($header, 'content-type') === false;
                });
                curl_setopt($connection, CURLOPT_HTTPHEADER, $filteredHeaders);

                curl_setopt($connection, CURLOPT_HEADER, true);
                curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($connection, CURLOPT_REFERER, 'https://poms-ssl.ux168.cn/  ');
                curl_setopt($connection, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Mobile Safari/537.36');

                // 强制使用 POST（multipart/form-data 通常用于 POST）
                $method = strtoupper(trim($method));
                if ($method !== 'POST') {
                    // 虽然 RFC 不推荐，但某些服务端接受 POST 模拟 PUT/DELETE via _method
                    // 若需严格支持其他方法，需确认服务端是否接受 multipart/form-data 非 POST
                    // 这里建议仅支持 POST，或抛出警告
                    // 为兼容性，仍允许，但底层仍用 POST + 可能加 _method 字段
                    if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
                        throw new \Exception("multipart/form-data 通常仅用于 POST、PUT、PATCH 请求");
                    }
                    // 可选：添加 _method 伪装（如果后端支持）
                    // $params['_method'] = $method;
                    // 但更安全的做法是只允许 POST
                }

                curl_setopt($connection, CURLOPT_POST, true);
                curl_setopt($connection, CURLOPT_POSTFIELDS, $params); // 直接传数组

                $this->log->log("请求 (form-data): {$method}：{$url}");
                $this->log->log("表单参数：" . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));

                $result = curl_exec($connection);

                if ($result === false) {
                    $error = curl_error($connection);
                    throw new \Exception("cURL error: " . $error);
                }

                $httpCode = intval(curl_getinfo($connection, CURLINFO_HTTP_CODE));
                $headerSize = curl_getinfo($connection, CURLINFO_HEADER_SIZE);
                $headerResponse = substr($result, 0, $headerSize);
                $body = json_decode(substr($result, $headerSize), true);

                // 判断是否成功（非 4xx/5xx，但排除 401/404/429 以外的错误）
                if (!in_array($httpCode, [401, 404, 429]) && ($httpCode < 200 || $httpCode >= 300)) {
                    throw new \Exception("HTTP {$httpCode}");
                } else {
                    break; // 成功，跳出重试
                }

            } catch (\Exception $e) {
                $this->log->log("请求失败 (第 {$t} 次): " . $e->getMessage());
                if ($t < $tryTimes) {
                    sleep(3);
                }
            } finally {
                if (isset($connection)) {
                    curl_close($connection);
                }
            }
        } while ($t++ < $tryTimes);

        return [
            "httpCode" => $httpCode,
            "header" => $headerResponse,
            "result" => $body,
        ];
    }


    public function specialRequest($postData)
    {
        // ✅ 正确的 URL
        $url = 'https://sls.console.aliyun.com/console/logs/getLogs.json';

        // ✅ 最新 Headers（含 b3、traceparent、x-csrf-token 等）
        $headers = [
            'accept: application/json',
            'accept-language: zh-CN,zh;q=0.9,sq;q=0.8',
            'b3: 7958f1adfb631427929b7389ffdaab2f-b96a644f4ec6bd58-1',
            'bx-v: 2.5.31',
            'content-type: application/x-www-form-urlencoded',
            'origin: https://sls.console.aliyun.com',
            'referer: https://sls.console.aliyun.com/lognext/project/aliyun-hn1-all-log/logsearch/pa-biz-application-new?slsRegion=cn-shenzhen',
            'sec-ch-ua: "Not;A=Brand";v="99", "Google Chrome";v="139", "Chromium";v="139"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Linux"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'traceparent: 00-7958f1adfb631427929b7389ffdaab2f-b96a644f4ec6bd58-01',
            'uber-trace-id: 7958f1adfb631427929b7389ffdaab2f:b96a644f4ec6bd58:0:1',
            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'x-csrf-token: c345f390' // ✅ 与 cookie 中 csrf 一致
        ];

        // ✅ 最新 Cookies（完整复制你提供的）
        $cookies = 'cna=NaHqIBsy20oCAQ6Rk5/H14em; currentRegionId=cn-shenzhen; aliyun_country=CN; aliyun_lang=zh; aliyun_site=CN; login_aliyunid_pk=1196618798442729; login_current_pk=292058973591134045; login_aliyunid_csrf=4c30595991944660bb5e3ada61f3e09b; login_aliyunid="16363354792682515 @ 1196618798442729"; login_aliyunid_ticket=3SAW2yxnMCnjh5CCTX4bamPn.1118UkfUMcuYEraQfG6TNsrgxnbbErczXizSE2HhYQiVbmn5DvFZFjgJEwX8dTyC8qBvzHcmptsXRpmf4FdQA2Cu7GaUmcVVa9y5dWS2hdTL9YxeG3MhNmGtUHZWhRHLfnbs3GwsYQj7TbqpQbfDT3geUEDmRzGcYcohGaFqzLwj2TFirUa.2mWNaj2JF4NdyosWnc7a5gh7HBmZjDqARFscxXPvraEz3knwYexFKWTahgPNJEjfZr; login_aliyunid_sc=3R5H3e3HY2c8q5WiJ2aXp2hw.1113f6jMjXPatwNEC8RGu5Ud7tJ7QHFBHJEG8CHJCmWEHQsi1prrAhCc5xmjQCDfReFuQX.2mWNaj33VASFhtjxuZ9tu2Wt7tEcx7QyjmfHSHaQxZQiN58DBaEVM7HR2A8XHm2cBP; tfstk=g63iyagRkgfbSjQ1jI46jTTV8e-dRPajuxQYHre2YJyCWretg-DmKjwTkCG4oIDtsGExgdMmoJkUBAhv1sP4LjPYBmd_nSyEnrhOX5nm-AML_l-sHI0ndvM0cCwY3-DxgcdpyUhs1ra4oLLJyAVRxYH0gRrw8IPYZSRLb43Ztgz2eLLdv6r6BrlT3WH7LXyQiN740xrUYSPzu5yabB2Ui7sVQxz2tBV4iOyV7llUY7Ngu-k4uBcUdS44_xz2tXyQgfrGb83q42Jf9l76D5jqu5qgUfyZ6XgFNkssgJbVu2m3j888KZ7q-5lmoX0R8EyrVvoEb0fNJoUQKvFihU__7omqWyuwLEzqYA3uiqOPm-qm5y2nEU7bOcki8o0pGUcLSSD7ZxY5cXZ4ZAaZ7pBgoyV7DmD9dZytqXk322BdyzoSKqqzzg5VYg8wcZNeM2SfclPQt83mMxGMqerj1BAh2jZaO7wJtBjfTlPQtxRHtgn8bWNyY; isg=BLa2mCfywzGJR7YAVWRXFL5_B-W41_oRsCi-4yCc8Bk2Y179jW5FIYRVez8PS_Ip';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 生产环境建议开启
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }

        curl_close($ch);

        // 尝试解析 JSON
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data; // 成功解析为数组
        } else {
            return $response; // 返回原始字符串（可能是错误页面或非 JSON 响应）
        }

    }
}







