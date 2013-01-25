<?
require_once "../../include.php";
//arrays that hold review questions and answers
$recommendation = array
(
"Reject: Content inappropriate to the conference or has little merit",
"Probable Reject: Basic flaws in content or presentation or very poorly written",
"Marginal Tend to Reject: Not as badly flawed; major effort necessary to make acceptable but content well-covered in literature already",
"Marginal Tend to Accept: Content has merit, but accuracy, clarity, completeness, and/or writing should and could be improved in time",
"Clear Accept: Content, presentation, and writing meet professional norms; improvements may be advisable but acceptable as is",
"Must Accept: Candidate for outstanding submission. Suggested improvements still appropriate"
);
$category = array
(
"Highly theoretical",
"Tends towards theoretical",
"Balanced theory and practice",
"Tends toward practical",
"Highly practical"
);
$value = array
(
"New information",
"Valuable confirmation of present knowledge",
"Clarity to present understanding",
"New perspective, issue, or problem definition",
"Not much",
"Other"
);
$difference = array
(
"Totally or largely different from other submissions",
"Moderately different from other submissions",
"Totally or largely identical to other submissions",
"Don't know"
);

//get information posted from the form
$pid= $_POST['paperid'];
$sessions = implode("\n",$_POST['session']);
//create the file where the review is to be exported
$filename="".$pid.".review.txt";
if (!($fp = fopen($filename, 'w'))) {
    return;
}

//export the review to the text file
fprintf($fp,"REVIEW\n
submission id:\n
%s 
=======================================================================
Recommendation:\n
%s
=======================================================================
Submission Categorization:\n
%s
=======================================================================
Overall Value Added to the Field:\n
%s
%s
%s
%s
%s
%s
=======================================================================
Reviewer Familiarity with Subject Matter:\n
%s
=======================================================================
Is this submission a candidate for the best submission award?:\n
%s
=======================================================================
Is the submission length appropriate?:\n
%s
=======================================================================
If from reading the submission you know who the author is, how different is this from earlier submissions on the same topic by the same author? That is, is it the same as or a slight modification of other submissions, with little or no new information?:\n
%s
=======================================================================
Optional: Which of the following session(s) would be the most appropriate for this submission?(ids)\n
%s
=======================================================================
Comments for the Authors:\n
%s
=======================================================================
Comments for the Program Committee (authors will not see these comments):\n
%s", $pid, $recommendation[$_POST['recommendation']-1], $category[$_POST['category']-1], $value[$_POST['value'][0]-1], $value[$_POST['value'][2]-1],$value[$_POST['value'][4]-1], $value[$_POST['value'][6]-1], $value[$_POST['value'][8]-1], $value[$_POST['value'][10]-1], $_POST['familiar'], $_POST['bpcandidate'], $_POST['length'], $difference[$_POST['difference']-1],$sessions, $_POST['authorcomments'], $_POST['pccomments']);



//use the openconf function to display the file 
if (! oc_displayFile($filename, "text/plain")) {
	printHeader("File Retrieval Error", $printHeaderFunction);
	warn("Unable to retrieve file " . $_GET['p']);
}
//delete file
unlink($filename);
