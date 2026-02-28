<?php
/**
 * TestController 执行入口文件
 * 使用方法：php run_test.php
 */

// 引入自动加载器
require_once __DIR__ . '/autoload.php';

use App\Shell\TestController;

// 创建实例
$controller = new TestController();

// 根据需要调用不同的方法
// $controller->downloadPaSkuPhotoProgress(); // 下载拍摄进度
// $controller->readPaSkuPhotoProgress();     // 读取Excel文件

echo "TestController 已初始化，可以调用其方法\n";
echo "可用方法：\n";
echo "  - downloadPaSkuPhotoProgress() : 下载拍摄进度\n";
echo "  - readPaSkuPhotoProgress()     : 读取Excel文件\n";

