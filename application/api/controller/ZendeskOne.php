<?php
/**
 * Created by PhpStorm.
 * User: josephine
 * Date: 2020/2/20
 * Time: 09:32
 */

namespace app\api\controller;

use app\admin\model\zendesk\ZendeskReply;
use app\admin\model\zendesk\ZendeskReplyDetail;
use fast\Http;
use fast\Trackingmore;
use think\Controller;
use think\Db;
use think\Exception;
use Zendesk\API\HttpClient as ZendeskAPI;
use function Stringy\create as s;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

/**
 * 临时使用的只处理processing的方法
 * Class ZendeskOne
 * @package app\api\controller
 */
class ZendeskOne extends Controller
{
    //基本参数配置
    protected $subdomain = "zeelooloptical";
    protected $username = "complaint@zeelool.com";
    protected $token = "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B";
    protected $count = 1;
    //客户按要求回复的内容
    protected $auto_answer = [
        'order status',
        'change information',
        'others'
    ];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    //匹配自动回复的单词
    protected $preg_word = ['when', 'delivery', 'deliver', 'receive', 'receiving', 'track', 'tracking', 'ship', 'shipped', 'shipment', 'shipping', 'status', 'eta', 'expected', 'expect', 'update', 'where is', 'wait', 'waiting', 'send', 'arrive', 'arriving', 'check', 'get', 'mail', 'find'];
    public $client = null;
    //public $testId = [383401621551,383402124271,383347471012,393708243591,383347496492,394745643811,394627403612,394627403852,394745654451,394627408052,383402007531,394627410752,394745679291];

    /**
     * 方法初始化
     * @throws \Exception
     */
    public function _initialize()
    {
        parent::_initialize();
        try{
            $this->client = new ZendeskAPI($this->subdomain);
            $this->client->setAuth('basic', ['username' => $this->username, 'token' => $this->token]);
        }catch (\Exception $e){
            exception('zendesk链接失败', 100006);
        }
    }

    /**
     * 测试的代码
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function test()
    {
        //try {
            // Query Zendesk API to retrieve the ticket details
             //$id = 86205;
             //$ticket = $this->client->tickets()->find($id);
            // $result = $this->client->tickets($id)->comments()->findAll();
            // $requester_email = $ticket->via->source->from->address;
            // $count = $result->count;
            // $comments = $result->comments;
            // //判断，如果最后一条为发送着的评论，则需要回复，如果为客服或自动回复的，则不需要回复
            // $last_comment = $comments[$count-1];
            // $body = strtolower($last_comment->body);
            // $customr_comment_all = [
            //     'now' => ' '.$body.' ',
            //     'first' => ' '.($comments[0])->body.' ',
            //     'title' => ' '.$ticket->ticket->subject.' '
            // ];
            // $get_order_id = $this->getOrderId($customr_comment_all);
            // $order = $this->findOrderByEmail($requester_email,$get_order_id);
            // $res = $this->getTrackMsg(41);
            $apiKey = 'F26A807B685D794C676FA3CC76567035 '; // your api key

            $trackNumber = '3616952791'; // Your track number

            $trackingConnector = new TrackingConnector($apiKey);
            $trackingConnector->register($trackNumber,100001);
            $trackNumbersHistories =  $trackingConnector->getTrackInfo($trackNumber,100001);
            echo 1;
            //$track = new Trackingmore();
            //74890988318622362133
//            $res = $track->getRealtimeTrackingResults('usps', '7489098831862085069');
//            echo json_encode($res);
            die;
//        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
//            echo $e->getMessage().'</br>';
//        }
    }
    /**
     * 查询tickets
     */
    public function searchTickets()
    {
        $search = [
            'type' => 'ticket',
            'via' => ['mail','web','web_widget'],
            'status' => ['new','open'],
            'tags' => [
                'keytype' => '-',
                'value' => '转客服'
            ], // -排除此tag
            'assignee' => [
                382940274852,
                'none'
            ],
//            'requester' => [393708243591],
             'updated_at' => [
                 'valuetype' => '>=',
                 'value'   => '20minutes',
             ],//>=意思是3分钟之内，<=是三分钟之外
            'created_at' => [
                'valuetype' => '>=',
                'value'   => '2020-04-09T01:00:00Z'
            ], //添加创建时间的限制
            'order_by' => 'updated_at',
            'sort' => 'desc'
        ];
        $this->getTickets($search);
        echo 'success';
        exit;
    }

