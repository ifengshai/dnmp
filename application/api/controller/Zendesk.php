<?php
/**
 * Created by PhpStorm.
 * User: josephine
 * Date: 2020/2/20
 * Time: 09:32
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Exception;
use Zendesk\API\HttpClient as ZendeskAPI;
use function Stringy\create as s;
use fast\Trackingmore;

class Zendesk extends Controller
{
    //基本参数配置
    protected $subdomain = "zeelooloptical";
    protected $username = "complaint@zeelool.com";
    protected $token = "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B";
    //客户按要求回复的内容
    protected $auto_answer = [
        'check order information',
        'track order',
        'change order information',
        'others'
    ];
    //匹配自动回复的单词
    protected $preg_word = ['when','deliver','delivery','receive','track','ship','shipping','tracking','status','order','shipment'];
    public $client = null;

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
     * 测试的方法
     */
    public function test()
    {
        $title = 'USPS';
        $title = str_replace(' ', '-', $title);
        $track_number = '92748902348242002000808424';
        $track = new Trackingmore();
        $result = $track->getRealtimeTrackingResults($title, $track_number);
        ///$track2 = $track->getSingleTrackingResult($title, $track_number,'en');
        dump($result);
        //dump($track2);
        die;
        //dump(s('str contains foo')->contains('contains foo'));die;
        try {
            // Query Zendesk API to retrieve the ticket details

            $id = 73887;
            $id = 78710;
           // dump($this->client->tickets()->findMany([$id]));die;
            $tickets = $this->client->tickets($id)->comments()->findAll();
            // Show the results
            $comments = $tickets->comments;
            foreach( $comments as $comment){
                echo $comment->body.'</br>';
                echo '----------------------------'.'</br>';
            }
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
            echo $e->getMessage().'</br>';
        }
    }
    /**
     * 查询tickets
     */
    public function searchTickets()
    {
        $search = [
            'type' => 'ticket',
            'via' => 'mail',
            'updated_at' => [
                'valuetype' => '>=',
                'value'   => '90minutes',
            ],//>=意思是3分钟之内，<=是三分钟之外
            'status' => ['new','open','solved'],
            'tag' => [
                'keytype' => '-',
                'value' => '转客服'
            ], // -排除此tag
            'assignee' => [
                382940274852,
                'none'
            ],
            'requester' => 393708243591,
            'order_by' => 'updated_at',
            'sort' => 'desc'
        ];
        $this->getTickets($search);
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
        $this->findCommentsByTickets($tickets);
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
        foreach($tickets as $ticket){
            $id = $ticket->id;
            //发送者的id
            $requester_id = $ticket->requester_id;
            //所有的tag
            $tags = $ticket->tags;
            //email
            $requester_email = $ticket->via->source->from->address;
            try{
                $result = $this->client->tickets($id)->comments()->findAll();
            }catch (\Exception $e){
                exception('获取邮件评论失败',10002);
            }

            $count = $result->count;
            $comments = $result->comments;
            //判断，如果最后一条为发送着的评论，则需要回复，如果为客服或自动回复的，则不需要回复
            $last_comment = $comments[$count-1];
            if($requester_id == 393708243591) {
                if ($last_comment->author_id == $requester_id) {
                    //邮件的内容
                    $body = strtolower($last_comment->body);
                    //开始匹配邮件内容
                    //查看是否已有自动回复的tag
                    if (in_array('自动回复', $tags)) { //次类是顾客根据要求回复的内容
                        $answer_key = 0;
                        foreach ($this->auto_answer as $key => $answer) {
                            //回复内容包含自动回复的内容，且相匹配
                            if (s($body)->contains($answer)) {
                                $answer_key = $key + 1;
                                break;//匹配到则跳出循环
                            }
                        }
                        if ($answer_key == 1 || $answer_key == 2) {
                            $order = $this->findOrderByEmail($requester_email);
                            $status = $order['status'];
                            $increment_id = $order['increment_id'];
                            $order_id = $order['order_id'];
                            if ($status == 'pendind' or $status == 'creditcard_failed') {
                                //状态改solved，tag支付失败
                                $params = [
                                    'comment' => [
                                        'body' => config('zendesk.t3')
                                    ],
                                    'tags' => ['支付失败'],
                                    'status' => 'solved'
                                ];
                            } elseif ($status == 'canceled') {
                                //状态solved，tag取消订单
                                $params = [
                                    'comment' => [
                                        'body' => sprintf(config('zendesk.t7'), $increment_id)
                                    ],
                                    'tags' => ['取消订单'],
                                    'status' => 'solved'
                                ];
                            } elseif ($status == 'processing') {
                                //回复1-7天发货，状态solved，tag查询加工状态
                                $params = [
                                    'comment' => [
                                        'body' => config('zendesk.t2')
                                    ],
                                    'tags' => ['查询加工状态'],
                                    'status' => 'solved'
                                ];
                            } elseif ($status == 'complete') {
                                $res = $this->getTrackMsg($order_id);
                                //模拟状态测试
//                                $res = [
//                                    'status' => 'transit',
//                                    'lastUpdateTime' => '2020-02-20 15:35:30',
//                                    'lastEvent' => 'adde ddae ',
//                                    'carrier_code' => 'dhl',
//                                    'updated_at' => '2020-01-20 12:11:12',
//                                    'track_number' => '54455aad2122'
//                                ];
                                //判断是否签收
                                if ($res['status'] == 'delivered') { //已签收
                                    $params = [
                                        'comment' => [
                                            'body' => sprintf(config('zendesk.t4'), $res['updated_at'], $res['track_number'], $res['carrier_code'], $res['lastEvent'], $res['lastUpdateTime'])
                                        ],
                                        'tags' => ['已签收', '查询物流信息'],
                                        'status' => 'solved'
                                    ];
                                } elseif ($res['status'] == 'transit' || $res['status'] == 'pickup') { //判断物流时效
                                    $params = [
                                        'comment' => [
                                            'body' => ''
                                        ],
                                        'tags' => [],
                                        'status' => 'pending'
                                    ];

                                    $lastUpdateTime = strtotime($res['lastUpdateTime']);
                                    $now = time();
                                    if ($now - $lastUpdateTime > 7 * 24 * 3600) { //超7天未更新
                                        $params['comment']['body'] = sprintf(config('zendesk.t6'), $res['updated_at'], $res['track_number'], $res['carrier_code']);
                                        $params['status'] = ['超时', '查询物流信息'];
                                    } else {
                                        $params['comment']['body'] = sprintf(config('zendesk.t5'), $res['updated_at'], $res['track_number'], $res['carrier_code'], $res['lastEvent'], '([Track Order])(https://www.zeelool.com/ordertrack)', '([AfterShip])(https://tools.usps.com/go/TrackConfirmAction_input)');
                                        $params['status'] = ['查询物流信息'];
                                    }
                                } else { //转客服，状态open
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
                        } elseif ($answer_key == 3 || $answer_key == 4) {
                            //open，转客服
                            $params = [
                                'tags' => ['转客服'],
                                'status' => 'open'
                            ];
                        }
                    } else {
                        //When,deliver,delivery,receive,track,ship,shipping,tracking,status,order,shipment
                        //匹配到相应的关键字，自动回复消息，修改为pending，回复共客户选择的内容
                        if (s($body)->containsAny($this->preg_word)) {
                            //回复模板1：状态pending，增加tag自动回复
                            $params = [
                                'comment' => [
                                    'body' => config('zendesk.t1')
                                ],
                                'tags' => ['自动回复'],
                                'status' => 'pending'
                            ];
                        }
                    }
                    if (!empty($params)) {
                        //tag合并
                        $params['tags'] = array_unique(array_merge($tags, $params['tags']));
                        $params['comment']['author_id'] = 382940274852;
                        $params['assignee_id'] = 382940274852;
                        $this->autoUpdate($id, $params);
                    }
                }
            }
        }
    }

    /**
     * 回复消息，更新状态
     * @param $ticket_id
     * @param $params
     * @throws \Exception
     */
    public function autoUpdate($ticket_id,$params)
    {
        try{
            $this->client->tickets()->update($ticket_id, $params);
        }catch (\Exception $e){
            exception($e->getMessage(), 10001);
        }

    }

    /**
     * 通过email查找订单号
     * @param $email
     * @return mixed|string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findOrderByEmail($email)
    {
        $order = Db::connect('database.db_zeelool')
            ->table('sales_flat_order')
            ->field('entity_id,state,status,increment_id')
            ->where('customer_email',$email)
            ->order('entity_id desc')
            ->find();
        $status = isset($order['status']) ? $order['status'] : '';
        return ['status' => $status, 'increment_id' => $order['increment_id'],'order_id' => $order['entity_id']];
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

        $track_result = Db::connect('database.db_zeelool')
            ->table('sales_flat_shipment_track')
            ->field('track_number,title,updated_at')
            ->where('order_id',$order_id)
            ->find();
        //查询物流信息
        $title = strtolower(str_replace(' ', '-', $track_result['title']));
        $track = new Trackingmore();
        $result = $track->getRealtimeTrackingResults($title, $track_result['track_number']);
        $data = $result['data']['items'][0];
        $lastUpdateTime = $data['lastUpdateTime']; //物流最新跟新时间
        $lastEvent = $data['lastEvent'];
        $res = [
            'status' => $data['status'],
            'lastUpdateTime' => $lastUpdateTime,
            'lastEvent' => $lastEvent,
            'carrier_code' => $data['carrier_code'],
            'updated_at' => $track_result['updated_at'],
            'track_number' => $track_result['track_number']
        ];
        return $res;

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
}