# Coding Standards

This document describes the conventions used in the EduRoot codebase. Follow these when adding new code or refactoring existing files.

---

## PHP Version

The codebase targets **PHP 7.4** minimum. Avoid features introduced in PHP 8.0+ unless you've verified CI4 is in use and PHP 8.x is confirmed in production.

PHP 7.4-compatible features:
- Typed properties (`string $name;`)
- Arrow functions (`fn($x) => $x * 2`)
- Null coalescing assignment (`$a ??= 'default'`)

Avoid:
- `match` expressions (PHP 8.0+)
- Named arguments (PHP 8.0+)
- Union types in function signatures (PHP 8.0+)

---

## CodeIgniter Conventions

### Controllers

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feetype extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Feetype_model');
        // Auth check
        if (!$this->session->userdata('user_id')) {
            redirect('site/login');
        }
    }

    public function index() {
        $data['fee_types'] = $this->Feetype_model->getAll();
        $this->load->view('admin/feetype/index', $data);
    }

    public function edit($id) {
        // Always validate input
        $this->form_validation->set_rules('name', 'Fee Type Name', 'required|trim');
        if ($this->form_validation->run() === FALSE) {
            // Return validation errors
        }
    }
}
```

**Rules:**
- Every controller method that processes POST must validate input via `form_validation`
- Never trust `$_POST` or `$_GET` directly — use `$this->input->post()` and `$this->input->get()`
- Always check authentication in `__construct()`
- Return JSON from AJAX methods using `echo json_encode($data); exit;`

### Models

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Feetype_model extends CI_Model {

    public function getAll($school_id = null) {
        if ($school_id) {
            $this->db->where('school_id', $school_id);
        }
        return $this->db->get('feetype')->result();
    }

    public function create($data) {
        return $this->db->insert('feetype', $data);
    }

    public function edit($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('feetype', $data);
    }

    public function delete($id) {
        // Prefer soft delete where the table has is_active
        $this->db->where('id', $id);
        return $this->db->update('feetype', ['is_active' => 0]);
    }
}
```

**Rules:**
- All database queries go in models — never in controllers or views
- Use Active Record methods, not raw SQL strings
- Use `$this->db->escape()` if you must build a query string
- Use `result()` for arrays of objects, `row()` for single object, `result_array()` only when you need arrays
- Always scope by `school_id` in multi-tenant queries

### Views

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- Load layout header -->
<?php $this->load->view('layout/header'); ?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?php echo $this->lang->line('fee_type'); ?></h1>
    </section>

    <section class="content">
        <!-- Always use lang() for user-facing strings -->
        <p><?php echo $this->lang->line('description'); ?></p>

        <!-- Always escape output -->
        <span><?php echo html_escape($fee_type->name); ?></span>

        <!-- Forms use CI form helpers -->
        <?php echo form_open('admin/feetype/create'); ?>
            <input type="text" name="name" value="<?php echo set_value('name'); ?>">
            <?php echo form_error('name'); ?>
        <?php echo form_close(); ?>
    </section>
</div>

<?php $this->load->view('layout/footer'); ?>
```

**Rules:**
- Never echo raw user input — always use `html_escape()`
- All user-facing strings must use `$this->lang->line('key')` — never hardcode English strings in views
- Use CI's `form_open()` / `form_close()` for POST forms — this auto-inserts CSRF token
- Keep logic out of views — computed values should come from the controller

---

## Naming Conventions

| What | Convention | Example |
|---|---|---|
| Controller class | PascalCase | `FeeType`, `StudentFee` |
| Controller file | PascalCase.php | `Feetype.php`, `Studentfee.php` |
| Model class | `Name_model` | `Feetype_model` |
| Model file | `Name_model.php` | `Feetype_model.php` |
| View folder | lowercase | `views/admin/feetype/` |
| View file | lowercase.php | `index.php`, `edit.php` |
| Method names | camelCase | `getAll()`, `collectFee()` |
| DB table names | snake_case plural | `fee_groups`, `student_sessions` |
| Config keys | snake_case | `$config['saas_enabled']` |
| Language keys | snake_case | `fee_type`, `collection_date` |
| JS variables | camelCase | `studentId`, `feeAmount` |

---

## Security Rules

### SQL Injection Prevention

Always use Active Record bindings:

```php
// GOOD
$this->db->where('student_id', $id);
$this->db->get('students');

// GOOD — named bindings
$this->db->query("SELECT * FROM students WHERE id = ?", [$id]);

// BAD — never do this
$this->db->query("SELECT * FROM students WHERE id = " . $_GET['id']);
```

### XSS Prevention

```php
// Output — always escape
echo html_escape($user_input);

// Input — use CI's XSS clean for rich-text fields only
$body = $this->input->post('body', TRUE); // second param = XSS clean
```

### File Upload Validation

Never trust the uploaded file's extension alone. Validate MIME type:

```php
$config = [
    'allowed_types' => 'jpg|jpeg|png',
    'max_size'      => 2048, // KB
    'encrypt_name'  => TRUE,
];
$this->load->library('upload', $config);
if (!$this->upload->do_upload('photo')) {
    // handle error
}
```

---

## Language / Internationalisation

Every user-facing string must be in the language file:

1. Add to `application/language/English/{module}_lang.php`:
```php
$lang['your_new_key'] = 'Your English String';
```

2. Use in view:
```php
echo $this->lang->line('your_new_key');
```

3. Translators then add the same key to all 78 language files.

**Never** hardcode English strings in views, controllers, or error messages shown to users.

---

## AJAX Responses

All AJAX endpoints must return consistent JSON:

```php
// Success
echo json_encode([
    'status'  => 'success',
    'message' => $this->lang->line('record_saved'),
    'data'    => $result,
]);
exit;

// Error
echo json_encode([
    'status'  => 'error',
    'message' => $this->lang->line('something_went_wrong'),
]);
exit;
```

---

## Error Handling

```php
// For user-facing errors, flash to session and redirect
$this->session->set_flashdata('error', $this->lang->line('delete_failed'));
redirect('admin/feetype');

// For AJAX errors
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => '...']);
exit;

// Never expose stack traces in production
// ENVIRONMENT=production suppresses this automatically
```

---

## Common Anti-Patterns to Avoid

| Anti-pattern | Better approach |
|---|---|
| Business logic in views | Move to controller or model |
| Raw `$_POST` access | Use `$this->input->post('key')` |
| Direct SQL strings | Use Active Record |
| Hardcoded English in views | Use `$this->lang->line()` |
| `exit()` in cron jobs | Use `return;` — `exit()` kills the master process |
| Duplicate query in controller + model | Query once in model, pass result to controller |
| `SELECT *` in models | Specify needed columns |
| Missing auth check in controller | Add to `__construct()` |
