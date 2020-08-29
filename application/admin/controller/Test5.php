<?php
namespace app\admin\controller;
use app\common\controller\Backend;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdCampaign;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Object\AdAccount;
use think\Db;

class Test5 extends Backend
{
    protected $app_id = "623060648636265";
    protected $app_secret = "ad00911ec3120286be008c02bdd66a92";
    protected $access_token = "EAAI2q5yir2kBAG9rzV6IEHQXWdinl28NHuXactNphDhupdrDPJY0YeDVF5usXyC0zKBVKZAX8v6nSZCiHPPyiZC69dn2jtYZA51Ox4ZA7WIKpc7oVqPkeUwmRYLBH57qZANcKA8UAK0lFnWEJEBBZB22ZAiCKHu1mfxj8n3sw6uynMNEgcZB3h6tR8u6BypNc9mIzSyPYiUU4PQZDZD";
    public function test()
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);
        $all_facebook_spend = 0;
        $accounts = array(
            'act_262835201038048','act_736073500078882',
            'act_269449023642271','act_521842581603049',
            'act_441648552997691','act_1744800885633107',
            'act_899481596907679','act_2090167671055487',
            'act_439802446536567','act_791641617892869',
            'act_2190112471224775','act_293134724755985',
            'act_2374101719500794','act_2181479815217727',
            'act_475650842941529','act_702824906877683',
            'act_421102155506178','act_696112760911417',
            'act_426104834940521','act_504634933448952',
            'act_2511464062308305','act_730981940722566',
            'act_2532553127015409','act_2450837438490062',
            'act_780912609044303','act_871218786629333',
            'act_901873296937411','act_284027059351709',
            'act_208494053835351','act_3453767384653933',
            'act_2685258475062863','act_661080561423864'
        );
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
            'time_range' => array('since'=>'2020-08-14','until'=>'2020-08-14'),
            );
            $cursor = $campaign->getInsights([],$params);
            foreach ($cursor->getObjects() as $key => $value) {
               if($value){
                 $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }
        dump($all_facebook_spend);
        exit; 

    }
}