    /**
     * 得到查找的tickets
     * @param array $array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getTickets(Array $array)
    {
        $params = $this->parseStr($array);
        $search = $this->client->search()->find($params);
        $tickets = $search->results;
        if(!$search->count){
            return true;
        }
        //$page = ceil($search->count / 100 );
        //先获取第一页的,一次100条
        $this->findCommentsByTickets($tickets);
//        if($page > 1){
//            //获取后续的
//            for($i=2;$i<= $page;$i++){
//                $search = $this->client->search()->find($params,['page' => $i]);
//                $tickets = $search->results;
//                $this->findCommentsByTickets($tickets);
//            }
//        }

    }

    /**
     * 查找评论并回复
     * @param $tickets
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findCommentsByTickets($tickets)
    {
        foreach($tickets as $key => $ticket){
//           if($key >= 10){
//               break;
//           }
            $id = $ticket->id;
            //发送者的id
            $requester_id = $ticket->requester_id;
            //所有的tag
            $tags = $ticket->tags;
            $subject = $ticket->subject;
            //email
            $requester_email = $ticket->via->source->from->address;
            try{
                $result = $this->client->tickets($id)->comments()->findAll();
            }catch (\Exception $e){
                //此次获取失败则继续下一条
                continue;
                //exception('获取邮件评论失败',10002);
            }

            $count = $result->count;
            $comments = $result->comments;
            //判断，如果最后一条为发送着的评论，则需要回复，如果为客服或自动回复的，则不需要回复
            $last_comment = $comments[$count-1];
            //if(in_array($requester_id,$this->testId)) {
                $reply_detail_data = [];
                if ($last_comment->author_id == $requester_id) {
                    //邮件的内容
                    $body = strtolower($last_comment->body);
                    //开始匹配邮件内容
                    //查看是否已有自动回复的tag
                    if (in_array('自动回复', $tags)) { //次类是顾客根据要求回复的内容
                        //file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk2.txt',$ticket->id."\r\n",FILE_APPEND);
                        $answer_key = 0;
                        foreach ($this->auto_answer as $key => $answer) {
                            //回复内容包含自动回复的内容，且相匹配
                            if (s($body)->contains($answer)) {
                                $answer_key = $key + 1;
                                break;//匹配到则跳出循环
                            }
                        }
                        //web时无email时获取最后一条评论里的email，都无email则转客服
                        if(!$requester_email){
                            $requester_email = $last_comment->via->source->from->address;
                        }
                        //查询订单状态的
                        if ($answer_key == 1 && $requester_email) {
                            $params = $this->sendByOrder($ticket,$comments,$body,$requester_email);
                        } else{
                            //open，转客服
                            $params = [
                                'tags' => ['转客服'],
                                'status' => 'open'
                            ];
                        }
                        //判断是否有主评论，无则不新增
                        if(!empty($params)){
                            if($zendesk_reply = ZendeskReply::get(['email_id' => $ticket->id])){
                                //添加用户的评论
                                ZendeskReplyDetail::create([
                                    'reply_id' => $zendesk_reply->id,
                                    'body' => $last_comment->body,
                                    'html_body' => $last_comment->html_body,
                                    'tags' => join(',',array_unique(array_merge($tags, $params['tags']))),
                                    'status' => $ticket->status,
                                    'assignee_id' => 382940274852,
                                    'is_admin' => 2
                                ]);
                                //回复评论
                                $reply_detail_data = [
                                    'reply_id' => $zendesk_reply->id,
                                    'body' => $params['comment']['body'] ? $params['comment']['body'] : '',
                                    'html_body' => $params['comment']['body'] ? $params['comment']['body'] : '',
                                    'tags' => join(',',array_unique(array_merge($tags, $params['tags']))),
                                    'status' => $params['status'],
                                    'assignee_id' => 382940274852,
                                    'is_admin' => 1
                                ];
                            }
                            //tag合并
                            $params['tags'] = array_unique(array_merge($tags, $params['tags']));
                            $params['comment']['author_id'] = 382940274852;
                            $params['assignee_id'] = 382940274852;
                            $res = $this->autoUpdate($id, $params);
                            if($res){
                                if(!empty($reply_detail_data)){
                                    $reply_detail_res = ZendeskReplyDetail::create($reply_detail_data);
                                    if($reply_detail_res){
                                        //更新主表状态
                                        ZendeskReply::where('id',$zendesk_reply->id)->update([
                                            'status' => $reply_detail_data['status'],
                                            'tags' => join(',',$params['tags'])
                                        ]);
                                    }
                                }
                            }
                        }

                    } else {
                        //匹配到相应的关键字，自动回复消息，修改为pending，回复共客户选择的内容，并且不包含return，refund
                        if ((s($body)->containsAny($this->preg_word) === true || s($subject)->containsAny($this->preg_word) === true) && s($body)->containsAny(['return','refund']) === false && s($subject)->containsAny(['return','refund']) === false) {
                            $reply_detail_data = [];
                            $recent_reply_count = 0;
                            //判断最近12小时发送的第几封，超过2封，超过2封直接转客服+tag-》多次发送
                            if($requester_email){
                                $recent_reply_count = ZendeskReply::where('email',$requester_email)
                                    ->whereTime('create_time','-12 hours')
                                    ->count();
                            }
                            if($recent_reply_count >= 2){
                                $params = [
                                    'tags' => ['自动回复','转客服', '多次发送'],
                                    //'status' => 'open'
                                ];
                            }else{
                                //回复模板1：状态pending，增加tag自动回复
                                $params = [
                                    'comment' => [
                                        'body' => config('zendesk.templates')['t1']
                                    ],
                                    'tags' => ['自动回复'],
                                    'status' => 'pending'
                                ];
                            }

                            //file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.txt',$ticket->id."\r\n",FILE_APPEND);
                            //如果是第一条评论，则把对应的客户内容插入主表，回复内容插入附表，其余不做处理
                            if($count == 1){
                                //主email
                                $reply_data = [
                                    'email' => $requester_email,
                                    'title' => $ticket->subject,
                                    'email_id' => $ticket->id,
                                    'body' => $last_comment->body,
                                    'html_body' => $last_comment->html_body,
                                    'tags' => join(',',$tags),
                                    'status' => $ticket->status,
                                    'requester_id' => $requester_id,
                                    'assignee_id' => $ticket->assignee_id ? $ticket->assignee_id : 0,
                                    'source' => $ticket->via->channel

                                ];
                                //添加主评论
                                $zendesk_reply = ZendeskReply::create($reply_data);
                                //file_put_contents('/www/wwwroot/mojing/runtime/log/zendeskreply.txt',$zendesk_reply->email_id."\r\n",FILE_APPEND);
                                if(!$zendesk_reply->email_id){
                                    //file_put_contents('/www/wwwroot/mojing/runtime/log/zendeskreply2.txt',$zendesk_reply->email_id."\r\n",FILE_APPEND);
                                }
                                //回复评论
                                if($zendesk_reply->id){
                                    $reply_detail_data = [
                                        'reply_id' => $zendesk_reply->id,
                                        'body' => $params['comment']['body'],
                                        'html_body' => $params['comment']['body'],
                                        'tags' => join(',',array_unique(array_merge($tags, $params['tags']))),
                                        'status' => isset($params['status']) ? $params['status'] : $ticket->status,
                                        'assignee_id' => 382940274852,
                                        'is_admin' => 1
                                    ];
                                }

                            }
                            if (!empty($params)) {
                                //tag合并
                                $params['tags'] = array_unique(array_merge($tags, $params['tags']));
                                $params['comment']['author_id'] = 382940274852;
                                $params['assignee_id'] = 382940274852;
                                $res = $this->autoUpdate($id, $params);
                                if($res){
                                    if(!empty($reply_detail_data)){
                                        $reply_detail_res = ZendeskReplyDetail::create($reply_detail_data);
                                        if($reply_detail_res){
                                            //更新主表状态
                                            ZendeskReply::where('id',$zendesk_reply->id)->update([
                                                'status' => $reply_detail_data['status'],
                                                'tags' => join(',',$params['tags'])
                                            ]);

                                        }
                                    }
                                }
                            }
                        }
                    }

                }
           // }
        }
    }

    /**
     * 回复消息，更新状态
     * @param $ticket_id
     * @param $params
     * @throws \Exception
     */
    public function autoUpdate($ticket_id,$params,$echo = true)
    {
        try{
            $this->client->tickets()->update($ticket_id, $params);
            if($echo) echo $ticket_id . "\r\n";
            sleep(1);
        }catch (\Exception $e){
            return false;
            //exception($e->getMessage(), 10001);
        }
        return true;


    }
    /**
     * 查询邮件状态发送相关模板
     * @param $ticket
     * @param $comments
     * @param $body
     * @param $requester_email
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendByOrder($ticket,$comments,$body,$requester_email)
    {
        //客户第一条回复，现在的回复，主题中提取订单号
        $customr_comment_all = [
            'now' => ' '.$body.' ',
            'first' => ' '.($comments[0])->body.' ',
            'title' => ' '.$ticket->subject.' '
        ];
        $get_order_id = $this->getOrderId($customr_comment_all);
        $order = $this->findOrderByEmail($requester_email,$get_order_id);
        $status = $order['status'];
        $increment_id = $order['increment_id'];
        if ($status == 'pending' or $status == 'creditcard_failed') {
            //状态改solved，tag支付失败
            $params = [
                'comment' => [
                    'body' => config('zendesk.templates')['t3']
                ],
                'tags' => ['支付失败'],
                'status' => 'solved'
            ];
        } elseif ($status == 'canceled') {
            //状态solved，tag取消订单
            $params = [
                'comment' => [
                    'body' => sprintf(config('zendesk.templates')['t7'], $increment_id)
                ],
                'tags' => ['取消订单'],
                'status' => 'solved'
            ];
        } elseif($status == 'processing') {
            $params = [
                'comment' => [
                    'body' => config('zendesk.templates')['t15']
                ],
                'tags' => ['未发货'],
                'status' => 'pending'
            ];

        } elseif ($status == 'complete') {
            $res = $this->getTrackMsg($order['order_id']);
            $shipTime = $this->getShipTime($order['order_id']);
            $diffTime = ceil((time() - $shipTime) / (3600 * 24 * 7));
            //判断是否签收
            if ($res['status'] == '40') { //已签收
                $params = [
                    'comment' => [
                        'body' => sprintf(config('zendesk.templates')['t4'], $res['updated_at'], $res['track_number'], $res['carrier_code'], $res['lastEvent'], $res['lastUpdateTime'])
                    ],
                    'tags' => ['已签收', '查询物流信息'],
                    'status' => 'solved'
                ];
            } elseif (in_array($res['status'], [10, 20, 30])) { //判断物流时效
                $params = [
                    'comment' => [
                        'body' => ''
                    ],
                    'tags' => [],
                    'status' => 'pending'
                ];

                //根据发货时间进行补偿
                //2周内
                if ($diffTime <= 2) {
                    $params = [
                        'comment' => [
                            'body' => sprintf(config('zendesk.templates')['t9'], date('Y-m-d H:i', $shipTime), $res['track_number'], $res['carrier_code'], $res['lastEvent'])
                        ],

                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                } elseif ($diffTime <= 3 && $diffTime > 2) {
                    $params = [
                        'comment' => [
                            'body' => sprintf(config('zendesk.templates')['t10'], $res['carrier_code'], $res['track_number'], date('Y-m-d H:i', $shipTime))
                        ],
                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                } elseif ($diffTime <= 4 && $diffTime > 3) {
                    $params = [
                        'comment' => [
                            'body' => config('zendesk.templates')['t11']
                        ],
                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                } elseif ($diffTime <= 6 && $diffTime > 4) {
                    $params = [
                        'comment' => [
                            'body' => config('zendesk.templates')['t12']
                        ],
                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                } elseif ($diffTime <= 9 && $diffTime > 6) {
                    $params = [
                        'comment' => [
                            'body' => config('zendesk.templates')['t13']
                        ],
                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                } elseif ($diffTime > 9) {
                    $params = [
                        'comment' => [
                            'body' => config('zendesk.templates')['t14']
                        ],
                        'tags' => ['超时', '查询物流信息'],
                        'status' => 'pending'
                    ];
                }
            } elseif (in_array($res['status'], [35,50])){
                $params = [
                    'comment' => [
                        'body' => sprintf(config('zendesk.templates')['t16'], date('Y-m-d H:i', $shipTime),$res['track_number'], $res['carrier_code'], $res['lastEvent'],$res['carrier_code'])
                    ],
                    'tags' => ['投递失败', '可能异常', '查询物流信息'],
                    'status' => 'pending'
                ];
            }else { //转客服，状态open
                //状态open，tag转客服
                $params = [
                    'tags' => ['转客服', '查询物流信息'],
                    'status' => 'open'
                ];
            }
        } else {
            //状态open，tag转客服
            $params = [
                'tags' => ['转客服'],
                'status' => 'open'
            ];
        }
        return $params;
    }
    /**
     * 获取物流状态
     * @param $order_id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getTrackMsg($order_id)
    {
        $res = [
            'status' => '',
            'lastUpdateTime' => '',
            'lastEvent' => '',
            'carrier_code' => '',
            'updated_at' => '',
            'track_number' => '',
        ];
        $track_result = Db::connect('database.db_zeelool')
            ->table('sales_flat_shipment_track')
            ->field('track_number,title,updated_at')
            ->where('order_id',$order_id)
            ->find();
        //查询物流信息
        $title = strtolower(str_replace(' ', '-', $track_result['title']));
        if($title == 'china-post'){
            $title = 'china-ems';
        }
        $trackNumber = $track_result['track_number']; // Your track number
        try{
            $trackingConnector = new TrackingConnector($this->apiKey);
            $carrier = $this->getCarrier($title);
            //无物流商，直接返回
            if(!$carrier['carrierId']){
                return $res;
            }
            $trackingConnector->register($trackNumber,$carrier['carrierId']);


            $trackNumbersHistories =  $trackingConnector->getTrackInfo($trackNumber,$carrier['carrierId']);
            $data = $this->formatTrack($trackNumbersHistories);
        }catch(\Exception $e){
            return $res;
        }
        $res = [
            'status' => $data['status'],
            'lastUpdateTime' => $data['lastUpdateTime'],
            'lastEvent' => $data['lastEvent'],
            'carrier_code' => $carrier['title'],
            'updated_at' => $track_result['updated_at'],
            'track_number' => $track_result['track_number']
        ];
        return $res;

    }

    /**
     * 格式化查询信息
     * @param $trackNumbersHistories
     * @return array|bool
     */
    public function formatTrack($trackNumbersHistories)
    {
        $code = isset($trackNumbersHistories['code']) ? $trackNumbersHistories['code'] : 0;
        if($code !== 0){
            exception('查询失败');
            return false;
        }
        $track = $trackNumbersHistories['track'];
        $lastEvent = $track['z0']['z'];
        $lastUpdateTime = $track['z0']['a'];
        //0：查询不到，10：运输中，20：运输过久，30：到达待取，35：投递失败，40：成功签收，50：可能异常
        $status = $track['e'];
        return compact('lastEvent','lastUpdateTime','status');
    }
    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    public function getCarrier($title)
    {
        $carrierId = '';
        if(stripos($title,'post') !== false){
            $carrierId = 'chinapost';
            $title = 'China Post';
        }elseif(stripos($title,'ems') !== false){
            $carrierId = 'chinaems';
            $title = 'China Ems';
        }elseif(stripos($title,'dhl') !== false){
            $carrierId = 'dhl';
            $title = 'DHL';
        }elseif(stripos($title,'fede') !== false){
            $carrierId = 'fedex';
            $title = 'Fedex';
        }elseif(stripos($title,'usps') !== false){
            $carrierId = 'usps';
            $title = 'Usps';
        }elseif(stripos($title,'yanwen') !== false){
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        }elseif(stripos($title,'cpc') !== false){
            $carrierId = 'cpc';
            $title = 'Canada Post';
        }
        $carrier = [
            'dhl' => '100001',
            'chinapost' => '03011',
            'chinaems' => '03013',
            'cpc' =>  '03041',
            'fedex' => '100003',
            'usps' => '21051',
            'yanwen' => '190012'
        ];
        if($carrierId){
            return ['title' => $title,'carrierId' => $carrier[$carrierId]];
        }
        return ['title' => $title,'carrierId' => $carrierId];
    }

