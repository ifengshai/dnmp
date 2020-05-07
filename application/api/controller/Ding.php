<?php
/**
 * @Author: CrashpHb彬
 * @Date: 2020/3/16 9:15
 * @Email: 646054215@qq.com
 */

namespace app\api\controller;


use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\demand\ItTestRecord;
use app\admin\model\demand\ItWebDemand;
use app\admin\model\Department;
use app\common\model\Auth;
use fast\Random;
use think\Controller;
use EasyDingTalk\Application;

class Ding extends Controller
{
    protected $app = null;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        try {
            $this->app = new Application(config('ding'));
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 注册事件回调
     * @return [type] [description]
     */
    public function register()
    {
        $params = [
            'call_back_tag' => ['user_add_org', 'user_modify_org', 'user_leave_org', 'org_dept_create', 'org_dept_modify', 'org_dept_remove'],
            'url' => 'http://mojing.mruilove.com/api/ding/receive',
        ];
        $this->app->callback->register($params);
    }

    /**
     * 更新事件回调
     * [update description]
     * @return [type] [description]
     */
    public function update()
    {
        $params = [
            'call_back_tag' => ['user_add_org', 'user_modify_org', 'user_leave_org', 'org_dept_create', 'org_dept_modify', 'org_dept_remove'],
            'url' => 'http://mojing.mruilove.com/api/ding/receive',
        ];
        $this->app->callback->update($params);
    }

    /**
     * 回调事件接收
     * @return [type] [description]
     */
    public function receive()
    {
        // 获取 server 实例
        $server = $this->app->server;
        $server->push(function ($payload) {
            $type = $payload['EventType'];
            switch ($type) {
                //添加用户
                case 'user_add_org':
                    $userIds = $payload['UserId'];
                    foreach ($userIds as $userId) {
                        if (!Admin::where('userid', $userId)->value('id')) {
                            //不存在改用户，添加用户
                            $user = $this->app->user->get($userId);
                            Admin::userAdd($user);
                        }
                    }
                    break;
                case 'user_modify_org':
                    //用户更新
                    $userIds = $payload['UserId'];
                    foreach ($userIds as $userId) {
                        $id = Admin::where('userid', $userId)->value('id');
                        if ($id) {
                            $user = $this->app->user->get($userId);
                            Admin::userUpdate($user, $id);
                        }
                    }
                    break;
                case 'user_leave_org':
                    //用户离职
                    $userIds = $payload['UserId'];
                    foreach ($userIds as $userId) {
                        Admin::where('userid', $userId)->setField('status', 'hidden');
                    }
                    break;
                case 'org_dept_create':
                    //创建部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        //获取部门详情
                        $department = $this->app->department->get($deptId);
                        Department::deptAdd($department);
                    }
                    break;
                case 'org_dept_modify':
                    //修改部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        //获取部门详情
                        $department = $this->app->department->get($deptId);
                        Department::deptUpdate($department);
                    }
                    break;
                case 'org_dept_modify':
                    //删除部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        Department::deptDelete($deptId);
                    }
                    break;
            }
        });

        $server->serve()->send();
    }

    /**
     * 第一次插入所有的部门详情
     * @param null $id
     * @param int $pid
     */
    public function setDepartment($id = null, $pid = 1)
    {
        //获取一级的所有的部门
        $departments = $this->app->department->list($id);
        if ($departments['errcode'] === 0 && !empty($departments['department'])) {
            foreach ($departments['department'] as $department) {
                //查找是否存在，已存在的不创建
                $depart = Department::where('department_id',$department['id'])->value('id');
                if (!$depart) {
                    $data = [
                        'name' => $department['name'],
                        'pid' => $pid,
                        'department_id' => $department['id'],
                        'parentid' => $department['parentid']
                    ];
                    $depart = Department::create($data);
                    $departId = $depart->id;
                }
                echo $departId;
                $this->setDepartment($department['id'], $departId);
            }
        }
    }

    /**
     * 第一次导入所有的用户并绑定关系
     */
    public function setUser()
    {
        $departments = $this->app->department->list(null, true);
        foreach ($departments['department'] as $department) {
            $departmentId = $department['id'];
            $res = $this->app->user->getDetailedUsers($departmentId, '0', '100');
            if ($res['errcode'] == 0) {
                $users = $res['userlist'];
                foreach ($users as $user) {
                    //判断用户是否已经存在
                    $userId = Admin::where('nickname', $user['name'])->value('id');

                    if ($userId) {
                        $data = [
                            'id' => $userId,
                            'avatar' => $user['avatar'] ?: '/assets/img/avatar.png',
                            'position' => $user['position'],
                            'mobile' => $user['mobile'],
                            'userid' => $user['userid'],
                            'unionid' => $user['unionid'],
                            'department_id' => $departmentId
                        ];
                        $userAdd = Admin::update($data);
                    } else {
                        $username = str_replace(' ', '', pinyin($user['name']));
                        $count = Admin::where('username', $username)->count();
                        if($count == 1){
                            $username = $username.$count;
                        }
                        $salt = Random::alnum();
                        $password = md5(md5($username) . $salt);
                        $data = [
                            'username' => $username,
                            'nickname' => $user['name'],
                            'password' => $password,
                            'salt' => $salt,
                            'avatar' => $user['avatar'] ?: '/assets/img/avatar.png',
                            'email' => $user['email'] ?? '',
                            'status' => 'normal',
                            'position' => $user['position'],
                            'mobile' => $user['mobile'],
                            'userid' => $user['userid'],
                            'unionid' => $user['unionid'],
                            'department_id' => $departmentId
                        ];
                        $userAdd = Admin::create($data);
                    }
                    echo $userAdd->id;
                }
            }
        }
    }

    public function test($url = '')
    {
//        $this->setDepartment();
//        exit;
//        $params = send_ding_message(['040740464839840580'], '收到需求2', '钱海信用卡支付后重复发送确认订单的邮件');
//        dump($this->app->conversation->sendCorporationMessage($params));
//        die;
        dump($this->app->callback->list());
        die;
        $depart_ids = [144092586, 144052776, 102054298];
        $userIds = [];
        //获取所有客服的id
        foreach ($depart_ids as $id) {
            $userId = $this->app->user->getUserIds($id);
            $userIds = array_merge($userIds, $userId['userIds']);
        }
        dump($userIds);
        die;
        //获取下一天客服在线的状态
        $from = strtotime(date('Y-m-d', (time() + 3600 * 24))) * 1000;
        $from = strtotime('2020-03-20') * 1000;
        $person = [];
        foreach ($userIds as $userId) {
            $listByDay = $this->app->attendance->listByDay($userId, $userId, $from);
            dump($listByDay);
            die;
            //判断用户是否排休
            $results = $listByDay['result'];
            //放假的掠过分配
            if (count($results) < 2 && $results[0]['is_rest'] == 'Y') continue;
            //获取用户的工作的时间
            foreach ($results as $result) {

                $person[$userId] = [
                ];
            }
        }

        // $res = $this->app->user->getDetailedUsers($department_id,'0','100');
        // 导入表，用户名 + 排班时间
        // 获取邮件为new的进行分配，其余按第一次分配的为准
        // 获取上班的人数 eg:5
        // 邮件id%5求余数分配
    }

    public function ding_notice(array $userIds,$url = null,$title=null,$text=null, $picUrl = 'https://static.dingtalk.com/media/lALPDeC2v2wwMcPMpcyk_164_165.png'){
        $url = config('ding.message_url');
        $agentId = config('ding.agent_id');
        $link = [
            'msgtype' => 'link',
            'link' => [
                'messageUrl' => $url,
                'picUrl' => $picUrl,
                'title' => $title,
                'text' => $text
            ]

        ];
        $params = [
            'agent_id' => $agentId,
            'userid_list' => join(',', $userIds),
            'msg' => json_encode($link)
        ];
        //$params = send_ding_message(['0550643549844645'], '收到需求2', '钱海信用卡支付后重复发送确认订单的邮件');
        $return_date = $this->app->conversation->sendCorporationMessage($params);
        return $return_date;
    }

    private static $instance;

    /**
     * 发送钉钉通知
     * @param string|int|array $users 接收人userid, 也可以是用户id或用户名, 可使用数组方式发送给多人
     * @param string $title 通知标题
     * @param string $content 通知内容
     */
    public static function cc_ding($users, $title, $content, $picUrl = 'https://static.dingtalk.com/media/lALPDeC2v2wwMcPMpcyk_164_165.png') {
        if (!self::$instance) self::$instance = new self();
        $instance = self::$instance;
        if (!is_array($users)) $users = [$users];
        foreach ($users as $k =>&$user) {
            // 使用id获取userid
            if (($get = Admin::get(['id' =>$user])) || ($get = Admin::get(['username' =>$user]))) {
                $user = $get['userid'];
            }
            if (!$user) unset($users[$k]);
        }
        // var_dump($users);
        // die();
        if (!$title) $title = '您有一条新消息';
        if (!$content) $content = '请前往魔晶查看详情';
        return $instance ->ding_notice($users, $url, $title, strip_tags($content), $picUrl);
    }

    /**
     * 根据it_web_demand表返回任务类型
     * @param $type
     * @return string
     */
    public static function demandType($type) {
        return ['bug', '需求', '疑难'][$type - 1]?? '消息';
    }

    /**
     * 根据it_web_demand表返回站点名称
     */
    public static function siteType($type_id) {
        return [
            'zeelool',
            'voogueme',
            'nihao',
            'wesee',
            'orther'
        ][$type_id - 1]?? '';
    }

    /**
     * 回调发起钉钉通知
     * @param string $name 事件名称
     * @param \app\admin\model\demand\ItWebDemand $demand 需求管理模型
     */
    public static function dingHook(string $name, \app\admin\model\demand\ItWebDemand $demand) {
        if ($demand ->type == 3) return false; // 疑难不作处理
        $demand = ItWebDemand::get($demand->id);
        $send_ids = []; // 被发送者id, userid或nickname
        $msg = ''; // 消息内容
        switch($name){
            case 'add':                     // 添加内容通知, 发送给所有测试人员
                $authUserIds = Auth::getUsersId('demand/it_web_demand/test_distribution') ?: [];
                $copy_to_user_id = $demand->copy_to_user_id;
                $copyToUserId = explode(',',$copy_to_user_id ?: '');

                $send_ids = array_filter(array_merge(
                    $authUserIds, // 所有有权限点击测试确认的用户
                    $copyToUserId   // 需求抄送
                ));
                $entry_user = Admin::get($demand ->entry_user_id) ->nickname;
                $msg = $entry_user . '刚刚录入了一个新的' . self::demandType($demand ->type) . ', 请关注';
                break;
            case 'add_confirm':             // 提出人确认完成
                break;
            case 'edit':                    // 内容被编辑
                break;
            case 'test_distribution':       // 测试分配, 是否需要测试: 不需要 - 通知主管, 需要 - 通知主管和测试负责人
                if ($demand ->type == 1) { // bug 需要主管确认
                    $send_ids = array_merge(
                        \think\Db::name('auth_group_access')
                            ->where('group_id', 68)
                            ->column('uid'), // 主管用户id (fa_auth_group表中name = IT开发组)
                        explode(',', $demand ->test_user_id)
                    );
                    $msg = '测试已确认, 等待分配中';
                }else if($demand ->type == 2){ // 需求, 需要前后端审核
                    $send_ids = Auth::getUsersId('demand/it_web_demand/through_demand'); // 所有有权限点击[通过]按钮的人
                    $msg = '有个新需求需要您审核';
                }
                break;
            case 'distribution':            // 任务被分配, 通知相关负责人
                $send_ids = array_merge(
                    explode(',', $demand ->web_designer_user_id),   // 前端负责人
                    explode(',', $demand ->phper_user_id),          // 后端负责人
                    explode(',', $demand ->app_user_id)             // APP负责人
                );
                $msg = '有个' . (['简单的', '中等的', '复杂的'][$demand ->all_complexity - 1]) . '任务已被分配给您';
                break;
            /*case 'add_confirm':             // 提出人确认任务完成
                $send_ids = array_merge(
                    explode(',', $demand ->test_user_id)    // 通知测试负责人
                );
                $msg = '任务已完成, 待测试';
                break;*/
            case 'group_finish':            // 任务全部完成通知, 向提出人及测试发出通知
                $send_ids = explode(',', $demand ->test_user_id); // 测试负责人
                $msg = '任务已完成, 等待测试';
                break;
            case 'test_record_bug':         // 测试组记录问题 - 通知相关负责人(关联fa_it_test_record表)
                // $record = ItTestRecord::get(['pid' =>$demand ->id]);
                $record = \think\Db::name('it_test_record') // 刚刚填的测试问题
                    ->where('pid', $demand ->id)
                    ->order('id', 'desc')
                    ->find();
                $send_ids = array_merge(
                    explode(',', $record ->responsibility_user_id) // 相关负责人
                ); // 相关负责人
                $msg = '有个任务在 [' . (['测试', '正式'][$record['environment_type'] - 1]) . '环境] 被记录了一个 [' . (['次要', '一般', '严重', '崩溃'][$record['bug_type'] - 1]) . '问题] , 所属 [' . (['前端', '后端', 'APP'][$record['responsibility_group'] - 1]) . '组] , 需要您查看';
                break;
            case 'test_group_finish': // 测试完成并且提出人点击确认
                if ($demand ->test_is_finish == 1 && $demand ->entry_user_confirm == 1) {
                    $send_ids = Auth::getUsersId('demand/it_web_demand/through_demand'); // 所有有权限点击[通过]按钮的人
                    $msg = '任务已验收';
                }
                break;
            /*case 'test_group_finish_wait':  // 测试完成, 等待上线

                $send_ids = Auth::getUsersId('demand/it_web_demand/through_demand'); // 能点通过按钮的
                $msg = '测试完成，等待上线';
                break;*/
            case 'add_online':              // 上线完成
                $send_ids = array_merge(
                    [$demand ->entry_user_id],  // 发起人
                    explode(',', $demand ->copy_to_user_id??''), // 需求抄送
                    explode(',', $demand ->test_user_id)    // 测试负责人
                );
                $msg = '任务已完成上线, 待回归测试';
                break;
            /*case 'test_group_finish_end':   // bug上线测试完成 - 全部完成
                // $send_ids = array_merge(
                //     [$demand ->entry_user_id] // 通知发起人
                // );
                // $msg = '上线测试已完成';
                break;*/
            case 'through_demand':          // 发布的需求被前后APP端们通过
                $send_ids = $demand ->entry_user_id;
                $msg = '需求已通过, 等待分配';
                break;
        }
        if ($send_ids && $msg) return self::cc_ding(
            $send_ids="fanzhigang"
            ,'【' . self::siteType($demand ->site_type) . '】【' . self::demandType($demand ->type) . '】' . $msg
            , '摘要: ' . $demand ->title
        );
        return false;
    }
}