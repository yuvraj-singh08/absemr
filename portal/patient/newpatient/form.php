<?php

$ignoreAuth_onsite_portal = true; // ignore the standard authentication for a regular OpenEMR user
require_once dirname(__FILE__, 4) . '/interface/globals.php';
require_once dirname(__FILE__, 4) . '/library/appointments.inc.php';
require_once "appointments/past_appointments.php";
require_once('./formUI.php');

$pid = $_REQUEST['pid'];

use OpenEMR\Core\Header;
use OpenEMR\Services\AbsPortal\ActivityService;
use OpenEMR\Services\AppointmentService;

$date = date('Y-m-d');
$patientAppointments = new AppointmentService();
$listOfAppointments = $patientAppointments->getAppointmentsForPatient($pid); //fetchNextXAppts($date, $pid, 5, true);
$groupAppointments = $patientAppointments->getGroupAppointments($pid);
$pastAppointments = getPatientsPastAppointments($pid, 5);

?>
<html>
<head>
    <title><?= xlt("Intake Form") ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <link href="<?= $GLOBALS['web_root']; ?>/portal/sign/css/signer_modal.css?v=<?= $GLOBALS['v_js_includes']; ?>" rel="stylesheet">
    <script src="<?= $GLOBALS['web_root']; ?>/portal/sign/assets/signer_api.js?v=<?= $GLOBALS['v_js_includes']; ?>"></script>
    <script src="<?= $GLOBALS['web_root']; ?>/portal/sign/assets/signature_pad.umd.js?v=<?= $GLOBALS['v_js_includes']; ?>"></script>
    <script src="<?= $GLOBALS['web_root']; ?>/portal/patient/scripts/libs/LAB.min.js"></script>
    <style>
        .ui-tabs-nav.fixed-top {
            position: fixed;
            /* Fix the ul at the top of the page */
            top: 0;
            /* Adjust the top position as needed */
            width: 100%;
            /* Make the ul take the full width of the viewport */
            z-index: 999;
            /* Ensure the ul is above other content */
            background-color: #e9e9e9;
            /* Add a background color if needed */
        }

        .tabs-container {
            position: relative;
            /* Set the container as a reference for the fixed ul */
        }

        .tab-content {
            margin-top: 15px;
            /* Adjust margin-top to push the content below the fixed ul */

            /* Add any other necessary styling for the tab content */
        }


    </style>
