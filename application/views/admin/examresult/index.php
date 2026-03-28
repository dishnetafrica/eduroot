<div class="content-wrapper" style="min-height: 946px;">
    <section class="content-header">
        <h1>
            <i class="fa fa-map-o"></i> <?php echo $this->lang->line('examinations'); ?> <small><?php echo $this->lang->line('student_fee1'); ?></small>
        </h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('select_criteria'); ?></h3>
                    </div>
                    <div class="box-body">
                        <form role="form" action="<?php echo site_url('admin/examresult') ?>" method="post" >
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-sm-6 col-lg-3 col-md-4 col20">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('exam_group'); ?></label><small class="req"> *</small>
                                        <select autofocus="" id="exam_group_id" name="exam_group_id" class="form-control select2" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php
foreach ($examgrouplist as $ex_group_key => $ex_group_value) {
    ?>
                                                <option value="<?php echo $ex_group_value->id ?>" <?php
if (set_value('exam_group_id') == $ex_group_value->id) {
        echo "selected=selected";
    }
    ?>><?php echo $ex_group_value->name; ?></option>
                                                        <?php
}
?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('exam_group_id'); ?></span>
                                    </div>
                                </div>
                                <!--./col-md-3-->
                                <div class="col-sm-6 col-lg-3 col-md-4 col20">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('exam') ?></label><small class="req"> *</small>
                                        <select  id="exam_id" name="exam_id" class="form-control select2" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('exam_id'); ?></span>
                                    </div>
                                </div>
                                <!--./col-md-3-->
                                <div class="col-sm-6 col-lg-3 col-md-4 col20">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('session'); ?></label><small class="req"> *</small>
                                        <select  id="session_id" name="session_id" class="form-control" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php
foreach ($sessionlist as $session) {
    ?>
                                                <option value="<?php echo $session['id'] ?>" <?php
if (set_value('session_id') == $session['id']) {
        echo "selected=selected";
    }
    ?>><?php echo $session['session'] ?></option>
                                                        <?php
}
?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('session_id'); ?></span>
                                    </div>
                                </div>
                                <!--./col-md-3-->
                                <div class="col-sm-6 col-lg-3 col-md-6 col20">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('class'); ?></label><small class="req"> *</small>
                                        <select id="class_id" name="class_id" class="form-control" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php
foreach ($classlist as $class) {
    ?>
                                                <option value="<?php echo $class['id'] ?>" <?php
if (set_value('class_id') == $class['id']) {
        echo "selected=selected";
    }
    ?>><?php echo $class['class'] ?></option>
                                                        <?php
}
?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('class_id'); ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3 col-md-6 col20">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('section'); ?></label><small class="req"> *</small>
                                        <select  id="section_id" name="section_id" class="form-control" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('section_id'); ?></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button type="submit" name="search" value="search_filter" class="btn btn-primary pull-right btn-sm checkbox-toggle"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php
