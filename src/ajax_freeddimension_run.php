<?php
    // -----------------------------------------------------
    // module: set freed dimension
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get index to record
    if ($_REQUEST['recordid'])
        $recordid = $_REQUEST['recordid'];
    else
        $recordid = 0;

    // connect to database
    $dbh = pdoconnect (1);


    // ----- set freed dimension

    // reset field list
    $fields = '';

    // loop all fields: set fields to copy
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // i.c. add delimiter
        if ($fields != '')
            $fields .= ',';

        // add sql code
        if ($dbf_v['array_options']['freeddimension_source'] == 'norm')
            $fields .= '`fdd_'.$dbf_v['key'].'`=`'.$dbf_v['key'].'`';
        else
            $fields .= '`fdd_'.$dbf_v['key'].'`=`rtp_'.$dbf_v['key'].'`';
        }

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET ".$fields." WHERE `id`=:recordid");

        // bind parameter
        $stmt -> bindParam (':recordid', $recordid);

        // execute sql command
        $stmt -> execute();

        // add editors entry
        editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'freed', '', '');
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

    // set success message
    echo '[{"pass": "ok"}]';
?>
