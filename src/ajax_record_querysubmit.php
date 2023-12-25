<?php
    // -----------------------------------------------------
    // module: saves query data to database
    // status: finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // get id (index to record)
    $recordid = $_REQUEST['recordid'];


    // ----- save query results

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // reset array
        $array = array();

        // loop form values
        for ($i = 0; array_key_exists ('_fk_'.$i, $_REQUEST); $i++)
            {
            // add key and value to message text
            $array[$i]['key']   = urlcomponentdecode ($_REQUEST['_fk_'.$i]);
            $array[$i]['value'] = urlcomponentdecode ($_REQUEST['_fv_'.$i]);
            }

        // encode values of array
// necessary?
        array_walk_recursive ($array, 'array_utf8_encode');

        // get json string
        $results = json_encode ($array);

        // generate project id (I)
        $projectid = ',';

        // generate project id (II)
        foreach ($gbl_project_rightprofiles as $k => $v)
            $projectid .= $k.',';

        // prepare sql command
        $stmt = $dbh -> prepare ("INSERT INTO `queriesresults` SET `createdatetime`=localtime(), `userid`=:userid, `queryid`=:queryid, `results`=:results, `projectid`=:projectid, `browser`=:browser, `ip`=:ip, `delflag`=0");

        // bind parameters
        $stmt -> bindValue (':userid',        $gbl_actuser['id']);
        $stmt -> bindValue (':queryid',       $recordid);
        $stmt -> bindValue (':results',       $results);
        $stmt -> bindValue (':projectid',     $projectid);
        $stmt -> bindValue (':browser',       $_SERVER['HTTP_USER_AGENT']);
        $stmt -> bindValue (':ip',            $_SERVER['REMOTE_ADDR']);

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

        // set failure message
        echo '[{"pass": "dberror"}]';

        // return
        exit;
        }

    // build rtp data
    $record = array();
    record_rtp_build ($dbh, $gbl_db_index['queriesresults'], $dbh -> lastInsertId(), $record);

    // close database
    $dbh = null;

    // set success
    echo '[{"pass": "ok"}]';
?>
