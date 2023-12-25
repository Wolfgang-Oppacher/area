<?php
    // -----------------------------------------------------
    // module: re-order record
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // get index to record
    $recordid = $_REQUEST['recordid'];

    // get top/sub database flag
    if (array_key_exists ('topsubflag', $_REQUEST) && $_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- right to re-order record?

    // right to abo database
    if (   checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_edit', $recordid, 0, 0, 0, 0) == 0
        || !array_key_exists ('reorder', $gbl_db_data[$dbix]['options_array'])
        || count ($gbl_db_data[$dbix]['options_array']['reorder']) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- get record data

    // reset field string
    $fields = '';
    $set = '';

    // set field string
    foreach ($gbl_db_data[$dbix]['options_array']['reorder'] as $key => $value)
        {
        // i.c. add delimiter
        if ($fields != '')
            {
            $fields .= ',';
            $set    .= ',';
            }

        // add field to fields list
        $fields .= '`'.$value['key'].'`';

        // add field to set list
        $set .= '`'.$value['key'].'`=`'.$value['key'].'`+1';
        $set .= ',`rtp_'.$value['key'].'`=`'.$value['key'].'`';

        // save order key
        $orderkey = $value['key'];
        }

    // super database?
    if (array_key_exists ('reldbname', $gbl_dbf_data[$dbix]) && array_key_exists ('relrecid', $gbl_dbf_data[$dbix]))
        $fields .= ',`reldbname`,`relrecid`';

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // set sql string
        $sql = "SELECT ".$fields." FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE ".$gbl_db_filter[$dbix]." AND `id`=:recordid AND `delflag`=0";

        // prepare sql command
        $stmt = $dbh -> prepare ($sql);

        // bind parameter
        $stmt -> bindValue (':recordid', $recordid);

        // execute sql command
        $stmt -> execute();

        // get data
        if (!($record = $stmt -> fetch(PDO::FETCH_ASSOC)))
            {
            // close database
            $dbh = null;

            // log error message
            msg_log (LOG_ERROR_HIDE, 'data not found', '', 0, 0, $recordid);

            // set error message
            echo '[{"pass":"dberror"}]';

            // exit
            exit;
            }
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $recordid);

        // set error message
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }


    // ----- re-order record

    // sql area
    try
        {
        // i.c. set relative database parameter
        if (array_key_exists ('reldbname', $gbl_dbf_data[$dbix]) && array_key_exists ('relrecid', $gbl_dbf_data[$dbix]))
            $relpar = " AND `reldbname`='".$record['reldbname']."' AND `relrecid`=".intval ($record['relrecid']);
        else
            $relpar = '';

        // set sql string
        $sql = "UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET ".$set." WHERE ".$gbl_db_filter[$dbix].$relpar." AND `order`>=:order";

        // prepare sql command
        $stmt = $dbh -> prepare ($sql);

        // bind parameter
        $stmt -> bindValue (':order', $record[$orderkey]);

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
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // set success
    echo '[{"pass":"ok"}]';

    // close database
    $dbh = null;

    // return
    return;
?>
