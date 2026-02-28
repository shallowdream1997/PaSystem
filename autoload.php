<?php
/**
 * PaSystem 自动加载器
 * 统一的类自动加载入口
 */

// 1. 加载 Composer 依赖
require_once __DIR__ . '/vendor/autoload.php';

// 2. 定义项目根目录
define('PA_ROOT', __DIR__);
define('PA_PHP', PA_ROOT . '/php');

// 3. PSR-4 自动加载映射（目录名 => 命名空间）
$classMap = [
    // 核心类
    'class' => 'App\\Core',
    // 控制器
    'controller' => 'App\\Controller',
    'shell' => 'App\\Shell',
    // 服务类
    'curl' => 'App\\Service',
    'redis' => 'App\\Service',
    // 工具类
    'utils' => 'App\\Helper',
];

// 4. 注册自动加载器
spl_autoload_register(function ($class) use ($classMap) {
    // 只处理 App 命名空间
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    
    // 移除命名空间前缀
    $className = substr($class, 4); // 去掉 "App\"
    
    // 遍历映射查找文件
    foreach ($classMap as $dir => $namespace) {
        $nsPrefix = substr($namespace, 4) . '\\'; // 去掉 "App\"
        
        if (strpos($className, $nsPrefix) === 0) {
            // 获取实际类名
            $actualClass = substr($className, strlen($nsPrefix));
            
            // 构造文件路径
            $file = PA_PHP . '/' . $dir . '/' . $actualClass . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// 5. 创建向后兼容的类别名
spl_autoload_register(function ($class) {
    // 旧类名到新类名的映射
    $aliases = [
        'MyLogger' => 'App\\Core\\MyLogger',
        'CurlService' => 'App\\Service\\CurlService',
        'RedisService' => 'App\\Service\\RedisService',
        'ExcelUtils' => 'App\\Helper\\ExcelUtils',
        'DataUtils' => 'App\\Helper\\DataUtils',
        'ProductUtils' => 'App\\Helper\\ProductUtils',
        'RequestUtils' => 'App\\Helper\\RequestUtils',
    ];
    
    if (isset($aliases[$class]) && class_exists($aliases[$class], true)) {
        class_alias($aliases[$class], $class);
    }
});
