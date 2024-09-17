<?php
	if (!isSuperUser($myUser)) {
		become403Page();
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	        $q_problem_id_list = isset($_POST['problem_id']) ? $_POST['problem_id'] : null;
		if ($q_problem_id_list) {
			$q_problem_id = explode(',', $q_problem_id_list);
			$q_problem_id = array_filter($q_problem_id, 'is_numeric');
			$q_problem_id = implode(',', $q_problem_id);
		}
		$q_start_time = null;
		if (isset($_POST['start_time'])) {
			try {
				$q_start_time = new DateTime($_POST["start_time"]);
			} catch (Exception $e) { }
		}
		$q_end_time = null;
                if (isset($_POST['end_time'])) {
                        try {
                                $q_end_time = new DateTime($_POST["end_time"]);
                        } catch (Exception $e) { }
                }
	        $q_submitter = isset($_POST['submitter']) && validateUsername($_GET['submitter']) ? $_POST['submitter'] : null;
	        $q_min_score = isset($_POST['min_score']) && validateUInt($_POST['min_score']) ? $_POST['min_score'] : null;
	        $q_max_score = isset($_POST['max_score']) && validateUInt($_POST['max_score']) ? $_POST['max_score'] : null;
        	$q_language = isset($_POST['language']) ? $_POST['language'] : null;

		$h_q_language = htmlspecialchars($q_language);

		$query = "SELECT * FROM submissions WHERE 1=1";

		if ($q_problem_id !== null) {
			$query .= " AND problem_id IN ($q_problem_id)";
		}
		if ($q_start_time !== null) {
			$query .= " AND submit_time >= '" . $q_start_time->format('Y-m-d H:i:s') . "'";
		}
		if ($q_end_time !== null) {
			$query .= " AND submit_time <= '" . $q_end_time->format('Y-m-d H:i:s') . "'";
		}
		if ($q_submitter !== null) {
			$query .= " AND submitter = '$q_submitter'";
		}
		if ($q_min_score !== null) {
			$query .= " AND score >= $q_min_score";
		}
		if ($q_max_score !== null) {
			$query .= " AND score <= $q_max_score";
		}
		if ($q_language !== null) {
			$query .= " AND language = '$h_q_language'";
		}

		$res = DB::selectAll($query);
		echo "submission id,problem id,uid,status,score,time,language\n";
		foreach ($res as $submission) {
			$score = $submission['score'] ? $submission['score'] : 0;
			echo $submission['id'].','.$submission['problem_id'].','.$submission['submitter'].','.$submission['result_error'].','.$score.','.$submission['submit_time'].','.$submission['language']."\n";
		}
		//var_dump($res);
		
		die();
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>
<div class="d-none d-sm-block">
	<form id="form-search" method="get">
		<div id="form-group-problem_id" class="form-group">
			<label for="input-problem_id" class="control-label"><?= UOJLocale::get('problems::problem id')?>(可以为空，多个用英文逗号,隔开):</label>
			<input type="text" class="form-control" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" maxlength="4" />
		</div>
		<div id="form-group-time" class="form-group">
			<label for="input-start_time" class="control-label">提交时间范围(可以为空):</label>
			<div class="row col-sm-12">
				<input type="text" class="form-control" name="start_time" id="input-start_time" value="<?= $q_start_time ?>" style="width:40%" placeholder="<?= date("Y-m-d H:i:s") ?>" />
                                <label for="input-max_score" class="control-label">~</label>
				<input type="text" class="form-control" name="end_time" id="input-end_time" value="<?= $q_end_time ?>" style="width:40%" placeholder="<?= date("Y-m-d H:i:s") ?>" />
			</div>
		</div>
		<div id="form-group-submitter" class="form-group">
			<label for="input-submitter" class="control-label"><?= UOJLocale::get('username')?>(可以为空):</label>
			<input type="text" class="form-control" name="submitter" id="input-submitter" value="<?= $q_submitter ?>" maxlength="20" />
		</div>
		<div id="form-group-score" class="form-group">
			<label for="input-min_score" class="control-label"><?= UOJLocale::get('score range')?>(可以为空):</label>
			<div class="row col-sm-12">
				<input type="text" class="form-control input-sm" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="3" style="width:4em" placeholder="0" />
				<label for="input-max_score" class="control-label">~</label>
				<input type="text" class="form-control input-sm" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="3" style="width:4em" placeholder="100" />
			</div>
		</div>
		<div id="form-group-language" class="form-group">
			<label for="input-language" class="control-label"><?= UOJLocale::get('problems::language')?>(可以为空):</label>
			<input type="text" class="form-control" name="language" id="input-language" value="<?= $html_esc_q_language ?>" maxlength="10"/>
		</div>
		<button type="submit" id="submit-search" class="btn btn-outline-primary ml-2">导出</button>
	</form>
	<script type="text/javascript">
		$('#form-search').submit(function(e) {
			e.preventDefault();

			$("#submit-search").prop("disabled", true);
			$("#submit-search").text("导出中...");

			qs = {};
			$(['problem_id', 'submitter', 'start_time', 'end_time', 'min_score', 'max_score', 'language']).each(function () {
				if ($('#input-' + this).val()) {
					qs[this] = $('#input-' + this).val();
				}
			});

			$.post("/submissions/export", qs, (response) => {
				$("#submit-search").prop("disabled", false);
				$("#submit-search").text("导出");
				const blob = new Blob([response], { type: 'text/csv;charset=utf-8;' });
				const link = document.createElement("a");
				if (link.download !== undefined) {
					const url = URL.createObjectURL(blob);
					link.setAttribute("href", url);
					link.setAttribute("download", "submissions.csv");
					link.style.visibility = 'hidden';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				}
			}).fail(() => {
				$("#submit-search").prop("disabled", false);
				$("#submit-search").text("导出");
				alert("导出失败!");
			});
		});
	</script>
	<div class="top-buffer-sm"></div>
</div>
<?php echoUOJPageFooter() ?>
