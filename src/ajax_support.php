<?php
    // -----------------------------------------------------
    // module: sends content of support form by email
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- send message

    // set default parameter
    $receiveraddress = $gbl_general['mailaddress_default'];
    $senderaddress   = $gbl_actuser['pr_emailaddress'];
    $username        = $gbl_actuser['firstname'].' '.$gbl_actuser['lastname'];

    // address given?
    if ($senderaddress == '' && isset ($_REQUEST['emailaddress']))
        $senderaddress = urlcomponentdecode ($_REQUEST['emailaddress']);

    // name given?
    if ($username == ' ' && isset ($_REQUEST['username']))
        $username = urlcomponentdecode ($_REQUEST['username']);

    // name given?
    if ($username == '')
        $username = UI_WINDOW_SUPPORT_USERUNKNOWN;

    // set subject begin
    $subject = $gbl_general['tool_name'].': '.UI_WINDOW_SUPPORT_MESSAGEOF.' '.$username.' ';

    // test subject
    if (!isset ($_REQUEST['subject']))
        $_REQUEST['subject'] = '';

    // set mail receiver and subject
    switch (urlcomponentdecode ($_REQUEST['subject']))
        {
        // subject: general
        case 0:
            break;

        // subject: trainings organization
        case 1:
            $subject .= UI_WINDOW_SUPPORT_SUBJECTPREP.' '.UI_WINDOW_SUPPORT_SUBJECT2;
            break;

        // subject: trainings content
        case 2:
            $subject .= UI_WINDOW_SUPPORT_SUBJECTPREP.' '.UI_WINDOW_SUPPORT_SUBJECT3;
            break;

        // subject: technic
        case 3:
            $subject .= UI_WINDOW_SUPPORT_SUBJECTPREP.' '.UI_WINDOW_SUPPORT_SUBJECT4;
            $receiveraddress = $gbl_general['mailaddress_admin'];
            break;

        // default subject
        default:
            $subject .= UI_WINDOW_SUPPORT_SUBJECT5;
            $receiveraddress = $gbl_general['mailaddress_admin'];
        }

    // send mail
    if (isset ($_REQUEST['message']))
        if (smail ($senderaddress, $receiveraddress, '', '', $subject, urlcomponentdecode ($_REQUEST['message']), '', '') == 1)
            {
            // print success message
            echo '[{"pass":"mailerror"}]';

            // return
            return;
            }

    // print success message
    echo '[{"pass":"ok"}]';
?>
