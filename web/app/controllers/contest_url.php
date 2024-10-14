<?php
$contest = validateUInt($_GET['id']) ? queryContest($_GET['id']) : null;
if ($contest == null) {
    become404Page();
}
$id = $_GET['id'];
$sql = "SELECT conkey FROM contests WHERE id = $id";
$str = DB::selectFirst($sql);
$canroute = DB::query("SELECT can_route FROM contests WHERE id = $id");
if($str != $_GET['contkey'] || !$canroute)//此处没加md5
{
    $page = <<<EOT
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>登录失败</title>
    <link rel="stylesheet" href="/css/public.css"/>
    <link rel="stylesheet" href="/css/main.css"/>
</head>
<body>
<div class="page_container">
    <div class="oj_center">
        <div class="oj_title">
            <span><?= $contest["name"]; ?></span>
        </div>
        <div class="oj_exam_result">
            <div class="result_tips">你不在考试名单中！请咨询工作人员。</div>
            <div class="student_info">
                <div class="s_info_item">
                    <p>姓名</p>
                    <p><?= $_GET['uname']; ?></p>
                </div>
                <div class="s_info_item">
                    <p>学校</p>
                    <p><?= $_GET['school']; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
EOT;
echo $page;
}
else{
//此处导入名单待完成
crsf_defend();
Auth::logout();
$username = $_GET['phone'];
if(!queryUser($_GET['phone']))
{
    $password = $_GET['qqnum'];
    $qq = $_GET['qqnum'];
    $name = $_GET['uname'];

    $password = getPasswordToStore($password, $username);

    $esc_email = DB::escape($username.'@user.com');
    $sch = 'school:'.$_GET['school'].'speciality:'.$_GET['speciality'].'education:'.$_GET['education'];

    $svn_pw = uojRandString(10);
    DB::query("insert into user_info (username, email, password, svn_password, register_time, usergroup, qq, sch_info, chi_name) 
    values ('$username', '$esc_email', '$password', '$svn_pw', now(), 'S', '$qq', '$sch', '$name')");
}
if(!DB::query("SELECT COUNT(*) FROM contests_registrants WHERE contest_id = $id AND username = '$username'"))
{
    $camera = $_GET['camera'];
    DB::query("insert into contests_registrants (username, user_rating, contest_id, has_participated, camera) 
    values ('$username', 1500, $id, 0, $camera);");
}
else{
    $camera = $_GET['camera'];
    DB::query("UPDATE contests_registrants SET camera = $camera WHERE contest_id = $id AND username = '$username'");
}
if($camera == 1)
{
    redirectTo('/contest/'.$id.'/video');
}
else{
    redirectTo('/contest/'.$id.'/register');
}
}
?>