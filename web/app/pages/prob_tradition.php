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

//var_dump($contest["end_time"]);

$tmpc = DB::selectFirst("select camera from contests where id={$_GET['contest_id']}");
$nowUser = $myUser["username"];
$need_camera = $tmpc["camera"];

$tmpc2 = DB::selectFirst("select camera from contests_registrants where contest_id={$_GET['contest_id']} and username='$nowUser'");
$need_camera2 = $tmpc2 != NULL ? $tmpc2["camera"] : false;

//var_dump($user_finish_time);
$submission_requirement = json_decode($problem['submission_requirement'], true);
$problem_extra_config = getProblemExtraConfig($problem);
$custom_test_requirement = getProblemCustomTestRequirement($problem);

$endtimestr = DB::selectFirst("select * from contests_registrants where contest_id={$_GET['contest_id']} and username='$nowUser'");
$endtime = $endtimestr["finish_time"];

function chktime()
{  
    global $endtime;
    $currentTime = new DateTime();
    // 创建 DateTime 对象来表示 endtime
    $endTimeObj = new DateTime($endtime);
    // 比较两个 DateTime 对象
    if ($currentTime > $endTimeObj) {
        return 1;
    } else {
        return 0;
    }
}

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
            'html' => getSubmissionStatusDetails($custom_test_submission),
            'detail' => $custom_test_submission_result
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
	    'result' => $result,
            'detail' => $custom_test_submission_result
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
// $redirect_page = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';$problem_list = queryContestUserProblemList($contest, $myUser);

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
        //if ($ban_in_contest) {
        //    return '请耐心等待比赛结束后题目对所有人可见了再提交';
        //}
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
$problem_type = ["单选题", "多选题", "判断题", "填空题", "编程题"];
?>
<?php
$REQUIRE_LIB['mathjax'] = '';
$REQUIRE_LIB['shjs'] = '';
?>
<?php
$limit = getUOJConf("/var/uoj_data/{$problem['id']}/problem.conf");
$time_limit = $limit['time_limit'];
$memory_limit = $limit['memory_limit'];
?>
<!DOCTYPE html>
<html>

<head lang="en">
    <meta charset="UTF-8">
    <title>正式答题-实操题</title>
    <link rel="stylesheet" href="/css/public.css" />
    <link rel="stylesheet" href="/css/main.css" />
    <script type="text/javascript">uojHome = 'http://localhost'</script>

    <style>
textarea {
height: 300px;
    display: block;
    width: 100%;
    padding: 10px 15px;
    font-size: 16px;
    font-family: consolas;
    outline: none;
    border: 1px solid #888;
    border-radius: 5px;
    box-sizing: border-box;
    transition: all .2s;
}
textarea:focus {
    border-color: #1780db;
}
pre {
background-color: #eaeaea;
width: 100%;
padding: 10px 15px;
box-sizing: border-box;
}
	</style>
    <!-- jQuery (necessary for Bootstrap\'s JavaScript plugins) -->
    <script src="/js/jquery.min.js"></script>
    <!-- jQuery autosize -->
    <!--script src="/js/jquery.autosize.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('textarea').autosize();
        });
    </script-->

    <!-- jQuery cookie -->
    <script src="/js/jquery.cookie.min.js"></script>
    <!-- jQuery modal -->
    <script src="/js/jquery.modal.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/js/popper.min.js?v=2019.5.31"></script>
    <!-- Color converter -->
    <script src="/js/color-converter.min.js"></script>
    <!-- uoj -->
    <!--script src="/js/uoj.js?v=2017.01.01"></script-->
    <!-- readmore -->
    <script src="/js/readmore/readmore.min.js"></script>
    <!-- LAB -->
    <script src="/js/LAB.min.js"></script>
    <!-- favicon -->
    <link rel="shortcut icon" href="/images/favicon.ico" />
    <!-- MathJax -->
    <script type="text/x-mathjax-config">
    MathJax.Hub.Config({
        showProcessingMessages: false,
        tex2jax: {
            inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],
            processEscapes:true
        },
        menuSettings: {
            zoom: "Hover"
        }
    });
</script>
    <script src="//cdn.bootcss.com/mathjax/2.6.0/MathJax.js?config=TeX-AMS_HTML"></script>

    <!-- jquery form -->
    <script src="/js/jquery.form.min.js"></script>





    <!-- shjs -->
    <link type="text/css" rel="stylesheet" href="/css/sh_typical.min.css" />
    <script src="/js/sh_main.min.js"></script>
    <script type="text/javascript">$(document).ready(function () { sh_highlightDocument() })</script>


    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->

    <script type="text/javascript">
        before_window_unload_message = null;
        $(window).on('beforeunload', function () {
            if (before_window_unload_message !== null) {
                return before_window_unload_message;
            }
        });
    </script>
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
                4、每一题作答后都需要点击“提交”按钮，注意保存自己的代码，比赛中无法查看之前的提交。
            </div>
            <div class="answer_area">
                <div class="answer_top clearfix">
                    <div class="answer_title">
                        <?= $problem_type[$problem_letter["filter"]["problem_type"]] ?>(<?= $problem_letter["cnt"]; ?>/<?= $problem_letter["filter"]["problem_count"]; ?>)
                    </div>
                    <div class="answer_time" style="box-sizing: content-box">
                        <p style="margin: 0;">截止时间</p>
                        <p style="margin: 0;"><?php echo $endtime; ?></p>
                    </div>
                </div>

                <div class="answer_main clearfix">
                    <div class="sc_question">
		    <h3 class="sc_id"><?= $problem_letter["all_cnt"]; ?>. [编程题](<?=$problem_letter["filter"]["problem_score"];?>分)</h3>
