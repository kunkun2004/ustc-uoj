<?php
	requirePHPLib('form');
	
	$upcoming_contest_name = null;
	$upcoming_contest_href = null;
	$rest_second = 1000000;
	function echoContest($contest) {
		global $myUser, $upcoming_contest_name, $upcoming_contest_href, $rest_second;
		
		$constestlink = <<<EOD
<h3><a href="/contest/{$contest['id']}" class="button">点击进入</a></h3>
EOD;
		$contest_name_link = <<<EOD
<a href="/contest/{$contest['id']}">{$contest['name']}</a>
EOD;
		genMoreContestInfo($contest);
		if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
			$cur_rest_second = $contest['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
			if ($cur_rest_second < $rest_second) {
				$upcoming_contest_name = $contest['name'];
				$upcoming_contest_href = "/contest/{$contest['id']}";
				$rest_second = $cur_rest_second;
			}
			if ($myUser != null && hasRegistered($myUser, $contest)) {
				$contest_name_link .= '<sup><a style="color:green">'.UOJLocale::get('contests::registered').'</a></sup>';
			} else {
				$contest_name_link .= '<sup><a style="color:red" href="/contest/'.$contest['id'].'/register">'.UOJLocale::get('contests::register').'</a></sup>';
			}
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::in progress').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_PENDING_FINAL_TEST) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::pending final test').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_TESTING) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::final testing').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_FINISHED) {
			$contest_name_link .= '<sup><a style="color:grey" href="/contest/'.$contest['id'].'/standings">'.UOJLocale::get('contests::ended').'</a></sup>';
		}
		
		$last_hour = round($contest['last_min'] / 60, 2);
		
		$click_zan_block = getClickZanBlock('C', $contest['id'], $contest['zan']);
		echo '<div class="contest-container">';
		echo '<div class="contest-image">';
		echo '<img src="https://www.baidu.com/img/PCtm_d9c8750bed0b3c7d089fa7d55720d6cf.png" alt="比赛图片">';
		echo '</div>';
		echo '<div class="contest-info">';
		echo '<h3>', $contest_name_link, '</h3>';
		echo '<h5>开始时间：', '<a href="'.HTML::timeanddate_url($contest['start_time'], array('duration' => $contest['last_min'])).'">'.$contest['start_time_str'].'</a>', '</h5>';
		echo '<h5>时长：', UOJLocale::get('hours', $last_hour), '</h5>';
		echo $constestlink;
		echo '</div>';

		//echo '<td>', $contest_name_link, '</td>';
		//echo '<td>', '<a href="'.HTML::timeanddate_url($contest['start_time'], array('duration' => $contest['last_min'])).'">'.$contest['start_time_str'].'</a>', '</td>';
		//echo '<td>', UOJLocale::get('hours', $last_hour), '</td>';
		//echo '<td>', '<a href="/contest/'.$contest['id'].'/registrants"><span class="glyphicon glyphicon-user"></span> &times;'.$contest['player_num'].'</a>', '</td>';
		//echo '<td>', '<div class="text-left">'.$click_zan_block.'</div>', '</td>';
		
		echo '</div>';
		echo '<br>';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('contests')); ?>
<?php
    // 可以动态加载图片路径，或从数据库获取
    $images = [
      "https://via.placeholder.com/600x300/FF5733/FFFFFF?text=Slide+1",
      "https://via.placeholder.com/600x300/33FF57/FFFFFF?text=Slide+2",
      "https://via.placeholder.com/600x300/3357FF/FFFFFF?text=Slide+3"
    ];
  ?>

  <div class="carousel">
    <div class="carousel-images">
      <?php foreach ($images as $image): ?>
        <img src="<?php echo $image; ?>" alt="Slide">
      <?php endforeach; ?>
    </div>
    <button class="prev">&#10094;</button>
    <button class="next">&#10095;</button>
  </div>

  <!-- 圆点指示器 -->
  <div class="dots">
    <?php foreach ($images as $index => $image): ?>
      <span class="dot"></span>
    <?php endforeach; ?>
  </div>

  <script>
    // 获取元素
    const images = document.querySelectorAll('.carousel-images img');
    const carouselImages = document.querySelector('.carousel-images');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    const dots = document.querySelectorAll('.dot');
    
    let currentIndex = 0;
    let autoScrollInterval;

    // 更新轮播图显示
    function updateCarousel() {
      carouselImages.style.transform = `translateX(${-currentIndex * 100}%)`;
      dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
      });
    }

    // 下一张图片
    function showNextImage() {
      currentIndex = (currentIndex + 1) % images.length;
      updateCarousel();
    }

    // 上一张图片
    function showPrevImage() {
      currentIndex = (currentIndex - 1 + images.length) % images.length;
      updateCarousel();
    }

    // 点击按钮切换图片
    nextBtn.addEventListener('click', () => {
      showNextImage();
      resetAutoScroll();
    });

    prevBtn.addEventListener('click', () => {
      showPrevImage();
      resetAutoScroll();
    });

    // 点击圆点导航
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        currentIndex = index;
        updateCarousel();
        resetAutoScroll();
      });
    });

    // 自动滚动功能
    function startAutoScroll() {
      autoScrollInterval = setInterval(showNextImage, 3000);
    }

    // 当用户手动切换时重置自动滚动
    function resetAutoScroll() {
      clearInterval(autoScrollInterval);
      startAutoScroll();
    }

    // 初始化
    updateCarousel();
    startAutoScroll();
  </script>
<h4><?= UOJLocale::get('contests::current or upcoming contests') ?></h4>
<?php
	$table_header = '';
	$table_header .= '<tr>';
	$table_header .= '<th>'.UOJLocale::get('contests::contest name').'</th>';
	$table_header .= '<th style="width:15em;">'.UOJLocale::get('contests::start time').'</th>';
	$table_header .= '<th style="width:100px;">'.UOJLocale::get('contests::duration').'</th>';
	$table_header .= '<th style="width:100px;">'.UOJLocale::get('contests::the number of registrants').'</th>';
	$table_header .= '<th style="width:180px;">'.UOJLocale::get('appraisal').'</th>';
	$table_header .= '</tr>';
	echoContestTable(array('*'), 'contests', "status != 'finished'", 'order by id desc', $table_header,
		echoContest,
		array('page_len' => 100)
	);

?>

<h4><?= UOJLocale::get('contests::ended contests') ?></h4>
<?php
	echoContestTable(array('*'), 'contests', "status = 'finished'", 'order by id desc', $table_header,
		echoContest,
		array('page_len' => 100,
			'print_after_table' => function() {
				global $myUser;
				if (isSuperUser($myUser)) {
					echo '<div class="text-right">';
					echo '<a href="/contest/new" class="btn btn-primary">'.UOJLocale::get('contests::add new contest').'</a>';
					echo '</div>';
				}
			}
		)
	);
?>
<?php echoUOJPageFooter() ?>
