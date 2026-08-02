# ux168 PA 运营修复系统(pa-operation-tools)

基于 **PHP 7.4.33** 的无框架运营工具:通过 HTTP 调用后端微服务(pa-biz 等)完成商品/广告/供应链数据运营与修复,不直接操作数据库(数据访问走 CurlService → 后端服务,Redis 用于缓存)。

## 架构总览(2026-08 重构后)

```
pa-operation-tools/
├── composer.json / composer.lock   # 依赖 + autoload(classmap+files)
├── composer.phar                   # 本地 Composer 二进制(不入库)
├── php/
│   ├── requiredfile/               # 唯一引导文件 requiredfile.php(=requiredChorm.php)
│   │                               #   → vendor/autoload.php + PA_* 路径常量 + chmod777 辅助
│   ├── curl/CurlService.php        # 唯一 HTTP 出口(链式:环境→服务→方法)
│   ├── utils/                      # DataUtils / RequestUtils / ExcelUtils / ProductUtils
│   ├── redis/RedisService.php      # phpredis 封装(REDIS_HOST/PORT 来自 Constant)
│   ├── constant/Constant.php       # 常量(Redis 连接 + 业务 key),由 autoload files 加载
│   ├── class/Logger.php            # MyLogger:唯一日志方法 log(),自动 chmod777
│   ├── shell/                      # CLI 脚本,按业务分类:
│   │   ├── sp/                     #   Amazon SP 广告(SpController/SpEnabled/SpPaused/...)
│   │   ├── sync/                   #   数据同步(SyncCurlController/SyncAiCategoryRecommand/...)
│   │   ├── fix/                    #   数据修复(FixCeSkuMaterial)
│   │   ├── QD/ ebay/               #   渠道/ebay 模块
│   │   └── (根)                    #   通用控制器(ProductSkuController/Calc/GatWayRequest...)
│   ├── job/                        # 定时任务(php + .sh)
│   ├── export/                     # 导出产物统一目录(PA_EXPORT_PATH)
│   │   └── uploads/                # 上传/下载统一目录(PA_UPLOAD_PATH)
│   ├── log/                        # 日志统一目录(PA_LOG_PATH),git 忽略
│   └── tests/                      # 冒烟测试(php php/tests/smoke.php)
└── vendor/                         # Composer 依赖(phpspreadsheet 已替代 PHPExcel)

> **注**:原前端(template/)与对接前端的后端(php/controller/)已于安全加固时移除,
> 本工程现为纯 CLI 脚本端(无 web 攻击面)。
```

## 核心约定(重构后)

1. **类加载**:所有核心类由 `composer.json` 的 `classmap` 自动加载(`php/curl|utils|redis|class|shell|job`),
   常量由 `files` 加载(`php/constant/Constant.php`)。**任何脚本都不再手动 require 类文件**,
   只需引入引导文件即可获得全部类,PhpStorm 点击类/方法可直接跳转到定义文件(classmap 映射)。

2. **唯一引导入口**:
   ```php
   require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
   // 之后直接使用:new CurlService() / new MyLogger(...) / ExcelUtils / DataUtils / RedisService ...
   ```
   `requiredChorm.php` 与 `requiredfile.php` 内容完全一致(兼容历史引用)。

3. **统一路径常量**(定义于引导文件,基于 `__DIR__`,不依赖运行 CWD):
   - `PA_ROOT_PATH` 项目根
   - `PA_EXPORT_PATH` → `php/export`(导出文件统一目录)
   - `PA_UPLOAD_PATH` → `php/export/uploads`(上传/下载统一目录)
   - `PA_LOG_PATH` → `php/log`(日志统一目录)
   - `paEnsureDir($path)` / `paEnsureFile($file)` 自动创建并 **chmod 777**

4. **日志**:`MyLogger` 只保留一个方法 `log()`(数组/对象自动 json 序列化),目录与文件自动创建并 chmod 777。

5. **Excel**:`ExcelUtils` 基于 `phpoffice/phpspreadsheet 1.29.2`(原 PHPExcel-1.8 已移除)。
   方法签名与旧版完全一致: `download()` / `downloadXlsx()` / `_readXlsFile()` / `getXlsxData()` / `_readCSV()`。
   注意:PhpSpreadsheet 列索引为 1-based,已内部兼容处理。

6. **CLI 脚本安全**:所有 `shell/` 下的"类库+脚本"双用途文件,顶层业务代码已用
   `if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__)` 包裹——直接 `php 脚本.php` 照常执行;
   被 autoload/require 时不会误触发业务逻辑。

## 请求接口写法示例

```php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");

$curlService = new CurlService();
// step2 环境:test() / uat() / pro() / local()
// step3 服务:s3015() / s3009() / s3023() / s3044() / s3047() / gateway() ...
// step4 方法:get / post / put / delete / upload / getWayPost / getWayGet
$res = $curlService->test()->s3015()->get("pa_products/queryPage", ["limit" => 100]);
$res = $curlService->pro()->s3015()->post("pa_products", $data);
$res = $curlService->uat()->gateway()->getWayPost("/some/path", $data);
```

接收返回:统一 `["httpCode" => int, "header" => string, "result" => array]`,
用 `DataUtils::getPageList($res)` / `getResultData($res)` / `getQueryList($res)` 等解包。

## 环境要求与本地命令

- **PHP 7.4.33**(本机已装),扩展:redis、json、mbstring、zip、xml、curl、openssl、pdo
- **Composer**:项目内自带 `composer.phar`,本机因安全限制需加参数运行:
  ```bash
  php -d pcre.jit=0 composer.phar install
  php -d pcre.jit=0 composer.phar dump-autoload -o
  ```
  若已全局安装 composer,直接使用 `composer` 命令亦可。
- 新增/移动了类文件后,需重新生成 autoload:`php -d pcre.jit=0 composer.phar dump-autoload -o`
- 运行脚本:`php php/shell/sync/SyncCurlController.php`(CLI 直接执行)
- 定时任务:`php/job/*.sh`(cron 调用)