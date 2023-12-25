<?php
    // -----------------------------------------------------
    // module: populate query record(s)
    // status: v6 working
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // get id(s) (index to record)
    $recordid = $_REQUEST['recordid'];

    // right to delete record
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_querypopulate', $recordid, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }

/*
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
##########

    // delete queries list?
    if ($rflag == 1)
        {
        // sql area: delete querieslist records
        try
            {
            // prepare sql command
            $stmt = $dbh -> prepare ("DELETE FROM `querieslist` WHERE `reldbname`=:reldbname");

            // bind parameter
            $stmt -> bindParam (':reldbname', $gbl_db_data[$dbix]['dbname']);

            // execute sql command
            $stmt -> execute();
            }

        // error?
        catch (Exception $error)
            {
            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $dbix);
            }
        }

    // search personal database index
    foreach ($gbl_actuser['pdb_settings'] as $pdbix => $v)
        if ($v['dbname'] == $gbl_db_data[$dbix]['dbname'])
            break;

    // reset jobs array
    $jobs = array();

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // standard queries?
        if ($dbf_v['type'] != 'table' || $dbf_v['default'] != 'queries')
            continue;

        // sql area
        try
            {
            // prepare sql command
            if ($gbl_dbf_data[$dbix]['type'] == 'contacts')
                $stmt = $dbh->prepare("SELECT `id`, `projectid` FROM `" . $gbl_db_data[$dbix]['dbname'] . "` WHERE `tags` != '|auto_traininggroup|' AND `loginactive`=1 ORDER BY `id`");
            else
                $stmt = $dbh->prepare("SELECT `id`, `projectid` FROM `" . $gbl_db_data[$dbix]['dbname'] . "` ORDER BY `id`");

            // execute sql command
            $stmt->execute();

            // get data
            while ($record = $stmt->fetch())
                // process field
                db_formvalue_get($dbh, $dbix, $pdbix, $dbf_k, $record, $record, 0, 'create', $jobs);
            }

        // error?
        catch (Exception $error)
            {
            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $dbix);
            }
        }
*/

    // close database
    $dbh = null;

    // set success
    echo '[{"pass":"ok"}]';
?>
