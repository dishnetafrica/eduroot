<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Kowri_payment
{

    private $_CI;
    public $api_config;
    private $api_url = 'https://api.kowri.app/v1/'; // Kowri API base URL

    function __construct()
    {
        $this->_CI = &get_instance();
        $this->api_config = $this->_CI->paymentsetting_model->getActiveMethod();
    }

    /**
     * Create a payment request with Kowri
     */
    public function createPayment($data)
    {
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;

        $payment_data = array(
            'app_id' => $app_id,
            'app_reference' => $app_reference,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'return_url' => $data['return_url'],
            'cancel_url' => $data['cancel_url'],
            'webhook_url' => $data['webhook_url']
        );

        $response = $this->makeApiCall('payments/create', $payment_data, $secret);
        
        return $response;
    }

    /**
     * Verify payment status
     */

     public function getallpaymentoptions(){
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;
        
        // Check if credentials are empty
        if (empty($app_id) || empty($app_reference) || empty($secret)) {
            return array('status' => false, 'error' => 'API credentials not configured');
        }
      
        $post_data = array(
            'requestId' => uniqid(),
            'appReference' => $app_reference,
            'secret' => $secret
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://posapi.kowri.app/webpos/listPayOptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'appId: '.$app_id.'',
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);

        if ($error) {
            return array('status' => false, 'error' => 'CURL Error: ' . $error);
        }

        $decoded_response = json_decode($response, true);

        if ($http_code == 200) {
            return array('status' => true, 'data' => $decoded_response);
        } else {
            return array('status' => false, 'error' => isset($decoded_response['message']) ? $decoded_response['message'] : 'API Error', 'http_code' => $http_code);
        }
     }
    public function verifyPayment($transaction_id)
    {
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;

        $verify_data = array(
            'app_id' => $app_id,
            'app_reference' => $app_reference,
            'transaction_id' => $transaction_id
        );

        $response = $this->makeApiCall('payments/verify', $verify_data, $secret);
        
        return $response;
    }

    /**
     * Process payment callback
     */
    public function processCallback($callback_data)
    {
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;

        // Verify the callback signature
        $signature = $this->generateSignature($callback_data, $secret);
        
        if ($signature === $callback_data['signature']) {
            return array(
                'status' => true,
                'transaction_id' => $callback_data['transaction_id'],
                'amount' => $callback_data['amount'],
                'currency' => $callback_data['currency'],
                'status_code' => $callback_data['status']
            );
        }

        return array('status' => false, 'error' => 'Invalid signature');
    }

    /**
     * Make API call to Kowri
     */
    private function makeApiCall($endpoint, $data, $secret)
    {
        $url = $this->api_url . $endpoint;
        
        // Add signature to the data
        $data['signature'] = $this->generateSignature($data, $secret);
        $data['timestamp'] = time();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return array('status' => false, 'error' => 'CURL Error: ' . $error);
        }

        $decoded_response = json_decode($response, true);

        if ($http_code == 200) {
            return array('status' => true, 'data' => $decoded_response);
        } else {
            return array('status' => false, 'error' => isset($decoded_response['message']) ? $decoded_response['message'] : 'API Error');
        }
    }

    /**
     * Generate signature for API calls
     */
    private function generateSignature($data, $secret)
    {
        // Remove signature and timestamp from data for signing
        $sign_data = $data;
        unset($sign_data['signature']);
        unset($sign_data['timestamp']);
        
        // Sort the data by keys
        ksort($sign_data);
        
        // Create query string
        $query_string = http_build_query($sign_data);
        
        // Generate HMAC signature
        return hash_hmac('sha256', $query_string, $secret);
    }

    /**
     * Insert transaction record
     */
    public function InsertTransaction($jsonObj)
    {
        $this->_CI->load->model('feegrouppayment_model');
        
        $params = $this->_CI->session->userdata('params');
        $student_fees_master_array = $params['student_fees_master_array'];
        
        $payment_data = array(
            'student_fees_master_id' => $jsonObj->student_fees_master_id,
            'fee_groups_feetype_id' => $jsonObj->fee_groups_feetype_id,
            'student_id' => $jsonObj->student_id,
            'amount' => $jsonObj->amount,
            'amount_discount' => $jsonObj->amount_discount,
            'amount_fine' => $jsonObj->amount_fine,
            'amount_paid' => $jsonObj->amount_paid,
            'payment_mode' => 'kowri',
            'description' => $jsonObj->description,
            'date' => date('Y-m-d'),
            'transaction_id' => $jsonObj->transaction_id,
            'status' => 'success'
        );

        $insert_id = $this->_CI->feegrouppayment_model->add($payment_data);
        
        if ($insert_id) {
            return array('status' => true, 'insert_id' => $insert_id);
        } else {
            return array('status' => false, 'error' => 'Failed to insert transaction');
        }
    }

    /**
     * Process PayNow payment (Card Payment) - Uses payNow API directly
     */
    public function processPayNowPayment($data)
    {
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;

        // Generate unique transaction ID
        $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
        $request_id = uniqid();

        $post_data = array(
            'requestId' => $request_id,
            'appReference' => $app_reference,
            'secret' => $secret,
            'amount' => (float)$data['amount'],
            'currency' => $data['currency'],
            'customerName' => $data['customer_name'],
            'customerSegment' => 'Student',
            'reference' => $data['description'],
            'transactionId' => $transaction_id,
            'provider' => 'CARD',
            'walletRef' => '',
            'customerMobile' => $data['customer_phone']
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://posapi.kowri.app/webpos/payNow',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'appId: ' . $app_id,
                'Content-Type: application/json',
                'Accept: application/json'
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);

        if ($error) {
            return array('status' => false, 'error' => 'CURL Error: ' . $error);
        }

        $decoded_response = json_decode($response, true);

        if ($http_code == 200 && isset($decoded_response['success']) && $decoded_response['success']) {
            // Check if response contains redirect URL
            if (isset($decoded_response['result']['redirectUrl'])) {
                return array(
                    'status' => true, 
                    'data' => $decoded_response,
                    'payment_url' => $decoded_response['result']['redirectUrl'],
                    'transaction_id' => $transaction_id,
                    'reference' => $decoded_response['result']['reference'],
                    'token' => $decoded_response['result']['token'],
                    'request_id' => $request_id
                );
            } else {
                return array('status' => false, 'error' => 'No redirect URL received from PayNow Card API');
            }
        } else {
            $error_message = 'PayNow Card API Error';
            
            if (isset($decoded_response['statusMessage'])) {
                $error_message = $decoded_response['statusMessage'];
            } elseif (isset($decoded_response['message'])) {
                $error_message = $decoded_response['message'];
            } elseif (isset($decoded_response['error'])) {
                $error_message = $decoded_response['error'];
            }
            
            return array('status' => false, 'error' => $error_message, 'http_code' => $http_code, 'response' => $decoded_response);
        }
    }

    /**
     * Process PayNow MOMO payment (MTN Mobile Money via PayNow) - Uses createInvoice API
     */
    public function processPayNowMOMO($data)
    {
        // Create invoice and get checkout URL directly
        $invoice_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => $data['description']
        );

        $invoice_response = $this->createInvoice($invoice_data);
        
        if (!$invoice_response['status']) {
            return array('status' => false, 'error' => 'Failed to create invoice: ' . $invoice_response['error']);
        }

        // Generate unique transaction ID for tracking
        $transaction_id = 'MOMO_' . time() . '_' . rand(1000, 9999);

        // Return success with checkout URL for direct redirect
        return array(
            'status' => true,
            'payment_url' => $invoice_response['checkout_url'],
            'transaction_id' => $transaction_id,
            'invoice_num' => $invoice_response['invoice_num'],
            'request_id' => $invoice_response['request_id'],
            'reference' => $data['description']
        );
    }

    /**
     * Process payment for online admission
     */
    public function processOnlineAdmissionPayment($data)
    {
        $payment_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'return_url' => $data['return_url'],
            'cancel_url' => $data['cancel_url'],
            'webhook_url' => $data['webhook_url']
        );

        $response = $this->createPayment($payment_data);
        
        if ($response['status']) {
            return array(
                'status' => true,
                'payment_url' => $response['data']['payment_url'],
                'transaction_id' => $response['data']['transaction_id']
            );
        } else {
            return array('status' => false, 'error' => $response['error']);
        }
    }

    /**
     * Create Invoice using Kowri API with retry mechanism
     */
    public function createInvoice($data, $retry_count = 0)
    {
        $app_id = $this->api_config->api_publishable_key;
        $app_reference = $this->api_config->api_username;
        $secret = $this->api_config->api_secret_key;

        // Generate unique merchant order ID
        $merchant_order_id = 'ORDER_' . time() . '_' . rand(1000, 9999);

        $post_data = array(
            'requestId' => uniqid(),
            'appReference' => $app_reference,
            'secret' => $secret,
            'merchantOrderId' => $merchant_order_id,
            'currency' => $data['currency'],
            'amount' => (string)$data['amount'],
            'reference' => $data['description']
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://posapi.kowri.app/webpos/createInvoice', // Updated to correct endpoint
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Set timeout to 30 seconds instead of 0
            CURLOPT_CONNECTTIMEOUT => 10, // Connection timeout
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'appId: ' . $app_id
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'School Management System/1.0',
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);

        if ($error) {
            return array('status' => false, 'error' => 'CURL Error: ' . $error, 'http_code' => $http_code);
        }

        $decoded_response = json_decode($response, true);

        // Handle different HTTP status codes
        if ($http_code == 503) {
            // Retry logic for 503 errors
            if ($retry_count < 3) {
                $retry_delay = pow(2, $retry_count) * 5; // Exponential backoff: 5s, 10s, 20s
                sleep($retry_delay);
                
                log_message('info', "Kowri API 503 error, retrying in {$retry_delay} seconds (attempt " . ($retry_count + 1) . "/3)");
                return $this->createInvoice($data, $retry_count + 1);
            }
            
            return array(
                'status' => false, 
                'error' => 'Kowri API Service Unavailable (503). The payment service is temporarily down. Please try again later or contact support.',
                'http_code' => $http_code,
                'response' => $decoded_response,
                'retry_after' => 300 // Suggest retry after 5 minutes
            );
        } elseif ($http_code == 500) {
            return array(
                'status' => false, 
                'error' => 'Kowri API Internal Server Error (500). Please contact Kowri support.',
                'http_code' => $http_code,
                'response' => $decoded_response
            );
        } elseif ($http_code == 401) {
            return array(
                'status' => false, 
                'error' => 'Kowri API Authentication Failed (401). Please check your API credentials.',
                'http_code' => $http_code,
                'response' => $decoded_response
            );
        } elseif ($http_code == 400) {
            return array(
                'status' => false, 
                'error' => 'Kowri API Bad Request (400). Please check your request parameters.',
                'http_code' => $http_code,
                'response' => $decoded_response
            );
        } elseif ($http_code == 200 && isset($decoded_response['success']) && $decoded_response['success']) {
            return array(
                'status' => true, 
                'data' => $decoded_response,
                'request_id' => $decoded_response['requestId'],
                'invoice_num' => isset($decoded_response['result']['invoiceNum']) ? $decoded_response['result']['invoiceNum'] : null,
                'checkout_url' => isset($decoded_response['result']['checkoutUrl']) ? $decoded_response['result']['checkoutUrl'] : null
            );
        } else {
            $error_message = 'Create Invoice API Error (HTTP ' . $http_code . ')';
            
            if (isset($decoded_response['statusMessage'])) {
                $error_message = $decoded_response['statusMessage'] . ' (HTTP ' . $http_code . ')';
            } elseif (isset($decoded_response['message'])) {
                $error_message = $decoded_response['message'] . ' (HTTP ' . $http_code . ')';
            } elseif (isset($decoded_response['error'])) {
                $error_message = $decoded_response['error'] . ' (HTTP ' . $http_code . ')';
            }
            
            return array('status' => false, 'error' => $error_message, 'http_code' => $http_code, 'response' => $decoded_response);
        }
    }

    /**
     * Get suggested payment methods when CARD is not supported
     */
    public function getSuggestedPaymentMethods($amount, $currency)
    {
        $suggestions = array();
        
        // Get available payment options
        $payment_options = $this->getallpaymentoptions();
        
        if ($payment_options['status'] && isset($payment_options['data']['result'])) {
            foreach ($payment_options['data']['result'] as $option) {
                $min_amount = isset($option['minAmount']) ? (float)$option['minAmount'] : 0;
                $max_amount = isset($option['maxAmount']) ? (float)$option['maxAmount'] : 999999;
                
                // Check if amount is within range
                if ($amount >= $min_amount && $amount <= $max_amount) {
                    $suggestions[] = array(
                        'provider' => $option['provider'],
                        'name' => $option['name'],
                        'description' => $option['description'],
                        'logo' => isset($option['logo']) ? $option['logo'] : ''
                    );
                }
            }
        }
        
        // Add MOMO as fallback suggestion
        $suggestions[] = array(
            'provider' => 'MOMO',
            'name' => 'MTN Mobile Money',
            'description' => 'Pay with MTN Mobile Money',
            'logo' => ''
        );
        
        return $suggestions;
    }
}
