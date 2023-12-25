<?php
    // -----------------------------------------------------
    // module: sets record to user ownership
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get id (index to record)
    $recordid = $_REQUEST['recordid'];

    // reset key
    $ownerid_key = '';

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        // type = relativeowner?
        if ($dbf_v['type'] == 'relativeowner')
            $ownerid_key = $dbf_v['key'];

    // key found?
    if ($ownerid_key == '')
        {
        // log error message
        msg_log (LOG_ERROR_HIDE, 'owner key in database fields not found', $gbl_db_data[$dbix]['dbname'], 0, 0, $recordid);

        // set error message
        echo '[{"pass": "dberror"}]';

        // exit
        exit;
        }


    // ----- set owner

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$ownerid_key."`=:ownerid WHERE `id`=:recordid");

        // bind id parameter
        $stmt -> bindValue (':ownerid', $gbl_actuser['id']);
        $stmt -> bindValue (':recordid', $recordid);

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

        // set error message
        echo '[{"pass": "dberror"}]';

        // exit
        exit;
        }

    // add entry to editors field
    editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'ownrecord', '', '');

    // build rtp data
    $record = array();
    record_rtp_build ($dbh, $dbix, $recordid, $record);

    // close database
    $dbh = null;

    // set success message
    echo '[{"pass": "ok"}]';
?>
