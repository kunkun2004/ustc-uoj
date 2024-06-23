<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	$tags = queryProblemTags($_GET['id']);

    if (in_array('choice', $tags)) {
        include 'prob_choice.php';
    } else {
        include 'prob_tradition.php';
    }
?>
