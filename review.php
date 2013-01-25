<?php

// +----------------------------------------------------------------------+
// | OpenConf                                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 Zakon Group LLC.  All Rights Reserved.       |
// +----------------------------------------------------------------------+
// | This source file is subject to the OpenConf License, available on    |
// | the OpenConf web site: www.OpenConf.com                              |
// +----------------------------------------------------------------------+

require_once "../include.php";
require_once "review_inc.php";
require_once OCC_MIME_FILE;

beginSession();

printHeader("Review",2);










function saveReviewForm($review, $thepid) {
	global $OC_reviewQuestionsAR, $OC_completedReviewQuestionsAR;
	
	// Check for valid submission
	if (!validToken('ac')) {
		$w = 'This submission failed our security check, possibly due to you have signed in again, or a third-party having redirected you here.  Below is the information provided.  If you were attempting to submit a review, print this information out or copy/paste it to a new document so it can be re-entered; then <a href="' . $_SERVER['PHP_SELF'] . '?pid=' . (is_numeric($_POST['pid']) ? $_POST['pid'] : '') . '">try again</a>.  If the problem persists, please contact the Chair.<div style="color: #000; margin-top: 1em; font-weight: normal;">';
		$OC_reviewQuestionsARkeys = array_keys($OC_reviewQuestionsAR);
		foreach ($_POST as $k => $v) {
			if (($k == 'submit') || ($k == 'token')) { continue; }
			if (in_array($k, $OC_reviewQuestionsARkeys)) {
				$w .= "<br />\n<hr /><p />\n<b>" . safeHTMLstr($OC_reviewQuestionsAR[$k]['short']) . "</b> ";
				if ($OC_reviewQuestionsAR[$k]['usekey']) {
					$w .= $OC_reviewQuestionsAR[$k]['values'][$v];
				} else {
					$w .= safeHTMLstr($v);
				}
			} else {
				$w .= "<br />\n<hr /><p />\n<b>" . safeHTMLstr($k) . ":</b> " . safeHTMLstr($v);
			}
		}
		$w .= '<hr /></div>';
		warn($w);
	}

	// Email review copy - do it here in case of errors/problems below
	if (isset($_POST['emailcopy']) && ($_POST['emailcopy'] == "1")) {
		$msg = "Following is a copy of your review for submission number " . $thepid . " submitted to " . $GLOBALS['OC_configAR']['OC_confName'] . ".  Note that you will receive this email even if an error occured during submission.\n\n----------------------------------------\n\n";
		
		foreach ($OC_reviewQuestionsAR as $qid => $q) {
			$msg .= $q['short'] . "\n\n";	// hci - changed question to short
			if (!isset($_POST[$qid])) {
				// empty value
			} elseif (is_array($_POST[$qid])) {
				foreach($_POST[$qid] as $qv) {
					$msg .= '-' . strip_tags($q['values'][$qv]) . "\n";
				}
			} elseif ($q['usekey'] && isset($q['values'][$_POST[$qid]])) {
				$msg .= strip_tags($q['values'][$_POST[$qid]]);
			} else {
				$msg .= $_POST[$qid];
			}
			$msg .= "\n\n----------------------------------------\n\n";
		}

		if (oc_hookSet('committee-review-msg')) {
			foreach ($GLOBALS['OC_hooksAR']['committee-review-msg'] as $v) {
				require_once $v;
			}
		}

		if (!sendEmail($review['email'],"Review of submission $thepid",$msg)) {
			print '<p class="err">Unable to send copy of review via email</p>';
		}
	}

	$q = "UPDATE `" . OCC_TABLE_PAPERREVIEWER . "` SET ";
	$q .= " `updated`='" . date('Y-m-d') . "'";
	if (isset($OC_reviewQuestionsAR['recommendation']) && isset($_POST['recommendation']) && preg_match("/^\d+$/",$_POST['recommendation'])) {
		$q .= ', recommendation="'.$_POST['recommendation'].'"';
	}
	if (isset($OC_reviewQuestionsAR['category']) && isset($_POST['category']) && preg_match("/^\d+$/",$_POST['category'])) {
		$q .= ', `category`="' . $_POST['category'] . '"';
	}
	if (isset($OC_reviewQuestionsAR['value']) && isset($_POST['value']) && !empty($_POST['value'])) {
		$valstr = implode(",",$_POST['value']); 
		if (preg_match("/^[\d,]+$/",$valstr)) {
			$q .= ', `value`="' . $valstr . '"';
		}
	}
	else {
		$q .= ', `value`=NULL';
	}
	if (isset($OC_reviewQuestionsAR['familiar']) && isset($_POST['familiar']) && in_array($_POST['familiar'], $OC_reviewQuestionsAR['familiar']['values'])) {
		$q .= ', `familiar`="' . $_POST['familiar'] . '"';
	}
	if (isset($OC_reviewQuestionsAR['bpcandidate']) && isset($_POST['bpcandidate']) && in_array($_POST['bpcandidate'], $OC_reviewQuestionsAR['bpcandidate']['values'])) {
		$q .= ', `bpcandidate`="' . $_POST['bpcandidate'] . '"';
	}
	if (isset($OC_reviewQuestionsAR['length']) && isset($_POST['length']) && in_array($_POST['length'], $OC_reviewQuestionsAR['length']['values'])) {
		$q .= ', `length`="' . $_POST['length'] . '"';
	}
	if (isset($OC_reviewQuestionsAR['difference']) && isset($_POST['difference']) && preg_match("/^\d+$/", $_POST['difference'])) {
		$q .= ', `difference`="' . $_POST['difference'] . '"';
	}

	if (isset($OC_reviewQuestionsAR['pccomments']) && isset($_POST['pccomments']) && !empty($_POST['pccomments'])) {
		$q .= ', `pccomments`="' . safeSQLstr($_POST['pccomments']) . '"';
	}
	else {
		$q .= ', `pccomments`=NULL';
	}
	if (isset($OC_reviewQuestionsAR['authorcomments']) && isset($_POST['authorcomments']) && !empty($_POST['authorcomments'])) {
		$q .= ', `authorcomments`="' . safeSQLstr($_POST['authorcomments']) . '"';
	}
	else {
		$q .= ', `authorcomments`=NULL';
	}

	// Completed?
	if (!empty($OC_completedReviewQuestionsAR) && isset($_POST['completed']) && ($_POST['completed'] == 1)) {
		$completed = 'T';
		foreach ($OC_completedReviewQuestionsAR as $question) {
			if (!isset($_POST[$question]) || empty($_POST[$question])) {
				$completed='F';
			}
		}
	} else {
		$completed = 'F';
	}
	$q .= ', `completed`="' . $completed . '"';
	
	$q .= ' WHERE `paperid`="' . $thepid . '" AND `reviewerid`="' . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . '"';
	ocsql_query($q) or err("Unable to submit review - ".mysql_errno());

	// Update papersession
	if (isset($OC_reviewQuestionsAR['sessions'])) {
		$q2 = "DELETE FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . $thepid . "' AND `reviewerid`='" . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "'";
		ocsql_query($q2) or err("Unable to update sessions - ".mysql_errno());
		if (isset($_POST['sessions']) && is_array($_POST['sessions'])) {
			foreach ($_POST['sessions'] as $tid) { 
				if (preg_match("/^[\d]+$/",$tid)) { 
					$q3 = "INSERT INTO `" . OCC_TABLE_PAPERSESSION . "` (`paperid`,`reviewerid`,`topicid`) VALUES (" . $thepid . "," . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "," . $tid . ")";
					ocsql_query($q3) or err("Unable to add session - ".mysql_errno());
				}
			}
		}
	}
	
	if (oc_hookSet('committee-review-save')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-save'] as $v) {
			require_once $v;
		}
	}

	print '<p>Review has been submitted.</p>';


	if (oc_hookSet('show-link2')) {
			foreach ($GLOBALS['OC_hooksAR']['show-link2'] as $hook) {
				require_once $hook;
			}
		}



	if (isset($_POST['completed']) && ($_POST['completed'] == 1) && ($completed == 'F')) {
		print '<p>However as not all required questions were answered, the review was not marked as completed.</p>';
	}
	print '<p><a href="reviewer.php">Return to Reviewer page</a></p>';
}// function saveReviewForm

