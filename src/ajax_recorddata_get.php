<?php
    // -----------------------------------------------------
    // module: delivers record data
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization

    // set error level
    if (!isset ($print_type))
        $print_type = '';

    // i.c. start processes
    if ($print_type != 'raw')
        include ('mod_init_ajax.php');

    // get database indices
    include ('mod_dbix_get.php');

    // connect to database
    $dbh = pdoconnect (1);

    // get index to record
    if ($_REQUEST['recordid'])
        $recordid = $_REQUEST['recordid'];
    else
        $recordid = 0;

    // get access type
    $accesstype = $_REQUEST['cmd'];

    // build portion access type
    if ($accesstype == 'create')
        $accesstype_portion = '';
    else
        $accesstype_portion = '_edit';

    // get active tab
    $activetab = $_REQUEST['activetab'];

    // reset portion name
    if (!isset ($_REQUEST['portion']))
        $portion = '';
    else
        $portion = urlcomponentdecode ($_REQUEST['portion']);

    // reset message (1)
    if (!isset ($message))
        $message = '';

    // reset message (2)
    if (!isset ($message_html))
        $message_html = '';

    // set error level
    if (!isset ($error_level))
        $error_level = 0;

    // set error flag
    $error_flg = 0;

    // reset result
    $res = '';
    $form_open = '';

    // get top/sub database flag
    if (isset ($_REQUEST['topsubflag']) && $_REQUEST['topsubflag'] != '')
        $topsubflag = '_sub';
    else
        $topsubflag = '';

    // list of input fields which have user-defined evaluation code attached
    $dynamic_fields = array();

    // reset record array
    $record = array();


    // ----- get and index record

    // i.c. get record
    if ($accesstype != 'create')
        {
        // sql area
        try
            {
            // prepare sql command
            $stmt_record = $dbh -> prepare ("SELECT `".$gbl_db_data[$dbix]['dbname']."`.* FROM `".$gbl_db_data[$dbix]['dbname']."` WHERE ".$gbl_db_filter[$dbix]." AND `".$gbl_db_data[$dbix]['dbname']."`.`id`=:recordid AND `".$gbl_db_data[$dbix]['dbname']."`.`delflag`=0");

            // bind id parameter to sql command string
            $stmt_record -> bindParam (':recordid', $recordid);

            // execute sql command
            $stmt_record -> execute();

            // get data
            if (!($record = $stmt_record -> fetch()))
                {
                // close database
                $dbh = null;

                // give error?
                if ($error_level != 1)
                    // i.c. set failure
                    if ($print_type != 'raw')
                        echo '[{"pass": "norecord"}]';

                // set error flag
                $error_flg = 1;

                // set error message
                $message = ERROR_RECORD1A.' '.$gbl_db_data[$dbix]['recordname'].'-'.ERROR_RECORD1B;
                $message_html = ERROR_RECORD1A.' '.printhtml ($gbl_db_data[$dbix]['recordname']).'-'.ERROR_RECORD1B;

                // return
                return;
                }
            }

        // error?
        catch (Exception $error)
            {
            // close database
            $dbh = null;

            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_record, $recordid);

            // i.c. set failure
            if ($print_type != 'raw')
                echo '[{"pass":"dberror"}]';

            // set error message
            $message = ERROR_DATABASE;
            $message_html = ERROR_DATABASE;

            // set error flag
            $error_flg = 1;

            // return
            return;
            }


        // --- save record id

        // i.c. save record id
        if ($accesstype == 'print' && $print_type != 'raw' && $gbl_actuser['pdb_settings'][$pdbix]['actrecordid'] != intval ($recordid))
            // save actual record id
            $gbl_actuser['pdb_settings'][$pdbix]['actrecordid'] = intval ($recordid);

        // sql area
        try
            {
            // prepare sql command
            $stmt_matrix = $dbh -> prepare ("SELECT `projectid`,`subprojectid` FROM `matrix_projectids` WHERE `relrecordid`=:relrecordid AND `reldbix`=".$dbix);

            // bind id parameter to sql command string
            $stmt_matrix -> bindParam (':relrecordid', $recordid);

            // execute sql command
            $stmt_matrix -> execute();

            // get data
            while ($record_t = $stmt_matrix -> fetch())
                $record['matrix_data'][] = $record_t;
            }

        // error?
        catch (Exception $error)
            {
            // close database
            $dbh = null;

            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_matrix, $recordid);

            // i.c. set failure
            if ($print_type != 'raw')
                echo '[{"pass":"dberror"}]';

            // set error message
            $message = ERROR_DATABASE;
            $message_html = ERROR_DATABASE;

            // set error flag
            $error_flg = 1;

            // return
            return;
            }
        }


    // ----- right to create/append/copy/edit record?

    // project id given?
    if (!array_key_exists ('projectid', $record))
        $record['projectid'] = 0;

    // project id passed?
    if (array_key_exists ('preset_relprojectid', $_REQUEST))
        $record['projectid'] = $_REQUEST['preset_relprojectid'];

    // check right
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$accesstype, $recordid, $record['projectid'], $record['editors'], 0, 0) == 0)
        {
        // close database
        $dbh_record = null;

        // i.c. set failure
        if ($print_type != 'raw')
            echo '[{"pass":"noright"}]';

        // set error message
        $message = ERROR_RIGHT;
        $message_html = ERROR_RIGHT;

        // set error flag
        $error_flg = 1;

        // return
        return;
        }


    // ----- get validity data

    // get validity array (I)
