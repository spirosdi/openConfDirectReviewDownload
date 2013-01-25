<?php
//select from database the value of the submission
$pq = "SELECT " . OCC_TABLE_PAPERREVIEWER . ".value FROM " . OCC_TABLE_PAPERREVIEWER . ", " . OCC_TABLE_REVIEWER . ", " . OCC_TABLE_PAPER . " WHERE " . OCC_TABLE_PAPERREVIEWER . ".reviewerid='".$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".paperid='".$thepid."' AND " . OCC_TABLE_PAPERREVIEWER . ".reviewerid=" . OCC_TABLE_REVIEWER . ".reviewerid AND " . OCC_TABLE_PAPERREVIEWER . ".paperid=" . OCC_TABLE_PAPER . ".paperid";
$pr = ocsql_query($pq) or err("Unable to view comments (" . mysql_errno() . ")",$printHeader,$printHeaderFunction);
$pl = mysql_fetch_array($pr);

//select paper's recommended sessions
$sq = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . $thepid . "' AND `reviewerid`='" . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "'";
	$sr = ocsql_query($sq); ### or err("Unable to retrieve sessions");
	$review['sessions'] = array();
	while ($sl = mysql_fetch_array($sr)) { 
		$review['sessions'][] = $sl['topicid'];
	}

//print download option link, form with hidden inputs for posting information selected from the database
print '
you may now want to download your submitted review:
<form action="../modules/download_review/download.php" method="POST">
<input type="hidden" name="reviewerid" value="'.$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'].'">
<input type="hidden" name="paperid" value="'.$thepid.'">


<input type="hidden" name="recommendation" value="'.$_POST['recommendation'].'">
<input type="hidden" name="category" value="'.$_POST['category'].'">
<input type="hidden" name="value" value="'.$pl['value'].'">
<input type="hidden" name="familiar" value="'.$_POST['familiar'].'">
<input type="hidden" name="bpcandidate" value="'.$_POST['bpcandidate'].'">
<input type="hidden" name="length" value="'.$_POST['length'].'">
<input type="hidden" name="difference" value="'.$_POST['difference'].'">
<input type="hidden" name="pccomments" value="'.$_POST['pccomments'].'">
<input type="hidden" name="authorcomments" value="'.$_POST['authorcomments'].'">
<input type="submit" name="submit" value="Download Review">';
foreach($review['sessions'] as $session)
	print '<input type="hidden" name="session[]" value="'.$session.'">';

print'
</form>';
