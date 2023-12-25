<?php
    // -----------------------------------------------------
    // module: delivers outbill data for custom grid
    // status: v10 working
    // TODO:     find reldata, if outbill sub record of training
    //           Bezug auf Aufwandsschätzungsnummer (Betrag über-/unterschritten?)
    //           Rechnung schon gestellt?
    //           falls ein Eintrag einer offenen Serie nach Rechnungsstellung hinzukommt --> ?
    //           Aufteilung 100%?
    //           assistierende Trainer*innen berücksichtigen
    //           Konzept und Vorbereitung u.a. nur bei bestimmten trainings types
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // connect to database
    $dbh = pdoconnect (1);

    // set success message
    echo '[{"pass":"ok",';

    // open rows
    echo '"rows":[';

    // get records indices
    $recordsids = array_map ('trim', explode (',', trim ($_REQUEST['reldata'],",")));

    // reset sorted records array
    $records_sorted = array();
    $ix = 0;

    // reset row index
    $rowix = 1;


    // ----- sort related records

    // get new training object
    $training_data = new training_data();
    $training_data->dbh = $dbh;

    // loop records ids array
    foreach ($recordsids as $i => $id)
        {
        // set record id
        $record = array();
        $record['id'] = $id;

        // get trainings details
        $training = $training_data->trainingdata_get ($record, 0, 1, 1);

        // get sub projects ids string
        $subids = implode ('-', $training['md_subprojects']);

        // save training data
        $records_sorted[$subids.'-'.$training['startdate'].'-'.$ix++] = $training;
        }

    // sort trainings array
    ksort ($records_sorted);


    // ----- process related records

    // loop records ids array
    foreach ($records_sorted as $i => $training)
        {
        // reset ud ids variables
        $ud_ids = ',';
        $ud_ids_names = '';

        // loop sub project ids
        foreach ($training['md_subprojects'] as $i_t => $id_t)
            {
            // skip, when sub id invalid
            if ($id_t == '-')
                continue;

            // add id
            $ud_ids .= $gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['id'].',';

            // add name
            if ($ud_ids_names == '')
                $ud_ids_names = $gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['rtp_projectid'].', '.$id_t.': '.$gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['subcustomer_short'].', '.$gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['title'];
            else
                $ud_ids_names .= '; '.$gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['rtp_projectid'].', '.$id_t.': '.$gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['subcustomer_short'].', '.$gbl_activeprojects[$training['projectid']]['subprojects_array_indexed'][$id_t]['title'];
            }

        // set parameter: group with trainings data
        $group = $training['type'].' \''.$training['subject'].'\' ('.$training['md_daterange'].')';

        // set array of bill positions
        $positions = array (
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Telefonat zur Vorbesprechung', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Vorabbefragung der Teilnehmer*innen', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Vor- und Nachbereitung', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Konzeption und Vorbereitung', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'working',  'skip' => 1, 'type' => 'Teilnehmendenunterlage', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_tn', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => 'Faktor an Seiten- und/oder Teilnehmendenanzahl anpassen', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Online-Plattform', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_tn', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'working',  'skip' => 1, 'type' => 'Reisezeit', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => $training['rtp_startdate'], 'enddate' => $training['rtp_enddate'], 'todo' => 'Faktor an Reisezeit anpassen und Datum eintragen', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'working',  'skip' => 1, 'type' => 'Reisekosten', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => $training['rtp_startdate'], 'enddate' => $training['rtp_enddate'], 'todo' => 'Faktor und Preise anpassen und Datum eintragen (siehe Belege im Anhang)', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'working',  'skip' => 1, 'type' => 'Übernachtung', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'val', 'factor_val' => $training['md_overnights_payedbyservicepartner'], 'comment' => 'hotel', 'startdate' => $training['rtp_hotel_startdate'], 'enddate' => $training['rtp_hotel_enddate'], 'todo' => 'Preis anpassen (siehe Belege im Anhang)', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop')),
            array ('state' => 'checking', 'skip' => 0, 'type' => $training['type'], 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'val', 'factor_val' => $training['md_fk_minutes'], 'comment' => 'training', 'startdate' => $training['rtp_startdate'], 'enddate' => $training['rtp_enddate'], 'todo' => '', 'type_proof' => false, 'types' => array()),
            array ('state' => 'checking', 'skip' => 1, 'type' => $training['type'], 'role' => 'name_cofk', 'pricepos' => 'calculation_amount_cofk_ag', 'factor' => 'val', 'factor_val' => $training['md_cofk_minutes'], 'comment' => 'training', 'startdate' => $training['rtp_startdate'], 'enddate' => $training['rtp_enddate'], 'todo' => '', 'type_proof' => false, 'types' => array()),
            array ('state' => 'checking', 'skip' => 1, 'type' => 'Telefonat zur Nachbesprechung', 'role' => 'name_fk', 'pricepos' => 'calculation_amount_fk_ag', 'factor' => 'std', 'factor_val' => 0, 'comment' => 'empty', 'startdate' => '', 'enddate' => '', 'todo' => '', 'type_proof' => true, 'types' => array('Beratung', 'Ausbildung', 'Befragung', 'Coaching', 'Hospitation', 'Individuelle Begleitung', 'Kick-off-Veranstaltung', 'Klärungshilfe', 'Kollegiale Beratung', 'Konfliktbearbeitung', 'Mediation', 'Moderation', 'Präsentation', 'Supervision', 'Tagung', 'Teamentwicklung', 'Therapie', 'Train-the-Trainer-Training', 'Trainer-Coaching', 'Training', 'Vortrag', 'Workshop'))
            );

        // loop special bill positions
        foreach ($positions as $i => $position)
            {
            // get data for type
            $amounts_row = array();
            $flag = project_amounts_get ($amounts_row, $position['type'], $training['placetype'], $training['targetgroups'], $training['rtp_trainingplaceid'], $training['projectid'], $training['startdate']);

            // position not given and skip allowed?
            if ($flag == 0 && $position['skip'] == 1)
                continue;

            // role given?
            if ($amounts_row[$position['role']] == '' && $position['skip'] == 1)
                continue;

            // hotel skip?
            if ($position['comment'] == 'hotel' && $training['hotelflag'] == 0)
               continue;

            // price=0 and skip allowed?
            if ($amounts_row[$position['pricepos']] == 0 && $position['skip'] == 1)
               continue;

            // type proof and type not given?
            if ($position['type_proof'] == true && !in_array ($training['type'], $position['types']))
               continue;

            // reset warnings array
            $warnings = array();

            // reset comment
            $comment = '';

            // comment for hotel?
            if ($position['comment'] == 'hotel')
                $comment = $training['rtp_hotelid'];

            // comment for training?
            if ($position['comment'] == 'training')
                {
                // set training comment
                $comment = $training['placetype'];

                // i.c. add training place
                if ($training['placetype'] == 'gemeinsame Präsenz an einem Ort')
                    $comment .= $training['rtp_trainingplaceid']==''?'':': '.$training['rtp_trainingplaceid'];

                // i.c. reset comment: storniert
                if ($training['state'] == 2)
                    $comment = 'Veranstaltung storniert; #Achtung#: in Spalte Faktor Storno-Anteil eintragen';
                }

            // position found?
            if ($flag == 0)
                // add comment parameter
                $warnings[] = 'Einzelpreis für '.$position['type'].' nicht gefunden';

            // multiple amounts found?
            if ($flag == 2)
                // add comment parameter
                $warnings[] = 'mehr als einen Einzelpreis für '.$position['type'].' gefunden';

            // i.c. add comment of amount row
            if ($amounts_row['comment'] != '')
                // add comment parameter
                $warnings[] = 'Hinweis beachten - '.$amounts_row['comment'];

            // get factor
            $factor = $position['factor_val'];

            // consider unit
            switch ($amounts_row['unit'])
                {
                // page
                case 'page':

                    // set unit
                    $unit = 'Seite';
                    break;

                // kilometer
                case 'km':

                    // set unit
                    $unit = 'Kilometer';
                    break;

                // night
                case 'night':

                    // set unit
                    $unit = 'Nacht';
                    break;

                // 1 hour of 45 minutes
                case 'hour45':

                    // set factor and unit
                    $factor = $factor/45;
                    $unit = 'Dreiviertelstunde';
                    break;

                // 1 hour of 60 minutes
                case 'hour60':

                    // set factor and unit
                    $factor = $factor/60;
                    $unit = 'Stunde';
                    break;

                // 1 hour of 90 minutes
                case 'hour90':

                    // set factor and unit
                    $factor = $factor/90;
                    $unit = '90-Minuten-Einheit';
                    break;

                // 4 hours of 45 minutes working time plus 15 minutes per hour resting time
                case 'day4':

                    // set factor and unit
                    $factor = $factor/60/4;
                    $unit = 'halber Tag';
                    break;

                // 8 hours of 45 minutes working time plus 15 minutes per hour resting time
                case 'day8':

                    // set factor and unit
                    $factor = $factor/60/8;
                    $unit = 'Tag';
                    break;

                // flat
                case 'flat':

                    // set factor and unit
                    $factor = 1;
                    $unit = 'Pauschale';
                    break;

                // flat per year
                case 'flatperyear':

                    // set unit
                    $factor = 1;
                    $unit = 'Pauschale pro Jahr';
                    break;

                // per participant and year
                case 'pertnandyear':

                    // set factor and unit
                    $factor = $training['md_participantscnt'];
                    $unit = 'Teilnehmer*in und Jahr';
                    break;

                // per participant
                case 'pertn':

                    // set factor and unit
                    $factor = $training['md_participantscnt'];
                    $unit = 'Teilnehmer*in';
                    break;

                // default
                default:

                    // set number
                    $unit = '???';

                    // add comment parameter
                    $warnings[] = 'unbekannte Einheit';

                    // log error message
                    msg_logx (LOG_ERROR_HIDE, __FILE__, __FUNCTION__, __LINE__, 'unknown unit', $amounts_row['unit'], 0, 0, $training['id']);
                }

            // get factor
            if ($factor == 0 && $position['factor'] == 'std')
                $factor = $amounts_row['factor'];

            // factor=0 and skip allowed?
            if ($factor == 0 && $position['skip'] == 1)
               continue;

            // i.c. add to do comment
            if ($position['todo'] != '')
                $warnings[] = $position['todo'];

            // add warning hints
            foreach ($warnings as $i => $warning)
                // add comment parameter
                if ($comment == '')
                    $comment .= '#Achtung#: '.$warning;
                else
                    $comment .= '; #Achtung#: '.$warning;

            // i.c. add delimiter
            if ($rowix++ > 1)
                 echo ',';

            // reset counter
            $cnt = 1;

            // print row data
            echo '{'
                .$cnt++.':{"value":"'.$position['state'].'","ud_ids":""}, '
                .$cnt++.':{"value":"'.urlcomponentencode ($group).'","ud_ids":""},'
                .$cnt++.':{"value":"'.urlcomponentencode ($position['type'].($amounts_row[$position['role']]==''?'':' bzgl. '.$amounts_row[$position['role']]).($amounts_row['contractpos']==''?'':' (Vertragsposition: '.$amounts_row['contractpos'].')')).'","ud_ids":""},'
                .$cnt++.':{"value":"'.urlcomponentencode ($comment).'","ud_ids":""},'
                .$cnt++.':{"value":"'.$position['startdate'].'","ud_ids":""},'
                .$cnt++.':{"value":"'.$position['enddate'].'","ud_ids":""},'
                .$cnt++.':{"value":"'.$amounts_row[$position['pricepos']].'","ud_ids":""},'
                .$cnt++.':{"value":"'.number_format ($factor, 2, '.', '').'","ud_ids":""},'
                .$cnt++.':{"value":"'.$unit.'","ud_ids":""},'
                .$cnt++.':{"value":"100","ud_ids":""},'
                .$cnt++.':{"value":"","ud_ids":""},'
                .$cnt++.':{"value":"'.$amounts_row['reduction'].'","ud_ids":""},'
                .$cnt++.':{"value":"","ud_ids":""},'
                .$cnt++.':{"value":"","ud_ids":""},'
                .$cnt++.':{"value":"'.($amounts_row['mwst']=='p4n21'?'0':$gbl_general['ustvalue']).'","ud_ids":""},'
                .$cnt++.':{"value":"","ud_ids":""},'
                .$cnt++.':{"value":"","ud_ids":""},'
                .$cnt++.':{"value":"'.$ud_ids_names.'","ud_ids":"'.$ud_ids.'"}}';
            }
        }

    // close rows
    echo ']';

    // close array
    echo '}]';

    // close database
    $dbh = null;
?>