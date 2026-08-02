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
}