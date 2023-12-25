<?php
    // -----------------------------------------------------
    // module: delivers scheduler parameter
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- set general parameter

    // print general parameter
    $res = '[{"pass":"ok",';


    // ----- set views

    // reset array string
    $opts = 'Array(';

    // reset delimiter flag
    $dlflg = 1;

    // loop all scheduler databases
    foreach ($gbl_dbf_scheduler as $dbid => $dbrecord)
        {
        // loop all views
        foreach ($gbl_db_data[$dbid]['views_array'] as $key => $value)
            // scheduler views?
            if (array_key_exists ('scheduler', $value) && $value['scheduler'] == 1)
                {
                // add delimiter
                if ($opts != 'Array(')
                    $opts .= ',';

                // i.c. add optical delimiter
                if ($dlflg == 0)
                    $opts .= 'Array(\'\', \'separator\', \'\', \'\'),';

                // add entry
                if (!array_key_exists ('view_'.$value['id'], $gbl_actuser['pdb_settings']['scheduler']) || $gbl_actuser['pdb_settings']['scheduler']['view_'.$value['id']] == 1)
                    $opts .= 'Array(\'schedulerviews_select&view='.$value['id'].'\', \'obj\', \''.stripquotes ($value['title']).'\', \'done.gif\')';
                else
                    $opts .= 'Array(\'schedulerviews_select&view='.$value['id'].'\', \'obj\', \''.stripquotes ($value['title']).'\')';

                // set delimiter flag
                $dlflg = 1;
                }

        // reset delimiter flag
        $dlflg = 0;
        }

    // i.c. add delimiter
    if ($opts != 'Array(')
        $opts .= ',Array(\'\', \'separator\', \'\', \'\'),';

    // add entry
    if (!array_key_exists ('view_holidaysandpublicdays', $gbl_actuser['pdb_settings']['scheduler']) || $gbl_actuser['pdb_settings']['scheduler']['view_holidaysandpublicdays'] == 1)
        $opts .= 'Array(\'schedulerviews_select&view=holidaysandpublicdays'.'\', \'obj\', \''.UI_SCHEDULER_HOLIDAYSANDPUBLICDAYS.'\', \'done.gif\')';
    else
        $opts .= 'Array(\'schedulerviews_select&view=holidaysandpublicdays'.'\', \'obj\', \''.UI_SCHEDULER_HOLIDAYSANDPUBLICDAYS.'\')';

    // close options
    $opts .= ')';

    // print parameter
    $res .= '"filter":"'.$opts.'",';


    // ----- set list to abo records

    // reset array string
    $opts = 'Array(';

    // loop all scheduler databases
    foreach ($gbl_dbf_scheduler as $dbid => $dbrecord)
        {
        // right to abo record?
        if (checkright_profile (1, $dbid, 'db_'.$gbl_db_data[$dbid]['dbname'].'_abo', 0, 0, 0, 0, 0) == 0)
            continue;

        // add delimiter
        if ($opts != 'Array(')
            $opts .= ',';

        // add entry
        $opts .= 'Array(\'handle_database_abo&dbname='.$gbl_db_data[$dbid]['dbname'].'\', \'obj\', \''.$gbl_db_data[$dbid]['name'].' '.UI_SCHEDULER_ABORECORDS.'\')';
        }

    // close options
    $opts .= ')';

    // print parameter
    $res .= '"menu_aborecords":"'.$opts.'",';


    // ----- set list to watch records

    // reset array string
    $opts = 'Array(';

    // loop all scheduler databases
    foreach ($gbl_dbf_scheduler as $dbid => $dbrecord)
        {
        // right to watch record?
        if (checkright_profile (1, $dbid, 'db_'.$gbl_db_data[$dbid]['dbname'].'_watch', 0, 0, 0, 0, 0) == 0)
            continue;

        // add delimiter
        if ($opts != 'Array(')
            $opts .= ',';

        // add entry
        $opts .= 'Array(\'handle_database_watch&dbname='.$gbl_db_data[$dbid]['dbname'].'\', \'obj\', \''.$gbl_db_data[$dbid]['name'].' '.UI_SCHEDULER_WATCHRECORDS.'\')';
        }

    // close options
    $opts .= ')';

    // print parameter
    $res .= '"menu_watchrecords":"'.$opts.'",';


    // ----- set list to create records

    // reset array string
    $opts = 'Array(';

    // loop through personal database
    foreach ($gbl_actuser['pdb_settings'] as $key => $value)
        // scheduler database?
        if (array_key_exists ('dbix', $value) && array_key_exists ($value['dbix'], $gbl_dbf_scheduler))
            {
            // right to watch record?
            if (checkright_profile (1, $value['dbix'], 'db_'.$gbl_db_data[$value['dbix']]['dbname'].'_create', 0, 0, 0, 0, 0) == 0)
                continue;

            // add delimiter
            if ($opts != 'Array(')
                $opts .= ',';

            // add entry
            $opts .= 'Array(\'handle_record_create&pdbix='.$key.'&portion=\', \'obj\', \''.$value['recordname'].' '.UI_SCHEDULER_CREATERECORDS.'\')';
            }

    // close options
    $opts .= ')';

    // print parameter
    $res .= '"menu_createrecords":"'.$opts.'"';


    // ----- close database and return with result parameter and content

    // write and close parameter json array
    echo $res;
    echo '}]';
?>
