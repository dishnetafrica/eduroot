# Adding a New Payment Gateway

This guide walks through adding a new payment gateway to EduRoot. You'll need to create files in 4 places.

---

## Files to Create / Edit

| Action | File |
|---|---|
| Create | `application/controllers/user/gateway/NewGateway.php` |
| Create | `application/controllers/onlineadmission/NewGateway.php` |
| Create | `application/views/user/gateway/newgateway/index.php` |
| Create | `application/views/onlineadmission/newgateway/index.php` |
| Edit | `application/controllers/admin/Paymentsettings.php` |
| Edit | `application/views/admin/paymentsettings/index.php` |
| Edit | `application/controllers/Webhooks.php` (if gateway uses webhooks) |
| Edit | DB: `payment_settings` table — add gateway columns |

---

## Step 1 — Student Fee Gateway Controller

Create `application/controllers/user/gateway/NewGateway.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NewGateway extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Studentfee_model');
        $this->load->model('Paymentsetting_model');
        // Auth check
        if (!$this->session->userdata('user_id')) {
            redirect('site/login');
        }
    }

    /**
     * Initiates payment — redirects student to the gateway
     */
    public function index() {
        $student_fees_master_id = $this->input->post('student_fees_master_id');
        $amount = $this->input->post('amount');

        // 1. Load gateway settings
        $settings = $this->Paymentsetting_model->getByGateway('newgateway');

        // 2. Create a processing record
        $this->db->insert('student_fees_processing', [
            'student_fees_master_id' => $student_fees_master_id,
            'gateway'  => 'newgateway',
            'amount'   => $amount,
            'status'   => 'pending',
            'txn_id'   => '',
        ]);
        $processing_id = $this->db->insert_id();

        // 3. Build gateway redirect URL using their SDK or API
        $redirect_url = $this->_build_gateway_url($settings, $amount, $processing_id);

        // 4. Redirect student to gateway
        redirect($redirect_url);
    }

    /**
     * Gateway redirects back here on success/failure
     */
    public function callback() {
        $txn_id = $this->input->get('transaction_id'); // gateway-specific
        $status = $this->input->get('status');

        if ($status === 'success') {
            // Verify with gateway API before marking paid
            if ($this->_verify_payment($txn_id)) {
                $this->_mark_paid($txn_id);
                $this->session->set_flashdata('success', $this->lang->line('payment_success'));
                redirect('user/studentfee');
            }
        }

        $this->session->set_flashdata('error', $this->lang->line('payment_failed'));
        redirect('user/studentfee');
    }

    private function _mark_paid($txn_id) {
        // 1. Update processing record
        $this->db->where('txn_id', $txn_id);
        $processing = $this->db->get('student_fees_processing')->row();

        $this->db->where('id', $processing->id);
        $this->db->update('student_fees_processing', ['status' => 'success']);

        // 2. Create deposit record
        $this->db->insert('student_fees_deposite', [
            'student_fees_master_id' => $processing->student_fees_master_id,
            'receipt_no'             => $this->_next_receipt_no(),
            'amount'                 => $processing->amount,
            'payment_mode'           => 'online',
            'date'                   => date('Y-m-d'),
            'collected_by'           => $this->session->userdata('user_id'),
        ]);

        // 3. Send notification
        $this->load->model('Notification_model');
        $this->Notification_model->sendFeePaymentNotification($processing->student_fees_master_id);
    }

    private function _next_receipt_no() {
        $school_id = $this->session->userdata('school_id');
        $this->db->where('school_id', $school_id);
        $row = $this->db->get('fee_receipt_no')->row();
        $next = ($row ? $row->receipt_no : 0) + 1;
        $this->db->where('school_id', $school_id);
        $this->db->update('fee_receipt_no', ['receipt_no' => $next]);
        return $next;
    }

    private function _build_gateway_url($settings, $amount, $ref) {
        // Implement using the gateway's SDK or HTTP client
        // Return redirect URL
    }

    private function _verify_payment($txn_id) {
        // Call gateway API to verify — never trust callback params alone
        // Return bool
    }
}
```

---

## Step 2 — Online Admission Gateway Controller

Create `application/controllers/onlineadmission/NewGateway.php`. Structure is identical to the student fee controller but uses `online_admission_payment` instead of `student_fees_deposite`:

```php
// Key difference — record payment in admission table
$this->db->insert('online_admission_payment', [
    'admission_id' => $admission_id,
    'gateway'      => 'newgateway',
    'txn_id'       => $txn_id,
    'amount'       => $amount,
    'status'       => 'success',
    'paid_at'      => date('Y-m-d H:i:s'),
]);
```

---

## Step 3 — Admin Settings

In `Paymentsettings.php`, add the gateway to the list of available gateways and handle saving its credentials to `payment_settings`.

In the view, add a settings card for the new gateway:

```php
<!-- views/admin/paymentsettings/index.php — add a new section -->
<div class="box box-primary" id="newgateway-settings">
    <div class="box-header">
        <h3 class="box-title">NewGateway</h3>
    </div>
    <div class="box-body">
        <div class="form-group">
            <label><?php echo $this->lang->line('api_key'); ?></label>
            <input type="text" name="newgateway_api_key" class="form-control"
                   value="<?php echo $settings->newgateway_api_key ?? ''; ?>">
        </div>
        <div class="form-group">
            <label><?php echo $this->lang->line('secret_key'); ?></label>
            <input type="password" name="newgateway_secret_key" class="form-control">
        </div>
        <div class="form-group">
            <label><?php echo $this->lang->line('test_mode'); ?></label>
            <input type="checkbox" name="newgateway_sandbox" value="1"
                   <?php echo ($settings->newgateway_sandbox ?? 0) ? 'checked' : ''; ?>>
        </div>
    </div>
</div>
```

---

## Step 4 — Webhook Handler (if applicable)

If the gateway pushes webhooks, add to `application/controllers/Webhooks.php`:

```php
public function newgateway() {
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    // Verify signature
    $signature = $_SERVER['HTTP_X_NEWGATEWAY_SIGNATURE'] ?? '';
    if (!$this->_verify_newgateway_signature($payload, $signature)) {
        http_response_code(401);
        exit('Unauthorized');
    }

    // Process
    if ($data['event'] === 'payment.success') {
        // Mark paid — same logic as _mark_paid() above
    }

    http_response_code(200);
    echo 'OK';
}
```

---

## Step 5 — Test Checklist

- [ ] Test mode works with gateway's sandbox credentials
- [ ] Student can initiate payment from the student portal
- [ ] Gateway redirect works
- [ ] Success callback marks the payment correctly in `student_fees_deposite`
- [ ] Failure callback does NOT create a deposit record
- [ ] Receipt number increments correctly
- [ ] WhatsApp/email notification fires on success
- [ ] Webhook verifies signature before marking paid
- [ ] Works for online admission payments too
- [ ] Settings are saved and loaded correctly from `payment_settings`
