<?php
    // -----------------------------------------------------
    // module: delivers view settings for personal database
    // status: v6 working: scheduler part
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- scheduler data
    if ($_REQUEST['tab'] == 'scheduler')
        {
        // set success message
        echo '[{"pass": "ok"';

//

        // close array
        echo '}]';

        // return
        return;
        }


    // ----- initialization

    // get database indices
    include ('mod_dbix_get.php');

    // get top/sub database flag
    if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- print database data

    // set success message
    echo '[{"pass": "ok"';

    // set view flag
    if ($gbl_actuser['pdb_settings'][$pdbix]['view'] == 0)
        echo ',"view":false';
    else
        echo ',"view":true';

    // set path
    echo ',"path":"'.$gbl_actuser['pdb_settings'][$pdbix]['path'].'"';

    // set tree order
    echo ',"treeorder":'.$gbl_actuser['pdb_settings'][$pdbix]['treeorder'];

    // set database view name
    echo ',"name":"'.$gbl_actuser['pdb_settings'][$pdbix]['name'].'"';

    // set record name
    echo ',"recordname":"'.$gbl_actuser['pdb_settings'][$pdbix]['recordname'].'"';

    // set grid filter flag
    if ($gbl_actuser['pdb_settings'][$pdbix]['gridfilterflg'] == 1)
        echo ',"gridfilterflg":true';
    else
        echo ',"gridfilterflg":false';

    // set edit record tab view flag
    if ($gbl_actuser['pdb_settings'][$pdbix]['editrecordtabviewflg'] == 1)
        echo ',"editrecordtabviewflg":true';
    else
        echo ',"editrecordtabviewflg":false';

    // reset counter
    $pdbcnt = 0;

    // loop through all personal databases
    foreach ($gbl_actuser['pdb_settings'] as $key => $value)
        if ($value['dbname'] == $gbl_actuser['pdb_settings'][$pdbix]['dbname'])
            $pdbcnt++;

    // print parameter
    echo ',"remove":"'.$pdbcnt.'"';


    // --- open order array (sort array 1)

    // get order array
    $orderarray = array_map ('trim', explode(',', $gbl_actuser['pdb_settings'][$pdbix]['order']));

    // open array
    echo ',"sortarray1":[{"text":" ","value":""}';

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // field access?
        if ($dbf_v['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$dbf_v['right'], 0, 0, 0, 0, 0) == 0)
                continue;

        // set field and name
        if (array_key_exists (0, $orderarray) && $orderarray[0] == $dbf_v['key'])
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'","selected":true}';
        else
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'"}';
        }

    // close order array
    echo ']';


    // --- open order array (sort array 2)
    echo ',"sortarray2":[{"text":" ","value":""}';

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // field access?
        if ($dbf_v['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$dbf_v['right'], 0, 0, 0, 0, 0) == 0)
                continue;

        // set field and name
        if (array_key_exists (1, $orderarray) && $orderarray[1] == $dbf_v['key'])
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'","selected":true}';
        else
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'"}';
        }

    // close order array
    echo ']';


    // --- open order array (sort array 3)
    echo ',"sortarray3":[{"text":" ","value":""}';

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // field access?
        if ($dbf_v['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$dbf_v['right'], 0, 0, 0, 0, 0) == 0)
                continue;

        // set field and name
        if (array_key_exists (2, $orderarray) && $orderarray[2] == $dbf_v['key'])
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'","selected":true}';
        else
            echo ',{"text":"'.$dbf_v['name'].'","value":"'.$dbf_v['key'].'"}';
        }

    // close order array
    echo ']';

    // open order direction array
    echo ',"order_add":[';

    // set field and name
    if ($gbl_actuser['pdb_settings'][$pdbix]['order_add'] == '')
        echo '{"text":"'.UI_WINDOW_VIEWSETTING_TABBAR_DATABASE_SORTDIR1.'","value":"","selected":true},{"text":"'.UI_WINDOW_VIEWSETTING_TABBAR_DATABASE_SORTDIR2.'","value":"DESC"}';
    else
        echo '{"text":"'.UI_WINDOW_VIEWSETTING_TABBAR_DATABASE_SORTDIR1.'","value":""},{"text":"'.UI_WINDOW_VIEWSETTING_TABBAR_DATABASE_SORTDIR2.'","value":"DESC","selected":true}';

    // close order array
    echo ']';


    // --- put general default values

    // open default array
    echo ',"defaults_general":{';

    // default path
    echo '"path":"'.$gbl_db_data[$dbix]['path'].'"';

    // default tree order
    echo ',"treeorder":"'.$gbl_db_data[$dbix]['treeorder'].'"';

    // default grid filter flag
    echo ',"gridfilterflg":"'.$gbl_db_data[$dbix]['gridfilterflg'].'"';

    // default edit record tab view flag
    echo ',"editrecordtabviewflg":"'.$gbl_db_data[$dbix]['editrecordtabviewflg'].'"';

    // default name
    echo ',"name":"'.$gbl_db_data[$dbix]['name'].'"';

    // default record name
    echo ',"recordname":"'.$gbl_db_data[$dbix]['recordname'].'"';

    // get default order array
    $orderarray = array_map ('trim', explode(',', $gbl_db_data[$dbix]['order']));

    // default order fields 1
    if (array_key_exists (0, $orderarray))
        echo ',"order1":"'.$orderarray[0].'"';
    else
        echo ',"order1":""';

    // default order fields 2
    if (array_key_exists (1, $orderarray))
        echo ',"order2":"'.$orderarray[1].'"';
    else
        echo ',"order2":""';

    // default order fields 3
    if (array_key_exists (2, $orderarray))
        echo ',"order3":"'.$orderarray[2].'"';
    else
        echo ',"order3":""';

    // default order addendum
    echo ',"order_add":"'.$gbl_db_data[$dbix]['order_add'].'"';

    // close
    echo '}';


    // --- put field grid default values

    // open grid data
    echo ',"defaults_grid":[';

    // reset index
    $i = 1;

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // field access?
        if ($dbf_v['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$dbf_v['right'], 0, 0, 0, 0, 0) == 0)
                continue;

        // i.c. add delimiter
        if ($i++ > 1)
            echo ',';

        // open record
        echo '{"key":"'.$dbf_k.'"';

        // print order
        echo ',"order":"'.$dbf_v['order'].'"';

        // print column order
        echo ',"columnorder":"'.$dbf_v['columnorder'].'"';

        // print column width
        echo ',"columnwidth":"'.$dbf_v['table_width'].'"';

        // column filter type
        echo ',"columnfilter":"'.$dbf_v['table_filter'].'"';

        // table view flag
        if ($dbf_v['array_flags']['tableview'.$topsubflag] == 0)
            echo ',"tableview_flg":""';
        else
            echo ',"tableview_flg":"'.($dbf_v['array_flags']['tableview'.$topsubflag] & 1).'"';

        // print view flag
        if (    $dbf_v['array_flags']['preview'.$topsubflag] == 0
            && ($dbf_v['array_flags']['form_create'.$topsubflag] & 3) == 0
            && ($dbf_v['array_flags']['form_copy'.$topsubflag]  & 3) == 0
            && ($dbf_v['array_flags']['form_edit'.$topsubflag] & 3) == 0)
            echo ',"printview_flg":""';
        else
            echo ',"printview_flg":"1"';

        // same/new column type
        if ($dbf_v['type'] == 'table')
            echo ',"column_flg":""';
        else
            // old/new column?
            if ($dbf_v['column'] == '' || $dbf_v['column'] == 'oldcolumn')
                echo ',"column_flg":"oldcolumn"';
            else
                echo ',"column_flg":"newcolumn"';

        // portions (create/edit record)
        echo ',"portion":"'.$dbf_v['tags'].'"';
        echo ',"portion_edit":"'.$dbf_v['tags_edit'].'"';

        // close record
        echo '}';
        }

    // close grid data
    echo ']';

    // close array
    echo '}]';
?>
