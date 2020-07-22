<?php

namespace fast;

class Soap
{
    /**
     * 创建产品
     *
     * @Description
     * @author wpl
     * @since 2020/07/22 11:34:19 
     * @param string $magentoUrl
     * @param [type] $magento_account
     * @param [type] $magento_key
     * @param [type] $params
     * @return void
     */
    public static function createProduct($config = [], $params)
    {
        if (!$config) {
            return false;
        }
        $client = new \SoapClient($config['magento_url'] . '/api/soap/?wsdl');
        $session = $client->login($config['magento_account'], $config['magento_key']);
        //获取magento产品属性设置
        $attributeSets = $client->call($session, 'product_attribute_set.list');
        foreach ($attributeSets as $v) {
            //选择默认值
            if ($v['name'] == $config['item_attr_name']) {
                $attributeSet['set_id'] = $v['set_id'];
            }
        }
        try {
            // product creation
            $client->call($session, 'catalog_product.create', array($config['item_type'], $attributeSet['set_id'], $params['sku'], $params));
        } catch (\SoapFault $e) {
            return false;
        }
        return true;
    }
}
