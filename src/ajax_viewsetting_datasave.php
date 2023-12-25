<?php
    // -----------------------------------------------------
    // module: saves view settings for personal database
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // administration right?
    $adminright = checkright_profile (1, 0, 'administration', 0, 0, 0, 0, 0);

    // i.c. set new personal database index
    if ($_REQUEST['savenew'] == 1)
        {
        // reset index
        $maxix = 0;

        // loop personal databases
        foreach ($gbl_actuser['pdb_settings'] as $key => $value)
            if ($maxix < $key)
                $maxix = $key;

        // set maximum new index
        $pdbix = $maxix + 1;
        }

    // get top/sub database flag
    if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- save view settings to user data array

    // set database parameter (I)
    if ($_REQUEST['relpdbix'] == '')
        {
        $gbl_actuser['pdb_settings'][$pdbix]['view'] = intval ($_REQUEST['view']);
        $gbl_actuser['pdb_settings'][$pdbix]['path'] = strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['path'])));
        $gbl_actuser['pdb_settings'][$pdbix]['treeorder'] = intval ($_REQUEST['treeorder']);
        }

    // set database parameter (II)
    $gbl_actuser['pdb_settings'][$pdbix]['dbname'] = $gbl_db_data[$dbix]['dbname'];
    $gbl_actuser['pdb_settings'][$pdbix]['name'] = strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['name'])));
    $gbl_actuser['pdb_settings'][$pdbix]['recordname'] = strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['recordname'])));
    $gbl_actuser['pdb_settings'][$pdbix]['views'] = $gbl_db_data[$dbix]['views'];
    $gbl_actuser['pdb_settings'][$pdbix]['accessright'] = 1;
    $gbl_actuser['pdb_settings'][$pdbix]['gridfilterflg'] = intval ($_REQUEST['gridfilterflg']);
    $gbl_actuser['pdb_settings'][$pdbix]['editrecordtabviewflg'] = intval ($_REQUEST['editrecordtabviewflg']);

    // get order 1
    $order = strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['order1'])));

    // i.c. get order 2
    if ($_REQUEST['order2'] != '')
        $order .= ','.strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['order2'])));

    // i.c. get order 3
    if ($_REQUEST['order3'] != '')
        $order .= ','.strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['order3'])));

    // save order parameter
    $gbl_actuser['pdb_settings'][$pdbix]['order'] = $order;
    $gbl_actuser['pdb_settings'][$pdbix]['order_add'] = strip_tags (stripquotes (urlcomponentdecode ($_REQUEST['order_add'])));


    // ----- save fields grid parameter

    // get fields grid data
    $gridxml = urlcomponentdecode ($_REQUEST['grid_fields']);
    $xml = simplexml_load_string ($gridxml);

    // loop all fields grid data
    foreach ($xml->row as $k => $v)
        {
        // get attributes array
        $attributes = $v->attributes();
        $key = (string) $attributes['id'];

        // save field name
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['fieldname'] = strip_tags (stripquotes ((string) $v->cell[2]));

        // save filter mode
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['filtermode'] = (string) $v->cell[3];

        // save filter text
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['filtertext'] = strip_tags (stripquotes ((string) $v->cell[4]));

        // save table active flag
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['tableactive'.$topsubflag] = (string) intval ($v->cell[5]);

        // save column order
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['columnorder'] = (string) intval ($v->cell[6]);

        // save column width
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['columnwidth'] = (string) $v->cell[7];

        // save column filter type
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['columnfiltertype'] = strip_tags (stripquotes ((string) $v->cell[8]));

        // save print active flag
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['printactive'.$topsubflag] = (string) intval ($v->cell[9]);

        // save order
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['order'] = (string) intval ($v->cell[10]);

        // save tab name
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['tabname'] = strip_tags (stripquotes ((string) $v->cell[11]));

        // save column type
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['column'] = strip_tags (stripquotes ((string) $v->cell[12]));

        // save portion name/list (create record)
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion'] = strip_tags (stripquotes ((string) $v->cell[13]));

        // save portion name/list (edit record)
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion_edit'] = strip_tags (stripquotes ((string) $v->cell[14]));
        }

    // save view settings
    include ('mod_viewsetting_save.php');


    // ----- save users grid parameter

    // connect to database
    $dbh = pdoconnect (1);

    // get users grid data
    $gridxml = urlcomponentdecode ($_REQUEST['grid_users']);
    $xml = simplexml_load_string ($gridxml);

    // loop all users grid data
    foreach ($xml->row as $k => $v)
        {
        // get attributes array
        $attributes = $v->attributes();
        $userid = (string) $attributes['id'];


        // --- set watch settings

        // sql area
        try
            {
            // prepare sql command
            $stmt = $dbh -> prepare ("SELECT `id`, `watchids`, `accessid` FROM `contacts` WHERE `loginactive`=1 AND `loginname`!='' AND `password`!='' AND `delflag`=0 AND (`tags`!='|auto_traininggroup|' OR `tags` IS NULL) AND `id`=:userid");

            // bind parameter
            $stmt -> bindParam (':userid', $userid);

            // execute sql command
            $stmt -> execute();

            // get record data
            if ($record = $stmt -> fetch())
                {
                // get right array
                $idarray = json_decode ($record['accessid'], TRUE);

                // error?
                if (!is_array ($idarray))
                    // reset array
                    $idarray = array();

                // reset result
                $right_list = 0;

                // loop project ids
                foreach ($idarray as $id => $rightid)
                    // does right token to list database exist in rights array?
                    if (isset ($gbl_profilerights[$rightid]['db_'.$gbl_db_data[$dbix]['dbname'].'_list']))
                        // is right to list database granted?
                        if ($gbl_profilerights[$rightid]['db_'.$gbl_db_data[$dbix]['dbname'].'_list'] != 0)
                            {
                            // set result
                            $right_list = 1;

                            // break
                            break;
                            }

                // set read only tag
                if ($right_list != 0 && ($adminright != 0 || $gbl_actuser['id'] == $record['id']))
                    {
                    // sql area
                    try
                        {
                        // prepare sql command
                        $stmt_t = $dbh -> prepare ("UPDATE `contacts` SET `watchids`=:watchids WHERE `id`=:userid AND `delflag`=0");

                        // get watch array
                        $watcharray = json_decode ($record['watchids'], TRUE);

                        // update actual watch setting
                        $watcharray[$gbl_db_data[$dbix]['dbname']] = intval ($v->cell[4]);

                        // set watch ids
                        $watchids = json_encode ($watcharray);

                        // bind parameters to sql command
                        $stmt_t -> bindParam (':watchids', $watchids);
                        $stmt_t -> bindParam (':userid',   $userid);

                        // execute sql command
                        $stmt_t -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_t, 0);
                        }

                    // i.c. save cache
                    if ($userid == $gbl_actuser['id'])
                        {
                        // make json string
                        $gbl_actuser['watchids'] = $watchids;

                        // save cache
                        save_cache (2);
                        }
                    }
                }
            }

        // error?
        catch (Exception $error)
            {
            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, 0);
            }


        // --- reset view settings?
        if (intval ($v->cell[5]) == 1)
            {
            // sql area
            try
                {
                // prepare sql command
                $stmt = $dbh -> prepare ("SELECT `id`, `viewsettings` FROM `contacts` WHERE `loginactive`=1 AND `loginname`!='' AND `password`!='' AND `delflag`=0 AND (`tags`!='|auto_traininggroup|' OR `tags` IS NULL) AND `id`=:userid");

                // bind parameter
                $stmt -> bindParam (':userid', $userid);

                // execute sql command
                $stmt -> execute();

                // get record data
                if ($record = $stmt -> fetch())
                    {
                    // get right array
                    $idarray = json_decode ($record['viewsettings'], TRUE);

                    // error?
                    if (!is_array ($idarray))
                        // reset array
                        $idarray = array();

                    // loop personal databases: does database exist in personal array?
                    foreach ($idarray as $pkey => $pvalue)
                        // database found?
                        if ($pvalue['dbname'] == $gbl_db_data[$dbix]['dbname'])
                            // remove settings for database
                            unset ($idarray[$pkey]);

                    // sql area
                    try
                        {
                        // prepare sql command
                        $stmt_t = $dbh -> prepare ("UPDATE `contacts` SET `viewsettings`=:viewsettings WHERE `id`=:userid AND `delflag`=0");

                        // get watch array
                        $viewsettings = json_encode ($idarray);

                        // bind parameters to sql command
                        $stmt_t -> bindParam (':viewsettings', $viewsettings);
                        $stmt_t -> bindParam (':userid', $userid);

                        // execute sql command
                        $stmt_t -> execute();
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_t, 0);
                        }

                    // delete cache
                    delete_cache (0, 0);
                    }
                }

            // error?
            catch (Exception $error)
                {
                // log error message
                msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, 0);
                }
            }
        }

    // close database
    $dbh = null;

    // set success message
    echo '[{"pass": "ok"}]';
?>
