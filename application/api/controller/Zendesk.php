<?php
/**
 * Created by PhpStorm.
 * User: josephine
 * Date: 2020/2/20
 * Time: 09:32
 */

namespace app\api\controller;

use think\Controller;
use Zendesk\API\HttpClient as ZendeskAPI;
use Stringy\Stringy;
use function Stringy\create as s;

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
    //初始化方法
    public function _initialize()
    {
        parent::_initialize();
        $this->client = new ZendeskAPI($this->subdomain);
        $this->client->setAuth('basic', ['username' => $this->username, 'token' => $this->token]);
    }

    /**
     * 测试的方法
     */
    public function test()
    {
        dump(s('str contains foo')->contains('contains foo'));die;
        try {
            // Query Zendesk API to retrieve the ticket details

            $id = 73887;
            $id = 76909;
            $tickets = $this->client->tickets($id)->comments()->findAll();
            // Show the results
            $comments = $tickets->comments;
            foreach( $comments as $comment){
                echo $comment->body.'</br>';
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
     */
    public function findCommentsByTickets($tickets)
    {
        foreach($tickets as $ticket){
            $id = $ticket->id;
            //发送者的id
            $requester_id = $ticket->requester_id;
            $result = $this->client->tickets($id)->comments()->findAll();
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
                    switch ($answer_key){
                        case 1:
                            break;
                        case 2:
                            break;
                        case 3:
                            //open，转客服
                            $params = [
                                'tags' => ['转客服'],
                                'status' => 'open'
                            ];
                            $this->autoUpdate($id, $params);
                            break;
                        case 4:
                            //open，转客服
                            $params = [
                                'tags' => ['转客服'],
                                'status' => 'open'
                            ];
                            $this->autoUpdate($id, $params);
                            break;
                        default:  //其他不做处理
                            break;
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
                        $this->autoUpdate($id, $params);
                    }
                }
            }
        }
    }
    public function autoUpdate($ticket_id,$params)
    {
        $this->client->tickets()->update($ticket_id, $params);
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