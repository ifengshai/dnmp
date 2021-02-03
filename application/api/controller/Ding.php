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
use app\admin\model\demand\DevelopDemand;
use app\admin\model\Department;
use app\common\model\Auth;
use fast\Random;
use think\Controller;
use EasyDingTalk\Application;
use think\Db;
use think\Request;
use app\admin\model\financepurchase\FinancePurchaseLog;
use app\admin\model\financepurchase\FinancePurchase;

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
     * 测试审批流程--示例
     *
     * @Description
     * @author wpl
     * @since 2020/06/01 17:35:27
     */
    public function initiate_approval_bak()
    {

        //  $list = $this->app->callback->list();
        //  dump($list);die;

        $userId = '071829462027950349';
        $params['agent_id'] = config('ding.agent_id');
        $params['process_code'] = config('ding.process_code');
        //发起人
        $params['originator_user_id'] = $userId;
        //发起人部门id
        $params['dept_id'] = 143678442;
        //审批人userid 多审批人依次传入
        $params['approvers'] = '285501046927507550,0550643549844645,056737345633028055';
        //抄送人
        $params['cc_list'] = '071829462027950349';
        //抄送节点
        $params['cc_position'] = 'FINISH';
        //表单参数
        $params['form_component_values'] = [
            [
                'name' => '采购方式',
                'value' => '线上采购',
            ],
            [
                'name' => '采购产品类型',
                'value' => '镜框',
            ],
            [
                'name' => '付款类型',
                'value' => '预付款',
            ],
            [
                'name' => '供应商名称',
                'value' => '临海市森邦眼镜源头厂家',
            ],
            [
                'name' => '付款比例',
                'value' => '30%',
            ],
            [
                'name' => '币种',
                'value' => '人民币',
            ],
            [
                'name' => '付款比例',
                'value' => '30%',
            ],
            [
                'name' => '采购事由',
                'value' => [
                    [
                        [
                            'name' => '采购单号',
                            'value' => 'PO20210113135657295686',
                        ],
                        [
                            'name' => '采购品名',
                            'value' => '镜架',
                        ],
                        [
                            'name' => '数量',
                            'value' => '20',
                        ],
                        [
                            'name' => '金额（元）',
                            'value' => '100',
                        ]
                    ],
                    [
                        [
                            'name' => '采购单号',
                            'value' => 'PO20210113134633462344',
                        ],
                        [
                            'name' => '采购品名',
                            'value' => '镜架',
                        ],
                        [
                            'name' => '数量',
                            'value' => '20',
                        ],
                        [
                            'name' => '金额（元）',
                            'value' => '500',
                        ]
                    ]
                ]
            ],
            [
                'name' => '付款总金额',
                'value' => '600',
            ],
            [
                'name' => '收款方名称',
                'value' => '测试名称',
            ],
            [
                'name' => '收款方账户',
                'value' => '测试账户',
            ],
            [
                'name' => '收款方开户行',
                'value' => '测试开户行',
            ],
            [
                'name' => '备注',
                'value' => '备注',
            ]

        ];

        $res = $this->app->process->create($params);
        dump($res);
    }



    /**
     * 获取钉盘space_id
     *
     * @Description
     * @author wpl
     * @since 2021/01/13 17:05:17 
     * @return void
     */
    public function test_detail()
    {
        $userId = '071829462027950349';
        $agentId = config('ding.agent_id');
        // $res = $this->app->process->get('43bab35a-169d-4eb6-8b95-4c908076489e');
        $res = $this->app->process->cspaceInfo($userId, $agentId);
        dump($res);
        die;
    }

    /**
     * 上传附件
     *
     * @Description
     * @author wpl
     * @since 2021/01/13 18:23:54 
     * @return void
     */
    public function ding_upload_file()
    {
        //上传文件
        $filesize = 10892;
        $agentId = config('ding.agent_id');
        $fileList = $this->app->file->uploadSingle(['C:\Users\Administrator\Desktop\test.docx'], $filesize, $agentId);

        //获取钉盘space_id
        $userId = '071829462027950349';
        $agentId = config('ding.agent_id');
        $spaceList = $this->app->process->cspaceInfo($userId, $agentId);


        //保存文件到钉盘
        $media_id = $fileList['media_id'];
        $space_id = $spaceList['result']['space_id'];
        $filename = 'test';
        $res = $this->app->file->saveFile($media_id, $agentId, $space_id, $filename);
        dump($res);
    }



    /**
     * 人员遗漏，手动补充
     *
     * @Description
     * @author Lx
     * @since 2020/06/01 17:35:27
     */
    public function test2()
    {
        $userId = '045127074321643707';
        $user = $this->app->user->get($userId);
        dump($user);
        die;
        Admin::userAdd($user);
        // $user=$this->app->attendance->schedules('2020-06-07');
        $dinguserlist = '1965280658937204,246806095338604104,203462064629067860,294026503134238817,224632105739221648,1700124228692306,115402543935694805,103733210730389629,225802421126255952,285168290324340480,251768502236303778';
        $time = 1592150400;
        //$user=$this->app->attendance->listByUsers('1965280658937204',$user_arr,'1592269200000','1592269200000');
        $user = $this->app->attendance->listByUsers('1965280658937204', $dinguserlist, $time . '000', $time . '000');
        $userlist = $user['result'];
        $rest_list = array();
        foreach ($userlist as $item) {
            if ($item['is_rest'] == 'Y') {
                $rest_list[] = Db::name('admin')->where('userid', $item['userid'])->value('id');
            }
        }
        //$aa=$this->app->attendance->groups();
        echo "<pre>";
        print_r($userlist);
        dump($rest_list);
        exit;
        //Admin::userAdd($user);
    }




    /*****************************start***********************************/

    /**
     * 测试审批流程
     *
     * @Description
     * @author wpl
     * @since 2020/06/01 17:35:27
     */
    public function initiate_approval($params)
    {

        $params['agent_id'] = config('ding.agent_id');
        $params['process_code'] = config('ding.process_code');
        //发起人
        $params['originator_user_id'] = $params['originator_user_id'];
        //发起人部门id
        $params['dept_id'] = $params['dept_id'];
        //审批人userid 多审批人依次传入
        $params['approvers'] = $params['approvers'];
        //抄送人
        $params['cc_list'] = $params['cc_list'];
        //抄送节点
        $params['cc_position'] = 'FINISH';
        //表单参数
        $params['form_component_values'] = $params['form_component_values'];
        //发起审批
        $res = $this->app->process->create($params);
        return $res;
        // dump($res);die;
        // if ($res['errcode'] == 0) {
        //     return $res;
        // } else {
        //     return false;
        // }
    }


    /**
     * 批量查询客服休息用户id
     *
     * @Description
     * @author mjj
     * @since 2020/06/15 15:52:31 
     * @param [type] 客服的钉钉id集合，格式'246806095338604104,285168290324340480,225802421126255952'
     * @param [type] 时间戳
     * @return void
     */
    public function getRestList($userlist_str, $time)
    {
        //listByUsers中的第一个参数为开发者李想的钉钉id,如果后期有问题及时更改
        $user = $this->app->attendance->listByUsers('1965280658937204', $userlist_str, $time . '000', $time . '000');
        $userlist = $user['result'];
        $rest_list = array();
        foreach ($userlist as $item) {
            if ($item['is_rest'] == 'Y') {
                $rest_list[] = Db::name('admin')->where('userid', $item['userid'])->value('id');
            }
        }
        return $rest_list;
    }
    /**
     * 注册事件回调
     * @return [type] [description]
     */
    public function register()
    {
        $params = [
            'call_back_tag' => ['user_add_org', 'user_modify_org', 'user_leave_org', 'org_dept_create', 'org_dept_modify', 'org_dept_remove', 'bpms_task_change'],

            'url' => 'https://mojing.nextmar.com/api/ding/receive',
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

            'call_back_tag' => ['user_add_org', 'user_modify_org', 'user_leave_org', 'org_dept_create', 'org_dept_modify', 'org_dept_remove', 'bpms_task_change'],
            'url' => 'https://mojing.nextmar.com/api/ding/receive',
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
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
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
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
                    break;
                case 'user_leave_org':
                    //用户离职
                    $userIds = $payload['UserId'];
                    foreach ($userIds as $userId) {
                        Admin::where('userid', $userId)->setField('status', 'hidden');
                    }
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
                    break;
                case 'org_dept_create':
                    //创建部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        //获取部门详情
                        $department = $this->app->department->get($deptId);
                        Department::deptAdd($department);
                    }
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
                    break;
                case 'org_dept_modify':
                    //修改部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        //获取部门详情
                        $department = $this->app->department->get($deptId);
                        Department::deptUpdate($department);
                    }
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
                    break;
                case 'org_dept_remove':
                    //删除部门
                    $deptIds = $payload['DeptId'];
                    foreach ($deptIds as $deptId) {
                        Department::deptDelete($deptId);
                    }
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.txt', json_encode($payload), FILE_APPEND);
                    break;
                case 'bpms_instance_change':
                    //审批任务事件(开始、结束、转交)
                    /**
                     * @todo 修改审批任务为完成状态
                     */
                    if ($payload['type'] == 'finish') {
                        //审核日志
                        FinancePurchaseLog::create([
                            'process_instance_id' => $payload['processInstanceId'],
                            'check_time' => $payload['finishTime'],
                            'title' => $payload['title'],
                            'result' => $payload['result'],
                            'userid' => $payload['staffId']
                        ]);

                        //判断审核状态 审核拒绝
                        if ($payload['result'] == 'refuse') {
                            FinancePurchase::where(['process_instance_id' => $payload['process_instance_id']])->update(['status' => 3]);
                            //最后一步判断如果为李亚方审核通过改为完成
                        } elseif ($payload['result'] == 'agree' && $payload['staffId'] == '171603353926064429') {
                            FinancePurchase::where(['process_instance_id' => $payload['process_instance_id']])->update(['status' => 4]);
                        }
                    }

                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.log', 'bpms_instance_change---------------' . serialize($payload) . "\n\n", FILE_APPEND);
                    break;

                case 'bpms_task_change':
                    file_put_contents('/www/wwwroot/mojing/runtime/log/Ding.log', 'bpms_task_change---------------' . serialize($payload) . "\n\n", FILE_APPEND);
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
                $depart = Department::where('department_id', $department['id'])->value('id');
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
                        if ($count == 1) {
                            $username = $username . $count;
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
        //获取指定用户的钉钉信息，添加到魔晶系统中
        $userId = '165829435336546371';
        $user = $this->app->user->get($userId);
        Admin::userAdd($user);
        echo 'ok';
        exit;
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
            if (count($results) < 2 && $results[0]['is_rest'] == 'Y') {
                continue;
            }
            //获取用户的工作的时间
            foreach ($results as $result) {
                $person[$userId] = [];
            }
        }

        // $res = $this->app->user->getDetailedUsers($department_id,'0','100');
        // 导入表，用户名 + 排班时间
        // 获取邮件为new的进行分配，其余按第一次分配的为准
        // 获取上班的人数 eg:5
        // 邮件id%5求余数分配
    }

    public function ding_notice(array $userIds, $url = null, $title = null, $text = null, $picUrl = 'https://static.dingtalk.com/media/lALPDeC2v2wwMcPMpcyk_164_165.png')
    {
        $url = $url ?: config('ding.message_url');
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
    public static function cc_ding($users, $title, $content, $url = null, $picUrl = 'https://static.dingtalk.com/media/lALPDeC2v2wwMcPMpcyk_164_165.png')
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        $instance = self::$instance;
        if (!is_array($users)) {
            $users = [$users];
        }
        foreach ($users as $k => &$user) {
            // 使用id获取userid
            if (($get = Admin::get(['id' => $user])) || ($get = Admin::get(['username' => $user]))) {
                $user = $get['userid'];
            }
            if (!$user) {
                unset($users[$k]);
            }
        }

        if (!$title) {
            $title = '您有一条新消息';
        }
        if (!$content) {
            $content = '请前往魔晶查看详情';
        }
        return $instance->ding_notice($users, $url, $title, strip_tags($content), $picUrl);
    }

    /**
     * 根据it_web_demand表返回任务类型
     * @param $type
     * @return string
     */
    public static function demandType($type)
    {
        return ['bug', '需求', '疑难'][$type - 1] ?? '消息';
    }

    /**
     * 优先级 priority
     * @param $type
     * @return string
     */
    public static function demandPriority($type)
    {
        return ['低', '中', '高'][$type - 1] ?? '低';
    }

    /**
     * 根据it_web_demand表返回站点名称
     */
    public static function siteType($type_id)
    {
        return [
            'zeelool',
            'voogueme',
            'nihao',
            'wesee',
            'orther'
        ][$type_id - 1] ?? '';
    }

    /**
     * 回调发起钉钉通知
     * @param string $name 事件名称
     * @param \app\admin\model\demand\ItWebDemand $demand 需求管理模型
     */
    public static function dingHook(string $name, \app\admin\model\demand\ItWebDemand $demand)
    {
        if ($demand->type == 3) {
            return false;
        } // 疑难不作处理
        $demand = ItWebDemand::get($demand->id);
        $send_ids = []; // 被发送者id, userid或nickname
        $msg = ''; // 消息内容
        switch ($name) {
            case 'add':                     // 添加内容通知, 发送给所有测试人员
                $authUserIds = Auth::getUsersId('demand/it_web_demand/test_distribution') ?: [];
                $copy_to_user_id = $demand->copy_to_user_id;
                $copyToUserId = explode(',', $copy_to_user_id ?: '');

                $send_ids = array_filter(array_merge(
                    $authUserIds, // 所有有权限点击测试确认的用户
                    $copyToUserId   // 需求抄送
                ));
                $entry_user = Admin::get($demand->entry_user_id)->nickname;
                $msg = $entry_user . '刚刚录入了一个新的' . self::demandType($demand->type) . ', 请关注';
                break;
            case 'add_confirm':             // 提出人确认完成
                break;
            case 'edit':                    // 内容被编辑
                break;
            case 'test_distribution':       // 测试分配, 是否需要测试: 不需要 - 通知主管, 需要 - 通知主管和测试负责人
                if ($demand->type == 1) { // bug 需要主管确认
                    $send_ids = array_merge(
                        \think\Db::name('auth_group_access')
                            ->where('group_id', 68)
                            ->column('uid'), // 主管用户id (fa_auth_group表中name = IT开发组)
                        explode(',', $demand->test_user_id)
                    );
                    $msg = '测试已确认, 等待分配中';
                } elseif ($demand->type == 2) { // 需求, 需要前后端审核
                    $send_ids = Auth::getUsersId('demand/it_web_demand/through_demand'); // 所有有权限点击[通过]按钮的人
                    $msg = '有个新需求需要您审核';
                }
                break;
            case 'distribution':            // 任务被分配, 通知相关负责人
                $send_ids = array_merge(
                    explode(',', $demand->web_designer_user_id),   // 前端负责人
                    explode(',', $demand->phper_user_id),          // 后端负责人
                    explode(',', $demand->app_user_id)             // APP负责人
                );
                $msg = '有个' . (['简单的', '中等的', '复杂的'][$demand->all_complexity - 1]) . '任务已被分配给您';
                break;
                /*case 'add_confirm':             // 提出人确认任务完成
                $send_ids = array_merge(
                    explode(',', $demand ->test_user_id)    // 通知测试负责人
                );
                $msg = '任务已完成, 待测试';
                break;*/
            case 'group_finish':            // 任务全部完成通知, 向提出人及测试发出通知
                $send_ids = explode(',', $demand->test_user_id); // 测试负责人
                $msg = '任务已完成, 等待测试';
                break;
            case 'web_group_finish':
                //通知前端人员
                $send_ids = explode(',', $demand->web_designer_user_id); // 前端负责人
                $msg = '任务已完成, 等待测试';
                break;
            case 'php_group_finish':
                //通知后端人员
                $send_ids = explode(',', $demand->phper_user_id); // 后端负责人
                $msg = '任务已完成, 等待测试';
                break;
            case 'test_record_bug':         // 测试组记录问题 - 通知相关负责人(关联fa_it_test_record表)
                // $record = ItTestRecord::get(['pid' =>$demand ->id]);
                $record = \think\Db::name('it_test_record') // 刚刚填的测试问题
                    ->where('pid', $demand->id)
                    ->order('id', 'desc')
                    ->find();
                $send_ids = array_merge(
                    explode(',', $record->responsibility_user_id) // 相关负责人
                ); // 相关负责人
                $msg = '有个任务在 [' . (['测试', '正式'][$record['environment_type'] - 1]) . '环境] 被记录了一个 [' . (['次要', '一般', '严重', '崩溃'][$record['bug_type'] - 1]) . '问题] , 所属 [' . (['前端', '后端', 'APP'][$record['responsibility_group'] - 1]) . '组] , 需要您查看';
                break;
            case 'test_group_finish': // 测试完成并且提出人点击确认
                if ($demand->test_is_finish == 1 && $demand->entry_user_confirm == 1) {
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
                    [$demand->entry_user_id],  // 发起人
                    explode(',', $demand->copy_to_user_id ?? ''), // 需求抄送
                    explode(',', $demand->test_user_id)    // 测试负责人
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
                $send_ids = $demand->entry_user_id;
                $msg = '需求已通过, 等待分配';
                break;
        }
        if ($send_ids && $msg) {

            return self::cc_ding(
                $send_ids,
                '【' . self::siteType($demand->site_type) . '】【' . self::demandType($demand->type) . '】' . $msg,
                '摘要: ' . '[id:' . $demand->id . '],title:' . $demand->title
            );
        }
        return false;
    }


    /**
     * 回调发起钉钉通知
     * @param string $name 事件名称
     * @param \app\admin\model\demand\DevelopDemand $demand 需求管理模型
     */
    public static function dingHookByDevelop(string $name, \app\admin\model\demand\DevelopDemand $demand)
    {
        if ($demand->type == 3) {
            return false;
        } // 疑难不作处理
        $demand = DevelopDemand::get($demand->id);
        $send_ids = []; // 被发送者id, userid或nickname
        $msg = ''; // 消息内容
        switch ($name) { //type =1 BUG    type =2 需求
            case 'add':                     // 添加内容通知, 需求管理通知产品经理审核，产品经理审核通过通知 开发主管审核，  开发主管分配完成 通知开发负责人，  开发人员点击开发完成，通知测试人，测试通过通知产品经理确认，产品经理确认完成 通知测试进行回归测试。中间节点，测试记录问题通知责任人
                if ($demand->type == 1) {
                    $send_ids = Auth::getUsersId('demand/develop_demand/review_status_develop') ?: [];
                    $entry_user = Admin::get($demand->create_person_id)->nickname;
                    $msg = $entry_user . '刚刚录入了一个新的[' . self::demandType($demand->type) . '], 请关注';
                    break;
                } elseif ($demand->type == 2) {
                    $send_ids = Auth::getUsersId('demand/develop_demand/review') ?: [];
                    $entry_user = Admin::get($demand->create_person_id)->nickname;
                    $msg = $entry_user . '刚刚录入了一个新的' . self::demandType($demand->type) . ', 等待您的审核';
                    break;
                }
                // no break
            case 'review':             // 产品经理审核通过
                if ($demand->review_status_manager == 1) {
                    $send_ids = Auth::getUsersId('demand/develop_demand/review_status_develop') ?: []; // 所有有权限点击测试确认的用户
                    $msg = '一个优先级[' . self::demandPriority($demand->type) . ']的任务产品经理已审核通过, 等待您的审核';
                }
                break;
            case 'distribution':                    // 分配开发人员
                $send_ids =  explode(',', $demand->assign_developer_ids); //获取开发人员
                $msg = '有个[' . (['简单的', '中等的', '复杂的'][$demand->complexity - 1]) . ']任务已被分配给您';
                break;
            case 'set_complete_status':       // 开发完成-- 通知测试人员
                if ($demand->is_test == 1) { // 是否需要测试
                    $send_ids =  explode(',', $demand->test_person);
                    $msg = '有个测试复杂度为[' . (['简单的', '中等的', '复杂的'][$demand->test_complexity - 1]) . ']任务已开发完成,等待您的测试';
                } elseif ($demand->is_test == 0) { // 不需要测试,开发完成直接上线,根据不同类型通知不同人

                    if ($demand->type == 2) { //需求类型,产品经理确认
                        $send_ids = Auth::getUsersId('demand/develop_demand/review_status_develop'); // 所有有权限点击[通过]按钮的人
                        $msg = '一个任务已开发完成,等待您的确认';
                        //需求开发完成时，钉钉推送创建人和提出人
                        $user_all = explode(',', $demand->duty_user_id);
                        $user_all[] = $demand->create_person_id;
                        Ding::cc_ding(array_merge(array_filter($user_all)), '任务ID:' . $demand->id . '+任务已开发完成', $demand->title);
                    } elseif ($demand->type == 1) { //BUG 类型`
                        $send_ids = array_merge(
                            Auth::getUsersId('demand/develop_demand/review_status_develop'),
                            explode(',', $demand->assign_developer_ids)
                        );
                        $msg = '一个任务已开发完成,请您关注';
                    }
                }
                break;
            case 'test_is_passed': // 测试站通过测试,通知相关人员进行确认
                if ($demand->type == 2) { //需求类型,产品经理确认
                    $send_ids =  Auth::getUsersId('demand/develop_demand/is_finish_task'); // 所有有权限点击[通过]按钮的人
                    $msg = '测试已完成,等待您的确认';
                } elseif ($demand->type == 1) { //BUG 类型
                    $send_ids = explode(',', $demand->assign_developer_ids);
                    $msg = '测试已完成,请您关注';
                }
                break;
            case 'is_finish_task':          // 需求列表  产品经理确认
                if ($demand->is_test == 1) { // 是否需要测试
                    $send_ids =   explode(',', $demand->test_person);
                    $msg = '有个测试复杂度为[' . (['简单的', '中等的', '复杂的'][$demand->test_complexity - 1]) . ']任务已产品经理已确认,等待回归测试';
                } else {
                    $send_ids =  $demand->create_person_id;
                    $msg = '任务已完成,请关注';
                }
                break;
            case 'is_finish_bug':          // BUG 列表 已上线
                if ($demand->is_test == 1) { // 是否需要测试
                    $send_ids = explode(',', $demand->test_person);
                    $msg = '有个测试复杂度为[' . (['简单的', '中等的', '复杂的'][$demand->test_complexity - 1]) . ']任务已上线,等待回归测试';
                } else {
                    $send_ids =  $demand->create_person_id;
                    $msg = '任务已完成,请关注';
                }
                break;
            case 'test_record_bug':          // 测试站记录问题
                $send_ids = explode(',', $demand->assign_developer_ids);
                $record = \think\Db::name('develop_test_record') // 刚刚填的测试问题
                    ->where('pid', $demand->id)
                    ->order('id', 'desc')
                    ->find();

                $msg = '有个任务在 [测试环境] 被记录了一个[' . (['次要', '一般', '严重', '崩溃'][$record['bug_type'] - 1]) . '问题] , 需要您查看';
                break;
            case 'regression_test_info':          // 正式站记录问题
                $send_ids =    explode(',', $demand->assign_developer_ids);
                $record = \think\Db::name('develop_test_record') // 刚刚填的测试问题
                    ->where('pid', $demand->id)
                    ->order('id', 'desc')
                    ->find();

                $msg = '有个任务在 [正式环境] 被记录了一个 [' . (['次要', '一般', '严重', '崩溃'][$record['bug_type'] - 1]) . '问题] , 需要您查看';
                break;

            case 'test_complete':       // 回归测试完成-通知提出人
                $send_ids =  $demand->create_person_id;
                $msg = '任务已完成,请关注';
                break;
        }
        if ($send_ids && $msg) {

            if (is_array($send_ids)) {
                //排除谢梦飞账号
                if (in_array(80, $send_ids)) {
                    $key = array_search(80, $send_ids);
                    unset($send_ids[$key]);
                    $send_ids = array_values($send_ids);
                }
                //排除张晓账号
                if (in_array(148, $send_ids)) {
                    $key = array_search(148, $send_ids);
                    unset($send_ids[$key]);
                    $send_ids = array_values($send_ids);
                }
                // file_put_contents('/www/wwwroot/mojing/runtime/log/sku.log', json_encode($send_ids) . "\r\n", FILE_APPEND);
            }

            return self::cc_ding(
                $send_ids,
                '【开发组' . self::demandType($demand->type) . '】' . $msg,
                '摘要: ' . '[id:' . $demand->id . '],title:' . $demand->title
            );
        }
        return false;
    }
}
