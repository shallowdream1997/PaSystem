<?php

require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';

class EnvironmentConfig
{
    /**
     * @var CurlService
     */
    public $curlService;

    public function __construct($pageName = '')
    {
        $this->setPageEnvironment($pageName);
    }


    private function setPageEnvironment($pageName){
        switch ($pageName){
            case "paProductBrandSearch":
                $this->curlService = (new CurlService())->pro();
                break;
            case "pageSwitchConfig":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixTranslationManagements":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixCeMaterials":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paFbaChannelSellerConfig":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paSampleSku":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paProductList":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paFixProductLine":
                $this->curlService = (new CurlService())->pro();
                break;
            case "addBrandFor":
                $this->curlService = (new CurlService())->pro();
                break;
            case "uploadOss":
                $this->curlService = (new CurlService())->pro();
                break;
            case "getPmoData":
                $this->curlService = (new CurlService())->pro();
                break;
            case "registerIp":
                $this->curlService = (new CurlService())->test();
                break;
            case "fixFcuProductLine":
                $this->curlService = (new CurlService())->pro();
                break;
            case "textDiff":
                $this->curlService = (new CurlService())->local();
                break;
        }
        return $this;
    }

    public function getCurlService(){
        return $this->curlService;
    }
}