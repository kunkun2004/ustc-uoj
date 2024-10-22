<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST["problem_info"])) {
			$problem_info_json = $_POST["problem_info"];
			$problem_info = json_decode($problem_info_json, true);
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
			DB::query("insert into problems (title, is_hidden, submission_requirement) values ('".DB::escape("[".$problem_type_text[$problem_info["type"]]."]".$problem_content)."', $is_hidden, '{}')");
			$id = DB::insert_id();
			$problem_text = DB::escape($problem_text);
			$problem_text_md = DB::escape($problem_text_md);
			DB::query("insert into problems_contents (id, statement, statement_md) values ($id, '".$problem_text."', '".$problem_text_md."')");
			//echo "insert into problems_contents (id, statement, statement_md) values ($id, '', '')";
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
			// echo $ret;
			die();
		}
	}
    
    // var_dump(queryProblemBrief(90));
?>	
<?php echoUOJPageHeader('题目导入') ?>
<h1 class="page-header" align="center">题目导入</h1>
<div id="import-result"></div>
<form id="form-problem-import" class="form-horizontal", method="post">
    <div id="div-problem-list" class="form-group">
	<label for="input-problem-list" class="col-sm-2 control-label">题目信息列表</label> 
        <textarea class="form-control" id="input-problem-list" name="problem_list" placeholder="请输入导入的题目信息，一行一个，可以从Excel中直接粘贴。&#13;格式：&#13;题面&#9;类型&#9;标签&#9;难度&#9;状态&#9;答案&#9;选项1&#9;选项2&#9;...&#13;例如：&#13;测试1&#9;单选题&#9;计挑赛&#9;简单&#9;启用&#9;B&#9;选项a&#9;选项b&#9;选项c"></textarea>    
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
            tableHTML += `<span class="badge badge-error">失败</span>`;
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
            importProblemStatus[id] = { res: "error" };
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
    importProblemList = [];
    const salt = "<?= getPasswordClientSalt() ?>";
    
    let problemListInfo = $("#input-problem-list").val().trim();
    const placeholder = '__USTCOJ_PROBLEM_PLACEHOLDER__';
    const multiLinePattern = /"(?:[^"]|"")*"/g;
    const multiLineContents = [];
    problemListInfo = problemListInfo.replace(multiLinePattern, match => {
        const content = match.slice(1, -1).replace(/""/g, '"');
        multiLineContents.push(content);
        return placeholder;
    });

    const rows = problemListInfo.split('\n');

    const typeMap = {
        '单选题': 0,
        '多选题': 1,
        '判断题': 2
    };

    let placeholderIndex = 0;
    rows.forEach((line, index) => {
        const columns = [];
        const columnPattern = /([^\t]+)/g;
        let match;

        while ((match = columnPattern.exec(line)) !== null) {
            let value = match[0].trim();

            if (value === placeholder) {
                value = multiLineContents[placeholderIndex++];
            }

            columns.push(value);
        }

        if (columns.length < 7) {
            console.warn('数据行缺少列: ', columns);
            return;
        }

        const title = columns[0].trim();
        const type = typeMap[columns[1].trim()] ?? -1;

        if (type === -1) {
            console.warn('未知题目类型: ', columns);
            return;    
        }
        const categories = columns[2].split(',').map(category => category.trim());
        const difficulty = columns[3].trim();
        const defunct = columns[4].trim() !== '启用';

        let answerStr = columns[5].trim().toLowerCase();
        if (answerStr === '正确') {
            answerStr = 'a';
        }
        else if (answerStr === '错误') {
            answerStr = 'b';
        }
	let answer;
	if (type === 1) {
            answer = [...answerStr].map(ch => ch.charCodeAt(0) - 'a'.charCodeAt(0));
	}
	else {
            answer = [answerStr.charCodeAt(0) - 'a'.charCodeAt(0)];
	}
        const choices = columns.slice(6).map(choice => choice.trim());
	importProblemList.push({
            title,
            type,
            categories,
            difficulty,
            defunct,
            answer,
            choices
        });
    });
    importProblemStatus = [];
    for (let i = 0; i < importProblemList; ++i) {
        importProblemStatus.push(null);
    }
    showProblemImportTable();
    postProblemImport();
    /*$.post("/problem/import", {
        problem_list: JSON.stringify(importProblemList)
    }, (msg) => {
        $("#import-result").html(`<div class="alert alert-success" role="alert">
    导入成功，题号: ${msg}，<a href="/problem/${msg}">点击前往</a>
</div>`);
    });*/
}
$(document).ready(function() {
    $('#form-problem-import').submit(function(e) {
        submitProblemImport();
        return false;
    });
});
</script>
<?php echoUOJPageFooter() ?>
