<?php
namespace App\Controller;

use App\Helper\ExcelUtils;
use App\Controller\EnvironmentConfig;

/**
 * Excel文件上传和数据处理控制器
 * Class excelUpload
 */
class excelUpload
{
    private $uploadDir;
    private $allowedExtensions;
    private $maxFileSize;
    
    public function __construct()
    {
        $this->uploadDir = __DIR__ . "/../export/uploads/excel/";
        $this->allowedExtensions = ['xlsx', 'xls'];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        
        // 确保上传目录存在
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    /**
     * 处理Excel文件上传
     * @param array $file 上传的文件数组
     * @param array $params 额外参数
     * @return array
     */
    public function handleUpload($file, $params = [])
    {
        try {
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
            $uniqueName = uniqid('excel_') . '_' . time() . '.' . $extension;
            $targetPath = $this->uploadDir . $uniqueName;
            
            // 移动文件
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return [
                    'success' => false,
                    'message' => '文件保存失败',
                    'data' => []
                ];
            }
            
            // 读取Excel数据
            $excelData = $this->readExcelData($targetPath, $params);
            
            if (!$excelData['success']) {
                // 删除上传失败的文件
                unlink($targetPath);
                return $excelData;
            }
            
            // 返回成功结果
            return [
                'success' => true,
                'message' => '文件上传并处理成功',
                'data' => [
                    'fileName' => $originalName,
                    'uniqueName' => $uniqueName,
                    'filePath' => $targetPath,
                    'rowCount' => $excelData['rowCount'],
                    'columnCount' => $excelData['columnCount'],
                    'headers' => $excelData['headers'],
                    'rows' => $excelData['rows'],
                    'preview' => $excelData['preview']
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '处理文件时发生错误: ' . $e->getMessage(),
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
        // 检查是否有上传错误
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
    
    /**
     * 读取Excel数据
     * @param string $filePath 文件路径
     * @param array $params 参数配置
     * @return array
     */
    private function readExcelData($filePath, $params = [])
    {
        try {
            $excelUtils = new ExcelUtils();
            
            // 读取Excel文件
            $excelData = $excelUtils->_readXlsFile($filePath);
            
            if (empty($excelData)) {
                return [
                    'success' => false,
                    'message' => '无法读取Excel文件内容',
                    'rowCount' => 0,
                    'columnCount' => 0,
                    'headers' => [],
                    'rows' => [],
                    'preview' => []
                ];
            }
            
            // 获取第一个工作表的数据
            $sheetData = reset($excelData);
            
            if (empty($sheetData)) {
                return [
                    'success' => false,
                    'message' => 'Excel文件中没有数据',
                    'rowCount' => 0,
                    'columnCount' => 0,
                    'headers' => [],
                    'rows' => [],
                    'preview' => []
                ];
            }
            
            // 处理数据
            $headers = [];
            $rows = [];
            $preview = [];
            $rowCount = count($sheetData);
            $columnCount = 0;
            
            // 获取表头（第一行）
            if ($rowCount > 0) {
                $firstRow = $sheetData[0];
                $headers = array_keys($firstRow);
                $columnCount = count($headers);
            }
            
            // 处理数据行
            $startIndex = isset($params['hasHeader']) && $params['hasHeader'] ? 1 : 0;
            $maxPreviewRows = isset($params['previewRows']) ? (int)$params['previewRows'] : 10;
            
            for ($i = $startIndex; $i < $rowCount; $i++) {
                $rowData = $sheetData[$i];
                
                // 转换为索引数组以便前端处理
                $indexedRow = [];
                foreach ($rowData as $key => $value) {
                    $indexedRow[] = $value;
                }
                
                $rows[] = $indexedRow;
                
                // 生成预览数据
                if (count($preview) < $maxPreviewRows) {
                    $preview[] = $indexedRow;
                }
            }
            
            return [
                'success' => true,
                'rowCount' => count($rows),
                'columnCount' => $columnCount,
                'headers' => $headers,
                'rows' => $rows,
                'preview' => $preview
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '读取Excel文件时发生错误: ' . $e->getMessage(),
                'rowCount' => 0,
                'columnCount' => 0,
                'headers' => [],
                'rows' => [],
                'preview' => []
            ];
        }
    }
    
    /**
     * 批量处理Excel文件
     * @param array $files 多个文件数组
     * @param array $params 参数
     * @return array
     */
    public function handleMultipleUpload($files, $params = [])
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        // 重构文件数组格式
        $fileList = [];
        if (isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $fileList[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        } else {
            $fileList[] = $files;
        }
        
        foreach ($fileList as $file) {
            $result = $this->handleUpload($file, $params);
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        return [
            'success' => $failCount === 0,
            'message' => "处理完成：成功 {$successCount} 个，失败 {$failCount} 个",
            'data' => $results,
            'summary' => [
                'total' => count($fileList),
                'success' => $successCount,
                'failed' => $failCount
            ]
        ];
    }
    
    /**
     * 删除上传的文件
     * @param string $fileName 文件名
     * @return bool
     */
    public function deleteFile($fileName)
    {
        $filePath = $this->uploadDir . $fileName;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * 获取上传目录中的文件列表
     * @return array
     */
    public function getFileList()
    {
        $files = [];
        if (is_dir($this->uploadDir)) {
            $fileList = scandir($this->uploadDir);
            foreach ($fileList as $file) {
                if ($file !== '.' && $file !== '..' && is_file($this->uploadDir . $file)) {
                    $filePath = $this->uploadDir . $file;
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'extension' => pathinfo($file, PATHINFO_EXTENSION)
                    ];
                }
            }
        }
        return $files;
    }
}

// API入口点
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $excelUploader = new excelUpload();
    
    try {
        if (isset($_FILES['excelFile'])) {
            // 单文件上传
            $params = [
                'hasHeader' => isset($_POST['hasHeader']) ? (bool)$_POST['hasHeader'] : true,
                'previewRows' => isset($_POST['previewRows']) ? (int)$_POST['previewRows'] : 10
            ];
            
            $result = $excelUploader->handleUpload($_FILES['excelFile'], $params);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } elseif (isset($_FILES['excelFiles'])) {
            // 多文件上传
            $params = [
                'hasHeader' => isset($_POST['hasHeader']) ? (bool)$_POST['hasHeader'] : true,
                'previewRows' => isset($_POST['previewRows']) ? (int)$_POST['previewRows'] : 10
            ];
            
            $result = $excelUploader->handleMultipleUpload($_FILES['excelFiles'], $params);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } else {
            echo json_encode([
                'success' => false,
                'message' => '没有找到上传的文件',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '服务器内部错误: ' . $e->getMessage(),
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
    }
}