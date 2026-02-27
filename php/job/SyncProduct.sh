#!/bin/bash
source /etc/profile

if [ -z "$1" ]; then
    echo "需要输入 skuIdList 参数"
    exit 1
fi
skuIdList=$1
/xp/server/php/php-7.4/bin/php /xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/shell/SyncProductSku.php -skuIdList "$skuIdList"