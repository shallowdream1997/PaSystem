<?php

/**
 * 响应数据处理工具类 - 针对那些接口返回的数据格式做处理，封装好后直接用
 * Class DataUtils
 */
class DataUtils
{
    public function __construct(){

    }

    /**
     * 接收响应数据， 返回结果
     * @param null $response
     * @return array
     */
    public static function getResultData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] && isset($response['result']) && !empty($response['result'])){
            $data = $response['result'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回分页下的列表，不包含分页页码信息
     * @param null $response
     * @return array
     */
    public static function getPageList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']) > 0){
            $data = $response['result']['data'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回query查询下的全局列表，
     * @param null $response 响应内容
     * @return array
     */
    public static function getQueryList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']) && count($response['result']) > 0){
            $data = $response['result'];
        }
        return $data;
    }

    /**
     * node后端请求的分页接口里面，有一个特殊的返回格式，就是doc
     * @param null $response
     * @return array
     */
    public static function getPageDocList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data'])
            && isset($response['result']['data']['docs']) && count($response['result']['data']['docs']) > 0){
            $data = $response['result']['data']['docs'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回分页下的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageListInFirstData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']) > 0){
            $data = $response['result']['data'][0];
        }
        return $data;
    }
    /**
     * 接收响应数据，返回分页下的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageListInFirstDataV2($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']['data']) > 0){
            $data = $response['result']['data']['data'][0];
        }
        return $data;
    }
    /**
     * 取值
     * @param $data
     * @return array|mixed
     */
    public static function getOptionVal($data)
    {
        $info = [];
        if (self::checkArrFilesIsExist($data,'optionName')){
            $info = $data['optionVal'];
        }
        return $info;
    }

    /**
     * 获取数组里面的第一个值
     * @param array $list 数组
     * @return array
     */
    public static function getArrHeadData(array $list)
    {
        $data = [];
        if (!empty($list) && count($list) > 0){
            $data = $list[0];
        }
        return $data;
    }

    /**
     * 检查数组里面是否存在该字段且不为空，存在则true，不存在则false
     * @param array $array 数组
     * @param string $field 字段名称
     * @return bool
     */
    public static function checkArrFilesIsExist(array $array,string $field, $checkEmpty = true): bool
    {
        return (isset($array[$field]) && ($checkEmpty ? !empty($array[$field]) : true));
    }

    /**
     * 根据条件查询数组的下标以及下标所在的数据
     * @param array $array 数组
     * @param array $conditions 查询条件
     * @return array
     */
    public static function findIndexInArray(array $array,array $conditions)
    {
        // 使用array_filter根据多个条件过滤数组
        return array_filter($array, function ($item) use ($conditions) {
            foreach ($conditions as $key => $value) {
                if (!isset($item[$key])){
                    return false;
                }
                if ($item[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 检查数组里面是否存在该字段且等于指定值，存在则true，不存在则false
     * @param array $array 数组
     * @param string $field 字段名称
     * @param $value
     * @return bool
     */
    public static function checkArrFilesIsExistEqualValue(array $array,string $field, string $value): bool
    {
        return (isset($array[$field]) && $array[$field] == $value);
    }

    /**
     * 接收create方法响应数据， 返回主键Id
     * @param null $response
     * @return string
     */
    public static function getCreateReturnId($response = null): string
    {
        $data = "";
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result'])){
            $data = $response['result'];
            if (is_array($data)){
                $data = $data['_id'];
            }
        }
        return $data;
    }


    /**
     * 接收新架构的响应数据， 返回结果
     * @param null $response
     * @return array
     */
    public static function getNewResultData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] && isset($response['result']) && !empty($response['result']) && isset($response['result']['state']) && $response['result']['state']['code'] === 2000000){
            $data = $response['result']['data'];
        }
        return $data;
    }

    public static function jsonEncode($list,$option = JSON_UNESCAPED_UNICODE)
    {
        echo json_encode($list,$option);
    }

    /**
     * 去重数组对象重复数据
     * @param $array
     * @return array|mixed
     */
    public static function clearRepeatData($array){
        // 使用array_reduce和array_column去重
        $uniqueArray = array_reduce($array, function ($carry, $item) {
            // 创建一个用于比较的唯一键
            $key = md5(serialize($item));
            if (!isset($carry[$key])) {
                $carry[$key] = $item;
            }
            return $carry;
        }, []);
        // 将结果转换为数组
        $uniqueArray = array_values($uniqueArray);
        return $uniqueArray;
    }

    /**
     * 判断数组对象是否有重复，有重复就true，没有就false
     * @param $array
     * @return bool
     */
    public static function hasDuplicates($array) {
        $seen = [];
        foreach ($array as $item) {
            // 将对象转换为字符串，作为唯一标识
            $serializedItem = serialize($item);
            if (isset($seen[$serializedItem])) {
                // 如果已存在，则返回true
                return true;
            }
            // 将当前元素的序列化版本作为键存储，值为任意值
            $seen[$serializedItem] = true;
        }
        // 如果没有发现重复，则返回false
        return false;
    }

    /**
     * (记住！是数组对象，不是纯数组)从原数组对象 提取部分字段 拆解组合出 新的数组对象 并返回
     * @param $originArray
     * @param $fieldsToExtract
     * @return array|array[]
     */
    public static function arrayExtractSomeFilesCombineNewArray($originArray, $fieldsToExtract)
    {
        // 只获取这几个字段
        if (empty($fieldsToExtract)){
            return [];
        }
        // 使用 array_map 来创建一个新的数组对象，只包含特定的字段
        return array_map(function ($item) use ($fieldsToExtract) {
            return array_intersect_key($item, array_flip($fieldsToExtract));
        }, $originArray);
    }

    public static function buildGenerateUuidLike() {
        return bin2hex(random_bytes(16));
    }


}