function printReviewForm($review, $thepid) {
	global $OC_configAR, $OC_reviewQuestionsAR;

	// Make an array of sessions reviewer has listed the paper under
	$sq = "SELECT `topicid` FROM `" . OCC_TABLE_PAPERSESSION . "` WHERE `paperid`='" . $thepid . "' AND `reviewerid`='" . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'] . "'";
	$sr = ocsql_query($sq); ### or err("Unable to retrieve sessions");
	$review['sessions'] = array();
	while ($sl = mysql_fetch_array($sr)) { 
		$review['sessions'][] = $sl['topicid'];
	}

	if (oc_hookSet('committee-review-fields')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-fields'] as $v) {
			require_once $v;
		}
	}

	print "<center>Submission ID: ".$thepid."<br><b><i>";
	print safeHTMLstr($review['title']);
	/*
	$paper = $thepid . "." . $review['format'];
	if (oc_isFile($ptitle = $OC_configAR['OC_paperDir'] . $paper)) {
		print '<a href="paper.php?p='.$paper.'" target="_blank">' . safeHTMLstr($review['title']) . '<p><img src="../images/document.gif" border=0></a></b><br />(' . round((oc_fileSize($ptitle)/1024)) . 'KB)<b>';
	} else {
		print safeHTMLstr($review['title']);
	}
	*/
	print '</i></b></center><p><hr><font color="#006600"><i>TIP: Use a local text editor to write your review, and then select/copy the information below.  This way, in case of a network outage or some other problem, you won\'t lose the review.</i></font>';




	if (oc_hookSet('show-link')) {
			foreach ($GLOBALS['OC_hooksAR']['show-link'] as $hook) {
				require_once $hook;
			}
		}





	print'<hr><p>';
	print '
