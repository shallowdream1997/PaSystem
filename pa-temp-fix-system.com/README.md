# fixclient
请求接口写法示例：

在定义类之前：先引入加载类文件
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");

step1.实例化curl服务类 ：$curlService = new CurlService();

step2.引用环境：

比如是测试环境：$curlService->test();

如果是生产环境：$curlService->pro();

如果是UAT环境：$curlService->uat();

step3. 引用服务域名：

比如s30015项目的服务：$curlService->test()->s3015();

step4. 请求方式：

$curlService->test()->s3015()->get("xxxx",[]);

$curlService->test()->s3015()->post("xxx",[]);

$curlService->test()->s3015()->put("xxxx",[]);

step5. 接收返回参数

大部分数据的返回格式我都已经封装好了，可以使用数据工具类：DataUtils::function方法名

比如：DataUtils::getPageList($res);

其他的工具类：

比如ExcelUtils 是导入导出 

比如RequestUtils  是对部分常见接口的请求封装好的