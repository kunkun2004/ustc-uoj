<?php
requirePHPLib('form');

$upcoming_contest_name = null;
$upcoming_contest_href = null;
$rest_second = 1000000;
function echoContest($contest)
{
    global $myUser, $upcoming_contest_name, $upcoming_contest_href, $rest_second;

    $constestlink = <<<EOD
<h3><a href="/contest/{$contest['id']}" class="button">点击进入</a></h3>
EOD;

    genMoreContestInfo($contest);
    if (isset($_GET["type"]) && $_GET["type"] == "ongoing") {
        if ($contest['cur_progress'] != CONTEST_IN_PROGRESS) {
            return;
        }
    } elseif (isset($_GET["type"]) && $_GET["type"] == "finished") {
        if ($contest['cur_progress'] != CONTEST_FINISHED) {
            return;
        }
    }
    if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
        $cur_rest_second = $contest['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
        if ($cur_rest_second < $rest_second) {
            $upcoming_contest_name = $contest['name'];
            $upcoming_contest_href = "/contest/{$contest['id']}";
            $rest_second = $cur_rest_second;
        }
        if ($myUser != null && hasRegistered($myUser, $contest)) {
            $contest_name_link = '<span style="color:green">' . UOJLocale::get('contests::registered') . '</span>';
        } else {
            $contest_name_link = '<span style="color:red">' . UOJLocale::get('contests::register') . '</span>';
            $link = '/contest/' . $contest['id'] . '/video';
        }
    } elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
        $contest_name_link = '<span style="color:blue" href="/contest/' . $contest['id'] . '">' . UOJLocale::get('contests::in progress') . '</span>';
        $link = '/contest/' . $contest['id'];
    } elseif ($contest['cur_progress'] == CONTEST_PENDING_FINAL_TEST) {
        $contest_name_link = '<span style="color:blue" href="/contest/' . $contest['id'] . '">' . UOJLocale::get('contests::pending final test') . '</span>';
        $link = '/contest/' . $contest['id'];
    } elseif ($contest['cur_progress'] == CONTEST_TESTING) {
        $contest_name_link = '<span style="color:blue" href="/contest/' . $contest['id'] . '">' . UOJLocale::get('contests::final testing') . '</span>';
        $link = '/contest/' . $contest['id'];
    } elseif ($contest['cur_progress'] == CONTEST_FINISHED) {
        $contest_name_link = '<span style="color:grey" href="/contest/' . $contest['id'] . '/standings">' . UOJLocale::get('contests::ended') . '</span>';
        $link = '/contest/' . $contest['id'] . '/standings';
    }

    $contest_name = $contest['name'];
    $last_hour = round($contest['last_min'] / 60, 2);
    /*
          
          $click_zan_block = getClickZanBlock('C', $contest['id'], $contest['zan']);
          echo '<div class="contest-container">';
          echo '<div class="contest-image">';
          echo '<img src="https://www.baidu.com/img/PCtm_d9c8750bed0b3c7d089fa7d55720d6cf.png" alt="比赛图片">';
          echo '</div>';
          echo '<div class="contest-info">';
          echo '<h3>', $contest_name_link, '</h3>';
          echo '<h5>开始时间：', '<a href="'.HTML::timeanddate_url($contest['start_time'], array('duration' => $contest['last_min'])).'">'.$contest['start_time_str'].'</a>', '</h5>';
          echo '<h5>时长：', UOJLocale::get('hours', $last_hour), '</h5>';
          echo $constestlink;
          echo '</div>';*/
    ?>
    <div class="competition_list_item">
        <a href="<?= $link ?>" class="clearfix">
            <div class="competition_img">
                <img src="images/competition_img.png" width="400" height="172" alt="competition_img" />
            </div>
            <div class="competition_info">
                <p class="competition_title"><?= $contest_name ?></p>
                <p class="competition_description" style="padding-top: 10px; margin-bottom: -10px">当前状态：<?= $contest_name_link ?><br>开始时间：<?= $contest['start_time_str']; ?> 时长：<?= $last_hour; ?>小时</p>
                <p class="competition_btn">点击进入</p>
            </div>
        </a>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html>

<head lang="en">
    <meta charset="UTF-8">
    <title>赛事列表页</title>
    <link rel="stylesheet" href="css/public.css" />
    <link rel="stylesheet" href="css/main.css" />
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
            <div class="banner">
                <img src="images/banner.png" alt="banner" />
            </div>
            <div class="oj_screen clearfix">
                <ul class="oj_menu clearfix">
                    <li class="oj_menu_item<?php if (!isset($_GET["type"]) || $_GET["type"] == "all") echo ' oj_menu_active'; ?>"><a href="?type=all">全部</a></li>
                    <li class="oj_menu_item<?php if ($_GET["type"] == "ongoing") echo ' oj_menu_active'; ?>"><a href="?type=ongoing">进行中</a></li>
                    <li class="oj_menu_item<?php if ($_GET["type"] == "finished") echo ' oj_menu_active'; ?>"><a href="?type=finished">往期比赛</a></li>
                </ul>
                <div class="oj_search">
                    <input type="text" id="search" name="search" placeholder="请输入竞赛名称" />
                    <img src="images/search_icon.png" width="23" height="22" alt="search" />
                </div>
            </div>
            <div class="competition_list">
                <?php
                echoContestTable(
                    array('*'),
                    'contests',
                    "status != 'finished'",
                    'order by id desc',
                    '',
                    echoContest,
                    array(
                        'page_len' => 100,
                        'print_after_table' => function () {
                            global $myUser;
                            if (isSuperUser($myUser)) {
                                echo '<div class="text-right">';
                                echo '<a href="/contest/new" class="btn btn-primary">' . UOJLocale::get('contests::add new contest') . '</a>';
                                echo '</div>';
                            }
                        }
                    )
                );
                ?>
                <?php
                echoContestTable(
                    array('*'),
                    'contests',
                    "status = 'finished'",
                    'order by id desc',
                    '',
                    echoContest,
                    array(
                        'page_len' => 100,
                        'print_after_table' => function () {
                            global $myUser;
                            if (isSuperUser($myUser)) {
                                echo '<div class="text-right">';
                                echo '<a href="/contest/new" class="btn btn-primary">' . UOJLocale::get('contests::add new contest') . '</a>';
                                echo '</div>';
                            }
                        }
                    )
                );
                ?>
            </div>
        </div>
    </div>
</body>

</html>