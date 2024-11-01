<?php
$problem_content = queryProblemContent($problem['id']);

$is_in_contest = false;
$ban_in_contest = false;

$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;
if ($contest != null) {
    genMoreContestInfo($contest);
    $problem_rank = queryContestUserProblemRank($contest, $myUser, $problem);
    if ($problem_rank == null) {
        become404Page();
    } else {
        $problem_letter = $problem_rank;
    }
}

$tmpc = DB::selectFirst("select camera from contests where id={$_GET['contest_id']}");
$nowUser = $myUser["username"];
$need_camera = $tmpc["camera"];        

$tmpc2 = DB::selectFirst("select camera from contests_registrants where contest_id={$_GET['contest_id']} and username='$nowUser'");
$need_camera2 = $tmpc2 != NULL ? $tmpc2["camera"] : false;

$endtimestr = DB::selectFirst("select * from contests_registrants where contest_id={$_GET['contest_id']} and username='$nowUser'");
$endtime = $endtimestr["finish_time"];

$problem_id = $problem_letter["all_cnt"];
$history_answer = NULL;
$tmp_answer = DB::selectFirst("select choose from contests_user_choose where contest_id={$_GET['contest_id']} and user_id='$nowUser' and problem_id=$problem_id");
if ($tmp_answer != NULL) {
	$history_answer = $tmp_answer["choose"];
}

if (isset($_POST["save_choice"])) {
	$newAnswer = "";
	if (isset($_POST["answer"])) {
		$newAnswer = $_POST["answer"];
	}
	if ($history_answer == NULL) {
		DB::insert("insert into contests_user_choose (contest_id, user_id, problem_id, choose) values ({$_GET['contest_id']}, '$nowUser', $problem_id, '$newAnswer')");
	}
	else {
		DB::update("update contests_user_choose set choose='$newAnswer' where contest_id={$_GET['contest_id']} and user_id='$nowUser' and problem_id=$problem_id");
	}
	die();
}

// var_dump(DB::selectFirst("select * from contests WHERE id = {$_GET['contest_id']}"));
$submission_requirement = json_decode($problem['submission_requirement'], true);
$problem_extra_config = getProblemExtraConfig($problem);
$custom_test_requirement = getProblemCustomTestRequirement($problem);

if ($custom_test_requirement && Auth::check()) {
    $custom_test_submission = DB::selectFirst("select * from custom_test_submissions where submitter = '" . Auth::id() . "' and problem_id = {$problem['id']} order by id desc limit 1");
    $custom_test_submission_result = json_decode($custom_test_submission['result'], true);
}
if ($custom_test_requirement && $_GET['get'] == 'custom-test-status-details' && Auth::check()) {
    if ($custom_test_submission == null) {
        echo json_encode(null);
    } else if ($custom_test_submission['status'] != 'Judged') {
        echo json_encode(array(
            'judged' => false,
            'html' => getSubmissionStatusDetails($custom_test_submission)
        ));
    } else {
        ob_start();
        $styler = new CustomTestSubmissionDetailsStyler();
        if (!hasViewPermission($problem_extra_config['view_details_type'], $myUser, $problem, $submission)) {
            $styler->fade_all_details = true;
        }
        echoJudgementDetails($custom_test_submission_result['details'], $styler, 'custom_test_details');
        $result = ob_get_contents();
        ob_end_clean();
        echo json_encode(array(
            'judged' => true,
            'html' => getSubmissionStatusDetails($custom_test_submission),
            'result' => $result
        ));
    }
    die();
}

$can_use_zip_upload = true;
foreach ($submission_requirement as $req) {
    if ($req['type'] == 'source code') {
        $can_use_zip_upload = false;
    }
}

function handleUpload($zip_file_name, $content, $tot_size)
{
    global $problem, $contest, $myUser, $is_in_contest;

    $content['config'][] = array('problem_id', $problem['id']);
    if ($is_in_contest && $contest['extra_config']["contest_type"] != 'IOI' && !isset($contest['extra_config']["problem_{$problem['id']}"])) {
        $content['final_test_config'] = $content['config'];
        $content['config'][] = array('test_sample_only', 'on');
    }
    $esc_content = DB::escape(json_encode($content));

    $language = '/';
    foreach ($content['config'] as $row) {
        if (strEndWith($row[0], '_language')) {
            $language = $row[1];
            break;
        }
    }
    if ($language != '/') {
        Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
    }
    $esc_language = DB::escape($language);

    $result = array();
    $result['status'] = "Waiting";
    $result_json = json_encode($result);

    // if ($is_in_contest) {
        DB::query("insert into submissions (problem_id, contest_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, ${contest['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', 0)");
    // } else {
    //     DB::query("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', {$problem['is_hidden']})");
    // }
}
function handleCustomTestUpload($zip_file_name, $content, $tot_size)
{
    global $problem, $contest, $myUser;

    $content['config'][] = array('problem_id', $problem['id']);
    $content['config'][] = array('custom_test', 'on');
    $esc_content = DB::escape(json_encode($content));

    $language = '/';
    foreach ($content['config'] as $row) {
        if (strEndWith($row[0], '_language')) {
            $language = $row[1];
            break;
        }
    }
    if ($language != '/') {
        Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
    }
    $esc_language = DB::escape($language);

    $result = array();
    $result['status'] = "Waiting";
    $result_json = json_encode($result);

    DB::insert("insert into custom_test_submissions (problem_id, submit_time, submitter, content, status, result) values ({$problem['id']}, now(), '{$myUser['username']}', '$esc_content', '{$result['status']}', '$result_json')");
}

