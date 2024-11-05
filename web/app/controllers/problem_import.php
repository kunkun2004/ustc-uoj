<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isSuperUser($myUser)) {
    become403Page();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['problem_list'])) {
        $file = $_FILES['problem_list'];

        // Check if the file is an XLSX file
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($fileType != 'xlsx') {
            die('Invalid file type. Please upload an XLSX file.');
        }

        // Move the uploaded file to a temporary location
        $uploadPath = 'uploads/' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            die('Failed to move uploaded file.');
        }

        // Load the XLSX file
        $spreadsheet = IOFactory::load($uploadPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Extract data from the XLSX file
        $data = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }
            $data[] = $rowData;
        }

        // Delete the uploaded file
        unlink($uploadPath);

        // Process the extracted data
        $importProblemList = [];
        foreach ($data as $row) {
            if (count($row) < 7) {
                continue; // Skip rows with insufficient data
            }

            $title = trim($row[0]);
            $type = $row[1];
            $categories = explode(',', trim($row[2]));
            $difficulty = trim($row[3]);
            $defunct = trim($row[4]) !== '启用';
            $answerStr = strtolower(trim($row[5]));
            $choices = array_slice($row, 6);

            $typeMap = [
                '单选题' => 0,
                '多选题' => 1,
                '判断题' => 2
            ];

            if (!isset($typeMap[$type])) {
                continue; // Skip unknown types
            }

            $type = $typeMap[$type];

            if ($answerStr === '正确') {
                $answerStr = 'a';
            } elseif ($answerStr === '错误') {
                $answerStr = 'b';
            }

            $answer = [];
            if ($type === 1) {
                $answer = array_map(function ($ch) {
                    return ord($ch) - ord('a');
                }, str_split($answerStr));
            } else {
                $answer = [ord($answerStr) - ord('a')];
            }

            $importProblemList[] = [
                'title' => $title,
                'type' => $type,
                'categories' => $categories,
                'difficulty' => $difficulty,
                'defunct' => $defunct,
                'answer' => $answer,
                'choices' => $choices
            ];
        }

        // Insert data into the database
        foreach ($importProblemList as $problem_info) {
            $problem_text_md = "";
            $problem_text = "";
            $problem_type_text = array("单选题", "多选题", "判断题");
            $problem_answer_mapping = [
                0 => 'A',
                1 => 'B',
                2 => 'C',
                3 => 'D',
                4 => 'E',
                5 => 'F',
                6 => 'G',
                7 => 'H',
                8 => 'I',
                9 => 'J',
                10 => 'K',
                11 => 'L',
                12 => 'M',
                13 => 'N',
                14 => 'O',
                15 => 'P',
                16 => 'Q',
                17 => 'R',
                18 => 'S',
                19 => 'T',
                20 => 'U',
                21 => 'V',
                22 => 'W',
                23 => 'X',
                24 => 'Y',
                25 => 'Z'
            ];
            $problem_tags = array();
            $problem_text .= "<p>[".$problem_type_text[$problem_info["type"]]."]";
            $problem_text_md .= "[".$problem_type_text[$problem_info["type"]]."]";
            $problem_content = HTML::escape(str_replace("\n", "\n  ", trim($problem_info["title"])));
            $problem_text .= $problem_content."\n";
            $problem_text_md .= $problem_content."\n";
            foreach ($problem_info["choices"] as $choice) {
                $choice_text = HTML::escape(str_replace("\n", "\n  ", trim($choice)));
                $problem_text .= "- ".$choice_text."\n";
                $problem_text_md .= "- ".$choice_text."\n";
            }
            $problem_text .= "</p>\n";
            $problem_text_md .= "\n";
            $problem_count += 1;
            $problem_answer_letters = array_map(function ($number) use ($problem_answer_mapping) {
                return isset($problem_answer_mapping[$number]) ? $problem_answer_mapping[$number] : null;
            }, $problem_info["answer"]);
            $problem_answer_letters = array_filter($problem_answer_letters);
            $problem_answer = implode('', $problem_answer_letters);
            foreach ($problem_info["categories"] as $category) {
                $problem_tags[] = HTML::escape(trim($category));
            }
            $problem_tags[] = HTML::escape("难度:".trim($problem_info["difficulty"]));
            $problem_tags = array_unique($problem_tags);
            $is_hidden = $problem_info["defunct"] ? "1" : "0";
            DB::query("insert into problems (title, is_hidden, submission_requirement, zan) values ('".DB::escape("[".$problem_type_text[$problem_info["type"]]."]".$problem_content)."', $is_hidden, '{}', 0)");
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
                mkdir("$data_dir/$id", 0777, true);
            }
            file_put_contents("$data_dir/$id/problem.conf", $problem_conf_content);
            file_put_contents("$data_dir/$id/data1.in", "Problem 1");
            file_put_contents("$data_dir/$id/data1.out", $problem_answer);
            $problem = queryProblemBrief($id);
            $ret = dataSyncProblemData($problem, $myUser);
            echo $id;
        }
    }
}

