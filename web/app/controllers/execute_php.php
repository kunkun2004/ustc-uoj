<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    DB::update($_POST['action']);

    echo "ok ok";
}
?>