    /**
     * 匹配订单号，优先级：标题>第一条回复的订单号>第二次回复的订单号
     * @param $comments
     * @return mixed|string
     */
    function getOrderId($comments)
    {
        $pattern = '/[^\d]([100|400]\d{8})[^\d]/iU';
        $order_id = '';
        foreach($comments as $comment){
            preg_match($pattern,$comment,$matches);
            if($matches){
                $order_id = $matches[1];
            }
        }
        return $order_id;
    }

    /**
     * 通过email,订单号，用户id查找订单号
     * @param $email
     * @param string $order_id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findOrderByEmail($email,$order_id = '')
    {
        //先查订单号
        if($order_id){
            $order = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->field('entity_id,state,status,increment_id,created_at')
                ->where('increment_id',$order_id)
                ->order('entity_id desc')
                ->find();
        }

        //查询不到查询用户email
        if(empty($order)){
            //先用email
            $order = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->field('entity_id,state,status,increment_id,created_at')
                ->where('customer_email',$email)
                ->order('entity_id desc')
                ->find();
        }

        //都查不到的话，只用订单号
        if(empty($order)){
            $customer = Db::connect('database.db_zeelool')
                ->table('customer_entity')
                ->where('email',$email)
                ->find();
            if(!empty($customer)){
                $order = Db::connect('database.db_zeelool')
                    ->table('sales_flat_order')
                    ->field('entity_id,state,status,increment_id,created_at')
                    ->where('customer_id',$customer['entity_id'])
                    ->order('entity_id desc')
                    ->find();
            }

        }
        if(!empty($order)){
            $res = [
                'status' => $order['status'],
                'increment_id' => $order['increment_id'],
                'order_id' => $order['entity_id'],
                'created_at' => $order['created_at'],
                'ship' => 0,
                'order_id' => $order['entity_id']
            ];

        }else{

            $res = [
                'status' => '',
                'increment_id' => '',
                'order_id' => '',
                'created_at' => '',
                'ship' => 0,
                'order_id' => $order['entity_id']
            ];
        }
        return $res;

    }

    /**
     * 获取发货的时间
     * @param $order_id
     * @return false|int
     */
    public function getShipTime($order_id)
    {
        $created_at = Db::connect('database.db_zeelool')
            ->table('sales_flat_shipment_track')
            ->where('order_id',$order_id)
            ->value('created_at');
        return strtotime($created_at);
    }
    /**
     * 格式化筛选条件
     * @param array $array
     * @return string
     */
    protected function parseStr(Array $array)
    {
        $params = '';
        array_walk($array,function($val,$key) use (&$params){
            if(is_array($val)){
                //keytype
                if(isset($val['keytype'])){
                    $params .= $val['keytype'] . $key . ':' . $val['value'] . ' ';
                }elseif(isset($val['valuetype'])){
                    $params .= $key . $val['valuetype'].$val['value'] . ' ';
                }else{
                    foreach($val as $value){
                        $params .= $key . ':' . $value . ' ';
                    }
                }
            }else{
                $params .= $key . ':' . $val . ' ';
            }
        });
        return $params;
    }

    /**
     * 每5分钟运行一次
     * 当发送模板1，客户在5小时内没有回复时，增加tag“转客服”，邮件状态变更为Open
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function shellChange()
    {
        //判断主的只有自动回复的，证明是第一次自动回复但是没有得到回应的，则24小时后自动open并tag转客服
        //每5分钟运行一次
        $tickets = ZendeskReply::where(['tags' => '自动回复','id' => ['>',2726]])->whereTime('update_time','<=',date('Y-m-d H:i:s',time()-3600*24))->select();
        foreach($tickets as $ticket){
            $params = [
                'tags' => ['转客服', '自动回复'],
                'status' => 'open',
                'assignee_id' => 382940274852
            ];
            $this->autoUpdate($ticket->email_id, $params);
            ZendeskReply::where('id',$ticket->id)->update([
                'status' => 'open',
                'tags' => '转客服,自动回复'
            ]);
        }
    }
}