<?php
    // -----------------------------------------------------
    // module: changes login data (username, password) of training
    // status: v6 finished
    // -----------------------------------------------------


    // ----- initialization
    include ('mod_init_ajax.php');

    // get database id
    $dbix = $gbl_db_index['contacts'];

    // get database id
    $dbix_t = $gbl_db_index['trainings'];

    // get parameters
    $recordid = urlcomponentdecode ($_REQUEST['recordid']);
    $recordid_tr = urlcomponentdecode ($_REQUEST['tr_recordid']);


    // ----- right?

    // right to change login data?
    if (checkright_profile (1, $dbix_t, 'db_'.$gbl_db_data[$dbix_t]['dbname'].'_traininggroupaccesschange', $recordid_tr, 0, 0, 0, 0) == 0)
        {
        // set error message and exit
        echo '[{"pass":"noright"}]';

        // exit
        exit;
        }


    // ----- write new data

    // connect to database
    $dbh = pdoconnect (1);

    // sql area
    try
        {
        // prepare sql command
        $stmt = $dbh -> prepare ("UPDATE `contacts` SET `contacts`.`loginactive`=:loginactive, `contacts`.`rtp_loginactive`=:rtp_loginactive, `contacts`.`loginname`=:loginname, `contacts`.`rtp_loginname`=:rtp_loginname, `contacts`.`password`=:password, `contacts`.`rtp_password`=:rtp_password WHERE ".$gbl_db_filter[$dbix]." AND `contacts`.`id`=:id AND `contacts`.`tags` LIKE '%|auto_traininggroup|%' AND `contacts`.`delflag`=0");

        // bind id parameter (I)
        $stmt -> bindValue (':id', $recordid);

        // bind id parameter (II)
        if ($_REQUEST['accountactive'] == '1')
            {
            // set active data
            $stmt -> bindValue (':loginactive', '1');
            $stmt -> bindValue (':rtp_loginactive', UI_RECORD_TRAININGGROUPACCESS_ACTIVE);
            }
        else
            {
            // set active data
            $stmt -> bindValue (':loginactive', '0');
            $stmt -> bindValue (':rtp_loginactive', UI_RECORD_TRAININGGROUPACCESS_INACTIVE);
            }

        // bind id parameter (III)
        $stmt -> bindValue (':loginname',     urlcomponentdecode ($_REQUEST['loginname']));
        $stmt -> bindValue (':rtp_loginname', urlcomponentdecode ($_REQUEST['loginname']));
        $stmt -> bindValue (':password',      urlcomponentdecode ($_REQUEST['password']));
        $stmt -> bindValue (':rtp_password',  urlcomponentdecode ($_REQUEST['password']));

        // execute sql command
        $stmt -> execute();
        }

    // error?
    catch (Exception $error)
        {
        // close database
        $dbh = null;

        // log error message
        msg_log (LOG_ERROR_HIDE, 'database error', $error->getMessage(), $dbh, $stmt, $recordid);

        // set error message
        echo '[{"pass": "dberror"}]';

        // exit
        exit;
        }

    // add entry to editors field (contacts)
    editors_addrecord ($dbh, $gbl_actuser['id'], $gbl_db_index['contacts'], $recordid, 'edited', 'loginactive,loginname,password', '');

    // add entry to editors field (trainings)
    editors_addrecord ($dbh, $gbl_actuser['id'], $gbl_db_index['trainings'], $recordid_tr, 'edited', 'traininggroupid_account,traininggroupid_username,traininggroupid_password', '');

    // close database
    $dbh = null;

    // set success message
    echo '[{"pass": "ok"}]';
?>
