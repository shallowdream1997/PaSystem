#!/bin/bash
# API 测试脚本

echo "=========================================="
echo "PaSystem API 测试"
echo "=========================================="
echo ""

BASE_URL="http://localhost"
API_PATH="/xp/www/PaSystem/api/test.php"

echo "测试 1: 调用 downloadPaSkuPhotoProgress"
echo "------------------------------------------"
php -r "
\$_GET['action'] = 'downloadPaSkuPhotoProgress';
include '$API_PATH';
"
echo ""
echo ""

echo "测试 2: 调用 readPaSkuPhotoProgress"
echo "------------------------------------------"
php -r "
\$_GET['action'] = 'readPaSkuPhotoProgress';
include '$API_PATH';
"
echo ""
echo ""

echo "测试 3: 错误的action"
echo "------------------------------------------"
php -r "
\$_GET['action'] = 'invalidAction';
include '$API_PATH';
"
echo ""
echo ""

echo "=========================================="
echo "测试完成"
echo "=========================================="
