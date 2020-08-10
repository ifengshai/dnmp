<?php

namespace app\api\controller;

use app\admin\model\Admin;
use app\admin\model\demand\ItWebDemand;
use app\admin\model\demand\ItWebTask;
use app\admin\model\demand\ItWebTaskItem;
use app\common\controller\Api;
use Mpdf\Tag\A;
use think\Db;
use think\Env;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $connect = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        'hostname'    => '52.43.211.154',
        // 数据库名
        'database'    => 'xq',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => '5e9e71caef406084',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'xms_',
    ];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }
    public function test()
    {
        echo date('Y-m-d H:i:s',time())."\n";
        echo time()."\n";
        echo date('Y-m-d H:i:s',strtotime('2020-07-30 18:57:00'));
    }

    /**
     * 目标导入
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function taskImport()
    {
        $db = Db::connect($this->connect);
        $tasks = $db->name('object')->select();
        foreach($tasks as $task){
            $type = $task['type'] + 1;
            //判断是否已完成
            $count = $db->name('deadline')->where('pid',$task['id'])->where('status',0)->count();
            $webTask = [
                'type' => $type,
                'title' => $task['title'],
                'desc' => $task['title'],
                'closing_date' => $task['wish_time'],
                'complete_date' => $task['completed_time'],
                'is_complete' => $count > 0 ? 0 : 1,
                'is_test_adopt' => $count > 0 ? 0 : 1,
                'test_adopt_time' => $task['completed_time'],
                'create_person' => '张晓',
                'createtime' => $task['add_time'],
                'site_type' => 1,
                'test_regression_adopt' => $count > 0 ? 0 : 1,
                'test_regression_adopt_time' => $task['completed_time']
            ];
            $itWebTask = ItWebTask::create($webTask);
            $deadlines = $db->name('deadline')->where('pid',$task['id'])->select();
            foreach($deadlines as $deadline){
                //用户id
                $userId = Admin::where('nickname',$deadline['fz_name'])->value('id');
                $php = ['黄彬彬','李想','卢志恒','苗晶晶','钱会博'];
                $data = [
                    'task_id' => $itWebTask->id,
                    'person_in_charge' => $userId,
                    'title' => $deadline['title'],
                    'desc' => $deadline['title'],
                    'plan_date' => $deadline['wish_time'],
                    'complete_date' => $deadline['completed_time'],
                    'is_complete' => $deadline['status'] == 0 ? 0 : 1,
                    'group_type' => in_array($deadline['fz_name'],$php) ? 2 : 1,
                    'is_test_adopt' => $deadline['status'] == 0 ? 0 : 1,
                    'test_adopt_time' => $deadline['completed_time'],
                    'test_person' => '',
                    'type' => $type,
                ];
                ItWebTaskItem::create($data);
            }
        }
    }

    /**
     * 需求，bug导入，只导入成功的
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function demandImport()
    {
        $db = Db::connect($this->connect);
        $demands = $db->name('task')->where('status',5)->where('type','in','1,2,3,4')->order('id asc')->select();
        foreach($demands as $demand){
            $website = $demand['website'];
            switch($website){
                case 'zeelool':
                    $siteType = 1;
                    break;
                case 'voogueme':
                    $siteType = 2;
                    break;
                case 'nihaooptical':
                    $siteType = 3;
                    break;
                case 'other':
                    $siteType = 5;
                    break;
            }
            if($demand['type'] == 2){
                $type = 1;
            }elseif($demand['type'] == 4){
                $type = 3;
            }else{
                $type = 2;
            }
            $askName = $demand['ask_name'];
            if(strpos('王小花',$demand['ask_name']) !== false){
                $askName = str_replace('王小花','王桂华',$demand['ask_name']);
            }
            $qd_name = $demand['qd_name'];
            if($qd_name){
                if(strpos('李杨',$qd_name) !== false){
                    $qd_name = str_replace('李杨','李扬',$qd_name);
                }
            }

            $entry_user_id = Admin::where('nickname','in',$askName)->value('id');
            $copy_to_user_id = Admin::where('nickname','in',$askName)->column('id');
            $all_complexity = $demand['level'];
            if($all_complexity == 1){
                $all_complexity = 3;
            }elseif($all_complexity == 3){
                $all_complexity = 1;
            }
            $demandData = [
                'type' => $type,
                'site_type' => $siteType,
                'entry_user_id' => $entry_user_id,
                'copy_to_user_id' => join(',',$copy_to_user_id),
                'title' => $demand['title'],
                'content' => $demand['description'],
                'all_complexity' => $all_complexity,
                'status' => 7,
                'web_designer_group' => 0,
                'web_designer_group' => 0,
                'test_group' => 2,
                'entry_user_confirm' => 1,
                'entry_user_confirm_time' => strtotime($demand['completed_time']) ? $demand['completed_time'] : null,
                'create_time' => $demand['add_time'],
                'hope_time' => strtotime($demand['wish_time']) ? $demand['wish_time'] : null,
                'all_finish_time' => strtotime($demand['completed_time']) ? $demand['completed_time'] : null,
                'is_small_probability' => $demand['is_small_pro'],
                'is_work_time' => $demand['bug_is_not_work'] ?: 0,
            ];
            //测试数据
            $completeTime = strtotime($demand['completed_time']) ? $demand['completed_time'] : null;
            if($demand['is_need_test']){
                $demandData['test_group'] = 1;
                $demandData['test_complexity'] = $all_complexity;
                $demandData['test_is_finish'] = 1;
                $demandData['test_user_id'] = 195;
                $demandData['test_finish_time'] = strtotime($demand['test_time']) ? $demand['test_time'] : $completeTime;
                $demandData['return_test_is_finish'] = 1;
                $demandData['return_test_finish_time'] = $completeTime;
            }
            //前端数据
            if(($demand['process_type'] == 1 || $demand['process_type'] == 3) && $demand['qd_name']){
                $webDesignerUerId = Admin::where('nickname','in',$qd_name)->column('id');
                $demandData['web_designer_group'] = 1;
                $demandData['web_designer_complexity'] = $all_complexity;
                $demandData['web_designer_user_id'] = join(',',$webDesignerUerId);
                $demandData['web_designer_expect_time'] = strtotime($demand['qd_yq_time']) ? $demand['qd_yq_time'] : null;
                $demandData['web_designer_is_finish'] = 1;
                $demandData['web_designer_finish_time'] = strtotime($demand['qd_complete_time']) ? $demand['qd_complete_time'] : $completeTime;
                $demandData['web_designer_note'] = $demand['replay'];
            }
            //后端数据
            if(($demand['process_type'] == 2 || $demand['process_type'] == 3) && $demand['hd_name']){
                $phpUerId = Admin::where('nickname','in',$demand['hd_name'])->column('id');
                $demandData['phper_group'] = 1;
                $demandData['phper_complexity'] = $all_complexity;
                $demandData['phper_user_id'] = join(',',$phpUerId);
                $demandData['phper_expect_time'] = strtotime($demand['hd_yq_time']) ? $demand['hd_yq_time'] : null;
                $demandData['phper_is_finish'] = 1;
                $demandData['phper_finish_time'] = strtotime($demand['hd_complete_time']) ? $demand['hd_complete_time'] : $completeTime;
                $demandData['phper_note'] = $demand['replay'];
            }
            $res = ItWebDemand::create($demandData);
            echo $res->id."\r\n";
        }
    }

}