<div class="question1" style="font-size: 12px;line-height: 1.5;">1.请严格按照每道题目给出的输入/输出样例编写相关I/O代码，数字间的默认间隔是一个空格，样例以外的提示信息请不要在屏幕上输出。<br />
2.请大家确保提交的代码可以在指定的编译条件下正确地编译执行，否则自动评测程序将给出编译错误或运行时错误的信息。<br />
3.每道编程题会有多个测试用例，每通过一些测试用例可以获得相应的分值，但只有通过全部测试用例才能拿到这题全部的分数。<br />
4.代码须点击提交，以最后一次提交为评审依据。<br />
5.C语言请选择"C" , C++请选择"C++" , java请选择"java8" , python请选择" python3"。<br />
6.Python程序仅可以使用Python自带的库，评测时不会安装其他的扩展库。<br />
7.程序中应只包含计算模块，不要包含任何其他的模块，比如图形、系统接口调用、系统中断等。对于系统接口的调用都应通过标准库来进行。<br />
8.程序中引用的库应该在程序中以源代码的方式写出,在提交时也应当和程序的其他部分一起提交。</div>		    
	<div class="question_require">
                            时间限制：<?= $time_limit != null ? "$time_limit s" : "N/A" ?>
                            空间限制：<?= $memory_limit != null ? "$memory_limit MB" : "N/A" ?>
                        </div>
                        <h3 style="margin: 10px 0"><?= $problem["title"]; ?></h3>
<div id="problem_statement"></div>
<script>
const problem_raw_statement = `<?php echo str_replace('`','\\`',$problem_content['statement_md']); ?>`.replace(/&amp;/g, "&");;
const problem_lines = problem_raw_statement.split("\n");
let last = '';
let key_title = ["输入格式", "输出格式", "输入样例", "输出样例"];
for (let i of problem_lines) {
	let flag = true;
	while (flag) {
		let flag2 = -1;
		for (let j of key_title) {
			if (i.startsWith(j)) {
				flag2 = j;
				break;
			}
		}
		if (flag2 === -1) {
			flag = false;
			break;
		}
		if (last) {
			$("#problem_statement").append($("<p class='question1' />").html(last));
			last = ""
		}
		let pos = i.search(/[:：]/g);
		$("#problem_statement").append($("<p class='question_small_title' />").html(i.slice(0, pos)));
		i = i.slice(pos + 1);
	}
	if (i) {
		last += i + "<br />";
	}
}
                if (last) {
                        $("#problem_statement").append($("<p class='question1' />").html(last));                                                last = ""
                }
</script>
			<!--div class="raw-statement">
			    <?php //echo $problem_content['statement_md']; ?>
                        </div-->
                    </div>
                    <div class="sc_dt_area">
			<?php //$answer_form->printHTML(); ?>
			<div class="code_selection">
                        	<select name="codeSelection" id="codeSelection">
                            		<option value="C">C语言</option>
                          		<option value="C++">C++</option>
                            		<option value="Java8">Java</option>
                        		<option value="Python3">python</option>
                        	</select>
                        	<div class="editor_name">代码编辑器(text/x-csrc)</div>
			</div>
			<div class="editor_box" style="width: 100%; margin: 0;height: auto;box-sizing: border-box">
				<div class="form-group">
					<label for="code">代码</label>
					<textarea name="code" id="code" style="height: 300px;"></textarea>
				</div>
				<div class="form-group">
					<label for="textInput">输入文件(用于自定义测试)</label>
					<textarea name="textInput" id="textInput" style="height: 150px;"></textarea>
				</div>
			</div>
                        <div class="qsub">
                            <button class="ansub" name="answer" onclick="doCustomTest()">在线编译</button>
                            <button class="ansub1" name="answer" onclick="submitAnswer()">提交</button>
                            <input type="submit" class="ansub2" name="finishnow"
                                onclick="location.href='/contest/<?= $contest["id"] ?>/result';" value="交卷">
			</div>
			<div id="customTestResult" style="padding: 15px; width: 100%; box-sizing: border-box;">
			</div>
<script>
const codeSelection = document.getElementById("codeSelection");
const code = document.getElementById("code");
const textInput = document.getElementById("textInput");
				const token = "<?= crsf_token(); ?>";
$("#button-submit-answer").hide();
let lock = false;

