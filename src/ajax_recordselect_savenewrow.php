<?php
    // -----------------------------------------------------
    // module: save new row for record_select
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    $dbix   = $_REQUEST['dbix'];
    $dbix_t = $_REQUEST['dbix_t'];


    // ----- right?

    // right to abo database
    if (checkright_profile (1, $dbix_t, 'db_'.$gbl_db_data[$dbix_t]['dbname'].'_create', 0, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- save new record

    // connect to database
    $dbh = pdoconnect (1);

    // reset record
    $record = array ();

    // reset sql fields
    $sqlfields = '';

    // project id given?
    if ($gbl_db_data[$dbix_t]['projectfilterflag'] == 1)
        $sqlfields .= ',`projectid`=:projectid';

    // add keys
    foreach ($_REQUEST as $key => $value)
        // key valid?
        if ($key != '' && $key[0] == '_')
            $sqlfields .= ',`'.substr ($key, 1).'`=:'.substr ($key, 1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("INSERT INTO `".$gbl_db_data[$dbix_t]['dbname']."` SET `delflag`=0, `readid`=:readid, `editors`=:editors".$sqlfields);

        // save values
        $record['readid']  = ','.$gbl_actuser['id'].',';
        $record['editors'] = editors_addrecord_build ($record, $gbl_actuser['id'], 'created', '', '');

        // bind parameters to sql command
        $stmt -> bindValue (':readid',  $record['readid']);
        $stmt -> bindValue (':editors', $record['editors']);

        // project id given?
        if ($gbl_db_data[$dbix_t]['projectfilterflag'] == 1)
            {
            // save value
            $record['projectid'] = $_REQUEST['projectid'];

            // bind value
            $stmt -> bindValue (':projectid', $record['projectid']);
            }

        // add keys contents
        foreach ($_REQUEST as $key => $value)
            // key valid?
            if ($key != '' && $key[0] == '_')
                {
                // save value
                $record[substr ($key, 1)] = urlcomponentdecode ($value);

                // bind value
                $stmt -> bindValue (':'.substr ($key, 1), $record[substr ($key, 1)]);
                }

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, 0);

        // set failure message
        echo '[{"pass": "dberror"}]';

        // return
        exit;
        }

    // get record id
    $recordid = $dbh -> lastInsertId();

    // build rtp data
    record_rtp_build ($dbh, $dbix_t, $recordid, $record);

    // print success message
    echo '[{"pass":"ok", "recordid":"'.$recordid.'"}]';

    // close database
    $dbh = null;
?>
