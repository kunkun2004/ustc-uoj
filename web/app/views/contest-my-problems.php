<div>
<?php
$cnt = 0;
$pcnt = 0;
$chinese_count = ["一", "二", "三", "四", "五", "六", "七", "八", "九", "十", "十一", "十二", "十三"];

foreach ($problem_filters as $problem_filter) {
?>
<p>
<b><?= $chinese_count[$cnt]; ?>、<?= $problem_filter["problem_type"] === NULL ? "全部题型" : $problem_type[$problem_filter["problem_type"]]; ?>(共<?= $problem_filter["problem_count"]; ?>题，每题<?= $problem_filter["problem_score"]; ?>分，满分<?= intval($problem_filter["problem_count"]) * intval($problem_filter["problem_score"]); ?>分)</b>
<table class="table table-hover">
	<thead>
		<tr>
			<th>题号</th>
			<th>标题</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($problem_list[$cnt] as $p) {
		$pcnt ++;
?>
		<tr>
			<td><?= $pcnt; ?></td>
			<td><a href="/contest/<?= $contest["id"]; ?>/problem/<?= $p["id"]; ?>"><?= $p["title"]; ?></a></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<?php
	$cnt ++;
}
	?>
</div>
