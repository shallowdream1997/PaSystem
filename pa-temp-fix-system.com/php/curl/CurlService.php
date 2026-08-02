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
            case "config":
                $this->module = "platform-config-mgmt-application";
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


    public function gateway(): CurlService
    {
        $this->port = "gateway";

        $requestKey = "";
        if ($this->environment == 'test'){
            $requestKey = "bearer 8585fe5e-c604-43b7-83f7-2360dd986c5e";
        }elseif ($this->environment == 'uat'){
            $requestKey = "";
        }elseif ($this->environment == 'pro'){
            $requestKey = "bearer dd63d1ec-3b31-4a15-a05a-1ea5daa5aeb0";
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

}







