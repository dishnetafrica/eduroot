<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Smsgatewayhub_lib {

    private $_CI;
    var $AUTH_KEY = "071f7b7e-960e-4ecf-812a-eb61208ea93e"; 
    var $senderId = "LALSAA"; 
    var $routeId = ""; 
    var $smsContentType = ""; 
    var $route = "clickhere"; 
    var $EntityId = "1302157243747322354";
    var $dlttemplateid = ""; 
    var $channel = "2"; 
    var $DCS = "0";
    var $flashsms = "0"; 

    public function __construct($params) { 
        $this->_CI = & get_instance();
        $this->template_id = $params['templateid'];
        $this->session_name = $this->_CI->setting_model->getCurrentSessionName();
        //============added==============
        $this->AUTH_KEY=$params['authkey'];
        $this->senderId=$params['senderid'];
        $this->EntityId=$params['api_id'];
        //============added==============
    } 

    public function sendSMS($to, $message) {
	   
	    $template_id = $this->template_id;
        $content =  'APIKey=' . rawurlencode($this->AUTH_KEY) .
                    '&senderid=' . rawurlencode($this->senderId) .
                    '&channel=' . rawurlencode($this->channel) .
                    '&DCS=' . rawurlencode($this->DCS) .
                    '&flashsms=' . rawurlencode($this->flashsms) .               
                    '&number=' . rawurlencode($to) .
                    '&text=' . rawurlencode($message) .
                    '&route=' . rawurlencode($this->route) .
                    '&EntityId=' . rawurlencode($this->EntityId) .
                    '&dlttemplateid=' . rawurlencode($template_id);	
               
        $ch = curl_init('https://www.smsgatewayhub.com/api/mt/SendSMS?' . $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}


?>