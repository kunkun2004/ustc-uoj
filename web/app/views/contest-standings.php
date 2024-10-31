<div id="standings"></div>

<div class="table-responsive">
	<table id="standings-table" class="table table-bordered table-striped table-text-center table-vertical-middle"></table>
</div>
<!--button class="btn btn-primary">下载成绩排名</button-->

<script type="text/javascript">
const score_list = <?= json_encode($score_list); ?>;
const problem_filters = <?= json_encode($problem_filters); ?>;
const chinese_list = ["一","二","三","四","五","六","七","八","九","十","十一","十二","十三","十四","十五"];
const problem_types = ["单选题", "多选题", "判断题", "填空题", "编程题", "不限"];

function deepSum(s) {
	let res = 0;
	if (!Array.isArray(s))
		res += Number(s) ?? 0;
	else {
		for (const i of s) {
			res += deepSum(i);
		}
	}
	return res;
}

function sum(s) {
	let res = 0;
	for (const i of s) {
		res += Number(i) ?? 0;
	}
	return res;
}

function showStandingTable() {
	let h = `<thead><tr><th rowspan="2">#</th><th rowspan="2">选手</th><th rowspan="2">总分</th>`;
	let cnt = 0;
	for (const pf of problem_filters) {
		h += `<th colspan="${Number(pf["problem_count"]) + 1}">${chinese_list[cnt]}、`;
		if (pf["problem_type"] === null) {
			h += `不限</th>`;
		}
		else {
			h += `${problem_types[pf["problem_type"]]}</th>`;
		}
		cnt += 1;
	}
	h += "</tr><tr>";
	cnt = 0;
	for (const pf of problem_filters) {
		for (let i = 0; i < Number(pf["problem_count"]); ++i) {
			cnt += 1;
			h += `<th>${cnt}</th>`;
		}
		h += '<th>小计</th>';
	}
	h += `</tr></thead><tbody>`;
	let allPlayers = [];
	for (const pl in score_list) {
		allPlayers.push({
			id: pl,
			score: deepSum(score_list[pl]),
			all_score: score_list[pl]
		})
	}
	allPlayers = allPlayers.sort((a, b) => b.score - a.score );
	let rank = 0;
	for (const pl of allPlayers) {
		rank += 1;
		h += `<tr>
			<td>${rank}</td>
			<td>${pl["id"]}</td>`;
		h += `<td>${pl["score"]}</td>`;
		for (const d of pl["all_score"]) {
			for (const s of d) {
				h += `<td>${Number(s)}</td>`;
			}
			h += `<td>${sum(d)}</td>`;
		}
		h += "</tr>"
	}
	h += "</tbody>";
	$("#standings-table").html(h);
}
showStandingTable();
</script>
