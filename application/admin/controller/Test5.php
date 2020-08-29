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
    protected $access_token = "EAAI2q5yir2kBAKzt7dTzOrbGpNkT8wIxrQwzCSgtU3NtJp2MYZB6HTZAUtwq1s78VPPZCrlU7y04InPTBhZAgwUBgPf9J9LqzZAYGIlhG2pomAIeI59o6DSMFBp3ECvaWk4ic3LljoxaxJBwUdgQkggUBCxFG8wVW1E3oXAMtIOEYKAY25ybDeG7gHz5zFWgnFNfjatl2bptWj5Y3ZAJBP4qwx6coYXibTeOnAi0ua2Ttkl9QxiHJ2sop4ZAHgYIjMZD";
    public function test()
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);
        $all_facebook_spend = 0;
        $accounts = array(
            'act_262835201038048'
        );



        $campaign = new Campaign('act_262835201038048');
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
