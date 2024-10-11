<?php
	requirePHPLib('form');
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}

	// 创建表单
	$time_form = new UOJForm('time');

	// 比赛标题
	$time_form->addInput(
		'name', 'text', '比赛标题', 'New Contest',
		function($str) {
			return ''; // 验证逻辑，可以根据需求调整
		},
		null
	);

	// 开始时间
	$time_form->addInput(
		'start_time', 'text', '开始时间', date("Y-m-d H:i:s"),
		function($str, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($str);
			} catch (Exception $e) {
				return '无效时间格式';
			}
			return '';
		},
		null
	);

	// 时长（单位：分钟）
	$time_form->addInput(
		'last_min', 'text', '时长（单位：分钟）', 180,
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);

	// 添加结束时间字段
	$time_form->addInput(
		'end_time', 'text', '结束时间', date("Y-m-d H:i:s", strtotime("+3 hours")),  // 默认结束时间为当前时间的3小时后
		function($str, &$vdata) {
			try {
				$vdata['end_time'] = new DateTime($str);
			} catch (Exception $e) {
				return '无效时间格式';
			}
			return '';
		},
		null
	);
	// key（单位：分钟）
	$time_form->addInput(
		'key', 'text', 'key', 123456,
		function($str) {
			return strlen($str) >= 20 ? '长度必须小于20' : '';
		},
		null
	);

	// 处理表单提交
	$time_form->handle = function(&$vdata) {
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		$end_time_str = $vdata['end_time']->format('Y-m-d H:i:s'); // 获取结束时间
		
		$purifier = HTML::pruifier();
		
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		$esc_name = DB::escape($esc_name);
		
		// 插入数据库，新增了 end_time 字段
		DB::query("INSERT INTO contests (name, start_time, last_min, end_time, status) 
				   VALUES ('$esc_name', '$start_time_str', {$_POST['last_min']}, '$end_time_str', 'unfinished')");
	};

	// 成功后跳转
	$time_form->succ_href = "/contests";
	$time_form->runAtServer();
?>

<?php echoUOJPageHeader('添加比赛') ?>
<h1 class="page-header">添加比赛</h1>
<div class="tab-pane active" id="tab-time">
<?php
	// 输出表单的HTML
	$time_form->printHTML();
?>
</div>
<?php echoUOJPageFooter() ?>