<?php
    // -----------------------------------------------------
    // module: checks, whether record is in edit mode by another user (so another edit or deletion can be prevented)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get parameter
    $recordid = $_REQUEST['recordid'];
    $flag = $_REQUEST['flag'];


    // ----- set block time for record to block record for any other user

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        if ($flag == 0)
            $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `blockdatetime`='2000-01-01 00:00:00', `blockuser`=:userid WHERE `id`=:recordid");
        else
            $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `blockdatetime`=localtime(), `blockuser`=:userid WHERE `id`=:recordid");

        // bind id parameter
        $stmt -> bindValue (':userid', $gbl_actuser['id']);
        $stmt -> bindValue (':recordid', $recordid);

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $recordid);

        // close database
        $dbh = null;

        // set error message
        echo '[{"pass": "dberror"}]';

        // exit
        exit;
        }

    // close database
    $dbh = null;

    // set success message
    echo '[{"pass": "ok"}]';
?>
