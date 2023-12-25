<?php
    // -----------------------------------------------------
    // module: get view settings for window "record select"
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');


    // ----- get view settings

    // normal input field or grid?
    if ($_REQUEST['flag'] == 0)
        $searchtext = $gbl_actuser['pdb_settings'][$pdbix]['fields'][$_REQUEST['key']]['searchtext'];
    else
        $searchtext = $gbl_actuser['pdb_settings'][$pdbix]['fields'][$_REQUEST['key']][$_REQUEST['key_t'].'_searchtext'];

    // set success message
    echo '[{"pass": "ok", "searchtext":"'.urlcomponentencode ($searchtext).'", "username":"'.$gbl_actuser['loginname'].'", "actuserid":'.$gbl_actuser['id'].'}]';
?>
