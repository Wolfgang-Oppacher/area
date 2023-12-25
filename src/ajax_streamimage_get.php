<?php

$dir    = 'streaming';
$files = scandir($dir);

$arr = array();

foreach ($files as $k => $v)
    $arr[filemtime($dir.'/'.$v)] = $v;

krsort ($arr);

$i = 0;

    // set image name
    foreach ($arr as $k => $v)
        {
        // file type?
        if (!is_dir ($dir.'/'.$v))
            // newest file?
            if ($i++ == 0)
                echo '[{"pass": "'.$v.'"}]';
            else
                // information image?
                if ($v != 'Information.jpg')
                    // remove older file
                    unlink ($dir.'/'.$v);
        }
?>