if (isset($studentList)) {
    ?>
                        <form method="post" action="<?php echo base_url('admin/examresult/printmarksheet') ?>" id="printMarksheet">
                            <input type="hidden" name="marksheet_template" value="<?php echo $marksheet_template; ?>">
                            <div class="" >
                                <div class="box-header ptbnull"></div>
                                <div class="box-header ptbnull">
                                    <h3 class="box-title titlefix">
                                        <i class="fa fa-users"></i> <?php echo $this->lang->line('exam_result'); ?>
                                    </h3>
                                    <!-- ===== WHATSAPP BUTTONS ===== -->
                                    <div class="box-tools pull-right">
                                        <button type="button" class="btn btn-default btn-sm"
                                                onclick="openWATemplateModal()"
                                                title="Edit WhatsApp Message Template">
                                            <i class="fa fa-cog"></i> WA Template
                                        </button>
                                        &nbsp;
                                        <button type="button" class="btn btn-success btn-sm"
                                                onclick="openWhatsAppModal()">
                                            <i class="fa fa-whatsapp"></i> Send to Parents
                                        </button>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <input type="hidden" name="post_exam_id" value="<?php echo $exam_id; ?>">
                                    <input type="hidden" name="post_exam_group_id" value="<?php echo $exam_group_id; ?>">
                                    <div class="tab-pane active table-responsive no-padding" id="tab_1">
                                        <div class="download_label"> <?php echo $this->lang->line('exam_result'); ?></div>
                                       
                                        <table class="table table-striped table-bordered table-hover dt_table" cellspacing="0" width="100%"  data-export-title="<?php echo $this->lang->line('exam_result');?>">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $this->lang->line('admission_no'); ?></th>
                                                    <?php if ($sch_setting->roll_no) { ?>
                                                    <th><?php echo $this->lang->line('roll_number'); ?></th>
                                                    <?php } ?>
                                                    <th><?php echo $this->lang->line('student_name'); ?></th>
                                                    <?php
if (!empty($subjectList)) {
        foreach ($subjectList as $subject_key => $subject_value) {
            ?>
                                                            <th>
                                                                <?php
echo $subject_value->subject_name;
            echo "<br/>";
            if ($exam_details->exam_group_type == "average_passing") {
                echo "(" . $subject_value->max_marks . " - " . $subject_value->subject_code . ")";
            } else {
                echo "(" . $subject_value->min_marks . "/" . $subject_value->max_marks . " - " . $subject_value->subject_code . ")";
            }

            if ($exam_details->exam_group_type == "gpa") {
                ?>
                                                                    <br/>
                                                                    (<?php echo $this->lang->line('grade_point'); ?>) * (<?php echo $this->lang->line('credit_hours'); ?>)
                                                                    <?php
}
            ?>
                                                            </th>
                                                            <?php
}

        if ($exam_details->exam_group_type == "school_grade_system" || $exam_details->exam_group_type == "basic_system" || $exam_details->exam_group_type == "average_passing" || $exam_details->exam_group_type == "coll_grade_system") {
            ?>
                                                            <th><?php echo $this->lang->line('grand_total'); ?></th>
                                                            <th><?php echo $this->lang->line('percent') ?> (%)</th>
                                                            <th><?php echo $this->lang->line('rank') ?></th>
                                                            <?php
if ($exam_details->exam_group_type != "gpa") {
                ?>
                                                                <th><?php echo $this->lang->line('result') ?></th>
                                                                <?php
}
            ?>
                                                            <?php
} elseif ($exam_details->exam_group_type == "gpa") {

            ?>
                                                            <th><?php echo $this->lang->line('rank') ?></th>
                                                            <th><?php echo $this->lang->line('result') ?></th>
                                                            <?php
}
    }
    ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
if (empty($studentList)) {
        ?>
                                                    <?php
} else {
        $count = 1;  
        foreach ($studentList as $student_key => $student_value) {           

            $result_status     = 1;
            $no_subject_result = 0;
            ?>
                                                        <tr>
                                                            <td><?php echo $student_value->admission_no; ?></td>
                                                            <?php if ($sch_setting->roll_no) { ?>
                                                            <td><?php echo ($student_value->roll_no != 0) ? $student_value->roll_no : "-"; ?> </td>
                                                        <?php } ?>
                                                            <td>
                                                                <a href="<?php echo base_url(); ?>student/view/<?php echo $student_value->student_id; ?>"><?php echo $this->customlib->getFullName($student_value->firstname, $student_value->middlename, $student_value->lastname, $sch_setting->middlename, $sch_setting->lastname); ?>
                                                                </a>
                                                            </td>
                                                            <?php
if (!empty($subjectList)) {
   
                $total_marks         = 0;
                $get_marks           = 0;
                $total_percentage    = 0;
                $total_credit_hour   = 0;
                $total_quality_point = 0;
                foreach ($subjectList as $subject_key => $subject_value) {
                    $subject_status = 1;
                    $total_marks    = $total_marks + $subject_value->max_marks;
                    ?>
                    <td>
                    <?php
                    $result = getSubjectMarks($student_value->subject_results, $subject_value->subject_id);
                    if ($result) {
                        $no_subject_result = 1;
                        if ($exam_details->exam_group_type == "gpa") {
                            $get_marks           = $get_marks + $result->get_marks;
                            $subject_credit_hour = $subject_value->credit_hours;
                            $total_credit_hour   = $total_credit_hour + $subject_value->credit_hours;
                            $percentage_grade = ($result->get_marks * 100) / $result->max_marks;
                            $point            = findGradePoints($exam_grades, $percentage_grade);                        
                            $total_quality_point = $total_quality_point + ($point * $subject_credit_hour);
                            echo $point . " X " . $subject_credit_hour . " = " . number_format($point * $subject_credit_hour, 2, '.', '');
                                if ($result->attendence == "absent") { ?>
                                    <p class="text">
                                        <?php echo $this->lang->line($result->attendence); ?>
                                    </p>
                                <?php  }  ?>
                                    <p class="text"><?php echo $result->note; ?></p>
                  <?php } else {

                            $get_marks = $get_marks + $result->get_marks;
                            if ($result->get_marks < $subject_value->min_marks) {
                                $result_status  = 0;
                                $subject_status = 0;
                            }
                            echo $result->get_marks;
                            if ($exam_details->exam_group_type == "school_grade_system" || $exam_details->exam_group_type == "coll_grade_system") {
                                $percentage_grade = ($result->get_marks * 100) / $subject_value->max_marks;
                                echo " (" . get_ExamGrade($exam_grades, $percentage_grade) . ")";
                            }
                            if ($exam_details->exam_group_type == "basic_system") {
                                echo ($subject_status == 0) ? " (F)" : "";
                            }
                            if ($result->attendence == "absent") { ?>
                                <p class="text">
                                    <?php echo $this->lang->line($result->attendence); ?>
                                </p>
                            <?php }   ?>
                                <p class="text"><?php echo $result->note; ?></p>
                            <?php }
                        } else { 


                         } ?>
                        </td>
                        <?php }

                    if($exam_details->exam_group_type == "school_grade_system" || $exam_details->exam_group_type == "basic_system" || $exam_details->exam_group_type == "average_passing" || $exam_details->exam_group_type == "coll_grade_system") {

                     ?>
                        <td><?php echo number_format($get_marks, 2, '.', '') . "/" . number_format($total_marks, 2, '.', ''); ?></td>
                        <td>
                        <?php
                        $total_percentage = ($get_marks * 100) / $total_marks;
                        echo number_format($total_percentage, 2, '.', '');
                        echo " (" . get_ExamGrade($exam_grades, $total_percentage) . ")";
                        ?>
                        </td>
                       <?php 

                   } 

                        ?>
                         <td><?php echo $student_value->rank; ?></td>

                       <?php 

                               if ($exam_details->exam_group_type == "gpa") {  ?>
                        <td><?php
                      
                        if ($total_credit_hour > 0) {
                            $percentage_grade = ($get_marks * 100) / $total_marks;
                            $exam_qulity_point = number_format($total_quality_point / $total_credit_hour, 2, '.', '');
                            echo $exam_qulity_point . " [" . get_ExamGrade($exam_grades, $percentage_grade) . "]";
                        } else {
                            echo "--";
                        }
                        ?></td>
                    <?php
                    } elseif ($exam_details->exam_group_type == "school_grade_system" || $exam_details->exam_group_type == "basic_system" || $exam_details->exam_group_type == "average_passing" || $exam_details->exam_group_type == "coll_grade_system") {
                        echo "<td>";
                    if($no_subject_result) {
                        if ($exam_details->exam_group_type == "average_passing") {
                            $result_status = ($exam_details->passing_percentage > $total_percentage) ? 0 : 1;
                        }
                        if ($exam_details->exam_group_type == "basic_system"  ||  $exam_details->exam_group_type == "average_passing") {
                            if ($result_status) { ?>
                                <label class="label label-success" ><?php echo $this->lang->line('pass'); ?></label>
                    <?php   } else {    ?>
                                <label class="label label-danger"><?php echo $this->lang->line('fail'); ?></label>
                    <?php
                        }
                    }
                }
                    echo "</td>";
                }
                    
                    }  
                  ?>
                </tr>
                <?php
                    $count++;
                }
            }
    ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php
}
?>
            </div>
        </div>
    </section>
