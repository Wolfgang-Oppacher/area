<?php
    // -----------------------------------------------------
    // module: save databases tree node state (open/closed)
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get parameter

    // get node id and state
    $id = $_REQUEST['id'];
    $state = $_REQUEST['state'];

    // i.c. correct state
    if ($state == -1)
        $state = 0;


    // ----- set parameter

    // node state changed?
    if (array_key_exists ('treenodes', $gbl_actuser['pdb_settings']))
        if (array_key_exists ($id, $gbl_actuser['pdb_settings']['treenodes']))
            if ($gbl_actuser['pdb_settings']['treenodes'][$id] == $state)
                {
                // set success message
                echo '[{"pass": "ok"}]';

                // exit
                exit;
                }

    // set new state
    $gbl_actuser['pdb_settings']['treenodes'][$id] = $state;

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok"}]';
?>
