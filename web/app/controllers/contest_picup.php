<?php
if (!Auth::check()) {
	die("未登录");
}
$nowUser = $myUser["username"];
$pos = "pre";
if (isset($_POST["pos"])) {
	$pos = DB::escape($_POST["pos"]);
}
if (isset($_POST["datapic"]) && isset($_POST["contest_id"])) {
	$cid = $_POST["contest_id"];
	$datapic = $_POST["datapic"];
	$last = DB::selectAll("select id from contest_picup where pos='$pos' and contest_id=$cid and user_id='$nowUser'");
	if ((($pos == "pre") && (count($last) == 0)) || (($pos != "pre") && (count($last) < 3))) {
		DB::insert("insert into contest_picup (contest_id, user_id, pos, image) values ($cid, '$nowUser', '$pos', '".DB::escape($datapic)."')");
	}
	else {
		DB::update("update contest_picup set image='".DB::escape($datapic)."' where id=".($last[count($last) - 1]["id"]));
	}
	die("success");
}
?>
