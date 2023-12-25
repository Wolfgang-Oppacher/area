<?php
    // -----------------------------------------------------
    // module: selects active project
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- save actual project id

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `actprojectid`=:actprojectid WHERE `id`=:userid");

        // bind parameters to sql command
        $stmt -> bindParam (':userid', $gbl_actuser['id']);
        $stmt -> bindValue (':actprojectid', urlcomponentdecode ($_REQUEST['projectid']));

        // execute sql command
        $stmt -> execute();

        // delete cache
        delete_cache (1, 0);
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $gbl_actuser['id']);

        // sent failure response
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // close database
    $dbh = null;

    // sent ok message
    echo '[{"pass":"ok"}]';
?>
