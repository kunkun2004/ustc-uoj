$(document).ready(function () {
    const article = $('article.top-buffer-md');
    let content = article.html();
    let questionCounter = 0;
    let inputCounter = 0;

    const parseQuestions = (content) => {
        const questionPatterns = [
            { type: 'fill', regex: /\[填空题\](.*?)(?=\nblank=\d+)/gs }
        ];

        let questions = [];
        questionPatterns.forEach(pattern => {
            let matches = [...content.matchAll(pattern.regex)];
            matches.forEach(match => {
                const question = match[1].trim();
                const blankMatch = content.match(/blank=(\d+)/);
                const blanks = blankMatch ? parseInt(blankMatch[1], 10) : 0;
                questions.push({ type: pattern.type, question, blanks, raw: match[0] });
            });
        });

        return questions;
    };

    const processParagraph = (paragraph) => {
        const parsedQuestions = parseQuestions(paragraph);
        let newParagraph = paragraph;

        parsedQuestions.forEach((parsedQuestion) => {
            questionCounter += 1;
            const { type, question, blanks, raw } = parsedQuestion;

            // 创建题目容器
            const problemContainer = $('<div></div>').addClass('choose-problem').addClass(type === 'fill' ? 'fill-blank' : '');

            // 创建题目内容，添加序号
            const problemContent = $('<div></div>').addClass('problem-content').html(`${problemNum}. ${question.replace(/\n/g, '<br>')}`);
            problemContainer.append(problemContent);

            // 生成每个填空输入框
            for (let i = 1; i <= blanks; i++) {
                inputCounter += 1;
                const inputField = $('<input>')
                    .attr('type', 'text')
                    .addClass('form-control').addClass('fill')
                    .attr('name', `problem${inputCounter}`)
                    .attr('placeholder', `第${i}空`);
                problemContainer.append(inputField);
            }

            // 将生成的HTML转换为字符串
            const newHtml = $('<div>').append(problemContainer).html();

            // 替换原始内容
            newParagraph = newParagraph.replace(raw, newHtml).replace(/blank=\d+/, '');
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
        for (let i = 1; i <= inputCounter; ++i) {
            const ans = $(`input[name=problem${i}]`).val();
            user_ans.append(`answer_output${i}_upload_type`, "editor");
            user_ans.append(`answer_output${i}_editor`, ans);
            user_ans.append(`answer_output${i}_file`, new Blob([], { type: "application/octet-stream" }), "");
        }
        user_ans.append("submit-answer", "answer");
        const r = await submitForm(location.pathname, user_ans);
        location.href = redirect_page;
    });
});
