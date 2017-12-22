<!DOCTYPE html>
<html>
<head>
	<title>House Compare</title>
	<link rel="stylesheet" type="text/css" href="./styles.css">
	<script src="./jquery.min.js"></script>

</head>
<body>

<?php

include './house-compare-class.php';

$house = new house_compare("localhost", "root", "", "congress");

$active = FALSE;

$rep_a = NULL;
$rep_b = NULL;

$select_a = "<label class='dropdown'><select class='select_a' name='a'>";
$select_b = "<label class='dropdown'><select class='select_b' name='b'>";

if(isset($_GET['a']) AND isset($_GET['b'])) {

	$a = array(substr($_GET['a'], 1), ord(substr($_GET['a'], 0, 1)));
	$b = array(substr($_GET['b'], 1), ord(substr($_GET['b'], 0, 1)));

	$valid = $house->valid_id($a, $b);

	if($valid == TRUE AND is_bool($valid) == TRUE) {
		if($_GET['a'] == $_GET['b']) {
			$rep_a = $_GET['a'];
			$rep_1 = array(substr($rep_a, 1), ord(substr($rep_a, 0, 1)));
			$rep_info = $house->rep_info($rep_1, $rep_1);
			$select_a = "<label class='dropdown'><select class='select_a' id='".substr($rep_info[0][2], 0, 1)."' name='a'>";
		} else {
			$active = TRUE;

			$rep_a = $_GET['a'];
			$rep_b = $_GET['b'];

			$rep_1 = array(substr($rep_a, 1), ord(substr($rep_a, 0, 1)));
			$rep_2 = array(substr($rep_b, 1), ord(substr($rep_b, 0, 1)));

			$per = $house->compare($rep_1, $rep_2);
			$rep_info = $house->rep_info($rep_1, $rep_2);

			$select_a = "<label class='dropdown'><select class='select_a' id='".substr($rep_info[0][2], 0, 1)."' name='a'>";
			$select_b = "<label class='dropdown'><select class='select_b' id='".substr($rep_info[1][2], 0, 1)."' name='b'>";

			$details = $house->get_details();
		}
	} elseif(is_string($valid)) {
		$rep_a = ($valid == 'a' ? $_GET['a'] : $_GET['b']);
		$info_a = array(substr($rep_a, 1), ord(substr($rep_a, 0, 1)));
		$rep_info = $house->rep_info($info_a, $info_a);
		$select_a = "<label class='dropdown'><select class='select_a' id='".substr($rep_info[0][2], 0, 1)."' name='a'>";
	}

} elseif((isset($_GET['a']) == TRUE AND isset($_GET['b']) == FALSE) OR (isset($_GET['a']) == FALSE AND isset($_GET['b']) == TRUE)) {
	$rep_a = (isset($_GET['a']) ? $_GET['a'] : $_GET['b']);
	$rep_1 = array(substr($rep_a, 1), ord(substr($rep_a, 0, 1)));
	$valid = $house->valid_id($rep_1, $rep_1);
	if($valid) {
		$rep_info = $house->rep_info($rep_1, $rep_1);
		$select_a = "<label class='dropdown'><select class='select_a' id='".substr($rep_info[0][2], 0, 1)."' name='a'>";
	} else {
		$rep_a = NULL;
	}
}

$namelist = $house->name_list($rep_a, $rep_b);

?>

	<header>
		<h1>House Compare</h1>
		<h3>Select two U.S. representives to compare their voting records and determine the level of similarity in their voting behavior.</h3>
