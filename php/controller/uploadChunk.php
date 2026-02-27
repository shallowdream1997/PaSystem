<?php
require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';

class uploadChunk
{
    private $target_dir;

    public function __construct($target_dir) {
        $this->target_dir = $target_dir;
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir, 0777, true);
        }
    }

    public function chunkUpload()
    {
        $filename = $_POST['filename'];
        $chunkIndex = (int)$_POST['chunkIndex'];
        $totalChunks = (int)$_POST['totalChunks'];
        $Name = $_POST['name'];

        // 创建临时目录
        $tempDir = $this->target_dir . "temp/";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // 保存当前片段
        if (isset($_FILES['file'])) {
            move_uploaded_file($_FILES['file']['tmp_name'], $tempDir . $filename . '.part' . $chunkIndex);
        }

        // 检查是否所有片段都已上传
        $resultList = [];
        if ($chunkIndex + 1 === $totalChunks) {
            $file_name = basename($Name);

            // 生成新的文件名
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            // 生成唯一的文件名
            $fileName = DataUtils::buildGenerateUuidLike() . ".{$ext}";

            $finalFilePath = $this->target_dir . $fileName;
            $out = fopen($finalFilePath, 'wb');

            for ($i = 0; $i < $totalChunks; $i++) {
                $partFilePath = $tempDir . $filename . '.part' . $i;
                if (file_exists($partFilePath)) {
                    $in = fopen($partFilePath, 'rb');
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                    unlink($partFilePath); // 删除临时片段
                }
            }

            fclose($out);
            rmdir($tempDir); // 删除临时目录

            // 返回保存文件的路径
            echo json_encode(['success' => true, 'message' => '文件上传成功', 'filePath' => $finalFilePath]);

            $resultList[] = [
                "actualFileName" => $file_name,
                "fileName" => $fileName,
                "fullPath" => $finalFilePath,
            ];
        }

        if (count($resultList) > 0){
            return [
                "code" => 200,
                "message" => "加载文件成功",
                "fileCollect" => $resultList
            ];
        }

    }

}

// 使用示例
$target_dir = __DIR__ . "/../export/uploads/oss/";
$fileUploader = new uploadChunk($target_dir);
$return = $fileUploader->chunkUpload();
echo json_encode($return,JSON_UNESCAPED_UNICODE);