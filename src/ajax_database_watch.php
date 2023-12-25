<?php
    // -----------------------------------------------------
    // module: switch database watching on/off
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');


    // ----- right?

    // right to watch database
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_watch', 0, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- save database watch setting

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `watchids`=:watchids WHERE `id`=:userid AND `delflag`=0");

        // get watch array
        $watcharray = json_decode ($gbl_actuser['watchids'], TRUE);

        // update actual watch setting
        $watcharray[$gbl_db_data[$dbix]['dbname']] = intval ($_REQUEST['db_watch_flag']);

        // make json string
        $gbl_actuser['watchids'] = json_encode ($watcharray);

        // bind parameters to sql command
        $stmt -> bindParam (':watchids', $gbl_actuser['watchids']);
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
