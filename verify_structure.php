<?php
/**
 * 项目结构和命名空间验证脚本
 */

echo "========================================\n";
echo "PaSystem 项目结构扫描\n";
echo "========================================\n\n";

// 引入autoload
require_once __DIR__ . '/autoload.php';

echo "✓ autoload.php 加载成功\n\n";

// 1. 检查命名空间类
echo "=== 命名空间类加载测试 ===\n";

$tests = [
    'App\Core\MyLogger' => 'MyLogger日志类',
    'App\Service\CurlService' => 'CurlService服务类',
    'App\Service\RedisService' => 'RedisService服务类',
    'App\Helper\ExcelUtils' => 'ExcelUtils工具类',
    'App\Helper\DataUtils' => 'DataUtils工具类',
    'App\Helper\ProductUtils' => 'ProductUtils工具类',
    'App\Helper\RequestUtils' => 'RequestUtils工具类',
    'App\Controller\EnvironmentConfig' => 'EnvironmentConfig控制器',
    'App\Shell\TestController' => 'TestController Shell类',
];

foreach ($tests as $class => $description) {
    if (class_exists($class)) {
        echo "✓ {$description} ({$class})\n";
    } else {
        echo "✗ {$description} ({$class}) - 未找到\n";
    }
}

echo "\n=== 常量定义检查 ===\n";
$constants = ['REDIS_HOST', 'REDIS_PORT', 'REDIS_PWD', 'PA_ROOT', 'PA_PHP'];
foreach ($constants as $const) {
    if (defined($const)) {
        echo "✓ {$const} = " . constant($const) . "\n";
    } else {
        echo "✗ {$const} - 未定义\n";
    }
}

echo "\n=== 向后兼容类别名检查 ===\n";
$aliases = ['MyLogger', 'ExcelUtils', 'DataUtils', 'CurlService', 'RedisService'];
foreach ($aliases as $alias) {
    if (class_exists($alias)) {
        echo "✓ {$alias} (别名可用)\n";
    } else {
        echo "✗ {$alias} - 别名不可用\n";
    }
}

echo "\n=== 项目文件统计 ===\n";

$phpFiles = [
    'php/class' => 'Core类',
    'php/controller' => 'Controller类',
    'php/shell' => 'Shell类',
    'php/curl' => 'Curl服务',
    'php/redis' => 'Redis服务',
    'php/utils' => '工具类',
];

foreach ($phpFiles as $dir => $name) {
    $count = count(glob(__DIR__ . "/{$dir}/*.php"));
    echo "{$name}: {$count}个文件\n";
}

echo "\n=== 旧式require检查 ===\n";
$output = shell_exec("cd " . __DIR__ . " && find php/ -name '*.php' -not -name '*.backup' -exec grep -l 'require.*dirname(__FILE__)' {} \\; 2>/dev/null | wc -l");
$oldRequireCount = intval(trim($output));

if ($oldRequireCount === 0) {
    echo "✓ 没有发现旧式require_once(dirname(__FILE__)语句\n";
} else {
    echo "✗ 发现 {$oldRequireCount} 个文件仍使用旧式require\n";
}

echo "\n========================================\n";
echo "扫描完成！\n";
echo "========================================\n";
