<?php
    // -----------------------------------------------------
    // module: saves evaluation of record (1 to 5 points)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get id (index to record)
    $recordid = intval ($_REQUEST['recordid']);

    // get value
    $value = intval ($_REQUEST['value']);

    // get top/sub database flag
    if ($_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- validation

    // value valid? (II)
    if ($value < 1 || $value > 5)
        {
        // set failure
        echo '[{"pass":"novalidvalue"}]';

        // exit
        exit;
        }

    // reset flag
    $key = '';

    // loop fields for date ids
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        if ($dbf_v['type'] == 'evaluation')
            $key = $dbf_k;

    // field not found?
    if ($key == '')
        {
        // set failure
        echo '[{"pass":"noevaluationkey"}]';

        // log error message
        msg_log (LOG_ERROR_HIDE, 'no evaluation key in table \''.$gbl_db_data[$dbix]['dbname'].'\' found', '', 0, 0, $recordid);

        // exit
        exit;
        }


    // ----- get record

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("SELECT * FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE ".$gbl_db_filter[$dbix]." AND `id`=:recordid AND `delflag`=0");

        // bind id parameter to sql command string
        $stmt -> bindParam (':recordid', $recordid);

        // execute sql command
        $stmt -> execute();

        // get data
        if (!($record = $stmt -> fetch()))
            {
            // close database
            $dbh = null;

            // log error message
            msg_log (LOG_ERROR_HIDE, 'no '.$gbl_db_data[$dbix]['recordname'].' record found', 'dbix: '.$dbix, 0, 0, $recordid);

            // set failure
            echo '[{"pass":"norecord"}]';

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

        // set failure
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }


    // ----- process evaluation

    // get data
    $vals = json_decode ($record['evaluation'], TRUE);

    // i.c. reset array
    if (!is_array ($vals))
       $vals = array();

    // reset creator id
    $creatorid = 0;

    // get editors array
    $editors = json_decode ($record['editors'], TRUE);

    // i.c. reset array
    if (is_array ($editors))
        // get creator id
        $creatorid = $editors[key ($editors)]['userid'];

    // current user did not already vote and current user is not creator of this record?
    if (array_key_exists ($gbl_actuser['id'], $vals) || $gbl_actuser['id'] == $creatorid)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'record in \''.$gbl_db_data[$dbix]['dbname'].'\' already rated or own record', '', 0, 0, $recordid);

        // set failure
        echo '[{"pass":"ownorratedrecord"}]';

        // exit
        exit;
        }

    // sql area
    try
        {
        // prepare sql command
        $stmt_eval = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$key."`=:evaluation WHERE `id`=:recordid AND `delflag`=0");

        // add actual editor and edit date
        $vals[$gbl_actuser['id']] = $value;
        $evaluation = json_encode ($vals);

        // bind id parameter to sql command string
        $stmt_eval -> bindParam (':recordid', $recordid);
        $stmt_eval -> bindParam (':evaluation', $evaluation);

        // execute sql command
        $stmt_eval -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_eval, $recordid);

        // set failure
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

    // add entry to editors field
    editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'recordrated', $key, $value);

    // build rtp data
    record_rtp_build ($dbh, $dbix, $recordid, $record);

    // close database
    $dbh = null;


    // ----- calculate new cell content

    // get number of walues
    $cnt = count ($vals);

    // get quote
    $res = round (array_sum ($vals) / $cnt, 0);

    // build comment (I)
    if ($res == 1)
        $comment = UI_RECORDFIELD_EVALUATION_COMMENT_QUOTE.': '.$res.' '.UI_RECORDFIELD_EVALUATION_SCALE1.', ';
    else
        $comment = UI_RECORDFIELD_EVALUATION_COMMENT_QUOTE.': '.$res.' '.UI_RECORDFIELD_EVALUATION_SCALEX.', ';

    // build comment (II)
    if ($cnt == 1)
        $comment .= $cnt.' '.UI_RECORDFIELD_EVALUATION_COMMENT_ENTRY;
    else
        $comment .= $cnt.' '.UI_RECORDFIELD_EVALUATION_COMMENT_ENTRIES;


    // ----- calculate column index

    // reset fields array
    $pdbf = array();

    // loop through personal database to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        if ($value['tableactive'.$topsubflag] == 1)
            $pdbf[sprintf ("%03d", $value['columnorder'])] = $gbl_dbf_data[$dbix][$key]['type'];

    // sort index
    ksort ($pdbf);

    // reset counter
    $i = 0;
    $j = -1;

    // print headers
    foreach ($pdbf as $key => $value)
        // evaluation key?
        if ($value == 'evaluation')
            {
            // get index to evaluation key
            $j = $i;

            // break loop
            break;
            }
        else
            // increase index
            $i++;


    // ----- write results

    // set success
    if ($j == -1)
        echo '[{"pass":"ok", "column":"", "evalimage":"star'.$res.'.gif^'.$comment.'"}]';
    else
        echo '[{"pass":"ok", "column":"'.$j.'", "evalimage":"star'.$res.'.gif^'.$comment.'"}]';
?>
