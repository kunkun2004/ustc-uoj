<?php
// 在这里包含你的数据库连接或初始化代码

$users = DB::selectAll("SELECT * FROM user_info ORDER BY rating DESC username ASC");


$now_cnt = 1;
// 设置文件名，例如 'ranking_export_2024-06-23.txt'
$filename = 'ranking_export_' . date('Y-m-d') . '.txt';

// 设置文本文件头部，告诉浏览器这是一个文本文件并提供下载
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 打开输出流
$fp = fopen('php://output', 'w');

// 写入表头
fwrite($fp, "排名\t用户名\t座右铭\t评分\n");

// 遍历 $users 数组
foreach ($users as $index => $user) {
    // 计算排名
    if ($index === 0) {
        // 第一个用户，直接计算排名
        $rank = 1;
    } else if ($user['rating'] == $users[$index - 1]['rating']) {
        // 与上一个用户的评分相同，排名相同
        $rank = $users[$index - 1]['rank'];
    } else {
        // 不相同，使用当前索引 + 1 作为排名
        $rank = $index + 1;
    }

    // 将排名信息写入文本文件
    $line = $rank . "\t" . $user['username'] . "\t" . $user['motto'] . "\t" . $user['rating'] . "\n";
    fwrite($fp, $line);

    // 更新计数器
    $now_cnt++;
}

// 关闭输出流
fclose($fp);

// 结束脚本执行
exit();
?>
