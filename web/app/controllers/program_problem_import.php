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
			$problem_title = $problem_info["title"];
			$problem_text_md = "";
			$problem_text = "";
			$problem_time_limit = $problem_info["time"];
			$problem_memory_limit = $problem_info["memory"];
			$problem_content = HTML::escape(trim($problem_info["content"]));
			$problem_text .= "<p style='white-space: pre-line'>".$problem_content."</p>";
			$problem_text_md .= $problem_content;
			$is_hidden = "1";
			DB::query("insert into problems (title, is_hidden, submission_requirement, zan) values ('".DB::escape(HTML::escape($problem_title))."', $is_hidden, '{}', zan)");
			$id = DB::insert_id();
			$problem_tags = array();
			$problem_categories = explode(",", $problem_info["tags"]);
			foreach ($problem_categories as $category) {
				$problem_tags[] = HTML::escape(trim($category));
			}
			$problem_tags[] = HTML::escape("难度:".trim($problem_info["difficulty"]));
			$problem_tags = array_unique($problem_tags);
                        foreach ($problem_tags as $tag) {
                                DB::insert("insert into problems_tags (problem_id, tag) values ($id, '".DB::escape($tag)."')");
                        }
			$problem_text = DB::escape($problem_text);
			$problem_text_md = DB::escape($problem_text_md);
			DB::query("insert into problems_contents (id, statement, statement_md) values ($id, '".$problem_text."', '".$problem_text_md."')");
			//echo "insert into problems_contents (id, statement, statement_md) values ($id, '', '')";
			dataNewProblem($id);
			$data_dir = "/var/uoj_data/upload";
			$data_count = count($problem_info["data"]);
			$problem_conf_content = <<<EOD
use_builtin_judger on
use_builtin_checker fcmp
n_tests $data_count
n_ex_tests 0
n_sample_tests 0
input_pre data
input_suf in
output_pre data
output_suf out
time_limit $problem_time_limit
memory_limit $problem_memory_limit
EOD;
			file_put_contents("$data_dir/$id/problem.conf", $problem_conf_content);
			$cnt = 0;
			foreach ($problem_info["data"] as $data) {
				$cnt += 1;
				file_put_contents("$data_dir/$id/data$cnt.in", $data["in"]);
				file_put_contents("$data_dir/$id/data$cnt.out", $data["out"]);
			}
			$problem = queryProblemBrief($id);
			$ret = dataSyncProblemData($problem, $myUser);
			echo $id;
			die();
		}
	}
?>	
<?php echoUOJPageHeader('编程题目导入') ?>
<h1 class="page-header" align="center">编程题目导入</h1>
<div id="import-result"></div>
<form id="form-problem-import" class="form-horizontal", method="post">
    <div id="div-problem-list" class="form-group">
	<label for="input-problem-list" class="col-sm-2 control-label">题目信息列表</label> 
        <textarea class="form-control" id="input-problem-list" name="problem_list" placeholder="请输入导入的题目信息，一行一个，可以从Excel中直接粘贴。&#13;格式：&#13;标题&#9;类型&#9;标签(年份+比赛名称+语言)&#9;题面&#9;难度&#9;内存(mb)&#9;运行时长(毫秒)&#9;1.in&#9;1.out&#9;2.in&#9;...&#13;例如：&#13;测试1&#9;编程题&#9;2023年计挑赛C语言&#9;题面&#9;简单&#9;512&#9;1000&#9;1&#9;2&#9;3&#9;4...."></textarea>    
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
            <th>标题</th>
	    <th>标签</th>
	    <th>难度</th>
	    <th>时间限制</th>
	    <th>空间限制</th>
	    <th>测试数据组数</th>
            <th>导入状态</th>
        </tr>
    </thead>
    <tbody>`;
    const chooseLetter = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for (let i = 0; i < importProblemList.length; ++i) {
        tableHTML += `<tr>
    <td>${dealStr(importProblemList[i].title)}</td>
    <td>${importProblemList[i].tags}</td>
    <td>${importProblemList[i].difficulty}</td>
    <td>${importProblemList[i].time}ms</td>
    <td>${importProblemList[i].memory}mb</td><td>`;
        tableHTML += `${importProblemList[i].data.length}`;
        tableHTML += "</td><td>";
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
    $.post("/problem/import2", {
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
	const type = columns[1].trim();
	const tags = columns[2].trim();
	const content = columns[3].trim();
	const difficulty = columns[4].trim();
	const memory = columns[5].trim();
	const time = (Number(columns[6].trim()) / 1000).toFixed(3);
        if (type !== "编程题")
		return;
        const rawDatas = columns.slice(7).map(t => t.trim());
	const data = [];
	for (let i = 0; i + 1 < rawDatas.length; i += 2) {
            data.push({ in: rawDatas[i], out: rawDatas[i + 1] });
	}
	importProblemList.push({
            title,
            type,
	    tags,
	    content,
	    difficulty,
            memory,
            time,
            data
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