<form method="POST" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="token" value="' . $_SESSION[OCC_SESSION_VAR_NAME]['actoken'] . '" />
<input type="hidden" name="pid" value="'.$thepid.'">
	';
	
	// Iterate through & display review questions
	foreach ($OC_reviewQuestionsAR as $qid => $q) {
		print '<p><b>' . $q['question'] . '</b>';
		if (!empty($q['note'])) {
			print '<br /><span class="note">' . $q['note'] . '</span>';
		}
		print "</p>\n";
		
		switch ($q['type']) {
			case 'radio':
				print generateRadioOptions($qid, $q['values'], varValue($qid, $review), $q['usekey'], '', (isset($q['delimiter']) ? $q['delimiter'] : '<br />'));
				break;
			case 'checkbox':
				if (isset($review[$qid])) {
					if (!is_array($review[$qid])) {
						$vals = explode(',', $review[$qid]);
					} else {
						$vals = $review[$qid];
					}
				} else {
					$vals = array();
				}
				print generateCheckboxOptions($qid, $q['values'], $vals, $q['usekey'], '', (isset($q['delimiter']) ? $q['delimiter'] : '<br />'));
				break;
			case 'text':
				print '<input name="' . $qid . '" size="60" value="' . safeHTMLstr(varValue($qid, $review)) . '" />';
				break;
			case 'textarea':
				print '<textarea name="' . $qid . '" cols="60" rows="10">' . safeHTMLstr(varValue($qid, $review)) . '</textarea>';
				break;
			default:
				err('There is an error with review question ID ' . $qid);
		}
		
		if (!isset($q['nobreak']) || !$q['nobreak']) {
			print "<p><hr /></p>\n";
		}
	}

	if (oc_hookSet('committee-review-extra')) {
		foreach ($GLOBALS['OC_hooksAR']['committee-review-extra'] as $v) {
			require_once $v;
		}
	}
	
	print '<dl><dt><input type="checkbox" name="emailcopy" value="1" checked> <b>Email me a copy of this review</b></dt><dd><span class="note">Useful for your own record or in case there is some kind of error during updating. ';
	if ($OC_configAR['OC_ReviewerTimeout'] > 0) {
		print 'Note that if your session times out, you will not receive an email; instead you should log back in right away to recover the review.';
	}
	print "</span></dd></dl><p />\n";
	
	print '<dl><dt><input type="checkbox" name="completed" value="1"';
	if (varValue('completed', $review) == "T") { print ' checked'; }
	print '> <b>I have completed the review</b></dt><dd><span class="note">Check this box when you have made a recommendation and completely finished reviewing this submission.  This is used only to track how many outstanding reviews there are.  You will still be able to edit this review after checking this box, until review submission is closed.</span></dd></dl><p />';

	print "<hr><p>\n";
	
	if ($thepid != "blank") {
		print '<input type="submit" name="submit" value="Submit Review"><p>';
	}
	else {
		print '[ Sample Review Form - Fill in and submit review by clicking the submission title on main reviewer page ]<p>';
	}
	
	print '<span class="note">Before submitting your review, consider printing it out and copying/pasting the descriptive text fields to a text document.  This way, in case of a network/system problem, you will have all the information if it needs to be re-entered.</span><p />';

	if ($OC_configAR['OC_ReviewerTimeout'] > 0) {
		print '<span class="note">Should your session timeout while filling out this review, log back in right away as we may be able to recover your review.</span><p />';
	}
} // function printReviewForm


