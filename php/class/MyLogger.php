<?php
namespace App\Core;

/**
 * 日志类 - 支持自动创建目录
 * Class MyLogger
 */
class MyLogger {

    private $logFile;
    private $logDir;
    
    public function __construct($logFile = ""){
        // 新的日志目录结构，与php文件夹同级
        $this->logDir = dirname(dirname(dirname(__FILE__))) . "/log/";
        $logDefaultFile = $this->logDir . "default/" . date('Ymd') . ".log";
        
        if (!empty($logFile)) {
            // 如果指定了日志文件名，创建对应的子目录
            $subDir = $this->logDir . dirname($logFile) . "/";
            $this->ensureDirectoryExists($subDir);
            $this->logFile = $subDir . basename($logFile) . "_" . date('Ymd') . ".log";
        } else {
            // 使用默认日志文件
            $this->ensureDirectoryExists($this->logDir . "default/");
            $this->logFile = $logDefaultFile;
        }
    }

    /**
     * 确保目录存在，如果不存在则创建
     * @param string $directory 目录路径
     */
    private function ensureDirectoryExists($directory) {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function log($message) {
        $this->ensureDirectoryExists(dirname($this->logFile));
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    public function log2($message){
        $this->ensureDirectoryExists(dirname($this->logFile));
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    
    /**
     * 获取当前日志文件路径
     * @return string
     */
    public function getLogFile() {
        return $this->logFile;
    }
}
