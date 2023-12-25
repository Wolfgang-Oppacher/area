<?php
    // -----------------------------------------------------
    // module: resize grid column
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get old and new column
    $columnid = $_REQUEST['columnid'];
    $width = $_REQUEST['width'];

    // get top/sub database flag
    if ($_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- save column width

    // reset fields array
    $pdbf = array();

    // loop through personal database to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        // if table view active, then save index (only fields active for table view)
        if ($value['tableactive'.$topsubflag] == 1)
            $pdbf[sprintf ("%03d", $value['columnorder'])] = $key;

    // sort indices
    ksort ($pdbf);

    // reset column index
    $colix = 0;

    // loop column headers
    foreach ($pdbf as $key => $value)
        {
        // i.c. set old column id
        if ($colix == $columnid)
            $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnwidth'] = $width;

        // increase column index
        $colix++;
        }

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok"}]';
?>
