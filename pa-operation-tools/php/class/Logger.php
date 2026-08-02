<?php

/**
 * 日志类(统一日志输出)
 * Class MyLogger
 *
 * 说明:
 *  - 只保留一个写日志方法 log();历史方法 log2()/log3() 已移除,
 *    原调用点已统一改为 log()(行为完全一致);
 *  - 日志目录统一使用 PA_LOG_PATH(php/log),自动创建并 chmod 777;
 *  - 日志文件自动 chmod 777;
 *  - 通过 Composer autoload 加载,无需手动 require。
 */
class MyLogger
{
    private $logFile;

    public function __construct($logFile = "")
    {
        $logBase = defined('PA_LOG_PATH') ? PA_LOG_PATH : (dirname(__FILE__) . "/../../php/log");
        if (!empty($logFile)) {
            $this->logFile = $logBase . "/" . $logFile . "_" . date('Ymd') . ".log";
        } else {
            $this->logFile = $logBase . "/default/" . date('Ymd') . ".log";
        }
    }

    /**
     * 唯一日志输出方法:写入文件 + error_log
     * @param mixed $message
     * @return void
     */
    public function log($message)
    {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);
        if (!is_file($this->logFile)) {
            @file_put_contents($this->logFile, '');
        }
        @chmod($this->logFile, 0777); // 日志文件自动 777
        @file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        @error_log($message);
    }
}