code.value = localStorage.getItem(`code@${location.href}`);
function doLock() {
	lock = true;
	$("button[name=answer]").attr("disabled", "disabled");
	$("button[name=answer]").css("background-color", "#eeeeee")
}
function unlock() {
	lock = false;
	$("button[name=answer]").attr("disabled", null);
	$("button[name=answer]").css("background-color", "")
}
function doCustomTest() {
    var time = <?= strtotime($endtime)?>;
    var date_now = new Date()/1000;
    
	if (lock) { alert("请耐心等待上一次提交结束!"); return }
    if(date_now > time) { alert("考试已结束！"); window.location.href="/contest/<?=$_GET['contest_id']?>/result"; return }
	doLock();
			var ok = true;
		var post_data = {};
		if (post_data != {}) {
			post_data['check-custom_test'] = "";
			$.ajax({
				url : '',
				type : 'POST',
				dataType : 'json',
				async : false,
				data : post_data,
				success : function(data) {
					if (data.extra != undefined) {
						alert(data.extra);
						ok = false;
					}
				}
			});
		}
		const customTestData = new FormData();
		customTestData.append("_token", token);
		customTestData.append("custom_test_answer_language", codeSelection.value);
		customTestData.append("custom_test_answer_upload_type", "editor");
		customTestData.append("custom_test_answer_editor", code.value);
		customTestData.append("custom_test_answer_file", "");
		customTestData.append("custom_test_input_upload_type", "editor");
		customTestData.append("custom_test_input_editor", textInput.value);
		customTestData.append("custom_test_input_file", "");
		customTestData.append("submit-custom_test", "custom_test");
   		$.ajax({
                    url : '',
                    type : 'POST',
		    data : customTestData,
		    processData: false,
		    contentType: false,
		    success: function(response_text) {
			    if (response_text != '') {
				    $("#customTestResult").html('<div class="text-danger">' + response_text + '</div>');
			    }
			    else {
				    $("#customTestResult").html("自定义测试编译运行中...");
				    	var update = function() {
		var can_next = true;
		$.get("?get=custom-test-status-details",
			function(data) {
				if (data?.judged === undefined) {
					$("#customTestResult").html('<div class="text-danger">error</div>');
				} else {
					if (data.judged) {
						if (data.detail) {
							if (data.detail.error) {
								let error = data.detail.details;
								let domparser = new DOMParser();                                                                                                     let doc = domparser.parseFromString(data.detail.details, 'text/xml');                                                                error = doc.querySelector("error")?.innerHTML ?? data.detail.details;
								$("#customTestResult").html('<p><b class="text-danger">' + data.detail.error + '</b><pre>' + error + '</pre><p>');
							}
							else {
								let domparser = new DOMParser();
								let doc = domparser.parseFromString(data.detail.details, 'text/xml');
								let res = doc.querySelector("out").innerHTML;
								$("#customTestResult").html('<p><b class="text">运行结束!</b><br>耗时: ' + data.detail.time + 'ms，内存：' + data.detail.memory + 'kb<br><pre>' + res + '</pre></p>');
							}
						}
						else {
							$("#customTestResult").html('<div class="text-danger">error</div>');
						}
						can_next = false;
						unlock();
					}
				}
			}, 'json')
		.always(function() {
			if (can_next) {
				setTimeout(update, 500);
			}
		});
	};
	setTimeout(update, 500);
			    }
	            }
               }).always(function () { unlock(); });
}
function submitAnswer() {
    var time = <?= strtotime($endtime)?>;
    var date_now = new Date()/1000;
    
	if (lock) { alert("请耐心等待上一次提交结束!"); return }
    if(date_now > time) { alert("考试已结束！"); window.location.href="/contest/<?=$_GET['contest_id']?>/result";return }
		doLock();
		localStorage.setItem(`code@${location.href}`, code.value);
                var ok = true;
		var post_data = {};
		if (post_data != {}) {
			post_data['check-answer'] = "";
			$.ajax({
				url : '',
				type : 'POST',
				dataType : 'json',
				async : false,

				data : post_data,
				success : function(data) {
					if (data.extra != undefined) {
						alert(data.extra);
						ok = false;
					}
				}
			});
		}
	        const submitData = new FormData();
		submitData.append("_token", token);	
		submitData.append("answer_answer_language", codeSelection.value);
		submitData.append("answer_answer_editor", code.value);
		submitData.append("answer_answer_upload_type", "editor");
		submitData.append("answer_answer_file", new Blob());
		submitData.append("submit-answer", "answer");
		$.ajax({
			url : '',
                    type : 'POST',
                    data : submitData,
                    processData: false,
		    contentType: false,
		    success: function() {
			    alert("提交成功!");
			    location.href = "<?= $redirect_page; ?>";
//        var redirectUrl = data.redirectUrl; // 假设请求返回i的数据中包含了跳转的 URL
  //      window.location.href = redirectUrl; // 页面跳转到请求返回的 URL
		    },
		    error: function() {
			    alert("提交失败，请重试");
		    }
		}).always(function() { unlock(); });
                            }
                        </script>
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
<?php if ($need_camera && $need_camera2) { ?>
       <script>
const problemNum = <?= $problem_letter["all_cnt"]; ?>;
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
</body>

</html>
