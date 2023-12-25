<?php
    // -----------------------------------------------------
    // module: saves record data
    // status: v6 working
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // connect to database
    $dbh = pdoconnect (1);

    // get index to record
    $recordid = $_REQUEST['recordid'];

    // get access type
    $accesstype = $_REQUEST['accesstype'];

    // build portion access type
    if ($accesstype == 'create')
        $accesstype_portion = '';
    else
        $accesstype_portion = '_edit';

    // get top/sub database flag
    if (array_key_exists ('topsubflag', $_REQUEST) && $_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';

    // get portion name
// ?
    $portion = urlcomponentdecode ($_REQUEST['_preset__portion']);

    // reset record array
    $record = array();
    $record['projectid'] = 0;
    $record['editors'] = '';








    // ----- i.c. get record

    // i.c. get record
    if ($accesstype != 'create')
        {
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
                msg_log (LOG_ERROR_HIDE, 'no record data found', $gbl_db_data[$dbix]['recordname'], $dbh, $stmt, $recordid);

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
            msg_log (LOG_ERROR_HIDE, 'database error 1', $error->getMessage(), $dbh, $stmt, $recordid);

            // set error message
            echo '[{"pass":"dberror"}]';

            // exit
            exit;
            }
        }


    // ----- right to create/append/copy/edit record?

    // check right
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$accesstype, $recordid, $record['projectid'], $record['editors'], 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- build index to field

    // reset fields arrays
    $pdbf = array();
    $pdbf_columns = array();

    // reset sql parameter string
    $sqlpar = '';

    // reset new record array
    $record_new = array();

    // reset tabulator number
    $tabnr = 0;

    // reset jobs array
    $jobs = array();

    // loop through all personal database fields to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        {
        // field access right?
        if ($gbl_dbf_data[$dbix][$key]['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$gbl_dbf_data[$dbix][$key]['right'], $recordid, $record['projectid'], $record['editors'], 0, 0) == 0)
                continue;

        // table view active?
        if ($value['tableactive'.$topsubflag] == 1)
            // save index
            $pdbf_columns[sprintf ("%03d", $value['columnorder'])] = $key;

        // field always to be saved?
        if ($gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$accesstype.$topsubflag] != 4)
            {
            // active for user and editable
            if ($value['printactive'.$topsubflag] == 0 || $gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$accesstype.$topsubflag] < 2)
                continue;

            // i.c. check portion list
            if ($value['portion'.$accesstype_portion] != '')
                {
                // get array
                $arr = array_map ('trim', explode (',', $value['portion'.$accesstype_portion]));

                // key not of type projectid, key not `reldbname`/`relrecid` (mandatory for create record) or not creation mode?
                if (($gbl_dbf_data[$dbix][$key]['type'] != 'projectid' && $gbl_dbf_data[$dbix][$key]['tblkey'] != 'reldbname' && $gbl_dbf_data[$dbix][$key]['tblkey'] != 'relrecid') || $accesstype != 'create')
                    // portion given?
                    if ($portion != '')
                        if (!in_array ($portion, $arr))
                            continue;
                }
            else
                // key not of type projectid, key not `reldbname`/`relrecid` (mandatory for create record) or not creation mode?
                if (($gbl_dbf_data[$dbix][$key]['type'] != 'projectid' && $gbl_dbf_data[$dbix][$key]['tblkey'] != 'reldbname' && $gbl_dbf_data[$dbix][$key]['tblkey'] != 'relrecid') || $accesstype != 'create')
                    // portion given?
                    if ($portion != '')
                        continue;
            }

        // save key to array
        $pdbf[sprintf ("%03d", $value['order'])] = $key;

        // saveable key?
        if (strpos ($key, 'nn_') === 0)
            continue;

        // key already exists?
        if (strpos ($sqlpar, '`'.$gbl_dbf_data[$dbix][$key]['tblkey'].'`') === TRUE)
            continue;

        // add key to sql parameter string
        $sqlpar .= ', `'.$gbl_dbf_data[$dbix][$key]['tblkey'].'`=:'.$gbl_dbf_data[$dbix][$key]['tblkey'];
        }

    // sort index
    ksort ($pdbf_columns);


    // ----- save record

    // sql area
    try
        {
        // prepare sql command
        if ($accesstype != 'edit')
            $stmt = $dbh -> prepare ("INSERT INTO `".$gbl_db_data[$dbix]['dbname']."` SET `delflag`=0".$sqlpar);
        else
            $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `blockdatetime`='2000-01-01 00:00:00'".$sqlpar." WHERE ".$gbl_db_filter[$dbix]." AND `id`=:recordid AND `delflag`=0");

        // bind parameters to sql command string
        foreach ($pdbf as $i => $key)
            {
            // saveable key?
            if (strpos ($key, 'nn_') === 0)
                continue;

            // key already exists?
            if (array_key_exists ($gbl_dbf_data[$dbix][$key]['tblkey'], $record_new))
                continue;

            // get formatted value
            $formvalue = db_formvalue_get ($dbh, $dbix, $pdbix, $key, $record_new, $record, $tabnr, $accesstype, $jobs);

            // get value and i.c. trim spaces/commata (start and end)
            switch ($gbl_dbf_data[$dbix][$key]['array_flags']['save_trim'.$topsubflag])
                {
                // trim spaces and quotes
                case 3:

                    // replace quotes (")
                    while (($pos = stripos ($formvalue, '"')) !== false)
                        $formvalue = substr_replace ($formvalue, '', $pos, 1);

                    // replace quotes (')
                    while (($pos = stripos ($formvalue, '\'')) !== false)
                        $formvalue = substr_replace ($formvalue, '', $pos, 1);

                // trim spaces
                case 1:

                    // replace double spaces
                    while (($pos = stripos ($formvalue, '  ')) !== false)
                        $formvalue = substr_replace ($formvalue, ' ', $pos, 2);

                    // trim spaces
                    $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = trim ($formvalue);

                    // break
                    break;

                // trim commata
                case 2:

                    // trim spaces
                    $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = trim ($formvalue);

                    // i.c. trim beginning comma
                    if (strlen ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']]) > 1)
                        if ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']][0] == ',')
                            $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = substr ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']], 1);

                    // i.c. trim ending comma
                    if (strlen ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']]) > 1)
                        if ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']][strlen ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']]) - 1] == ',')
                            $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = substr ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']], 0, strlen ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']]) - 1);

                    // break
                    break;

                // default
                default:

                    // get value without a change
                    $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = $formvalue;
                }

            // sub database and project key?
