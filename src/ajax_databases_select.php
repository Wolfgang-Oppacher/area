<?php
    // -----------------------------------------------------
    // module: switch personal database view on/off
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- process command

    // activate all databases?
    if ($_REQUEST['pdbix'] == '0')
        {
        // loop all personal databases and activate view
        foreach ($gbl_actuser['pdb_settings'] as $k => $v)
            $gbl_actuser['pdb_settings'][$k]['view'] = 1;
        }
    else
        {
        // get database indices
        include ('mod_dbix_get.php');

        // toggle view flag of personal database
        $gbl_actuser['pdb_settings'][$pdbix]['view'] = 1 - $gbl_actuser['pdb_settings'][$pdbix]['view'];
        }

    // save view settings
    include ('mod_viewsetting_save.php');

    // sent ok message
    echo '[{"pass":"ok"}]';
?>
