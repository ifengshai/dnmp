<?php

namespace fast;

use fast\Http;
use function GuzzleHttp\json_encode;

/**
 * alibaba类
 */
class Kuaidi100
{
    protected static $key = 'FTwkeoNA1046';
  
    /**
     * 设置快递100订阅推送信息
     * @param string $company 快递公司编码
     * @param string $number 快递编号
     * @return array
     */
    public static function setPoll($company, $number, $purchase_id)
    {
        //参数设置
        $key = self::$key;    //客户授权key
        $param = array(
            'company' => $company,  //快递公司编码
            'number' => $number,    //快递单号
            'from' => '',           //出发地城市
            'to' => '',             //目的地城市
            'key' => $key,          //客户授权key
            'parameters' => array(
                'callbackurl' => config('kuaidi100.callback') . '?purchase_id=' . $purchase_id,        //回调地址
                'salt' => '',                //加密串
                'resultv2' => '1',            //行政区域解析
                'autoCom' => '0',            //单号智能识别
                'interCom' => '0',            //开启国际版
                'departureCountry' => '',    //出发国
                'departureCom' => '',        //出发国快递公司编码
                'destinationCountry' => '',    //目的国
                'destinationCom' => '',        //目的国快递公司编码
                'phone' => ''                //手机号
            )
        );

        //请求参数
        $post_data = array();
        $post_data["schema"] = 'json';
        $post_data["param"] = json_encode($param);

        $url = 'http://poll.kuaidi100.com/poll';    //订阅请求地址

        $params = "";
        foreach ($post_data as $k => $v) {
            $params .= "$k=" . urlencode($v) . "&";        //默认UTF-8编码格式
        }
        $post_data = substr($params, 0, -1);
       
        //发送post请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data = str_replace("\"", '"', $result);
        return json_decode($data);
    }
}
