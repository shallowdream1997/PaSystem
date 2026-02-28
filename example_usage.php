<?php
/**
 * PaSystem 使用示例
 * 展示如何使用命名空间方式调用各个类
 */

// 引入自动加载器
require_once __DIR__ . '/autoload.php';

// ==================== 方式1: 使用完整命名空间 ====================

// 1. 日志类
$logger = new App\Core\MyLogger("example/demo");
$logger->log("这是使用命名空间方式的日志");

// 2. Excel工具类
$excel = new App\Helper\ExcelUtils("output/");
// $excel->createExcel($data, $fileName); // 导出Excel

// 3. 数据处理工具类
$response = [
    'code' => 200,
    'data' => ['id' => 1, 'name' => '测试']
];
$data = App\Helper\DataUtils::getResultData($response);

// 4. Curl服务类（需要配置环境）
// $curl = new App\Service\CurlService();

// 5. Redis服务类
// $redis = new App\Service\RedisService();

// ==================== 方式2: 使用 use 语句（推荐） ====================

use App\Core\MyLogger;
use App\Helper\ExcelUtils;
use App\Helper\DataUtils;
use App\Service\CurlService;
use App\Service\RedisService;

// 使用use后，可以直接使用类名
$logger2 = new MyLogger("example/use_statement");
$logger2->log("使用use语句的方式更简洁");

$excel2 = new ExcelUtils();
$result = DataUtils::getResultData($response);

// ==================== 方式3: 向后兼容（不推荐新代码使用） ====================

// 旧代码可以继续使用类名，通过class_alias实现向后兼容
$oldLogger = new MyLogger("example/old_way");
$oldLogger->log("旧代码无需修改即可运行");

$oldExcel = new ExcelUtils();

echo "✓ 所有方式都能正常工作！\n";
echo "✓ 推荐新代码使用方式2（use语句）\n";
echo "✓ 旧代码无需修改，自动兼容\n";
