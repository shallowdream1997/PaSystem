# PaSystem 命名空间重构说明

## 📋 项目结构

```
/xp/www/PaSystem/
├── autoload.php          # 统一自动加载入口（必须引入）
├── vendor/               # Composer依赖
├── log/                  # 日志文件目录（自动创建）
├── export/               # 导出文件目录（自动创建）
└── php/
    ├── class/            # App\Core 核心类
    ├── controller/       # App\Controller 控制器
    ├── shell/            # App\Shell Shell脚本
    ├── curl/             # App\Service 服务类
    ├── redis/            # App\Service 服务类
    └── utils/            # App\Helper 工具类
```

## 🔧 命名空间映射

| 目录 | 命名空间 | 说明 |
|------|---------|------|
| `php/class/` | `App\Core` | 核心类（MyLogger等） |
| `php/controller/` | `App\Controller` | 控制器类 |
| `php/shell/` | `App\Shell` | Shell脚本类 |
| `php/curl/` | `App\Service` | Curl服务类 |
| `php/redis/` | `App\Service` | Redis服务类 |
| `php/utils/` | `App\Helper` | 工具类 |

## 🚀 使用方法

### 1. 基础使用（任何PHP文件的开头）

```php
<?php
// 引入自动加载器（必须）
require_once __DIR__ . '/autoload.php';

// 现在可以直接使用命名空间
```

### 2. 推荐方式：使用 use 语句

```php
<?php
require_once __DIR__ . '/autoload.php';

use App\Core\MyLogger;
use App\Helper\ExcelUtils;
use App\Helper\DataUtils;
use App\Service\CurlService;
use App\Service\RedisService;

// 直接使用类名
$logger = new MyLogger("test/log");
$logger->log("日志内容");

$excel = new ExcelUtils();
$data = DataUtils::getResultData($response);
```

### 3. 完整命名空间方式

```php
<?php
require_once __DIR__ . '/autoload.php';

// 使用完整命名空间
$logger = new App\Core\MyLogger("test/log");
$excel = new App\Helper\ExcelUtils();
```

### 4. 向后兼容方式（旧代码自动支持）

```php
<?php
require_once __DIR__ . '/autoload.php';

// 旧代码无需修改，通过class_alias自动兼容
$logger = new MyLogger("test/log");
$excel = new ExcelUtils();
$curl = new CurlService();
```

## ✅ 已添加命名空间的类

| 类名 | 命名空间 | 文件路径 |
|------|---------|----------|
| MyLogger | `App\Core\MyLogger` | php/class/MyLogger.php |
| CurlService | `App\Service\CurlService` | php/curl/CurlService.php |
| RedisService | `App\Service\RedisService` | php/redis/RedisService.php |
| ExcelUtils | `App\Helper\ExcelUtils` | php/utils/ExcelUtils.php |
| DataUtils | `App\Helper\DataUtils` | php/utils/DataUtils.php |
| ProductUtils | `App\Helper\ProductUtils` | php/utils/ProductUtils.php |
| RequestUtils | `App\Helper\RequestUtils` | php/utils/RequestUtils.php |

## 📝 迁移指南

### 旧代码迁移步骤

#### 1. 替换require_once

**旧代码：**
```php
<?php
require_once(dirname(__FILE__) . "/../class/Logger.php");
require_once(dirname(__FILE__) . "/../utils/ExcelUtils.php");
```

**新代码：**
```php
<?php
require_once __DIR__ . '/autoload.php';

use App\Core\MyLogger;
use App\Helper\ExcelUtils;
```

#### 2. 类实例化保持不变

```php
// 旧代码和新代码都支持
$logger = new MyLogger("test/log");
$excel = new ExcelUtils();

// 也可以使用完整命名空间
$logger = new App\Core\MyLogger("test/log");
```

### 新代码编写规范

#### 1. 文件开头添加命名空间

```php
<?php
namespace App\Helper;

use App\Core\MyLogger;
use App\Service\CurlService;

class NewClass {
    // 类定义
}
```

#### 2. 使用全局类需要加反斜杠

```php
// 正确：使用全局Exception类
throw new \Exception("错误信息");
catch (\Exception $e) {}

// 正确：使用全局Redis类
$redis = new \Redis();
```

## 🎯 优势

1. **无需require_once**：只需在文件开头引入`autoload.php`，所有类自动加载
2. **命名空间隔离**：避免类名冲突，代码更清晰
3. **向后兼容**：旧代码无需修改即可运行
4. **PSR-4标准**：符合PHP现代开发规范
5. **IDE友好**：支持代码提示和自动补全

## 🔍 测试验证

运行测试文件验证配置：

```bash
cd /xp/www/PaSystem
php test_autoload.php      # 测试自动加载
php example_usage.php       # 查看使用示例
```

## 📚 示例代码

完整示例请参考：
- `test_autoload.php` - 自动加载测试
- `example_usage.php` - 使用示例

## ⚠️ 注意事项

1. **所有PHP文件都必须引入 autoload.php**
2. **文件名必须与类名一致**（PSR-4规范）
3. **使用全局类（Exception、Redis等）需要加 `\` 前缀**
4. **常量文件仍需手动引入**（php/constant/Constant.php）