<form id='compare' class='main-form' action='' method='get'>
				<label class='dropdown'><select class='sel_state_a'>
					<optgroup>
						<option selected disabled style='display: none' value=''>State</option>
							<option value="all">All</option>
							<option value="AL">AL</option>
							<option value="AK">AK</option>
							<option value="AZ">AZ</option>
							<option value="AR">AR</option>
							<option value="CA">CA</option>
							<option value="CO">CO</option>
							<option value="CT">CT</option>
							<option value="DE">DE</option>
							<option value="FL">FL</option>
							<option value="GA">GA</option>
							<option value="HI">HI</option>
							<option value="ID">ID</option>
							<option value="IL">IL</option>
							<option value="IN">IN</option>
							<option value="IA">IA</option>
							<option value="KS">KS</option>
							<option value="KY">KY</option>
							<option value="LA">LA</option>
							<option value="ME">ME</option>
							<option value="MD">MD</option>
							<option value="MA">MA</option>
							<option value="MI">MI</option>
							<option value="MN">MN</option>
							<option value="MS">MS</option>
							<option value="MO">MO</option>
							<option value="MT">MT</option>
							<option value="NE">NE</option>
							<option value="NV">NV</option>
							<option value="NH">NH</option>
							<option value="NJ">NJ</option>
							<option value="NM">NM</option>
							<option value="NY">NY</option>
							<option value="NC">NC</option>
							<option value="ND">ND</option>
							<option value="OH">OH</option>
							<option value="OK">OK</option>
							<option value="OR">OR</option>
							<option value="PA">PA</option>
							<option value="RI">RI</option>
							<option value="SC">SC</option>
							<option value="SD">SD</option>
							<option value="TN">TN</option>
							<option value="TX">TX</option>
							<option value="UT">UT</option>
							<option value="VT">VT</option>
							<option value="VA">VA</option>
							<option value="WA">WA</option>
							<option value="WV">WV</option>
							<option value="WI">WI</option>
							<option value="WY">WY</option>
					</optgroup>
				</select></label>
				<?php echo $select_a; ?>
					<optgroup id="og_a">
					<option selected disabled hidden style='display: none' data-state='default' value=''>Select First Rep</option>
					<?php echo $namelist["a"]; ?>
					</optgroup>
				</select></label>
				<label class='dropdown'><select class='sel_state_b'>
					<optgroup>
						<option selected disabled style='display: none' value=''>State</option>
							<option value="all">All</option>
							<option value="AL">AL</option>
							<option value="AK">AK</option>
							<option value="AZ">AZ</option>
							<option value="AR">AR</option>
							<option value="CA">CA</option>
							<option value="CO">CO</option>
							<option value="CT">CT</option>
							<option value="DE">DE</option>
							<option value="FL">FL</option>
							<option value="GA">GA</option>
							<option value="HI">HI</option>
							<option value="ID">ID</option>
							<option value="IL">IL</option>
							<option value="IN">IN</option>
							<option value="IA">IA</option>
							<option value="KS">KS</option>
							<option value="KY">KY</option>
							<option value="LA">LA</option>
							<option value="ME">ME</option>
							<option value="MD">MD</option>
							<option value="MA">MA</option>
							<option value="MI">MI</option>
							<option value="MN">MN</option>
							<option value="MS">MS</option>
							<option value="MO">MO</option>
							<option value="MT">MT</option>
							<option value="NE">NE</option>
							<option value="NV">NV</option>
							<option value="NH">NH</option>
							<option value="NJ">NJ</option>
							<option value="NM">NM</option>
							<option value="NY">NY</option>
							<option value="NC">NC</option>
							<option value="ND">ND</option>
							<option value="OH">OH</option>
							<option value="OK">OK</option>
							<option value="OR">OR</option>
							<option value="PA">PA</option>
							<option value="RI">RI</option>
							<option value="SC">SC</option>
							<option value="SD">SD</option>
							<option value="TN">TN</option>
							<option value="TX">TX</option>
							<option value="UT">UT</option>
							<option value="VT">VT</option>
							<option value="VA">VA</option>
							<option value="WA">WA</option>
							<option value="WV">WV</option>
							<option value="WI">WI</option>
							<option value="WY">WY</option>
					</optgroup>
				</select></label>
				<?php echo $select_b; ?>
					<optgroup id="og_b">
					<option selected disabled hidden style='display: none' data-state='default' value=''>Select Second Rep</option>
					<?php echo $namelist["b"]; ?>
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
		<h2 style='border-left-color: <?php echo $rep_info[0][4]; ?>;' id='sen_name'><?php echo $rep_info[0][0]; ?></h2>
		<span id='sen_info'><?php echo $rep_info[0][2]." - ".$rep_info[0][1]; ?></span>
		<div class="sen-a-img">
			<img src="<?php echo $rep_info[0][3]; ?>"/>
		</div>
	</div>

	<div class="sen-b">
		<h2 style='border-right-color: <?php echo $rep_info[1][4]; ?>;' id='sen_name'><?php echo $rep_info[1][0]; ?></h2>
		<span id='sen_info'><?php echo $rep_info[1][2]." - ".$rep_info[1][1]; ?></span>
		<div class="sen-b-img">
			<img src="<?php echo $rep_info[1][3]; ?>"/>
		</div>
	</div>

	<div class="percentage">
		<span style="line-height: 22px;">When voting on issues, the two representives "agree"</span>
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
				<td class="values"><?php echo round((float)$house->timing(), 5); ?> seconds</td>
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