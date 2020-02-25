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
        '测试回复1',
        '测试回复2',
        '测试回复3',
        '其他'
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
                'value'   => '30minutes',
            ],//>=意思是3分钟之内，<=是三分钟之外
            'status' => ['new','open'],
            'tag' => [
                'keytype' => '-',
                'value' => '转客服'
            ], // -排除此tag
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
        $tickets = $search->result;
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
            if($last_comment->author_id == $requester_id){
                //邮件的内容
                $body = $last_comment->body;
                //开始匹配邮件内容
                //When,deliver,delivery,receive,track,ship,shipping,tracking,status,order,shipment
                //首先查看是否是自动回复的内容，如果不是，则匹配上述字段自动回
                if(false){
                    $answer_key = 0;
                    foreach($this->auto_answer as $key => $answer){
                        //回复内容包含自动回复的内容，且相匹配
                        if(s($body)->contains($answer)){
                            $answer_key = $key + 1;
                            break;//匹配到则跳出循环
                        }
                    }
                    if($answer_key == 1 || $answer_key == 2){
                        $status = $this->findOrderByEmail($requester_email);
                        if($status == 'pendind' or $status == 'creditcard_failed'){
                            //状态改solved，tag支付失败
                            $params = [
                                'tags' => ['支付失败'],
                                'status' => 'solved'
                            ];
                        }elseif($status == 'canceled'){
                            //状态solved，tag取消订单
                            $params = [
                                'tags' => ['取消订单'],
                                'status' => 'solved'
                            ];
                        }elseif($status == 'processing'){
                            //回复1-7天发货，状态solved，tag查询加工状态
                            $params = [
                                'comment'  => [
                                    'body' => '1-7天发货',
                                    'author_id' => 383342590872
                                ],
                                'tags' => ['查询加工状态'],
                                'status' => 'solved'
                            ];
                        }elseif($status == 'complete'){
                            $res = $this->getTrackMsg($id);
                            //判断是否签收
                            if($res['status'] == 'delivered'){ //已签收
                                $params = [
                                    'comment'  => [
                                        'body' => '已签收',
                                        'author_id' => 383342590872
                                    ],
                                    'tags' => ['已签收','查询物流信息'],
                                    'status' => 'sloved'
                                ];
                            }elseif($res['status'] == 'transit' || $res['status'] == 'pickup'){ //判断物流时效
                                $params = [
                                    'comment'  => [
                                        'body' => '',
                                        'author_id' => 383342590872
                                    ],
                                    'tags' => [],
                                    'status' => 'pending'
                                ];
                                $lastUpdateTime = strtotime($res['lastUpdateTime']);
                                $now = time();
                                if($now - $lastUpdateTime > 7 * 24 * 3600 ){ //超7天未更新
                                    $params['comment']['body'] = '超7天';
                                    $params['status'] = ['超时','查询物流信息'];
                                }else{
                                    $params['comment']['body'] = '正在运送';
                                    $params['status'] = ['查询物流信息'];
                                }
                            }else{ //转客服，状态open
                                //状态open，tag转客服
                                $params = [
                                    'tags' => ['转客服','查询物流信息'],
                                    'status' => 'open'
                                ];
                            }
                        }else{
                            //状态open，tag转客服
                            $params = [
                                'tags' => ['转客服'],
                                'status' => 'open'
                            ];
                        }
                    }elseif($answer_key == 3 || $answer_key == 4){
                        //open，转客服
                        $params = [
                            'tags' => ['转客服'],
                            'status' => 'open'
                        ];
                    }
                }else{
                    //匹配到相应的关键字，自动回复消息，修改为pending，回复共客户选择的内容
                    if(s($body)->containsAny($this->preg_word)){
                        $params = [
                            'comment'  => [
                                'body' => '1、测试的相关回复
                                2、测试回复2
                                3、测试回复3
                                4、其他',
                                'author_id' => 383342590872
                            ],
                            'tags' => ['自动回复'],
                            'status' => 'pending'
                        ];
                    }
                }
                if(!empty($params)){
                    $this->autoUpdate($id, $params);
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
            exception('更新失败', 10001);
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
        $db = Db::connect('database.db_zeelool');
        $order = $db->table('sales_flat_order')->where('customer_email',$email)->field('entity_id,state,status,increment_id')->order('entity desc')->find();
        $status = isset($order['status']) ? $order['status'] : '';
        return $status;
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
            ->field('track_number,title')
            ->where('order_id',$order_id)
            ->find();
        //查询物流信息
        $title = strtolower(str_replace(' ', '-', $track_result['title']));
        $track = new Trackingmore();
        $result = $track->getRealtimeTrackingResults($title, $track_result['track_number']);
        $data = $result['data']['items'][0];
        $lastUpdateTime = $data['lastUpdateTime']; //物流最新跟新时间
        return ['status' => $data['status'], 'lastUpdateTime' => $lastUpdateTime];

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