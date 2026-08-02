<?php
/**
 * 冒烟测试:验证重构后的核心链路(Composer autoload / 日志 chmod777 / Excel 读写往返)
 *
 * 用法:php php/tests/smoke.php
 * 依赖:本机 PHP 7.4.33 + ext-redis(可选,RedisService 仅在 class_exists 时加载不连库)
 */
require_once dirname(__DIR__, 2) . '/php/requiredfile/requiredfile.php';

$pass = true;

// 1. 核心类 autoload 加载
$classes = ['CurlService', 'DataUtils', 'RequestUtils', 'ExcelUtils', 'ProductUtils', 'RedisService', 'MyLogger'];
foreach ($classes as $c) {
    if (!class_exists($c)) {
        echo "[FAIL] 类 $c 未加载\n";
        $pass = false;
    }
}
echo "[OK] 核心类 autoload 加载\n";

// 2. MyLogger 写日志 + chmod777
$log = new MyLogger("smoke_test");
$log->log("smoke test " . date('Y-m-d H:i:s'));
$logFile = PA_LOG_PATH . '/smoke_test_' . date('Ymd') . '.log';
if (!is_file($logFile)) {
    echo "[FAIL] 日志文件未创建\n";
    $pass = false;
} else {
    $perm = substr(sprintf('%o', fileperms($logFile)), -3);
    echo "[OK]  日志文件 perm=$perm\n";
    @unlink($logFile);
}

// 3. ExcelUtils 写 xlsx + 读回(PhpSpreadsheet)
try {
    $excel = new ExcelUtils();
    $file = $excel->downloadXlsx(['col1', 'col2'], [['a1', 'b1'], ['a2', 'b2']], 'smoke_' . date('YmdHis') . '.xlsx');
    $perm = substr(sprintf('%o', fileperms($file)), -3);
    echo "[OK]  导出文件 perm=$perm\n";
    $readBack = $excel->getXlsxData($file);
    if (count($readBack) == 2 && $readBack[0]['col1'] === 'a1' && $readBack[1]['col2'] === 'b2') {
        echo "[OK]  读回数据正确\n";
    } else {
        echo "[FAIL] 读回数据不正确: " . json_encode($readBack, JSON_UNESCAPED_UNICODE) . "\n";
        $pass = false;
    }
    @unlink($file);
} catch (Throwable $e) {
    echo "[FAIL] ExcelUtils 异常: " . $e->getMessage() . "\n";
    $pass = false;
}

echo $pass ? "SMOKE PASS\n" : "SMOKE FAIL\n";
exit($pass ? 0 : 1);