// TODO: 'validityarray' -> options
    if (array_key_exists ('validityarray', $record))
        $validityarray = json_decode ($record['validityarray'], TRUE);
    else
        $validityarray = array();

    // get validity array (III)
    $record['validityarray'] = $validityarray;


    // ----- print toolbar menu

    // print toolbar menu
    switch ($accesstype)
        {
        // --- output type: print
        case 'print':

            // get right flag
            $freed_rightflag = checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_freeddimension', $recordid, $record['projectid'], $record['editors'], 0, 0);

            // right to set free dimension of record?
            $res .= ',"freeddimension_right":"'.$freed_rightflag.'"';

            // reset freed flag
            $freed_flag = 0;

            // right to free content?
            if ($freed_rightflag != 0)
                {
                // compare normal and fdd content
                foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
                    // field type 'editor'?
                    if ($dbf_v['type'] != 'editors')
                        // compare to normal value?
                        if ($dbf_v['array_options']['freeddimension_source'] == 'norm')
                            {
                            // compare content
                            if ($record['fdd_'.$dbf_v['key']] != $record[$dbf_v['key']])
                                // set freed flag
                                $freed_flag = 1;
                            }
                        else
                            {
                            // compare content
                            if ($record['fdd_'.$dbf_v['key']] != $record['rtp_'.$dbf_v['key']])
                                // set freed flag
                                $freed_flag = 1;
                            }
                }

            // possibility to free dimension of record?
            $res .= ',"freeddimension_flag":"'.$freed_flag.'"';

            // right to edit record?
            $right = checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_edit', $recordid, $record['projectid'], $record['editors'], 0, 0);
            $res .= ',"edit_right":"'.$right.'"';

            // right to re-order record?
            if (!array_key_exists ('reorder', $gbl_db_data[$dbix]['options_array']) || count ($gbl_db_data[$dbix]['options_array']['reorder']) == 0)
                $res .= ',"reorder_right":"0"';
            else
                $res .= ',"reorder_right":"'.$right.'"';

            // right to print record?
            $res .= ',"print_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_printer', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // copy database record
            $res .= ',"copy_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_copy', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // delete database record
            $res .= ',"delete_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_delete', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // training group access test and change
            if (array_key_exists ('traininggroupid', $record) && $record['traininggroupid'] != '' && $record['traininggroupid'] != '0')
                {
                $res .= ',"traininggroupaccesstest_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_traininggroupaccesstest', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';
                $res .= ',"traininggroupaccesschange_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_traininggroupaccesschange', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';
                }
            else
                {
                $res .= ',"traininggroupaccesstest_right":"0"';
                $res .= ',"traininggroupaccesschange_right":"0"';
                }

            // test acquise sources
            $res .= ',"acquisesearchtest_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_acquisesearchtest', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // run query
            $res .= ',"queryrun_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_queryrun', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // populate query
            $res .= ',"querypopulate_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_querypopulate', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // login password change
            if ($gbl_db_data[$dbix]['type'] == 'contacts' && $record['loginactive'] == 1 && $record['loginname'] != '')
                $res .= ',"loginpasswordchange_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_loginpasswordchange', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';
            else
                $res .= ',"loginpasswordchange_right":"0"';

            // login data send
            if ($gbl_db_data[$dbix]['type'] == 'contacts' && $record['loginactive'] == 1 && $record['loginname'] != '' && $record['password'] != '')
                $res .= ',"logindatasend_right":"'.checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_logindatasend', $recordid, $record['projectid'], $record['editors'], 0, 0).'"';

            // reset right
            $right = 0;

            // set right and parameter for "create document"
            if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_documentsgenerate', $recordid, $record['projectid'], $record['editors'], 0, 0) == 1)
                {
                // loop databases
                foreach ($gbl_db_data as $dbix_src => $dbrecord)
                    {
                    // right type?
                    if (   $dbrecord['type'] != 'generictemplates'
                        && $dbrecord['type'] != 'filetemplates_files')
                           continue;

                    // sql area
                    try
                        {
                        // prepare sql command
                        $stmt_documents = $dbh -> prepare ("SELECT `id` FROM `".$dbrecord['dbname']."` WHERE ".$gbl_db_filter[$dbix_src]." AND `active`=1 AND `relid`=:relid AND `delflag`=0");

                        // bind parameters to sql command string
                        $stmt_documents -> bindParam (':relid', $dbix);

                        // execute sql command
                        $stmt_documents -> execute();

                        // get data
                        if (($row = $stmt_documents -> fetch()))
                            // set right: right ok
                            $right = 1;
                        }

                    // error?
                    catch (Exception $error)
                        {
                        // log error message
                        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_documents, $dbix_src);
                        }

                    // break?
                    if ($right == 1)
                        break;
                    }
                }

            // set right: no right
            $res .= ',"documentsgenerate_right":"'.$right.'"';

            // register shutdown function
            register_shutdown_function ('ajax_recorddata_get_shutdown', $dbix, $print_type, $accesstype, $recordid, $record);

            // break
            break;


        // --- output type: create
        case 'create':

            // reset job array
            $jobs = array();

            // loop through all personal database fields to set default values
            foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
                // active for user?
                if ($value['printactive'.$topsubflag] == 1)
                    // allowed for create mode?
                    if (($gbl_dbf_data[$dbix][$key]['array_flags']['form_create'.$topsubflag] & 3) != 0)
                        $record[$key] = string_repr_replace ($dbix, $gbl_dbf_data[$dbix][$key]['default'], $record['projectid'], '', $accesstype, $jobs, $key, $record, 1);

            // break
            break;


        // ----- edit
        case 'edit':

            // record edited more than 1 minute in the past (not blocked any more)
            if ((time() - strtotime ($record['blockdatetime'])) < 60)
                {
                // push blocked message
                $result = '';
                contact_get ($record['blockuser'], 1, 1, 0, 0, $result);
                echo '[{"pass":"blocked", "blockuser":"'.stripquotes ($result).'"}]';

                // close database
                $dbh = null;

                // return
                return;
                }

            // register shutdown function
            register_shutdown_function ('ajax_recorddata_get_shutdown', $dbix, $print_type, $accesstype, $recordid, $record);

            // break
            break;


        // ----- copy
        case 'copy':

            // loop through all personal database fields to reset specific values
            foreach ($gbl_dbf_data[$dbix] as $dbf_k => $dbf_v)
                // reset in copy case?
                if (array_key_exists ('copyreset', $dbf_v['array_options']) && $dbf_v['array_options']['copyreset'] == 1)
                    // reset value
                    $record[$dbf_k] = '';

            // break;
            break;
        }


    // ----- save preset parameter

    // i.c. loop all preset parameter
    if ($accesstype != 'print')
        {
        foreach ($_REQUEST as $r_key => $r_value)
            if (strpos ($r_key, 'preset_') !== false)
                $form_open .= '<input type="hidden" name="'.$r_key.'" id="hf_'.$r_key.'" value="'.printhtml ($r_value).'" />';

        // save portion name
        $form_open .= '<input type="hidden" name="preset__portion" id="hf_preset__portion" value="'.$portion.'" />';
        }


    // ----- get and sort field index

    // reset fields array
    $pdbf = array();

    // loop through all personal database fields to build sorted index
    foreach ($gbl_actuser['pdb_settings'][$pdbix]['fields'] as $key => $value)
        // active for user?
        if ($value['printactive'.$topsubflag] == 1)
            // allowed for print/create/copy/edit mode?
            if (($gbl_dbf_data[$dbix][$key]['array_flags']['preview'.$topsubflag] == 1 && $accesstype == 'print') || ($gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$accesstype.$topsubflag] & 3) != 0)
                {
                // field access?
                if ($gbl_dbf_data[$dbix][$key]['right'] != '')
                    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_'.$gbl_dbf_data[$dbix][$key]['right'], $recordid, $record['projectid'], $record['editors'], 0, 0) == 0)
                        continue;

                // save key index
                $pdbf[sprintf ("%03d", $value['order'])] = $key;
                }

    // sort index
    ksort ($pdbf);


    // ----- print record data

    // i.c. open table
    if ($print_type == 'raw')
        $message_html .= '<table>';

    // reset parameter
    $tab = '';
    $tabs = '';
    $tab_cnt = 0;

    // reset column counter
    $column_cnt = array();
    $column_cnt[$tab_cnt] = 1;

    // reset output array
    $output = array();

    // reset parameter string
    $firstfield = '';
    $colorpicker_keys = '';
    $date_keys = '';
    $editorids = '';
    $protocolids = '';
    $combo_keys = '';
    $sourceeditorids = '';

    // reset portion array
    $portionarr = array ();

    // print headers
    foreach ($pdbf as $key => $value)
        {
        // i.c. reset ouput element
        if (!array_key_exists ($tab_cnt, $output))
            $output[$tab_cnt] = '';

        // get tab and key name
        $tabname = $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['tabname'];
        $keyname = $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['fieldname'];

        // i.c. fill portion list
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['portion'.$accesstype_portion] != '')
            {
            // get array
            $arr = array_map ('trim', explode (',', $gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['portion'.$accesstype_portion]));

            // key not of type projectid (mandatory for create/copy record)?
            if ($gbl_dbf_data[$dbix][$value]['type'] != 'projectid' || $accesstype == 'edit')
                // portion given?
                if ($portion != '')
                    if (!in_array ($portion, $arr))
                        continue;

            // swap keys and values
            $arr = array_flip ($arr);

            // merge arrays
            $portionarr = $portionarr + $arr;
            }
        else
            // key not of type projectid (mandatory for create/copy record)?
            if ($gbl_dbf_data[$dbix][$value]['type'] != 'projectid' || $accesstype == 'edit')
                // portion given?
                if ($portion != '')
                    continue;

        // new tab?
        if ($tab != $tabname)
            {
            // i.c. add delimiter
            if ($tabs != '')
                $tabs .= ',';

            // add tab name (grid or field?)
            if ($gbl_dbf_data[$dbix][$value]['type'] == 'table' && $accesstype == 'print')
                $tabs .= urlcomponentencode ('# '.$tabname);
            else
                $tabs .= urlcomponentencode ($tabname);

            // save tab name
            $tab = $tabname;

            // increase tab counter
            $tab_cnt++;

            // i.c. reset ouput element
            if (!array_key_exists ($tab_cnt, $output))
                $output[$tab_cnt] = '';

            // reset column counter
            $column_cnt[$tab_cnt] = 1;
            }

        // add current field to list of dynamic fields (user-defined evaluation code will be run for this)
        if (isset ($gbl_dbf_data[$dbix][$value]['array_options']['calculation_js']))
            {
            // create new sub-array for current record field
            $dynamic_fields[$value] = array();

            // set JavaScript code expression
            $dynamic_fields[$value]['expression'] = $gbl_dbf_data[$dbix][$value]['array_options']['calculation_js'];

            // set number of decimals (used for rounding)
            $dynamic_fields[$value]['decimals'] = $gbl_dbf_data[$dbix][$value]['array_options']['format_print_number_digitsaftercomma'];

            // set comma sign (e.g. "," for German numeric notation, etc.)
            $dynamic_fields[$value]['comma_sign'] = $gbl_dbf_data[$dbix][$value]['array_options']['format_print_number_comma'];

            // set separator sign for grouping digits into thousands
            $dynamic_fields[$value]['thousands_sign'] = $gbl_dbf_data[$dbix][$value]['array_options']['format_print_number_1000sign'];
            }

        // table type and print mode? -> insert sub database parameter
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'table' && $accesstype == 'print')
            {
            // print table parameter
            $output[$tab_cnt] = ':table|'.$gbl_dbf_data[$dbix][$value]['array_options']['dbname'];

            // continue loop
            continue;
            }

        // set first input field
        if (   $firstfield == ''
            && isset ($gbl_dbf_data[$dbix][$value]['array_flags']['form_'.$accesstype.$topsubflag])
            && $gbl_dbf_data[$dbix][$value]['array_flags']['form_'.$accesstype.$topsubflag] != 1
            && (   $activetab == urlcomponentencode ($tabname)
                || $activetab == ''
                || (isset ($gbl_dbf_data[$dbix][$value]['array_options']['editrecordtabviewflg']) && $gbl_dbf_data[$dbix][$value]['array_options']['editrecordtabviewflg'] == 0)))
            if (   $gbl_dbf_data[$dbix][$value]['type'] == 'string'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'integer'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'textarea'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'richtextarea'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'webtextarea'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'wysiwygtextarea'
                || $gbl_dbf_data[$dbix][$value]['type'] == 'sourceeditor')
                $firstfield = $value;

        // i.c. close column and open new column
        if ($gbl_actuser['pdb_settings'][$pdbix]['fields'][$value]['column'] == 'newcolumn')
            {
            // close column and open new column
            $output[$tab_cnt] .= '</table></div><div style="width:###%;float:left;"><table class="viewtab">';

            // increase column counter
            $column_cnt[$tab_cnt]++;
            }

        // i.c. begin row
        if ($print_type != 'raw')
            $output[$tab_cnt] .= '<tr class="viewtab">';
        else
            // open line
            $message_html .= '<tr>';

        // divisor or fixed text?
        if (   $gbl_dbf_data[$dbix][$value]['type'] == 'explanation'
            || $gbl_dbf_data[$dbix][$value]['type'] == 'divisor')
            {
            // i.c. open field
            if ($print_type != 'raw')
                $output[$tab_cnt] .= '<td colspan="2" class="record_span">';
            }
        else
            {
            // i.c. open cell and print key
            if ($print_type == 'raw')
                {
                // set key
                $message .= '# '.$keyname.': ';
                $message_html .= '<td style="vertical-align:top;text-align:left;border-bottom:2px solid #c6e2ff;background-color:#FFE09F;color:#404040;">'.printhtml ($keyname).'</td>';
                }
            else
                {
                // print mode?
                if ($accesstype != 'print')
                    {
                    // i.c. print mandatory color
                    if ($gbl_dbf_data[$dbix][$value]['array_flags']['form_'.$accesstype.$topsubflag] == 3)
                        // set output
                        $output[$tab_cnt] .= '<td class="record_key"><span style="color:#ff0000;font-weight:bold;">'.printhtml ($keyname).'</span>';
                    else
                        // open key
                        $output[$tab_cnt] .= '<td class="record_key">'.printhtml ($keyname);
                    }
                 else
                    // open key
                    $output[$tab_cnt] .= '<td class="record_key">'.printhtml ($keyname);
                }

            // i.c. print key comment
            if ($gbl_dbf_data[$dbix][$value]['explanation'] != '')
                if ($print_type != 'raw')
                    $output[$tab_cnt] .= '<img src="images/icons/help.gif" title="'.printhtml ($gbl_dbf_data[$dbix][$value]['explanation']).'" />';

            // i.c. print validation hint
            if ($gbl_dbf_data[$dbix][$value]['validationhint'] != '')
                if ($accesstype != 'print')
                    if ($print_type != 'raw')
                        $output[$tab_cnt] .= '<img src="images/icons/key.gif" title="'.strip_tags (printhtml ('Hinweise zur Validierungsprüfung:'."\n".$gbl_dbf_data[$dbix][$value]['validationhint'])).'" />';

            // i.c. print copy hint
            if ($accesstype == 'copy' && array_key_exists ('copyreset', $gbl_dbf_data[$dbix][$value]['array_options']) && $gbl_dbf_data[$dbix][$value]['array_options']['copyreset'] == 1)
                $output[$tab_cnt] .= '<img src="images/icons/copy.gif" title="Dieses Feld wird beim Kopiervorgang nicht vom Original übernommen und wurde daher geleert." />';  // TODO: Internationalization

            // i.c. close cell and open value field
            if ($print_type != 'raw')
                {
                // close cell
                $output[$tab_cnt] .= '</td>';

                // open value field
                $output[$tab_cnt] .= '<td class="record_value">';
                }
            }

        // get and save value
        if ($print_type == 'raw')
            {
            // get and save value
            $message .= dbfield_get ($dbh, $dbix, $pdbix, $value, $record, $tab_cnt, 'table', $topsubflag);
            $message_html .= ' <td style="vertical-align:top;text-align:left;border-bottom:1px solid #c6e2ff;border-right:1px solid #c6e2ff;color:#404040;">'.dbfield_get ($dbh, $dbix, $pdbix, $value, $record, $tab_cnt, 'table', $topsubflag).'</td>';
            }
        else
            {
            // get value
/*
if ($value == 'validityflag'
|| $value == 'active'
|| $value == 'relid'
|| $value == 'title'
|| $value == 'titleshort'
|| $value == 'token'
|| $value == 'description'
|| $value == 'type'
|| $value == 'charset'
|| $value == 'pageorientation'
|| $value == 'paper_width'
|| $value == 'paper_height'
|| $value == 'margin_left'
|| $value == 'margin_right'
|| $value == 'margin_top'
|| $value == 'margin_bottom'
|| $value == 'selectsource'
|| $value == 'documentsource'
|| $value == 'projectid_auto_flag'
|| $value == 'projectid_auto_organization' //#
|| $value == 'groupaccess' //#
|| $value == 'useraccess' //#
|| $value == 'useraccess' //#
|| $value == 'readid' //#
|| $value == 'editors' //#
)
*/
/*
projectid
*/
//if ($value != 'projectid')
            $res_t = dbfield_get ($dbh, $dbix, $pdbix, $value, $record, $tab_cnt, $accesstype, $topsubflag);
//else
//            $res_t = '-'.$recordid;

            // i.c. print keys and comments for not valid field contents
            if (is_array ($record) && array_key_exists ('validityarray', $record) && is_array ($record['validityarray']) && array_key_exists ($value, $record['validityarray']))
                {
                // print explanation
                $output[$tab_cnt] .= '<span style="background-color:red;color:white;">'.UI_RECORDFIELD_NOTVALID.': ';
                $output[$tab_cnt] .= trim (printhtml ($record['validityarray'][$value]));
                $output[$tab_cnt] .= '</span><br />';
                }

            // i.c. print copy hint
            if ($accesstype == 'copy' && array_key_exists ('copyreset', $gbl_dbf_data[$dbix][$value]['array_options']) && $gbl_dbf_data[$dbix][$value]['array_options']['copyreset'] == 1)
                {
                // print explanation
                $output[$tab_cnt] .= '<span style="background-color:blue;color:white;">';
                $output[$tab_cnt] .= 'Dieses Feld wird beim Kopiervorgang nicht vom Original übernommen und wurde daher geleert.'; // TODO: Internationalization
                $output[$tab_cnt] .= '</span><br />';
                }

            // save value
            $output[$tab_cnt] .= $res_t;
            }

        // i.c. close unit
        if ($print_type == 'raw')
            {
            // finish line
            $message .= "\r\n";
            $message_html .= "</tr>";
            }
        else
            {
            // finish cell and line
            $output[$tab_cnt] .= '</td>';
            $output[$tab_cnt] .= '</tr>';
            }

        // combo keys
        if (   $gbl_dbf_data[$dbix][$value]['type'] == 'list'
            && isset ($gbl_dbf_data[$dbix][$value]['array_options']['combo'])
            && $gbl_dbf_data[$dbix][$value]['array_options']['combo'] == 1)
            if ($combo_keys == '')
                $combo_keys  = $value;
            else
                $combo_keys .= ','.$value;

        // color picker keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'color')
            if ($colorpicker_keys == '')
                $colorpicker_keys  = 'hf_'.$value;
            else
                $colorpicker_keys .= ',hf_'.$value;

        // date keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'datetime')
            if ($date_keys == '')
                $date_keys  = $value.'_date';
            else
                $date_keys .= ','.$value.'_date';

        // date keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'date')
            if ($date_keys == '')
                $date_keys  = $value;
            else
                $date_keys .= ','.$value;

        // editor keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'wysiwygtextarea')
            {
            // regular WYSIWYG text areas
            if ($editorids == '')
                $editorids  = $value;
            else
                $editorids .= ','.$value;
            }

        // editor keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'protocol')
            {
            // protocol WYSIWYG text area
            if ($protocolids == '')
                $protocolids  = $value;
            else
                $protocolids .= ','.$value;
            }

        // source editor keys
        if ($gbl_dbf_data[$dbix][$value]['type'] == 'sourceeditor')
            {
            // get theme for source editor
            if (!$gbl_dbf_data[$dbix][$value]['array_options']['theme'] || $gbl_dbf_data[$dbix][$value]['array_options']['theme'] == '')
                $theme = 'eclipse';
            else
                $theme = $gbl_dbf_data[$dbix][$value]['array_options']['theme'];

            // get mode for source editor
            if (!$gbl_dbf_data[$dbix][$value]['array_options']['mode'] || $gbl_dbf_data[$dbix][$value]['array_options']['mode'] == '')
                $mode = 'text';
            else
                $mode = $gbl_dbf_data[$dbix][$value]['array_options']['mode'];

            // get word wrap mode for source editor
            if (!$gbl_dbf_data[$dbix][$value]['array_options']['wordwrap'] || $gbl_dbf_data[$dbix][$value]['array_options']['wordwrap'] == '')
                $wordwrap = 'false';
            else
                $wordwrap = $gbl_dbf_data[$dbix][$value]['array_options']['wordwrap'];

            // read only?
            if ($gbl_dbf_data[$dbix][$key]['array_flags']['form_'.$otype.$topsubflag] == 1)
                $readonly = 1;
            else
                $readonly = 0;

            // add source editor data
            if ($sourceeditorids == '')
                $sourceeditorids  = $value.'|'.$theme.'|'.$mode.'|'.$wordwrap.'|'.$readonly;
            else
                $sourceeditorids .= ','.$value.'|'.$theme.'|'.$mode.'|'.$wordwrap.'|'.$readonly;
            }
        }

