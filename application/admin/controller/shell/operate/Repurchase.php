<?php
/**
 * 运营统计--用户复购率分析脚本
 */
namespace app\admin\controller\shell\operate;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Repurchase extends Command
{
    public function __construct()
    {
        parent::__construct();
        $this->order = new \app\admin\model\order\order\NewOrder();
    }

    protected function configure()
    {
        $this->setName('repurchase')
            ->setDescription('repurchase rate run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getUserRepurchase(1);  //zeelool复购数据
        $this->getUserRepurchase(2);  //voogueme复购数据
        $this->getUserRepurchase(3);  //nihao复购数据
        $this->getOldNewUser(1);  //zeelool新老用户数据
        $this->getOldNewUser(2);  //voogueme新老用户数据
        $this->getOldNewUser(3);  //nihao新老用户数据
        $output->writeln("All is ok");
    }
    /**
     * 获取用户复购数据
     * @param $site  站点
     * @author mjj
     * @date   2021/4/1 10:02:32
     */
    protected function getUserRepurchase($site){
        $today = date('Y-m-d');
        //获取前一个月时间
        $lastOneMonthStart = date("Y-m-d", strtotime("first day of -1 month", strtotime($today)));
        $lastOneMonthEnd = date("Y-m-d 23:59:59", strtotime("last day of -1 month", strtotime($today)));
        #############################################   一月期复购率start  #########################################
        $oneMonthDate = date("Y-m", strtotime("-2 month", strtotime($today)));
        //获取前两个月时间
        $lastTwoMonthStart = date("Y-m-01", strtotime("-2 month", strtotime($today)));
        $lastTwoMonthEnd = date("Y-m-t 23:59:59", strtotime("-2 month", strtotime($today)));
        $repurchaseDataOne = $this->getRepurchaseUserNum($site, $lastTwoMonthStart, $lastTwoMonthEnd,
            $lastOneMonthStart, $lastOneMonthEnd);
        //一月期复购率
        $oneMonthArr = array(
            'site'=>$site,  //站点
            'type'=>1,   //复购周期：1：一月
            'day_date'=>$oneMonthDate,  //时间
            'usernum'=>$repurchaseDataOne['usernum'],  //客户数
            'againbuy_usernum'=>$repurchaseDataOne['againbuy_usernum'],  //复购用户数
            'againbuy_usernum_ordernum'=>$repurchaseDataOne['againbuy_usernum_ordernum'],  //复购用户订单数
            'againbuy_rate'=>$repurchaseDataOne['againbuy_rate'],  //复购率
            'againbuy_num_rate'=>$repurchaseDataOne['againbuy_num_rate'],  //复购频次
        );
        Db::name('datacenter_month_repurchase')->insert($oneMonthArr);
        echo "站点：".$site." 一月期复购 is ok"."\n";
        #############################################   一月期复购率end    #########################################
        #############################################   三月期复购率start  #########################################
        $threeMonthDate = date("Y-m", strtotime("-4 month", strtotime($today)));
        //获取前四个月时间
        $lastThreeMonthStart = date("Y-m-01", strtotime("-3 month", strtotime($today)));
        $lastFourMonthStart = date("Y-m-01", strtotime("-4 month", strtotime($today)));
        $lastFourMonthEnd = date("Y-m-t 23:59:59", strtotime("-4 month", strtotime($today)));
        $repurchaseDataThree = $this->getRepurchaseUserNum($site, $lastFourMonthStart, $lastFourMonthEnd,
            $lastThreeMonthStart, $lastOneMonthEnd);
        //三月期复购率
        $threeMonthArr = array(
            'site'=>$site,  //站点
            'type'=>2,   //复购周期：2：三月
            'day_date'=>$threeMonthDate,  //时间
            'usernum'=>$repurchaseDataThree['usernum'],  //客户数
            'againbuy_usernum'=>$repurchaseDataThree['againbuy_usernum'],  //复购用户数
            'againbuy_usernum_ordernum'=>$repurchaseDataThree['againbuy_usernum_ordernum'],  //复购用户订单数
            'againbuy_rate'=>$repurchaseDataThree['againbuy_rate'],  //复购率
            'againbuy_num_rate'=>$repurchaseDataThree['againbuy_num_rate'],  //复购频次
        );
        Db::name('datacenter_month_repurchase')->insert($threeMonthArr);
        echo "站点：".$site." 三月期复购 is ok"."\n";
        #############################################   三月期复购率end    #########################################
        #############################################   半年期复购率start  #########################################
        $halfYearDate = date("Y-m", strtotime("-7 month", strtotime($today)));
        //获取前七个月时间
        $lastSixMonthStart = date("Y-m-01", strtotime("-6 month", strtotime($today)));
        $lastSevenMonthStart = date("Y-m-01", strtotime("-7 month", strtotime($today)));
        $lastSevenMonthEnd = date("Y-m-t 23:59:59", strtotime("-7 month", strtotime($today)));
        $repurchaseDataSix = $this->getRepurchaseUserNum($site, $lastSevenMonthStart, $lastSevenMonthEnd,
            $lastSixMonthStart, $lastOneMonthEnd);
        //半年期复购率
        $halfYearArr = array(
            'site'=>$site,  //站点
            'type'=>3,   //复购周期：3：半年
            'day_date'=>$halfYearDate,  //时间
            'usernum'=>$repurchaseDataSix['usernum'],  //客户数
            'againbuy_usernum'=>$repurchaseDataSix['againbuy_usernum'],  //复购用户数
            'againbuy_usernum_ordernum'=>$repurchaseDataSix['againbuy_usernum_ordernum'],  //复购用户订单数
            'againbuy_rate'=>$repurchaseDataSix['againbuy_rate'],  //复购率
            'againbuy_num_rate'=>$repurchaseDataSix['againbuy_num_rate'],  //复购频次
        );
        Db::name('datacenter_month_repurchase')->insert($halfYearArr);
        echo "站点：".$site." 半年期复购 is ok"."\n";
        #############################################   半年期复购率end    #########################################
        #############################################  一年期复购率start #########################################
        $oneYearDate = date("Y-m", strtotime("-13 month", strtotime($today)));
        //获取前十三个月时间
        $lastTwelveMonthStart = date("Y-m-01", strtotime("-12 month", strtotime($today)));
        $lastThirteenMonthStart = date("Y-m-01", strtotime("-13 month", strtotime($today)));
        $lastThirteenMonthEnd = date("Y-m-t 23:59:59", strtotime("-13 month", strtotime($today)));
        $repurchaseDataThirteen = $this->getRepurchaseUserNum($site, $lastThirteenMonthStart, $lastThirteenMonthEnd,
            $lastTwelveMonthStart, $lastOneMonthEnd);
        //一年期复购率
        $oneYearArr = array(
            'site'=>$site,  //站点
            'type'=>4,   //复购周期：4：一年
            'day_date'=>$oneYearDate,  //时间
            'usernum'=>$repurchaseDataThirteen['usernum'],  //客户数
            'againbuy_usernum'=>$repurchaseDataThirteen['againbuy_usernum'],  //复购用户数
            'againbuy_usernum_ordernum'=>$repurchaseDataThirteen['againbuy_usernum_ordernum'],  //复购用户订单数
            'againbuy_rate'=>$repurchaseDataThirteen['againbuy_rate'],  //复购率
            'againbuy_num_rate'=>$repurchaseDataThirteen['againbuy_num_rate'],  //复购频次
        );
        Db::name('datacenter_month_repurchase')->insert($oneYearArr);
        echo "站点：".$site." 一年期复购 is ok"."\n";
        usleep(10000);
        #############################################  一年期复购率end   #########################################
    }
    /**
     * 获取用户邮箱及用户数
     *
     * @param $site 站点
     * @param $startDate   用户所在开始时间
     * @param $endDate     用户所在结束时间
     *
     * @return int
     * @author mjj
     * @date   2021/4/1 09:57:30
     */
    protected function getUser($site,$startDate,$endDate){
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        //订单查询条件
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $where['payment_time'] = ['between',[$startTime,$endTime]];
        //获取当前时间段内的用户人数
        $sql1 = $this->order
            ->where($where)
            ->where('customer_email is not null')
            ->field('customer_email')
            ->group('customer_email')
            ->buildSql();
        $count = $this->order
            ->table([$sql1=>'t2'])
            ->count();
        return $count;
    }

    /**
     * 获取时间段内复购数据
     * @param $site   站点
     * @param $startDate1   用户所在开始时间
     * @param $endDate1    用户所在结束时间
     * @param $startDate2    用户行为开始时间
     * @param $endDate2     用户行为结束时间
     * @return array
     * @author mjj
     * @date   2021/4/1 09:58:28
     */
    protected function getRepurchaseUserNum($site, $startDate1, $endDate1, $startDate2, $endDate2)
    {
        $startTime1 = strtotime($startDate1);
        $startTime2 = strtotime($startDate2);
        $endTime1 = strtotime($endDate1);
        $endTime2 = strtotime($endDate2);
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = [
            'in',
            [
                'free_processing',
                'processing',
                'complete',
                'paypal_reversed',
                'payment_review',
                'paypal_canceled_reversal',
                'delivered'
            ]
        ];
        $where1['payment_time'] = ['between', [$startTime1, $endTime1]];
        $sql1 = $this->order
            ->where($where)
            ->where($where1)
            ->field('customer_email')
            ->buildSql();

        $where2 = [];
        $where2[] = ['exp', Db::raw("customer_email in " . $sql1)];
        $where3['payment_time'] = ['between', [$startTime2, $endTime2]];
        $sql2 = $this->order
            ->alias('t1')
            ->field('customer_email,count(*) as count')
            ->where($where)
            ->where($where2)
            ->where($where3)
            ->group('customer_email')
            ->having('count(*)>= 1')
            ->buildSql();
        $userOrderInfo = $this->order->table([$sql2=>'t2'])->field('count(*) as count,sum(count) as num')->select();
        $orderCount = $userOrderInfo[0]['count'] ? $userOrderInfo[0]['count'] : 0;//复购客户数
        $orderNum = $userOrderInfo[0]['num'] ? $userOrderInfo[0]['num'] : 0;//复购客户订单数
        //客户数
        $userNum = $this->getUser($site,$startDate1,$endDate1);
        //复购率：复购用户数/客户数
        $repurchaseRate = $userNum ? round($orderCount/$userNum*100,2) : 0;
        //复购频次：复购客户订单数/复购客户数
        $repurchaseNumRate = $orderCount ? round($orderNum/$orderCount,2) : 0;
         $arr = array(
             'usernum'=>$userNum,   //客户数
             'againbuy_usernum'=>$orderCount,   //复购客户数
             'againbuy_usernum_ordernum'=>$orderNum,   //复购客户订单数
             'againbuy_rate'=>$repurchaseRate,   //复购率
             'againbuy_num_rate'=>$repurchaseNumRate,   //复购频次
         );
        return $arr;
    }
    /**
     * 获取用户新老数据
     * @param $site
     * @author mjj
     * @date   2021/4/1 11:22:13
     */
    protected function getOldNewUser($site){
        $today = date('Y-m-d');
        $nowMonth = date("Y-m", strtotime("first day of -1 month", strtotime($today)));
        $lastOneMonthStart = date("Y-m-01", strtotime("first day of -1 month", strtotime($today)));
        $lastOneMonthEnd = date("Y-m-d 23:59:59", strtotime("last day of -1 month", strtotime($today)));
        $lastOneMonthTimeStart = strtotime($lastOneMonthStart);
        $lastOneMonthTimeEnd = strtotime($lastOneMonthEnd);
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        $where1['payment_time'] = ['between',[$lastOneMonthTimeStart,$lastOneMonthTimeEnd]];
        $sql1 = $this->order
            ->where($where)
            ->where($where1)
            ->field('customer_email')
            ->buildSql();
        $where2 = [];
        $where2[] = ['exp', Db::raw("customer_email in " . $sql1)];
        $where3['payment_time'] = ['<',$lastOneMonthTimeStart];
        $sql2 = $this->order
            ->alias('t1')
            ->field('count(*) as count')
            ->where($where)
            ->where($where2)
            ->where($where3)
            ->group('customer_email')
            ->having('count(*)>= 1')
            ->buildSql();
        $oldUserCount = $this->order->table([$sql2=>'t2'])->value('count(*) as count');
        //获取用户邮箱及用户数
        $userCount = $this->getUser($site,$lastOneMonthStart,$lastOneMonthEnd);  //用户数
        $newUserCount = intval($userCount)-intval($oldUserCount);   //新用户数
        $oldUserRate = $userCount ? round($oldUserCount/$userCount*100,2) : 0; //老用户数占比
        //获取上个月的用户信息
        $lastMonth = date("Y-m", strtotime("first day of -1 month", strtotime($lastOneMonthStart)));
        $lastData = Db::name('datacenter_supply_month_web')
            ->where('day_date',$lastMonth)
            ->where('site',$site)
            ->find();
        //老客户环比变动
        $oldSequential = $lastData['old_usernum'] ? round(($oldUserCount / $lastData['old_usernum'] - 1) * 100, 2) : 0;
        //新用户环比变动
        $lastMonthNewUser = $lastData['usernum'] - $lastData['old_usernum'];
        $newSequential = $lastMonthNewUser ? round(($newUserCount / $lastMonthNewUser - 1) * 100, 2) : 0;
        $arr = array(
            'usernum' => $userCount,
            'old_usernum' => $oldUserCount,
            'old_usernum_rate' => $oldUserRate,
            'old_usernum_sequential' => $oldSequential,
            'new_usernum_sequential' => $newSequential,
        );
        Db::name('datacenter_supply_month_web')
            ->where('day_date',$nowMonth)
            ->where('site',$site)
            ->update($arr);
        echo '站点：'.$site.' '.$nowMonth." is ok"."\n";
        usleep(10000);
    }
}