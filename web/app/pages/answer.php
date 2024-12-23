<?php
    $problem_content = queryProblemContent($problem['id']);

    $is_in_contest = false;
    $ban_in_contest = false;
    if ($contest != null) {
        if (!hasContestPermission($myUser, $contest)) {
            if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
                become404Page();
            } elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
                if ($myUser == null || !hasRegistered($myUser, $contest)) {
                    becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧 ～</p>");
                } else {
                    $is_in_contest = true;
                    if(!hasConstParticipated($myUser, $contest)) {
                        $current_time = new DateTime();  // 获取当前时间
                        $current_time_str = $current_time->format('Y-m-d H:i:s');  // 格式化为字符串
                        $minutes = queryLastmin($content); 
                        $current_time->modify("+$minutes minutes");  // 将当前时间增加30分钟
                        $end_time_str = $current_time->format('Y-m-d H:i:s');  // 格式化为字符串
                        DB::update("update contests_registrants set finish_time = '$end_time_str' where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
                        DB::update("update contests_registrants set has_participated = 1 where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
                    }else {
                        $user_finish_time = queryfinishtime($myUser, $content);
                        if(UOJTime::$time_now >= $user_finish_time) {
                            $ban_in_contest = true;
                        }
                    }
                }
            } else {
                $ban_in_contest = !isProblemVisibleToUser($problem, $myUser);
            }
        }
    } else {
        if (!isProblemVisibleToUser($problem, $myUser)) {
            become404Page();
        }
    }

    $submission_requirement = json_decode($problem['submission_requirement'], true);
    $problem_extra_config = getProblemExtraConfig($problem);
    $custom_test_requirement = getProblemCustomTestRequirement($problem);

    if ($custom_test_requirement && Auth::check()) {
        $custom_test_submission = DB::selectFirst("select * from custom_test_submissions where submitter = '".Auth::id()."' and problem_id = {$problem['id']} order by id desc limit 1");
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

    function handleUpload($zip_file_name, $content, $tot_size) {
        global $problem, $contest, $myUser, $is_in_contest;

        $content['config'][] = array('problem_id', $problem['id']);
        if ($is_in_contest && $contest['extra_config']["contest_type"]!='IOI' && !isset($contest['extra_config']["problem_{$problem['id']}"])) {
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

        if ($is_in_contest) {
            DB::query("insert into submissions (problem_id, contest_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, ${contest['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', 0)");
        } else {
            DB::query("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', {$problem['is_hidden']})");
        }
    }
    function handleCustomTestUpload($zip_file_name, $content, $tot_size) {
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
        $zip_answer_form = newZipSubmissionForm('zip_answer',
            $submission_requirement,
            'uojRandAvaiableSubmissionFileName',
            'handleUpload');
        $zip_answer_form->extra_validator = function() {
            global $ban_in_contest;
            if ($ban_in_contest) {
                return '请耐心等待比赛结束后题目对所有人可见了再提交';
            }
            return '';
        };
        $zip_answer_form->succ_href = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
        $zip_answer_form->runAtServer();
    }

    $answer_form = newSubmissionForm('answer',
        $submission_requirement,
        'uojRandAvaiableSubmissionFileName',
        'handleUpload');
    $answer_form->extra_validator = function() {
        global $ban_in_contest;
        if ($ban_in_contest) {
            return '请耐心等待比赛结束后题目对所有人可见了再提交';
        }
        return '';
    };
    $redirect_page = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
    $answer_form->succ_href = $redirect_page;
    $answer_form->runAtServer();

    if ($custom_test_requirement) {
        $custom_test_form = newSubmissionForm('custom_test',
            $custom_test_requirement,
            function() {
                return uojRandAvaiableFileName('/tmp/');
            },
            'handleCustomTestUpload');
        $custom_test_form->appendHTML(<<<EOD
<div id="div-custom_test_result"></div>
EOD
        );
        $custom_test_form->succ_href = 'none';
        $custom_test_form->extra_validator = function() {
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
    <link rel="stylesheet" href="css/public.css"/>
    <link rel="stylesheet" href="css/main.css"/>
</head>
<body>
<div class="page_container">
    <div class="oj_header">
        <ul class="oj_nav clearfix">
            <li><a href="#">赛事</a></li>
            <li><a href="#">题库</a></li>
        </ul>
    </div>
    <div class="oj_center">
        <div class="oj_title">
            <span>全国青少年机器人及人工智能素质提升测评试卷（软件编程C++语言)一级卷</span>
        </div>
        <div class="answer_notic">
            <p>考试须知</p>
            1、请确保网络良好，不会影响线上考试；<br/>
            2、本人信息仅做本次考试用，严禁泄露；<br/>
            3、请所有考生诚信考试、独立作答，如出现替考、作弊等现象，一经核实，取消考试成绩。
        </div>
        <div class="answer_area">
            <div class="answer_top clearfix">
                <div class="answer_title">单选题（已答1/10）</div>
                <div class="answer_time">
                    <p>截止时间</p>
                    <p>2024.9.13  12:30</p>
                </div>
            </div>

            <div class="answer_main">
                <div class="dt_area">
                    <div class="question">
                        <p>1、在Word中，替换文本的快捷键是（）。</p>
                        <div class="anwer_choice">
                            <div class="anwer_item">
                                <input type="radio" class="anwer" id="anwer0" name="anwer" value="0">
                                <label for="anwer0">A.Ctrl+F</label>
                            </div>
                            <div class="anwer_item">
                                <input type="radio" class="anwer" id="anwer1" name="anwer" value="1">
                                <label for="anwer1">B.Ctrl+H</label>
                            </div>
                            <div class="anwer_item">
                                <input type="radio" class="anwer" id="anwer2" name="anwer" value="2">
                                <label for="anwer2">C.Ctrl+G</label>
                            </div>
                            <div class="anwer_item">
                                <input type="radio" class="anwer" id="anwer3" name="anwer" value="3">
                                <label for="anwer3">D.Ctrl+J</label>
                            </div>
                        </div>
                    </div>
                    <div class="qsub">
                        <input type="button" class="ansub1" name="answer" value="提交">
                        <input type="submit" class="ansub2" name="finishnow" value="交卷">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dt_card_float" onclick="answerCard();">
    <img src="images/dt_card_float.png" width="133" height="230" alt="datika"/>
</div>
<div class="dt_card_box">
    <div class="dt_card_title"><span>答题卡</span></div>
    <div class="dt_card_list">
        <div class="question_type_item">
            <p>选择题<span>1-10题</span></p>
            <ul class="clearfix">
                <li class="question_id question_id_active"><a href="#">1</a></li>
                <li class="question_id"><a href="#">2</a></li>
                <li class="question_id"><a href="#">3</a></li>
                <li class="question_id"><a href="#">4</a></li>
                <li class="question_id"><a href="#">5</a></li>
                <li class="question_id"><a href="#">6</a></li>
                <li class="question_id"><a href="#">7</a></li>
                <li class="question_id"><a href="#">8</a></li>
                <li class="question_id"><a href="#">9</a></li>
                <li class="question_id"><a href="#">10</a></li>
            </ul>
        </div>


        <div class="question_type_item">
            <p>判断题<span>1-10题</span></p>
            <ul class="clearfix">
                <li class="question_id"><a href="#">1</a></li>
                <li class="question_id"><a href="#">2</a></li>
                <li class="question_id"><a href="#">3</a></li>
                <li class="question_id"><a href="#">4</a></li>
                <li class="question_id"><a href="#">5</a></li>
                <li class="question_id"><a href="#">6</a></li>
                <li class="question_id"><a href="#">7</a></li>
                <li class="question_id"><a href="#">8</a></li>
                <li class="question_id"><a href="#">9</a></li>
                <li class="question_id"><a href="#">10</a></li>
            </ul>
        </div>

        <div class="question_type_item">
            <p>实操题<span>1-10题</span></p>
            <ul class="clearfix">
                <li class="question_id"><a href="#">1</a></li>
                <li class="question_id"><a href="#">2</a></li>
                <li class="question_id"><a href="#">3</a></li>
                <li class="question_id"><a href="#">4</a></li>
                <li class="question_id"><a href="#">5</a></li>
                <li class="question_id"><a href="#">6</a></li>
                <li class="question_id"><a href="#">7</a></li>
                <li class="question_id"><a href="#">8</a></li>
                <li class="question_id"><a href="#">9</a></li>
                <li class="question_id"><a href="#">10</a></li>
            </ul>
        </div>
    </div>
</div>
<script>
    var answerShow="N";
    var dtCardElem=document.getElementsByClassName("dt_card_box")[0];
    function answerCard(){
        if(answerShow == "N"){
            answerShow="Y";
            dtCardElem.style.display="block";
        }else{
            answerShow="N";
            dtCardElem.style.display="none";
        }
    }
</script>
</body>
</html>