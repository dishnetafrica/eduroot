<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Whatsappgateway
{
    private $CI;
    private $api_url  = 'https://whatsml.eduroot.in/api/whatsapp-web/send-message';
    private $app_key  = '159392e4-4a05-4374-aefd-735debe424d8'; // Replace with your app key
    private $auth_key = 'TSrGRonD6ozbQ7ycCHoBRPfH6gP9OLrn1';

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    // =============================================
    // CORE SEND FUNCTION
    // =============================================
    private function sendWhatsApp($to, $message)
    {
        if (empty($to) || empty($message)) return false;

        // Clean phone number
        $to = preg_replace('/[^0-9]/', '', $to);
        if (empty($to)) return false;

        if (strlen($to) == 10 && in_array(substr($to, 0, 1), ['6','7','8','9'])) {
            $to = '91' . $to; // Add India country code
        }
    
        // If number is 11 digits starting with 0, remove 0 and add 91
        if (strlen($to) == 11 && substr($to, 0, 1) == '0') {
            $to = '91' . substr($to, 1);
        }

        $data = json_encode([
            'app_key'  => $this->app_key,
            'auth_key' => $this->auth_key,
            'to'       => $to,
            'type'     => 'text',
            'message'  => $message,
        ]);

        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        return ($httpCode == 200 && isset($result['success']) && $result['success']);
    }

    // =============================================
    // TEMPLATE BUILDER
    // Build message from template + student data
    // =============================================
    public function buildMessage($template, $student_data, $exam_name, $class_name, $marks_array)
    {
        // Build subject marks block
        $subject_marks = '';
        $grand_total   = 0;
        $max_total     = 0;

        foreach ($marks_array as $mark) {
            $subject_marks .= "  • {$mark['subject']}: {$mark['marks']}/{$mark['max']}\n";
            $grand_total   += floatval($mark['marks']);
            $max_total     += floatval($mark['max']);
        }

        $percentage = ($max_total > 0) ? round(($grand_total / $max_total) * 100, 2) : 0;

        // Replace all placeholders
        $replacements = [
            '{{student_name}}'       => $student_data['student_name'] ?? '',
            '{{exam_roll_no}}'       => $student_data['roll_no'] ?? '',
            '{{roll_no}}'            => $student_data['roll_no'] ?? '',
            '{{admission_no}}'       => $student_data['admission_no'] ?? '',
            '{{exam}}'               => $exam_name,
            '{{class}}'              => $class_name,
            '{{subject_marks}}'      => rtrim($subject_marks),
            '{{grand_total}}'        => $grand_total . '/' . $max_total,
            '{{percentage}}'         => $percentage . '%',
            '{{father_name}}'        => $student_data['father_name'] ?? '',
            '{{guardian_name}}'      => $student_data['guardian_name'] ?? '',
            '{{guardian_phone}}'     => $student_data['guardian_phone'] ?? '',
            '{{exam_date}}'          => $student_data['exam_date'] ?? '',
        ];

        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }

        return $template;
    }

    // =============================================
    // DIRECT MESSAGE (used by exam result)
    // =============================================
    public function sendDirectMessage($to, $message)
    {
        return $this->sendWhatsApp($to, $message);
    }

    // =============================================
    // EXISTING METHODS (for mailsmsconf.php)
    // =============================================
    private function buildFromArray($detail, $template)
    {
        foreach ($detail as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        return $template;
    }

    public function sentExamResultWhatsapp($detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        if (!empty($detail['contact_numbers'])) {
            foreach ($detail['contact_numbers'] as $number) {
                if (!empty($number)) {
                    $this->sendWhatsApp($number, $message);
                }
            }
        }
    }

    public function sentRegisterWhatsapp($student_id, $to, $template, $whatsapp_template_id)
    {
        $student = $this->CI->student_model->get($student_id);
        $message = $this->buildFromArray($student, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentAddFeeWhatsapp($detail, $to, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray((array)$detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentAddGroupFeeWhatsapp($detail, $to, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray((array)$detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentFeeProcessingNotification($detail, $to, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray((array)$detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentfeesreminderNotification($detail, $to, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray((array)$detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sendstudentlhomework($student_list, $template, $whatsapp_template_id)
    {
        foreach ($student_list as $number => $detail) {
            if (!empty($number)) {
                $message = $this->buildFromArray($detail, $template);
                $this->sendWhatsApp($number, $message);
            }
        }
    }

    public function sentOnlineexamStudentWhatsapp($student_list, $template, $whatsapp_template_id)
    {
        foreach ($student_list as $number => $detail) {
            if (!empty($number)) {
                $message = $this->buildFromArray($detail, $template);
                $this->sendWhatsApp($number, $message);
            }
        }
    }

    public function sendPresentAttendancenotification($detail, $template, $whatsapp_template_id, $to)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sendAbsentAttendancenotification($detail, $template, $whatsapp_template_id, $to)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentPresentStaffWhatsapp($detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($detail['contact_no'], $message);
    }

    public function sentAbsentStaffWhatsapp($detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($detail['contact_no'], $message);
    }

    public function sendOnlineadmissionformsubmit($detail, $template, $to, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sentstudentOnlineadmissionFeessubmissionWhatsapp($detail, $template, $to, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($to, $message);
    }

    public function sendStudentLoginCredential($chk_mail_sms, $detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($detail['contact_no'], $message);
    }

    public function sendStaffLoginCredential($chk_mail_sms, $detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($detail['contact_no'], $message);
    }

    public function student_apply_leave($detail, $template, $whatsapp_template_id)
    {
        $message = $this->buildFromArray($detail, $template);
        $this->sendWhatsApp($detail['contact_no'], $message);
    }
}