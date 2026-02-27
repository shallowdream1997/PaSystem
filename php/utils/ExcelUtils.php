<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

// 使用PhpSpreadsheet替代PHPExcel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriteXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls as WriteXls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls as ReaderXls;
use PhpOffice\PhpSpreadsheet\Reader\Csv as ReaderCsv;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * 导入导出文件工具类
 * Class ExcelUtils
 */
class ExcelUtils
{
    public $downPath;

    public function __construct($downPath = "")
    {
        $downDefaultFile = __DIR__ . "/../export/";
        $this->downPath = !empty($downPath) ? $downDefaultFile . $downPath : $downDefaultFile . "default/";
    }

    /**
     * 数据写入xls文件,下载文件
     * @param array $titleList $header = [
     * '_id' => '主键',
     * 'channel' => '渠道',
     * ];
     * @param array $data $export = [
     * [
     * "_id" => "sasdadada",
     * "channel" => "amazon_us"
     * ]
     * ];
     * @param string $fileName "开发清单_".date("YmdHis").".xlsx"
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download(array $titleList, array $data, $fileName = "")
    {
        $downDefaultFileName = "导出默认文件_" . date('YmdHis') . ".xlsx";
        $downFileName = !empty($fileName) ? $fileName : $downDefaultFileName;
    
        if (count($data) > 0) {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);
            $index = 0;
            // 获取表头
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex($index);
            $spreadsheet->getActiveSheet()->setTitle('Sheet' . ($index + 1));
            $titleNum = 1;
            $dataNum = 2;
            $columnIndex = 1; // PhpSpreadsheet使用1-based索引
                
            foreach ($data[0] as $key => $item) {
                $titleName = isset($titleList[$key]) ? $titleList[$key] : $key;
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $spreadsheet->getActiveSheet()->setCellValue($columnLetter . $titleNum, $titleName);
                $columnIndex++;
                unset($titleName);
            }
                
            foreach ($data as $item) {
                $columnIndex = 1;
                foreach ($item as $key => $itemSon) {
                    $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                    $spreadsheet->getActiveSheet()->setCellValue($columnLetter . $dataNum, $itemSon);
                    $columnIndex++;
                }
                $dataNum++;
                unset($item);
            }
            unset($data);
            $tmpName = $this->downPath . $downFileName;
            $writer = new WriteXls($spreadsheet);
            $writer->save($tmpName);
        }
    }

    /**
     * 导出xlsx文件
     * @param $customHeaders
     * @param $list
     * @param string $fileName
     * @return false|string
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function downloadXlsx($customHeaders,$list,$fileName = "")
    {
        if (empty($fileName)){
            $fileName  = "默认导出文件_".date("YmdHis").".xlsx";
        }
        // 创建一个新的 Spreadsheet 对象
        $spreadsheet = new Spreadsheet();

        // 设置当前活动的工作表
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头 (PhpSpreadsheet使用1-based索引)
        $columnIndex = 1;

        foreach ($customHeaders as $header) {
            // 设置自定义表头
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 2; // 从第二行开始填充数据
        foreach ($list as $row) {
            $columnIndex = 1;
            foreach ($row as $cellValue) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cellValue);
                $columnIndex++;
            }
            $rowIndex++;
        }

        // 设置文件格式和保存路径
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        // 保存文件到指定路径
        $filePath = $this->downPath ."{$fileName}";
        $writer->save($filePath);

        return $filePath;
    }


    /**
     * 读取 xls 文件
     * @param $fileName
     * @return array
     * @throws Exception
     */
    public function _readXlsFile($fileName)
    {
        $returnArray = array();
        $spreadsheet = IOFactory::load($fileName);
        
        // PhpSpreadsheet有更好的内存管理，不需要手动设置缓存
        
        $sheetNames = $spreadsheet->getSheetNames();
        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $spreadsheet->getSheet($sheetId);
            $highestColumn = $sheet->getHighestColumn(); // 获取最后一列的列名
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // 取得excel中的列数

            $columnArray = array();
            for ($excelColumnIndex = 1; $excelColumnIndex <= $highestColumnIndex; $excelColumnIndex++) { // 改为1-based索引
                $columnArray[] = trim($sheet->getCellByColumnAndRow($excelColumnIndex, 1)->getValue());
            }

            $rowCount = $sheet->getHighestRow(); // 行数
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    // 注意：这里$key需要+1因为columnArray是从0开始的索引
                    $columnIndex = $key + 1;
                    $value = trim($sheet->getCellByColumnAndRow($columnIndex, $j)->getValue());
                    $data[$columnName] = $value;
                }
                $sheetData[] = $data;
            }
            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 读取csv文件数据
     * @param $filename
     * @param string $sheet
     * @return array|mixed
     * @throws Exception
     */
    public function getXlsxData($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFile($filename);
        if (sizeof($fileContent) == 1){
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }else{
            return $fileContent;
        }
    }

    public function getXlsxDataV2($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFileV2($filename);
        if (sizeof($fileContent) == 1){
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }else{
            return $fileContent;
        }
    }

    /**
     * 读取json文件数据
     * @param $filename
     * @return mixed|null
     */
    private function getJsonDate($filename)
    {
        $json_content = null;
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $json_content = json_decode($content, true);
        }
        return $json_content;
    }


