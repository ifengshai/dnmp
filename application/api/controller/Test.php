<?php

namespace app\api\controller;

use think\Controller;
use Zendesk\API\HttpClient as ZendeskAPI;

/**
 * 首页接口
 */
class Test extends Controller
{
    public function index()
    {
        $subdomain = "zeelooloptical";
        $username = "complaint@zeelool.com";
        $token = "wAhNtX3oeeYOJ3RI1i2oUuq0f77B2MiV5upmh11B";
        
        $client = new ZendeskAPI($subdomain);
        $client->setAuth('basic', ['username' => $username, 'token' => $token]);
        try {
        	// Query Zendesk API to retrieve the ticket details
        
        	$id = 73887;
            $id = 76909;
        	$tickets = $client->tickets($id)->comments()->findAll();
        	
        		// Show the results
        	$comments = $tickets->comments;
        		foreach( $comments as $comment){
        		echo $comment->body.'</br>';
        	}
        } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
        	echo $e->getMessage().'</br>';
        }
    }
}