// TODO: only create record -> save existing projectids, when edit/copy
// also check key? (Feld vom Typ "Projektzuordnung" soll sichtbar sein, wenn es sich nicht um die Projektzuordnung des Datensatzes handelt, sondern bspw. um "Akquisezuordnung")
//            if ($gbl_dbf_data[$dbix][$key]['type'] == 'projectid' && $gbl_dbf_data[$dbix][$key]['key'] == 'projectid' && isset ($_REQUEST['_preset_relpdbix']) && $_REQUEST['_preset_relpdbix'] != '')
//                $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = $_REQUEST['_preset_relprojectid'];

            // value to be saved and no value given?
            if (   $gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$accesstype.$topsubflag] == 4
                && ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] == ''
                    || ($gbl_dbf_data[$dbix][$key]['type'] == 'projectid' && ($record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] == ',0,' || $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] == ''))))
                // default given?
                if ($gbl_dbf_data[$dbix][$key]['default'] != '')
                    // set default string and replace representants
                    $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] = string_repr_replace ($dbix, $gbl_dbf_data[$dbix][$key]['default'], 0, $record[$gbl_dbf_data[$dbix][$key]['key']], $accesstype, $jobs, $key, $record, 1);
                else
                    // log error message
                    msg_log (LOG_ERROR_HIDE, 'no default value given in database \''.$gbl_db_data[$dbix]['dbname'].'\' for field \''.$key.'\' ('.$gbl_dbf_data[$dbix][$key]['name'].')', '', 0, 0, $recordid);

            // empty integer value? -> NULL
            if ($gbl_dbf_data[$dbix][$key]['type'] == 'integer' && $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] == '' && $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] != 0)
                // bind null
                $stmt -> bindValue (':'.$gbl_dbf_data[$dbix][$key]['tblkey'], null, PDO::PARAM_INT);
            else
                // bind value
                if (  ($gbl_dbf_data[$dbix][$key]['type'] == 'relative' || $gbl_dbf_data[$dbix][$key]['type'] == 'prid')
                    && $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']] == '')
                    $stmt -> bindValue (':'.$gbl_dbf_data[$dbix][$key]['tblkey'], 0);
                else
                    $stmt -> bindValue (':'.$gbl_dbf_data[$dbix][$key]['tblkey'], $record_new[$gbl_dbf_data[$dbix][$key]['tblkey']]);
            }

        // bind record id parameter
        if ($accesstype == 'edit')
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
        msg_log (LOG_ERROR_HIDE, 'database error 2', $error->getMessage(), $dbh, $stmt, $recordid);

        // set error message
        echo '[{"pass":"dberror"}]';

        // exit
        exit;
        }

