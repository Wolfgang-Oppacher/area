<?php
    // -----------------------------------------------------
    // module: switch database abonnement on/off
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');


    // ----- right?

    // right to abo database
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_abo', 0, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- save database abonnement setting

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `aboids`=:aboids WHERE `id`=:userid AND `delflag`=0");

        // get abo array
        $aboarray = json_decode ($gbl_actuser['aboids'], TRUE);

        // update actual abo setting
        $aboarray[$gbl_db_data[$dbix]['dbname']] = intval ($_REQUEST['db_abo_flag']);

        // make json string
        $gbl_actuser['aboids'] = json_encode ($aboarray);

        // bind parameters to sql command
        $stmt -> bindParam (':aboids', $gbl_actuser['aboids']);
        $stmt -> bindParam (':userid', $gbl_actuser['id']);

        // execute sql command
        $stmt -> execute();

        // save cache
        save_cache (2);
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

    // print success message
    echo '[{"pass":"ok"}]';
?>
