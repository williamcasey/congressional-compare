<!--
- index.php
-
- page creates the front-end in HTML/CSS for the program,
- and it calls on 'senate_compare' class in the senate-compare-class.php file
-
-->
<!DOCTYPE html>
<html>
<head>
	<title>Senate Compare</title>
	<link rel="stylesheet" type="text/css" href="./styles.css">
	<script src="./jquery.min.js"></script>

</head>
<body>

<?php

include './senate-compare-class.php';

$sen = new senate_compare("localhost", "root", "", "congress");

$active = FALSE;

$sen_a = NULL;
$sen_b = NULL;

$select_a = "<label class='dropdown'><select class='select_a' name='a'>";
$select_b = "<label class='dropdown'><select class='select_b' name='b'>";

if(isset($_GET['a']) AND isset($_GET['b'])) {

	$valid = $sen->valid_id($_GET['a'], $_GET['b']);

	if($valid == TRUE AND is_bool($valid) == TRUE) {
		if($_GET['a'] == $_GET['b']) {
			$sen_a = $_GET['a'];
			$sen_info = $sen->sen_info($sen_a, $sen_a);
			$select_a = "<label class='dropdown'><select class='select_a' id='".substr($sen_info[0][2], 0, 1)."' name='a'>";
		} else {
			$active = TRUE;

			$sen_a = $_GET['a'];
			$sen_b = $_GET['b'];

			$per = $sen->compare($sen_a, $sen_b);
			$sen_info = $sen->sen_info($sen_a, $sen_b);

			$select_a = "<label class='dropdown'><select class='select_a' id='".substr($sen_info[0][2], 0, 1)."' name='a'>";
			$select_b = "<label class='dropdown'><select class='select_b' id='".substr($sen_info[1][2], 0, 1)."' name='b'>";

			$details = $sen->get_details();
		}
	} elseif(is_string($valid)) {
		$sen_a = ($valid == 'a' ? $_GET['a'] : $_GET['b']);
		$sen_info = $sen->sen_info($sen_a, $sen_a);
		$select_a = "<label class='dropdown'><select class='select_a' id='".substr($sen_info[0][2], 0, 1)."' name='a'>";
	}

} elseif((isset($_GET['a']) == TRUE AND isset($_GET['b']) == FALSE) OR (isset($_GET['a']) == FALSE AND isset($_GET['b']) == TRUE)) {
	$sen_a = (isset($_GET['a']) ? $_GET['a'] : $_GET['b']);
	$valid = $sen->valid_id($sen_a, $sen_a);
	if($valid == TRUE) {
		$sen_info = $sen->sen_info($sen_a, $sen_a);
		$select_a = "<label class='dropdown'><select class='select_a' id='".substr($sen_info[0][2], 0, 1)."' name='a'>";
	} else {
		$sen_a = NULL;
	}
}

$name_list = $sen->name_list($sen_a, $sen_b);

?>

	<header>
		<h1>Senate Compare</h1>
		<h3>Select two U.S. Senators to compare their voting records and determine the level of similarity in their voting behavior.</h3>
		<form id='compare' class='main-form' action='' method='GET'>
				<?php echo $select_a; ?>
					<optgroup>
					<option selected disabled hidden style='display: none' value=''>Select First Senator</option>
					<?php echo $name_list["a"]; ?>
					</optgroup>
				</select></label>
				<?php echo $select_b; ?>
				<optgroup>
					<option selected disabled hidden style='display: none' value=''>Select Second Senator</option>
					<?php echo $name_list["b"]; ?>
					</optgroup>
				</select></label>
			<input type='submit' id='compare-submit' form='compare' value='Compare Votes'/>
		</form>
	</header>

<?php 

if($active == TRUE) {

?>

<div class="sens">
	<div class="sen-a">
		<h2 style='border-left-color: <?php echo $sen_info[0][4]; ?>;' id='sen_name'><?php echo $sen_info[0][0]; ?></h2>
		<span id='sen_info'><?php echo $sen_info[0][2]." - ".$sen_info[0][1]; ?></span>
		<div class="sen-a-img">
			<img src="<?php echo $sen_info[0][3]; ?>"/>
		</div>
	</div>

	<div class="sen-b">
		<h2 style='border-right-color: <?php echo $sen_info[1][4]; ?>;' id='sen_name'><?php echo $sen_info[1][0]; ?></h2>
		<span id='sen_info'><?php echo $sen_info[1][2]." - ".$sen_info[1][1]; ?></span>
		<div class="sen-b-img">
			<img src="<?php echo $sen_info[1][3]; ?>"/>
		</div>
	</div>

	<div class="percentage">
		<span style="line-height: 22px;">When voting on issues, the two senators "agree"</span>
		<span id='per' title='click to view details of comparison'><?php echo number_format((float)$per, 1, '.', ''); ?>%<div id='bottom'></div></span>
		<span>of the time.</span>
	</div>

	<div class="details">
		<table>
			<tr>
				<td class="labels">similarity percentage:</td>
				<td class="values"><?php echo $details['comparison']['percentage']; ?></td>
			</tr>
			<tr>
				<td class="labels"># of votes compared:</td>
				<td class="values"><?php echo $details['comparison']['total']; ?></td>
			</tr>
			<tr>
				<td class="labels">votes in agreement:</td>
				<td class="values"><?php echo $details['comparison']['same']; ?></td>
			</tr>
			<tr>
				<td class="labels">votes in disagreement:</td>
				<td class="values"><?php echo $details['comparison']['different']; ?></td>
			</tr>
			<tr>
				<td class="labels">date of oldest vote:</td>
				<td class="values"><a target='_BLANK' href='<?php echo $details['oldest_vote']['link']; ?>'><?php echo date('F j, Y', strtotime($details['oldest_vote']['date'])); ?></a></td>
			</tr>
			<tr>
				<td class="labels">date of most recent vote:</td>
				<td class="values"><a target='_BLANK' href='<?php echo $details['newest_vote']['link']; ?>'><?php echo date('F j, Y', strtotime($details['newest_vote']['date'])); ?></a></td>
			</tr>
			<tr>
				<td class="labels">congresses compared:</td>
				<td class="values">
					<?php echo $details['congresses_compared'][0]."<sup>th</sup>".(count($details['congresses_compared']) > 1 ? "-".end($details['congresses_compared'])."<sup>th</sup>" : ""); ?>
				</td>
			</tr>
			<tr>
				<td class="labels">execution time:</td>
				<td class="values"><?php echo round((float)$sen->timing(), 5); ?> seconds</td>
			</tr>
		</table>
	</div>
</div>

<?php

}

?>
<script type="text/javascript" src="./script.js"></script>
</body>
</html>