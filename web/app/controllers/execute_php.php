<?php

    // echo "123123";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // echo $_POST['act1'].date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')).$_POST['act2'];
    
    $cid = $_GET['id'];
    $row = DB::selectFirst("select * from contests where id = $cid");
    // echo $row['end_time'];
    // var_dump($row);
    $endtime = new DateTime($row['end_time']);
    $newtime = new DateTime(date('Y-m-d H:i:s', strtotime('+'.$_POST['lastmin'].' minutes')));
    // echo $endtime.'\n';
    // echo $newtime.'\n';
    if($endtime > $newtime)
    {
        $endtime = $newtime;
    }
    DB::update($_POST['act1'].$endtime->format('Y-m-d H:i:s').$_POST['act2']);
    echo $_POST['act1'].$endtime->format('Y-m-d H:i:s').$_POST['act2'];
}
?>