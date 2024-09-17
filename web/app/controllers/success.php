<?php
	requireLib('shjs');
	requireLib('mathjax');
	echoUOJPageHeader(UOJLocale::get('help')) 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Close Page</title>
    <script type="text/javascript">
        // 设置3秒后自动关闭网页
        setTimeout(function() {
            window.close();
        }, 3000);
    </script>
</head>
<body>
<?php
	$currentDateTime = new DateTime();
	$targetDateTime = new DateTime('2024-06-30 16:00:00');
	$pystart = new DateTime('2024-06-30 16:30:00');
	$pyend = new DateTime('2024-06-30 18:00:00');
?>
	<?php if (($currentDateTime < $targetDateTime)||($currentDateTime > $pystart && $currentDateTime < $pyend)) : ?>
<article>
	<header>
		<h2 class="page-header">提交成功!</h2>
	</header>
	<section>
		<div>
			您的代码已经成功提交！本窗口将在3秒后自动关闭。<hr />
			<button class="btn btn-primary" onclick="history.back(-1);">返回</button>
		</div>
	</section>
</article>
<?php else: ?>
<article>
        <header>
                <h2 class="page-header">比赛已结束，提交失败!</h2>
        </header>
        <section>
                <div>
                        本窗口将在3秒后自动关闭。<hr />
                        <button class="btn btn-primary" onclick="history.back(-1);">返回</button>
                </div>
        </section>
</article>
<?php endif ?>
</body>
</html>
<?php echoUOJPageFooter() ?>
