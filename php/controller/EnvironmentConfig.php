<?php
namespace App\Controller;

use App\Service\CurlService;

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
            case "paFixProductLine":
                $this->curlService = (new CurlService())->pro();
                break;
            case "uploadOss":
                $this->curlService = (new CurlService())->test();
                break;
            case "registerIp":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixFcuProductLine":
                $this->curlService = (new CurlService())->test();
                break;
            case "consignmentQD":
                $this->curlService = (new CurlService())->pro();
                break;
            case "skuPhotoFix":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixCurrency":
                $this->curlService = (new CurlService())->pro();
                break;
        }
        return $this;
    }

    public function getCurlService(){
        return $this->curlService;
    }
}