if ($can_use_zip_upload) {
    $zip_answer_form = newZipSubmissionForm(
        'zip_answer',
        $submission_requirement,
        'uojRandAvaiableSubmissionFileName',
        'handleUpload'
    );
    $zip_answer_form->extra_validator = function () {
        global $ban_in_contest;
        if ($ban_in_contest) {
            return '比赛已经结束或比赛未开始，无法提交！';
        }
        return '';
    };
    $zip_answer_form->succ_href = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
    $zip_answer_form->runAtServer();
}

$answer_form = newSubmissionForm(
    'answer',
    $submission_requirement,
    'uojRandAvaiableSubmissionFileName',
    'handleUpload'
);
$answer_form->extra_validator = function () {
	global $ban_in_contest;
    if ($ban_in_contest) {
        return '比赛已经结束或比赛未开始，无法提交！';
    }
    return '';
};
// $redirect_page = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
$problem_list = queryContestUserProblemList($contest, $myUser);
$found = false;
$nxtprob = 0;
foreach($problem_list as $classlist)
{
    foreach($classlist as $p)
    {
        if($found)
        {
            $nxtprob = $p['id'];
            break 2;
        }
        if($p['id']==$problem['id'])
        {
            $found = true;
        }
    }
}
if(!$nxtprob)
{
    $nxtprob = $problem['id'];
}
$redirect_page = "/contest/{$contest['id']}/problem/{$nxtprob}";
$answer_form->succ_href = $redirect_page;
$answer_form->runAtServer();

if ($custom_test_requirement) {
    $custom_test_form = newSubmissionForm(
        'custom_test',
        $custom_test_requirement,
        function () {
            return uojRandAvaiableFileName('/tmp/');
        },
        'handleCustomTestUpload'
    );
    $custom_test_form->appendHTML(<<<EOD
<div id="div-custom_test_result"></div>
EOD
    );
    $custom_test_form->succ_href = 'none';
    $custom_test_form->extra_validator = function () {
        global $ban_in_contest, $custom_test_submission;
        if ($ban_in_contest) {
            return '请耐心等待比赛结束后题目对所有人可见了再提交';
        }
        if ($custom_test_submission && $custom_test_submission['status'] != 'Judged') {
            return '上一个测评尚未结束';
        }
        return '';
    };
    $custom_test_form->ctrl_enter_submit = true;
    $custom_test_form->setAjaxSubmit(<<<EOD
function(response_text) {custom_test_onsubmit(response_text, $('#div-custom_test_result')[0], '{$_SERVER['REQUEST_URI']}?get=custom-test-status-details')}
EOD
    );
    $custom_test_form->submit_button_config['text'] = UOJLocale::get('problems::run');
    $custom_test_form->runAtServer();
}
$problem_type = ["单选题", "不定项选择题", "判断题", "填空题", "编程题"];
// var_dump("insert into submissions (problem_id, contest_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, ${contest['id']}, now(), '${myUser['username']}')");
?>
<?php
$REQUIRE_LIB['mathjax'] = '';
$REQUIRE_LIB['shjs'] = '';
?>
<!DOCTYPE html>
<html>

