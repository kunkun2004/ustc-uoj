<?php
// 报错提示
ini_set("display_errors", "On");
	requirePHPLib('form');
	requirePHPLib('judger');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	$tags = queryProblemTags($_GET['id']);
	$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;

	if ($contest != null) {
		if (!hasContestPermission($myUser, $contest)) {
			if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
				become404Page();
			} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
				if ($myUser == null || !hasRegistered($myUser, $contest)) {
					becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧 ～</p>");
				} 
				else {
				}
	
			}
		}
	} else {
		if (!isProblemVisibleToUser($problem, $myUser)) {
			become404Page();
		}
	}

    if (in_array('choice', $tags)) {
        include 'prob_choice.php';
    } 
	else if(in_array('fill', $tags))
	{
		include 'prob_fill.php';
	}
	else {
        include 'prob_tradition.php';
    }
?>
