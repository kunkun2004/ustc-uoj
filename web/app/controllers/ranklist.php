<?php
	if (isset($_GET['type']) && $_GET['type'] == 'rating') {
		$config = array('page_len' => 100);
	} else {
		become404Page();
	}
?>
<?php echoUOJPageHeader('比赛排行榜') ?>

<!-- 导出按钮 -->
<a href="export_ranklistra" class="btn btn-primary">导出排行榜</a>

<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
