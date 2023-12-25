<?php
    // -----------------------------------------------------
    // module: switch grid columns on/off
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get column
    $column = $_REQUEST['column'];

    // get top/sub database flag
    if ($_REQUEST['relpdbix'] == '')
        $topsubflag = '';
    else
        $topsubflag = '_sub';


    // ----- process column settings

    // default columns?
    if ($column != '_default')
        {
        // (de)select column
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$column]['tableactive'.$topsubflag] == 0)
            $gbl_actuser['pdb_settings'][$pdbix]['fields'][$column]['tableactive'.$topsubflag] = 1;
        else
            {
            // reset column counter
            $cnt = 0;

            // loop through personal database to test, if at least two columns are active
            foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
                if ($value['tableactive'.$topsubflag] == 1)
                    $cnt++;

            // disable column, if at least two columns are active
            if ($cnt > 1)
                $gbl_actuser['pdb_settings'][$pdbix]['fields'][$column]['tableactive'.$topsubflag] = 0;
            }
        }
    else
        // loop all fields
        foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
            // table view set?
            $gbl_actuser['pdb_settings'][$pdbix]['fields'][$dbf_v['key']]['tableactive'.$topsubflag] = ($dbf_v['array_flags']['tableview'.$topsubflag] & 1);

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok"}]';
?>
