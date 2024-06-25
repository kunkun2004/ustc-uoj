$(document).ready(function () {
    const article = $('article.top-buffer-md');
    let content = article.html();
    let questionCounter = 0;

    const parseQuestions = (content) => {
        const questionPatterns = [
            { type: 'single', regex: /\[单选题\](.*?)(?=\[|$|\[多选题\])/gs },
            { type: 'multiple', regex: /\[多选题\](.*?)(?=\[|$|\[单选题\])/gs }
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
            const problemTypeBadge = type === 'single' ? '单选题' : '多选题';
            const problemContent = $('<div></div>').addClass('problem-content').html(`${questionCounter}. [${problemTypeBadge}]${question.replace(/\n/g, '<br>')}`);
            problemContainer.append(problemContent);

            // 创建选项容器
            const problemChoices = $('<div></div>').addClass('problem-choices');

            // 生成每个选项
            choices.forEach((choice, index) => {
                const formCheck = $('<div></div>').addClass('form-check');
                const label = $('<label></label>').addClass('form-check-label');
                const input = $('<input>')
                    .attr('type', type === 'single' ? 'radio' : 'checkbox')
                    .addClass('form-check-input')
                    .attr('name', `problem${questionCounter}`)
                    .attr('value', String.fromCharCode(65 + index)); // 将索引转换为A, B, C, D

                label.append(input).append(`${String.fromCharCode(65 + index)}. ${choice.replace(/\n/g, '<br>&nbsp;&nbsp;')}`);
                formCheck.append(label);
                problemChoices.append(formCheck);
            });

            problemContainer.append(problemChoices);

            // 将生成的HTML转换为字符串
            const newHtml = $('<div>').append(problemContainer).html();

            // 替换原始内容
            newParagraph = newParagraph.replace(raw, newHtml);
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
    $("#choice-submit-answer-button").click(async () => {
        const user_ans = new FormData();
        user_ans.append("_token", token);
        for (let i = 1; i <= questionCounter; ++i) {
            const ans_list = $(`input[name=problem${i}]:checked`).map((i, e) => e.value).get();
            const ans = ans_list.join("");
            user_ans.append(`answer_output${i}_upload_type`, "editor");
            user_ans.append(`answer_output${i}_editor`, ans);
            user_ans.append(`answer_output${i}_file`, new Blob([], { type: "application/octet-stream" }), "");
        }
        user_ans.append("submit-answer", "answer");
        const r = await submitForm(location.pathname, user_ans);
        location.href = redirect_page;
    });
});
