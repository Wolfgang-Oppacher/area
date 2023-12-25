<?php
    // -----------------------------------------------------
    // module: save chat message
    // status: v6 working
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- save message

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("INSERT INTO `forum` SET `delflag`=0,`recordrelid`=:recordrelid,`userid`=:userid,`subject`=:subject,`message`=:message,`groupaccess`=:groupaccess,`useraccess`=:useraccess,`readid`=:readid,`editors`=:editors,`projectid`=:projectid,`datetime`=:datetime");

        // bind values
        $stmt -> bindValue (':userid', $gbl_actuser['id']);
        $stmt -> bindValue (':recordrelid', $gbl_actuser['id']);
        $stmt -> bindValue (':subject', $_REQUEST['subject']);
        $stmt -> bindValue (':message', $_REQUEST['message']);
// generisch
        $stmt -> bindValue (':groupaccess', ',1,3,68,');
        $stmt -> bindValue (':useraccess', ','.$gbl_actuser['id'].',');
        $stmt -> bindValue (':readid', ','.$gbl_actuser['id'].',');
        $stmt -> bindValue (':editors', '[{"datetime":"'.date ("Y-m-d H:i:s", time()).'","userid":"'.$gbl_actuser['id'].'","action":"created","keys":"","comment":""}]');
        $stmt -> bindValue (':projectid', ','.intval ($_REQUEST['projectid']).',');
        $stmt -> bindValue (':datetime', date ("Y-m-d H:i:s", time()));

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $_REQUEST['userid']);

        // set error message
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // print ok result
    echo '[{"pass": "ok"}]';

    // send mail to organization
    smail ($gbl_general['mailaddress_admin'], $gbl_general['mailaddress_admin'], '', '', $gbl_general['org_name_sh'].', '.$gbl_general['tool_name'].': Neue Nachricht im asynchronen Chat', 'user id '.$_REQUEST['userid'], '', '');


	// https://github.com/sebbmeyer/php-microsoft-teams-connector/tree/master
//    require_once ("libs/microsoft-teams-connector/autoload.php");

/*
	// create connector instance
	$connector = new \Sebbmyr\Teams\TeamsConnector("https://outlook.office.com/webhook/c310c987-036f-4ce5-a98d-4cf434c66ab9@b0749ac9-3fe5-4dcc-bbae-d1db4a89fcbb/IncomingWebhook/1c234360e000429880be18689eaddfec/5ea491bd-f04e-4fb5-ad2a-48410c8ec39d");

	// create card
	$card  = new \Sebbmyr\Teams\Cards\SimpleCard(['title' => 'Simple card title', 'text' => 'Testnachricht']);

	// send card via connector
	$connector->send($card);
*/

    // return
    return;
?>
