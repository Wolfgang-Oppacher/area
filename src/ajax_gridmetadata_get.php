<?php
    // -----------------------------------------------------
    // module: delivers basic grid parameters to show database in grid
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // get top/sub database flag
    if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';


    // ----- get and save search text

    // search text given?
    if (urlcomponentdecode ($_REQUEST['searchtext']) != '[]')
        $gbl_actuser['pdb_settings'][$pdbix]['searchtext'] = trim (urlcomponentdecode ($_REQUEST['searchtext']));


    // ----- get and save filter

    // filter given?
    if ($_REQUEST['filter'] != '')
        $gbl_actuser['pdb_settings'][$pdbix]['filter'] = urlcomponentdecode ($_REQUEST['filter']);


    // ----- save personal database id and view settings to user data array

    // other than calendar, mail and related database? -> save personal database index
    if ($gbl_actuser['pdb_settings'][$pdbix]['dbname'] != 'calendar' && $gbl_actuser['pdb_settings'][$pdbix]['dbname'] != 'mails' && (!isset ($_REQUEST['relpdbix']) || $_REQUEST['relpdbix'] == ''))
        $gbl_actuser['actpdbix'] = $pdbix;

    // save view settings
    include ('mod_viewsetting_save.php');


    // ----- build grid parameter

    // reset extern sources array
    $sources_extern_arr = array();

    // extern sources data exists?
    if (array_key_exists ('sources_extern', $gbl_db_data[$dbix]['options_array']))
        // loop extern sources
        foreach ($gbl_db_data[$dbix]['options_array']['sources_extern'] as $es_k => $es_r)
            // extern source active?
            if ($es_r['active'] == 1)
                // save extern source
                $sources_extern_arr[$es_k] = $es_r;

    // preset footer
    $footer = $gbl_actuser['pdb_settings'][$pdbix]['name'].': {#stat_count}';

    // reset fields array
    $pdbf = array();

    // reset portion arrays
    $portionarr = array ();
    $portionarr_edit = array ();

    // loop through personal database to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        {
        // i.c. fill portion list
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion'] != '')
            {
            // get array
            $arr = array_map ('trim', explode (',', $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion']));

            // swap keys and values
            $arr = array_flip ($arr);

            // merge arrays
            $portionarr = $portionarr + $arr;
            }

        // i.c. fill edit portion list
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion_edit'] != '')
            {
            // get array
            $arr = array_map ('trim', explode (',', $gbl_actuser['pdb_settings'][$pdbix]['fields'][$key]['portion_edit']));

            // swap keys and values
            $arr = array_flip ($arr);

            // merge arrays
            $portionarr_edit = $portionarr_edit + $arr;
            }

        // if table view active, then save index
        if ($value['tableactive'.$topsubflag] == 1)
            $pdbf[sprintf ("%03d", $value['columnorder'])] = $key;
        }

    // sort index
    ksort ($pdbf);

    // reset counter and grid variables
    $i = 1;
    $headers = '';
    $widths  = '';
    $widths_min = '';
    $aligns  = '';
    $types   = '';
    $sorts   = '';
    $filters = '';
    $numberformats = '';

    // print headers
    foreach ($pdbf as $key => $value)
        {
        // print delimiter?
        if ($i++ > 1)
            {
            // add delimiter
            $headers .= ',';
            $widths  .= ',';
            $widths_min .= ',';
            $aligns  .= ',';
            $types   .= ',';
            $sorts   .= ',';
            $filters .= ',';
            $numberformats .= ',';

            // set footer
            $footer .= ',#cspan';
            }

        // add grid parameter
        $headers .= '<span title="'.escape_comma (stripquotes (linearize ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['fieldname'].': '.$gbl_dbf_data[$dbix][$value]['explanation'], 1))).'">'.$gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['fieldname'].'</span>';
        $filters .= $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnfiltertype'];
        $aligns  .= $gbl_dbf_data[$dbix][$value]['table_align'];

        // set width
        $widths .= $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['columnwidth'];

        // min width given?
        if ($gbl_dbf_data[$dbix][$value]['table_width_min'] == '')
            $widths_min .= '10';
        else
            $widths_min .= $gbl_dbf_data[$dbix][$value]['table_width_min'];

        // consider type
        switch ($gbl_dbf_data[$dbix][$value]['type'])
            {
            // image type
            case 'filetype':
            case 'evaluation':
            case 'validityflag':
            case 'imageflag':
                $types .= 'img';
                break;

            // integer type
            case 'integer':

                // option for table format given?
                if (!array_key_exists ('format_table', $gbl_dbf_data[$dbix][$value]['array_options']))
                    $types .= 'ro';
                else
                    {
                    // consider cell type
                    if (array_key_exists ('format_table_celltype', $gbl_dbf_data[$dbix][$value]['array_options']))
                        // set special type
                        $types .= $gbl_dbf_data[$dbix][$value]['array_options']['format_table_celltype'];
                    else
                        // set special format
                        $types .= 'ron';

                    // set number format
                    $numberformats .= urlcomponentencode ($gbl_dbf_data[$dbix][$value]['array_options']['format_table']);
                    }

                // break
                break;

            // table generic value?
            case 'table_generic_value':

                // sum integer type?
                if ($gbl_dbf_data[$dbix][$value]['array_options']['type'] == 'suminteger')
                    {
                    // option for table format given?
                    if (!array_key_exists ('format_table', $gbl_dbf_data[$dbix][$value]['array_options']))
                        $types .= 'ro';
                    else
                        {
                        // consider cell type
                        if (array_key_exists ('format_table_celltype', $gbl_dbf_data[$dbix][$value]['array_options']))
                            // set special type
                            $types .= $gbl_dbf_data[$dbix][$value]['array_options']['format_table_celltype'];
                        else
                            // set special format
                            $types .= 'ron';

                        // set number format
                        $numberformats .= urlcomponentencode ($gbl_dbf_data[$dbix][$value]['array_options']['format_table']);
                        }
                    }
                else
                    // set default type
                    $types .= 'ro';

                // break
                break;

            // default type
            default:
                $types .= 'ro';
            }

        // general type
        switch ($gbl_dbf_data[$dbix][$value]['type'])
            {
            // table generic value?
            case 'table_generic_value':

                // sub type
                switch ($gbl_dbf_data[$dbix][$value]['array_options']['type'])
                    {
                    // date format?
                    case 'startdate':
                    case 'enddate':
                    case 'indexdate':
                        $sorts .= 'date';
                        break;

                    // integer?
                    case 'suminteger':
                        $sorts .= 'int';
                        break;

                    // default
                    default:
                        $sorts .= 'str';
                    }

                // break
                break;

            // to do list state?
            case 'todoliststate':

            // checkbox
            case 'checkbox':

            // hits?
            case 'hits':

            // file size?
            case 'filesize':

            // date index
            case 'dateindex':

            // slider?
            case 'slider':

            // integer?
            case 'integer':
                $sorts .= 'int';
                break;

            // file state?
            case 'filestate':

            // date?
            case 'date':

            // datetime?
            case 'datetime':
                $sorts .= 'date';
                break;

            // default
            default:
                $sorts .= 'str';
            }
        }


    // ----- set general parameter

    // print general parameter
    $res  = '[{"pass":"ok",';
    $res .= '"name":"'.urlcomponentencode ($gbl_db_data[$dbix]['name']).'",';
    $res .= '"pname":"'.urlcomponentencode ($gbl_actuser['pdb_settings'][$pdbix]['name']).'",';
    $res .= '"type":"'.$gbl_db_data[$dbix]['type'].'",';
    $res .= '"recordname":"'.urlcomponentencode ($gbl_actuser['pdb_settings'][$pdbix]['recordname']).'",';

    // print grid parameter (I)
    $res .= '"headers":"'.urlcomponentencode ($headers).'",';
    $res .= '"widths":"'.$widths.'",';
    $res .= '"widths_min":"'.$widths_min.'",';
    $res .= '"aligns":"'.$aligns.'",';
    $res .= '"types":"'.$types.'",';
    $res .= '"sorts":"'.$sorts.'",';

    // print grid parameter (II)
    if ($gbl_db_data[$dbix]['dynamicloadingflg'] == 1 || $gbl_actuser['pdb_settings'][$pdbix]['gridfilterflg'] == 0)
        $res .= '"filters":"",';
    else
        $res .= '"filters":"'.$filters.'",';

    // set footer
    $res .= '"footer":"'.urlcomponentencode ($footer).'",';

    // set number formats
    $res .= '"numberformats":"'.$numberformats.'",';

    // right to change view settings?
    $res .= '"changeviewsettings_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_changeviewsettings', 0, 0, 0, 0, 0).'",';

    // right to create record?
    $res .= '"create_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_create', 0, 0, 0, 0, 0).'",';

    // right to delete record?
    $res .= '"delete_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_delete', 0, 0, 0, 0, 0).'",';

    // right to edit record?
    $res .= '"edit_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_edit', 0, 0, 0, 0, 0).'",';

    // right to copy record?
    $res .= '"copy_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_copy', 0, 0, 0, 0, 0).'",';

    // right to print records?
    $res .= '"print_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_printer', 0, 0, 0, 0, 0).'",';

    // right to export records?
    $res .= '"export_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_export', 0, 0, 0, 0, 0).'",';

    // right to export query results?
    $res .= '"queryresultsexport_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_queryresultsexport', 0, 0, 0, 0, 0).'",';


    // ----- set watch and abonnement parameter

    // right to abo database?
    $res .= '"abo_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_abo', 0, 0, 0, 0, 0).'",';

    // right to watch database?
    $res .= '"watch_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_watch', 0, 0, 0, 0, 0).'",';

    // right to send e-mails?
    $res .= '"sendmail_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_sendmail', 0, 0, 0, 0, 0).'",';


    // ----- set search parameter

    // set search parameter
    $res .= '"search_text":"'.urlcomponentencode ($gbl_actuser['pdb_settings'][$pdbix]['searchtext']).'",';


    // ----- set parameters for sub grid
    if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
        $relpar = '&dbname='.$gbl_db_data[$dbix]['dbname'].'&relpdbix='.intval ($_REQUEST['relpdbix']).'&relrecordid='.intval ($_REQUEST['relrecordid']).'&tabid='.urlcomponentencode($_REQUEST['tabid']).'&projectid='.stripquotes ($_REQUEST['projectid']);
    else
        $relpar = '';


    // ----- print portion names

    // print portions
    if (sizeof ($portionarr) == 0 && sizeof ($sources_extern_arr) == 0)
        $res .= '"portions":"",';
    else
        {
        // print portions array (I): create
        $res .= '"portions":"Array(Array(\'handle_record_create&pdbix='.$pdbix.'&portion=\',\'obj\',\''.$gbl_actuser['pdb_settings'][$pdbix]['recordname'].' '.UI_RECORDVIEW_CREATEPART2.'\')';

        // build portions array (II): create
        foreach ($portionarr as $k => $v)
            $res .= ',Array(\'handle_record_create&pdbix='.$pdbix.'&portion='.urlcomponentencode($k).'\',\'obj\',\''.$k.' '.UI_RECORDVIEW_CREATEPART2.'\')';

        // create link to extern source
        foreach ($sources_extern_arr as $k => $v)
            // add command for extern source
            $res .= ',Array(\'handle_record_create&pdbix='.$pdbix.'&portion=&externsourceid='.$v['id'].'\',\'obj\',\''.$v['record_name'].' '.UI_RECORDVIEW_CREATELINK.'\')';

        // print portions array (III): create
        $res .= ')",';
        }

    // print edit portions
    if (sizeof ($portionarr_edit) == 0)
        $res .= '"portions_edit":"",';
    else
        {
        // print portions array (I): edit
        if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
            $res .= '"portions_edit":"Array(Array(\'handle_record_edit&pdbix='.$pdbix.$relpar.'&recordid=&topsubflag=sub&portion=\',\'obj\',\''.$gbl_actuser['pdb_settings'][$pdbix]['recordname'].' '.UI_RECORDVIEW_EDITPART2.'\')';
        else
            $res .= '"portions_edit":"Array(Array(\'handle_record_edit&pdbix='.$pdbix.'&recordid=&topsubflag=sub&portion=\',\'obj\',\''.$gbl_actuser['pdb_settings'][$pdbix]['recordname'].' '.UI_RECORDVIEW_EDITPART2.'\')';

        // build portions array (II): edit
        foreach ($portionarr_edit as $k => $v)
            if (isset ($_REQUEST['relpdbix']) && $_REQUEST['relpdbix'] != '')
                $res .= ',Array(\'handle_record_edit&pdbix='.$pdbix.$relpar.'&recordid=&topsubflag=sub&portion='.urlcomponentencode($k).'\',\'obj\',\''.$k.' '.UI_RECORDVIEW_EDITPART2.'\')';
            else
                $res .= ',Array(\'handle_record_edit&pdbix='.$pdbix.'&recordid=&topsubflag=sub&portion='.urlcomponentencode($k).'\',\'obj\',\''.$k.' '.UI_RECORDVIEW_EDITPART2.'\')';

        // print portions array (III): edit
        $res .= ')",';
        }


    // ----- set view filter parameter

    // reset actual filter
    $filter_act = '';

    // preset options
    $opts = 'Array(';

    // add view type select field (selected)
    if ($gbl_actuser['pdb_settings'][$pdbix]['filter'] == '*' || $gbl_actuser['pdb_settings'][$pdbix]['filter'] == '')
        {
        // set actual filter
        $filter_act = '*';

        // set entry
        $opts .= 'Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=*\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALL.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\', \'done.gif\')';
        }
    else
        $opts .= 'Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=*\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALL.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\')';

    // i.c. insert filter read/unread
    if (array_key_exists ('read_key', $gbl_db_data[$dbix]))
        {
        // filter selected?
        if ($gbl_actuser['pdb_settings'][$pdbix]['filter'] == 'allread')
            {
            // set actual filer
            $filter_act = UI_TOOLBAR_DB_FILTERALLREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'];

            // set filter
            $opts .= ',Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=allread\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALLREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\', \'done.gif\')';
            }
        else
            $opts .= ',Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=allread\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALLREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\')';

        // filter selected?
        if ($gbl_actuser['pdb_settings'][$pdbix]['filter'] == 'allunread')
            {
            // set actual filer
            $filter_act = UI_TOOLBAR_DB_FILTERALLUNREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'];

            // set filter
            $opts .= ',Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=allunread\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALLUNREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\', \'done.gif\')';
            }
        else
            $opts .= ',Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter=allunread\', \'obj\', \''.UI_TOOLBAR_DB_FILTERALLUNREAD.' '.$gbl_actuser['pdb_settings'][$pdbix]['name'].'\')';
        }

    // add optional views parameter
    foreach ($gbl_db_data[$dbix]['views_array'] as $key => $value)
        {
        // scheduler view?
        if (array_key_exists ('scheduler', $value) && $value['scheduler'] == 1)
            continue;

        // i.c. add delimiter
        if ($opts != 'Array(')
            $opts .= ',';

        // filter selected?
        if ($gbl_actuser['pdb_settings'][$pdbix]['filter'] == $value['id'])
            {
            // set actual filter
            $filter_act = $value['title'];

            // set filter
            $opts .= 'Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter='.$value['id'].'\', \'obj\', \''.$value['title'].'\', \'done.gif\')';
            }
        else
            // set filter
            $opts .= 'Array(\'filter_set&pdbix='.$pdbix.$relpar.'&filter='.$value['id'].'\', \'obj\', \''.$value['title'].'\')';
        }

    // close options
    $opts .= ')';

    // print parameter
    $res .= '"filter":"'.$opts.'",';
    $res .= '"filter_act":"'.$filter_act.'",';


    // ----- set columns array

    // open array
    $columns = 'Array(Array(\'columns_select&pdbix='.$pdbix.$relpar.'&column=_default\', \'obj\', \'Standardspalten\', \'iconFilter.gif\')';

    // loop all fields
    foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
        {
        // field access?
        if ($dbf_v['right'] != '')
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$dbf_v['right'], 0, 0, 0, 0, 0) == 0)
                continue;

        // accessible for table?
        if ($dbf_v['array_flags']['tableview'.$topsubflag] == 0)
            continue;

        // set column
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$dbf_k]['tableactive'.$topsubflag] == 1)
            $columns .= ',Array(\'columns_select&pdbix='.$pdbix.$relpar.'&column='.$dbf_k.'\', \'obj\', \''.$gbl_actuser['pdb_settings'][$pdbix]['fields'][$dbf_k]['fieldname'].'\', \'done.gif\')';
        else
            $columns .= ',Array(\'columns_select&pdbix='.$pdbix.$relpar.'&column='.$dbf_k.'\', \'obj\', \''.$gbl_actuser['pdb_settings'][$pdbix]['fields'][$dbf_k]['fieldname'].'\')';
        }

    // close array
    $columns .= ')';

    // print parameter
    $res .= '"columns":"'.$columns.'",';


    // ----- documents generation info

    // set right and parameter for "create document"
    $res .= '"documentsgenerate_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_documentsgenerate', 0, 0, 0, 0, 0).'",';


    // ----- set actual record id

    // get actual record id
    if (!isset ($_REQUEST['recordid']) || $_REQUEST['recordid'] == '' || $_REQUEST['recordid'] == 0)
        $res .= '"actrecordid":"'.intval ($gbl_actuser['pdb_settings'][$pdbix]['actrecordid']).'",';
    else
        $res .= '"actrecordid":"'.intval ($_REQUEST['recordid']).'",';


    // ----- close database and return with result parameter and content

    // write and close parameter json array
    echo $res;
    echo '"pdbix":"'.$pdbix.'"}]';
?>
