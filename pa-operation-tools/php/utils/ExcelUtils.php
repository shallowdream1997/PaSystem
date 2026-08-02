<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls as WriterXls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv as ReaderCsv;

/**
 * 导入导出文件工具类(基于 PhpOffice/PhpSpreadsheet,替代原 PHPExcel-1.8)
 * Class ExcelUtils
 *
 * 依赖说明:
 *  - PhpSpreadsheet 由 Composer 自动加载(vendor/autoload.php),无需手动 require;
 *  - 导出目录统一使用 PA_UPLOAD_PATH(php/export/uploads),自动创建并 chmod 777;
 *  - 本类方法签名与原 PHPExcel 版本完全一致,调用方无需改动。
 */
class ExcelUtils
{
    public $downPath;

    public function __construct($downPath = "")
    {
        $uploadBase = defined('PA_UPLOAD_PATH') ? PA_UPLOAD_PATH : (__DIR__ . "/../export/uploads");
        $this->downPath = !empty($downPath) ? $uploadBase . "/" . $downPath : $uploadBase . "/default/";
    }

    /**
     * 数据写入 xls 文件,下载文件
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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function download(array $titleList, array $data, $fileName = "")
    {
        $downDefaultFileName = "导出默认文件_" . date('YmdHis') . ".xlsx";
        $downFileName = !empty($fileName) ? $fileName : $downDefaultFileName;

        if (count($data) > 0) {
            $obj = new Spreadsheet();
            $obj->removeSheetByIndex(0);
            $index = 0;
            // 获取表头
            $obj->createSheet();
            $obj->setActiveSheetIndex($index);
            $obj->getActiveSheet()->setTitle('Sheet' . ($index + 1));
            $titleNum = 1;
            $dataNum = 2;
            $keyNum = 'A';
            foreach ($data[0] as $key => $item) {
                $titleName = isset($titleList[$key]) ? $titleList[$key] : $key;
                $obj->getActiveSheet()->setCellValue($keyNum . $titleNum, $titleName);
                $keyNum++;
            }
            foreach ($data as $item) {
                $keyNum = 'A';
                foreach ($item as $key => $itemSon) {
                    $obj->getActiveSheet()->setCellValue($keyNum . $dataNum, $itemSon);
                    $keyNum++;
                }
                $dataNum++;
            }
            $tmpName = $this->downPath . $downFileName;
            paEnsureDir($this->downPath); // 自动创建目录 + chmod 777
            $objWriter = new WriterXls($obj);
            $objWriter->save($tmpName);
            @chmod($tmpName, 0777); // 导出文件自动 777
        }
    }

    /**
     * 导出 xlsx 文件
     * @param $customHeaders
     * @param $list
     * @param string $fileName
     * @return false|string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function downloadXlsx($customHeaders, $list, $fileName = "")
    {
        if (empty($fileName)) {
            $fileName = "默认导出文件_" . date("YmdHis") . ".xlsx";
        }
        // 创建一个新的 Spreadsheet 对象
        $objPHPExcel = new Spreadsheet();

        // 设置当前活动的工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Sheet1'); // 对齐旧 PHPExcel 默认表名,保证 getXlsxData 默认参数可用

        // 设置表头
        $columnIndex = 0;
        foreach ($customHeaders as $header) {
            // 注意:PhpSpreadsheet 列索引为 1-based(Coordinate::stringFromColumnIndex),故 +1
            $sheet->setCellValueByColumnAndRow($columnIndex + 1, 1, $header);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 2; // 从第二行开始填充数据
        foreach ($list as $row) {
            $columnIndex = 0;
            foreach ($row as $cellValue) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex, $cellValue);
                $columnIndex++;
            }
            $rowIndex++;
        }

        // 设置文件格式和保存路径
        paEnsureDir($this->downPath); // 自动创建目录 + chmod 777
        $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $filePath = $this->downPath . $fileName;
        $objWriter->save($filePath);
        @chmod($filePath, 0777); // 导出文件自动 777

        return $filePath;
    }

    /**
     * 读取 xls/xlsx 文件(返回 [sheetName => [列名 => 值, ...], ...])
     * @param $fileName
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function _readXlsFile($fileName)
    {
        $returnArray = array();
        // 原 PHPExcel 的 phpTemp 1024MB 缓存由 PhpSpreadsheet 默认内存缓存替代(功能不受影响)
        $objPHPExcel = IOFactory::load($fileName);
        $sheetNames = $objPHPExcel->getSheetNames();
        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $objPHPExcel->getSheet($sheetId);
            $highestColumn = $sheet->getHighestColumn(); // 获取最后一列的列名
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // 取得 excel 中的列数

            $columnArray = array();
            for ($excelColumnIndex = 1; $excelColumnIndex <= $highestColumnIndex; $excelColumnIndex++) {
                // 注意:PhpSpreadsheet 列索引为 1-based(Coordinate::stringFromColumnIndex),故从 1 开始
                $columnArray[] = trim($sheet->getCellByColumnAndRow($excelColumnIndex, 1)->getValue());
            }

            $rowCount = $sheet->getHighestRow(); // 行数
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $value = trim($sheet->getCellByColumnAndRow($key + 1, $j)->getValue());
                    $data[$columnName] = $value;
                }
                $sheetData[] = $data;
            }
            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 读取 csv 文件数据
     * @param $filename
     * @param string $sheet
     * @return array|mixed
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function getXlsxData($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFile($filename);
        if (sizeof($fileContent) == 1) {
            // 只有一个 sheet 时直接返回其数据(sheet 名可能为 Sheet1/Worksheet 等)
            return reset($fileContent);
        } else {
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }
    }

    /**
     * 读取 json 文件数据
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

    /**
     * 读取 CSV 文件(行为与原版一致:跳过第 1 行空数据,第 2 行为标题,第 3 行起为数据)
     * @param $csvPath
     * @return array
     */
    public function _readCSV($csvPath)
    {
        try {
            $reader = new ReaderCsv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');

            $spreadsheet = $reader->load($csvPath);
            $sheet = $spreadsheet->getActiveSheet();

            // 强制指定需要文本格式的列(D/E 列的 adgroup_id)
            $textColumns = ['D', 'E'];

            $headerKeys = [];
            $data = [];
            $rowNum = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;
                if ($rowNum === 1) {
                    continue; // 跳过首行空数据
                }

                $rowData = [];
                foreach ($row->getCellIterator() as $col => $cell) {
                    if ($rowNum === 2) {
                        // 读取真实标题行(第 2 行)
                        $headerKeys[] = $cell->getValue(); // 标题如 campaign_id, adgroup_name 等
                        continue;
                    }

                    $key = $headerKeys[Coordinate::columnIndexFromString($col) - 1] ?? $col;

                    // 针对 D/E 列强制文本格式读取
                    if (in_array($col, $textColumns)) {
                        $value = $cell->getFormattedValue(); // 直接获取显示值(如 5.48474E+14 原文)
                        $value = (string)$value;
                    } else {
                        $value = $cell->getValue();
                    }

                    // 修复图片中数字粘连问题(如 311196306576001411arrc250326)
                    if (is_numeric($value) && strlen($value) > 15) {
                        $value = (string)$value;
                    }

                    $rowData[$key] = $value;
                }
                if ($rowNum > 2) {
                    $data[] = $rowData;
                }
            }

            return $data;
        } catch (\Exception $e) {
            die("读取CSV失败: " . $e->getMessage());
        }
    }
}
