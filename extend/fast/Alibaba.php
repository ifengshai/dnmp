<?php

namespace fast;

use function GuzzleHttp\json_encode;
use GuzzleHttp\Client;

/**
 * alibaba类
 */
class Alibaba
{
    protected static $appKey = '8240623';
    protected static $appSecret = 'kIeMZ3gwdeMm';
    protected static $access_token = '9b2c2ee1-ed98-403d-8a7b-ec0a950913dd';
    protected static $url = 'https://gw.open.1688.com/openapi/'; //1688开放平台使用gw.open.1688.com域名

    /**
     * 获取1688订单列表
     * @param string $apiInfo 获取签名的参数 格式为protocol/apiVersion/namespace/apiName/
     * @return array
     */
    public static function getOrderList($page = 1, $addparams = [])
    {
        $apiInfo = 'param2/1/com.alibaba.trade/alibaba.trade.getBuyerOrderList/';
        $url = self::$url;
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;

        /***************获取签名*********************/
        $apiInfo = $apiInfo . $appKey; //此处请用具体api进行替换
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr = array(
            'webSite' => '1688',
            'pageSize' => 50,
            'page' => $page,
            'access_token' => self::$access_token
        );
        $code_arr = array_merge($code_arr, $addparams);
        $aliParams = array();
        foreach ($code_arr as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        /********************END*************************/
        $url =  $url . $apiInfo;
        $params = [
            'webSite' => '1688',
            'pageSize' => 50,
            'page' => $page,
            'access_token' => self::$access_token,
            '_aop_signature' => $code_sign
        ];
        $params = array_merge($params, $addparams);

        $client = new Client(['verify' => false]);
        $response = $client->request('POST', $url, array('form_params' => $params));

        //请求URL
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody, true);
        if ($res === null) {
            exception('网络异常');
        }
        return $res;
    }


    /**
     * 获取1688订单详情
     * @param string $apiInfo 获取签名的参数 格式为protocol/apiVersion/namespace/apiName/
     * @return array
     */
    public static function getOrderDetail(string $orderId = '')
    {
        $url = self::$url;
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;
        $apiInfo = 'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/';

        /***************获取签名*********************/
        $apiInfo = $apiInfo . $appKey; //此处请用具体api进行替换
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr = array(
            'webSite' => '1688',
            'orderId' => $orderId,
            'access_token' => self::$access_token
        );
        $aliParams = array();
        foreach ($code_arr as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        /********************END*************************/
        $url =  $url . $apiInfo;
        $params = [
            'webSite' => '1688',
            'orderId' => $orderId,
            'access_token' => self::$access_token,
            '_aop_signature' => $code_sign
        ];

        $client = new Client(['verify' => false]);
        $response = $client->request('POST', $url, array('form_params' => $params));
        //请求URL
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody);
        if ($res === null) {
            exception('网络异常');
        }
        return $res;
    }


    /**
     * 获取1688商品详情
     * @param string $apiInfo 获取签名的参数 格式为protocol/apiVersion/namespace/apiName/
     * @return array
     */
    public static function getGoodsDetail($productId = '')
    {
        $url = self::$url;
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;
        $apiInfo = 'param2/1/com.alibaba.product/alibaba.cross.productInfo/';

        /***************获取签名*********************/
        $apiInfo = $apiInfo . $appKey; //此处请用具体api进行替换
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr = array(
            'webSite' => '1688',
            'productId' => $productId,
            'access_token' => self::$access_token
        );
        $aliParams = array();
        foreach ($code_arr as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        /********************END*************************/
        $url =  $url . $apiInfo;
        $params = [
            'webSite' => '1688',
            'productId' => $productId,
            'access_token' => self::$access_token,
            '_aop_signature' => $code_sign
        ];
        $client = new Client(['verify' => false]);
        $response = $client->request('POST', $url, array('form_params' => $params));
        //请求URL
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody);
        if ($res === null) {
            exception('网络异常');
        }
        return $res;
    }


    /**
     * 添加1688商品铺货
     * @param string $apiInfo 获取签名的参数 格式为protocol/apiVersion/namespace/apiName/
     * @return array
     */
    public static function getGoodsPush(array $productIdList = [])
    {
        //数组转成json再处理
        $productIdList = json_encode($productIdList);
        $url = self::$url;
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;
        $apiInfo = 'param2/1/com.alibaba.product.push/alibaba.cross.syncProductListPushed/';

        /***************获取签名*********************/
        $apiInfo = $apiInfo . $appKey; //此处请用具体api进行替换
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr = array(
            'webSite' => '1688',
            'productIdList' => $productIdList,
            'access_token' => self::$access_token
        );
        $aliParams = array();
        foreach ($code_arr as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        /********************END*************************/
        $url =  $url . $apiInfo;
        $params = [
            'webSite' => '1688',
            'productIdList' => $productIdList,
            'access_token' => self::$access_token,
            '_aop_signature' => $code_sign
        ];
        $client = new Client(['verify' => false]);
        $response = $client->request('POST', $url, array('form_params' => $params));
        //请求URL
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody, true);
        if ($res === null) {
            exception('网络异常');
        }
        return $res;
    }

    /**
     * 获取订单物流跟踪信息
     * @param string $apiInfo 获取签名的参数 格式为protocol/apiVersion/namespace/apiName/
     * @return array
     */
    public static function getLogisticsMsg(string $orderId = '')
    {
        $url = self::$url;
        $appKey = self::$appKey;
        $appSecret = self::$appSecret;
        $apiInfo = 'param2/1/com.alibaba.logistics/alibaba.trade.getLogisticsTraceInfo.buyerView/';

        /***************获取签名*********************/
        $apiInfo = $apiInfo . $appKey; //此处请用具体api进行替换
        //配置参数，请用apiInfo对应的api参数进行替换
        $code_arr = array(
            'webSite' => '1688',
            'orderId' => $orderId,
            'access_token' => self::$access_token
        );
        $aliParams = array();
        foreach ($code_arr as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $apiInfo . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
        /********************END*************************/
        $url =  $url . $apiInfo;
        $params = [
            'webSite' => '1688',
            'orderId' => $orderId,
            'access_token' => self::$access_token,
            '_aop_signature' => $code_sign
        ];
        $client = new Client(['verify' => false]);
        $response = $client->request('POST', $url, array('form_params' => $params));
        //请求URL
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody, true);
        if ($res === null) {
            exception('网络异常');
        }
        return $res;
    }
}