// save old record index
    $recordid_old = $recordid;

    // get record id, if new record
    if ($accesstype != 'edit')
        $recordid = $dbh -> lastInsertId();

    // save record id
    $record_new['id'] = $recordid;

    // i.c. save project id
    if ($gbl_db_data[$dbix]['projectfilterflag'] == 1 && $record_new['projectid'] == '')
        $record_new['projectid'] = $record['projectid'];

    // i.c. add entry to editors field: create
// TODO: evt. anders -> kein erneuter Zugriff?
    if ($accesstype == 'create')
        editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'created', '', '');

    // i.c. add entry to editors field: copy
// TODO: evt. anders -> kein erneuter Zugriff?
    if ($accesstype == 'copy')
        editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'copied', '', 'source record index '.$recordid_old);

    // i.c. add entry to editors field: edit
// TODO: evt. anders -> kein erneuter Zugriff?
    if ($accesstype == 'edit')
        {
        // reset keys list
        $keys = '';

        // loop through all personal database fields
        foreach ($pdbf as $i => $key)
            {
            // right field type?
            if ($gbl_dbf_data[$dbix][$key]['type'] == 'validityflag' || strpos ($key, 'nn_') === 0)
                continue;

            // field not only to be saved?
            if ($gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$accesstype.$topsubflag] != 4)
                // field content different?
                if ($record[$key] != $record_new[$key])
                    {
                    // i.c. skip key: type relative and no change
                    if ($gbl_dbf_data[$dbix][$key]['type'] == 'relative' && $record[$key] == 0 && $record_new[$key] == '')
                        continue;

                    // add key
                    if ($keys == '')
                        $keys = $key;
                    else
                        $keys .= ','.$key;
                    }
            }

        // i.c. add entry to editors field: edit
        if ($keys != '')
            editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $recordid, 'edited', $keys, '');
        }


    // ----- process jobs

    // do jobs?
    if (!empty ($jobs))
        {
        // parse postsave jobs
        foreach ($jobs as $k => $job)
            {
            // job switch
            switch ($job['type'])
                {
                // update project id matrix
                case 'matrix_projectids_update':

                    // update matrix project id data
                    matrix_projectid_data_save ($dbh, $dbix, $recordid, $job['projectid_array']);

                    // break
                    break;

                // set unique code YYYY-NNN
                case 'editors_addrecord':

                    // add entry to editors field
// TODO: evt. anders -> kein erneuter Zugriff?
                    editors_addrecord ($dbh, $gbl_actuser['id'], $dbix, $job['recordid'], $job['recordtype'], $job['key'], '');

                    // break
                    break;

                // set unique code YYYY-NNN
                case 'auto_prid':

                    // get year
                    $year = date ("Y", time());

                    // sql area: get highest unique code
                    try
                        {
                        // prepare sql command
                        $stmt_prid = $dbh -> prepare ("SELECT `".$job['key']."` FROM `".$gbl_db_data[$dbix]['dbname']."` ORDER BY `".$job['key']."` DESC");

                        // execute sql command
                        $stmt_prid -> execute();

                        // get data
                        if (!($pridrecord = $stmt_prid -> fetch()))
                            // build default code
                            $prid = $year.'001';
                        else
                            {
                            // actual year?
                            if (($year * 1000) < $pridrecord[$job['key']])
                                {
                                // overflow?
                                if ($pridrecord[$job['key']] == 999)
                                    {
                                    // close database
                                    $dbh = null;

                                    // log error message
                                    msg_log (LOG_ERROR_HIDE, 'prid overflow in database '.$gbl_db_data[$dbix]['dbname'], $job['key'], 0, 0, $recordid);

                                    // set error message
                                    echo '[{"pass":"dberror"}]';

                                    // exit
                                    exit;
                                    }
                                else
                                    // set new id
                                    $prid = $pridrecord[$job['key']] + 1;
                                }
                            else
                                // build default code
                                $prid = $year.'001';
                            }
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 5', $error->getMessage(), $dbh, $stmt_prid, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

                    // sql area: set unique code to record
                    try
                        {
                        // prepare sql command: set unique code
                        $stmt_prid = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['key']."`=:prid WHERE `id`=:recordid");

                        // bind parameter
                        $stmt_prid -> bindParam (':recordid', $recordid);
                        $stmt_prid -> bindParam (':prid', $prid);

                        // execute sql command
                        $stmt_prid -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 6', $error->getMessage(), $dbh, $stmt_prid, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

                    // break
                    break;

                // set project id to record itself / set access right for saving user
                case 'auto_projectid':

                    // sql area: set project id of record to itself
                    try
                        {
                        // prepare sql command: set project id to record itself
                        $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['key']."`=:projectid WHERE `id`=:recordid");

                        // bind parameter
                        $stmt -> bindValue (':recordid', $recordid);
                        $stmt -> bindValue (':projectid', ','.$recordid.',');

                        // save project id
                        $record_new['projectid'] = ','.$recordid.',';

                        // execute sql command
                        $stmt -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 7', $error->getMessage(), $dbh, $stmt, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

                    // sql area: set access right for saving user
                    try
                        {
                        // prepare sql command: set project id to record itself
                        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `projectid`=CONCAT(`projectid`, :projectid), `accessid`=REPLACE(`accessid`, '}', :accessid) WHERE `projectid` NOT LIKE :projectid_w AND `id`=:recordid");

                        // get profile id
                        if ($gbl_actuser['accessid_auto_profile'] != '')
                            $profile_id = $gbl_actuser['accessid_auto_profile'];
                        else
                            {
                            // get right array
                            $idarray = json_decode ($gbl_actuser['accessid'], TRUE);
                            reset ($idarray);
                            $profile_id = current ($idarray);
                            }

                        // bind parameter
                        $stmt -> bindValue (':recordid', $gbl_actuser['id']);
                        $stmt -> bindValue (':projectid', $recordid.',');
                        $stmt -> bindValue (':projectid_w', '%,'.$recordid.',%');
                        $stmt -> bindValue (':accessid', ',"'.$recordid.'":'.$profile_id.'}');

                        // execute sql command
                        $stmt -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 8', $error->getMessage(), $dbh, $stmt, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

                    // actualize access ids of all contact records according to auto parameters
// TODO: später ausführen lassen (nach return)
                    admintask_run ($dbix, 'admin_contacts_actualizeaccessids');

                    // actualize project ids of all records of all databases (with project id) according to auto parameters
// TODO: später ausführen lassen (nach return)
                    admintask_run ($dbix, 'admin_databases_actualizeprojectids');

                    // remove cache data (all users data) -> all user data will be reloaded
                    delete_cache (0, 0);

                    // break
                    break;

                // create/update training group login account
                case 'auto_traininggroup':

                    // initialize group id
                    $gid = 0;

                    // search training rights group
                    foreach ($gbl_profilerights as $k => $v)
                        // right group with training group right found?
                        if (($gbl_profilerights[$k]['traininggroup'] & 3) == 3)
                            $gid = $k;

                    // no group id found?
                    if ($gid == 0)
                        {
                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'no training rights group found', '', 0, 0, $recordid);

                        // break
                        break;
                        }

                    // get project ids
                    $pid = array_map ('trim', explode (',', $record_new['projectid']));

                    // open access ids list
                    $accessid = '{';

                    // loop project id array
                    foreach ($pid as $k => $v)
                        {
                        // project id empty?
                        if ($v == '')
                            continue;

                        // set access id
                        if ($accessid != '{')
                            $accessid .= ',"'.$v.'":'.$gid;
                        else
                            $accessid .= '"'.$v.'":'.$gid;
                        }

                    // close access ids list
                    $accessid .= '}';

                    // sql area
                    try
                        {
                        // prepare sql command
                        $stmt = $dbh -> prepare ("INSERT INTO `contacts` SET `loginname`=:loginname,"
                                                                            ."`password`=:password,"
                                                                            ."`projectid`=:projectid,"
                                                                            ."`accessid`=:accessid,"
                                                                            ."`loginactive`=1,"
                                                                            ."`tags`='|auto_traininggroup|',"
// TODO:
//                                                                            ."`editors`=:editors,"
                                                                            ."`delflag`=0");

                        // bind parameter
                        $stmt -> bindValue (':loginname', password_create (1, 2).'-'.$recordid);
                        $stmt -> bindValue (':password',  password_create (0, 8));
                        $stmt -> bindValue (':projectid', $record_new['projectid']);
                        $stmt -> bindValue (':accessid',  $accessid);
// TODO:
//                        $stmt -> bindValue (':editors',   '{"'.date ("Y-m-d H:i:s", time()).'":"'.$gbl_actuser['id'].'"}');

                        // execute sql command
                        $stmt -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 9', $error->getMessage(), $dbh, $stmt, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

                    // get last index
                    $lid = $dbh -> lastInsertId();

                    // initialize contact record array
                    $record_contact = array();

                    // build rtp values of participant contact record
                    record_rtp_build ($dbh, $gbl_db_index['contacts'], $lid, $record_contact);

                    // sql area
                    try
                        {
                        // prepare sql command: set relative id to contact record
                        $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['key']."`=:lid WHERE `id`=:id");

                        // bind parameter
                        $stmt -> bindParam (':id',  $recordid);
                        $stmt -> bindParam (':lid', $lid);

                        // execute sql command
                        $stmt -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // close database
                        $dbh = null;

                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error 10', $error->getMessage(), $dbh, $stmt, $recordid);

                        // set error message
                        echo '[{"pass":"dberror"}]';

                        // exit
                        exit;
                        }

// TODO: also update access ids (when record edited)

                    // break
                    break;

                // spread data over records
                case 'spread':

// spread auch projectid_links

                    // no value given? -> check, if data to be spread from other records
                    if ($job['fieldvalue'] == '|' || $job['fieldvalue'] == '')
                        {
                        // find first record with organization in tags
                        try
                            {
                            // prepare sql command
                            if ($job['titlekey'] == '')
                                $stmt = $dbh -> prepare ("SELECT `".$job['fieldkey']."` FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE `".$job['fieldkey']."` LIKE :relation AND `delflag`=0");
                            else
                                $stmt = $dbh -> prepare ("SELECT `".$job['fieldkey']."`,`".$job['titlekey']."` FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE `".$job['fieldkey']."` LIKE :relation AND `delflag`=0");

                            // bind parameter
                            if ($job['fieldtype'] == 'tags')
                                $stmt -> bindValue (':relation', '%|'.$record_new[$job['relationkey']].'|%');
                            else
// anders -> Unterscheidung nach type -> auch an anderen Stellen in diesem case
                                $stmt -> bindValue (':relation', $record_new[$job['relationkey']]);

                            // execute sql command
                            $stmt -> execute();

                            // get data
                            if (!($record_s = $stmt -> fetch()))
                                break 1;
                            }

                        // error?
                        catch (Exception $error)
                            {
                            // close database
                            $dbh = null;

                            // log error message
                            msg_log (LOG_ERROR_HIDE, 'database error 11', $error->getMessage(), $dbh, $stmt, $recordid);

                            // set error message
                            echo '[{"pass":"dberror"}]';

                            // exit
                            exit;
                            }

                        // write found data to actual record
                        try
                            {
                            // prepare sql command
                            if ($job['titlekey'] == '')
                                $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['fieldkey']."`=:key,`rtp_".$job['fieldkey']."`=:rtp_key WHERE `id`=:id");
                            else
                                $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['fieldkey']."`=:key,`rtp_".$job['fieldkey']."`=:rtp_key,`".$job['titlekey']."`=:title,`rtp_".$job['titlekey']."`=:rtp_title WHERE `id`=:id");

                            // bind parameter (I)
                            $stmt -> bindParam (':id', $recordid);
                            $stmt -> bindParam (':key',$record_s[$job['fieldkey']]);
                            $stmt -> bindValue (':rtp_key', implode ('; ', array_map ('trim', explode ('|', trim ($record_s[$job['fieldkey']], " \t\n\r\0\x0B|")))));

                            // bind parameter (II)
                            if ($job['titlekey'] != '')
                                {
                                $stmt -> bindParam (':title', $record_s[$job['titlekey']]);
                                $stmt -> bindParam (':rtp_title', $record_s[$job['titlekey']]);
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
                            msg_log (LOG_ERROR_HIDE, 'database error 12', $error->getMessage(), $dbh, $stmt, $recordid);

                            // set error message
                            echo '[{"pass":"dberror"}]';

                            // exit
                            exit;
                            }
                        }
                    else
                        {
// spread auch projectid_links
// auch records bereinigen, die nicht mehr zur Liste gehören

                        // get array with organization names
// anders -> Unterscheidung nach type
                        $org_arr = array_map ('trim', explode ('|', trim ($record_new[$job['fieldkey']], " \t\n\r\0\x0B|")));

                        // reset filter and counter
                        $filter = '';
                        $n = 0;

                        // loop organization array
                        foreach ($org_arr as $id0 => $org)
                            // filter empty?
                            if ($filter == '')
                                $filter = '`'.$job['relationkey'].'`=:org'.$n++;
                            else
                                $filter .= ' OR `'.$job['relationkey'].'`=:org'.$n++;

                        // write data to actual record
                        try
                            {
                            // prepare sql command
                            if ($job['titlekey'] == '')
                                $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['fieldkey']."`=:key,`rtp_".$job['fieldkey']."`=:rtp_key WHERE `delflag`=0 AND (".$filter.")");
                            else
                                $stmt = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `".$job['fieldkey']."`=:key,`rtp_".$job['fieldkey']."`=:rtp_key,`".$job['titlekey']."`=:title,`rtp_".$job['titlekey']."`=:rtp_title WHERE `delflag`=0 AND (".$filter.")");

                            // bind parameter (I)
                            $stmt -> bindParam (':key', $record_new[$job['fieldkey']]);
                            $stmt -> bindValue (':rtp_key', implode ('; ', array_map ('trim', explode ('|', trim ($record_new[$job['fieldkey']], " \t\n\r\0\x0B|")))));

                            // bind parameter (II)
                            if ($job['titlekey'] != '')
                                {
                                $stmt -> bindParam (':title', $record_new[$job['titlekey']]);
                                $stmt -> bindParam (':rtp_title', $record_new[$job['titlekey']]);
                                }

                            // reset counter
                            $n = 0;

                            // loop organization array
                            foreach ($org_arr as $id0 => $org)
                                $stmt -> bindValue (':org'.$n++, $org);

                            // execute sql command
                            $stmt -> execute();
                            }

                        // error?
                        catch (Exception $error)
                            {
                            // close database
                            $dbh = null;

                            // log error message
                            msg_log (LOG_ERROR_HIDE, 'database error 13', $error->getMessage(), $dbh, $stmt, $recordid);

                            // set error message
                            echo '[{"pass":"dberror"}]';

                            // exit
                            exit;
                            }
                        }

                    // break
                    break;

                // default
                default:

                    // log error message
                    msg_log (LOG_ERROR_HIDE, 'unknown '.$accesstype.' closing job', $job['type'], 0, 0, $recordid);
                }
            }
        }

    // save old record
    $record_old = $record;


    // ----- build rtp data
