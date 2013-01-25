<?php
//print download option link and start form for posting review information to the download function
print '
<font color="#006600"><i>or you can download your review to your pc.</i></font> 
<form action="../modules/download_review/download.php" method="POST">';


//select review information from the database
$pq = "SELECT " . OCC_TABLE_PAPERREVIEWER . ".*, " . OCC_TABLE_PAPER . ".title FROM " . OCC_TABLE_PAPERREVIEWER . ", " . OCC_TABLE_REVIEWER . ", " . OCC_TABLE_PAPER . " WHERE " . OCC_TABLE_PAPERREVIEWER . ".reviewerid='".$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".paperid='".$_GET['pid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".reviewerid=" . OCC_TABLE_REVIEWER . ".reviewerid AND " . OCC_TABLE_PAPERREVIEWER . ".paperid=" . OCC_TABLE_PAPER . ".paperid";
$pr = ocsql_query($pq) or err("Unable to view comments (" . mysql_errno() . ")",$printHeader,$printHeaderFunction);
$pl = mysql_fetch_array($pr);
//select paper's recommended sessions
$sq = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . $_GET['pid'] . "' AND `reviewerid`='" . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "'";
	$sr = ocsql_query($sq); ### or err("Unable to retrieve sessions");
	$review['sessions'] = array();
	while ($sl = mysql_fetch_array($sr)) { 
		$review['sessions'][] = $sl['topicid'];
	}


//print hidden inputs for posting information selected from the database
print'
<input type="hidden" name="reviewerid" value="'.$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'].'">
<input type="hidden" name="paperid" value="'.$_GET['pid'].'">
<input type="submit" name="submit" value="Download Review">

<input type="hidden" name="recommendation" value="'.$pl['recommendation'].'">
<input type="hidden" name="category" value="'.$pl['category'].'">
<input type="hidden" name="value" value="'.$pl['value'].'">
<input type="hidden" name="familiar" value="'.$pl['familiar'].'">
<input type="hidden" name="bpcandidate" value="'.$pl['bpcandidate'].'">
<input type="hidden" name="length" value="'.$pl['length'].'">
<input type="hidden" name="difference" value="'.$pl['difference'].'">
<input type="hidden" name="pccomments" value="'.$pl['pccomments'].'">
<input type="hidden" name="authorcomments" value="'.$pl['authorcomments'].'">
';
foreach($review['sessions'] as $session)
	print '<input type="hidden" name="session[]" value="'.$session.'">';

print'
</form>';
