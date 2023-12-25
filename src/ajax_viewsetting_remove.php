<?php
    // -----------------------------------------------------
    // module: removes personal database (if at least one personal database of the real database is left)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');


    // ----- save view settings to user data array

    // remove view setting
    unset ($gbl_actuser['pdb_settings'][$pdbix]);

    // reset personal database index
    reset ($gbl_actuser['pdb_settings']);

    // get first personal database id
    $gbl_actuser['actpdbix'] = key ($gbl_actuser['pdb_settings']);

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok"}]';
?>