// double (siehe unten): wie vermeiden?
    record_rtp_build ($dbh, $dbix, $recordid, $record);

// TODO: add record to editors


    // ----- process record save event

    // check if we are supposed to trigger a saverecord event
    if ($gbl_db_data[$dbix]['saverecord_event'] && $gbl_db_data[$dbix]['saverecord_fields'])
        {
/*
        // turn field list string into an array
        $saverecord_fields = array_map ('trim', explode (',', $gbl_db_data[$dbix]['saverecord_fields']));

        // assemble event URL
        $save_url = '';

        // add specified parameters to URL
        foreach ($saverecord_fields as $k => $key)
            {
            if (array_key_exists($key, $record) && $record[$key] !== '')
                {
                // initialize or append to URL string
                if ($save_url == '')
                    $save_url = $gbl_db_data[$dbix]['saverecord_event'].'?';
                else
                    $save_url .= '&';

                // append URL parameter
                $save_url .= $key.'='.urlencode (trim ($record[$key]));
                }
            }

        // create stream context for HTTP(S) connection
        $stream_context = stream_context_create (array ('http' => array('timeout' => 10)));

        // execute saverecord event and receive status reply in JSON format
        // for example: {"success":true,"info":{"code":0,"message":"publish_course.php: Success"}}
        // all "code" values greater than 0 are error codes

        $reply = json_decode (file_get_contents ($save_url, false, $stream_context), true);

        // error?
        if (!$reply['success'])
            // log error message
            msg_log (LOG_ERROR_HIDE, 'Fehler bei Saverecord-Event: '.$reply['info']['message'], $save_url, 0, '', $recordid);
*/
        }


    // ----- set watch job

    // sql area
    try
        {
/*
        // prepare sql command
        $stmt_w = $dbh -> prepare ("INSERT INTO `jobs` SET `dbid`=:dbid, `recordid`=:recordid, `type`=:type, `userid`=:userid");

        // set parameter
        $stmt_w -> bindValue (':dbid', $dbix);
        $stmt_w -> bindValue (':recordid', $recordid);
        $stmt_w -> bindValue (':type', $accesstype);
        $stmt_w -> bindValue (':userid', $gbl_actuser['id']);

        // execute sql command
        $stmt_w -> execute();
*/
        }

    // error?
    catch (Exception $error)
        {
        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error 14', $error->getMessage(), $dbh, $stmt_w, $recordid);
        }


    // ----- close database

    // close database
    $dbh = null;


    // ----- give process result

    // set success message, record id and grid row contents
    echo '[{"pass":"ok", "recordid":"'.$recordid.'"}]';

