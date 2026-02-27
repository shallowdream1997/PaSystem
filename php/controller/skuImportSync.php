<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';
require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/../utils/ExcelUtils.php';
require_once dirname(__FILE__) . '/../shell/SyncProductSku.php';
require_once dirname(__FILE__) . '/../class/Logger.php';

/**
 * SKU数据导入和同步控制器
 * Class skuImportSync
 */
class skuImportSync
{
    private $uploadDir;
    private $allowedExtensions;
    private $maxFileSize;
    private $logger;
    
    public function __construct()
    {
        $this->uploadDir = __DIR__ . "/../export/uploads/sku/";
        $this->allowedExtensions = ['xlsx', 'xls'];
        $this->maxFileSize = 2 * 1024 * 1024; // 2MB (根据PHP配置限制)
        $this->logger = new MyLogger('sku_import_sync');
        
        // 确保上传目录存在
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        
        // 记录初始化日志
        $this->logger->log("SKU导入同步控制器初始化完成");
    }
    
    /**
     * 处理请求
     * @param array $params 请求参数
     * @return array
     */
    public function handleRequest($params = [])
    {
        try {
            $action = isset($params['action']) ? $params['action'] : '';
            
            // 记录请求日志
            $this->logger->log("========================================");
            $this->logger->log("接收到请求 - Action: {$action}");
            $this->logger->log("请求参数: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            $this->logger->log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
            
            switch ($action) {
                case 'parse':
                    $this->logger->log("执行操作: 解析Excel文件");
                    return $this->parseExcelFile();
                case 'sync':
                    $this->logger->log("执行操作: 同步SKU数据");
                    return $this->syncSingleSku($params);
                case 'downloadTemplate':
                    $this->logger->log("执行操作: 下载模板");
                    return $this->downloadTemplate();
                default:
                    $this->logger->log("错误: 无效的操作类型 - {$action}");
                    return [
                        'success' => false,
                        'message' => '无效的操作类型',
                        'data' => []
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '处理请求时发生错误: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 下载Excel模板
     * @return array
     */
    private function downloadTemplate()
    {
        try {
            // 模板文件路径
            $templatePath = $this->uploadDir . 'sku_import_template.xlsx';
            
            // 如果模板不存在，创建模板
            if (!file_exists($templatePath)) {
                $this->createTemplate($templatePath);
            }
            
            // 设置响应头，直接下载文件
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="SKU导入模板.xlsx"');
            header('Content-Length: ' . filesize($templatePath));
            header('Cache-Control: no-cache, must-revalidate');
            
            readfile($templatePath);
            exit;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '下载模板失败: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 创建Excel模板文件
     * @param string $filePath 文件路径
     */
    private function createTemplate($filePath)
    {
        try {
            // 使用PhpSpreadsheet创建模板
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $spreadsheet->setActiveSheetIndex(0);
            $sheet = $spreadsheet->getActiveSheet();
            
            // 设置表头
            $sheet->setCellValue('A1', 'SKU ID');
            $sheet->setCellValue('B1', '备注（可选）');
            
            // 添加示例数据
            $sheet->setCellValue('A2', 'a25010100ux0001');
            $sheet->setCellValue('B2', '示例SKU 1');
            
            $sheet->setCellValue('A3', 'a25010100ux0002');
            $sheet->setCellValue('B3', '示例SKU 2');
            
            $sheet->setCellValue('A4', 'a25010100ux0003');
            $sheet->setCellValue('B4', '示例SKU 3');
            
            // 设置列宽
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(30);
            
            // 添加说明
            $sheet->setCellValue('A6', '说明：');
            $sheet->setCellValue('A7', '1. 第一列必须填写SKU ID');
            $sheet->setCellValue('A8', '2. 第二列为备注，可选填');
            $sheet->setCellValue('A9', '3. 支持批量导入，每行一个SKU');
            $sheet->setCellValue('A10', '4. 文件格式支持.xlsx和.xls');
            
            // 保存文件
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);
            
        } catch (Exception $e) {
            // 如果创建失败，记录错误
            error_log("创建模板失败: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 解析Excel文件
     * @return array
     */
    private function parseExcelFile()
    {
        if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => '没有上传有效的文件',
                'data' => []
            ];
        }
        
        $file = $_FILES['excelFile'];
        
        // 验证文件
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'data' => []
            ];
        }
        
        // 生成唯一文件名
        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('sku_') . '_' . time() . '.' . $extension;
        $targetPath = $this->uploadDir . $uniqueName;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errorMsg = '文件保存失败';
            // 添加详细的错误信息
            if (!is_writable($this->uploadDir)) {
                $errorMsg .= '：目录不可写 (' . $this->uploadDir . ')';
            } else if (!is_uploaded_file($file['tmp_name'])) {
                $errorMsg .= '：临时文件不存在或无效';
            } else {
                $errorMsg .= '：未知错误 (源: ' . $file['tmp_name'] . ', 目标: ' . $targetPath . ')';
            }
            
            return [
                'success' => false,
                'message' => $errorMsg,
                'data' => []
            ];
        }
        
        // 读取Excel数据
        try {
            $excelUtils = new ExcelUtils();
            $excelData = $excelUtils->_readXlsFile($targetPath);
            
            if (empty($excelData)) {
                unlink($targetPath); // 删除临时文件
                return [
                    'success' => false,
                    'message' => '无法读取Excel文件内容',
                    'data' => []
                ];
            }
            
            // 获取第一个工作表的数据
            $sheetData = reset($excelData);
            
            if (empty($sheetData)) {
                unlink($targetPath); // 删除临时文件
                return [
                    'success' => false,
                    'message' => 'Excel文件中没有数据',
                    'data' => []
                ];
            }
            
            // 提取SKU列表（假设第一列是SKU ID）
            $skuList = [];
            foreach ($sheetData as $row) {
                $firstValue = reset($row); // 获取第一列的值
                if (!empty($firstValue) && is_string($firstValue)) {
                    $skuList[] = trim($firstValue);
                }
            }
            
            // 去重
            $skuList = array_unique($skuList);
            
            // 删除临时文件
            unlink($targetPath);
            
            return [
                'success' => true,
                'message' => '文件解析成功',
                'data' => [
                    'skuList' => array_values($skuList),
                    'count' => count($skuList)
                ]
            ];
            
        } catch (Exception $e) {
            if (file_exists($targetPath)) {
                unlink($targetPath); // 清理临时文件
            }
            return [
                'success' => false,
                'message' => '解析Excel文件时发生错误: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 同步单个SKU数据
     * @param array $params 同步参数
     * @return array
     */
    private function syncSingleSku($params)
    {
        $skuId = isset($params['skuId']) ? trim($params['skuId']) : '';
        $module = isset($params['module']) ? trim($params['module']) : '';
        $port = isset($params['port']) ? trim($params['port']) : '';
        $field = isset($params['field']) ? trim($params['field']) : '';
        $targetEnv = isset($params['targetEnv']) ? trim($params['targetEnv']) : 'test'; // 默认test环境
        
        if (empty($skuId) || empty($module) || empty($port) || empty($field)) {
            return [
                'success' => false,
                'message' => '缺少必要的同步参数',
                'data' => []
            ];
        }
        
        // 验证目标环境
        if (!in_array($targetEnv, ['test', 'uat'])) {
            return [
                'success' => false,
                'message' => '目标环境只能是test或uat',
                'data' => []
            ];
        }
        
        try {
            // 记录同步开始日志
            $this->logger->log("========== 开始同步SKU ==========");
            $this->logger->log("SKU ID: {$skuId}");
            $this->logger->log("模块: {$module} ({$port})");
            $this->logger->log("字段: {$field}");
            $this->logger->log("目标环境: {$targetEnv}");
            
            // 创建SyncProductSku实例
            $syncProductSku = new SyncProductSku();
            
            // 根据目标环境设置相应的CurlService
            $reflection = new ReflectionClass($syncProductSku);
            if ($targetEnv === 'test') {
                $syncProductSku->toCurlService = (new CurlService())->test();
                $this->logger->log("目标环境设置为: TEST");
            } else {
                $syncProductSku->toCurlService = (new CurlService())->uat();
                $this->logger->log("目标环境设置为: UAT");
            }
            
            // 构造查询条件
            $condition = [
                $field => $skuId
            ];
            $this->logger->log("查询条件: " . json_encode($condition, JSON_UNESCAPED_UNICODE));
            
            // 从pro环境查询数据（源环境固定为pro）
            $this->logger->log("---------- 开始从PRO环境查询数据 ----------");
            $this->logger->log("调用方法: commonFromQueryPage");
            $this->logger->log("参数: module={$module}, port={$port}, condition=" . json_encode($condition, JSON_UNESCAPED_UNICODE));
            
            $fromData = $syncProductSku->commonFromQueryPage($module, $port, $condition);
            
            $this->logger->log("PRO环境查询结果数量: " . count($fromData));
            if (count($fromData) > 0) {
                $this->logger->log("PRO环境查询到的数据: " . json_encode($fromData, JSON_UNESCAPED_UNICODE));
            } else {
                $this->logger->log("PRO环境未查询到数据");
            }
            
            if (count($fromData) > 0) {
                // 查询目标环境是否存在数据
                $this->logger->log("---------- 开始从目标环境({$targetEnv})查询数据 ----------");
                $toData = $syncProductSku->commonToQueryPage($module, $port, $condition);
                $this->logger->log("目标环境查询结果数量: " . count($toData));
                if (count($toData) > 0) {
                    $this->logger->log("目标环境查询到的数据: " . json_encode($toData, JSON_UNESCAPED_UNICODE));
                }
                
                // 如果目标环境存在数据，先删除
                if (count($toData) > 0) {
                    $this->logger->log("---------- 开始删除目标环境旧数据 ----------");
                    foreach ($toData as $toDatum) {
                        try {
                            $this->logger->log("删除数据: Module={$module}, ID={$toDatum['_id']}");
                            $deleteResult = $syncProductSku->commonToDeleteId($module, $port, $toDatum['_id']);
                            // 记录删除成功日志
                            $this->logger->log("删除成功: ID={$toDatum['_id']}");
                        } catch (Exception $e) {
                            // 删除失败不影响整体流程，继续执行
                            $errorMsg = "删除数据失败: Module={$module}, ID={$toDatum['_id']}, Error=" . $e->getMessage();
                            $this->logger->log($errorMsg);
                            error_log($errorMsg);
                        }
                    }
                }
                
                // 创建新数据到目标环境
                $this->logger->log("---------- 开始创建新数据到目标环境 ----------");
                foreach ($fromData as $index => $fromDatum) {
                    try {
                        $this->logger->log("创建数据[" . ($index + 1) . "/" . count($fromData) . "]: " . json_encode($fromDatum, JSON_UNESCAPED_UNICODE));
                        $createResult = $syncProductSku->commonToCreate($module, $port, $fromDatum);
                        // 记录创建成功日志
                        $this->logger->log("创建成功[" . ($index + 1) . "/" . count($fromData) . "]");
                    } catch (Exception $e) {
                        // 创建失败记录错误
                        $errorMsg = "创建数据失败: Module={$module}, Data=" . json_encode($fromDatum) . ", Error=" . $e->getMessage();
                        $this->logger->log($errorMsg);
                        error_log($errorMsg);
                        throw new Exception("创建数据失败: " . $e->getMessage());
                    }
                }
                
                $this->logger->log("========== SKU同步完成 ==========");
                return [
                    'success' => true,
                    'message' => "SKU {$skuId} 在 {$module} 模块同步成功 ({$targetEnv}环境)",
                    'data' => [
                        'skuId' => $skuId,
                        'module' => $module,
                        'targetEnv' => $targetEnv,
                        'sourceCount' => count($fromData),
                        'targetCount' => count($toData)
                    ]
                ];
            } else {
                $this->logger->log("========== SKU同步完成（无数据） ==========");
                return [
                    'success' => true,
                    'message' => "SKU {$skuId} 在 {$module} 模块无数据需要同步 ({$targetEnv}环境)",
                    'data' => [
                        'skuId' => $skuId,
                        'module' => $module,
                        'targetEnv' => $targetEnv,
                        'sourceCount' => 0
                    ]
                ];
            }
            
        } catch (Exception $e) {
            $errorMsg = "SKU同步失败: SKU={$skuId}, Module={$module}, Error=" . $e->getMessage();
            $this->logger->log("========== SKU同步失败 ==========");
            $this->logger->log($errorMsg);
            $this->logger->log("异常堆栈: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => "同步SKU {$skuId} 到{$targetEnv}环境时发生错误: " . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 验证上传文件
     * @param array $file
     * @return array
     */
    private function validateFile($file)
    {
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
                UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入失败',
                UPLOAD_ERR_EXTENSION => '文件上传被扩展程序中断'
            ];
            
            return [
                'valid' => false,
                'message' => $errorMessages[$file['error']] ?? '未知上传错误'
            ];
        }
        
        // 检查文件大小
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'message' => '文件大小不能超过' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // 检查文件扩展名
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'message' => '只允许上传Excel文件(xlsx, xls)'
            ];
        }
        
        return ['valid' => true];
    }
}

// API入口点
$controller = new skuImportSync();

try {
    // 检查是否是下载模板请求
    if (isset($_GET['action']) && $_GET['action'] === 'downloadTemplate') {
        $controller->handleRequest(['action' => 'downloadTemplate']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['excelFile'])) {
            // 文件上传解析请求
            $result = $controller->handleRequest(['action' => 'parse']);
        } else {
            // JSON数据请求
            $input = file_get_contents('php://input');
            $params = json_decode($input, true) ?: $_POST;
            $result = $controller->handleRequest($params);
        }
    } else {
        $result = [
            'success' => false,
            'message' => '只支持POST请求',
            'data' => []
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}