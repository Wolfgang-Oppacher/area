<?php
    // -----------------------------------------------------
    // module: import CSV file contents into 'contacts' database
    // status: finished
    // -----------------------------------------------------

    // ----- initialization
    require_once('mod_init_ajax.php');


    // ----- type identifiers for database field types

    const FIELD_NUMBER = 0;
    const FIELD_STRING = 1;
    const FIELD_FUNCTION = 2;
    const FIELD_DATE = 3;
    const FIELD_WILDCARD_FUNC = 4;


    // global CSV table row counter
    $gbl_entry_i = 1;

    // accumulation array for message objects
    $gbl_message_data = array();

    // whether we will send message object data or not
    $gbl_send_message = false;


    // function for precessing 'notices' field
    $func_notices = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        global $gbl_actuser;

        // assemble JSON field contents
        $notices = '[{"datetime":"' . date ('Y-m-d H:i:s', time()) . '","userid":"' . $gbl_actuser['id'];

        $notices .= '","protocol":"' . urlencode ($field_value) . '"}]';

        // set field value
        $db_values['notices'] = $notices;
        };


    // function for processing 'contact person' field
    $func_contact_person = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        $appellation = $csv_line[$fields['Ansprechpartner (Anrede)'][1]];

        $firstname = $csv_line[$fields['Ansprechpartner (Vorname)'][1]];

        $lastname = $csv_line[$fields['Ansprechpartner (Nachname)'][1]];

        // only look up contact person if all name information is available
        if ($appellation && $firstname && $lastname)
            {
            // get contact ID for name
            $contact_ids = get_contact_ids ($dbh, $firstname, $lastname);

            // count number of ID values
            $ids_n = count ($contact_ids);

            // check if we found any IDs at all
            if ($ids_n)
                {
                // one ID is the only allowed amount of IDs
                if ($ids_n == 1)
                    {
                    // set field values
                    $db_values['contact_person_id'] = $contact_ids[0];

                    $db_values['rtp_contact_person_id'] = $lastname . ', ' . $firstname;
                    }
                else
                    send_message ("Ansprechpartner $firstname $lastname konnte nicht zugewiesen werden: Name zu hÃ¤ufig!");
                }
            // ...we couldn't find an existing contact person, so create one
            else if ($new_contact = create_contact ($dbh, $appellation, $firstname, $lastname))
                {
                $db_values['contact_person_id'] = $new_contact;

                $db_values['rtp_contact_person_id'] = $lastname . ', ' . $firstname;
                }
            }
        };


    // function for processing 'canvasser' field
    $func_canvasser = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        // look up canvasser contact ID
        $query = "SELECT `id` FROM `contacts` WHERE `loginname` = :loginname";

        $stmt = $dbh->prepare ($query);

        // identify canvasser by login name (should be a combination of firstname and lastname)
        $stmt->bindValue (':loginname', $field_value);

        $stmt->execute();

        // fetch ID value
        $canvasser_id = $stmt->fetch (PDO::FETCH_ASSOC)['id'];

        if ($canvasser_id != '')
            // set field value
            $db_values['canvasser_id'] = $canvasser_id;
        else // couldn't find canvasser contact
            send_message ("Akquisiteur konnte nicht zugeordnet werden!");
        };


    // function for processing 'distribution partner' field
    $func_distribution_partner = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        $shortcuts = array('IRGmbH' => 'Improved Reading GmbH & Co. KG',
                           'IRHM' => 'Improved Reading Hannover/MÃ¼nster',
                           'IRO'  => 'Improved Reading Ost',
                           'IRRN' => 'Improved Reading Rhein/Neckar');

        // check if a valid distribution partner shortcut was used
        if (array_key_exists ($field_value, $shortcuts))
            {
            // fetch ID of distribution partner company
            $query = "SELECT `id` FROM `contacts` WHERE `lastname` = :company";

            $stmt = $dbh->prepare($query);

            $stmt->bindValue (':company', $shortcuts[$field_value]);

            $stmt->execute();

            // fetch distribution partner company ID
            $distro_partner_id = $stmt->fetch (PDO::FETCH_ASSOC)['id'];

            if ($distro_partner_id != '')
                {
                // set field values
                $db_values['distribution_partner_id'] = $distro_partner_id;

                $db_values['rtp_distribution_partner_id'] = $shortcuts[$field_value];
                }
            else // couldn't find distribution partner
                send_message ("Vertriebspartner ${shortcuts[$field_value]} konnte nicht zugeordnet werden!");
            }
        else  // no valid distribution partner shortcut found
            send_message ("$field_value ist kein bekanntes Vertriebspartner-KÃ¼rzel!");
        };


    // processing function for 'company' field
    $func_company = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        // check if we have a valid 'company' field value and appellation
        if ($field_value !== '' && $fields['Anrede'][1] != -1)
            {
            // replace newline characters in company names with whitespaces
            $field_value = preg_replace("/\r?\n/", " ", $field_value);
            // fetch appellation
            $appellation = $csv_line[$fields['Anrede'][1]];

            // if appellation is 'company', create a company record
            if ($appellation == 'Firma')
                $db_values['lastname'] = $field_value;
            else  // ...if it isn't, attach the company mentioned in this field
                { // to the current contact
                $query = "SELECT `id` FROM `contacts` WHERE `lastname` = :company";

                $stmt = $dbh->prepare ($query);

                $stmt->bindValue (':company', $field_value);

                $stmt->execute();

                // fetch company ID
                $company_id = $stmt->fetch (PDO::FETCH_ASSOC)['id'];

                if ($company_id != '')
                {
                    // set field values
                    $db_values['company_id'] = $company_id;

                    $db_values['rtp_company_id'] = $field_value;
                }
                else  // failed to look up company
                    send_message ("Firma konnte nicht zugeordnet werden!");
                }
            }
        };


    // processing function for 'street' field
    $func_street = function (&$fields, $csv_line, $field_value, &$db_values, $dbh)
        {
        if ($field_value != '')
            {
            // assemble full street name from street name...
            $full_street = $field_value;

            // ...and house nr. parts
            if ($fields['Strasse'][1] != -1)
                $full_street .= ' ' . $csv_line[$fields['Hausnummer'][1]];

            // set field value
            $db_values['pr_street'] = $full_street;
            }
        };


    // ----- functions for filling in RTP field data

    $rtp_func_contact_person = function ($field_value, $dbh)
        {
        return fetch_rtp_contact_name ($field_value, $dbh);
        };


    $rtp_func_canvasser = function ($field_value, $dbh)
        {
        return fetch_rtp_contact_name ($field_value, $dbh);
        };


    $rtp_func_distribution_partner = function ($field_value, $dbh)
        {
        return fetch_rtp_contact_name ($field_value, $dbh);
        };


    $rtp_func_date = function ($field_value, $dbh)
        {
        return implode ('.', array_reverse (explode ('-', $field_value)));
        };


    function fetch_rtp_contact_name ($contact_id, $dbh)
        {
        // firstname and lastname based on contact ID
        $query = "SELECT `firstname`, `lastname` FROM `contacts` WHERE `id` = :contact_id";

        $stmt = $dbh->prepare ($query);

        $stmt->bindValue (':contact_id', $contact_id);

        $stmt->execute();

        // concatenate first- and lastname
        if ($db_row = $stmt->fetch (PDO::FETCH_ASSOC))
            return $db_row['lastname'] . ', ' . $db_row['firstname'];
        else
            return '';
        }


    // ----- database base fields available for import

    $fields = array(
        'Anrede' => array('appellation', -1, FIELD_STRING),
        'Titel' => array('title', -1, FIELD_STRING),
        'Vorname' => array('firstname', -1, FIELD_STRING),
        'Familienname' => array('lastname', -1, FIELD_STRING),
        'Firma' => array('lastname', -1, FIELD_WILDCARD_FUNC, $func_company),
        'Position in Firma' => array('position', -1, FIELD_STRING),
        'Abteilung' => array('department', -1, FIELD_STRING),
        'Strasse' => array('pr_street', -1, FIELD_FUNCTION, $func_street),
        'Hausnummer' => array('#void', -1),
        'PLZ' => array('pr_plz', -1, FIELD_STRING),
        'Ort' => array('pr_city', -1, FIELD_STRING),
        'Telefon-Nr. 1' => array('pr_telephone', -1, FIELD_STRING),
        'Telefon-Nr. 2' => array('bs_telephone', -1, FIELD_STRING),
        'E-Mail-Adresse' => array('pr_emailaddress', -1, FIELD_STRING),
        'Website' => array('webaddress', -1, FIELD_STRING),
        'Bemerkung' => array('notices', -1, FIELD_FUNCTION, $func_notices),
        'Ansprechpartner (Anrede)' => array('contact_person_id', -1, FIELD_FUNCTION, $func_contact_person),
        'Ansprechpartner (Vorname)' => array('#void', -1),
        'Ansprechpartner (Nachname)' => array('#void', -1),
        'Akquise-Einstufung (A, B, C)' => array('akq_level', -1, FIELD_STRING),
        'Akquise-Temperatur' => array('akq_temperature', -1, FIELD_NUMBER),
        'Bearbeiter (Vor- und Nachname)' => array('#void', -1, FIELD_FUNCTION, $func_canvasser),
        'Franchise-Nehmer' => array('distribution_partner_id', -1, FIELD_FUNCTION, $func_distribution_partner),
        'Aktions-ID' => array('activity_id', -1, FIELD_NUMBER),
        'Wiedervorlage (Datum)' => array('reminder_date', -1, FIELD_DATE),
        );


    // ----- RTP fields generated from database fields

    $rtp_fields = array(
        'appellation' => array('rtp_appellation', null),
        'title' => array('rtp_title', null),
        'firstname' => array('rtp_firstname', null),
        'lastname' => array('rtp_lastname', null),
        'pr_street' => array('rtp_pr_street', null),
        'pr_plz' => array('rtp_pr_plz', null),
        'pr_city' => array('rtp_pr_city', null),
        'pr_telephone' => array('rtp_pr_telephone', null),
        'bs_telephone' => array('rtp_bs_telephone', null),
        'pr_emailaddress' => array('rtp_pr_emailaddress', null),
        'webaddress' => array('rtp_webaddress', null),
        'notices' => array('rtp_notices', "'[-]'"),
        'akq_level' => array('rtp_akq_level', null),
        'akq_temperature' => array('rtp_akq_temperature', null),
        'canvasser_id' => array('rtp_canvasser_id', $rtp_func_canvasser),
        'reminder_date' => array('rtp_reminder_date', $rtp_func_date),
        'position' => array('rtp_position', null),
        'department' => array('rtp_department', null)
        );


    // fetch contact ID(s) based on first- and lastname
    function get_contact_ids ($dbh, $firstname, $lastname)
        {
        $query = "SELECT `id` FROM `contacts` WHERE `firstname` = :firstname AND `lastname` = :lastname";

        $stmt = $dbh->prepare ($query);

        $stmt->bindValue (':firstname', $firstname);

        $stmt->bindValue (':lastname', $lastname);

        $stmt->execute();

        $contact_ids = array();

        // fill ID list with found contact IDs
        while ($row = $stmt->fetch (PDO::FETCH_ASSOC))
            $contact_ids[] = $row['id'];

        return $contact_ids;
        }


    // create a fully formed SQL contact insertion query and return
    // a PDO statement based on it
    function create_contact_query ($dbh, &$contact)
        {
        // begin query
        $query = "INSERT INTO `contacts` (";

        $i = 0;

        // list field names to be inserted
        foreach ($contact as $field => $value)
            {
            $query .= "`" . $field . "`";

            if ($i < count ($contact) - 1)
                $query .= ', ';

            $i++;
            }

        $query .= ") VALUES (";

        $i = 0;

        // list field values
        foreach ($contact as $field => $value)
            {
            $query .= ':' . $field;

            if ($i < count ($contact) - 1)
                $query .= ', ';

            $i++;
            }

        // close query string
        $query .= ")";

        $stmt = $dbh->prepare ($query);

        // bind values
        foreach ($contact as $field => $value)
            $stmt->bindValue (':' . $field, $value);

        // return PDO statement
        return $stmt;
        }


    // create a new contact entry and fill in appellation, firstname and
    // lastname
    function create_contact ($dbh, $appellation, $firstname, $lastname)
        {
        global $rtp_fields;

        // create new contact
        $new_contact = array();

        // add base fields to create complete contact record
        add_base_fields ($new_contact);

        // set basic personal information
        $new_contact['appellation'] = "$appellation";

        $new_contact['firstname'] = "$firstname";

        $new_contact['lastname'] = "$lastname";

        // generate RTP field contents
        add_rtp_fields ($rtp_fields, $new_contact, $dbh);

        // create a PDO statement
        $stmt = create_contact_query ($dbh, $new_contact);

        // create database record for contact
        try
            {
            $stmt->execute();
            }
        catch (PDOException $error)
            {
            send_message ("Datenbankfehler: Kontakt $firstname $lastname konnte nicht angelegt werden!");

            return false;
            }

        // return ID of newly created contact
        return $dbh->lastInsertId();
        }


    // find column headers and look up their column indices
    function assign_fields (&$fields, &$headers)
        {
        // traverse all available database fields
        foreach ($fields as $field => &$details)
            {
            // reset header column counter
            $header_i = 0;

            // traverse all headers (taken from CSV file)
            foreach ($headers as $header)
                {
                // if the header name matches the current field, set its column index
                if ($header == $field)
                    $details[1] = $header_i;

                // increase column index
                $header_i++;
                }
            }
        }


    // add basic contact record information to create valid contact entry
    function add_base_fields (&$db_values)
        {
        global $gbl_actuser;

        $db_values['delflag'] = 0;

        $creation_time = time();

        $date_time = date ('Y-m-d H:i:s', $creation_time);

        $rtp_date_time = date ('d.m.Y, H:i:s', $creation_time);

        // set field values
        $db_values['createdatetime'] = $date_time;

        $db_values['rtp_createdatetime'] = $rtp_date_time;

        $db_values['editdatetime'] = $date_time;

        $db_values['rtp_editdatetime'] = $rtp_date_time;

        $db_values['auditdatetime'] = $date_time;

        $db_values['editors'] = "{\"" . date ('Y-m-d H:i:s', $creation_time) . "\":\"" . $gbl_actuser['id'] . "\"}";

        $db_values['rtp_editors'] = $gbl_actuser['loginname'] . ', ' . date ('(d.m.Y, H:i:s)', $creation_time);
        }


    // add RTP fields to a contact's database field list
    function add_rtp_fields (&$rtp_fields, &$db_values, $dbh)
        {
        // traverse all used database fields
        foreach ($db_values as $field => $value)
            {
            // traverse all available RTP fields
            foreach ($rtp_fields as $rtp_field => $details)
                {
                // if a field name matches an RTP field name, we can fill in
                // the RTP data
                if ($field == $rtp_field)
                    {
                    // check if content information is available for this RTP field
                    if (isset ($details[1]) && $details[1])
                        {
                        // if it's a string, use it as it is
                        if (is_string ($details[1]))
                            $rtp_value = $details[1];
                        else  // if it's a function, call it
                            $rtp_value = $details[1] ($value, $dbh);
                        }
                    else  // just copy the regular value to the RTP field
                        $rtp_value = $value;

                    // set RTP field value
                    $db_values[$details[0]] = $rtp_value;

                    break;
                    }
                }
            }
        }


    // get database field information based on column index
    function get_field (&$fields, $index)
        {
        foreach ($fields as $field => $details)
            {
            // field column matches $index value
            if (isset ($details[1]) && $details[1] == $index)
                return $field;
            }

        // field couldn't be found
        return '';
        }


    // fetch database values from CSV table row
    function fetch_db_values (&$fields, $csv_line, $dbh)
        {
        // create empty array
        $db_values = array();

        // traverse CSV row elements
        for ($i = 0; $i < count ($csv_line); $i++)
            {
            // get field information for current column
            $field_name = get_field ($fields, $i);

            // check if we found a field information entry and if there is type information available
            // for this field
            if ($field_name && isset ($fields[$field_name][2]))
                {
                // set shortcut reference for abbreviation purposes
                $field_ref = &$db_values[$fields[$field_name][0]];

                // check field type and process contents
                switch ($fields[$field_name][2])
                    {
                    case FIELD_NUMBER:
                    case FIELD_STRING:

                        $field_ref = $csv_line[$i];

                        break;
                    case FIELD_DATE:

                        // convert date from human-readable form to SQL date string
                        $date_elements = explode ('.', $csv_line[$i]);

                        if (count($date_elements) == 3)
                            $field_ref = implode ('-', array_reverse ($date_elements));
                        else
                            $field_ref = '';

                        break;
                    case FIELD_FUNCTION:

                        // call data formatting/conversion function
                        if ($fields[$field_name][3])
                            {
                            $fields[$field_name][3] ($fields, $csv_line, $csv_line[$i],
                                                     $db_values, $dbh);
                            }

                        break;
                    case FIELD_WILDCARD_FUNC:

                        // wildcard functions don't necessarily map exactly to one database field
                        // (unlike regular FIELD_FUNCTION fields)
                        if ($fields[$field_name][3])
                            {
                            $fields[$field_name][3] ($fields, $csv_line, $csv_line[$i],
                                                     $db_values, $dbh);
                            }

                        break;
                    default:
                        break;
                    }
                }
            }

        // find empty/unused database fields and remove them
        foreach ($db_values as $field => $value)
            {
            if ($value == '' || $value == "''")
                unset ($db_values[$field]);
            }

        // return list of contact record database field key/value pairs
        return $db_values;
        }


    // send a text message to the client
    function send_message ($message, $finished = false)
        {
        global $entry_i;
        global $gbl_send_message;
        global $gbl_message_data;

        // create new message object
        $reply_obj = array();

        $reply_obj['entry'] = $entry_i;

        $reply_obj['message'] = $message;

        // 'complete' status will end the client-side Ajax loop, 'increment' will
        // keep it going
        $reply_obj['status'] = $finished ? 'complete' : 'increment';

        // add message data to growing list of similar objects
        $gbl_message_data[] = $reply_obj;

        // set global 'yes, we need to send message data' flag
        if (!$gbl_send_message)
            $gbl_send_message = true;
        }


    // send JSON-encoded message object list to client
    function send_reply ($reply_objects)
        {
        echo json_encode ($reply_objects);

        exit;
        }

    // check if at least one importable column could be found in the CSV file
    function check_fields (&$fields)
        {
        // traverse field information list
        foreach ($fields as $field => &$value)
            {
            // at least one column has a proper index, and can be imported
            if ($value[1] != -1)
                return true;
            }

        // couldn't find a single usable column
        return false;
        }


    // check if an entry number paramater was passed to this Ajax call
    // (this is used for incremental updates, so that we know where to resume)
    if (array_key_exists ('line', $_REQUEST))
        $line_i = $_REQUEST['line'];
    else
        $line_i = 0;

    // connect to database
    $dbh = pdoconnect(1);

    // set output filename
    $filename = 'uploads/csv_import/__contact_sheet.csv';

    // try to open the input CSV file
    if ($csv_file = fopen ($filename, 'r'))
        {
        // peek into CSV file and get column headers
        $column_headers = fgetcsv ($csv_file, 0, ';');

        // proceed only if we actually found at least one column header
        if (count ($column_headers))
            {
            // look up column indices and match them to database fields
            assign_fields ($fields, $column_headers);

            // check if at least on importable database field was found
            if (check_fields ($fields))
                {
                $i = 0;

                // skip table rows which have already been processed by earlier
                // Ajax calls
                while ($i < $line_i && fgetcsv ($csv_file, 0, ';') !== false)
                    $i++;

                $entry_i = $i + 1;

                // fetch all table rows from CSV file
                while ($csv_line = fgetcsv ($csv_file, 0, ';'))
                    {
                    // fetch contact record database fields
                    $sql_fields = fetch_db_values ($fields, $csv_line, $dbh);

                    // only import contact record, if usable data could be found
                    if (count ($sql_fields))
                        {
                        // add basic contact record information
                        add_base_fields ($sql_fields);

                        // fill in RTP values
                        add_rtp_fields ($rtp_fields, $sql_fields, $dbh);

                        // create PDO statement for contact creation
                        $stmt = create_contact_query ($dbh, $sql_fields);

                        // import contact to database
                        $stmt->execute();
                        }

                    // current contact record caused at least one message to be sent
                    if ($gbl_send_message)
                        send_reply ($gbl_message_data);

                    $i++;

                    $entry_i++;
                    }
                }
            else
                send_message ('BenÃ¶tigte SpaltenkÃ¶pfe nicht gefunden!');
            }

        // close CSV input file
        fclose ($csv_file);

        // tell client that the import process is finished
        send_message ('Kontaktimport abgeschlossen.', true);

        // remove CSV input file after processing
        unlink ($filename);
        }
    else  // failed to open input CSV file
        send_message ('Konnte Eingabe-Datei nicht Ã¶ffnen - Upload fehlgeschlagen?', true);

    $dbh = null;

    // check if any messages are pending and send them
    if ($gbl_send_message)
        send_reply ($gbl_message_data);

/*
old:

    // start and initialize session
    include ('start_session.php');

    // get file data
    $input_name = $_GET['userfile'];
    $temp_file = $_FILES[$input_name]['tmp_name'];

    $target_path = 'uploads/csv_import/__contact_sheet.csv';

    // upload file
    if (move_uploaded_file ($temp_file, $target_path))
        $_SESSION['vault_value'] = -1;
    else
        $_SESSION['vault_value'] = -3;
*/
?>
