<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}

	function nullEscape($str) {
		return ($str === 'null' || $str === null) ? "NULL" : "\"".$str."\"";
	}
	
	if (isset($_POST["problem_filters"])) {
		$problem_filters = json_decode($_POST["problem_filters"], true);
		DB::delete("delete from contests_problem_filters where contest_id = {$contest['id']}");
		foreach ($problem_filters as $filter) {
			$sql = "insert into contests_problem_filters (contest_id, problem_type, problem_tags, problem_difficulty, problem_count, problem_score) values ({$contest['id']}, ".nullEscape($filter['problem_type']).", ".nullEscape($filter['problem_tags']).", ".nullEscape($filter['problem_difficulty']).", {$filter['problem_count']}, {$filter['problem_score']})";
			DB::query($sql);
		}
		echo 'ok';
		exit;
	}
	
	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', '比赛标题', $contest['name'],
		function($str) {
			return '';
		},
		null
	);
	$time_form->addInput(
		'start_time', 'text', '开始时间', $contest['start_time_str'],
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
	$time_form->addInput(
		'last_min', 'text', '时长（单位：分钟）', $contest['last_min'],
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);
	$time_form->addInput(
		'end_time', 'text', '结束时间', $contest['end_time_str'],
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
	$time_form->handle = function(&$vdata) {
		global $contest;
		
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		$end_time_str = $vdata['end_time']->format('Y-m-d H:i:s');  // 获取用户输入的结束时间
		
		$purifier = HTML::pruifier();
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		$esc_name = DB::escape($esc_name);
		
		// 更新数据库，添加了对 end_time 的更新
		DB::update("UPDATE contests SET start_time = '$start_time_str', last_min = {$_POST['last_min']}, name = '$esc_name', end_time = '$end_time_str' WHERE id = {$contest['id']}");
	};
	
	function processManagersSubmission() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_participants'])) {
			$managers_text = $_POST['participants'];
			$managers_array = explode("\n", $managers_text);

			$cnt = 0;
			$errinfo = [];
			$id = $_GET['id'];
			//$camera = DB::selectfirst("SELECT camera FROM contests WHERE id = $id");
			foreach($managers_array as $line)
			{
				//魏娜	13156959848	15846956	安徽大学	计算机	本科	开启
				$cnt ++;
				$info = explode('	', $line);
				if ($info=='')
				{
					continue;
				}
				// Output the $info array in a readable format
				
				$username = $info[1];
				if(!validateUsername($username))
				{
					$errinfo[] = [$cnt, '不合法的手机号'];
					continue;
				}
				if(!queryUser($username))//添加用户
				{
					$password = $info[2];
					if($password == ''){
						$password = '123456';
					}

					$password = getPasswordToStore($password, $username);
					
					$esc_email = DB::escape($info[1].'@user.com');
					$sch = 'school:'.$info[3].'speciality:'.$info[4].'education:'.$info[5];

					$svn_pw = uojRandString(10);
					DB::query("insert into user_info (username, email, password, svn_password, register_time, qq, sch_info, chi_name) 
					values ('$username', '$esc_email', '$password', '$svn_pw', now(), '$info[2]', '$sch', '$info[0]')");
				}
				else{//如果已有，则更新密码
					$password = $info[2];
					if($password == ''){
						$password = '123456';
					}

					$password = getPasswordToStore($password, $username);
					DB::query("update user_info set password = $password where username = '$username'");
				}
				//用户不在名单中
				if(DB::selectFirst("select * from contests_registrants where username = '$info[1]' and contest_id = $id") == null)
				{
					$newcamera = $info[6]=='开启'?1:0;
					DB::query("insert into contests_registrants (username, user_rating, contest_id, has_participated, camera) 
					values ('$username', 1500, $id, 0, $newcamera);");
				}
				else
				{
					$errinfo[]=[$cnt, '用户已经出现'];
				}

			}
			echo '错误信息：[0]表示行数，[1]表示问题，空则没有问题';
			echo '<pre>';
			print_r($errinfo);
			echo '</pre>';
		}
	}
	
	// 调用处理函数
	processManagersSubmission();

	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "不存在名为{$username}的用户";
			}
			return '';
		},
		function($type, $username) {
			global $contest;
			if ($type == '+') {
				DB::query("insert into contests_permissions (contest_id, username) values (${contest['id']}, '$username')");
			} else if ($type == '-') {
				DB::query("delete from contests_permissions where contest_id = ${contest['id']} and username = '$username'");
			}
		}
	);
	
	$problems_form = newAddDelCmdForm('problems',
		function($cmd) {
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "无效题号";
			}
			$problem_id = $matches[1];
			if (!validateUInt($problem_id) || !($problem = queryProblemBrief($problem_id))) {
				return "不存在题号为{$problem_id}的题";
			}
			if (!hasProblemPermission(Auth::user(), $problem)) {
				return "无权添加题号为{$problem_id}的题";
			}
			return '';
		},
		function($type, $cmd) {
			global $contest;
			
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "无效题号";
			}
			
			$problem_id = $matches[1];
			
			if ($type == '+') {
				DB::insert("insert into contests_problems (contest_id, problem_id) values ({$contest['id']}, '$problem_id')");
			} else if ($type == '-') {
				DB::delete("delete from contests_problems where contest_id = {$contest['id']} and problem_id = '$problem_id'");
			}
			
			if (isset($matches[2])) {
				switch ($matches[2]) {
					case '[sample]':
						unset($contest['extra_config']["problem_$problem_id"]);
						break;
					case '[full]':
						$contest['extra_config']["problem_$problem_id"] = 'full';
						break;
					case '[no-details]':
						$contest['extra_config']["problem_$problem_id"] = 'no-details';
						break;
				}
				$esc_extra_config = json_encode($contest['extra_config']);
				$esc_extra_config = DB::escape($esc_extra_config);
				DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
			}
		}
	);
	
	if (isSuperUser($myUser)) {
		$rating_k_form = new UOJForm('rating_k');
		$rating_k_form->addInput('rating_k', 'text', 'rating 变化上限', isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400,
			function ($x) {
				if (!validateUInt($x) || $x < 1 || $x > 1000) {
					return '不合法的上限';
				}
				return '';
			},
			null
		);
		$rating_k_form->handle = function() {
			global $contest;
			$contest['extra_config']['rating_k'] = $_POST['rating_k'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$rating_k_form->runAtServer();
		
		$rated_form = new UOJForm('rated');
		$rated_form->handle = function() {
			global $contest;
			if (isset($contest['extra_config']['unrated'])) {
				unset($contest['extra_config']['unrated']);
			} else {
				$contest['extra_config']['unrated'] = '';
			}
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$rated_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
		$rated_form->submit_button_config['text'] = isset($contest['extra_config']['unrated']) ? '设置比赛为rated' : '设置比赛为unrated';
		$rated_form->submit_button_config['smart_confirm'] = '';
	
		$rated_form->runAtServer();
		
		$version_form = new UOJForm('version');
		$version_form->addInput('standings_version', 'text', '排名版本', $contest['extra_config']['standings_version'],
			function ($x) {
				if (!validateUInt($x) || $x < 1 || $x > 2) {
					return '不是合法的版本号';
				}
				return '';
			},
			null
		);
		$version_form->handle = function() {
			global $contest;
			$contest['extra_config']['standings_version'] = $_POST['standings_version'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$version_form->runAtServer();

		$contest_type_form = new UOJForm('contest_type');
		$contest_type_form->addInput('contest_type', 'text', '赛制', $contest['extra_config']['contest_type'],
			function ($x) {
				if ($x != 'OI' && $x != 'ACM' && $x != 'IOI') {
					return '不是合法的赛制名';
				}
				return '';
			},
			null
		);
		$contest_type_form->handle = function() {
			global $contest;
			$contest['extra_config']['contest_type'] = $_POST['contest_type'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
		};
		$contest_type_form->runAtServer();
	}
	
	$time_form->runAtServer();
	$managers_form->runAtServer();
	$problems_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 比赛管理') ?>
<h1 class="page-header" align="center"><?=$contest['name']?> 管理</h1>
<ul class="nav nav-tabs mb-3" role="tablist">
	<li class="nav-item"><a class="nav-link active" href="#tab-time" role="tab" data-toggle="tab">比赛时间</a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-managers" role="tab" data-toggle="tab">管理者</a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-participants" role="tab" data-toggle="tab">参赛者</a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-problems" role="tab" data-toggle="tab">试题</a></li>
	<?php if (isSuperUser($myUser)): ?>
	<li class="nav-item"><a class="nav-link" href="#tab-others" role="tab" data-toggle="tab">其它</a></li>
	<?php endif ?>
	<li class="nav-item"><a class="nav-link" href="/contest/<?=$contest['id']?>" role="tab">返回</a></li>
</ul>
<div class="tab-content top-buffer-sm">
	<div class="tab-pane active" id="tab-time">
		<?php $time_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-managers">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$row_id = 0;
	$result = DB::query("select username from contests_permissions where contest_id = {$contest['id']}");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
		<?php $managers_form->printHTML(); ?>
	</div>
	

	<div class="tab-pane" id="tab-participants">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$row_id = 0;
	$result = DB::query("select username from contests_registrants where contest_id = {$contest['id']}");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">注意：请保证手机号列是纯数字，且一个手机号只有一个人使用。默认密码为qq号，若qq为空则设为123456</p>
		<form action="" method="POST">
			<div id="div-managers_cmds" class="form-group">
				<label for="input-managers" class="control-label">导入用户</label>
				<textarea class="form-control" name="participants" id="input-managers"></textarea>
				<br> <!-- Added a line break here -->
				<button type="submit" class="btn btn-primary" name="submit_participants">提交</button>
			</div>
		</form>

	</div>


	<div class="tab-pane" id="tab-problems">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>题目类型</th>
					<th>题目标签</th>
					<th>题目难度</th>
					<th>题目数量</th>
					<th>题目分值</th>
					<th>小计</th>
				</tr>
			</thead>
			<?php
			$problem_filters = DB::selectAll("select * from contests_problem_filters where contest_id = {$contest['id']}");
			$all_tags = DB::selectAll("SELECT  DISTINCT(`tag`) FROM `problems_tags`");
			?>
			<tbody id="problem-filter-body">
			</tbody>
		</table>
		<p><button class="btn btn-success" onclick="newProblemFilter()">新建大题</button> <button class="btn btn-primary" id="save-filter-button" onclick="saveFilters()">保存</button></p>
		<p>总分：<span id="problem-total-score"></span></p>
		<script>
			const problem_filters = JSON.parse(`<?= json_encode($problem_filters) ?>`);
			const all_tags = JSON.parse(`<?= json_encode($all_tags) ?>`);
			const tags = [];
			const difficulties = [];
			const types = ["单选题", "不定项选择题", "判断题", "填空题", "编程题"];
			for (const t of all_tags) {
				if (t.tag.slice(0, 3) === '难度:') {
					if (t.tag.length > 3)
						difficulties.push(t.tag.slice(3));
				}
				else if (t.tag === 'choice' || t.tag === 'fill') {
					// type
				}
				else {
					if (t.tag)
						tags.push(t.tag);
				}
			}
			function genTagsSelect(id) {
				const select = $("<select></select>").addClass('form-control');
				select.append($("<option></option>").text('不限制').val('null'));
				for (const tag of tags) {
					select.append($("<option></option>").text(tag).val(tag));
				}
				select.change(function () {
					problem_filters[id].problem_tags = select.val();
					genTable();
				});
				return select;
			}
			function genDifficultiesSelect(id) {
				const select = $("<select></select>").addClass('form-control');
				select.append($("<option></option>").text('不限制').val('null'));
				for (const difficulty of difficulties) {
					select.append($("<option></option>").text(difficulty).val(difficulty));
				}
				select.change(function () {
					problem_filters[id].problem_difficulty = select.val();
					genTable();
				});
				return select;
			}
			function genTypesSelect(id) {
				const select = $("<select></select>").addClass('form-control');
				select.append($("<option></option>").text('不限制').val('null'));
				for (const type in types) {
					select.append($("<option></option>").text(types[type]).val(type));
				}
				select.change(function () {
					problem_filters[id].problem_type = select.val();
					genTable();
				});
				return select;
			}
			function genCountInput(id) {
				const input = $("<input></input>").addClass('form-control').attr('type', 'number').attr('min', '0').val('0');
				input.change(function () {
					problem_filters[id].problem_count = parseInt(input.val());
					genTable();
				});
				return input;
			}
			function genScoreInput(id) {
				const input = $("<input></input>").addClass('form-control').attr('type', 'number').attr('min', '0').val('0');
				input.change(function () {
					problem_filters[id].problem_score = parseInt(input.val());
					genTable();
				});
				return input;
			}
			function newProblemFilter() {
				problem_filters.push({
					problem_type: 'null',
					problem_tags: 'null',
					problem_difficulty: 'null',
					problem_count: 0,
					problem_score: 0
				});
				genTable();
			}
			let lock = false;
			function saveFilters() {
				if (lock) return;
				lock = true;
				$("#save-filter-button").text('保存中...');
				$("#save-filter-button").attr('disabled', 'disabled');
				$.post('/contest/<?=$contest["id"];?>/manage', {
					problem_filters: JSON.stringify(problem_filters)
				}, function (data) {
					if (data === 'ok') {
						alert('保存成功');
					} else {
						alert('保存失败');
					}
				}).always(function () {
					lock = false;
					$("#save-filter-button").text('保存');
					$("#save-filter-button").removeAttr('disabled');
				});
			}
			const tmp = genTagsSelect();
			function genTable() {
				const k = $("#problem-filter-body");
				k.empty();
				let total_score = 0;
				for (let i = 0; i < problem_filters.length; i++) {
					const filter = problem_filters[i];
					const row = $("<tr></tr>");
					row.append($("<td></td>").text(i + 1));
					row.append($("<td></td>").append(genTypesSelect(i).val(String(filter.problem_type))));
					row.append($("<td></td>").append(genTagsSelect(i).val(String(filter.problem_tags))));
					row.append($("<td></td>").append(genDifficultiesSelect(i).val(String(filter.problem_difficulty))));
					row.append($("<td></td>").append(genCountInput(i).val(filter.problem_count)));
					row.append($("<td></td>").append(genScoreInput(i).val(filter.problem_score)));
					row.append($("<td></td>").text(filter.problem_count * filter.problem_score));
					row.append($("<td></td>").append($("<button></button>").addClass('btn btn-danger').text('删除').click(function () {
						problem_filters.splice(i, 1);
						genTable();
					})));
					total_score += filter.problem_count * filter.problem_score;
					k.append(row);
				}
				$("#problem-total-score").text(total_score);
			}
			genTable();
		</script>
	</div>
	<?php if (isSuperUser($myUser)): ?>
	<div class="tab-pane" id="tab-others">
		<div class="row">
			<div class="col-sm-12">
				<h3>Rating控制</h3>
				<div class="row">
					<div class="col-sm-3">
						<?php $rated_form->printHTML(); ?>
					</div>
				</div>
				<div class="top-buffer-sm"></div>
				<?php $rating_k_form->printHTML(); ?>
			</div>
			<div class="col-sm-12 top-buffer-sm">
				<h3>版本控制</h3>
				<?php $version_form->printHTML(); ?>
			</div>
			<div class="col-sm-12 top-buffer-sm">
				<h3>赛制</h3>
				<?php $contest_type_form->printHTML(); ?>
			</div>
		</div>
		<form action="" method="POST" enctype="multipart/form-data">
			<label for="image">选择图片：</label>
			<input type="file" name="image" id="image" required>
			<button type="submit" name="upload_image">上传</button>
		</form>
	</div>
		
	<?php endif ?>
</div>
<?php echoUOJPageFooter() ?>