//    public function _readXlsFileV2($fileName){
//        // 载入 Excel 文件
//        $spreadsheet = IOFactory::load($fileName);
//        $worksheet = $spreadsheet->getActiveSheet();
//
//        if (count($worksheet->toArray()) == 0){
//            return [];
//        }
//
//        $headerArray = $worksheet->toArray()[0];
//        $list = [];
//        if (count($worksheet->toArray()) >= 1){
//            for ($index = 1;$index < count($worksheet->toArray());$index++){
//                $list[] = array_combine($headerArray,$worksheet->toArray()[$index]);
//            }
//        }
//        return $list;
//    }


    public function _readCSV($csvPath)
    {
        try {
            $reader = new ReaderCsv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');
                
            // PhpSpreadsheet没有setRowIteratorStart方法，需要手动处理
            $spreadsheet = $reader->load($csvPath);
            $sheet = $spreadsheet->getActiveSheet();
    
            // 获取所有行数据
            $allRows = $sheet->toArray();
                
            if (empty($allRows)) {
                return [];
            }
    
            // 假设第2行是标题行（索引1）
            $headerRowIndex = 1;
            if (count($allRows) <= $headerRowIndex) {
                return [];
            }
                
            $headerKeys = $allRows[$headerRowIndex];
                
            // 强制指定需要文本格式的列（D/E列对应索引3,4）
            $textColumnIndexes = [3, 4]; // 0-based索引
    
            $data = [];
                
            // 从第3行开始读取数据（索引2）
            for ($rowIndex = 2; $rowIndex < count($allRows); $rowIndex++) {
                $rowData = [];
                $rowValues = $allRows[$rowIndex];
                    
                foreach ($rowValues as $colIndex => $cellValue) {
                    $key = $headerKeys[$colIndex] ?? 'Column' . ($colIndex + 1);
    
                    // 针对D/E列强制文本格式读取
                    if (in_array($colIndex, $textColumnIndexes)) {
                        // 对于长数字，确保作为字符串处理
                        if (is_numeric($cellValue) && strlen((string)$cellValue) > 15) {
                            $value = (string)$cellValue;
                        } else {
                            $value = (string)$cellValue;
                        }
                    } else {
                        $value = $cellValue;
                    }
    
                    // 修复长数字问题
                    if (is_numeric($value) && strlen((string)$value) > 15) {
                        $value = (string)$value;
                    }
    
                    $rowData[$key] = $value;
                }
                $data[] = $rowData;
            }
    
            return $data;
        } catch (Exception $e) {
            die("读取CSV失败: " . $e->getMessage());
        }
    }



    public function _readXlsFileV2($fileName)
    {
        $returnArray = array();

        // PhpSpreadsheet有更好的内存管理，不需要手动设置缓存

        // 加载文件
        $spreadsheet = IOFactory::load($fileName);

        // 获取所有工作表名称
        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $spreadsheet->getSheet($sheetId);

            // 获取列数和行数
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            $rowCount = $sheet->getHighestRow();

            // 读取列标题（第一行）
            $columnArray = array();
            for ($excelColumnIndex = 1; $excelColumnIndex <= $highestColumnIndex; $excelColumnIndex++) { // 改为1-based索引
                $cell = $sheet->getCellByColumnAndRow($excelColumnIndex, 1);
                $columnArray[] = $this->_getCellValueV2($cell);
            }

            // 读取数据行（从第二行开始）
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    // 注意：这里$key需要+1因为columnArray是从0开始的索引
                    $columnIndex = $key + 1;
                    $cell = $sheet->getCellByColumnAndRow($columnIndex, $j);
                    $data[$columnName] = $this->_getCellValueV2($cell);
                }
                $sheetData[] = $data;
            }

            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 获取单元格值，处理长数字不转为科学计数法
     * @param \PhpOffice\PhpSpreadsheet\Cell\Cell $cell 单元格对象
     * @return mixed 处理后的值
     */
    protected function _getCellValueV2($cell)
    {
        $value = $cell->getValue();

        // 处理富文本
        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        // 处理长数字
        if (is_numeric($value)) {
            // 获取单元格格式
            $format = $cell->getStyle()->getNumberFormat()->getFormatCode();

            // 如果是常规格式且数字长度超过10位，转为字符串保持原样
            if ($format == NumberFormat::FORMAT_GENERAL &&
                strlen((string)$value) > 10) {
                return (string)$value;
            }

            // 如果是文本格式，直接返回字符串形式
            if ($format == NumberFormat::FORMAT_TEXT) {
                return (string)$value;
            }
        }

        // 去除前后空格
        return is_string($value) ? trim($value) : $value;
    }



}