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

	$base64_data = explode(',', $datapic)[1];

	$decoded_data = base64_decode($base64_data);

	$image_name = $cid . '_' . $nowUser . '_' . $pos . '.png';
	$save_path = "/var/uoj_data/contest_picture/$image_name.png";

	if (!file_put_contents($save_path, $decoded_data)) {
		die("fail to write dir");
	}

	$last = DB::selectAll("select id from contest_picup where pos='$pos' and contest_id=$cid and user_id='$nowUser'");
	if($pos == "pre"){
		if (count($last) == 0) {
			DB::insert("insert into contest_picup (contest_id, user_id, pos, image) values ($cid, '$nowUser', '$pos', '$save_path')");
		}
		else {
			DB::update("update contest_picup set image='$save_path' where id=".($last[count($last) - 1]["id"]));
		}
		die("success");
	}
	//else{}
	// to do
}
?>