// register shutdown function
register_shutdown_function ('record_save_postwork', $dbix, $pdbix, $pdbf, $recordid, $record, $record_old, $accesstype);


// return
    return;





// -----------------------------------------------
// function: postwork for record save
// input:    database index
//           personal database index
//           array of database fields
//           record id
//           record array
//           old record array
//           access type
// state:    v6 working
// -----------------------------------------------

function record_save_postwork ($dbix, $pdbix, $pdbf, $recordid, $record, $record_old, $accesstype)
    {
    // include global variable definitions
    include ('global_vars.php');


    // ----- open database

    // connect to database
    $dbh = pdoconnect (1);








    // ----- build rtp data
    record_rtp_build ($dbh, $dbix, $recordid, $record);


    // ----- process nn_ keys (table, ...)

    // reset jobs array
    $jobs = array();

    // loop fields
    foreach ($pdbf as $i => $key)
        // nn_ key?
        if (strpos ($key, 'nn_') === 0)
            // process key
            $formvalue = db_formvalue_get ($dbh, $dbix, $pdbix, $key, $record, $record_old, 0, $accesstype, $jobs);


    // ----- build super rtp data

    // super database?
// TODO: anders -> generisch, alle Abhängigkeiten abarbeiten
    if (array_key_exists ('reldbname', $gbl_dbf_data[$dbix]) && (array_key_exists ('relrecid', $gbl_dbf_data[$dbix]) || array_key_exists ('relrecid_record', $gbl_dbf_data[$dbix])))
        {
        // reset record array
        $record_t = array();

        // reset flag
        $flg = 0;

        // search to do list state key
// TODO. warum ist diese Abfrage nötig?
if (array_key_exists ($record['reldbname'], $gbl_db_index))
        foreach ($gbl_dbf_data[$gbl_db_index[$record['reldbname']]] as $k => $dbfrec_t)
            // field type found?
			if ($dbfrec_t['type'] == 'subrecord_value')
                // set flag
                $flg = 1;

        // refresh super record?
        if ($flg == 1)
            {
            // get relative record ids
            $relrecids_array = array_map ('trim', explode (',', $record['relrecid']));

            // loop all records
            foreach ($relrecids_array as $k => $id)
                // id given?
                if ($id != '' && $id != 0)
                    // refresh rtp values of super record
                    record_rtp_build ($dbh, $gbl_db_index[$record['reldbname']], $id, $record_t);
            }
        }


    // ----- refresh task list values

    // refresh task list state
// TODO: if reldbname and relrecid exists; otherwise refresh tasks for super record (e.g. if state of training has changed)
	tasklist_refresh ($dbix, $record['reldbname'], $record['relrecid']);


    // ----- process database post tasks

    // process database post tasks
// TODO: erneutes Lesen des Records in dieser Funktion evt. nicht nötig
    databases_postwork ($dbix, $recordid, $record);


    // ----- check validity of record
// TODO: erneutes Lesen des Records in dieser Funktion evt. nicht nötig
// TODO: check evt. subrecords auf homogene projectid-Anbindung, groupaccess, useraccess
/*
        // ----- check projectid of related fields / generic tables
            // get trainer ids
            $arr = array_map ('trim', explode (',', $v_t['trainerids']));

            // flip keys and values
            $arr = array_flip ($arr);

            // save trainer ids
            $trainerarray = $trainerarray + $arr;

            // loop training place ids
            foreach ($trainingplacearray as $id => $v)
                {
                // id empty?
                if ($id == '')
                    continue;

                // sql area
                try
                    {
                    // prepare sql command
                    $stmt_t = $dbh -> prepare ("SELECT `organization` FROM `contacts` WHERE `projectid` NOT LIKE :projectid AND `id`=:id AND `delflag`=0");

                    // bind id parameter
                    $stmt_t -> bindParam (':id', $id);
                    $stmt_t -> bindValue (':projectid', '%,'.$record['projectid'].',%');

                    // execute sql command
                    $stmt_t -> execute();

                    // get data
                    if (($rec = $stmt_t -> fetch()))
                        $results[$key] .= $rec['organization'].': Anbindung an das Projekt der Veranstaltung herstellen; ';
                    }

                // error?
                catch (Exception $error)
                    {
                    // log error message
                    msg_log (LOG_ERROR_HIDE, 'database error 15', $error->getMessage(), $dbh, $stmt_t, $id);
                    }
                }
-----

        // sql area - check false project binding
        try
            {
            // prepare sql command
            $stmt_t = $dbh -> prepare ("SELECT `id` FROM `tasks` WHERE `reldbname`='trainings' AND `relrecid`=:id AND `projectid` <> :projectid AND `delflag`=0");

            // bind id parameter
            $stmt_t -> bindValue (':id', $record['id']);
            $stmt_t -> bindValue (':projectid', $record['projectid']);

            // execute sql command
            $stmt_t -> execute();

            // record found?
            if ($row = $stmt_t -> fetch())
                $results[$key] .= 'mindestens eine Aufgabe mit anderer Projektzuordnung angebunden; ';
            }

        // error?
        catch (Exception $error)
            {
            // log error message
            msg_log (LOG_ERROR_HIDE, 'Datenbankfehler', $error->getMessage(), $dbh, $stmt_t, $record['id']);
            }
*/

    // check validity of record
    databases_integritycheck ($dbix, $recordid, $record, 0);


    // ----- (re-)build scheduler data

    // scheduler data in database?
    if (array_key_exists ($dbix, $gbl_dbf_scheduler))
        {
        // (re-)build record data
        schedulerdata_fill_record ($dbh, $dbix, $gbl_dbf_scheduler[$dbix], $record, 1);

        // build calendars
        calendar_ics_build ($dbh, $dbix, $recordid);
        }


    // ----- handle cache

// set as parameter in admindatabases

    // i.c. remove cache records
    if (   $gbl_db_data[$dbix]['dbname'] == 'admindatabases'
		|| $gbl_db_data[$dbix]['dbname'] == 'admindatabasesfields'
		|| $gbl_db_data[$dbix]['dbname'] == 'formrecords'
		|| $gbl_db_data[$dbix]['dbname'] == 'formrecordfields'
		|| $gbl_db_data[$dbix]['dbname'] == 'adminrights'
		|| $gbl_db_data[$dbix]['dbname'] == 'adminfiletypes'
		|| $gbl_db_data[$dbix]['dbname'] == 'general'
		|| $gbl_db_data[$dbix]['dbname'] == 'projects'
		|| $gbl_db_data[$dbix]['dbname'] == 'subprojects')
        // remove cache records
        delete_cache (0, 1);


    // ----- close database

    // close database
    $dbh = null;

    // return
    return;
    }
?>
