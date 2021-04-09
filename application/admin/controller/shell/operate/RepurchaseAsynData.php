<?php
/**
 * 运营统计--用户复购率分析脚本
 */
namespace app\admin\controller\shell\operate;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class RepurchaseAsynData extends Command
{
    public function __construct()
    {
        parent::__construct();
        $this->order = new \app\admin\model\order\order\NewOrder();
    }

    protected function configure()
    {
        $this->setName('repurchase_asyn_data')
            ->setDescription('repurchase rate run');
    }

    protected function execute(Input $input, Output $output)
    {
        /*$this->getUserRepurchase(1);  //zeelool复购数据
        $this->getUserRepurchase(2);  //voogueme复购数据
        $this->getUserRepurchase(3);  //nihao复购数据
        $this->getOldNewUser(1);  //zeelool新老用户数据
        $this->getOldNewUser(2);  //voogueme新老用户数据
        $this->getOldNewUser(3);  //nihao新老用户数据*/
        $this->getOldNewUserUpdate();  //更新环比数据
        $output->writeln("All is ok");
    }

    /**
     * 循环时间段内的所有月份
     * @param $startDate   开始时间
     * @param $endDate   结束时间
     * @author mjj
     * @date   2021/4/1 15:01:26
     */
    protected function getDateFromRange($startDate, $endDate)
    {
        $dateArr[] = date('Y-m', strtotime($startDate));
        while( $startDate < $endDate ) {
            $startDate = date("Y-m", strtotime("first day of +1 month", strtotime($startDate)));
            $dateArr[] = $startDate;
        }
        return $dateArr;
    }
    /**
     * 获取用户复购数据
     * @param $site  站点
     * @author mjj
     * @date   2021/4/1 10:02:32
     */
    protected function getUserRepurchase($site){
        $today = date('Y-m-d');
        $allMonth = $this->getDateFromRange('2018-01-01', '2021-04-01');
        foreach ($allMonth as $v) {
            //获取当前月份的开始时间和结束时间
            $nowMonthStart = $v . '-01';
            $nowMonthEnd = date('Y-m-t 23:59:59', strtotime($v));
            //用户购买行为的开始时间
            $oneMonthStart = date("Y-m-d", strtotime("first day of +1 month", strtotime($nowMonthStart)));
            #############################################   一月期复购率start    #######################################
            //未来一个月的结束时间
            $oneMonthEnd = date("Y-m-d 23:59:59", strtotime("last day of +1 month", strtotime($nowMonthStart)));
            if ($today > $oneMonthEnd) {
                $repurchaseDataOne = $this->getRepurchaseUserNum($site, $nowMonthStart, $nowMonthEnd, $oneMonthStart,
                    $oneMonthEnd);
                //一月期复购率
                $oneMonthArr = array(
                    'site' => $site,  //站点
                    'type' => 1,   //复购周期：1：一月
                    'day_date' => $v,  //时间
                    'usernum'=>$repurchaseDataOne['usernum'],  //客户数
                    'againbuy_usernum'=>$repurchaseDataOne['againbuy_usernum'],  //复购用户数
                    'againbuy_usernum_ordernum'=>$repurchaseDataOne['againbuy_usernum_ordernum'],  //复购用户订单数
                    'againbuy_rate'=>$repurchaseDataOne['againbuy_rate'],  //复购率
                    'againbuy_num_rate'=>$repurchaseDataOne['againbuy_num_rate'],  //复购频次
                );
                Db::name('datacenter_month_repurchase')->insert($oneMonthArr);
                echo $v."站点：".$site." 一月期复购 is ok"."\n";
            }
            #############################################   一月期复购率end    #########################################
            #############################################   三月期复购率start  #########################################
            //未来三个月的结束时间
            $threeMonthEnd = date("Y-m-d 23:59:59", strtotime("last day of +3 month", strtotime($nowMonthStart)));
            if($today>$threeMonthEnd){
                $repurchaseDataThree = $this->getRepurchaseUserNum($site, $nowMonthStart, $nowMonthEnd, $oneMonthStart,
                    $threeMonthEnd);
                //三月期复购率
                $threeMonthArr = array(
                    'site'=>$site,  //站点
                    'type'=>2,   //复购周期：2：三月
                    'day_date'=>$v,  //时间
                    'usernum'=>$repurchaseDataThree['usernum'],  //客户数
                    'againbuy_usernum'=>$repurchaseDataThree['againbuy_usernum'],  //复购用户数
                    'againbuy_usernum_ordernum'=>$repurchaseDataThree['againbuy_usernum_ordernum'],  //复购用户订单数
                    'againbuy_rate'=>$repurchaseDataThree['againbuy_rate'],  //复购率
                    'againbuy_num_rate'=>$repurchaseDataThree['againbuy_num_rate'],  //复购频次
                );
                Db::name('datacenter_month_repurchase')->insert($threeMonthArr);
                echo $v."站点：".$site." 三月期复购 is ok"."\n";
            }
            #############################################   三月期复购率end    #########################################
            #############################################   半年期复购率start  #########################################
            //未来半年的结束时间
            $halfYearEnd = date("Y-m-d 23:59:59", strtotime("last day of +6 month", strtotime($nowMonthStart)));
            if($today>$halfYearEnd){
                $repurchaseDataSix = $this->getRepurchaseUserNum($site, $nowMonthStart, $nowMonthEnd, $oneMonthStart,
                    $halfYearEnd);
                //半年期复购率
                $halfYearArr = array(
                    'site'=>$site,  //站点
                    'type'=>3,   //复购周期：3：半年
                    'day_date'=>$v,  //时间
                    'usernum'=>$repurchaseDataSix['usernum'],  //客户数
                    'againbuy_usernum'=>$repurchaseDataSix['againbuy_usernum'],  //复购用户数
                    'againbuy_usernum_ordernum'=>$repurchaseDataSix['againbuy_usernum_ordernum'],  //复购用户订单数
                    'againbuy_rate'=>$repurchaseDataSix['againbuy_rate'],  //复购率
                    'againbuy_num_rate'=>$repurchaseDataSix['againbuy_num_rate'],  //复购频次
                );
                Db::name('datacenter_month_repurchase')->insert($halfYearArr);
                echo $v."站点：".$site." 半年期复购 is ok"."\n";
            }
            #############################################   半年期复购率end    #########################################
            #############################################  一年期复购率start #########################################

            //未来一年的结束时间
            $onefYearEnd = date("Y-m-d 23:59:59", strtotime("last day of +12 month", strtotime($nowMonthStart)));
            if($today>$onefYearEnd){
                $repurchaseDataThirteen = $this->getRepurchaseUserNum($site, $nowMonthStart, $nowMonthEnd,
                    $oneMonthStart, $onefYearEnd);
                //一年期复购率
                $oneYearArr = array(
                    'site'=>$site,  //站点
                    'type'=>4,   //复购周期：4：一年
                    'day_date'=>$v,  //时间
                    'usernum'=>$repurchaseDataThirteen['usernum'],  //客户数
                    'againbuy_usernum'=>$repurchaseDataThirteen['againbuy_usernum'],  //复购用户数
                    'againbuy_usernum_ordernum'=>$repurchaseDataThirteen['againbuy_usernum_ordernum'],  //复购用户订单数
                    'againbuy_rate'=>$repurchaseDataThirteen['againbuy_rate'],  //复购率
                    'againbuy_num_rate'=>$repurchaseDataThirteen['againbuy_num_rate'],  //复购频次
                );
                Db::name('datacenter_month_repurchase')->insert($oneYearArr);
                echo $v."站点：".$site." 一年期复购 is ok"."\n";
            }
            usleep(10000);
            #############################################  一年期复购率end   #########################################
        }
    }
    /**
     * 获取用户邮箱及用户数
     * @param $site 站点
     * @param $startDate   用户所在开始时间
     * @param $endDate     用户所在结束时间
     * @return array
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
        $endTime1 = strtotime($endDate1);
        $startTime2 = strtotime($startDate2);
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
        $allMonth = $this->getDateFromRange('2018-12-01', '2021-03-01');
        $where['site'] = $site;
        $where['order_type'] = 1;
        $where['status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']];
        foreach ($allMonth as $v) {
            //获取当前月份的开始时间和结束时间
            $nowMonthStart = $v . '-01';
            $nowMonthEnd = date('Y-m-t 23:59:59', strtotime($v));
            $nowMonthTimeStart = strtotime($nowMonthStart);
            $nowMonthTimeEnd = strtotime($nowMonthEnd);
            $where1['payment_time'] = ['between',[$nowMonthTimeStart,$nowMonthTimeEnd]];
            $sql1 = $this->order
                ->where($where)
                ->where($where1)
                ->field('customer_email')
                ->buildSql();
            $where2 = [];
            $where2[] = ['exp', Db::raw("customer_email in " . $sql1)];
            $where3['payment_time'] = ['<',$nowMonthTimeStart];
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
            $userCount = $this->getUser($site,$nowMonthStart,$nowMonthEnd);  //用户数
            $newUserCount = intval($userCount)-intval($oldUserCount);   //新用户数
            $oldUserRate = $userCount ? round($oldUserCount/$userCount*100,2) : 0; //老用户数占比
            //获取上个月的用户信息
            $lastMonth = date("Y-m", strtotime("first day of -1 month", strtotime($v)));
            $lastData = Db::name('datacenter_supply_month_web')
                ->where('day_date',$lastMonth)
                ->where('site',$site)
                ->find();
            if($lastData['id']){
                //老客户环比变动
                $oldSequential = $lastData['old_usernum'] ? round($oldUserCount/$lastData['old_usernum']*100,2) : 0;
                //新用户环比变动
                $lastMonthNewUser = $lastData['usernum'] - $lastData['old_usernum'];
                $newSequential = $lastMonthNewUser ? round($newUserCount/$lastMonthNewUser*100,2) : 0;
            }else{
                //老客户环比变动
                $oldSequential = 100;
                //新用户环比变动
                $newSequential = 100;
            }
            //判断是否有当月数据
            $isExist = Db::name('datacenter_supply_month_web')
                ->where('day_date',$v)
                ->where('site',$site)
                ->value('id');
            $arr = array(
                'usernum'=>$userCount,
                'old_usernum'=>$oldUserCount,
                'old_usernum_rate'=>$oldUserRate,
                'old_usernum_sequential'=>$oldSequential,
                'new_usernum_sequential'=>$newSequential,
            );
            if ($isExist) {
                Db::name('datacenter_supply_month_web')
                    ->where('id', $isExist)
                    ->update($arr);
                echo '站点：'.$site.' '.$v." update is ok"."\n";
                usleep(10000);
            } else {
                $arr['day_date'] = $v;
                $arr['site'] = $site;
                Db::name('datacenter_supply_month_web')
                    ->insert($arr);
                echo '站点：'.$site.' '.$v." is ok"."\n";
                usleep(10000);
            }
        }
    }

    /**
     * 更新用户新老数据
     *
     * @param $site
     *
     * @author mjj
     * @date   2021/4/1 11:22:13
     */
    protected function getOldNewUserUpdate()
    {
        $list = Db::name('datacenter_supply_month_web')->order('id asc')->select();
        foreach ($list as $v) {
            //获取上个月的用户信息
            $lastMonth = date("Y-m", strtotime("first day of -1 month", strtotime($v['day_date'])));
            $lastData = Db::name('datacenter_supply_month_web')
                ->where('day_date', $lastMonth)
                ->where('site', $v['site'])
                ->find();
            //老客户环比变动
            $oldSequential = $lastData['old_usernum'] ? round(($v['old_usernum'] / $lastData['old_usernum'] - 1) * 100,
                2) : 0;
            //新用户环比变动
            $newUserCount = $v['usernum'] - $v['old_usernum'];
            $lastMonthNewUser = $lastData['usernum'] - $lastData['old_usernum'];
            $newSequential = $lastMonthNewUser ? round(($newUserCount / $lastMonthNewUser - 1) * 100, 2) : 0;
            //判断是否有当月数据
            $arr = [
                'old_usernum_sequential' => $oldSequential,
                'new_usernum_sequential' => $newSequential,
            ];
            Db::name('datacenter_supply_month_web')
                ->where('id', $v['id'])
                ->update($arr);
            echo $v['id'].'站点：'.$v['site'].' '.$v['day_date']." update is ok"."\n";
            usleep(10000);
        }
    }
}