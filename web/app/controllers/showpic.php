<?php
	
    if (!isSuperUser($myUser)) {
        become403Page();
    }

?>

<?php echoUOJPageHeader('查看图片') ?>
<h1 class="page-header">查看图片</h1>
<div class="tab-pane active" id="tab-time">
<?php

    $nowUser = $_GET["username"];
    $cid = $_GET["id"];
	// 输出$save_path = "/var/uoj_data/contest_picture/$nowUser/$image_name.png";下是所有图片
    $save_path = "/var/uoj_data/contest_picture/$nowUser/";
    $files = scandir($save_path);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            echo "<img src=".$save_path.$file." alt='$file'>";
            echo "<br />";
            //输出文件名
            echo "<p>$file</p>";
            echo "<br />";
        }
    }
?>
</div>
<?php echoUOJPageFooter() ?>