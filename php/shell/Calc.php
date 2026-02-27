<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredChorm.php");
require_once(dirname(__FILE__) . "/../../php/constant/Constant.php");
class Calc
{
    public function calcSalary($actualHour, $otHour,$otOut8Count)
    {
        $baseSalary = 10000;
        // 设置时区（根据需要设置）
        date_default_timezone_set('Asia/Shanghai');

        // 获取当前月份的第一天和最后一天
        $firstDay = new DateTime('first day of last month');
        $lastDay = new DateTime('last day of last month');

        // 计算工作日
        $workDays = 0;
        $currentDay = clone $firstDay; // 克隆对象以避免修改原始对象

        while ($currentDay <= $lastDay) {
            // 检查是否为周一至周五
            if ($currentDay->format('N') >= 1 && $currentDay->format('N') <= 5) {
                $workDays++;
            }
            // 增加一天
            $currentDay->modify('+1 day');
        }

        echo "上月工作日数量为：" . $workDays . "天";
        echo "\n";
        // 如果需要计算每日8小时的工作小时数
        $workHours = $workDays * 8;
        echo "上月总工作小时数为：" . $workHours . "小时";
        echo "\n";
        $meritsSalary = 0;
        if ($otHour <= 10){
            $meritsSalary = $otHour * 66;
        }elseif ($otHour > 10 && $otHour <= 60){
            $meritsSalary = (10 * 66) + ($otHour - 10) * 72;
        }else{
            $meritsSalary = (10 * 66) + (50 * 72) + ($otHour - 60) * 79;
        }
        $shebao = 547.24 + 422.72 + 119.92 + 4.6 + 360;

        $totalSalary = (($baseSalary/$workHours) * $actualHour) + ($otOut8Count * 30) + $meritsSalary - $shebao;

        echo "税前工资：".$totalSalary."\n";

        $koushui = ($totalSalary - 5000) * 0.03;
        echo "应该缴纳个税：".$koushui."\n";

        $shuiHou = $totalSalary - $koushui;
        echo "税后工资：".$shuiHou."\n";

    }

    public function test($batchName)
    {
        $prePurchaseBillNo = $batchName;
        $position = strpos($batchName, '-');
        if ($position !== false) {
            // 从开始到 '-' 的位置截取字符串
            $prePurchaseBillNo = substr($batchName, 0, $position);
        }
        echo $prePurchaseBillNo;
    }

    public function calcProgress(){
        $redis = new RedisService();

        $r = new Redis();
        $r->connect(REDIS_HOST,REDIS_PORT);
        $r->incr(111);


    }
}

$calc = new Calc();
//$calc->calcSalary(168.5,31.5,12);
$calc->test("DPMO260205001-陈雅-吕治政");
//$calc->calcProgress();