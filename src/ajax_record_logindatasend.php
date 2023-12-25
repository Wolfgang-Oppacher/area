<?php
    // -----------------------------------------------------
    // module: sends login data (username, password) to user
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');


    // ----- right?

    // get database id
    $dbix = $gbl_db_index['contacts'];

    // get id (index to record)
    $recordid = $_REQUEST['recordid'];

    // right to abo database
    if (checkright_profile (1, $dbix, 'db_'.$gbl_db_data[$dbix]['dbname'].'_logindatasend', $recordid, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- send login data

    // send mail
    smail (urlcomponentdecode ($_REQUEST['address_from']), urlcomponentdecode ($_REQUEST['address_to']), '', '', urlcomponentdecode ($_REQUEST['subject']), urlcomponentdecode ($_REQUEST['message']), '', '');

    // connect to database
    $dbh = pdoconnect (1);

    // add entry to editors field
    editors_addrecord ($dbh, $gbl_actuser['id'], $gbl_db_index['contacts'], $recordid, 'logindatasent', 'loginname,password', $_REQUEST['address_to']);

    // close database
    $dbh = null;

    // set success
    echo '[{"pass": "ok"}]';
?>
