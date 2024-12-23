<?php
$contest = validateUInt($_GET['id']) ? queryContest($_GET['id']) : null;
if ($contest != null) {
    genMoreContestInfo($contest);
}
$username = $myUser['username'];
$user = queryUser($username);//这里$user后面用到了'name'和'school'，当前数据库没有这个项，后面加上
$school = '';
if (preg_match('/school:(.*?)speciality:/', $user['sch_info'], $matches)) {
    $school = $matches[1];
} 
DB::update("update contests_registrants set finish_time = now() where username = '{$myUser['username']}' and contest_id={$_GET['id']}");


?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>考试成绩</title>
    <link rel="stylesheet" href="/css/public.css"/>
    <link rel="stylesheet" href="/css/main.css"/>
</head>
<body>
<div class="page_container">
    <div class="oj_header">
        <ul class="oj_nav clearfix">
            <li><a href="/contests">赛事</a></li>
            <li><a href="#">题库</a></li>
        </ul>
    </div>
    <div class="oj_center">
        <div class="oj_title">
            <span><?= $contest["name"]; ?></span>
        </div>
        <div class="oj_exam_result">
            <div class="result_img"><img src="/images/result_img.png" width="266" height="212" alt="result"/></div>
            <div class="result_tips">本场考试已结束，您已成功交卷！</div>
            <div class="student_info">
                <div class="s_info_item">
                    <p>姓名</p>
                    <p><?= urldecode($user['chi_name']); ?></p>
                </div>
                <div class="s_info_item">
                    <p>学校</p>
                    <p><?= $school; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>