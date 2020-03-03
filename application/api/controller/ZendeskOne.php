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
use think\Controller;
use think\Db;
use think\Exception;
use Zendesk\API\HttpClient as ZendeskAPI;
use function Stringy\create as s;

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
        'check order information',
        'track order',
        'change order information',
        'others'
    ];
    //匹配自动回复的单词
    protected $preg_word = ['deliver','delivery','receive','track','ship','shipping','tracking','status','shipment','where','where is','find','update','eta','expected'];
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
     * 查询tickets
     */
    public function searchTickets()
    {
        $search = [
            'type' => 'ticket',
            'via' => ['mail','web'],
            'status' => ['new'],
            'tags' => [
                'keytype' => '-',
                'value' => '转客服'
            ], // -排除此tag
            'assignee' => [
                382940274852,
                'none'
            ],
            //'requester' => $this->testId,
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
        //echo $params;die;
        $search = $this->client->search()->find($params);
        $tickets = $search->results;
        dump($search->count);die;
        $page = ceil($search->count / 100 );
        //先获取第一页的
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
            //if(in_array($requester_id,$this->testId)) {
                $reply_detail_data = [];
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
                            //dump($last_comment);die;
                            $order = $this->findOrderByEmail($requester_email);
                            $status = $order['status'];
                            if($status == 'processing') {
                                //判断商品下单时间，1月31日前，8,9.2月1日后，转客服
                                if($order['created_at'] >= '2020-02-01 00:00:00'){
                                    $params = [
                                        'tags' => ['转客服'],
                                        'status' => 'open'
                                    ];
                                }else{
                                    if(!$order['ship']){
                                        $params = [
                                            'comment' => [
                                                'body' => config('zendesk.t8')
                                            ],
                                            'tags' => ['com20'],
                                            'status' => 'pending'
                                        ];
                                    }
                                }

                            } else {
                                //状态open，tag转客服
                                $params = [
                                    'tags' => ['转客服'],
                                    'status' => 'open'
                                ];
                            }
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
                                    'assignee_id' => 382940274852
                                ]);
                                //回复评论
                                $reply_detail_data = [
                                    'reply_id' => $zendesk_reply->id,
                                    'body' => $params['comment']['body'] ? $params['comment']['body'] : '',
                                    'html_body' => $params['comment']['body'] ? $params['comment']['body'] : '',
                                    'tags' => join(',',array_unique(array_merge($tags, $params['tags']))),
                                    'status' => $params['status'],
                                    'assignee_id' => 382940274852,
                                ];
                            }
                        }

                    } else {
                        //匹配到相应的关键字，自动回复消息，修改为pending，回复共客户选择的内容
                        if (s($body)->containsAny($this->preg_word) === true) {
                            //回复模板1：状态pending，增加tag自动回复
                            $params = [
                                'comment' => [
                                    'body' => config('zendesk.t1')
                                ],
                                'tags' => ['自动回复'],
                                'status' => 'pending'
                            ];
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
                                    'assignee_id' => $ticket->assignee_id ? $ticket->assignee_id : 0

                                ];
                                //添加主评论
                                $zendesk_reply = ZendeskReply::create($reply_data);
                                //回复评论
                                $reply_detail_data = [
                                    'reply_id' => $zendesk_reply->id,
                                    'body' => $params['comment']['body'],
                                    'html_body' => $params['comment']['body'],
                                    'tags' => join(',',array_unique(array_merge($tags, $params['tags']))),
                                    'status' => $params['status'],
                                    'assignee_id' => 382940274852,
                                ];
                            }
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
                                    ZendeskReply::where('id',$zendesk_reply->id)->setField('status',$reply_detail_data['status']);
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
    public function autoUpdate($ticket_id,$params)
    {
        try{
            $this->client->tickets()->update($ticket_id, $params);
            sleep(1);
        }catch (\Exception $e){
            return false;
            //exception($e->getMessage(), 10001);
        }
        return true;

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
            ->field('entity_id,state,status,increment_id,created_at')
            ->where('customer_email',$email)
            ->order('entity_id desc')
            ->find();
        if(!empty($order)){
            $res = [
                'status' => $order['status'],
                'increment_id' => $order['increment_id'],
                'order_id' => $order['entity_id'],
                'created_at' => $order['created_at'],
                'ship' => 0
            ];

        }else{
            $res = [
                'status' => '',
                'increment_id' => '',
                'order_id' => '',
                'created_at' => ''
            ];
        }
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