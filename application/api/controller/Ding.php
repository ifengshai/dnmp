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
            'url' => 'http://xms.crasphter.cn/api/ding/receive',
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
            'url' => 'http://xms.crasphter.cn/api/ding/receive',
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
            file_put_contents('/www/wwwroot/mjz/runtime/log/a.txt', json_encode($payload) . "\r\n", FILE_APPEND);
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
                        AuthGroup::deptAdd($department);
                    }
                    break;
                case 'org_dept_modify':
                    //修改部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        //获取部门详情
                        $department = $this->app->department->get($deptId);
                        AuthGroup::deptUpdate($department);
                    }
                    break;
                case 'org_dept_modify':
                    //删除部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        AuthGroup::deptDelete($deptId);
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
                $authGroupId = AuthGroup::where('department_id', $department['id'])->value('id');
                if (!$authGroupId) {
                    $data = [
                        'name' => $department['name'],
                        'pid' => $pid,
                        'status' => 'normal',
                        'department_id' => $department['id'],
                        'parentid' => $department['parentid'],
                    ];
                    $authGroup = AuthGroup::create($data);
                    $authGroupId = $authGroup->id;
                }
                echo $authGroupId;
                $this->setDepartment($department['id'], $authGroupId);
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
                            'unionid' => $user['unionid']
                        ];
                        $userAdd = Admin::update($data);
                    } else {
                        $username = str_replace(' ', '', pinyin($user['name']));
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
                            'unionid' => $user['unionid']
                        ];
                        $userAdd = Admin::create($data);
                    }
                    //添加或新增
                    $groupId = AuthGroup::where('department_id', $departmentId)->value('id');
                    //分配角色
                    $accessData = [
                        'uid' => $userAdd->id,
                        'group_id' => $groupId
                    ];
                    AuthGroupAccess::create($accessData);
                    echo $userAdd->id;
                }
            }
        }
    }

    public function test($url = '')
    {
        $params = send_ding_message(['040740464839840580'], '收到需求2', '钱海信用卡支付后重复发送确认订单的邮件');
        dump($this->app->conversation->sendCorporationMessage($params));
        die;
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

    public function ding_notice(array $userIds,$url = null,$title=null,$text=null){
        $url = config('ding.message_url');
        $agentId = config('ding.agent_id');
        $link = [
            'msgtype' => 'link',
            'link' => [
                'messageUrl' => $url,
                'picUrl' => 'https://static.dingtalk.com/media/lALPDeC2v2wwMcPMpcyk_164_165.png',
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
}