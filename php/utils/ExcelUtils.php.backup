<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . "/../../extends/PHPExcel-1.8/Classes/PHPExcel.php");

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriteXlsx;
//use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
//use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 导入导出文件工具类
 * Class ExcelUtils
 */
class ExcelUtils
{
    public $downPath;

    public function __construct($downPath = "")
    {
        $downDefaultFile = __DIR__ . "/../export/uploads/";
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
            $obj = new \PHPExcel();
            $obj->removeSheetByIndex(0);
            $index = 0;
            // 获取表头
            $title = array();
            $obj->createSheet();
            $obj->setActiveSheetIndex($index);
            $obj->getActiveSheet($index)->setTitle('Sheet' . ($index + 1));
            $titleNum = 1;
            $dataNum = 2;
            $keyNum = 'A';
            $checkTitle = array();
            foreach ($data[0] as $key => $item) {
                $titleName = isset($titleList[$key]) ? $titleList[$key] : $key;
                $obj->getActiveSheet($index)->setCellValue($keyNum . $titleNum, $titleName);
                $keyNum++;
                unset($titleName);
            }
            foreach ($data as $item) {
                $keyNum = 'A';
                foreach ($item as $key => $itemSon) {
                    $obj->getActiveSheet($index)->setCellValue($keyNum . $dataNum, $itemSon);
                    $keyNum++;
                }
                $dataNum++;
                unset($item);
            }
            unset($data);
            $tmpName = $this->downPath . $downFileName;
            $objWriter = new \PHPExcel_Writer_Excel5($obj);
            $objWriter->save($tmpName);

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
        // 创建一个新的 PHPExcel 对象
        $objPHPExcel = new PHPExcel();

        // 设置当前活动的工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // 设置表头
        $columnIndex = 0;

        foreach ($customHeaders as $header) {
            // 设置自定义表头
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 2; // 从第二行开始填充数据
        foreach ($list as $row) {
            $columnIndex = 0;
            foreach ($row as $cellValue) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cellValue);
                $columnIndex++;
            }
            $rowIndex++;
        }

        // 设置文件格式和保存路径
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        // 保存文件到指定路径
        $filePath = $this->downPath ."{$fileName}";
        $objWriter->save($filePath);

        return $filePath;

        //// 保存文件
        //$objWriter->save($this->downPath ."{$fileName}_".date("YmdHis").".xlsx");

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
        $objPHPExcel = PHPExcel_IOFactory::load($fileName);
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array(
            'memoryCacheSize' => '1024MB'
        );
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $sheetNames = $objPHPExcel->getSheetNames();
        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $objPHPExcel->getSheet($sheetId);
            $highestColumn = $sheet->getHighestColumn(); // 获取最后一列的列名
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // 取得excel中的列数

            $columnArray = array();
            for ($excelColumnIndex = 0; $excelColumnIndex < $highestColumnIndex; $excelColumnIndex++) {
                $columnArray[] = trim($sheet->getCellByColumnAndRow($excelColumnIndex, 1)->getValue());
            }

            $rowCount = $sheet->getHighestRow(); // 行数
            for ($j = 2; $j <= $rowCount; $j++) {
                $emptyColumn = 0;
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $value = trim($sheet->getCellByColumnAndRow($key, $j)->getValue());
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
            $reader = new PHPExcel_Reader_CSV();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');

            // 指定从第2行开始读取标题（跳过首行空数据）
            $reader->setRowIteratorStart(2);

            $phpExcel = $reader->load($csvPath);
            $sheet = $phpExcel->getActiveSheet();

            // 读取真实标题行（图片中的第2行）
            $headerRow = $sheet->getRowIterator()->current();
            $headerKeys = [];
            foreach ($headerRow->getCellIterator() as $cell) {
                $headerKeys[] = $cell->getValue(); // 标题如campaign_id, adgroup_name等
            }

            // 强制指定需要文本格式的列（D/E列的adgroup_id）
            $textColumns = ['D', 'E'];

            $data = [];
            $rowIterator = $sheet->getRowIterator();
            $rowIterator->resetStart(3); // 数据从第3行开始

            foreach ($rowIterator as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $col => $cell) {
                    $key = $headerKeys[PHPExcel_Cell::columnIndexFromString($col) - 1] ?? $col;

                    // 针对D/E列强制文本格式读取
                    if (in_array($col, $textColumns)) {
                        $value = $cell->getFormattedValue(); // 直接获取显示值（如5.48474E+14原文）
                        $value = (string)$value;
                    } else {
                        $value = $cell->getValue();
                    }

                    // 修复图片中数字粘连问题（如311196306576001411arrc250326）
                    if (is_numeric($value) && strlen($value) > 15) {
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

        // 设置缓存以提高大文件处理性能
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '1024MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        // 加载文件
        $objPHPExcel = PHPExcel_IOFactory::load($fileName);

        // 获取所有工作表名称
        $sheetNames = $objPHPExcel->getSheetNames();

        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $objPHPExcel->getSheet($sheetId);

            // 获取列数和行数
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
            $rowCount = $sheet->getHighestRow();

            // 读取列标题（第一行）
            $columnArray = array();
            for ($excelColumnIndex = 0; $excelColumnIndex < $highestColumnIndex; $excelColumnIndex++) {
                $cell = $sheet->getCellByColumnAndRow($excelColumnIndex, 1);
                $columnArray[] = $this->_getCellValue($cell);
            }

            // 读取数据行（从第二行开始）
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $cell = $sheet->getCellByColumnAndRow($key, $j);
                    $data[$columnName] = $this->_getCellValue($cell);
                }
                $sheetData[] = $data;
            }

            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 获取单元格值，处理长数字不转为科学计数法
     * @param PHPExcel_Cell $cell 单元格对象
     * @return mixed 处理后的值
     */
    protected function _getCellValue(PHPExcel_Cell $cell)
    {
        $value = $cell->getValue();

        // 处理富文本
        if ($value instanceof PHPExcel_RichText) {
            $value = $value->getPlainText();
        }

        // 处理长数字
        if (is_numeric($value)) {
            // 获取单元格格式
            $format = $cell->getStyle()->getNumberFormat()->getFormatCode();

            // 如果是常规格式且数字长度超过10位，转为字符串保持原样
            if ($format == PHPExcel_Style_NumberFormat::FORMAT_GENERAL &&
                strlen((string)$value) > 10) {
                return (string)$value;
            }

            // 如果是文本格式，直接返回字符串形式
            if ($format == PHPExcel_Style_NumberFormat::FORMAT_TEXT) {
                return (string)$value;
            }
        }

        // 去除前后空格
        return is_string($value) ? trim($value) : $value;
    }



}