</div>

<?php
function getSubjectMarks($subject_results, $subject_id)
{
    if (!empty($subject_results)) {
        foreach ($subject_results as $subject_result_key => $subject_result_value) {
            if ($subject_id == $subject_result_value->subject_id) {
                return $subject_result_value;
            }
        }
    }
    return false;
}

function get_ExamGrade($exam_grades, $percentage)
{
    if (!empty($exam_grades)) {
        foreach ($exam_grades as $exam_grade_key => $exam_grade_value) {
            if ($exam_grade_value->mark_from >= $percentage && $exam_grade_value->mark_upto <= $percentage) {
                return $exam_grade_value->name;
            }
        }
    }
    return "-";
}

function findGradePoints($exam_grades, $percentage)
{
    if (!empty($exam_grades)) {
        foreach ($exam_grades as $exam_grade_key => $exam_grade_value) {
            if ($exam_grade_value->mark_from >= $percentage && $exam_grade_value->mark_upto <= $percentage) {
                return $exam_grade_value->point;
            }
        }
    }
    return 0;
}
?>

<!-- ================================================================
     MODAL 1: WhatsApp Template Editor
     ================================================================ -->
<div class="modal fade" id="waTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#25D366;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-whatsapp fa-lg"></i>&nbsp;
                    Edit WhatsApp Message Template — Exam Result
                </h4>
            </div>
            <div class="modal-body">

                <!-- Placeholders -->
                <div class="alert alert-info">
                    <strong><i class="fa fa-info-circle"></i> Click to insert placeholder at cursor:</strong>
                    <div style="margin-top:8px;line-height:2.2;">
                        <?php
                        $phs = [
                            '{{student_name}}'  => 'Student Name',
                            '{{exam_roll_no}}'  => 'Roll No',
                            '{{admission_no}}'  => 'Admission No',
                            '{{exam}}'          => 'Exam Name',
                            '{{class}}'         => 'Class',
                            '{{subject_marks}}' => 'All Subjects+Marks',
                            '{{grand_total}}'   => 'Grand Total',
                            '{{percentage}}'    => 'Percentage',
                            '{{father_name}}'   => 'Father Name',
                            '{{guardian_name}}' => 'Guardian Name',
                            '{{exam_date}}'     => 'Exam Date',
                        ];
                        foreach ($phs as $ph => $label): ?>
                            <span onclick="waInsertPlaceholder('<?php echo $ph; ?>')"
                                  style="display:inline-block;background:#e8f5e9;border:1px solid #4CAF50;
                                         color:#1b5e20;padding:2px 8px;border-radius:12px;font-size:12px;
                                         margin:2px;cursor:pointer;font-family:monospace;">
                                <?php echo $ph; ?> <small style="color:#555;">(<?php echo $label; ?>)</small>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted" style="display:block;margin-top:6px;">
                        ⚠️ <strong>{{subject_marks}}</strong> auto-builds full subject list with marks. &nbsp;|&nbsp;
                        WhatsApp: *bold* &nbsp; _italic_ &nbsp; ~strikethrough~
                    </small>
                </div>

                <!-- Quick Templates -->
                <div class="form-group">
                    <label><strong>Quick Load Default Template:</strong></label>&nbsp;
                    <button type="button" class="btn btn-xs btn-default" onclick="waLoadTemplate('gujarati')">🇮🇳 Gujarati</button>
                    <button type="button" class="btn btn-xs btn-default" onclick="waLoadTemplate('english')">🇬🇧 English</button>
                    <button type="button" class="btn btn-xs btn-default" onclick="waLoadTemplate('hindi')">🇮🇳 Hindi</button>
                </div>

                <!-- Textarea -->
                <div class="form-group">
                    <label><strong>Message Template:</strong></label>
                    <textarea id="waTemplateText" class="form-control" rows="10"
                              style="font-family:monospace;font-size:13px;resize:vertical;"
                              placeholder="Write your WhatsApp message here..."></textarea>
                </div>

                <!-- Live Preview -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong><i class="fa fa-mobile"></i> Live Preview (Sample Data)</strong>
                        <button type="button" class="btn btn-xs btn-default pull-right" onclick="waRefreshPreview()">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                    <div class="panel-body" style="background:#ECE5DD;padding:15px;">
                        <div style="background:#fff;border-radius:10px;padding:14px;max-width:340px;
                                    box-shadow:0 2px 5px rgba(0,0,0,0.2);margin:0 auto;">
                            <pre id="waPreviewText"
                                 style="font-size:13px;white-space:pre-wrap;margin:0;
                                        font-family:'Helvetica Neue',Arial,sans-serif;
                                        border:none;background:transparent;padding:0;"></pre>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="waSaveTemplate()">
                    <i class="fa fa-save"></i> Save Template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================
     MODAL 2: Send WhatsApp Messages to Parents
     ================================================================ -->
