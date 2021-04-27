<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class DataCenterAsynData extends Command
{
    protected function configure()
    {
        $this->setName('data_center')
            ->setDescription('data_center run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getData(10);
        $this->getData(11);
        $output->writeln("All is ok");
    }

    /**
     * 获取每日数据
     * @author mjj
     * @date   2021/4/15 09:24:50
     */
    public function getData($site)
    {
        //获取datacenter表中德语站和日本站的数据
        $data = Db::name('datacenter_day')
            ->where('site',$site)
            ->where('sessions',0)
            ->select();
        foreach ($data as $value){
            //会话
            $arr['sessions'] = $this->google_session($site, $value['day_date']);
            //计算加购率
            $arr['add_cart_rate'] = $arr['sessions'] ? round($value['new_cart_num']/$arr['sessions']*100,2) : 0;
            //计算会话转化率
            $arr['session_rate'] = $arr['sessions'] ? round($value['order_num']/$arr['sessions']*100,2) : 0;
            Db::name('datacenter_day')
                ->where('id',$value['id'])
                ->update($arr);
            echo $value['id']."---".$value['day_date']." is ok";
            usleep(10000);
        }
    }
}