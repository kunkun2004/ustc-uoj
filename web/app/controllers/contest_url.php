<?php
$contest = validateUInt($_GET['id']) ? queryContest($_GET['id']) : null;
if ($contest != null) {
    genMoreContestInfo($contest);
}
$id = $_GET['id'];
$sql = "SELECT `key` FROM contests WHERE id = $id";
$str = DB::selectFirst($sql);
if($str!=$_GET['contkey'])
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
                    <p>$_GET['uname']</p>
                </div>
                <div class="s_info_item">
                    <p>学校</p>
                    <p>$_GET['school']</p>
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
header("Location: /contest/$_GET['id']");
}
?>