if (isset($_POST['submit']) && ($_POST['submit'] == "Submit Review")) {
	$q = "SELECT paperid, email FROM " . OCC_TABLE_PAPERREVIEWER . ", " . OCC_TABLE_REVIEWER . " WHERE paperid='".$_POST['pid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".reviewerid='".$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".reviewerid=" . OCC_TABLE_REVIEWER . ".reviewerid";
	$thepid = $_POST['pid'];
} else {
	$q = "SELECT title, format, " . OCC_TABLE_PAPERREVIEWER . ".* FROM " . OCC_TABLE_PAPERREVIEWER . ", " . OCC_TABLE_PAPER . " WHERE " . OCC_TABLE_PAPERREVIEWER . ".paperid='".$_GET['pid']."' AND reviewerid='".$_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']."' AND " . OCC_TABLE_PAPERREVIEWER . ".paperid=" . OCC_TABLE_PAPER . ".paperid";
	$thepid = $_GET['pid'];
}

require_once "review_inc.php";

if ($thepid == "blank") {	// display blank form
	$review = array();
	$review['title'] = "Sample Review Title";
	$review['format'] = "";
	printReviewForm($review,0);
} elseif (!preg_match("/^\d+$/",$thepid)) {
	print '<span class="err">Invalid submission id</span><p>';
} else {
	// Warn if conflict
	$conflictAR = getConflicts($_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid']);
	if (in_array($thepid . '-' . $_SESSION[OCC_SESSION_VAR_NAME]['acreviewerid'], $conflictAR)) {
	 	warn("You appear to have a conflict with this submission; please contact the Chair");
	}

	$r = ocsql_query($q) or err("Unable to retrieve submission for review ".mysql_errno());
	if (mysql_num_rows($r) == 0) { 
		print '<span class="err">Either the submission does not exist, or you have not been assigned it for review.  If this is in error, please contact the <a href="mailto:' . $OC_configAR['OC_pcemail'] . '?subject=Review error">Program Chair</a>.</span><p>';
	} else {
		$review = mysql_fetch_array($r); 
		if (isset($_POST['submit']) && ($_POST['submit'] == "Submit Review")) {
			saveReviewForm($review, $thepid);
		} else {
			printReviewForm($review, $thepid);
		}
    }
}

printFooter();

?>