//msg_log (LOG_MESSAGE, 'm1', '', 0, 0, 0);

    // i.c. close table
    if ($print_type == 'raw')
        {
        // close record
        $message .= "\r\n\r\n";
        $message_html .= '</table>';
        }


    // ----- close database

    // close database
    $dbh = null;


    // ----- i.c return
    if ($print_type == 'raw')
        return;


    // ----- print parameter

    // send success response
    echo '[{"pass":"ok"';

    // print record name
    echo ',"recordname":"'.urlcomponentencode ($gbl_actuser['pdb_settings'][$pdbix]['recordname']).'"';

    // print record header
    if ($gbl_db_data[$dbix]['recordheader'] == '' || $accesstype == 'create')
        echo ',"recordheader":"'.urlcomponentencode($gbl_actuser['pdb_settings'][$pdbix]['recordname']).'"';
    else
        {
        // reset header
        $header = '';

        // get key parts
        $kparts = array_map ('trim', explode ('.', $gbl_db_data[$dbix]['recordheader']));

        // loop all key parts
        foreach ($kparts as $k => $v)
            // string part?
            if ($v[0] == '\'')
                {
                // get string
                $t = explode ('\'', $v);

                // add string to header
                $header .= $t[1];
                }
            else
                // add parameter
                $header .= $record['rtp_'.$v];

        // write header
        echo ',"recordheader":"'.urlcomponentencode ($header).'"';
        }

    // print edit record tab view flag
    echo ',"editrecordtabviewflg":"'.$gbl_actuser['pdb_settings'][$pdbix]['editrecordtabviewflg'].'"';

    // print combo ids
    echo ',"comboids":"'.$combo_keys.'"';

    // print color picker ids
    echo ',"colorpickerids":"'.$colorpicker_keys.'"';

    // print calendar ids
    echo ',"calendarids":"'.$date_keys.'"';

    // print editor ids
    echo ',"editorids":"'.$editorids.'"';

    // print protocol editor ids
    echo ',"protocolids":"'.$protocolids.'"';

    // print source editor ids
    echo ',"sourceeditorids":"'.$sourceeditorids.'"';

    // print project id
    echo ',"projectid":"'.$record['projectid'].'"';

    // print portions
    if (sizeof ($portionarr) == 0)
        echo ',"portions":""';
    else
        {
        // print portion names (I)
        echo ',"portions":"Array(Array(\'handle_record_edit&pdbix='.$pdbix.'&portion=&topsubflag=&recordid='.$record['id'].'\',\'obj\',\''.$gbl_actuser['pdb_settings'][$pdbix]['recordname'].' '.UI_RECORDVIEW_EDITPART2.'\')';

        // build portions array
        foreach ($portionarr as $k => $v)
            echo ',Array(\'handle_record_edit&pdbix='.$pdbix.'&recordid='.$record['id'].'&topsubflag=&portion='.urlcomponentencode($k).'\',\'obj\',\''.$k.' '.UI_RECORDVIEW_EDITPART2.'\')';

        // print portion names (II)
        echo ')"';
        }

    // print tab name list
    echo ',"tabs":"'.$tabs.'"';

    // print first field
    echo ',"firstfield":"'.$firstfield.'"';

    // print list of dynamic fields - i.e. fields which will be updated "live" (I)
    echo ',"dynamicfields":';

    // print list of dynamic fields - i.e. fields which will be updated "live" (II)
    if (count ($dynamic_fields))
        echo json_encode ($dynamic_fields);
    else
        echo '{}';

    // close response
    echo $res;
    echo ',"dbix":"'.$dbix.'"';
    echo ',"dbname":"'.$gbl_db_data[$dbix]['dbname'].'"';
    echo ',"pdbix":"'.$pdbix.'"}';

    // print tabs content
    for ($i = 1; $i <= $tab_cnt; $i++)
        {
        // i.c. delete form open code
        if ($i > 1)
            $form_open = '';

        // content table or normal?
        if ($output[$i][0] == ':')
            echo ',{"content":"'.urlcomponentencode ($output[$i]).'"}';
        else
            {
            // calculate width of column
            if ($column_cnt[$i] > 1)
                $width = intval (100/$column_cnt[$i]);
            else
                $width = 100;

            // replace width dummy value with right value
            $output[$i] = str_replace ('width:###', 'width:'.$width, $output[$i]);

            // add content
            echo ',{"content":"'.urlcomponentencode ($form_open.'<div style="width:'.$width.'%;float:left;"><table class="viewtab">'.$output[$i].'</table></div>').'"}';
            }
        }

    // close response
    echo ']';

    // return
    return;





