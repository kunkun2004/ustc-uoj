<div class="dt_card_list">
    <?php
    $problem_filters = DB::selectALL("select * from contests_problem_filters where contest_id = {$contest['id']}");

    $problem_type = ["单选题", "多选题", "判断题", "填空题", "编程题"];

    $problem_list_res = queryContestUserProblemList($contest, $myUser);
    $cnt = 0;
    $pcnt = 0;
    $chinese_count = ["一", "二", "三", "四", "五", "六", "七", "八", "九", "十", "十一", "十二", "十三"];

    foreach ($problem_filters as $problem_filter) {
        ?>
        <div class="question_type_item">
            <p><?= $problem_filter["problem_type"] === NULL ? "全部题型" : $problem_type[$problem_filter["problem_type"]]; ?><span><?= $pcnt + 1; ?>
                    - <?= $pcnt + $problem_filter["problem_count"]; ?>题</span></p>
            <ul class="clearfix"></ul>
            <?php
            foreach ($problem_list_res[$cnt] as $p) {
                $pcnt++;
                $user = $_SESSION['username'];
                $conid = $_GET['contest_id'];
                $probid = $p["id"];
                var_dump($user);
                var_dump($conid);
                var_dump($probid);
                var_dump(DB::query("select count(*) from hasanswer where username = '$user' and contest_id = '$conid' and _id = '$probid';"));
                if(!DB::query("select count(*) from hasanswer where username = '$user' and contest_id = '$conid' and _id = '$probid';")){
                ?>
                <li class="new_question_id"><a href="/contest/<?= $contest["id"]; ?>/problem/<?= $p["id"]; ?>"><?= $pcnt; ?></a>
                </li>
            <?php }
            else{ ?>
                <li class="question_id"><a href="/contest/<?= $contest["id"]; ?>/problem/<?= $p["id"]; ?>"><?= $pcnt; ?></a>
                </li>
            <?php }} ?>
            </ul>
        </div>
        <div style="clear: both"></div>
        <?php
        $cnt++;
    } ?>
</div>