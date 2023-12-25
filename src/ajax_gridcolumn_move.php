<?php
    // -----------------------------------------------------
    // module: move grid column
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- get database parameter

    // get database indices
    include ('mod_dbix_get.php');

    // get old and new column
    $oldcolumn = $_REQUEST['oldcolumn'];
    $newcolumn = $_REQUEST['newcolumn'];

    // get top/sub database flag
    if ($_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- re-arrange column order

    // reset fields arrays
    $pdbf   = array();
    $pdbf_c = array();

    // loop through personal database to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        {
        // if table view active, then save index (only fields active for table view)
        if ($value['tableactive'.$topsubflag] == 1)
            $pdbf[sprintf ("%03d", $value['columnorder'])] = $key;

        // save complete index (all fields)
        $pdbf_c[sprintf ("%03d", $value['columnorder'])] = $key;
        }

    // sort indices
    ksort ($pdbf);
    ksort ($pdbf_c);

    // reset column index
    $colix = 0;

    // reset column ids
    $oldcolumnid = '';
    $newcolumnid = '';

    // loop column headers
    foreach ($pdbf as $key => $value)
        {
        // i.c. set old column id
        if ($colix == $oldcolumn)
            $oldcolumnid = $value;

        // i.c. set new column id
        if ($colix == $newcolumn)
            $newcolumnid = $value;

        // increase column index
        $colix++;
        }

    // new data given?
    if ($oldcolumnid == $newcolumnid || $oldcolumn == $newcolumn)
        {
        // set success message
        echo '[{"pass": "ok"}]';

        // return
        return;
        }

    // data correct?
    if ($oldcolumnid == '' || $newcolumnid == '')
        {
        // set parameter error message
        echo '[{"pass": "parerror"}]';

        // return
        return;
        }

    // get target column
    $tcol = $gbl_actuser['pdb_settings'][$pdbix]['fields'][$newcolumnid]['columnorder'];

    // reset range flag
    $rangeflg = 0;

    // re-arrange order from left to right
    if ($oldcolumn < $newcolumn)
        {
        // re-order old column to new column position
        foreach ($pdbf_c as $key => $value)
            {
            // i.c. re-arrange column order
            if ($rangeflg == 1)
                $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnorder'] = intval ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnorder']) - 1;

            // start?
            if ($value == $oldcolumnid)
                $rangeflg = 1;

            // end?
            if ($value == $newcolumnid)
                $rangeflg = 0;
            }

        // set order position of old column to new position
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$oldcolumnid]['columnorder'] = $tcol;
        }
    // re-arrange order from right to left
    else
        {
        // re-order old column to new column position
        foreach ($pdbf_c as $key => $value)
            {
            // start?
            if ($value == $newcolumnid)
                $rangeflg = 1;

            // end?
            if ($value == $oldcolumnid)
                $rangeflg = 0;

            // i.c. re-arrange column order
            if ($rangeflg == 1)
                $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnorder'] = intval ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnorder']) + 1;
            }

        // set order position of old column to new position
        $gbl_actuser['pdb_settings'][$pdbix]['fields'][$oldcolumnid]['columnorder'] = $tcol;
        }

    // save view settings
    include ('mod_viewsetting_save.php');

    // set success message
    echo '[{"pass": "ok"}]';
?>
