<?php

function hasProblemPermission($user, $problem) {
	if ($user == null) {
		return false;
	}
	if (isSuperUser($user)) {
		return true;
	}
	return DB::selectFirst("select * from problems_permissions where username = '{$user['username']}' and problem_id = {$problem['id']}") != null;
}
function hasViewPermission($str,$user,$problem,$submission) {
	if($str=='ALL')
		return true;
	if($str=='ALL_AFTER_AC')
		return hasAC($user,$problem);
	if($str=='SELF')
		return $submission['submitter']==$user['username'];
	return false;
}

function hasContestPermission($user, $contest) {
	if ($user == null) {
		return false;
	}
	if (isSuperUser($user)) {
		return true;
	}
	return DB::selectFirst("select * from contests_permissions where username = '{$user['username']}' and contest_id = {$contest['id']}") != null;
}

function hasRegistered($user, $contest) {
	return DB::selectFirst("select * from contests_registrants where username = '${user['username']}' and contest_id = ${contest['id']}") != null;
	}
function hasAC($user, $problem) {
	return DB::selectFirst("select * from best_ac_submissions where submitter = '${user['username']}' and problem_id = ${problem['id']}") != null;
}

function queryUser($username) {
	if (!validateUsername($username)) {
		return null;
	}
	return DB::selectFirst("select * from user_info where username='$username'", MYSQLI_ASSOC);
}
function queryProblemContent($id) {
	return DB::selectFirst("select * from problems_contents where id = $id", MYSQLI_ASSOC);
}
function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQLI_ASSOC);
}

function queryProblemTags($id) {
	$tags = array();
	$result = DB::query("select tag from problems_tags where problem_id = $id order by id");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$tags[] = $row[0];
	}
	return $tags;
}
function queryContestUserProblemList($contest, $user) {
	$problem_filters =  DB::selectAll("select * from contests_problem_filters where contest_id = {$contest['id']}");
	
	$problem_type = ["单选题", "多选题", "判断题", "填空题", "编程题"];
	$problem_list_res = [];
	
	$seed = crc32("{$contest['id']}-{$user['username']}orz");
	srand($seed);
	foreach ($problem_filters as $pf) {
		//$sql = "select p.* from problems p left join problems_tags pt on p.id=pt.problem_id where p.is_hidden=0";
		$sql = "select p.* from problems p left join problems_tags pt on p.id=pt.problem_id where 1=1";
		if ($pf["problem_type"] === null) {
			
		}
		elseif ($pf["problem_type"] >= 0 && $pf["problem_type"] <= 3) {
			$sql.= " and p.title like '[".$problem_type[$pf["problem_type"]]."]%'";
		}
		elseif ($pf["problem_type"] == 4) {
			$sql .= " and (p.title not like '[单选题]%' and p.title not like '[多选题]%' and p.title not like '[判断题]%' and p.title not like '[填空题]%')";
		}
		if ($pf["problem_tags"] !== NULL) {
			$sql .= " and pt.tag = '".DB::escape($pf["problem_tags"])."'";
		}
		if ($pf["problem_difficulty"] !== NULL) {
			$sql .= " and pt.tag = '".DB::escape("难度:".$pf["problem_difficulty"])."'";
		}
		$sql .= " group by p.id";
		//echo $sql;
		$problem_list = DB::selectALL($sql);
		//var_dump($problem_list);
		if (count($problem_list) <= intval($pf["problem_count"])) {
			$problem_list_res[] = $problem_list;
		}
                else {
			if (intval($pf["problem_count"]) > 1)
				$res = array_rand($problem_list, intval($pf["problem_count"]));
			else 
				$res = array(rand(0, count($problem_list) - 1));
			$problem_list_res[] = array_intersect_key($problem_list, array_flip($res));
		}
	}
	return $problem_list_res;
}
function queryContestUserProblemRank($contest, $user, $problem) {
	$problem_list = queryContestUserProblemList($contest, $user);
	//var_dump($problem_list);
	$cnt = 0;
	$flag = true;
	foreach ($problem_list as $pb) {
		foreach ($pb as $p) {
			if ($p["id"] === $problem["id"]) {
				$flag = false;
				break;
			}
			$cnt ++;
		}
	}
	if ($flag) {
		return -1;
	}
	return $cnt;
}
function queryContestProblemRank($contest, $problem) {
	if (!DB::selectFirst("select * from contests_problems where contest_id = {$contest['id']} and problem_id = {$problem['id']}")) {
		return null;
	}
	return DB::selectCount("select count(*) from contests_problems where contest_id = {$contest['id']} and problem_id <= {$problem['id']}");
}
function querySubmission($id) {
	return DB::selectFirst("select * from submissions where id = $id", MYSQLI_ASSOC);
}
function queryHack($id) {
	return DB::selectFirst("select * from hacks where id = $id", MYSQLI_ASSOC);
}
function queryContest($id) {
	return DB::selectFirst("select * from contests where id = $id", MYSQLI_ASSOC);
}
function queryContestProblem($id) {
	return DB::selectFirst("select * from contest_problems where contest_id = $id", MYSQLI_ASSOC);
}

function queryZanVal($id, $type, $user) {
	if ($user == null) {
		return 0;
	}
	$esc_type = DB::escape($type);
	$row = DB::selectFirst("select val from click_zans where username='{$user['username']}' and type='$esc_type' and target_id='$id'");
	if ($row == null) {
		return 0;
	}
	return $row['val'];
}

function queryBlog($id) {
	return DB::selectFirst("select * from blogs where id='$id'", MYSQLI_ASSOC);
}
function queryBlogTags($id) {
	$tags = array();
	$result = DB::select("select tag from blogs_tags where blog_id = $id order by id");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$tags[] = $row[0];
	}
	return $tags;
}
function queryBlogComment($id) {
	return DB::selectFirst("select * from blogs_comments where id='$id'", MYSQLI_ASSOC);
}

function isProblemVisibleToUser($problem, $user) {
	return !$problem['is_hidden'] || hasProblemPermission($user, $problem);
}
function isContestProblemVisibleToUser($problem, $contest, $user) {
	if (isProblemVisibleToUser($problem, $user)) {
		return true;
	}
	if ($contest['cur_progress'] >= CONTEST_PENDING_FINAL_TEST) {
		return true;
	}
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		return false;
	}
	return hasRegistered($user, $contest);
}

function isSubmissionVisibleToUser($submission, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} else if (!$submission['is_hidden']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}
function isHackVisibleToUser($hack, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif (!$hack['is_hidden']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function isSubmissionFullVisibleToUser($submission, $contest, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} elseif ($submission['submitter'] == $user['username']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}
function isHackFullVisibleToUser($hack, $contest, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} elseif ($hack['hacker'] == $user['username']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function deleteBlog($id) {
	if (!validateUInt($id)) {
		return;
	}
	DB::delete("delete from click_zans where type = 'B' and target_id = $id");
	DB::delete("delete from click_zans where type = 'BC' and target_id in (select id from blogs_comments where blog_id = $id)");
	DB::delete("delete from blogs where id = $id");
	DB::delete("delete from blogs_comments where blog_id = $id");
	DB::delete("delete from important_blogs where blog_id = $id");
	DB::delete("delete from blogs_tags where blog_id = $id");
}
