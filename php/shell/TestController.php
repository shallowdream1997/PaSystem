<?php
namespace App\Shell;

use App\Core\MyLogger;
use App\Helper\DataUtils;
use App\Helper\ExcelUtils;
use App\Service\CurlService;
use App\Service\RedisService;
use App\Helper\RequestUtils;

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class TestController
 */
class TestController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private RedisService $redis;
    
    public function __construct()
    {
        $this->log = new MyLogger("Test/test");
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

    public function downloadPaSkuPhotoProgress()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
            "limit" => 10,
            "page" => 1
        ]));
        $list = [];
        foreach ($ss['data'] as $info) {
            $list[] = [
                "batchName" => $info['batchName'],
                "ceBillNo" => $info['ceBillNo'],
                "createCeBillNoOn" => $info['createCeBillNoOn'],
                "skuId" => $info['skuId'],
                "status" => $info['status'],
                "photoBy" => $info['photoBy'],
                "photoOn" => $info['photoOn'],
            ];
        }

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
        }

    }


    public function readPaSkuPhotoProgress()
    {
        $excelUtils = new ExcelUtils();
        // 使用绝对路径
        $filePath = __DIR__ . "/../../export/default/图片拍摄进度导出_20260227175111.xlsx";
        $list = $excelUtils->getXlsxData($filePath);
        $this->log("读取到 " . count($list) . " 条数据");
        return $list;
    }
}

// 如果直接运行此文件，则执行测试
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    // 加载 autoload
    require_once __DIR__ . '/../../autoload.php';
    
    echo "TestController 测试开始\n";
    echo "====================\n\n";
    
    // 执行测试
    $c = new TestController();
    
    // 测试方法：读取Excel文件
    echo "测试 readPaSkuPhotoProgress 方法...\n";
    try {
        $result = $c->readPaSkuPhotoProgress();
        echo "✓ 执行成功！读取到 " . count($result) . " 条数据\n";
    } catch (\Exception $e) {
        echo "✗ 执行失败：" . $e->getMessage() . "\n";
    }
    
    echo "\n====================\n";
    echo "测试完成\n";
}