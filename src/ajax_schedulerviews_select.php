<?php
    // -----------------------------------------------------
    // module: sets scheduler view parameter
    // status: v6 finished
    // -----------------------------------------------------

    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get parameter

    // get view option
    $view = 'view_'.$_REQUEST['view'];


    // ----- set actual scheduler view data to 0/1

    // value = 0?
    if (!array_key_exists ($view, $gbl_actuser['pdb_settings']['scheduler']) || $gbl_actuser['pdb_settings']['scheduler'][$view] == 1)
        {
        // set value to 0
        $gbl_actuser['pdb_settings']['scheduler'][$view] = 0;

        // reset image
        $image = '';
        }
    else
        {
        // set value to 1
        $gbl_actuser['pdb_settings']['scheduler'][$view] = 1;

        // set image
        $image = 'done.gif';
        }

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok", "image":"'.$image.'"}]';
?>
