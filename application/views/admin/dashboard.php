<?php
/**
 * EduRoot — Mobile-First Dashboard View
 * File: application/views/admin/dashboard.php
 *
 * Replace existing dashboard.php with this file.
 * All PHP variables used here come from Admin::dashboard() controller.
 */

$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$role            = $this->customlib->getStaffRole();
$role_name       = json_decode($role)->name;
$userdata        = $this->customlib->getUserData();
$school_name     = $this->setting_model->getCurrentSchoolName();
$session_name    = $this->setting_model->getCurrentSessionName();
?>

<!-- ============================================================
  MOBILE-FIRST CSS — save as backend/dist/css/eduroot-mobile.css
  and include in layout/header.php AFTER AdminLTE CSS:
  <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/eduroot-mobile.css">
  ============================================================ -->
<style>
:root {
  --er-primary: #1a56db;
  --er-primary-dark: #1240a8;
  --er-primary-light: #e8f0fe;
  --er-accent: #f59e0b;
  --er-success: #10b981;
  --er-danger: #ef4444;
  --er-warning: #f59e0b;
  --er-bg: #f0f4f8;
  --er-surface: #fff;
  --er-text: #0f172a;
  --er-muted: #64748b;
  --er-border: #e2e8f0;
  --er-radius: 12px;
  --er-radius-sm: 8px;
  --er-shadow: 0 1px 3px rgba(0,0,0,0.07);
  --er-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
}

/* ===== DASHBOARD WRAPPER ===== */
.er-dashboard { padding: 0 0 80px 0; }
@media(min-width:768px){ .er-dashboard { padding-bottom: 0; } }

