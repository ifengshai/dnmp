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
    public static function createProduct($magentoUrl = '', $magento_account, $magento_key, $params)
    {
        $client = new \SoapClient($magentoUrl . '/api/soap/?wsdl');

        // If some stuff requires api authentification,
        // then get a session token
        $session = $client->login($magento_account, $magento_key);

        // get attribute set
        $attributeSets = $client->call($session, 'product_attribute_set.list');
        $attributeSet = current($attributeSets);

        $newProductData = array(
            'name'              => 'Test product',
            'websites'          => array(1),
            'short_description' => 'This is the short desc',
            'description'       => 'This is the long desc',
            'price'             => 150.00,
            'status'            => 1,
            'tax_class_id'      => 0,
            'visibility'        => 4
        );

        try {
            // product creation
            $client->call($session, 'product.create', array('simple', $attributeSet['set_id'], $params['sku'], $newProductData));
        } catch (\SoapFault $e) {
            $msg = "Error in inserting product with sku $ItemNmbr : " . $e->getMessage();
            echo $msg;
        }
    }
}