<div class="modal fade" id="whatsappModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#128C7E;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:1;"><span>&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-whatsapp fa-lg"></i>&nbsp;
                    Send Exam Results to Parents via WhatsApp
                </h4>
            </div>
            <div class="modal-body">

                <!-- Alerts -->
                <div id="waSuccess" class="alert alert-success" style="display:none;">
                    <i class="fa fa-check-circle fa-lg"></i>
                    <strong> Success!</strong> <span id="waSuccessMsg"></span>
                </div>
                <div id="waError" class="alert alert-danger" style="display:none;">
                    <i class="fa fa-exclamation-circle fa-lg"></i>
                    <strong> Error:</strong> <span id="waErrorMsg"></span>
                </div>

                <!-- Template banner -->
                <div class="alert alert-warning" style="padding:8px 12px;margin-bottom:10px;">
                    <i class="fa fa-envelope-o"></i>
                    <strong> Using Template:</strong>
                    <em id="waTemplateBanner" style="font-size:12px;">Loading...</em>
                    <a href="#" onclick="$('#whatsappModal').modal('hide');openWATemplateModal();return false;"
                       class="pull-right btn btn-xs btn-default">
                        <i class="fa fa-edit"></i> Edit Template
                    </a>
                </div>

                <!-- Exam Date Picker -->
                <div class="row" style="margin-bottom:12px;">
                    <div class="col-sm-4">
                        <label><strong>&#128197; Exam Date</strong></label>
                        <input type="date" id="waExamDate" class="form-control"
                               value="<?php echo date('Y-m-d'); ?>"
                               onchange="waUpdatePreview()">
                        <small class="text-muted">Used as {{exam_date}} in WA message</small>
                    </div>
                </div>
                <!-- Select All row -->
                <div class="row" style="margin-bottom:8px;">
                    <div class="col-sm-6">
                        <label style="margin:0;font-weight:bold;">
                            <input type="checkbox" id="waSelectAll" onchange="waToggleAll(this)">
                            &nbsp;Select / Deselect All
                        </label>
                    </div>
                    <div class="col-sm-6 text-right">
                        <span id="waSelectedBadge" class="badge"
                              style="background:#25D366;font-size:13px;padding:5px 10px;">
                            0 selected
                        </span>
                    </div>
                </div>

                <!-- Students table -->
                <div class="table-responsive" style="max-height:380px;overflow-y:auto;">
                    <table class="table table-bordered table-condensed table-hover">
                        <thead style="background:#f5f5f5;">
                            <tr>
                                <th width="35">
                                    <input type="checkbox" id="waSelectAllTop" onchange="waToggleAll(this)">
                                </th>
                                <th>Student</th>
                                <th>Parent Mobile</th>
                                <?php if (isset($subjectList) && !empty($subjectList)):
                                    foreach ($subjectList as $sub): ?>
                                    <th><?php echo $sub->subject_name; ?><br>
                                        <small class="text-muted">/<?php echo $sub->max_marks; ?></small>
                                    </th>
                                <?php endforeach; endif; ?>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($studentList) && !empty($studentList)):
                                foreach ($studentList as $wa_idx => $sv):
                                    $has_phone = !empty($sv->guardian_phone);
                                    $roll = isset($sv->exam_roll_no) && $sv->exam_roll_no ? $sv->exam_roll_no : (isset($sv->student_roll_no) ? $sv->student_roll_no : $sv->roll_no);

                                    // Build marks array for JS
                                    $marks_arr = [];
                                    if (isset($subjectList) && !empty($subjectList)) {
                                        foreach ($subjectList as $sub) {
                                            $got = '';
                                            $res = getSubjectMarks($sv->subject_results, $sub->subject_id);
                                            if ($res) { $got = $res->get_marks; }
                                            $marks_arr[] = [
                                                'subject' => $sub->subject_name,
                                                'marks'   => ($got !== '') ? $got : 0,
                                                'max'     => $sub->max_marks
                                            ];
                                        }
                                    }
                            ?>
                            <tr id="waRow<?php echo $wa_idx; ?>"
                                style="<?php echo !$has_phone ? 'background:#fff5f5;' : ''; ?>">
                                <td>
                                    <input type="checkbox" class="waChk"
                                           <?php echo !$has_phone ? 'disabled title="No parent mobile number"' : ''; ?>
                                           data-idx="<?php echo $wa_idx; ?>"
                                           data-name="<?php echo htmlspecialchars($this->customlib->getFullName($sv->firstname, $sv->middlename, $sv->lastname, $sch_setting->middlename, $sch_setting->lastname)); ?>"
                                           data-phone="<?php echo htmlspecialchars($sv->guardian_phone ?? ''); ?>"
                                           data-roll="<?php echo htmlspecialchars($roll); ?>"
                                           data-adm="<?php echo htmlspecialchars($sv->admission_no); ?>"
                                           data-father="<?php echo htmlspecialchars($sv->father_name ?? ''); ?>"
                                           data-guardian="<?php echo htmlspecialchars($sv->guardian_name ?? ''); ?>"
                                           data-marks='<?php echo htmlspecialchars(json_encode($marks_arr)); ?>'
                                           onchange="waUpdateCount()">
                                </td>
                                <td>
                                    <strong><?php echo $this->customlib->getFullName($sv->firstname, $sv->middlename, $sv->lastname, $sch_setting->middlename, $sch_setting->lastname); ?></strong><br>
                                    <small class="text-muted">
                                        Roll: <?php echo $roll; ?> | Adm: <?php echo $sv->admission_no; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($has_phone): ?>
                                        <span style="color:#25D366;">
                                            <i class="fa fa-whatsapp"></i> <?php echo $sv->guardian_phone; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fa fa-exclamation-triangle"></i> Not available
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if (isset($subjectList) && !empty($subjectList)):
                                    foreach ($subjectList as $sub):
                                        $res = getSubjectMarks($sv->subject_results, $sub->subject_id);
                                        $got = $res ? $res->get_marks : '';
                                ?>
                                    <td class="text-center">
                                        <?php echo ($got !== '') ? '<strong>'.$got.'</strong>' : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                <?php endforeach; endif; ?>
                                <td id="waStatus<?php echo $wa_idx; ?>">
                                    <span class="label label-default">Pending</span>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Message Preview -->
                <div class="panel panel-default" style="margin-top:12px;">
                    <div class="panel-heading">
                        <strong><i class="fa fa-mobile"></i> Message Preview</strong>
                        <small class="text-muted pull-right">First selected student's preview</small>
                    </div>
                    <div class="panel-body" style="background:#ECE5DD;padding:12px;">
                        <div style="background:#fff;border-radius:10px;padding:12px;max-width:320px;
                                    box-shadow:0 1px 3px rgba(0,0,0,.2);margin:0 auto;">
                            <pre id="waSendPreview"
                                 style="font-size:12px;white-space:pre-wrap;margin:0;
                                        font-family:inherit;border:none;background:transparent;padding:0;">
