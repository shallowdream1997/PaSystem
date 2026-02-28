<?php
/**
 * TestController API 入口
 * 通过URL调用TestController的方法
 * 
 * 使用示例：
 * http://your-domain/api/test.php?action=downloadPaSkuPhotoProgress
 * http://your-domain/api/test.php?action=readPaSkuPhotoProgress
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 加载autoload
require_once __DIR__ . '/../autoload.php';

use App\Shell\TestController;

try {
    // 获取请求参数
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('缺少action参数');
    }
    
    // 创建TestController实例
    $controller = new TestController();
    
    // 根据action执行对应方法
    switch ($action) {
        case 'downloadPaSkuPhotoProgress':
            // 下载拍摄进度数据
            $controller->downloadPaSkuPhotoProgress();
            
            echo json_encode([
                'success' => true,
                'message' => '下载任务已执行',
                'action' => $action
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'readPaSkuPhotoProgress':
            // 读取Excel文件
            $result = $controller->readPaSkuPhotoProgress();
            
            echo json_encode([
                'success' => true,
                'message' => '读取成功',
                'action' => $action,
                'count' => count($result),
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("不支持的action: {$action}");
    }
    
} catch (Exception $e) {
    // 错误响应
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getTrace()
    ], JSON_UNESCAPED_UNICODE);
}
