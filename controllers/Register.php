<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Register
 *
 * Public self-registration form at: https://eduroot.in/register
 * School fills in details → goes into registration_requests → you approve
 *
 * Routes needed in application/config/routes.php:
 *   $route['register']           = 'register/index';
 *   $route['register/submit']    = 'register/submit';
 *   $route['register/check']     = 'register/checkSubdomain';
 *   $route['register/thanks']    = 'register/thanks';
 */
class Register extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper(['form', 'url']);
        $this->load->database('master', false, true);
    }

    /**
     * GET /register  —  Show the registration form
     */
    public function index(): void
    {
        $data['title'] = 'Register Your School — EduRoot';
        $data['plans'] = [
            'trial'      => ['name' => 'Free Trial', 'price' => '₹0',    'days' => 30, 'students' => 500],
            'basic'      => ['name' => 'Basic',      'price' => '₹999',  'mo' => true, 'students' => 500],
            'pro'        => ['name' => 'Pro',         'price' => '₹2499', 'mo' => true, 'students' => 2000],
            'enterprise' => ['name' => 'Enterprise',  'price' => '₹5999', 'mo' => true, 'students' => 9999],
        ];
        $this->load->view('saas/register', $data);
    }

    /**
     * POST /register/submit  —  Process registration form
     */
    public function submit(): void
    {
        $this->form_validation->set_rules('school_name',    'School name',   'required|trim|max_length[200]');
        $this->form_validation->set_rules('subdomain',      'Subdomain',     'required|trim|alpha_dash|min_length[3]|max_length[50]|callback__validateSubdomain');
        $this->form_validation->set_rules('admin_name',     'Your name',     'required|trim|max_length[200]');
        $this->form_validation->set_rules('email',          'Email',         'required|trim|valid_email|max_length[200]');
        $this->form_validation->set_rules('phone',          'Phone',         'trim|max_length[20]');
        $this->form_validation->set_rules('city',           'City',          'trim|max_length[100]');
        $this->form_validation->set_rules('state',          'State',         'trim|max_length[100]');
        $this->form_validation->set_rules('student_count',  'Student count', 'trim|integer');
        $this->form_validation->set_rules('agree',          'Terms',         'required');

        if ($this->form_validation->run() === false) {
            $this->index();
            return;
        }

        // Check for duplicate email registration
        $email = $this->input->post('email');
        $existing = $this->db->get_where('registration_requests', ['email' => $email, 'status !=' => 'rejected'])->row();
        if ($existing) {
            $this->session->set_flashdata('error', 'An application with this email already exists.');
            $this->index();
            return;
        }

        $this->db->insert('registration_requests', [
            'school_name'   => $this->input->post('school_name'),
            'subdomain'     => strtolower(trim($this->input->post('subdomain'))),
            'admin_name'    => $this->input->post('admin_name'),
            'email'         => $email,
            'phone'         => $this->input->post('phone'),
            'city'          => $this->input->post('city'),
            'state'         => $this->input->post('state'),
            'student_count' => (int) $this->input->post('student_count') ?: null,
            'message'       => $this->input->post('message'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        // Notify you by email
        $this->_notifySuperadmin($this->input->post('school_name'), $email);

        redirect('register/thanks');
    }

    /**
     * GET /register/thanks
     */
    public function thanks(): void
    {
        $this->load->view('saas/register_thanks');
    }

    /**
     * GET /register/check?subdomain=greenvalley
     * AJAX endpoint — checks if subdomain is available
     */
    public function checkSubdomain(): void
    {
        header('Content-Type: application/json');
        $sub = strtolower(trim($this->input->get('subdomain') ?? ''));

        if (strlen($sub) < 3 || !preg_match('/^[a-z0-9\-]+$/', $sub)) {
            echo json_encode(['available' => false, 'reason' => 'Invalid format']);
            return;
        }

        $reserved = ['www','admin','api','static','cdn','support','billing',
                     'mail','app','login','register','eduroot'];
        if (in_array($sub, $reserved, true)) {
            echo json_encode(['available' => false, 'reason' => 'Reserved name']);
            return;
        }

        $exists = $this->db->get_where('subdomain_cache', ['subdomain' => $sub])->row()
               || $this->db->get_where('registration_requests', ['subdomain' => $sub, 'status !=' => 'rejected'])->row();

        echo json_encode([
            'available' => !$exists,
            'reason'    => $exists ? 'Already taken' : null,
            'preview'   => "{$sub}." . ($this->config->item('base_domain') ?? 'eduroot.in'),
        ]);
    }

    // ── Validation callback ──────────────────────────────────────────────────

    public function _validateSubdomain(string $sub): bool
    {
        $reserved = ['www','admin','api','static','cdn','support','billing',
                     'mail','app','login','register','eduroot'];
        $sub = strtolower(trim($sub));

        if (in_array($sub, $reserved, true)) {
            $this->form_validation->set_message('_validateSubdomain', 'That subdomain is reserved.');
            return false;
        }

        $exists = $this->db->get_where('subdomain_cache', ['subdomain' => $sub])->row()
               || $this->db->get_where('registration_requests', ['subdomain' => $sub, 'status !=' => 'rejected'])->row();
        if ($exists) {
            $this->form_validation->set_message('_validateSubdomain', 'That subdomain is already taken.');
            return false;
        }

        return true;
    }

    private function _notifySuperadmin(string $school_name, string $email): void
    {
        try {
            $this->load->library('email');
            $this->email->from('no-reply@eduroot.in', 'EduRoot');
            $this->email->to('superadmin@eduroot.in');
            $this->email->subject("New registration: {$school_name}");
            $this->email->message("New school registration from {$school_name} ({$email}).\n\nReview at: " . admin_url('superadmin/registrations'));
            $this->email->send();
        } catch (\Exception $e) {
            log_message('error', 'Register::_notifySuperadmin — ' . $e->getMessage());
        }
    }
}
