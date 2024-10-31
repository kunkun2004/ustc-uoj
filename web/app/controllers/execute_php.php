<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act1'])) {
    DB::update($_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2']);

    echo $_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2'];
}
?>