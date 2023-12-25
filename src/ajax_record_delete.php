<?php
    // -----------------------------------------------------
    // module: deletes record(s) (does not remove record, sets deletion flag instead)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // get id(s) (index to record)
    $recordid = $_REQUEST['recordid'];

    // right to delete record
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_delete', $recordid, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }

    // get record ids
    $recordids = array_map ('trim', explode (",", $recordid));

    // reset sql id string
    $sqlids = '';

    // build sql string
    foreach ($recordids as $key => $value)
        // id given?
        if ($value != '' && $value != 0)
            if ($sqlids == '')
                $sqlids = '`id`='.intval ($value);
            else
                $sqlids .= ' OR `id`='.intval ($value);

    // no record?
    if ($sqlids == '')
        {
        // log error message
        msg_log (LOG_ERROR_HIDE, 'no records of '.$gbl_db_data[$dbix]['recordname'].' found to delete', $recordid, $dbh, $stmt, 0);

        // set failure
        echo '[{"pass":"norecord"}]';

        // exit
        exit;
        }


    // ----- delete record

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command (delete record)
        $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `delflag`=1, `blockdatetime`='2000-01-01 00:00:00' WHERE ".$gbl_db_filter[$dbix]." AND (".$sqlids.") AND `delflag`=0");

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error, ids='.$recordid, $error->getMessage(), $dbh, $stmt, 0);

        // set failure
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // add delete entry
    editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'deleted', '', '');


    // ----- (re-)build scheduler data

    // reset record array
    $record = array();
    $record['id'] = $recordid;

    // scheduler data in database?
    if (array_key_exists ($dbix, $gbl_dbf_scheduler))
        // delete record data
        schedulerdata_fill_record ($dbh, $dbix, $gbl_dbf_scheduler[$dbix], $record, 0);

    // build calendars
    calendar_ics_build ($dbh, $dbix, $recordid);

    // close database
    $dbh = null;

    // set success
    echo '[{"pass":"ok"}]';
?>
