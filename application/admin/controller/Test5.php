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
    protected $app_id = "438689069966204";
    protected $app_secret = "1480382aa32283c6c13692908f7738a7";
    protected $access_token = "EAAGOZCEIuo3wBAMkIOgCGaUjUmgvY4CqtvXWQ2Jf8o2GkuyOls67R1kk04CDWD7BKSqwzQLTMBeaaeTJaRNyqHI5tihJVFoc6qsNvgJZCpf4mgxCHjZC99iZCu63fmPctNRpAyWyAJcdBq4x4eva0IU6Q7N8lk6vgq1yOLOF4hEqNWt8E5ie";
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
