<?php
require_once(dirname(__FILE__) ."/../../php/class/Logger.php");

/**
 * 产品表处理的工具类
 * Class ProductUtils
 */
class ProductUtils
{

    public function __construct()
    {

    }

    /**
     * 变更product-sku里面的attribute资料信息
     * @param $attribute
     * @param $label
     * @param $channel
     * @param string $value
     */
    public static function editProductAttribute(&$attribute, $label, $channel, $value = "")
    {
        $filter = DataUtils::findIndexInArray($attribute, ["label" => $label, "channel" => $channel]);
        if (empty($filter)) {
            //无则新增
            $attribute[] = ["label" => $label, "channel" => $channel, "value" => $value];
        } else {
            //有则更新
            foreach ($filter as $index => $array) {
                $attribute[$index]['value'] = $value;
            }
        }
    }

    /**
     * 批量变更product-sku里面的attribute资料信息
     * @param $attribute
     * @param $updateLabelChannelValue
     */
    public static function editProductAttributeByArr(&$attribute, $updateLabelChannelValue)
    {
        foreach ($updateLabelChannelValue as $info){
            $filter = DataUtils::findIndexInArray($attribute, [
                "label" => $info['label'],
                "channel" => $info['channel']
            ]);
            if (empty($filter)) {
                //无则新增
                $attribute[] = [
                    "label" => $info['label'],
                    "channel" => $info['channel'],
                    "value" => $info['value']
                ];
            } else {
                //有则更新
                foreach ($filter as $index => $array) {
                    $attribute[$index]['value'] = $info['value'];
                }
            }
        }
    }


    /**
     * 批量删除product-sku里面的attribute资料信息
     * @param array $attribute 引用传递的属性数组
     * @param array $deleteLabelChannelValue 待删除的属性条件数组
     */
    public static function deleteProductAttributeByArr(&$attribute, $deleteLabelChannelValue)
    {
        if (empty($attribute) || empty($deleteLabelChannelValue)) {
            return; // 空数组直接返回，避免无效循环
        }

        // 构建查询条件映射表，提高查找效率
        $deleteMap = [];
        foreach ($deleteLabelChannelValue as $info) {
            $key = $info['label'] . '|' . $info['channel'];
            $deleteMap[$key] = true;
        }

        // 一次遍历完成筛选，减少数组操作次数
        $filtered = [];
        foreach ($attribute as $item) {
            $currentKey = $item['label'] . '|' . $item['channel'];
            // 不在删除列表中的元素保留
            if (!isset($deleteMap[$currentKey])) {
                $filtered[] = $item;
            }
        }

        // 直接替换原数组，避免多次splice操作
        $attribute = $filtered;
    }



    public static function deleteProductAttributeByArrV2(&$attribute, $deleteLabelChannelValue)
    {
        if (empty($attribute) || empty($deleteLabelChannelValue)) {
            return; // 空数组直接返回，避免无效循环
        }

        // 构建查询条件映射表，提高查找效率
        $deleteMap = [];
        foreach ($deleteLabelChannelValue as $info) {
            $key = $info['label'] . '|' . $info['channel'];
            $deleteMap[$key] = true;
        }

        // 一次遍历完成筛选，减少数组操作次数
        $filtered = [];
        foreach ($attribute as $item) {
            $currentKey = $item['label'] . '|' . $item['channel'];
            // 不在删除列表中的元素保留
            if (!isset($deleteMap[$currentKey])) {
                $filtered[] = $item;
            }
        }

        // 直接替换原数组，避免多次splice操作
        $attribute = $filtered;
    }

}