<head lang="en">
    <meta charset="UTF-8">
    <title>正式答题</title>
    <link rel="stylesheet" href="/css/public.css" />
    <link rel="stylesheet" href="/css/main.css" />
    <!-- jQuery (necessary for Bootstrap\'s JavaScript plugins) -->
    <script src="/js/jquery.min.js"></script>
</head>

<body>
<?php if ($need_camera && $need_camera2) { ?>
    <div style="position: fixed; right: 0; top: 0; width: 245px;">
	<video id="video" src="" style="width: 100%" />
	<canvas id="canvas"></canvas>
    </div>
<?php } ?>
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
            <div class="answer_notic">
                <p>考试须知</p>
                1、请确保网络良好，不会影响线上考试；<br />
                2、本人信息仅做本次考试用，严禁泄露；<br />
                3、请所有考生诚信考试、独立作答，如出现替考、作弊等现象，一经核实，取消考试成绩。<br />
                4、每一题作答后都需要点击“提交”按钮。
            </div>
            <div class="answer_area">
                <div class="answer_top clearfix">
                    <div class="answer_title">
                        <?= $problem_type[$problem_letter["filter"]["problem_type"]] ?>(<?= $problem_letter["cnt"]; ?>/<?= $problem_letter["filter"]["problem_count"]; ?>)
                    </div>
                    <div class="answer_time">
                        <p>截止时间</p>
                        <p><?php echo $endtime; ?></p>
                    </div>
                </div>

                <div class="answer_main">
		    <div class="dt_area">
		        <div class="question">
                            <?= $problem_content['statement'] ?>
                        </div>

                        <div class="qsub">
                            <input type="button" class="ansub1" name="answer" id="choice-submit-answer-button"
                                value="提交">
                            <input type="submit" class="ansub2" name="finishnow"
                                onclick="location.href='/contest/<?= $contest["id"] ?>/result';" value="交卷">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dt_card_float" onclick="answerCard();">
        <img src="/images/dt_card_float.png" width="133" height="230" alt="datika" />
    </div>
    <div class="dt_card_box">
        <div class="dt_card_title"><span>答题卡</span></div>
        <?php
        include "problem_card.php";
        ?>
		</div>
    <script>const historyAnswer = '<?= $history_answer; ?>';const problemNum = <?= $problem_letter["all_cnt"]; ?>;const problemScore = <?=$problem_letter["filter"]["problem_score"];?>;const token = "<?= crsf_token(); ?>", redirect_page = "<?= $redirect_page; ?>";</script>
    <script src="/js/prob_choose.js"></script>
<?php if ($need_camera && $need_camera2) { ?>
       <script>
// 老的浏览器可能根本没有实现 mediaDevices，所以我们可以先设置一个空的对象
        if (navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
        }

        // 一些浏览器部分支持 mediaDevices。我们不能直接给对象设置 getUserMedia
        // 因为这样可能会覆盖已有的属性。这里我们只会在没有getUserMedia属性的时候添加它。
        if (navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = function (constraints) {

                // 首先，如果有getUserMedia的话，就获得它
                var getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

                // 一些浏览器根本没实现它 - 那么就返回一个error到promise的reject来保持一个统一的接口
                if (!getUserMedia) {
                    return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
                }

                // 否则，为老的navigator.getUserMedia方法包裹一个Promise
                return new Promise(function (resolve, reject) {
                    getUserMedia.call(navigator, constraints, resolve, reject);
                });
            }
        }

        video = document.getElementById('video');
        //video.style.width = document.width + 'px';
        //video.style.height = document.height + 'px';
        video.setAttribute('autoplay', '');
        video.muted = true;
	video.setAttribute('playsinline', '');

	var canvas = document.getElementById("canvas");
        var context = canvas.getContext("2d");
        var constraints = { audio: true, video: { width: 1280, height: 720 } };

	let need_upload = [1, 5, 10, 23, 31];
	function upload_image() {
		context.drawImage(video, 0, 0, 300, 180);
		var datapic = canvas.toDataURL();
		$.post("/contest/<?= $contest["id"]; ?>/mypicup", { datapic: datapic, pos: `t-${problemNum}`, contest_id: <?= $contest["id"]; ?>, }, function (result) {
		    if (result != "success") {
			    console.error("上传失败", result);
		    }
                });
	}
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (mediaStream) {
                var video = document.querySelector('video');

                if ("srcObject" in video) {
                    video.srcObject = mediaStream;
                } else {
                    // 防止在新的浏览器里使用它，应为它已经不再支持了
                    video.src = window.URL.createObjectURL(mediaStream);
                }


                video.onloadedmetadata = function (e) {
			video.play();
			if (need_upload.indexOf(problemNum) !== -1) {
				upload_image();
			}
                };
            })
            .catch(function (err) {
                alert('找不到摄像设备');
	    });
    </script>
<?php } ?>
    <script>
        var answerShow = "N";
        var dtCardElem = document.getElementsByClassName("dt_card_box")[0];
        function answerCard() {
            if (answerShow == "N") {
                answerShow = "Y";
                dtCardElem.style.display = "block";
            } else {
                answerShow = "N";
                dtCardElem.style.display = "none";
            }
        }
    </script>
</body>

</html>
