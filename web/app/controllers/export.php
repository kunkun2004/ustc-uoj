<?php

// if (!isSuperUser($myUser)) {
// 	become403Page();
// }

require '/opt/uoj/vendor/autoload.php';

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;

// 创建一个新的 PHPExcel 对象
$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->getActiveSheet();

$contest = queryContest($_GET['id']);

$problem_filters = DB::selectALL("select * from contests_problem_filters where contest_id = {$contest['id']}");
$contest_data = queryContestData($contest);
$all_registrants = DB::selectALL("select username from contests_registrants where contest_id = {$contest['id']}");
//var_dump($all_registrants);
// 统计每个用户的每个题目的分数
$score_list = array();
$sid_list = array();
foreach ($all_registrants as $r) {
    $u = $r["username"];
    $score_list[$u] = array();
    $sid_list[$u] = array();
    $pl = queryContestUserProblemList($contest, array("username" => $u));
    for ($i = 0; $i < count($problem_filters); ++$i) {
        $pf = $problem_filters[$i];
        $ps = $pl[$i];
        $sql = "
SELECT
p.id AS problem_id,
s.id AS submission_id,
s.score * {$pf["problem_score"]} / 100 AS pscore
FROM
problems p
LEFT JOIN
submissions s
ON
s.id = (
    SELECT id
    FROM submissions
    WHERE contest_id = {$contest['id']}
        AND submitter = '$u'
        AND problem_id = p.id
    ORDER BY id DESC
    LIMIT 1
)
WHERE
p.id IN (" . implode(',', array_column($ps, 'id')) . ")
";
        $res = DB::selectALL($sql);
        $score = array_column($res, 'pscore');
        $sid = array_column($res, 'submission_id');
        $score_list[$u][] = $score;
        $sid_list[$u][] = $sid;
    }
}
//var_dump($score_list);


//var_dump($contest_data);
//calcStandings($contest, $contest_data, $score, $standings);

// uojIncludeView('contest-standings', [
// 	'contest' => $contest,
// 	'isSuper' => isSuperUser($myUser),
// 	'problem_filters' => $problem_filters,
// 	'score_list' => $score_list,
// 	'contest_data' => $contest_data
// ]);
// echo '<pre>';
// print_r($score_list);
// echo '</pre>';
// echo '<pre>';
// print_r($contest_data);
// echo '</pre>';
// echo '<pre>';
// print_r($problem_filters);
// echo '</pre>';




$sheet->setCellValue('A1', '#');
$sheet->setCellValue('B1', '选手');
$sheet->setCellValue('C1', '总分');
$problem_type = ["单选题", "不定项选择题", "判断题", "填空题", "编程题"];
foreach($p as $problem_filters) {
    $num = $p["problem_count"];
    //循环num次
    for ($i = 0; $i < $num; $i++) {
        $sheet->setCellValue(chr(96 + 4 + $i).'1', $problem_type[$p["problem_type"]].($i + 1));
        if($p["problem_type"] == 4) {
            $sheet->setCellValue(chr(96 + 4 + $i).'1', $problem_type[$p["problem_type"]].($i + 1).'代码');
        }
    }
}






// 设置表头
// $sheet->setCellValue('A1', '列1');
// $sheet->setCellValue('B1', '列2');
// $sheet->setCellValue('C1', '列3');

// // 写入数据行
// for ($i = 1; $i <= 10; $i++) {
//     $row = "行{$i}\n列1";
//     $sheet->setCellValue("A" . ($i + 1), $row);
//     $sheet->getStyle("A" . ($i + 1))->getAlignment()->setWrapText(true);

//     $row = "行{$i}\n列2";
//     $sheet->setCellValue("B" . ($i + 1), $row);
//     $sheet->getStyle("B" . ($i + 1))->getAlignment()->setWrapText(true);

//     $row = "行{$i}\n列3";
//     $sheet->setCellValue("C" . ($i + 1), $row);
//     $sheet->getStyle("C" . ($i + 1))->getAlignment()->setWrapText(true);
// }

// 设置文件名和内容类型

$filename = "contest".$_GET['id'].".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 创建 Excel 文件写入器并输出到浏览器
$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$writer->save('php://output');
?>