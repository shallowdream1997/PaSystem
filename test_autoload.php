<?php
/**
 * 测试自动加载器
 */

require_once __DIR__ . '/autoload.php';

echo "========== 测试命名空间方式 ==========\n";

// 测试1: 使用完整命名空间
try {
    $logger = new App\Core\MyLogger("test/autoload");
    $logger->log("测试命名空间加载成功!");
    echo "✓ MyLogger (命名空间方式) 加载成功\n";
    echo "  日志文件: " . $logger->getLogFile() . "\n";
} catch (Exception $e) {
    echo "✗ MyLogger加载失败: " . $e->getMessage() . "\n";
}

// 测试2: 使用类别名（向后兼容）
try {
    $logger2 = new MyLogger("test/alias");
    $logger2->log("测试类别名加载成功!");
    echo "✓ MyLogger (类别名方式) 加载成功\n";
} catch (Exception $e) {
    echo "✗ 类别名加载失败: " . $e->getMessage() . "\n";
}

// 测试3: 测试ExcelUtils
try {
    $excel = new App\Helper\ExcelUtils();
    echo "✓ ExcelUtils 加载成功\n";
} catch (Exception $e) {
    echo "✗ ExcelUtils加载失败: " . $e->getMessage() . "\n";
}

// 测试4: 测试DataUtils
try {
    $data = App\Helper\DataUtils::getResultData(['code' => 200, 'data' => ['test' => 'value']]);
    echo "✓ DataUtils 加载并调用成功\n";
} catch (Exception $e) {
    echo "✗ DataUtils加载失败: " . $e->getMessage() . "\n";
}

// 测试5: 测试CurlService
try {
    // CurlService需要环境配置，只测试类加载
    $reflection = new ReflectionClass('App\Service\CurlService');
    echo "✓ CurlService 类加载成功\n";
} catch (Exception $e) {
    echo "✗ CurlService加载失败: " . $e->getMessage() . "\n";
}

echo "\n========== 测试完成 ==========\n";
