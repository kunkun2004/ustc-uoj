<?php
if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
    become404Page();
}
genMoreContestInfo($contest);

if ($myUser == null) {
    redirectToLogin();
} elseif ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
    redirectTo('/contests');
}

$contestid = $contest["id"];
$row = DB::selectFirst("select camera from contests where id = $contestid");
if($row['camera'] == 0)
{
    redirectTo('/contest/'. $contest["id"] .'/register');
}
$nowUser = $myUser["username"];
$pos = "pre";
if(DB::selectCount("select id from contest_picup where pos='$pos' and contest_id=$contestid and user_id='$nowUser'") > 0)
{
    redirectTo('/contest/'. $contest["id"] .'/register');
}

?><!DOCTYPE html>
<html>

<head lang="en">
    <meta charset="UTF-8">
    <title>测试摄像头</title>
    <link rel="stylesheet" href="/css/public.css" />
    <link rel="stylesheet" href="/css/main.css" />
</head>

<body>
    <div class="page_container">
        <div class="oj_header">
            <ul class="oj_nav clearfix">
                <li><a href="/contests">赛事</a></li>
                <li><a href="#">题库</a></li>
            </ul>
        </div>
        <div class="oj_center">
            <div class="oj_title">
                <span><?= $contest["name"]; ?></span>
            </div>
            <div class="oj_video_test">
                <div class="test_progress clearfix">
                    <div class="step_item step_active">
                        <p>1</p>
                        <p>测试摄像头</p>
                    </div>
                    <div class="step_item">
                        <p>2</p>
                        <p>阅读考试须知</p>
                    </div>
                </div>
                <div class="test_attention">请点击下方按钮进行拍照，考试过程中摄像头将全程开启。照片将用于和考场监控对比。</div>
                <div class="test_video_box">
                    <div class="myvideo">
                        <video id="video" src=""></video>
                        <div class="photograph"><button id="picture">点击拍照</button></div>
                    </div>
                    <div class="mypic">
                        <canvas id="canvas"></canvas>
                        <div class="photo_upload"><button id="picup">确定并上传</button></div>
                    </div>
                </div>
                <div class="video_tips">
                    1、请确保网络良好，不会影响线上考试；<br />
                    2、本人信息仅做本次考试用，严禁泄露；<br />
                    3、请所有考生诚信考试、独立作答，如出现替考、作弊等现象，一经核实，取消考试成绩。
                </div>
                <div class="operation_step clearfix">
                    <div class="jump_step">
                        <a href="/contest/<?= $contest["id"]; ?>/register">下一步</a>
                        <span>若没有摄像头，则跳过这一步</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/jquery-2.1.4/jquery.min.js"></script>
    <script src="/js/public.js"></script>
    <script>
        // 老的浏览器可能根本没有实现 mediaDevices，所以我们可以先设置一个空的对象
        if (navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
        }

        // 一些浏览器部分支持 mediaDevices。我们不能直接给对象设置 getUserMedia
        // 因为这样可能会覆盖已有的属性。这里我们只会在没有getUserMedia属性的时候添加它。
        if (navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = function (constraints) {

                // 首先，如果有getUserMedia的话，就获得它
                var getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

                // 一些浏览器根本没实现它 - 那么就返回一个error到promise的reject来保持一个统一的接口
                if (!getUserMedia) {
                    return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
                }

                // 否则，为老的navigator.getUserMedia方法包裹一个Promise
                return new Promise(function (resolve, reject) {
                    getUserMedia.call(navigator, constraints, resolve, reject);
                });
            }
        }

        video = document.getElementById('video');
        video.style.width = document.width + 'px';
        video.style.height = document.height + 'px';
        video.setAttribute('autoplay', '');
        video.muted = true;
        video.setAttribute('playsinline', '');

        var canvas = document.getElementById("canvas");
        var context = canvas.getContext("2d");

        var constraints = { audio: true, video: { width: 1280, height: 720 } };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (mediaStream) {
                var video = document.querySelector('video');

                if ("srcObject" in video) {
                    video.srcObject = mediaStream;
                } else {
                    // 防止在新的浏览器里使用它，应为它已经不再支持了
                    video.src = window.URL.createObjectURL(mediaStream);
                }


                video.onloadedmetadata = function (e) {
                    video.play();
                };
            })
            .catch(function (err) {
                alert('找不到摄像设备');
            });

        document.getElementById("picture").addEventListener("click", function () {
            context.drawImage(video, 0, 0, 300, 180);
            $(".myvideo").css("display", "none");
            $(".mypic").css("display", "block");
        });


        $(document).ready(function () {

            $("#picup").click(function () {

                var canvas = document.getElementById("canvas");
                var datapic = canvas.toDataURL();

                if (datapic.length < 5000) {
                    alert('上传失败！请先点击拍照'); location.href = '';
                    return false;
                }

		$.post("mypicup", { datapic: datapic, contest_id: <?= $contestid ?> }, function (result) {
                    if (result == "success") { alert("上传成功!");$(".jump_step a").css("background-color", "#08ca99"); }
		    else { alert(`上传失败: ${result}，请重试!`); locaton.href = ''; }
                });

            });
        });
    </script>
</body>

</html>
