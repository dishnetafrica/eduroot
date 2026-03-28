<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Omnipay\Omnipay;

require_once(APPPATH . 'third_party/omnipay/vendor/autoload.php');
class Paymongo {

    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
    }

    public function payment() {
        $gateway = Omnipay::create('Paymongo_Card');
        
        $gateway->setKeys('test', 'test');
        $token = $gateway->authorize([
            'number' => 'XXXX XXXX XXXX XXXX',
            'expiryMonth' => '1',
            'expiryYear' => '22',
            'cvv' => '123',
        ]);
        print_r($token);
    }

}
