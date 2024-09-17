<?php
        requirePHPLib('form');

        if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
                become404Page();
	}

        if (!hasContestPermission(Auth::user(), $contest)) {
                if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
                        header("Location: /contest/{$contest['id']}/register");
                        die();
                } elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
                        if ($myUser == null || !hasRegistered(Auth::user(), $contest)) {
                                becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
                        }
                }
	}

	$unique_str = "{$contest['id']}-{$myUser['username']}orz";
	$seed = crc32($unique_str);
	srand($seed);

	
?>
