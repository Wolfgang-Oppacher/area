<?php
    // -----------------------------------------------------
    // module: saves any user login data (password)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- right?

    // get database id
    $dbix = $gbl_db_index['contacts'];

    // get id (index to record)
    $recordid = $_REQUEST['recordid'];

    // right to change password
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_loginpasswordchange', $recordid, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- change user password in contact database

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `password`=:password WHERE `id`=:recordid AND `delflag`=0");

        // bind parameters to sql command
        $stmt -> bindValue (':password', urlcomponentdecode ($_REQUEST['newpwd']));
        $stmt -> bindParam (':recordid', $recordid);

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $recordid);

        // print error message and exit
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // add entry to editors field
    editors_addrecord ($dbh, $gbl_actuser['id'], $gbl_db_index['contacts'], $recordid, 'edited', 'password', '');

    // close database
    $dbh = null;

    // print success message
    echo '[{"pass":"ok"}]';
?>