echoUOJPageHeader('题目导入');
?>
<h1 class="page-header" align="center">题目导入</h1>
<div id="import-result"></div>
<form id="form-problem-import" class="form-horizontal" method="post" enctype="multipart/form-data">
    <div id="div-problem-list" class="form-group">
        <label for="input-problem-list" class="col-sm-2 control-label">题目信息列表</label> 
        <input type="file" class="form-control" id="input-problem-list" name="problem_list" accept=".xlsx">
    </div>
    <div class="form-group">
        <div class="">
            <button type="submit" id="button-submit" class="btn btn-primary">提交</button>
        </div>
    </div>
    <div id="addTable"></div>
</form>
<script>
let importProblemList = [];
let importProblemStatus = [];

function dealStr(s) {
    return htmlspecialchars(s).replace("\n", "<br>");
}

function showProblemImportTable() {
    while (importProblemStatus.length < importProblemList.length) {
        importProblemStatus.push(null);
    }
    let tableHTML = `<table class="table table-bordered table-hover table-striped table-text-center">
    <thead>
        <tr>
            <th>题面</th>
            <th>启用</th>
            <th>答案</th>
            <th>选项</th>
            <th>导入状态</th>
        </tr>
    </thead>
    <tbody>`;
    const chooseLetter = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for (let i = 0; i < importProblemList.length; ++i) {
        tableHTML += `<tr>
    <td>${dealStr(importProblemList[i].title)}</td>
    <td>${importProblemList[i].defunct ? "未启用" : "启用"}</td>
    <td>`;
        for (const a of importProblemList[i].answer) {
            tableHTML += chooseLetter[a];
        }
        tableHTML += "</td><td>";
        let cnt = 0;
        for (const c of importProblemList[i].choices) {
            tableHTML += `${chooseLetter[cnt]}. ${c}`;
            break;
        }
        tableHTML += "...</td><td>";
        if (importProblemStatus[i] === null) {
            tableHTML += '<span class="badge badge-secondary">等待</span>';  
        }
        else if (importProblemStatus[i].res === "success") {
            tableHTML += `<span class="badge badge-success">成功</span>
            <a href="/problem/${importProblemStatus[i].id}">点击前往#${importProblemStatus[i].id}</a>`;
        }
        else {
            tableHTML += `<span class="badge badge-error">${importProblemStatus[i].res}</span>`;
        }
        tableHTML += `</td></tr>`;
    }
    tableHTML += `</tbody></table>`;
    $("#addTable").html(tableHTML);
}

let lock = false;

function postProblemImport() {
    const id = importProblemStatus.indexOf(null);
    if (id === -1) {
        lock = false;
        $("#button-submit").attr("disabled", false).text("提交");
        return;
    }
    $.post("/problem/import", {
        problem_info: JSON.stringify(importProblemList[id]),
    }, (msg) => {
        if (/\s*\d+\s*/.test(msg)) {
            importProblemStatus[id] = { res: "success", id: msg };
        }
        else {
            importProblemStatus[id] = { res: msg };
        }
    }).error(function() {
        importProblemStatus[id] = { res: "error" };
    }).complete(function() {
        showProblemImportTable();
        postProblemImport();
    });
}

function submitProblemImport() {
    if (lock) {
        return;
    }
    lock = true;
    $("#button-submit").attr("disabled", true).text("在所有题目完成导入之前，请勿刷新或者关闭页面，不然可能会有未知错误!");

    // Read the uploaded file and extract data
    const fileInput = document.getElementById('input-problem-list');
    const file = fileInput.files[0];
    if (!file) {
        alert('请选择一个文件');
        lock = false;
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

        importProblemList = [];
        jsonData.forEach((row, index) => {
            if (index === 0) return; // Skip header row
            if (row.length < 7) return; // Skip rows with insufficient data

            const title = row[0];
            const type = row[1];
            const categories = row[2].split(',');
            const difficulty = row[3];
            const defunct = row[4] !== '启用';
            const answerStr = row[5].toLowerCase();
            const choices = row.slice(6);

            const typeMap = {
                '单选题': 0,
                '多选题': 1,
                '判断题': 2
            };

            if (!typeMap[type]) return; // Skip unknown types

            const typeValue = typeMap[type];

            let answer = [];
            if (typeValue === 1) {
                answer = [...answerStr].map(ch => ch.charCodeAt(0) - 'a'.charCodeAt(0));
            } else {
                answer = [answerStr.charCodeAt(0) - 'a'.charCodeAt(0)];
            }

            importProblemList.push({
                title,
                type: typeValue,
                categories,
                difficulty,
                defunct,
                answer,
                choices
            });
        });

        importProblemStatus = [];
        for (let i = 0; i < importProblemList.length; ++i) {
            importProblemStatus.push(null);
        }
        showProblemImportTable();
        postProblemImport();
    };
    reader.readAsArrayBuffer(file);
}

$(document).ready(function() {
    $('#form-problem-import').submit(function(e) {
        submitProblemImport();
        return false;
    });
});
</script>
<?php echoUOJPageFooter(); ?>