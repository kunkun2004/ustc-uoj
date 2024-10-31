$(document).ready(function () {
    const article = $('.question');
    let content = article.html();
    let questionCounter = 0;

    const parseQuestions = (content) => {
        const questionPatterns = [
            { type: 'single', regex: /\[单选题\](.*?)(?=\[|$)/gs },
            { type: 'multiple', regex: /\[多选题\](.*?)(?=\[|$)/gs },
	        { type: 'judgement', regex: /\[判断题\](.*?)(?=\[|$)/gs },
        ];

        let questions = [];
        questionPatterns.forEach(pattern => {
            let matches = [...content.matchAll(pattern.regex)];
            matches.forEach(match => {
                const parts = match[1].trim().split('\n').filter(line => line.trim() !== '');
                let question = '';
                let choices = [];
                let inQuestion = true;

                parts.forEach(part => {
                    if (inQuestion && part.trim().startsWith('- ')) {
                        inQuestion = false;
                        choices.push(part.trim().substring(2));
                    } else if (!inQuestion && part.trim().startsWith('- ')) {
                        choices.push(part.trim().substring(2));
                    } else if (inQuestion) {
                        question += (question ? '\n' : '') + part.trim();
                    } else {
                        choices[choices.length - 1] += '\n  ' + part.trim();
                    }
                });

                questions.push({ type: pattern.type, question, choices, raw: match[0] });
            });
        });

        return questions;
    };

    const processParagraph = (paragraph) => {
        const parsedQuestions = parseQuestions(paragraph);
        let newParagraph = paragraph;

        parsedQuestions.forEach((parsedQuestion) => {
            questionCounter += 1;
            const { type, question, choices, raw } = parsedQuestion;

            // 创建题目容器
            const problemContainer = $('<div></div>').addClass('choose-problem').addClass(type === 'single' ? 'single-choose' : 'multiple-choose');

            // 创建题目内容，添加序号
            const problemTypeMap = { single: '单选题', multiple: '不定项选择题', judgement: '判断题' };
            const problemTypeBadge = problemTypeMap[type] ?? '单选题';
            const problemContent = $('<div></div>').addClass('problem-content').html(`${problemNum}. [${problemTypeBadge}](${problemScore}分) ${question.replace(/\n/g, '<br>')}`);
            problemContainer.append(problemContent);

            // 创建选项容器
            const problemChoices = $('<div></div>').addClass('anwer_choice');

            // 生成每个选项
            choices.forEach((choice, index) => {
                const formCheck = $('<div></div>').addClass('anwer_item');
                const label = $('<label></label>').addClass('form-check-label').attr('for', `anwer${index}`);
                const option_letter = String.fromCharCode(65 + index);
		const input = $('<input>')
                    .attr('type', type === 'multiple' ? 'checkbox' : 'radio')
                    .addClass('anwer')
                    .attr('id', `anwer${index}`)
                    .attr('name', `problem${questionCounter}`)
                    .attr('value', option_letter); // 将索引转换为A, B, C, D
		if (historyAnswer.indexOf(option_letter) != -1) {
			input.attr('checked', '');
		}

                formCheck.append(input)
                label.html(`${String.fromCharCode(65 + index)}. ${choice.replace(/\n/g, '<br>&nbsp;&nbsp;')}`);
                formCheck.append(label);
                problemChoices.append(formCheck);
            });

            // 将生成的HTML转换为字符串
            const newHtml = $('<div>').append(problemContainer).html();

            // 替换原始内容
            newParagraph = newParagraph.replace(raw, newHtml);
            $('.question').append(problemChoices);
        });

        return newParagraph;
    };

    // 处理每个段落
    article.find('p').each(function () {
        const originalParagraph = $(this).html();
        const processedParagraph = processParagraph(originalParagraph);
        $(this).html(processedParagraph);
    });

    function submitForm(link, val) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: link,
                type: "POST",
                data: val,
                processData: false,
                contentType: false,
                success: response => resolve(response),
                error: (xhr, status, error) => reject(error)
            });
        })
    }
    function saveAnswer(link, val) {
        const d = new FormData();
        d.append("save_choice", "233");
        d.append("answer", val.get("answer_output1_editor"));
        return new Promise((resolve, reject) => {
            $.ajax({
                url: link,
                type: "POST",
                data: d,
                processData: false,
                contentType: false,
                success: response => resolve(response),
                error: (xhr, status, error) => reject(error)
            });
	});
    }
    $("#choice-submit-answer-button").click(async () => {
        const user_ans = new FormData();
        user_ans.append("_token", token);
	let empty_problem = false;
        for (let i = 1; i <= questionCounter; ++i) {
            const ans_list = $(`input[name=problem${i}]:checked`).map((i, e) => e.value).get();
            const ans = ans_list.join("");
            if (ans.length === 0) {
                empty_problem = true;
                break;
	    }
            user_ans.append(`answer_output${i}_upload_type`, "editor");
            user_ans.append(`answer_output${i}_editor`, ans);
            user_ans.append(`answer_output${i}_file`, new Blob([], { type: "application/octet-stream" }), "");
        }
        if (empty_problem) {
            alert("请至少选择一个选项!");
            return;
	}
        user_ans.append("submit-answer", "answer");
        const d = await saveAnswer(location.pathname, user_ans);
        const r = await submitForm(location.pathname, user_ans);
        location.href = redirect_page;
    });
});
