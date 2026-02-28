<?php
namespace App\Controller;

use App\Helper\ExcelUtils;
use App\Helper\DataUtils;

class upload
{
    private $target_dir;

    public function __construct($target_dir) {
        $this->target_dir = $target_dir;
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir, 0777, true);
        }
    }

    public function upload($file) {
        // 检查是否有文件被上传
        if ($file['error'] > 0) {
            return [
                "code" => 400,
                "message" => "错误: " . $file['error'],
                "fileName" => null,
                "excelList" => []
            ];
        } else {
            // 获取文件名
            $file_name = basename($file['name']);
            if (!in_array(pathinfo($file_name, PATHINFO_EXTENSION),["xlsx","xls"])){
                return [
                    "code" => 200,
                    "message" => "上传文件必须为xlsx或xls类型",
                    "fileName" => "",
                    "excelList" => []
                ];
            }
            // 生成唯一的文件名
            $unique_name = uniqid() . '-' . $file_name;
            $target_file = $this->target_dir . $unique_name;

            // 移动文件到指定目录
            if (move_uploaded_file($file['tmp_name'], $target_file)) {

                $excel = new ExcelUtils();
                $excelList = $excel->_readXlsFile("../export/uploads/{$unique_name}");
                $list = reset($excelList);
                return [
                    "code" => 200,
                    "message" => "上传文件成功",
                    "fileName" => $unique_name,
                    "excelList" => $list
                ];
            } else {
                return [
                    "code" => 400,
                    "message" => "上传文件出错",
                    "fileName" => null,
                    "excelList" => []
                ];
            }
        }
    }


    public function uploadOss($params){
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir, 0777, true);
        }
        if ($params) {

            $resultList = [];
            if (count($params['name']) > 0){
                for ($i = 0;$i < count($params['name']);$i++){

                    if ($params['error'][$i] > 0) {
                        return [
                            "code" => 400,
                            "message" => "错误: {$params['name'][$i]} " . $params['error'][$i],
                            "fileName" => null,
                            "excelList" => []
                        ];
                    } else {
                        // 获取文件名
                        $file_name = basename($params['name'][$i]);
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                        // 生成唯一的文件名
                        $fileName = DataUtils::buildGenerateUuidLike() . ".{$ext}";
                        $target_file = $this->target_dir . $file_name;

                        // 移动文件到指定目录
                        if (move_uploaded_file($params['tmp_name'][$i], $target_file)) {
                            $resultList[] = [
                                "actualFileName" => $file_name,
                                "fileName" => $fileName,
                                "fullPath" => $target_file,
                            ];
                        } else {
                            return [
                                "code" => 400,
                                "message" => "加载文件出错",
                                "fileName" => $file_name,
                                "excelList" => []
                            ];
                        }
                    }

                }

            }

            if (count($resultList) > 0){
                return [
                    "code" => 200,
                    "message" => "加载文件成功",
                    "fileCollect" => $resultList
                ];
            }

        }

        return true;
    }


}

// 使用示例
if (isset($_FILES['fileToUpload'])) {
    $target_dir = __DIR__ . "/../export/uploads/";
    $fileUploader = new upload($target_dir);

    $upload_result = $fileUploader->upload($_FILES['fileToUpload']);
    echo json_encode($upload_result,JSON_UNESCAPED_UNICODE);
}else if (isset($_FILES['fileToUploadOss'])){
    $target_dir = __DIR__ . "/../export/uploads/oss/";
    $fileUploader = new upload($target_dir);
    $return = $fileUploader->uploadOss($_FILES['fileToUploadOss']);
    echo json_encode($return,JSON_UNESCAPED_UNICODE);
}