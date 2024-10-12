<?php
	requirePHPLib('form');
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if ($myUser == null) {
		redirectToLogin();
	} elseif (hasContestPermission($myUser, $contest) || hasRegistered($myUser, $contest) || $contest['cur_progress'] != CONTEST_NOT_STARTED) {
		//redirectTo('/contests');
	}
	
	$register_form = new UOJForm('register');
	$register_form->handle = function() {
		global $myUser, $contest;
		DB::query("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$myUser['username']}', {$myUser['rating']}, {$contest['id']}, 0)");
		updateContestPlayerNum($contest);
	};
	$register_form->submit_button_config['class_str'] = 'btn btn-primary';
	$register_form->submit_button_config['text'] = '报名比赛';
	$register_form->succ_href = "/contest/{$contest['id']}";
	
	$register_form->runAtServer();
?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>考试须知</title>
    <link rel="stylesheet" href="/css/public.css"/>
    <link rel="stylesheet" href="/css/main.css"/>
</head>
<body>
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
        <div class="oj_video_test">
            <div class="test_progress clearfix">
                <div class="step_item">
                    <p>1</p>
                    <p>测试摄像头</p>
                </div>
                <div class="step_item step_active">
                    <p>2</p>
                    <p>阅读考试须知</p>
                </div>
            </div>
            <div class="exam_notic">
                <p>考试须知</p>
                设备准备：请提前检查您的电脑或移动设备，确保摄像头、麦克风和扬声器工作正常。确保您的设备已充满电或连接到电源。<br/>
                网络连接：确保您的网络连接稳定，避免考试过程中出现断线情况。<br/>
                考试环境：选择一个安静、光线充足的环境进行考试，避免外界干扰。<br/>
                身份验证：考试开始前，您可能需要通过摄像头进行身份验证，请准备好有效身份证件。<br/>
                考试规则：请严格遵守考试规则，考试期间不得查阅资料、使用电子设备搜索答案或与他人交流。<br/>
                诚信考试：我们鼓励诚信考试，任何作弊行为都将受到严肃处理。<br/>
                时间管理：请合理安排时间，考试开始后请尽快作答，注意考试时间限制。
            </div>
            <div class="privacy">
                <input type="checkbox" name="privacy" id="privacy" checked/>
                <label for="privacy">我保证在考试过程中遵守所有规定和准则，不参与任何形式的作弊行为。我会诚实地完成所有考试内容。</label>
            </div>
            <div class="operation_step clearfix">
                <div class="back_step">
                    <a href="/contest/<?= $contest["id"]; ?>/video"><< 返回上一步</a>
                </div>
                <div style="display: none"><?php $register_form->printHTML(); ?></div>
                <div class="start_answer">
                    <?php  
                    $problem_list_res = queryContestUserProblemList($contest, $myUser);
                    $p=$problem_list_res[$cnt][0];
                    if($p!=null){
                    ?>
                    <a href="/contest/<?= $contest["id"]; ?>/problem/<?= $p["id"]; ?>">开始答题</a>
                    <?php
                    }
                    else{
                    ?>
                    <a href="#">开始答题</a>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/js/jquery-2.1.4/jquery.min.js"></script>
<script>
    $(".start_answer").click(function(){
        if($("input[type='checkbox']").is(':checked')){
            $('#button-submit-register').click();
        }else{
            alert("请先阅读考试须知！")
        }
    })
</script>
</body>
</html>