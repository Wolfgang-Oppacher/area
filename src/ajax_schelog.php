<?php
    // -----------------------------------------------------
    // module: writes user related work time period to database (to log working periods)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get last time of user in schelog database

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("SELECT `end` FROM `schelog` WHERE `userid`=:userid ORDER BY `end` DESC");

        // bind parameters to sql command
        $stmt -> bindParam (':userid', $gbl_actuser['id']);

        // execute sql command
        $stmt -> execute();

        // get data
        if (!($row = $stmt -> fetch()))
            $row['end'] = date ("Y-m-d H:i:s", time());
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $gbl_actuser['id']);

        // set failure message
        echo '[{"pass": "dberror"}]';

        // return
        exit;
        }


    // ----- log to schelog database

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("INSERT INTO `schelog` SET `userid`=:userid, `projectid`=:projectid, `start`=:start, `end`=:end, `comment`=:comment, `delflag`=0, `rtp_userid`=:rtp_userid, `rtp_projectid`=:rtp_projectid, `rtp_start`=:rtp_start, `rtp_end`=:rtp_end, `rtp_comment`=:rtp_comment");

        // get date and time
        if (!array_key_exists ('datetime', $_REQUEST))
            $time = date ("Y-m-d H:i:s", time());
        else
            $time = date ("Y-m-d H:i:s", round ($_REQUEST['datetime']/1000));

        // get comment
        if (array_key_exists ('comment', $_REQUEST))
            $comment = $_REQUEST['comment'];
        else
            $comment = '';

        // bind parameters to sql command
        $stmt -> bindParam (':userid', $gbl_actuser['id']);
        $stmt -> bindParam (':rtp_userid', $gbl_actuser['loginname']);
        $stmt -> bindValue (':projectid', ','.$gbl_actuser['schelog_projectid'].',');
        $stmt -> bindValue (':rtp_projectid', project_titles_print ($gbl_actuser['schelog_projectid'], 0, 2, 1, 1, 1, 1, 1, 0, 0));
        $stmt -> bindValue (':start', $row['end']);
        $stmt -> bindValue (':rtp_start', datetime_mysql2german ($row['end']));
        $stmt -> bindValue (':end', $time);
        $stmt -> bindValue (':rtp_end', datetime_mysql2german ($time));
        $stmt -> bindValue (':comment', $comment);
        $stmt -> bindValue (':rtp_comment', $comment);

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $gbl_actuser['id']);

        // set failure message
        echo '[{"pass": "dberror"}]';

        // return
        exit;
        }


    // ----- save project id for user

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `schelog_projectid`=:projectid WHERE `id`=:userid");

        // bind parameters to sql command
        $stmt -> bindParam (':userid', $gbl_actuser['id']);
        $stmt -> bindValue (':projectid', $_REQUEST['projectid']);

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $gbl_actuser['id']);

        // set failure message
        echo '[{"pass": "dberror"}]';

        // return
        exit;
        }

    // close database
    $dbh = null;


    // ----- build schelog state string

    // get old project name
    if (array_key_exists ($gbl_actuser['schelog_projectid'], $gbl_activeprojects))
        $projectname_old = $gbl_activeprojects[$gbl_actuser['schelog_projectid']]['organization_short'];
    else
        $projectname_old = '';

    // get new project name
    if (array_key_exists ($_REQUEST['projectid'], $gbl_activeprojects))
        {
        // set short new project name
        $projectname_new = $gbl_activeprojects[$_REQUEST['projectid']]['organization_short'];

        // begin new schelog project name
        $projectname_new_complete = UI_SCHELOG_ACTIVE.' '.project_titles_print ($_REQUEST['projectid'], 0, 2, 1, 1, 0, 0, 1, 0, 0);
        }
    else
        {
        // set short new project name
        $projectname_new = '';

        // set default name
        $projectname_new_complete = UI_SCHELOG_INACTIVE;
        }

    // sent ok message and project data
    echo '[{"pass": "ok", "projectid_old":"'.$gbl_actuser['schelog_projectid']
                     .'", "projectname_old":"'.stripquotes ($projectname_old)
                     .'", "projectid_new":"'.$_REQUEST['projectid']
                     .'", "projectname_new":"'.stripquotes ($projectname_new)
                     .'", "projectname_new_complete":"'.stripquotes ($projectname_new_complete).'"}]';


    // ----- save new parameter to session
    $gbl_actuser['schelog_projectid'] = $_REQUEST['projectid'];
    save_cache (2);
?>
