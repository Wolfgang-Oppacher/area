<?php
    // -----------------------------------------------------
    // module: saves user login data (password)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- right?

    // right to change password
    if (checkright_profile (1, 0, 'userpassword_change', 0, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- change password in contact database

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `password`=:password WHERE `id`=:userid AND `delflag`=0");

        // bind parameters to sql command
        $stmt -> bindValue (':password', urlcomponentdecode ($_REQUEST['newpwd']));
        $stmt -> bindParam (':userid', $gbl_actuser['id']);

        // execute sql command
        $stmt -> execute();

        // set new password to session array and save data to cache
        $_SESSION['pwd'] = urlcomponentdecode ($_REQUEST['newpwd']);
        $gbl_actuser['password'] = urlcomponentdecode ($_REQUEST['newpwd']);
        save_cache (2);
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $gbl_actuser['id']);

        // print error message and exit
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // add entry to editors field
    editors_addrecord ($dbh, $gbl_actuser['id'], $gbl_db_index['contacts'], $gbl_actuser['id'], 'edited', 'password', '');

    // close database
    $dbh = null;

    // print success message
    echo '[{"pass":"ok"}]';
?>
