<?php
/**
 * 全局引导文件(唯一入口)
 * ------------------------------------------------------------------
 * 1. 引入 Composer autoload:所有核心类(CurlService/DataUtils/RequestUtils/
 *    ExcelUtils/ProductUtils/RedisService/MyLogger 等)
 *    由 composer classmap 自动加载,**无需再手动 require 类文件**。
 * 2. 定义统一路径常量:导出、上传、日志目录全部基于 __DIR__ 计算,
 *    不再依赖脚本运行时的 CWD。
 * 3. 提供目录/文件权限辅助:自动创建目录/文件并 chmod 777。
 * ------------------------------------------------------------------
 * 使用方法(与旧版完全兼容):
 *   require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
 *   $curlService = (new CurlService())->test()->s3015();
 */
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

// ================= 统一路径常量(基于 __DIR__,不依赖 CWD) =================
defined('PA_ROOT_PATH')   || define('PA_ROOT_PATH', dirname(__DIR__, 2));            // 项目根目录
defined('PA_PHP_PATH')    || define('PA_PHP_PATH', dirname(__DIR__));                // php/ 目录
defined('PA_EXPORT_PATH') || define('PA_EXPORT_PATH', dirname(__DIR__) . '/export');          // Excel/CSV 导出统一目录
defined('PA_UPLOAD_PATH') || define('PA_UPLOAD_PATH', dirname(__DIR__) . '/export/uploads');   // 上传下载统一目录
defined('PA_LOG_PATH')    || define('PA_LOG_PATH', dirname(__DIR__) . '/log');       // 日志统一目录

// ================= 目录/文件权限辅助(自动创建 + chmod 777) =================
if (!function_exists('paEnsureDir')) {
    /**
     * 确保目录存在并赋予 0777 权限
     *
     * @param string $path 目录路径
     * @return string
     */
    function paEnsureDir($path)
    {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        @chmod($path, 0777);
        return $path;
    }
}

if (!function_exists('paEnsureFile')) {
    /**
     * 确保文件存在并赋予 0777 权限
     *
     * @param string $file 文件路径
     * @return string
     */
    function paEnsureFile($file)
    {
        $dir = dirname($file);
        paEnsureDir($dir);
        if (!is_file($file)) {
            @file_put_contents($file, '');
        }
        @chmod($file, 0777);
        return $file;
    }
}