<script>
    <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4-alternate.js.php'); ?>
    $LAB.script("<?= $GLOBALS['web_root']; ?>/portal/patient/scripts/app/onsitedocuments.js?v=<?= $GLOBALS['v_js_includes']; ?>").wait().script(
        "<?= $GLOBALS['web_root']; ?>/portal/patient/scripts/app/onsiteportalactivities.js?v=<?= $GLOBALS['v_js_includes']; ?>").
    wait(function() {
        page.init();
        pageAudit.init();
        if (isPortal) {
            $('#Help').on('click', function(e) {
                e.preventDefault();
                $(".helpHide").addClass("d-none");
            });
            $("#Help").click();
            $(".helpHide").addClass("d-none");

            $('#showNav').on('click', () => {
                parent.document.getElementById('topNav').classList.toggle('collapse');
            });
        }
        console.log('init done template');

        setTimeout(function() {
            if (!page.isInitialized) {
                page.init();
                if (!pageAudit.isInitialized) {
                    pageAudit.init();
                }
            }
        }, 2000);
    });

    function printaDoc(divName) {
        flattenDocument();
        divName = 'templatediv';
        let printContents = document.getElementById(divName).innerHTML;
        let originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    function templateText(el) {
        $(el).data('textvalue', $(el).val());
        $(el).attr("data-textvalue", $(el).val())
        return false;
    }

    function templateCheckMark(el) {
        if ($(el).data('value') === 'Yes') {
            $(el).data('value', 'No');
            $(el).attr('data-value', 'No');
        } else {
            $(el).data('value', 'Yes');
            $(el).attr('data-value', 'Yes');
        }
        return false;
    }

    function templateRadio(el) {
        var rid = $(el).data('id')
        $('#rgrp' + rid).data('value', $(el).val());
        $('#rgrp' + rid).attr('data-value', $(el).val());
        $(el).prop('checked', true)
        return false;
    }

    function tfTemplateRadio(el) {
        var rid = $(el).data('id')
        $('#tfrgrp' + rid).data('value', $(el).val());
        $('#tfrgrp' + rid).attr('data-value', $(el).val());
        $(el).prop('checked', true);
        return false;
    }

    function replaceTextInputs() {
        $('.templateInput').each(function() {
            var rv = $(this).data('textvalue');
            $(this).replaceWith(jsText(rv));
        });
    }

    function replaceRadioValues() {
        $('.ynuGroup').each(function() {
            var gid = $(this).data('id');
            var grpid = $(this).prop('id');
            var rv = $('input:radio[name="ynradio' + jsAttr(gid) + '"]:checked').val();
            $(this).replaceWith(rv);
        });

        $('.tfuGroup').each(function() {
            var gid = $(this).data('id');
            var grpid = $(this).prop('id');
            var rv = $('input:radio[name="tfradio' + jsAttr(gid) + '"]:checked').val();
            $(this).replaceWith(rv);
        });
    }

    function replaceCheckMarks() {
        $('.checkMark').each(function() {
            var ckid = $(this).data('id');
            var v = $('#' + ckid).data('value');
            if (v === 'Yes')
                $(this).replaceWith('[\u2713]')
            else {
                $(this).replaceWith("[ ]")
            }
        });
    }

    function restoreTextInputs() {
        $('.templateInput').each(function() {
            var rv = $(this).data('textvalue');
            $(this).val(rv)
        });
    }

    function restoreRadioValues() {
        $('.ynuGroup').each(function() {
            var gid = $(this).data('id');
            var grpid = $(this).prop('id');
            var value = $(this).data('value');
            $("input[name=ynradio" + gid + "][value='" + value + "']").prop('checked', true);
        });

        $('.tfuGroup').each(function() {
            var gid = $(this).data('id');
            var grpid = $(this).prop('id');
            var value = $(this).data('value');
            $("input[name=tfradio" + gid + "][value='" + value + "']").prop('checked', true);
        });
    }

    function restoreCheckMarks() {
        $('.checkMark').each(function() {
            const ckid = $(this).data('id');
            if (ckid.data('value') === 'Yes')
                $('#' + ckid).prop('checked', true);
            else
                $('#' + ckid).prop('checked', false);
        });
    }

    function replaceSignatures() {
        $('.signature').each(function() {
            let type = $(this).data('type');
            if ($(this).attr('src') !== signhere && $(this).attr('src')) {
                $(this).removeAttr('data-action');
            }
            if (!isPortal) {
                $(this).attr('data-user', cuser);
            }
        });
    }

    function flattenDocument() {
        replaceCheckMarks();
        replaceRadioValues();
        replaceTextInputs();
        replaceSignatures();
    }

    function restoreDocumentEdits() {
        restoreCheckMarks();
        restoreRadioValues();
        restoreTextInputs();
    }

    $(document).ready(function() {
        // Scroll to top when the page loads
        $('html, body').scrollTop(0);
    });

    function openModal() {
        alert('open modal');
        var modal = document.getElementById('myModal');
        modal.style.display = 'display';
    }
</script>
</head>
<body class="p-0 m-0">
<div class="container-xl px-1">
<div id="successAlert" class="alert alert-success" style="display: none;">
    <?php print xlt('Your form has been successfully submitted') ?>
</div>
<?php if ((!referralTabSaved($pid)) || (!therapeuticTabSaved($pid)) || (!noticePracticeTabSaved($pid)) || (!releaseTabSaved($pid))) { ?>
    <div id="tabs">
        <ul class="fixed-top">
            <?php if (!referralTabSaved($pid)) { ?>
                <li><a href="#referral_tab">Referral Form</a></li>
            <?php } ?>
            <?php if (!therapeuticTabSaved($pid)) { ?>
                <li><a href="#therapeutic_tab">Therapeutic Form</a></li>
            <?php } ?>
            <?php if (!noticePracticeTabSaved($pid)) { ?>
                <li><a href="#notice_practice_tab">Notice of practice policies</a></li>
            <?php } ?>
            <?php if (!releaseTabSaved($pid)) { ?>
                <li><a href="#release_tab">Informed Consent For The Release Of Information</a></li>
            <?php } ?>
        </ul>

        <div class="panel-body p-0">
            <div class="tab-content">
                <?php if (!referralTabSaved($pid)) { ?>
                    <div id="referral_tab" class="tab-pane">
                        <form id="referralForm" method="POST">
                            <input type="hidden" name="referralTab" value="save">
                            <?php referralTab($pid); ?>
                            <button type="button" class="submit btn btn-primary">Save &amp; Continue</button>
                        </form>
                    </div>
                <?php } ?>
                <?php if (!therapeuticTabSaved($pid)) { ?>
                    <div id="therapeutic_tab" class="tab-pane">
                        <form id="therapeuticForm" method="POST">
                            <input type="hidden" name="therapeuticTab" value="therapeuticForm">
                            <?php therapeuticTab($pid); ?>
                            <button type="button" class="submit btn btn-primary">Save &amp; Continue</button>
                        </form>
                    </div>
                <?php } ?>

                <?php if (!noticePracticeTabSaved($pid)) { ?>
                    <div id="notice_practice_tab" class="tab-pane">
                        <form id="noticePracticeForm" method="POST">
                            <input type="hidden" name="noticePracticeTab" value="noticePracticeForm">
                            <?php noticePracticeTab($pid); ?>
                            <button id="notice_practice" type="button" class="submit btn btn-primary">Save &amp; Continue</button>
                        </form>
                    </div>
                <?php } ?>
                <?php if (!releaseTabSaved($pid)) { ?>
                    <div id="release_tab" class="tab-pane">
                        <form id="releaseForm" method="POST">
                            <input type="hidden" name="releaseTab" value="releaseForm">
                            <?php releaseTab($pid); ?>
                            <button type="button" class="submit btn btn-primary" id="release" <?php if (!noticePracticeTabSaved($pid) || !therapeuticTabSaved($pid) || !referralTabSaved($pid)) echo 'disabled'; ?>>
                                Save & Finish
                            </button>
                            <?php if (!noticePracticeTabSaved($pid) || !therapeuticTabSaved($pid) || !referralTabSaved($pid)): ?>
                                <span class="alert-danger" id="submit_finish">You have to submit all forms before finish.*</span>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

<?php } else { ?>
    <div class="col-12" >
        <div class="col-12 mt-5" >
        <h3><?php echo xlt("Individual/Family Session") ?></h3>
        </div>
        <div class="col-12 mt-2">
        <?php
        if (count($listOfAppointments) > 0) {
            echo "<table class='table mt-5 table-striped'> <tr><th>Appointment Date</th><th>Appointment Type</th><th>Appointment Time</th><th>Status</th><th></th></tr>";
            $a = 0;
            foreach ($listOfAppointments as $appt) {

                if ($appt['pc_eventDate'] >= date('Y-m-d')) {
                    $nameOfDate = date('D', strtotime($appt['pc_eventDate']));
                    $apptDate = date('m-d-Y', strtotime($appt['pc_eventDate']));
                    $apptTime = date('h:i A', strtotime($appt['pc_startTime']));
                    $appt_name = sqlQuery("SELECT pc_catname FROM `openemr_postcalendar_categories` WHERE pc_catid = ?", [$appt['pc_catid']]);

                    echo "<tr><td>" . $nameOfDate . " " . $apptDate . "</td><td>" . $appt_name['pc_catname'] . "</td><td> " . $apptTime . "</td><td>" . $appt['pc_apptstatus'] . "</td>";
                    if ($a == 0) {
                        echo "<td>" . xlt('Select Accounting & Make Payment') . " </td></tr>";
                    } else {
                        echo "<td></td></tr>";
                    }
                    $a++;
                }
            }
            echo "</table><p><strong>";
             echo xlt("If you need to reschedule or cancel an appointment, please contact the office.");
             echo "</strong></p><br><br>";
            if (!empty($groupAppointments)) { ?>
                <div class="col-12 mt-1" >
                <h3><?php echo xlt("Group Sessions") ?></h3>
                    <table class="table table-striped">
                    <?php
                    $i = 0;
                         foreach ($groupAppointments as $grpt) {
                             //echo "<pre>"; var_dump($grpt); echo "</pre>";
                             /*$nameOfGrpDate = date('D', strtotime($grpt['pc_eventDate']));
                             $apptGrpDate = date('m-d-Y', strtotime($grpt['pc_eventDate']));*/
                             $apptGrpTime = date('h:i A', strtotime($grpt['pc_startTime']));

                             echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "  . " " . "</td><td> " . $grpt['group_name'] . "</td><td> " . $apptGrpTime . "</td><td>" . "</td>";
                           if ($i < 1) {
                               echo "<td><button class='btn btn-success'>Pay Session</button> </td></tr>";
                               $i++;
                           } else {
                               echo "<td></td></tr>";
                           }
                         }
                    ?>
                    </table>

                </div>
            <?php }
        } else {
            echo "<p>" . xlt("You have no scheduled appointments, contact the office.") . "</p>";
        }

            echo "<div class='col-12 mt-1' ><table class='table table-striped'><h3> " . xlt('Past Appointments') . " </h3>";
            echo "<tr><th>Appointment Date</th><th>Appointment Type</th><th>Appointment Time</th><th>Status</th><th></th></tr>";
            foreach ($pastAppointments as $pastappt) {
                $nameOfPastDate = date('D', strtotime($pastappt['pc_eventDate']));
                $apptPastDate = date('m-d-Y', strtotime($pastappt['pc_eventDate']));
                $apptPastTime = date('h:i A', strtotime($pastappt['pc_startTime']));

                echo "<tr><td>" . $nameOfPastDate . " " . $apptPastDate . "</td><td> " . $pastappt['pc_title'] . "</td><td> " . $apptPastTime . "</td><td>" . $pastappt['pc_status'] . "</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td></tr>";
            }
            echo "</div></table>";
        ?>
        </div>
    </div>
</div><!-- end container xl -->

<!-- The Modal -->

<?php } ?>
</body>
<script>
    $(document).ready(function() {
        $("#tabs").tabs({
            active: 0
        });

        $('#tabs li').click(function() {
            let data = $(this).find('a').attr('href');
            if (data === "#release_tab") {
            }
        });

        $('.datepicker').datepicker({
            maxDate: 0
        });
        
        function handleTabNavigation () {
            let selectedTab = $('ul.ui-tabs-nav li[aria-selected="true"]');
            let currentIndex = selectedTab.index();
            
            let nextIndex = currentIndex + 1;

            if (currentIndex > 0) {
                let prevTabIndex = (currentIndex === 2) ? currentIndex - 2 : currentIndex - 1;
                let prevTab = $('ul.ui-tabs-nav li').eq(prevTabIndex);

                if (prevTab.is(':visible')) {
                    prevTab.find('a').trigger('click');
                    selectedTab.hide();
                    $(selectedTab.find('a').attr('href')).hide();
                    return;
                }
            }

            let nextTab = selectedTab.nextAll(':visible:first');
            if (nextTab.length > 0) {
                nextTab.find('a').trigger('click');
                if (nextTab.find('a').attr('href') === '#release_tab') {
                    $('#release').removeAttr('disabled');
                    $('#submit_finish').hide();
                }
            } else {
                $('ul.ui-tabs-nav li:visible:first a').trigger('click');
            }

            selectedTab.hide();
            $(selectedTab.find('a').attr('href')).hide();
        }
        
        function handleTabScrollNavigation () {
            let selectedTab = $('ul.ui-tabs-nav li[aria-selected="true"]');
            let hrefValue = selectedTab.find('a.ui-tabs-anchor').attr('href');

            if (hrefValue) {
                window.location.href = hrefValue;
                window.scrollTo(0, 0);
            }
            window.parent.scrollTo(0, 0);
        }

        $('#referralForm button.submit').on('click', function () {
            const form = $('#referralForm');

            $.ajax({
                type: 'POST',
                url: './formAjax.php',
                data: form.serialize(),
                success: function (data) {
                    form[0].reset();
                    handleTabNavigation();
                    handleTabScrollNavigation();
                },
            });
        });

        $('#therapeuticForm button.submit').on('click', function () {
            const form = $('#therapeuticForm');

            $.ajax({
                type: 'POST',
                url: './formAjax.php',
                data: form.serialize(),
                success: function (data) {
                    form[0].reset();
                    handleTabNavigation();
                    handleTabScrollNavigation();
                },
            });
        });

        $('#notice_practice').on('click', function () {
            let form = $('#noticePracticeForm');
            let templateContent = document.getElementById('notice-templatecontent').innerHTML;
            let payload = {
                noticePracticeTab: true,
                full_document: templateContent,
            };

            $.ajax({
                url: './formAjax.php',
                method: 'POST',
                data: payload,
                success: function (data) {
                    form[0].reset();
                    handleTabNavigation();
                    handleTabScrollNavigation();
                },
                error: function (error) {
                    console.error('Error saving template content:', error);
                },
            });
        });

        $('#release').on('click', function () {
            let form = $('#releaseForm');
            let templateContent = document.getElementById('release-templatecontent').innerHTML;
            let payload = {
                releaseTab: true,
                full_document: templateContent,
            };
            $.ajax({
                url: './formAjax.php',
                method: 'POST',
                data: payload,
                success: function (data) {
                    form[0].reset();
                    handleTabNavigation();
                    handleTabScrollNavigation();
                    location.reload();
                },
                error: function (error) {
                    console.error('Error saving template content:', error);
                },
            });
        });

        $('select[name=how_many_children]').on('change', function() {
            if ($(this).val() > 0) {
                $('div[name=childrens_names_ages_div]').css('display', 'block');
            } else {
                $('div[name=childrens_names_ages_div]').css('display', 'none');
            }
        });

        $('input[name=payment_ifo]').on('change', function() {
            if ($(this).val() === 'med_insu') {
                $('div[name=insuranceComDiv]').css('display', 'block');
            } else {
                $('div[name=insuranceComDiv]').css('display', 'none');
            }
            console.log($(this).val());
            if ($(this).val() === 'Eap') {
                $('div[name=eapDiv]').css('display', 'block');
            } else {
                $('div[name=eapDiv]').css('display', 'none');
            }
        });

        $('input[name^=Who_Referred_You_to_ABS_]').on('change', function() {
            const referersAndDivs = [{
                value: 'Patient',
                div: 'lawyerDiv'
            },
                {
                    value: 'Employee',
                    div: 'childwelfareDiv'
                },
                {
                    value: 'Court',
                    div: 'courtDiv'
                },
                {
                    value: 'Walk-In',
                    div: 'DMVDiv'
                },
                {
                    value: 'Newspaper',
                    div: 'probationDiv'
                },
                {
                    value: 'Radio',
                    div: 'doctorDiv'
                },
                {
                    value: 'T.V.',
                    div: 'eapDiv'
                },
                {
                    value: 'hospital',
                    div: 'hospitalDiv'
                },
                {
                    value: 'Direct Mail',
                    div: 'newyorkPblmGambDiv'
                },
                {
                    value: 'Coupon',
                    div: 'paroleDiv'
                },
                {
                    value: 'Referral Card',
                    div: 'socialWorkerDiv'
                },
            ];

            var referer = this;

            referersAndDivs.forEach(function(item) {
                const div = $('div[name=' + item.div + ']');
                if ($(referer).val() === item.value) {
                    const checked = $(referer).is(':checked');
                    div.css('display', checked ? 'block' : 'none');
                }
            });
        });

        $('input[name=countyName]').on('change', function() {
            $('div.county_courts').css('display', 'none');
            var div = $('div.county_courts[name="' + $(this).val() + '"]').css('display', 'block');
        });

        $('input[name="treatment_plan"]').attr('disabled', true);
        $('input[name="intake_diagnoses"]').on('change', function() {
            $('input[name="treatment_plan"]').attr('disabled', false);
            var plan = $(this).val().split('|');
            if (plan.length > 0) {
                plan = plan[0];
            }
            $('input[name="treatment_plan"][value="' + plan + '"]').attr('checked', true);
            $('input[name="treatment_plan"]:not(:checked)').attr('disabled', true);
        });

        $(document).on('change', 'select[name=living_environment]', function() {
            if ($(this).val() === 'apartment') {
                $('div[name=aptnumber]').css('display', 'block');
            } else
                $('div[name=aptnumber]').css('display', 'none');

            if ($(this).val() === '3_quater') {
                $('div[name=quaHouse]').css('display', 'block');
            } else
                $('div[name=quaHouse]').css('display', 'none');

            if ($(this).val() === 'half_way') {
                $('div[name=halfHouse]').css('display', 'block');
            } else
                $('div[name=halfHouse]').css('display', 'none');

            if ($(this).val() === 'Shelter') {
                $('div[name=shelterName]').css('display', 'block');
            } else
                $('div[name=shelterName]').css('display', 'none');
        });

        $(document).on('change', 'input[name^=therapSupportList]', function() {
            if ($(this).val() === 'employeement') {
                $('div[name=employeementDiv]').css('display', 'block');
            } else {
                $('div[name=employeementDiv]').css('display', 'none');
            }

            if ($(this).val() === 'unemployeement') {
                $('div[name=unemployeementDiv]').css('display', 'block');
            } else {
                $('div[name=unemployeementDiv]').css('display', 'none');
            }

            if ($(this).val() === 'pub_assistance') {
                $('div[name=publicAssistanceDiv]').css('display', 'block');
            } else {
                $('div[name=publicAssistanceDiv]').css('display', 'none');
            }

            if ($(this).val() === 'fam_support') {
                $('div[name=family_sup_div]').css('display', 'block');
            } else {
                $('div[name=family_sup_div]').css('display', 'none');
            }

            if ($(this).val() === 'ssi_ssd') {
                $('div[name=ssi_ssd_div]').css('display', 'block');
            } else {
                $('div[name=ssi_ssd_div]').css('display', 'none');
            }

        });
        $(document).on('change', 'input[name^=education_level_list]', function() {
            if ($(this).val() === 'college') {
                $('div[name=college_list_div]').css('display', 'block');
            } else {
                $('div[name=college_list_div]').css('display', 'none');
            }

        });

        $(document).on('change', 'input[name^=fam_relationship_list]', function() {
            if ($(this).val() === 'Divorced' || $(this).val() == 'Widowed') {
                $('div[name=DivorcedDiv]').css('display', 'block');
            } else {
                $('div[name=DivorcedDiv]').css('display', 'none');
            }

            if ($(this).val() === 'currently_married') {
                $('div[name=currentMarriedDiv]').css('display', 'block');
            } else {
                $('div[name=currentMarriedDiv]').css('display', 'none');
            }

            if ($(this).val() === 'single_never_married') {
                $('div[name=lastRelationShipDiv]').css('display', 'block');
            } else {
                $('div[name=lastRelationShipDiv]').css('display', 'none');
            }

        });
        $(document).on('change', 'select[name^=how_many_children]', function() {
            $.ajax({
                type: 'post',
                url: 'formUI.php?fn=getChildrenOptionList',
                data: {
                    'count': $(this).val()
                },
                success: function(data) {
                    $('div[name=children_custody_list_div]').empty();
                    $('div[name=children_custody_list_div]').append(data);
                }
            });
        });

        $(document).on('change', 'input[name^=fam_support_recovery]', function() {
            if ($(this).val() === 'yes') {
                $("div[name=fam_support_recovery_div]").css('display', 'block');
            } else {
                $("div[name=fam_support_recovery_div]").css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=fam_mem_willing_part]', function() {
            if ($(this).val() === 'yes') {
                $("div[name=fam_mem_willing_part_div]").css('display', 'block');
            } else {
                $("div[name=fam_mem_willing_part_div]").css('display', 'none');
            }
        });


        $(document).on('change', 'input[name^=fam_sub_abuse]', function() {
            if ($(this).val() === 'yes') {
                $("div[name=fam_sub_abuse_div]").css('display', 'block');
            } else {
                $("div[name=fam_sub_abuse_div]").css('display', 'none');
            }
        });


        $(document).on('change', 'input[name^=primary_care_doc]', function() {
            if ($(this).val() === 'yes') {
                $("div[name=primary_care_doc_div]").css('display', 'block');
            } else {
                $("div[name=primary_care_doc_div]").css('display', 'none');
            }
        });


        $(document).on('change', 'input[name^=curr_health]', function() {
            if ($(this).val() === 'good') {
                $("div[name=curr_health_good_div]").css('display', 'block');
                $("div[name=curr_health_fair_div]").css('display', 'none');
            } else if ($(this).val() === 'fair' || $(this).val() == 'poor') {
                $("div[name=curr_health_good_div]").css('display', 'block');
                $("div[name=curr_health_fair_div]").css('display', 'block');
            }
        });


        $(document).on('change', 'input[name^=curr_pres_med]', function() {
            if ($(this).val() === 'yes') {
                $("div[name=curr_pre_med_div]").css('display', 'block');
            } else
                $("div[name=curr_pre_med_div]").css('display', 'none');
        });
        $(document).on('change', 'input[name^=do_you_take_med_as_pres]', function() {
            if ($(this).val() === 'no') {
                $("div[name=do_you_take_med_as_pres_div]").css('display', 'block');
            } else
                $("div[name=do_you_take_med_as_pres_div]").css('display', 'none');
        });


        $(document).on('change', 'input[name^=being_referred_for_service]', function() {
            if ($(this).val() === 'anger_mgmt' && $(this).is(':checked')) {
                $('div[name=being_rf_for_services_anger_mgmt_div]').css('display', 'block');
            } else if ($(this).val() === 'anger_mgmt' && !($(this).is(':checked'))) {
                $('div[name=being_rf_for_services_anger_mgmt_div]').css('display', 'none');
            }

            if ($(this).val() === 'alcohol_drug' && $(this).is(':checked')) {
                $('div[name=alcohol_drug_abuse_div]').css('display', 'block');
            } else if ($(this).val() === 'alcohol_drug' && !($(this).is(':checked'))) {
                $('div[name=alcohol_drug_abuse_div]').css('display', 'none');
            }

            if ($(this).val() === 'dwi_dui' && $(this).is(':checked')) {
                $('div[name=dwi_dui_div]').css('display', 'block');
            } else if ($(this).val() === 'dwi_dui' && !($(this).is(':checked'))) {
                $('div[name=dwi_dui_div]').css('display', 'none');
            }
            if ($(this).val() === 'gam_pblm' && $(this).is(':checked')) {
                $('div[name=gam_pblm_div]').css('display', 'block');
            } else if ($(this).val() === 'gam_pblm' && !($(this).is(':checked'))) {
                $('div[name=gam_pblm_div]').css('display', 'none');
            }
            if ($(this).val() === 'sex_behav' && $(this).is(':checked')) {
                $('div[name=sex_behav_div]').css('display', 'block');
            } else if ($(this).val() === 'sex_behav' && !($(this).is(':checked'))) {
                $('div[name=sex_behav_div]').css('display', 'none');
            }
            if ($(this).val() === 'domestic_violence' && $(this).is(':checked')) {
                $('div[name=domestic_violence_div]').css('display', 'block');
            } else if ($(this).val() === 'domestic_violence' && !($(this).is(':checked'))) {
                $('div[name=domestic_violence_div]').css('display', 'none');
            }

            if ($(this).val() === 'parent_issue' && $(this).is(':checked')) {
                $('div[name=parenting_issues_div]').css('display', 'block');
            } else if ($(this).val() === 'parent_issue' && !($(this).is(':checked'))) {
                $('div[name=parenting_issues_div]').css('display', 'none');
            }

            if ($(this).val() === 'non_checm_addiction' && $(this).is(':checked')) {
                $('div[name=non_chemical_addiction_div]').css('display', 'block');
            } else if ($(this).val() === 'non_checm_addiction' && !($(this).is(':checked'))) {
                $('div[name=non_chemical_addiction_div]').css('display', 'none');
            }

        });

        $(document).on('change', 'input[name^=physically_hurt]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=injuriesSustainDiv]').css('display', 'block');
            } else {
                $('div[name=injuriesSustainDiv]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=physical_confrontation]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=physical_confrontationDiv]').css('display', 'block');
            } else {
                $('div[name=physical_confrontationDiv]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=Intake_suicide]', function() {
            if ($(this).val() === 'attemptedsuicide') {
                $('div[name=last_attempt_explanation_div]').css('display', 'block');
                $('div[name=felt_inclined_div]').css('display', 'block');
                $('div[name=feelings_explanation_div]').css('display', 'none');
            } else if ($(this).val() === 'suicidalthoughts') {
                $('div[name=last_attempt_explanation_div]').css('display', 'none');
                $('div[name=felt_inclined_div]').css('display', 'block');
                $('div[name=feelings_explanation_div]').css('display', 'block');
            } else {
                $('div[name=last_attempt_explanation_div]').css('display', 'none');
                $('div[name=felt_inclined_div]').css('display', 'none');
                $('div[name=feelings_explanation_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=arrested_ever]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=arrested_ever_yes_div]').css('display', 'block');
            } else {
                $('div[name=arrested_ever_yes_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'select[name^=how_many_arrest]', function() {
            if ($(this).val() !== '') {
                $('div[name=arrested_explanation_div]').css('display', 'block');
                $('div[name=monitoring_agency_div]').css('display', 'block');
                $('div[name=incarceration_div]').css('display', 'block');
                $('div[name=protection_orders_div]').css('display', 'block');
                $('div[name=state_registry_div]').css('display', 'block');
            } else {
                $('div[name=arrested_explanation_div]').css('display', 'none');
                $('div[name=monitoring_agency_div]').css('display', 'none');
                $('div[name=incarceration_div]').css('display', 'none');
                $('div[name=protection_orders_div]').css('display', 'none');
                $('div[name=state_registry_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=monitor_agency]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=sentence_length_div]').css('display', 'block');
            } else {
                $('div[name=sentence_length_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=incarcerated]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=incarcerated_length_div]').css('display', 'block');
                $('div[name=incarcerated_years_div]').css('display', 'block');
            } else {
                $('div[name=incarcerated_length_div]').css('display', 'none');
                $('div[name=incarcerated_years_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=state_registry]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=state_registry_list_div]').css('display', 'block');
            } else {
                $('div[name=state_registry_list_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'select[name^=state_registry_list]', function() {
            if ($(this).val() === '1') {
                $('div[name=sex_offender_div]').css('display', 'block');
            } else {
                $('div[name=sex_offender_div]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=exp_ver_phy_abuse]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=victimDiv]').css('display', 'block');
            } else {
                $('div[name=victimDiv]').css('display', 'none');
            }
        });

        $(document).on('change', 'input[name^=mental_health_treat]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=mentalHealthYesDiv]').css('display', 'block');
                $('div[name=mentalHealthNoDiv]').css('display', 'none');
            } else {
                $('div[name=mentalHealthYesDiv]').css('display', 'none');
                $('div[name=mentalHealthNoDiv]').css('display', 'block');
            }
        });
        $(document).on('change', 'input[name^=mentalHealthTreatment]', function() {
            if ($(this).val() === 'inpatient' && $(this).is(':checked')) {
                $('div[name=inPatientDiv]').css('display', 'block');
            } else if ($(this).val() === 'inpatient' && !($(this).is(':checked'))) {
                $('div[name=inPatientDiv]').css('display', 'none');
            }

            if ($(this).val() === 'outpatient' && $(this).is(':checked')) {
                $('div[name=outPatientDiv]').css('display', 'block');
            } else if ($(this).val() === 'outpatient' && !($(this).is(':checked'))) {
                $('div[name=outPatientDiv]').css('display', 'none');
            }

            if ($(this).val() === 'partialHospitaliation' && $(this).is(':checked')) {
                $('div[name=partialHospitalDiv]').css('display', 'block');
            } else if ($(this).val() === 'partialHospitaliation' && !($(this).is(':checked'))) {
                $('div[name=partialHospitalDiv]').css('display', 'none');
            }

            if ($(this).val() === 'dayTreatment' && $(this).is(':checked')) {
                $('div[name=dayTreatmentDiv]').css('display', 'block');
            } else if ($(this).val() === 'dayTreatment' && !($(this).is(':checked'))) {
                $('div[name=dayTreatmentDiv]').css('display', 'none');
            }
        });
        $(document).on('change', 'input[name^=recent_hospitalization]', function() {
            if ($(this).val() === 'yes') {
                $('div[name=recent_hospitalizationDiv]').css('display', 'block');
            } else {
                $('div[name=recent_hospitalizationDiv]').css('display', 'none');
            }
        });


        $(document).on('change', 'input[name^=perception_orientation]', function() {
            if ($(this).val() === 'disoriented') {
                $('div[name=disorientedDiv]').css('display', 'block');
            } else {
                $('div[name=disorientedDiv]').css('display', 'none');
            }
        });

    });


</script>

</html>
