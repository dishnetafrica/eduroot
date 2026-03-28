<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Superadmin
 *
 * Your panel at: https://admin.eduroot.in/superadmin/
 *
 * Routes:
 *   $route['superadmin']                         = 'superadmin/dashboard';
 *   $route['superadmin/login']                   = 'superadmin/login';
 *   $route['superadmin/registrations']           = 'superadmin/registrations';
 *   $route['superadmin/approve/(:num)']          = 'superadmin/approve/$1';
 *   $route['superadmin/reject/(:num)']           = 'superadmin/reject/$1';
 *   $route['superadmin/schools']                 = 'superadmin/schools';
 *   $route['superadmin/schools/create']          = 'superadmin/createSchool';
 *   $route['superadmin/schools/suspend/(:num)']  = 'superadmin/suspend/$1';
 *   $route['superadmin/schools/reactivate/(:num)'] = 'superadmin/reactivate/$1';
 *   $route['superadmin/logout']                  = 'superadmin/logout';
 */
class Superadmin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database('master', false, true);
        $this->load->library('session');
        $this->load->helper(['url', 'form']);

        // Gate: only allow on admin subdomain
        if (defined('IS_SUPERADMIN_PANEL') && !IS_SUPERADMIN_PANEL) {
            show_error('Forbidden', 403);
        }

        // Protect all methods except login
        if ($this->router->fetch_method() !== 'login'
            && !$this->session->userdata('superadmin_id')) {
            redirect('superadmin/login');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Auth
    // ──────────────────────────────────────────────────────────────────────────

    public function login(): void
    {
        if ($this->session->userdata('superadmin_id')) {
            redirect('superadmin/dashboard');
        }

        if ($this->input->post()) {
            $email = $this->input->post('email');
            $pass  = $this->input->post('password');

            $admin = $this->db->get_where('superadmins', ['email' => $email, 'is_active' => 1])->row();

            if ($admin && password_verify($pass, $admin->password)) {
                $this->session->set_userdata([
                    'superadmin_id'   => $admin->id,
                    'superadmin_name' => $admin->name,
                    'superadmin_email'=> $admin->email,
                ]);
                redirect('superadmin/dashboard');
                return;
            }

            $this->load->view('saas/superadmin/login', ['error' => 'Invalid credentials']);
            return;
        }

        $this->load->view('saas/superadmin/login');
    }

    public function logout(): void
    {
        $this->session->unset_userdata(['superadmin_id','superadmin_name','superadmin_email']);
        redirect('superadmin/login');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $data['stats'] = [
            'total'     => $this->db->count_all('schools'),
            'active'    => $this->db->where('status', 'active')->count_all_results('schools'),
            'trial'     => $this->db->where('status', 'active')->where('plan', 'trial')->count_all_results('schools'),
            'pending'   => $this->db->count_all('registration_requests'),
            'suspended' => $this->db->where('status', 'suspended')->count_all_results('schools'),
        ];

        $data['recent_schools'] = $this->db
            ->order_by('created_at', 'DESC')
            ->limit(10)
            ->get('schools')->result();

        $data['pending_requests'] = $this->db
            ->where('status', 'new')
            ->order_by('created_at', 'ASC')
            ->get('registration_requests')->result();

        $this->_view('dashboard', $data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Registration requests (self-registered schools waiting for approval)
    // ──────────────────────────────────────────────────────────────────────────

    public function registrations(): void
    {
        $filter = $this->input->get('status') ?? 'new';

        $this->db->where('status', $filter);
        $this->db->order_by('created_at', 'DESC');
        $data['requests'] = $this->db->get('registration_requests')->result();
        $data['filter']   = $filter;

        $this->_view('registrations', $data);
    }

    /**
     * POST /superadmin/approve/{request_id}
     * Approves registration → triggers full provisioning → school goes live
     */
    public function approve(int $request_id): void
    {
        $req = $this->db->get_where('registration_requests', ['id' => $request_id, 'status' => 'new'])->row();
        if (!$req) { show_error('Request not found', 404); }

        $this->load->library('SchoolProvisioner');

        $result = $this->schoolprovisioner->provision([
            'name'          => $req->school_name,
            'subdomain'     => $req->subdomain,
            'email'         => $req->email,
            'phone'         => $req->phone ?? '',
            'admin_name'    => $req->admin_name,
            'city'          => $req->city ?? '',
            'state'         => $req->state ?? '',
            'plan'          => 'trial',
        ]);

        if (!$result['success']) {
            $this->session->set_flashdata('error', 'Provisioning failed: ' . $result['error']);
            redirect('superadmin/registrations');
            return;
        }

        // Update registration record
        $this->db->where('id', $request_id);
        $this->db->update('registration_requests', [
            'status'    => 'approved',
            'school_id' => $result['school_id'],
        ]);

        // Mark approved_by in schools table
        $this->db->where('id', $result['school_id']);
        $this->db->update('schools', [
            'approved_by' => $this->session->userdata('superadmin_id'),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Email the school admin with their login details
        $this->_sendWelcomeEmail($req->email, $req->admin_name, $result);

        $this->session->set_flashdata('success',
            "School '{$req->school_name}' provisioned! URL: {$result['url']}");

        redirect('superadmin/registrations');
    }

    /**
     * POST /superadmin/reject/{request_id}
     */
    public function reject(int $request_id): void
    {
        $reason = $this->input->post('reason') ?? 'Application rejected.';

        $this->db->where('id', $request_id);
        $this->db->update('registration_requests', [
            'status'          => 'rejected',
            'rejected_reason' => $reason,
        ]);

        $this->session->set_flashdata('success', 'Application rejected.');
        redirect('superadmin/registrations');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Schools management
    // ──────────────────────────────────────────────────────────────────────────

    public function schools(): void
    {
        $status = $this->input->get('status') ?? '';
        $search = $this->input->get('search') ?? '';

        if ($status) { $this->db->where('status', $status); }
        if ($search) {
            $safe = $this->db->escape_like_str($search);
            $this->db->group_start();
            $this->db->like('name', $safe);
            $this->db->or_like('subdomain', $safe);
            $this->db->or_like('email', $safe);
            $this->db->group_end();
        }

        $this->db->order_by('created_at', 'DESC');
        $data['schools'] = $this->db->get('schools')->result();
        $data['status']  = $status;
        $data['search']  = $search;

        $this->_view('schools', $data);
    }

    /**
     * GET/POST /superadmin/schools/create
     * Create a school directly (without self-registration flow)
     */
    public function createSchool(): void
    {
        if ($this->input->post()) {
            $this->load->library('SchoolProvisioner');

            $result = $this->schoolprovisioner->provision([
                'name'       => $this->input->post('name'),
                'subdomain'  => $this->input->post('subdomain'),
                'email'      => $this->input->post('email'),
                'phone'      => $this->input->post('phone'),
                'admin_name' => $this->input->post('admin_name'),
                'city'       => $this->input->post('city'),
                'state'      => $this->input->post('state'),
                'plan'       => $this->input->post('plan') ?? 'trial',
            ]);

            if ($result['success']) {
                $this->db->where('id', $result['school_id']);
                $this->db->update('schools', [
                    'approved_by' => $this->session->userdata('superadmin_id'),
                    'approved_at' => date('Y-m-d H:i:s'),
                    'status'      => 'active',
                ]);

                $this->_sendWelcomeEmail($this->input->post('email'), $this->input->post('admin_name'), $result);
                $this->session->set_flashdata('success', "School created! URL: {$result['url']} | Password: {$result['temp_password']}");
            } else {
                $this->session->set_flashdata('error', $result['error']);
            }

            redirect('superadmin/schools');
        }

        $data['base_domain'] = $this->config->item('base_domain') ?? 'eduroot.in';
        $this->_view('create_school', $data);
    }

    public function suspend(int $school_id): void
    {
        $reason = $this->input->post('reason') ?? '';
        $this->load->library('SchoolProvisioner');
        $this->schoolprovisioner->suspend($school_id, $reason);
        $this->session->set_flashdata('success', 'School suspended.');
        redirect('superadmin/schools');
    }

    public function reactivate(int $school_id): void
    {
        $this->load->library('SchoolProvisioner');
        $this->schoolprovisioner->reactivate($school_id);
        $this->session->set_flashdata('success', 'School reactivated.');
        redirect('superadmin/schools');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function _sendWelcomeEmail(string $to_email, string $to_name, array $result): void
    {
        try {
            $this->load->library('email');
            $this->email->from('welcome@eduroot.in', 'EduRoot');
            $this->email->to($to_email);
            $this->email->subject('Your EduRoot school is ready!');
            $this->email->message(
                "Dear {$to_name},\n\n" .
                "Your EduRoot school is ready. Here are your login details:\n\n" .
                "URL:      {$result['url']}\n" .
                "Email:    {$result['login_email']}\n" .
                "Password: {$result['temp_password']}\n\n" .
                "Please change your password after first login.\n\n" .
                "Your 30-day free trial has started.\n\n" .
                "— The EduRoot Team"
            );
            $this->email->send();
        } catch (\Exception $e) {
            log_message('error', 'Superadmin::_sendWelcomeEmail — ' . $e->getMessage());
        }
    }

    private function _view(string $view, array $data = []): void
    {
        $data['admin_name'] = $this->session->userdata('superadmin_name');
        $this->load->view('saas/superadmin/layout_header', $data);
        $this->load->view("saas/superadmin/{$view}", $data);
        $this->load->view('saas/superadmin/layout_footer', $data);
    }
}
