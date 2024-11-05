<?php
// require '/opt/uoj/vendor/autoload.php';

// use PHPExcel;
// use PHPExcel_IOFactory;
// use PHPExcel_Style_Alignment;

// if (!isSuperUser($myUser)) {
//     become403Page();
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (isset($_FILES['problem_list'])) {
//         $file = $_FILES['problem_list'];

//         // Check if the file is an XLSX file
//         $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
//         if ($fileType != 'xlsx') {
//             die('Invalid file type. Please upload an XLSX file.');
//         }

//         // Move the uploaded file to a temporary location
//         $uploadPath = 'uploads/' . basename($file['name']);
//         if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
//             die('Failed to move uploaded file.');
//         }

//         // Load the XLSX file
//         $spreadsheet = IOFactory::load($uploadPath);
//         $sheet = $spreadsheet->getActiveSheet();

//         // Extract data from the XLSX file
//         $data = [];
//         foreach ($sheet->getRowIterator() as $row) {
//             $rowData = [];
//             foreach ($row->getCellIterator() as $cell) {
//                 $rowData[] = $cell->getValue();
//             }
//             $data[] = $rowData;
//         }

//         // Delete the uploaded file
//         unlink($uploadPath);

//         // Process the extracted data
//         $importProblemList = [];
//         foreach ($data as $row) {
//             if (count($row) < 7) {
//                 continue; // Skip rows with insufficient data
//             }

//             $title = trim($row[0]);
//             $type = $row[1];
//             $categories = explode(',', trim($row[2]));
//             $difficulty = trim($row[3]);
//             $defunct = trim($row[4]) !== '启用';
//             $answerStr = strtolower(trim($row[5]));
//             $choices = array_slice($row, 6);

//             $typeMap = [
//                 '单选题' => 0,
//                 '多选题' => 1,
//                 '判断题' => 2
//             ];

//             if (!isset($typeMap[$type])) {
//                 continue; // Skip unknown types
//             }

//             $type = $typeMap[$type];

//             if ($answerStr === '正确') {
//                 $answerStr = 'a';
//             } elseif ($answerStr === '错误') {
//                 $answerStr = 'b';
//             }

//             $answer = [];
//             if ($type === 1) {
//                 $answer = array_map(function ($ch) {
//                     return ord($ch) - ord('a');
//                 }, str_split($answerStr));
//             } else {
//                 $answer = [ord($answerStr) - ord('a')];
//             }

//             $importProblemList[] = [
//                 'title' => $title,
//                 'type' => $type,
//                 'categories' => $categories,
//                 'difficulty' => $difficulty,
//                 'defunct' => $defunct,
//                 'answer' => $answer,
//                 'choices' => $choices
//             ];
//         }

//         // Insert data into the database
//         foreach ($importProblemList as $problem_info) {
//             $problem_text_md = "";
//             $problem_text = "";
//             $problem_type_text = array("单选题", "多选题", "判断题");
//             $problem_answer_mapping = [
//                 0 => 'A',
//                 1 => 'B',
//                 2 => 'C',
//                 3 => 'D',
//                 4 => 'E',
//                 5 => 'F',
//                 6 => 'G',
//                 7 => 'H',
//                 8 => 'I',
//                 9 => 'J',
//                 10 => 'K',
//                 11 => 'L',
//                 12 => 'M',
//                 13 => 'N',
//                 14 => 'O',
//                 15 => 'P',
//                 16 => 'Q',
//                 17 => 'R',
//                 18 => 'S',
//                 19 => 'T',
//                 20 => 'U',
//                 21 => 'V',
//                 22 => 'W',
//                 23 => 'X',
//                 24 => 'Y',
//                 25 => 'Z'
//             ];
//             $problem_tags = array();
//             $problem_text .= "<p>[".$problem_type_text[$problem_info["type"]]."]";
//             $problem_text_md .= "[".$problem_type_text[$problem_info["type"]]."]";
//             $problem_content = HTML::escape(str_replace("\n", "\n  ", trim($problem_info["title"])));
//             $problem_text .= $problem_content."\n";
//             $problem_text_md .= $problem_content."\n";
//             foreach ($problem_info["choices"] as $choice) {
//                 $choice_text = HTML::escape(str_replace("\n", "\n  ", trim($choice)));
//                 $problem_text .= "- ".$choice_text."\n";
//                 $problem_text_md .= "- ".$choice_text."\n";
//             }
//             $problem_text .= "</p>\n";
//             $problem_text_md .= "\n";
//             $problem_count += 1;
//             $problem_answer_letters = array_map(function ($number) use ($problem_answer_mapping) {
//                 return isset($problem_answer_mapping[$number]) ? $problem_answer_mapping[$number] : null;
//             }, $problem_info["answer"]);
//             $problem_answer_letters = array_filter($problem_answer_letters);
//             $problem_answer = implode('', $problem_answer_letters);
//             foreach ($problem_info["categories"] as $category) {
//                 $problem_tags[] = HTML::escape(trim($category));
//             }
//             $problem_tags[] = HTML::escape("难度:".trim($problem_info["difficulty"]));
//             $problem_tags = array_unique($problem_tags);
//             $is_hidden = $problem_info["defunct"] ? "1" : "0";
//             DB::query("insert into problems (title, is_hidden, submission_requirement, zan) values ('".DB::escape("[".$problem_type_text[$problem_info["type"]]."]".$problem_content)."', $is_hidden, '{}', 0)");
//             $id = DB::insert_id();
//             $problem_text = DB::escape($problem_text);
//             $problem_text_md = DB::escape($problem_text_md);
//             DB::query("insert into problems_contents (id, statement, statement_md) values ($id, '".$problem_text."', '".$problem_text_md."')");
//             dataNewProblem($id);
//             foreach ($problem_tags as $tag) {
//                 DB::insert("insert into problems_tags (problem_id, tag) values ($id, '".DB::escape($tag)."')");
//             }
//             DB::insert("insert into problems_tags (problem_id, tag) values ($id, '".DB::escape("choice")."')");
//             $data_dir = "/var/uoj_data/upload";
//             $problem_conf_content = <<<EOD
// n_tests 1
// submit_answer on
// input_pre data
// input_suf in
// output_pre data
// output_suf out
// use_builtin_judger on
// use_builtin_checker wcmp
// EOD;
//             if (!is_dir("$data_dir/$id")) {
//                 mkdir("$data_dir/$id", 0777, true);
//             }
//             file_put_contents("$data_dir/$id/problem.conf", $problem_conf_content);
//             file_put_contents("$data_dir/$id/data1.in", "Problem 1");
//             file_put_contents("$data_dir/$id/data1.out", $problem_answer);
//             $problem = queryProblemBrief($id);
//             $ret = dataSyncProblemData($problem, $myUser);
//             echo $id;
//         }
//     }
// }

// echoUOJPageHeader('题目导入');
?>

<?php
require '/opt/uoj/vendor/autoload.php';

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;

if (!isSuperUser($myUser)) {
    become403Page();
}
function handeloptionline($str) {
    // 使用正则表达式匹配以"-"开头且后面有空白字符的情况
    $str = trim($str);
    return preg_replace('/^-\s+/', '-', $str);
    return $str."\n";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 检查文件是否上传成功
    if (isset($_FILES['problem_list']) && $_FILES['problem_list']['error'] == 0) {
        $file = $_FILES['problem_list']['tmp_name'];

        // 读取Excel文件
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $worksheet = $objPHPExcel->getActiveSheet();

        // 获取所有单元格的值
        $data = $worksheet->toArray();

        // 输出结果到 addTable
        echo "<div id='addTable'>";
        echo '<table class="table table-bordered table-hover table-striped table-text-center">
    <thead>
        <tr>
            <th>题面</th>
            <th>启用</th>
	    <th>答案</th>
	    <th>选项</th>
            <th>导入状态</th>
        </tr>
    </thead>
    <tbody>';
        foreach ($data as $rowIndex => $row) {
            $problem_type = $row[1] == null ? "":$row[1];
            $problem_content = $row[0];
            $problem_text = "<p>[".$problem_type."]".$problem_content."\n";
            $problem_text_md = "[".$problem_type."]".$problem_content."\n";
            $is_hidden = trim($row[4]) == "启用" ? 0 : 1;
            $options = "";
            for ($i = 6; $i <= 9; $i++) {
                $lines = preg_split('/\R/', trim($row[$i]));
                $option_text = "";
                foreach($lines as $line)
                {
                    $option_text .= handeloptionline($line);
                }
                $problem_text .= $option_text;
                $problem_text_md .= $option_text;
                $options .= $option_text;
            }
			$problem_text .= "</p>\n";
			$problem_text_md .= "\n";
            
			$problem_tags = array();
			$problem_tags[] = HTML::escape("难度:".trim($row[3]));
            $tags = explode(",", trim($row[2]));
            foreach ($tags as $tag) {
                $problem_tags[] = HTML::escape(trim($tag));
            }
			$problem_tags = array_unique($problem_tags);

            $problem_answer = trim($row[5]);
			DB::query("insert into problems (title, is_hidden, submission_requirement, zan) values ('".DB::escape("[".$problem_type."]".$problem_content)."', $is_hidden, '{}', 0)");
			$id = DB::insert_id();
			$problem_text = DB::escape($problem_text);
			$problem_text_md = DB::escape($problem_text_md);
			DB::query("insert into problems_contents (id, statement, statement_md) values ($id, '".$problem_text."', '".$problem_text_md."')");
			dataNewProblem($id);
            
			foreach ($problem_tags as $tag) {
				DB::insert("insert into problems_tags (problem_id, tag) values ($id, '".DB::escape($tag)."')");
			}
			DB::insert("insert into problems_tags (problem_id, tag) values ($id, '".DB::escape("choice")."')");
			$data_dir = "/var/uoj_data/upload";
			$problem_conf_content = <<<EOD
n_tests 1
submit_answer on
input_pre data
input_suf in
output_pre data
output_suf out
use_builtin_judger on
use_builtin_checker wcmp
EOD;
            if (!is_dir("$data_dir/$id")) {
                // echo '111';
                mkdir("$data_dir/$id", 0777, true);
            }
			file_put_contents("$data_dir/$id/problem.conf", $problem_conf_content);
			file_put_contents("$data_dir/$id/data1.in", "Problem 1");
			file_put_contents("$data_dir/$id/data1.out", $problem_answer);
			$problem = queryProblemBrief($id);
			$ret = dataSyncProblemData($problem, $myUser);

            echo "<tr>";
            echo "<td>".$problem_content."</td>";
            echo "<td>".trim($row[4])."</td>";
            echo "<td>".$problem_answer."</td>";
            echo "<td>".$options."</td>";
            if($ret == "")
            {
                echo '<td><span class="badge badge-success">成功</span>
                <a href="/problem/${importProblemStatus[i].id}">点击前往#${importProblemStatus[i].id}</a></td>';
            }
            else
            {
                echo '<td><span class="badge badge-error">'.$ret.'</span></td>';
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
    } else {
        echo "<div id='import-result'>文件上传失败，请重试。</div>";
    }
    echo "<div id='import-result'>文1234。</div>";
}

echoUOJPageHeader('题目导入');
?>

<h1 class="page-header" align="center">题目导入</h1>
<div id="import-result"></div>
<form id="form-problem-import" class="form-horizontal" method="post" enctype="multipart/form-data">
    <div id="div-problem-list" class="form-group">
        <label for="input-problem-list" class="col-sm-2 control-label">题目信息列表</label> 
        <input type="file" class="form-control" id="input-problem-list" name="problem_list" accept=".xlsx">
        <p>备注：不要有第一行的表头，应恰好有4个选项，选项如果多行，且如果有的行以"-"加上空白字符开头的，且空白字符后面有内容，则会自动删掉"-"后面的空白符，其他不变。</p>
        <p>在所有题目完成导入之前，请勿刷新或者关闭页面，不然可能会有未知错误!</p>
    </div>
    <div class="form-group">
        <div class="">
            <button type="submit" id="button-submit" class="btn btn-primary">提交</button>
        </div>
    </div>
</form>
<?php echoUOJPageFooter(); ?>