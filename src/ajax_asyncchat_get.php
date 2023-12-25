<?php
    // -----------------------------------------------------
    // module: get chat messages
    // status: v6 working
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- save message

    // get div ids
    $ids = array_map ('trim', explode (',', urlcomponentdecode ($_REQUEST['ids'])));

    // reset filter
    $filter = '';

    // add ids to filter
    foreach ($ids as $ix => $id)
        {
        // id not beginning with "chat_"?
        if (strpos ($id, 'chat_') === false)
            continue;

        // add id to filter
        $filter .= ' AND `id`<>'.intval (substr($id, 5));
        }

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("SELECT `subject`,`message`,`id`,`datetime`,`userid` FROM `forum` WHERE `delflag`=0 AND `recordrelid`=:recordrelid AND `useraccess` LIKE :useraccess".$filter);

        // bind values
        $stmt -> bindValue (':recordrelid', $gbl_actuser['id']);
        $stmt -> bindValue (':useraccess', '%,'.$gbl_actuser['id'].',%');

/*
        $stmt -> bindValue (':groupaccess', ',1,3,68,');
        $stmt -> bindValue (':projectid', ','.intval ($_REQUEST['projectid']).',');
*/

        // execute sql command
        $stmt -> execute();

        // reset messages array
        $messages = array();

        // get message records
        while ($row = $stmt -> fetch())
            $messages[$row['datetime']] = $row;

        // sort array
        ksort ($messages);
        }

    // error?
    catch (Exception $error)
        {
        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $_REQUEST['userid']);

        // set error message
        echo '[{"pass":"dberror"}]';

        // close database
        $dbh = null;

        // exit
        exit;
        }

    // print ok result
    echo '[{"pass":"ok",';

    // open messages
    echo '"messages":[';

    // reset counter
    $cnt = 0;

    // print all messages
    foreach ($messages as $id => $rec)
        {
        // add delimiter?
        if ($cnt++ > 0)
            echo ',';

        // get user name
        contact_get ($rec['userid'], 1, 1, 0, 0, $username);

        // record
        echo '{"message":"'.urlcomponentencode (printhtml ($rec['message'])).'",'
            .'"subject":"'.urlcomponentencode (printhtml ($rec['subject'])).'",'
            .'"id":"'.$rec['id'].'",'
            .'"user":"'.urlcomponentencode ($username).'",'
            .'"datetime":"'.datetime_mysql2german ($rec['datetime']).'"}';
        }

    // close messages
    echo ']';

    // close result
    echo '}]';

    // close database
    $dbh = null;

    // return
    return;
?>
