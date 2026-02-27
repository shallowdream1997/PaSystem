<?php
//require_once("../../vendor/autoload.php");

//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
//use LoggerOne\Logger as loggerOne;
//use LoggerOne\Handler\FileHandler;
//use LoggerOne\Formatter\CommonFormatter;

/**
 * 日志类
 * Class MyLogger
 */
class MyLogger {

    private $logFile;
    public function __construct($logFile = ""){
        $logDefaultFile = dirname(__FILE__) . "/../../php/log/default/".date('Ymd').".log";
        $this->logFile = !empty($logFile) ? dirname(__FILE__) . "/../../php/log/".$logFile."_".date('Ymd').".log" : $logDefaultFile;
    }

    public function log($message) {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    public function log2($message){
        // 创建日志器
//        $logger = new Logger('FixLogger');
//
//        $logger->pushHandler(new StreamHandler($this->logFile, Logger::INFO));
//        echo $message."\n";
//        $logger->info($message);

        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    public function log3($message = ""){
        // 创建自定义的文件处理器和格式化器
        $handler = new FileHandler($this->logFile);
        $formatter = new CommonFormatter();

        // 获取 LoggerOne 的实例
        $logger = LoggerOne::getInstance();

        // 设置处理器和格式化器
        $logger->setHandler($handler);
        $logger->setFormatter($formatter);

        // 写入日志
        $logger->info($message);
    }
}