// -----------------------------------------------------
// module: shutdown function
// status: v6 finished
// -----------------------------------------------------

function ajax_recorddata_get_shutdown ($dbix, $print_type, $accesstype, $recordid, $record)
    {
    // include global variable definitions
    include ('global_vars.php');

    // i.c. save record id
    if ($accesstype == 'print' && $print_type != 'raw' && $gbl_actuser['pdb_settings'][$pdbix]['actrecordid'] != intval ($recordid))
        // save view settings
        include ('mod_viewsetting_save.php');

    // handle read id field (i.c. append id of current reading user)
    if ($accesstype == 'print' && $print_type != 'raw' && array_key_exists ('read_key', $gbl_db_data[$dbix]) && strpos ($record[$gbl_db_data[$dbix]['read_key']], ','.$gbl_actuser['id'].',') === false)
        {
        // connect to database
        $dbh = pdoconnect (1);

        // sql area
        try
            {
            // prepare sql command
            $stmt_readid = $dbh -> prepare ("UPDATE `".$gbl_db_data[$dbix]['dbname']."` SET `editors`=:editors, `".$gbl_db_data[$dbix]['read_key']."`=:readid, `rtp_".$gbl_db_data[$dbix]['read_key']."`=:rtp_readid WHERE `id`=:recordid AND `delflag`=0");

            // get read id
            if ($record[$gbl_db_data[$dbix]['read_key']] == '' || is_array ($record[$gbl_db_data[$dbix]['read_key']]) || $record[$gbl_db_data[$dbix]['read_key']][0] != ',')
                $readid = ','.$gbl_actuser['id'].',';
            else
                $readid = $record[$gbl_db_data[$dbix]['read_key']].$gbl_actuser['id'].',';

            // get read id string
            contact_get ($readid, 1, 0, 0, 0, $readid_str);

            // bind parameters to sql command string
            $stmt_readid -> bindValue (':readid',     $readid);
            $stmt_readid -> bindValue (':rtp_readid', $readid_str);
            $stmt_readid -> bindValue (':recordid',   $recordid);
            $stmt_readid -> bindValue (':editors',    editors_addrecord_build ($record, $gbl_actuser['id'], 'viewed', '', ''));

            // execute sql command
            $stmt_readid -> execute();
            }

        // error?
        catch (Exception $error)
            {
            // log error message
            msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt_readid, $recordid);
            }

        // close database
        $dbh = null;
        }

    // return
    return;
    }
?>
