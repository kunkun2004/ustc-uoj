<?php

    echo "123123";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    echo $_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2'];
    DB::update($_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2']);

    echo $_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2'];
}
?>