Loading...</pre>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>
                <button type="button" id="waSendBtn" class="btn btn-success btn-lg"
                        onclick="waSendMessages()">
                    <i class="fa fa-whatsapp"></i> Send Messages
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    // ============================================================
    // EXISTING JS (unchanged)
    // ============================================================
    $(document).ready(function(){
        displayDataTable('dt_table',
        [
            {
                targets: [0,-2,1,-5],
                className: 'dt-body-left dt-head-left'
            },
            {
                targets: [-1],
                orderable: false,
            }
        ]);
    });

    $(document).ready(function () {
        $('.select2').select2();
    });

    $(document).ready(function () {
        $.extend($.fn.dataTable.defaults, {
            searching: true,
            ordering:  true,
            paging:    false,
            retrieve:  true,
            destroy:   true,
            info:      false
        });
    });

    var date_format   = '<?php echo $result = strtr($this->customlib->getSchoolDateFormat(), ['d' => 'dd', 'm' => 'mm', 'Y' => 'yyyy']) ?>';
    var class_id      = '<?php echo set_value('class_id') ?>';
    var section_id    = '<?php echo set_value('section_id') ?>';
    var session_id    = '<?php echo set_value('session_id') ?>';
    var exam_group_id = '<?php echo set_value('exam_group_id') ?>';
    var exam_id       = '<?php echo set_value('exam_id') ?>';

    getSectionByClass(class_id, section_id);
    getExamByExamgroup(exam_group_id, exam_id);

    $(document).on('change', '#exam_group_id', function (e) {
        $('#exam_id').html("");
        var exam_group_id = $(this).val();
        getExamByExamgroup(exam_group_id, 0);
    });

    $(document).on('change', '#class_id', function (e) {
        $('#section_id').html("");
        var class_id = $(this).val();
        getSectionByClass(class_id, 0);
    });

    function getSectionByClass(class_id, section_id) {
        if (class_id !== "") {
            $('#section_id').html("");
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            $.ajax({
                type: "GET",
                url: baseurl + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                beforeSend: function () { $('#section_id').addClass('dropdownloading'); },
                success: function (data) {
                    $.each(data, function (i, obj) {
                        var sel = (section_id === obj.section_id) ? "selected" : "";
                        div_data += "<option value=" + obj.section_id + " " + sel + ">" + obj.section + "</option>";
                    });
                    $('#section_id').append(div_data);
                },
                complete: function () { $('#section_id').removeClass('dropdownloading'); }
            });
        }
    }

    function getExamByExamgroup(exam_group_id, exam_id) {
        if (exam_group_id !== "") {
            $('#exam_id').html("");
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            $.ajax({
                type: "POST",
                url: baseurl + "admin/examgroup/getExamByExamgroup",
                data: {'exam_group_id': exam_group_id},
                dataType: "json",
                beforeSend: function () { $('#exam_id').addClass('dropdownloading'); },
                success: function (data) {
                    $.each(data, function (i, obj) {
                        var sel = (exam_id === obj.id) ? "selected" : "";
                        div_data += "<option value=" + obj.id + " " + sel + ">" + obj.exam + "</option>";
                    });
                    $('#exam_id').append(div_data);
                    $('#exam_id').trigger('change');
                },
                complete: function () { $('#exam_id').removeClass('dropdownloading'); }
            });
        }
    }

    // ============================================================
    // WHATSAPP VARIABLES
    // ============================================================
    var waExamName       = '<?php echo addslashes(isset($exam_details) ? $exam_details->exam : ''); ?>';
    var waClassName      = '<?php echo addslashes(isset($studentList[0]) ? ($studentList[0]->class ?? '') : ''); ?>';
    var waCsrfName       = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var waCsrfToken      = '<?php echo $this->security->get_csrf_hash(); ?>';
    var waCurrentTpl     = '';

    var waTemplates = {
        gujarati: "📊 *પરીક્ષા પરિણામ*\n─────────────────\n👤 *વિદ્યાર્થીનું નામ:* {{student_name}}\n🎓 *રોલ નંબર:* {{exam_roll_no}}\n📝 *પરીક્ષા:* {{exam}}\n📅 *તારીખ:* {{exam_date}}\n🏫 *વર્ગ:* {{class}}\n─────────────────\n📚 *વિષય અને ગુણ:*\n{{subject_marks}}\n─────────────────\n📊 *કુલ ગુણ:* {{grand_total}}\n📈 *ટકાવારી:* {{percentage}}\n─────────────────\n_ભાગ્યોદય એકેડેમી_",
        english:  "📊 *Exam Result*\n─────────────────\n👤 *Student:* {{student_name}}\n🎓 *Roll No:* {{exam_roll_no}}\n📝 *Exam:* {{exam}}\n📅 *Date:* {{exam_date}}\n🏫 *Class:* {{class}}\n─────────────────\n📚 *Subject Marks:*\n{{subject_marks}}\n─────────────────\n📊 *Total:* {{grand_total}}\n📈 *Percentage:* {{percentage}}\n─────────────────\n_Bhagyoday Academy_",
        hindi:    "📊 *परीक्षा परिणाम*\n─────────────────\n👤 *छात्र:* {{student_name}}\n🎓 *रोल नं:* {{exam_roll_no}}\n📝 *परीक्षा:* {{exam}}\n📅 *तारीख:* {{exam_date}}\n🏫 *कक्षा:* {{class}}\n─────────────────\n📚 *विषय और अंक:*\n{{subject_marks}}\n─────────────────\n📊 *कुल:* {{grand_total}}\n📈 *प्रतिशत:* {{percentage}}\n─────────────────\n_भाग्योदय अकादमी_"
    };

    // ============================================================
    // BUILD MESSAGE
    // ============================================================
    function waBuildMsg(tpl, data) {
        var subMarks = '', total = 0, maxTotal = 0;
        if (data.marks && data.marks.length) {
            $.each(data.marks, function(i, m) {
                subMarks += '  • ' + m.subject + ': ' + m.marks + '/' + m.max + '\n';
                total    += parseFloat(m.marks) || 0;
                maxTotal += parseFloat(m.max)   || 0;
            });
        }
        var pct = maxTotal > 0 ? ((total / maxTotal) * 100).toFixed(2) + '%' : '0%';
        return tpl
            .replace('{{student_name}}',  data.name     || '')
            .replace('{{exam_roll_no}}',  data.roll     || '')
            .replace('{{roll_no}}',       data.roll     || '')
            .replace('{{admission_no}}',  data.adm      || '')
            .replace('{{exam}}',          waExamName    || '')
            .replace('{{class}}',         waClassName   || '')
            .replace('{{subject_marks}}', subMarks.replace(/\n$/, ''))
            .replace('{{grand_total}}',   total + '/' + maxTotal)
            .replace('{{percentage}}',    pct)
            .replace('{{father_name}}',   data.father   || '')
            .replace('{{guardian_name}}', data.guardian || '')
            .replace('{{exam_date}}',     data.exam_date  || '');
    }

    // ============================================================
    // TEMPLATE MODAL
    // ============================================================
    function openWATemplateModal() {
        $.post(baseurl + 'admin/examresult/getWhatsAppTemplate',
            {type: 'exam_result', [waCsrfName]: waCsrfToken},
            function(r) {
                $('#waTemplateText').val(r.template && r.template.trim() ? r.template : waTemplates.gujarati);
                waRefreshPreview();
            }, 'json'
        ).fail(function() {
            $('#waTemplateText').val(waTemplates.gujarati);
            waRefreshPreview();
        });
        $('#waTemplateModal').modal('show');
    }

    function waLoadTemplate(lang) {
        $('#waTemplateText').val(waTemplates[lang]);
        waRefreshPreview();
    }

    function waInsertPlaceholder(ph) {
        var el = document.getElementById('waTemplateText');
        var s = el.selectionStart, e = el.selectionEnd;
        el.value = el.value.substring(0, s) + ph + el.value.substring(e);
        el.selectionStart = el.selectionEnd = s + ph.length;
        el.focus();
        waRefreshPreview();
    }

    function waRefreshPreview() {
        var tpl = $('#waTemplateText').val();
        var msg = waBuildMsg(tpl, {
            name: 'Jay Ranpariya', roll: '1', adm: '1001',
            father: 'Nilesh Ranpariya', guardian: 'Nilesh Ranpariya',
            marks: [
                {subject:'English', marks:22, max:25},
                {subject:'Maths',   marks:20, max:25},
                {subject:'Hindi',   marks:18, max:25},
                {subject:'EVS',     marks:21, max:25}
            ]
        });
        $('#waPreviewText').text(msg);
    }

    function waSaveTemplate() {
        var tpl = $.trim($('#waTemplateText').val());
        if (!tpl) { alert('Please enter a template!'); return; }
        $.post(baseurl + 'admin/examresult/saveWhatsAppTemplate',
            {type: 'exam_result', template: tpl, [waCsrfName]: waCsrfToken},
            function(r) {
                if (r.status == 1) {
                    waCurrentTpl = tpl;
                    successMsg('✅ WhatsApp template saved successfully!');
                    $('#waTemplateModal').modal('hide');
                } else {
                    errorMsg(r.message || 'Error saving template');
                }
            }, 'json'
        );
    }

    $('#waTemplateText').on('input', waRefreshPreview);

    // ============================================================
    // SEND MODAL
    // ============================================================
    function openWhatsAppModal() {
        <?php if (!isset($studentList) || empty($studentList)): ?>
            alert('Please search and load exam results first!');
            return;
        <?php endif; ?>

        $('#waSuccess').hide();
        $('#waError').hide();

        // Load current template
        $.post(baseurl + 'admin/examresult/getWhatsAppTemplate',
            {type: 'exam_result', [waCsrfName]: waCsrfToken},
            function(r) {
                waCurrentTpl = (r.template && r.template.trim()) ? r.template : waTemplates.gujarati;
                $('#waTemplateBanner').text(waCurrentTpl.substring(0, 70) + '...');
                waUpdatePreview();
            }, 'json'
        ).fail(function() {
            waCurrentTpl = waTemplates.gujarati;
            waUpdatePreview();
        });

        // Select all valid by default
        $('.waChk:not(:disabled)').prop('checked', true);
        $('#waSelectAll, #waSelectAllTop').prop('checked', true);
        waUpdateCount();

        // Reset status labels
        $('[id^=waStatus]').html('<span class="label label-default">Pending</span>');

        // Reset send button
        $('#waSendBtn').prop('disabled', false)
            .removeClass('btn-primary').addClass('btn-success')
            .html('<i class="fa fa-whatsapp"></i> Send Messages');

        $('#whatsappModal').modal('show');
    }

    function waToggleAll(chk) {
        var checked = $(chk).is(':checked');
        $('.waChk:not(:disabled)').prop('checked', checked);
        $('#waSelectAll, #waSelectAllTop').prop('checked', checked);
        waUpdateCount();
    }

    function waUpdateCount() {
        var n = $('.waChk:checked').length;
        $('#waSelectedBadge').text(n + ' selected');
        waUpdatePreview();
    }

    function waUpdatePreview() {
        var first = $('.waChk:checked:first');
        if (!first.length || !waCurrentTpl) {
            $('#waSendPreview').text('No student selected or template not loaded.');
            return;
        }
        var msg = waBuildMsg(waCurrentTpl, {
            name:     first.data('name'),
            roll:     first.data('roll'),
            adm:      first.data('adm'),
            father:   first.data('father'),
            guardian:  first.data('guardian'),
            exam_date: $('#waExamDate').val(),
            marks:     first.data('marks') || []
        });
        $('#waSendPreview').text(msg);
    }

    function waSendMessages() {
        var selected = [];
        $('.waChk:checked').each(function() {
            var phone = $(this).data('phone');
            if (!phone) return;
            selected.push({
                student_name:   $(this).data('name'),
                guardian_phone: String(phone),
                roll_no:        String($(this).data('roll')),
                admission_no:   String($(this).data('adm')),
                father_name:    $(this).data('father'),
                guardian_name:  $(this).data('guardian'),
                marks:          $(this).data('marks') || []
            });
        });

        if (!selected.length) {
            $('#waError').show();
            $('#waErrorMsg').text('Please select at least one student with a valid parent mobile number.');
            return;
        }

        $('#waSendBtn').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Sending ' + selected.length + ' message(s)...');
        $('#waSuccess').hide();
        $('#waError').hide();

        $.ajax({
            type: 'POST',
            url: baseurl + 'admin/examresult/sendwhatsapp',
            data: {
                students:     JSON.stringify(selected),
                exam_name:    waExamName,
                class_name:   waClassName,
                exam_date:    $("#waExamDate").val(),
                [waCsrfName]: waCsrfToken
            },
            dataType: 'json',
            success: function(r) {
                if (r.status == 1) {
                    $('#waSuccess').show();
                    $('#waSuccessMsg').text(
                        ' ' + r.sent + ' message(s) sent!' +
                        (r.failed > 0 ? ' (' + r.failed + ' failed)' : '')
                    );
                    // Per-row status update
                    if (r.results) {
                        var i = 0;
                        $('.waChk:checked').each(function() {
                            var res = r.results[i++];
                            var idx = $(this).data('idx');
                            if (res) {
                                var badge = res.status === 'sent'
                                    ? '<span class="label label-success"><i class="fa fa-check"></i> Sent</span>'
                                    : '<span class="label label-danger" title="' + (res.reason || '') + '"><i class="fa fa-times"></i> Failed</span>';
                                $('#waStatus' + idx).html(badge);
                            }
                        });
                    }
                    $('#waSendBtn').prop('disabled', false)
                        .removeClass('btn-success').addClass('btn-primary')
                        .html('<i class="fa fa-check"></i> Done!');
                } else {
                    $('#waError').show();
                    $('#waErrorMsg').text(r.message || 'Unknown error occurred.');
                    $('#waSendBtn').prop('disabled', false)
                        .html('<i class="fa fa-whatsapp"></i> Send Messages');
                }
            },
            error: function(xhr) {
                $('#waError').show();
                $('#waErrorMsg').text('Server error (' + xhr.status + '). Please try again.');
                $('#waSendBtn').prop('disabled', false)
                    .html('<i class="fa fa-whatsapp"></i> Send Messages');
            }
        });
    }

</script>