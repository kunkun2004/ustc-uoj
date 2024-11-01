<?php
// 报错提示
ini_set("display_errors", "On");
	requirePHPLib('form');
	requirePHPLib('judger');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	$tags = queryProblemTags($_GET['id']);

    if (in_array('choice', $tags)) {
        include 'prob_choice.php';
    } 
	else if(in_array('fill', $tags))
	{
		include 'prob_fill.php';
	}
	else {
        include 'prob_tradition.php';
    }
?>
