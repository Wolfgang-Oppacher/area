<?php
    // -----------------------------------------------------
    // module: looks for relations of record(s) to records of other databases
    //         looks for records which are blocked, because they are edited
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

    // reset record results array
    $recordresults = array();

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
        msg_log (LOG_ERROR_HIDE, 'no record ids found', $recordid, 0, 0, 0);

        // set failure
        echo '[{"pass":"norecord"}]';

        // exit
        exit;
        }


    // ----- record blocked (edit state) or related to other records?

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("SELECT `id`, `blockdatetime`, `blockuser` FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE ".$sqlids);

        // execute sql command
        $stmt -> execute();

        // get data
        while ($record = $stmt -> fetch())
            {
            // set type: free record
            $recordresults[$record['id']]['type'] = 'free';

            // record edited more than 1 minute in the past (not blocked any more)
            if ((time() - strtotime ($record['blockdatetime'])) < 60)
                {
                // set type: blocked record
                $recordresults[$record['id']]['type'] = 'blocked';

                // set user who is blocking record
                $result = '';
                contact_get ($record['blockuser'], 1, 0, 0, 0, $result);
                $recordresults[$record['id']]['user'] = $result;
                }

            // loop all databases
            foreach ($gbl_dbf_relatives as $relkey => $relvalue)
                // loop all relative fields
                foreach ($relvalue as $rk => $rv)
                    // same database as actual database?
                    if ($rv == $gbl_db_data[$dbix]['dbname'])
                        {
                        // sql area
                        try
                            {
                            // prepare sql command
                            if (array_key_exists ('multi', $gbl_dbf_data[$relkey][$rk]['array_options']) && $gbl_dbf_data[$relkey][$rk]['array_options']['multi'] == 1)
                                {
                                // prepare sql command
                                $stmt_t = $dbh -> prepare ("SELECT count(*) as `cnt` FROM `".$gbl_db_data[$relkey]['dbname']."` WHERE `".$gbl_dbf_data[$relkey][$rk]['tblkey']."` LIKE :recordid AND `delflag`=0");

                                // bind id
                                $stmt_t -> bindValue (':recordid', '%,'.$record['id'].',%');
                                }
                            else
                                {
                                // prepare sql command
                                $stmt_t = $dbh -> prepare ("SELECT count(*) as `cnt` FROM `".$gbl_db_data[$relkey]['dbname']."` WHERE `".$gbl_dbf_data[$relkey][$rk]['tblkey']."`=:recordid AND `delflag`=0");

                                // bind id
                                $stmt_t -> bindParam (':recordid', $record['id']);
                                }

                            // execute sql command
                            $stmt_t -> execute();

                            // get total number of records
                            $rowcnt = $stmt_t -> fetch(PDO::FETCH_ASSOC);
                            $count = $rowcnt['cnt'];

                            // get number of record and add to hint text
                            if ($count != 0)
                                {
                                // set type: blocked record
                                $recordresults[$record['id']]['type'] = 'related';

                                // set database name to whose records record is related to
                                $recordresults[$record['id']]['#'.$relkey.'-'.$rk]['dbname'] = $gbl_db_data[$relkey]['name'];

                                // set key name of related records
                                $recordresults[$record['id']]['#'.$relkey.'-'.$rk]['keyname'] = $gbl_dbf_data[$relkey][$rk]['name'];

                                // set number of related records
                                $recordresults[$record['id']]['#'.$relkey.'-'.$rk]['number'] = $count;
                                }
                            }

                        // error?
                        catch (Exception $error)
                            {
                            // close database handle
                            $dbh = null;

                            // log error message
                            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_t, $record['id']);

                            // set failure
                            echo '[{"pass":"dberror"}]';

                            // exit
                            exit;
                            }
                        }
            }
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

    // close database handle
    $dbh = null;

    // push ok message with block and relation information
    echo '[{"pass":"ok", "records":'.json_encode ($recordresults).'}]';
?>
