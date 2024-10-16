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
function hasConstParticipated($user,$contest){
	return DB::selectFirst("select * from contests_registrants where username = '${user['username']}' and contest_id = ${contest['id']} and has_participated = 1") != null;
}
function queryLastmin($contest){

    // 查询指定比赛的 last_min
    $result = DB::query("select last_min from contests WHERE contest_id = ${contest['id']} LIMIT 1");

    // 检查是否有返回结果
    if ($row = DB::fetch_assoc($result)) {
        // 返回整数类型的 last_min
        return intval($row['last_min']);
    } else {
        // 如果查询不到比赛，返回默认值或处理错误
        echo "未找到比赛编号为 $contest 的比赛";
        return 0;  // 或者你可以根据需要返回 null 或其他默认值
    }
}

function queryfinishtime($user,$contest){
	// 确保 $contest 被正确转义，避免SQL注入
    $contest_id = intval($contest);  // 如果 $contest 是整数，确保安全
    // 或者如果 $contest 是字符串类型的比赛编号，需要使用数据库的转义方法
    // $contest_id = DB::escape($contest);

    // 查询指定比赛的 last_min
    $result = DB::query("select finish_time from contests_registrants WHERE contest_id = ${contest['id']} and username = '${user['username']}' LIMIT 1");
	if ($result && DB::num_rows($result) > 0) {
        // 获取第一行的结果
        $row = DB::fetch($result, MYSQLI_ASSOC);
        
        // 检查 finish_time 字段是否存在且不为 null
        if (isset($row['finish_time']) && $row['finish_time'] !== null) {
            // 将日期字符串转换为 DateTime 对象
            try {
                $dateTime = new DateTime($row['finish_time']);
                return $dateTime; // 返回 DateTime 对象
            } catch (Exception $e) {
                // 如果转换失败，可以选择返回 null 或抛出异常
                return null; // 或者 throw $e;
            }
        }else {
			return new DateTime();
		}
    }
    
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


		$sql = "SELECT p.* FROM problems p WHERE 1=1 ";
// SELECT p.* FROM problems p WHERE 1=1 
// AND p.id IN (
//     SELECT pt.problem_id
//     FROM problems_tags pt
//     WHERE pt.tag IN ('2023年计挑赛C语言', '难度:简单')
//     GROUP BY pt.problem_id
//     HAVING COUNT(DISTINCT pt.tag) = 2
// );
		//$sql = "select p.* from problems p left join problems_tags pt on p.id=pt.problem_id where 1=1";
		if ($pf["problem_type"] === null) {
			
		}
		elseif ($pf["problem_type"] >= 0 && $pf["problem_type"] <= 3) {
			$sql.= " and p.title like '[".$problem_type[$pf["problem_type"]]."]%'";
		}
		elseif ($pf["problem_type"] == 4) {
			$sql .= " and (p.title not like '[单选题]%' and p.title not like '[多选题]%' and p.title not like '[判断题]%' and p.title not like '[填空题]%')";
		}
		if($pf["problem_tags"] === NULL && $pf["problem_difficulty"] === NULL)
		{
			$sql .= " AND p.id IN ( SELECT pt.problem_id FROM problems_tags pt WHERE 1=0";
		}
		else{
			$sql .= " AND p.id IN ( SELECT pt.problem_id FROM problems_tags pt WHERE pt.tag IN (";
			if ($pf["problem_tags"] !== NULL) {
				$sql .= " '".DB::escape($pf["problem_tags"])."'";
			}
			if ($pf["problem_difficulty"] !== NULL) {
				if($pf["problem_tags"] !== NULL){
					$sql .= ',';
				}
				$sql .= " '".DB::escape("难度:".$pf["problem_difficulty"])."'";
			}
			$sql .= " )";
		}
		$sql .= " GROUP BY pt.problem_id HAVING COUNT(DISTINCT pt.tag) = 2 );";
		echo $sql;
		$problem_list = DB::selectALL($sql);
		var_dump($problem_list);
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
	$problem_filters =  DB::selectAll("select * from contests_problem_filters where contest_id = {$contest['id']}");
	//var_dump($problem_list);
	$flag = true;
	$problem_filter = "";
	for ($i = 0; $i < count($problem_list); ++$i) {
		$cnt = 0;
		foreach ($problem_list[$i] as $p) {
			$cnt ++;
			if ($p["id"] === $problem["id"]) {
				$problem_filter = $problem_filters[$i];
				$flag = false;
				break;
			}
		}
		if (!$flag) {
			break;
		}
	}
	if ($flag) {
		return -1;
	}
	return array("cnt" => $cnt, "filter" => $problem_filter);
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
