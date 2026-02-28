<?php
namespace App\Helper;

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
     * 接收响应数据，返回query的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getQueryListInFirstDataV3($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && count($response['result']) > 0){
            $data = $response['result'][0];
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
     * 根据条件查询数组的下标以及下标所在的数据
     * @param array $array 数组
     * @param array $conditions 查询条件
     * @return array
     */
    public static function findIndexDataInArray(array $array,array $conditions)
    {
        // 使用array_filter根据多个条件过滤数组
        return array_filter($array, function ($item) use ($conditions) {
            foreach ($conditions as $key => $value) {
                if (!isset($item[$key])){
                    return [];
                }
                if ($item[$key] !== $value) {
                    return [];
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


    /**
     * 解释变量的标准程序
     * @author hello
     * @createdOn 2010-04-19
     *
     * 如果输入 php test.php -a v1 v2 -b v3 -t vt
     * 可以通过公共的函数，解释为:
     *
     * $params = array(
     * 		"a" => array("v1", "v2"),
     * 		"b" => "v3",
     * 		"t" => "vt",
     * 	);
     *
     * 注意，如果输入 php test.php -a v2 -b v3 -t vt
     * 则会变成:
     * $params = array(
     * 		"a" =>"v1",
     * 		"b" => "v3",
     * 		"t" => "vt",
     * 	);
     *
     * 正确的应该是 "a"=>array("v1"),
     * 不过由于无法预知　a　对应的是一个还是多个，所以，只能如此设定。
     * 如果需要，请使用者自行转换一下。
     *
     * e.g.
     * if (array_key_exists("a", $params) && !is_array($params["a"])){
     * 		$value = $params["a"];
     * 		$params["a"] = array($value);
     * }
     *
     * 如果确实需要（比如需要转换的比较多），那么可以带一个参数 keyNamesWhichIsArrayValue
     * e.g. :
     * $re = explainArgv($argv,	array(
     * 								"keyNamesWhichIsArrayValue" => array("a")
     * 							)
     * 					);
     *
     * $options:
     * 		keyNamesWhichIsArrayValue : 要求数值必须是array的key列表
     *
     * @param array $argv
     * @param array $options
     * @return array
     */
    public static function explainArgv($argv, $options = array()){
        $re = array();
        $count = count($argv);
        if ($count <= 1) return $re; // no params($argv[0]　is the execute filename, ignore it.)

        // explain argv
        $index = -1;
        $keyName = "";
        foreach($argv as $value){
            $index ++;
            if ($index == 0) continue; // $argv[0]　is the execute filename, ignore it.

            $keyFlag = substr($value, 0, 1);
            if ($keyFlag == "-"){
                $keyName = substr($value, 1);
                $re[$keyName] = "";
                continue;
            }
            if ($keyName == "") continue;

            if ($re[$keyName] == ""){ 			// not set
                $re[$keyName] = $value;
            }else if (is_array($re[$keyName])){	// already is array
                array_push($re[$keyName], $value);
            }else{
                $arr = array($re[$keyName]);	// single value to array value
                array_push($arr, $value);
                $re[$keyName] = $arr;
            }
        }

        // repair data
        if (array_key_exists("keyNamesWhichIsArrayValue", $options)){
            foreach ($options["keyNamesWhichIsArrayValue"] as $keyName){
                if (array_key_exists($keyName, $re) && !is_array($re[$keyName])){
                    $value = $re[$keyName];
                    $re[$keyName] = array($value);
                }
            }
        }

        return $re;
    }

    /**
     * node后端请求的分页接口里面，有一个特殊的返回格式，就是doc,返回第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageDocListInFirstDataV1($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data'])
            && isset($response['result']['data']['docs']) && count($response['result']['data']['docs']) > 0){
            $data = $response['result']['data']['docs'][0];
        }
        return $data;
    }



    public static function parseAndTransformQdLogList($qdLogList) {
        $actionMap = [
            'DISABLE' => '作废',
            'REPUBLISH' => '重新发布',
            'REASSIGN' => '重新分配',
            'PUBLISH_CREATE' => '清单发布',
            'ASSIGN_SUPPLIER' => '清单指定',
        ];

        $result = [];

        foreach ($qdLogList as $log) {
            // === 1. 解析 opRemark 获取 action 和初始 reason ===
            $opRemark = $log['opRemark'] ?? '';
            $parts = explode(';', $opRemark, 2);
            $rawAction = trim($parts[0] ?? '');
            $reason = isset($parts[1]) ? trim($parts[1]) : '';

            // === 2. 解析 opAfterContent 的变更项 ===
            $opAfterContent = $log['opAfterContent'] ?? '';
            $changes = [];

            // 使用非贪婪正则，匹配：字段名:【旧值】->【新值】
            // 支持中英文冒号，字段名允许任意字符（除冒号）
            preg_match_all('/([^:：]+?)[：:]\s*【([^】]*?)】->【([^】]*?)】/', $opAfterContent, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                // 清理字段名：去除控制字符、零宽字符、多余空白
                if (strpos($match[1],"寄卖清单发布竞标单主键ID") !== false){
                    $field = "寄卖清单发布竞标单主键ID";
                }else{
                    $field = preg_replace('/[\x00-\x1f\x7f\p{C}]/u', '', $match[1]); // 移除控制/不可见字符
                }
                $field = trim($field);
                $from = ($match[2] === 'null') ? null : $match[2];
                $to   = ($match[3] === 'null') ? null : $match[3];

                $changes[$field] = ['from' => $from, 'to' => $to];
            }

            // === 3. 特殊处理：若存在“QD单作废原因”且 to 非空，覆盖 reason ===
            if (isset($changes['QD单作废原因']) && !empty($changes['QD单作废原因']['to'])) {
                $reason = $changes['QD单作废原因']['to'];
            }

            // === 4. 映射 action ===
            $action = $actionMap[$rawAction] ?? $rawAction;

            // === 5. 安全提取特定字段（支持字段名模糊匹配）===
            $getFieldValue = function ($fieldName, $key) use ($changes) {
                // 精确匹配
                if (isset($changes[$fieldName])) {
                    return $changes[$fieldName][$key] ?? null;
                }

                // 模糊匹配：如果字段名包含关键词（应对可能的空格/不可见字符）
                foreach ($changes as $f => $vals) {
                    if (strpos($f, '集团id') !== false && strpos($fieldName, '集团id') !== false) {
                        return $vals[$key] ?? null;
                    }
                    if (strpos($f, '供应商id') !== false && strpos($fieldName, '供应商id') !== false) {
                        return $vals[$key] ?? null;
                    }
                    if (strpos($f, '竞标单号') !== false && strpos($fieldName, '竞标单号') !== false) {
                        return $vals[$key] ?? null;
                    }
                    if (strpos($f, '竞标单主键ID') !== false && strpos($f, '竞标单主键ID') !== false) {
                        // 匹配“最新轮次...主键ID”类字段
                        if (strpos($fieldName, '竞标单主键ID') !== false) {
                            return $vals[$key] ?? null;
                        }
                    }
                }
                return null;
            };

            // 提取各字段
            $beforeGroupId                          = $getFieldValue('集团id', 'from');
            $afterGroupId                           = $getFieldValue('集团id', 'to');
            $beforeSupplierId                       = $getFieldValue('供应商id', 'from');
            $afterSupplierId                        = $getFieldValue('供应商id', 'to');
            $beforeBidBillNo                        = $getFieldValue('竞标单号', 'from');
            $afterBidBillNo                         = $getFieldValue('竞标单号', 'to');
            $beforeConsignmentQdPublishRecordId     = $getFieldValue('最新轮次的寄卖清单发布竞标单主键ID', 'from');
            $afterConsignmentQdPublishRecordId      = $getFieldValue('最新轮次的寄卖清单发布竞标单主键ID', 'to');

            // === 6. 转换 createTime（毫秒时间戳 → Y-m-d H:i:s）===
            $createTimeMs = $log['createTime'] ?? 0;
            $createTime = is_numeric($createTimeMs)
                ? date('Y-m-d H:i:s', (int)($createTimeMs / 1000))
                : null;

            // === 7. 构建最终结果 ===
            $result[] = [
                'action'                                    => $action,
                'remark'                                    => $reason,
                'beforeGroupId'                             => $beforeGroupId,
                'afterGroupId'                              => $afterGroupId,
                'beforeSupplierId'                          => $beforeSupplierId,
                'afterSupplierId'                           => $afterSupplierId,
                'beforeBidBillNo'                           => $beforeBidBillNo,
                'afterBidBillNo'                            => $afterBidBillNo,
                'beforeConsignmentQdPublishRecordId'        => $beforeConsignmentQdPublishRecordId,
                'afterConsignmentQdPublishRecordId'         => $afterConsignmentQdPublishRecordId,
                'createBy'                                  => $log['createBy'] ?? null,
                'createTime'                                => $createTime,
            ];
        }

        return $result;
    }


    /**
     * 对已解析的日志列表进行二次整理
     *
     * @param array $logActionList 已结构化的日志列表（含 action, remark, createTime 等）
     * @return array 整理后的日志列表
     */
    public static function refineLogActionList($logActionList) {
        // 1. 按 createTime 升序排序（最早在前）
        usort($logActionList, function ($a, $b) {
            return strtotime($a['createTime']) <=> strtotime($b['createTime']);
        });

        $count = count($logActionList);

        for ($i = 0; $i < $count; $i++) {
            $current = &$logActionList[$i];

            if ($current['action'] === '重新发布') {
                if ($current['createBy'] === 'ConsignmentWorkFlow') {
                    $current['remark'] = '因寄卖商未参与竞标且满足重新发布条件，清单自动发布';
                } else {
                    $current['remark'] = '操作了重新发布';
                }

            } elseif ($current['action'] === '作废') {
                // 作废：只向前找 after... 上下文
                $context = null;
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prev = $logActionList[$j];
                    if (
                        !empty($prev['afterConsignmentQdPublishRecordId']) &&
                        !empty($prev['afterBidBillNo'])
                    ) {
                        $context = $prev;
                        break;
                    }
                }
                if ($context) {
                    $current['beforeConsignmentQdPublishRecordId'] = $context['afterConsignmentQdPublishRecordId'];
                    $current['beforeBidBillNo'] = $context['afterBidBillNo'];
                }

            } elseif ($current['action'] === '重新分配') {
                $filled = false;

                // 第一步：向前找（历史）—— 使用 after...
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prev = $logActionList[$j];
                    if (
                        !empty($prev['afterConsignmentQdPublishRecordId']) &&
                        !empty($prev['afterBidBillNo'])
                    ) {
                        $current['beforeConsignmentQdPublishRecordId'] = $prev['afterConsignmentQdPublishRecordId'];
                        $current['afterConsignmentQdPublishRecordId']  = $prev['afterConsignmentQdPublishRecordId'];
                        $current['beforeBidBillNo'] = $prev['afterBidBillNo'];
                        $current['afterBidBillNo']  = $prev['afterBidBillNo'];
                        $current['remark'] = '操作了重新分配';
                        $filled = true;
                        break;
                    }
                }

                // 第二步：如果没填上，向后找（未来）—— 使用 before...
                if (!$filled) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $next = $logActionList[$j];
                        if (
                            !empty($next['beforeConsignmentQdPublishRecordId']) &&
                            !empty($next['beforeBidBillNo'])
                        ) {
                            $current['beforeConsignmentQdPublishRecordId'] = $next['beforeConsignmentQdPublishRecordId'];
                            $current['afterConsignmentQdPublishRecordId']  = $next['beforeConsignmentQdPublishRecordId'];
                            $current['beforeBidBillNo'] = $next['beforeBidBillNo'];
                            $current['afterBidBillNo']  = $next['beforeBidBillNo'];
                            $current['remark'] = '操作了重新分配';
                            break; // 找到最近的一条即可
                        }
                    }
                }
            }
        }

        return $logActionList;
    }


    public static function refineLogActionListV2($logActionList) {
        // 1. 按 createTime 升序排序（最早在前）
        usort($logActionList, function ($a, $b) {
            return strtotime($a['createTime']) <=> strtotime($b['createTime']);
        });

        $count = count($logActionList);

        // 2. 原有逻辑：处理 remark、重新分配、作废等
        for ($i = 0; $i < $count; $i++) {
            $current = &$logActionList[$i];

            if ($current['action'] === '重新发布') {
                if ($current['createBy'] === 'ConsignmentWorkFlow') {
                    $current['remark'] = '因寄卖商未参与竞标且满足重新发布条件，清单自动发布';
                } else {
                    $current['remark'] = '操作了重新发布';
                }

            } elseif ($current['action'] === '作废') {
                $context = null;
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prev = $logActionList[$j];
                    if (
                        !empty($prev['afterConsignmentQdPublishRecordId']) &&
                        !empty($prev['afterBidBillNo'])
                    ) {
                        $context = $prev;
                        break;
                    }
                }
                if ($context) {
                    $current['beforeConsignmentQdPublishRecordId'] = $context['afterConsignmentQdPublishRecordId'];
                    $current['beforeBidBillNo'] = $context['afterBidBillNo'];
                }

            } elseif ($current['action'] === '重新分配') {
                $filled = false;

                // 向前找
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prev = $logActionList[$j];
                    if (
                        !empty($prev['afterConsignmentQdPublishRecordId']) &&
                        !empty($prev['afterBidBillNo'])
                    ) {
                        $current['beforeConsignmentQdPublishRecordId'] = $prev['afterConsignmentQdPublishRecordId'];
                        $current['afterConsignmentQdPublishRecordId']  = $prev['afterConsignmentQdPublishRecordId'];
                        $current['beforeBidBillNo'] = $prev['afterBidBillNo'];
                        $current['afterBidBillNo']  = $prev['afterBidBillNo'];
                        $current['remark'] = '操作了重新分配';
                        $filled = true;
                        break;
                    }
                }

                // 向后找
                if (!$filled) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $next = $logActionList[$j];
                        if (
                            !empty($next['beforeConsignmentQdPublishRecordId']) &&
                            !empty($next['beforeBidBillNo'])
                        ) {
                            $current['beforeConsignmentQdPublishRecordId'] = $next['beforeConsignmentQdPublishRecordId'];
                            $current['afterConsignmentQdPublishRecordId']  = $next['beforeConsignmentQdPublishRecordId'];
                            $current['beforeBidBillNo'] = $next['beforeBidBillNo'];
                            $current['afterBidBillNo']  = $next['beforeBidBillNo'];
                            $current['remark'] = '操作了重新分配';
                            break;
                        }
                    }
                }
            }
        }

        // ✅ 3. 【新增】修复所有 "重新发布" 记录的 before 字段，确保与上一条 after 一致
        for ($i = 0; $i < $count; $i++) {

            // 如果是第一条，无法取上一条，跳过（或可设为 null）
            if ($i === 0) {
                // 可选：强制设为 null 或保留原值
                continue;
            }

            $prev = $logActionList[$i - 1];

            // 强制同步：当前 before = 上一条 after
            $logActionList[$i]['beforeGroupId'] = $prev['afterGroupId'] ?? null;
            $logActionList[$i]['beforeSupplierId'] = $prev['afterSupplierId'] ?? null;

        }

        return $logActionList;
    }

    /**
     * 过滤掉重复的“重新发布”日志
     * 重复条件：action = "重新发布" 且 consignmentQdId + afterConsignmentQdPublishRecordId 相同
     * 保留 createTime 最早的一条
     *
     * @param array $logActionList 已整理的日志列表
     * @return array 去重后的日志列表
     */
    public static function removeDuplicateRepublishLogs($logActionList) {
        $uniqueRepublishKeys = [];
        $result = [];

        // 先按 createTime 升序排序，确保遍历时先遇到最早的
        usort($logActionList, function ($a, $b) {
            return strtotime($a['createTime']) <=> strtotime($b['createTime']);
        });

        foreach ($logActionList as $log) {
            // 如果不是“重新发布”，直接保留
            if ($log['action'] !== '重新发布') {
                $result[] = $log;
                continue;
            }

            // 构造去重键：consignmentQdId + afterConsignmentQdPublishRecordId
            $consignmentQdId = $log['consignmentQdId'] ?? '';
            $publishId = $log['afterConsignmentQdPublishRecordId'] ?? '';

            // 如果 publishId 为空，无法构成有效键，也保留（避免误删）
            if ($publishId === '') {
                $result[] = $log;
                continue;
            }

            $key = $consignmentQdId . '_' . $publishId;

            // 如果该键已存在，说明是重复，跳过
            if (isset($uniqueRepublishKeys[$key])) {
                continue;
            }

            // 首次出现，标记并保留
            $uniqueRepublishKeys[$key] = true;
            $result[] = $log;
        }

        // 可选：恢复原始时间顺序（如果需要）
        // 如果你希望保持升序，可不做处理；否则可按原顺序（需额外字段）

        return $result;
    }
}


