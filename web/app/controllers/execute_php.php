<?php

    // echo "123123";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // echo $_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2'];
    echo $_POST['act2'];
    $cid = $_GET['id'];
    $row = DB::selectFirst("select * from contests where id = $cid");
    $endtime = new DateTime($row['end_time']);
    if($endtime > strtotime('+'.$_POST['lastmin'].' minutes'))
    {
        $endtime = strtotime('+'.$_POST['lastmin'].' minutes');
    }
    DB::update($_POST['act1'].date('Y-m-d H:i:s', $endtime).$_POST['act2']);

    echo $_POST['act1'].date('Y-m-d H:i:s', $endtime).$_POST['act2'];
}
?>