/* ===== GRADIENT HEADER ===== */
.er-dash-header {
  background: linear-gradient(135deg, #1a56db 0%, #0891b2 100%);
  border-radius: var(--er-radius);
  padding: 16px 18px;
  margin-bottom: 16px;
  color: white;
  box-shadow: 0 4px 20px rgba(26,86,219,0.3);
}
.er-dash-header h2 {
  font-size: 19px;
  font-weight: 700;
  margin: 0 0 4px 0;
}
.er-dash-badge {
  background: rgba(255,255,255,0.2);
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  display: inline-block;
}
.er-dash-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}
.er-pill {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.er-pill-danger  { background: #fee2e2; color: #991b1b; }
.er-pill-warning { background: #fef3c7; color: #92400e; }
.er-pill-success { background: #d1fae5; color: #065f46; }
.er-pill-info    { background: #cffafe; color: #155e75; }

/* ===== CARD ===== */
.er-card {
  background: var(--er-surface);
  border-radius: var(--er-radius);
  box-shadow: var(--er-shadow);
  margin-bottom: 14px;
  overflow: hidden;
  border: none !important;
  transition: box-shadow 0.18s;
}
.er-card:hover { box-shadow: var(--er-shadow-md); }
.er-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 13px 15px;
  border-bottom: 1px solid var(--er-border);
  font-size: 13px;
  font-weight: 700;
  color: var(--er-text);
}
.er-card-header i { font-size: 14px; }
.er-card-link {
  margin-left: auto;
  font-size: 11px;
  font-weight: 600;
  color: var(--er-primary);
  text-decoration: none;
}
.er-card-body { padding: 14px; }
.er-card-body-p0 { padding: 0; }

/* ===== MINI STATS ===== */
.er-mini-stat {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px;
  background: var(--er-bg);
  border-radius: var(--er-radius-sm);
  cursor: pointer;
  transition: all 0.18s;
  text-decoration: none;
}
.er-mini-stat:hover { background: var(--er-primary-light); }
.er-mini-icon {
  width: 44px; height: 44px;
  border-radius: var(--er-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  flex-shrink: 0;
}
.er-bg-blue   { background: linear-gradient(135deg,#1a56db,#3b82f6); }
.er-bg-green  { background: linear-gradient(135deg,#10b981,#34d399); }
.er-bg-orange { background: linear-gradient(135deg,#f59e0b,#fbbf24); }
.er-bg-purple { background: linear-gradient(135deg,#8b5cf6,#a78bfa); }
.er-bg-red    { background: linear-gradient(135deg,#ef4444,#f87171); }
.er-bg-cyan   { background: linear-gradient(135deg,#06b6d4,#22d3ee); }

.er-mini-val {
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
  color: var(--er-text);
}
.er-mini-lbl {
  font-size: 10px;
  font-weight: 600;
  color: var(--er-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 3px;
}

/* ===== GRID LAYOUTS ===== */
.er-grid-4 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}
@media(min-width:576px){ .er-grid-4 { grid-template-columns: repeat(4,1fr); } }

.er-grid-2 { display: grid; grid-template-columns: 1fr; gap: 14px; }
@media(min-width:768px){ .er-grid-2 { grid-template-columns: 1fr 1fr; } }

.er-grid-stat {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.er-grid-stat-3 {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 8px;
}

/* ===== STAT ITEM ===== */
.er-stat-item {
  background: var(--er-bg);
  border-radius: var(--er-radius-sm);
  padding: 12px;
  text-align: center;
  transition: all 0.18s;
}
.er-stat-item:hover { background: var(--er-primary-light); }
.er-stat-val {
  font-size: 20px;
  font-weight: 700;
  color: var(--er-text);
  line-height: 1;
}
.er-stat-lbl {
  font-size: 10px;
  font-weight: 600;
  color: var(--er-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

/* ===== PROGRESS ===== */
.er-progress-wrap {
  height: 6px;
  background: var(--er-border);
  border-radius: 3px;
  overflow: hidden;
  margin-top: 6px;
}
.er-progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s;
}
.er-fill-green  { background: linear-gradient(90deg,#10b981,#34d399); }
.er-fill-blue   { background: linear-gradient(90deg,#1a56db,#3b82f6); }
.er-fill-orange { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.er-fill-red    { background: linear-gradient(90deg,#ef4444,#f87171); }

/* ===== FEE OVERVIEW ===== */
.er-fee-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  margin-top: 10px;
}
.er-fee-row .label { color: var(--er-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
.er-fee-row .val   { font-weight: 700; color: var(--er-text); }

/* ===== CONFIG GRID ===== */
.er-config-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
@media(min-width:576px){ .er-config-grid { grid-template-columns: repeat(3,1fr); } }
@media(min-width:992px){ .er-config-grid { grid-template-columns: repeat(4,1fr); } }

.er-config-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 11px;
  background: var(--er-bg);
  border-radius: var(--er-radius-sm);
  font-size: 12px;
  font-weight: 500;
  color: var(--er-text);
  gap: 6px;
}
.er-config-item i { color: var(--er-muted); font-size: 13px; }

.er-status-on  { background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.er-status-off { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }

/* ===== GATEWAY TAGS ===== */
.er-gateway-list { display: flex; flex-wrap: wrap; gap: 7px; }
.er-gateway-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  background: var(--er-primary-light);
  color: var(--er-primary);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

/* ===== EVENT LIST ===== */
.er-event-item {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 10px 15px;
  border-bottom: 1px solid var(--er-border);
}
.er-event-item:last-child { border-bottom: none; }
.er-event-date {
  min-width: 42px;
  text-align: center;
  padding: 6px;
  background: linear-gradient(135deg,#1a56db,#06b6d4);
  color: white;
  border-radius: var(--er-radius-sm);
  flex-shrink: 0;
}
.er-event-date .day { font-size: 18px; font-weight: 700; line-height: 1; }
.er-event-date .mon { font-size: 10px; font-weight: 600; text-transform: uppercase; }
.er-event-title { font-size: 13px; font-weight: 600; color: var(--er-text); }
.er-event-meta  { font-size: 11px; color: var(--er-muted); margin-top: 2px; }

/* ===== STUDENT FEE HISTORY TABLE ===== */
.er-table { width: 100%; border-collapse: collapse; }
.er-table th {
  font-size: 11px;
  font-weight: 700;
  color: var(--er-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 8px 12px;
  background: var(--er-bg);
  border-bottom: 1px solid var(--er-border);
  text-align: left;
  white-space: nowrap;
}
.er-table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--er-border);
  font-size: 13px;
  vertical-align: middle;
}
.er-table tr:last-child td { border-bottom: none; }
.er-table tr:hover td { background: #f8fafc; }

.er-pay-mode {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  background: var(--er-bg);
  color: var(--er-muted);
}
.er-pay-mode.online { background: var(--er-primary-light); color: var(--er-primary); }
.er-pay-mode.cash   { background: #d1fae5; color: #065f46; }
.er-pay-mode.cheque { background: #fef3c7; color: #92400e; }

/* ===== ALERT NOTICES ===== */
.er-notice {
  border-left: 4px solid var(--er-primary);
  background: var(--er-primary-light);
  border-radius: var(--er-radius-sm);
  padding: 12px 14px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 500;
  color: var(--er-primary);
}
.er-notice .close { color: var(--er-primary); opacity: 0.7; font-size: 16px; }

/* ===== BOTTOM NAV (mobile only) ===== */
.er-bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: 62px;
  background: var(--er-surface);
  border-top: 1px solid var(--er-border);
  display: flex;
  align-items: center;
  justify-content: space-around;
  z-index: 1050;
}
@media(min-width:768px){ .er-bottom-nav { display: none; } }

.er-bnav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 6px 10px;
  color: var(--er-muted);
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.18s;
  position: relative;
}
.er-bnav-item i { font-size: 19px; }
.er-bnav-item.active { color: var(--er-primary); }
.er-bnav-dot {
  position: absolute;
  top: 4px; right: 6px;
  width: 7px; height: 7px;
  background: var(--er-danger);
  border-radius: 50%;
  border: 1.5px solid white;
}

/* ===== UTILITIES ===== */
.er-text-success { color: var(--er-success) !important; }
.er-text-danger  { color: var(--er-danger) !important; }
.er-fw700 { font-weight: 700; }
.er-fs11  { font-size: 11px; }
.er-mt8   { margin-top: 8px; }
.er-mt12  { margin-top: 12px; }
</style>

<div class="content-wrapper" style="background:#f0f4f8;min-height:100vh">
  <section class="content er-dashboard">

    <!-- ============================================================
      ENVIRONMENT WARNING
      ============================================================ -->
    <?php if (ENVIRONMENT != 'production'): ?>
      <div class="alert alert-danger" style="border-radius:8px;margin-bottom:12px">
        <strong>Environment: <?php echo ENVIRONMENT; ?></strong> — Remember to set back to production in index.php.
      </div>
    <?php endif; ?>

    <!-- ============================================================
      NOTIFICATIONS / NOTICES
      ============================================================ -->
    <?php
    $role_id = json_decode($this->customlib->getStaffRole())->id;
    foreach ($notifications as $notice_value):
      $show = false;
      if ($role_id == 7) {
        $show = true;
      } elseif (date($this->customlib->getSchoolDateFormat()) >= date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($notice_value->publish_date))) {
        $show = true;
      }
      if ($show):
    ?>
      <div class="er-notice">
        <a href="<?php echo site_url('admin/notification') ?>" style="color:inherit;text-decoration:none">
          <i class="fa fa-bell" style="margin-right:6px"></i><?php echo $notice_value->title; ?>
        </a>
        <button type="button" class="close close_notice" data-dismiss="alert" data-noticeid="<?php echo $notice_value->id; ?>" style="background:none;border:none;cursor:pointer">&times;</button>
      </div>
    <?php endif; endforeach; ?>

    <!-- ============================================================
      GRADIENT HEADER
      ============================================================ -->
    <div class="er-dash-header">
      <h2><?php echo $this->lang->line('dashboard'); ?></h2>
      <span class="er-dash-badge"><?php echo $school_name; ?> &bull; <?php echo $role_name; ?> &bull; <?php echo $session_name; ?></span>
      <div class="er-dash-pills">
        <?php if (!empty($fees_overview['total_unpaid']) && $fees_overview['total_unpaid'] > 0): ?>
          <a href="<?php echo site_url('financereports/reportduefees'); ?>" class="er-pill er-pill-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $fees_overview['total_unpaid']; ?> <?php echo $this->lang->line('unpaid'); ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($fees_overview['total_partial']) && $fees_overview['total_partial'] > 0): ?>
          <a href="<?php echo site_url('financereports/reportduefees'); ?>" class="er-pill er-pill-warning">
            <i class="fa fa-clock-o"></i> <?php echo $fees_overview['total_partial']; ?> <?php echo $this->lang->line('partial'); ?>
          </a>
        <?php endif; ?>
        <?php if ($this->rbac->hasPrivilege('staff_present_today_widegts', 'can_view')): ?>
          <span class="er-pill er-pill-success">
            <i class="fa fa-calendar-check-o"></i>
            <?php echo ($Staffattendence_data + 0); ?>/<?php echo $getTotalStaff_data; ?> <?php echo $this->lang->line('staff'); ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============================================================
      ROW 1: MINI STAT CARDS
      ============================================================ -->
    <div class="er-grid-4">
      <?php if ($this->rbac->hasPrivilege('student', 'can_view')): ?>
        <a href="<?php echo site_url('student'); ?>" class="er-mini-stat">
          <div class="er-mini-icon er-bg-blue"><i class="fa fa-user-graduate"></i></div>
          <div>
            <div class="er-mini-val"><?php echo number_format($total_students); ?></div>
            <div class="er-mini-lbl"><?php echo $this->lang->line('students'); ?></div>
          </div>
        </a>
      <?php endif; ?>

      <?php if (isset($roles['Teacher']) && $this->rbac->hasPrivilege('staff', 'can_view')): ?>
        <a href="<?php echo site_url('admin/staff'); ?>" class="er-mini-stat">
          <div class="er-mini-icon er-bg-orange"><i class="fa fa-chalkboard-teacher"></i></div>
          <div>
            <div class="er-mini-val"><?php echo number_format($roles['Teacher'] ?? 0); ?></div>
            <div class="er-mini-lbl"><?php echo $this->lang->line('teachers'); ?></div>
          </div>
        </a>
      <?php endif; ?>

      <?php if (isset($roles['Parent']) && $this->rbac->hasPrivilege('parent', 'can_view')): ?>
        <a href="<?php echo site_url('admin/parent'); ?>" class="er-mini-stat">
          <div class="er-mini-icon er-bg-green"><i class="fa fa-users"></i></div>
          <div>
            <div class="er-mini-val"><?php echo number_format($roles['Parent'] ?? 0); ?></div>
            <div class="er-mini-lbl"><?php echo $this->lang->line('parents'); ?></div>
          </div>
        </a>
      <?php endif; ?>

      <?php if ($this->rbac->hasPrivilege('fees_collection', 'can_view')): ?>
        <a href="<?php echo site_url('studentfee'); ?>" class="er-mini-stat">
          <div class="er-mini-icon er-bg-purple"><i class="fa fa-money"></i></div>
          <div>
            <div class="er-mini-val"><?php echo $currency_symbol . number_format($month_collection, 0); ?></div>
            <div class="er-mini-lbl"><?php echo $this->lang->line('this_month'); ?></div>
          </div>
        </a>
      <?php endif; ?>
    </div>

    <!-- ============================================================
      ROW 2: ATTENDANCE + FEES COLLECTION
      ============================================================ -->
    <div class="er-grid-2">

      <!-- ATTENDANCE -->
      <?php if ($this->rbac->hasPrivilege('staff_present_today_widegts', 'can_view') || ($this->module_lib->hasActive('student_attendance') && $this->rbac->hasPrivilege('student_present_today_widegts', 'can_view'))): ?>
        <div class="er-card">
          <div class="er-card-header">
            <i class="fa fa-calendar-check-o" style="color:#10b981"></i>
            <?php echo $this->lang->line('attendance'); ?>
            <a href="<?php echo site_url('attendencereports'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
          </div>
          <div class="er-card-body">
            <div class="er-grid-stat">
              <?php if ($this->rbac->hasPrivilege('student_present_today_widegts', 'can_view')): ?>
                <?php
                  $present_students = 0 + ($attendence_data['total_half_day'] ?? 0) + ($attendence_data['total_late'] ?? 0) + ($attendence_data['total_present'] ?? 0);
                  $student_pct = ($total_students > 0) ? round(($present_students / $total_students) * 100) : 0;
                ?>
                <div class="er-stat-item">
                  <div class="er-stat-val"><?php echo $present_students; ?><small style="font-size:12px;color:var(--er-muted)">/<?php echo $total_students; ?></small></div>
                  <div class="er-stat-lbl"><?php echo $this->lang->line('students_present'); ?></div>
                  <div class="er-progress-wrap"><div class="er-progress-fill er-fill-green" style="width:<?php echo $student_pct; ?>%"></div></div>
                  <div style="font-size:10px;color:var(--er-muted);margin-top:3px"><?php echo $student_pct; ?>%</div>
                </div>
              <?php endif; ?>

              <?php if ($this->rbac->hasPrivilege('staff_present_today_widegts', 'can_view')): ?>
                <?php
                  $staff_pct = ($getTotalStaff_data > 0) ? round(($Staffattendence_data / $getTotalStaff_data) * 100) : 0;
                ?>
                <div class="er-stat-item">
                  <div class="er-stat-val"><?php echo ($Staffattendence_data + 0); ?><small style="font-size:12px;color:var(--er-muted)">/<?php echo $getTotalStaff_data; ?></small></div>
                  <div class="er-stat-lbl"><?php echo $this->lang->line('staff_present'); ?></div>
                  <div class="er-progress-wrap"><div class="er-progress-fill er-fill-blue" style="width:<?php echo $staff_pct; ?>%"></div></div>
                  <div style="font-size:10px;color:var(--er-muted);margin-top:3px"><?php echo $staff_pct; ?>%</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- FEES COLLECTION -->
      <?php if ($this->module_lib->hasActive('fees_collection') && $this->rbac->hasPrivilege('fees_overview_widegts', 'can_view')): ?>
        <div class="er-card">
          <div class="er-card-header">
            <i class="fa fa-money" style="color:var(--er-primary)"></i>
            <?php echo $this->lang->line('fees_overview'); ?>
            <a href="<?php echo site_url('financereports'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
          </div>
          <div class="er-card-body">
            <div class="er-grid-stat-3" style="margin-bottom:12px">
              <div class="er-stat-item" style="background:#d1fae5">
                <div class="er-stat-val" style="color:#065f46;font-size:16px"><?php echo $fees_overview['total_paid']; ?></div>
                <div class="er-stat-lbl" style="color:#065f46"><?php echo $this->lang->line('paid'); ?></div>
              </div>
              <div class="er-stat-item" style="background:#fef3c7">
                <div class="er-stat-val" style="color:#92400e;font-size:16px"><?php echo $fees_overview['total_partial']; ?></div>
                <div class="er-stat-lbl" style="color:#92400e"><?php echo $this->lang->line('partial'); ?></div>
              </div>
              <div class="er-stat-item" style="background:#fee2e2">
                <div class="er-stat-val" style="color:#991b1b;font-size:16px"><?php echo $fees_overview['total_unpaid']; ?></div>
                <div class="er-stat-lbl" style="color:#991b1b"><?php echo $this->lang->line('unpaid'); ?></div>
              </div>
            </div>

            <!-- Paid Progress -->
            <div class="er-fee-row">
              <span class="label"><?php echo $this->lang->line('paid'); ?></span>
              <span class="val er-text-success"><?php echo round($fees_overview['paid_progress'], 1); ?>%</span>
            </div>
            <div class="er-progress-wrap"><div class="er-progress-fill er-fill-green" style="width:<?php echo $fees_overview['paid_progress']; ?>%"></div></div>

            <!-- Partial Progress -->
            <div class="er-fee-row">
              <span class="label"><?php echo $this->lang->line('partial'); ?></span>
              <span class="val" style="color:var(--er-warning)"><?php echo round($fees_overview['partial_progress'], 1); ?>%</span>
            </div>
            <div class="er-progress-wrap"><div class="er-progress-fill er-fill-orange" style="width:<?php echo $fees_overview['partial_progress']; ?>%"></div></div>

            <!-- Month Collection -->
            <div class="er-fee-row" style="margin-top:12px;padding-top:10px;border-top:1px solid var(--er-border)">
              <span class="label"><?php echo $this->lang->line('this_month'); ?></span>
              <span class="val er-fw700"><?php echo $currency_symbol . number_format($month_collection, 2); ?></span>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- ============================================================
      ROW 3: EXPENSE + ENQUIRY
      ============================================================ -->
    <div class="er-grid-2">

      <!-- EXPENSE OVERVIEW -->
      <?php if ($this->module_lib->hasActive('expense') && $this->rbac->hasPrivilege('expense_donut_graph', 'can_view')): ?>
        <div class="er-card">
          <div class="er-card-header">
            <i class="fa fa-pie-chart" style="color:var(--er-danger)"></i>
            <?php echo $this->lang->line('expense'); ?>
            <a href="<?php echo site_url('admin/expense'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
          </div>
          <div class="er-card-body">
            <div class="er-grid-stat-3" style="margin-bottom:12px">
              <div class="er-stat-item">
                <div class="er-stat-val" style="font-size:15px"><?php echo $currency_symbol . number_format($month_expense, 0); ?></div>
                <div class="er-stat-lbl"><?php echo $this->lang->line('this_month'); ?></div>
              </div>
              <div class="er-stat-item">
                <div class="er-stat-val" style="font-size:15px"><?php echo count($expensegraph); ?></div>
                <div class="er-stat-lbl"><?php echo $this->lang->line('categories'); ?></div>
              </div>
              <div class="er-stat-item" style="background:#fee2e2">
                <div class="er-stat-val" style="color:var(--er-danger);font-size:15px"><?php echo $currency_symbol . number_format($month_expense, 0); ?></div>
                <div class="er-stat-lbl" style="color:var(--er-danger)"><?php echo $this->lang->line('expense'); ?></div>
              </div>
            </div>
            <!-- Expense Categories -->
            <?php if (!empty($expensegraph)): ?>
              <?php foreach (array_slice($expensegraph, 0, 3) as $exp): ?>
                <div class="er-fee-row">
                  <span class="label"><?php echo htmlspecialchars($exp['name']); ?></span>
                  <span class="val"><?php echo $currency_symbol . $exp['total']; ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ENQUIRY OVERVIEW -->
      <?php if ($this->module_lib->hasActive('front_office') && $this->rbac->hasPrivilege('enquiry_overview_widegts', 'can_view')): ?>
        <div class="er-card">
          <div class="er-card-header">
            <i class="fa fa-bullhorn" style="color:var(--er-accent)"></i>
            <?php echo $this->lang->line('enquiry_overview'); ?>
            <a href="<?php echo site_url('admin/enquiry'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
          </div>
          <div class="er-card-body">
            <div class="er-grid-stat" style="margin-bottom:12px">
              <div class="er-stat-item" style="background:#d1fae5">
                <div class="er-stat-val" style="color:#065f46"><?php echo $enquiry_overview['won']; ?></div>
                <div class="er-stat-lbl" style="color:#065f46"><?php echo $this->lang->line('won'); ?></div>
              </div>
              <div class="er-stat-item" style="background:var(--er-primary-light)">
                <div class="er-stat-val" style="color:var(--er-primary)"><?php echo $enquiry_overview['active']; ?></div>
                <div class="er-stat-lbl" style="color:var(--er-primary)"><?php echo $this->lang->line('active'); ?></div>
              </div>
            </div>
            <div class="er-fee-row">
              <span class="label"><?php echo $this->lang->line('won'); ?></span>
              <span class="val er-text-success"><?php echo round($enquiry_overview['won_progress'], 1); ?>%</span>
            </div>
            <div class="er-progress-wrap"><div class="er-progress-fill er-fill-green" style="width:<?php echo $enquiry_overview['won_progress']; ?>%"></div></div>
            <div class="er-fee-row">
              <span class="label"><?php echo $this->lang->line('active'); ?></span>
              <span class="val" style="color:var(--er-primary)"><?php echo round($enquiry_overview['active_progress'], 1); ?>%</span>
            </div>
            <div class="er-progress-wrap"><div class="er-progress-fill er-fill-blue" style="width:<?php echo $enquiry_overview['active_progress']; ?>%"></div></div>
            <div class="er-fee-row">
              <span class="label"><?php echo $this->lang->line('total'); ?></span>
              <span class="val er-fw700"><?php echo $total_enquiry; ?></span>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- ============================================================
      ROW 4: LIBRARY OVERVIEW
      ============================================================ -->
    <?php if ($this->module_lib->hasActive('library') && $this->rbac->hasPrivilege('book_overview_widegts', 'can_view')): ?>
      <div class="er-card">
        <div class="er-card-header">
          <i class="fa fa-book" style="color:#8b5cf6"></i>
          <?php echo $this->lang->line('library_overview'); ?>
          <a href="<?php echo site_url('admin/book'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
        </div>
        <div class="er-card-body">
          <div class="er-grid-stat-3">
            <div class="er-stat-item">
              <div class="er-stat-val"><?php echo $book_overview['total_issued']; ?></div>
              <div class="er-stat-lbl"><?php echo $this->lang->line('issued'); ?></div>
            </div>
            <div class="er-stat-item" style="background:#fee2e2">
              <div class="er-stat-val" style="color:var(--er-danger)"><?php echo $book_overview['dueforreturn']; ?></div>
              <div class="er-stat-lbl" style="color:var(--er-danger)"><?php echo $this->lang->line('due_for_return'); ?></div>
            </div>
            <div class="er-stat-item" style="background:#d1fae5">
              <div class="er-stat-val" style="color:#065f46"><?php echo $book_overview['forreturn']; ?></div>
              <div class="er-stat-lbl" style="color:#065f46"><?php echo $this->lang->line('returned'); ?></div>
            </div>
          </div>
          <div class="er-fee-row er-mt12">
            <span class="label"><?php echo $this->lang->line('issued'); ?> / <?php echo $this->lang->line('total'); ?></span>
            <span class="val"><?php echo $book_overview['total_issued']; ?> / <?php echo $book_overview['total_qty'] ?? 0; ?></span>
          </div>
          <div class="er-progress-wrap"><div class="er-progress-fill er-fill-blue" style="width:<?php echo $book_overview['issued_progress'] ?? 0; ?>%"></div></div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ============================================================
      ROW 5: RECENT FEE PAYMENTS
      ============================================================ -->
    <?php if ($this->rbac->hasPrivilege('fees_collection', 'can_view') && !empty($student_fee_history)): ?>
      <div class="er-card">
        <div class="er-card-header">
          <i class="fa fa-history" style="color:var(--er-success)"></i>
          <?php echo $this->lang->line('recent_fee_payments'); ?>
          <a href="<?php echo site_url('studentfee'); ?>" class="er-card-link"><?php echo $this->lang->line('view_all'); ?> →</a>
        </div>
        <div class="er-card-body-p0">
          <div style="overflow-x:auto">
            <table class="er-table">
              <thead>
                <tr>
                  <th><?php echo $this->lang->line('name'); ?></th>
                  <th><?php echo $this->lang->line('amount'); ?></th>
                  <th><?php echo $this->lang->line('payment_mode'); ?></th>
                  <th><?php echo $this->lang->line('date'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($student_fee_history, 0, 8) as $fee): ?>
                  <tr>
                    <td>
                      <strong><?php echo $this->customlib->getFullName($fee->firstname, $fee->middlename ?? '', $fee->lastname, $sch_setting->middlename, $sch_setting->lastname); ?></strong>
                      <br><span class="er-fs11" style="color:var(--er-muted)"><?php echo $fee->class ?? ''; ?> <?php echo !empty($fee->section) ? '(' . $fee->section . ')' : ''; ?></span>
                    </td>
                    <td class="er-text-success er-fw700"><?php echo $currency_symbol . amountFormat($fee->amount); ?></td>
                    <td>
                      <?php
                        $mode = strtolower($fee->payment_mode ?? 'cash');
                        $mode_class = in_array($mode, ['online','razorpay','paytm','phonepe']) ? 'online' : ($mode == 'cheque' ? 'cheque' : 'cash');
                      ?>
                      <span class="er-pay-mode <?php echo $mode_class; ?>"><?php echo $this->lang->line($mode); ?></span>
                    </td>
                    <td style="color:var(--er-muted);font-size:12px">
                      <?php echo date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($fee->date)); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ============================================================
      ROW 6: CHARTS (monthly collection & expense)
      ============================================================ -->
    <?php if (($this->module_lib->hasActive('fees_collection') || $this->module_lib->hasActive('expense')) && $this->rbac->hasPrivilege('fees_collection_and_expense_monthly_chart', 'can_view')): ?>
      <div class="er-card">
        <div class="er-card-header">
          <i class="fa fa-bar-chart" style="color:var(--er-primary)"></i>
          <?php echo $this->lang->line('fees_collection_expenses_for'); ?> <?php echo $this->lang->line(strtolower(date('F'))) . ' ' . date('Y'); ?>
        </div>
        <div class="er-card-body">
          <canvas id="barChart" height="120"></canvas>
        </div>
      </div>
    <?php endif; ?>

    <?php if (($this->module_lib->hasActive('fees_collection') || $this->module_lib->hasActive('expense')) && $this->rbac->hasPrivilege('fees_collection_and_expense_yearly_chart', 'can_view')): ?>
      <div class="er-card">
        <div class="er-card-header">
          <i class="fa fa-line-chart" style="color:var(--er-primary)"></i>
          <?php echo $this->lang->line('fees_collection_expenses_for_session'); ?> <?php echo $session_name; ?>
        </div>
        <div class="er-card-body">
          <canvas id="lineChart" height="120"></canvas>
        </div>
      </div>
    <?php endif; ?>

  </section><!-- end content -->
</div><!-- end content-wrapper -->

<!-- ============================================================
  BOTTOM NAVIGATION (mobile only)
  Add this once to layout/footer.php before </body>
  ============================================================ -->
<nav class="er-bottom-nav">
  <a href="<?php echo site_url('admin/admin/dashboard'); ?>" class="er-bnav-item active">
    <i class="fa fa-home"></i>Home
  </a>
  <?php if ($this->rbac->hasPrivilege('student', 'can_view')): ?>
    <a href="<?php echo site_url('student'); ?>" class="er-bnav-item">
      <i class="fa fa-users"></i>Students
    </a>
  <?php endif; ?>
  <?php if ($this->rbac->hasPrivilege('fees_collection', 'can_view')): ?>
    <a href="<?php echo site_url('studentfee'); ?>" class="er-bnav-item">
      <i class="fa fa-money"></i>Fees
      <?php if (!empty($fees_overview['total_unpaid']) && $fees_overview['total_unpaid'] > 0): ?>
        <span class="er-bnav-dot"></span>
      <?php endif; ?>
    </a>
  <?php endif; ?>
  <a href="<?php echo site_url('admin/calendar/events'); ?>" class="er-bnav-item">
    <i class="fa fa-calendar"></i>Calendar
  </a>
  <a href="<?php echo base_url() . 'admin/staff/profile/' . $this->customlib->getStaffID(); ?>" class="er-bnav-item">
    <i class="fa fa-user"></i>Profile
  </a>
</nav>
