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
       

        $campaign = new Campaign('act_439802446536567');
        $params = array(
            'time_range' => array('since' => '2020-08-14', 'until' => '2020-08-14'),
        );
        $cursor = $campaign->getInsights([], $params);
        die;
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
                'time_range' => array('since' => '2020-08-14', 'until' => '2020-08-14'),
            );
            $cursor = $campaign->getInsights([], $params);
            foreach ($cursor->getObjects() as $key => $value) {
                if ($value) {
                    $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }
        dump($all_facebook_spend);
        exit;
    }

}
