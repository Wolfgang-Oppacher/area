<?php
    // -----------------------------------------------------
    // module: process administration task
    // status: v6 working
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // command string and database ids given?
//    if (array_key_exists ('dbids', $_REQUEST) && array_key_exists ('cmd', $_REQUEST))
        // register shutdown function
//        register_shutdown_function ('admintask_run', $_REQUEST['dbids'], $_REQUEST['cmd']);
admintask_run ($_REQUEST['dbids'], $_REQUEST['cmd']);

    // set success
    echo '[{"pass":"ok"}]';

    // return
    return;
?>
