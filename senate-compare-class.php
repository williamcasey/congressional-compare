<?php

/**
* senate-compare-class.php
*
* main program that contains class that calculates similarity percentages
*
*/

class senate_compare {

	//create variable for PDO MySQL connection
	var $mysql = NULL;

	//define full spelling for party abbreviations
	var $parties = array('D' => 'Democrat', 'R' => 'Republican', 'I' => 'Independent');

	var $total_compared = 0;
	var $same_votes = 0;
	var $diff_votes = 0;
	var $percentage = 0;
	var $oldest_vote = NULL;
	var $newest_vote = NULL;
	var $congresses_compared = NULL;
	
	//on initialization connect to MySQL database using PDO
	function __construct($host, $user, $pass, $db, $charset="utf8mb4") {
		if(!$this->mysql = new PDO('mysql:host='.$host.';dbname='.$db.';charset='.$charset, $user, $pass)) {
			echo "Could not to the connect to the MySQL database";
			return FALSE;
		}
		//set profiling on the mysql db to time queries
		$this->mysql->query('SET profiling = 1');
		return TRUE;
	}

	//function for timing MySQL queries I wrote for debugging
	function timing($total = FALSE) {
		$show = $this->mysql->query('show profiles');
		$result = $show->fetchAll(PDO::FETCH_ASSOC);
		if(!$total) {
			$last = array_values(array_slice($result, -1))[0];
			return $last['Duration'];
		} else {
			$overall = 0;
			foreach ($result as $q) {
				$overall = $overall + $q['Duration'];
			}
			return count($result)." queries executed in ".$overall." seconds.</br>";
		}
	}

	//checks if inputted voter_ids exist in database
	function valid_id($a, $b) {
		$query = "SELECT COUNT(*) FROM `senators` WHERE `voter_id` = ?";
		$res_a = $this->mysql->prepare($query);
		$res_b = $this->mysql->prepare($query);
		if($res_a->execute(array($a)) AND $res_b->execute(array($b))) {
			$out['a'] = $res_a->fetchColumn();
			$out['b'] = $res_b->fetchColumn();
		}
		if($out['a'] == 1 AND $out['b'] == 1) {
			return TRUE;
		} elseif($out['a'] == 1 AND $out['b'] == 0) {
			return 'a';
		} elseif($out['a'] == 0 AND $out['b'] == 1) {
			return 'b';
		} elseif($out['a'] == 0 AND $out['b'] == 0) {
			return FALSE;
		}
	}

	//determines that most recent congress that senators were memebers of,
	//which prevents the program from looking for votes from past congresses
	//where it is impossible for there to be any participated in by both senators
	function earliest($sen_a, $sen_b) {
		$query = "SELECT `took_office` FROM `senators` WHERE `voter_id` = ? OR `voter_id` = ?";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute(array($sen_a, $sen_b))) {
			$congress = 111;
			while($row = $stmt->fetch()) {
		  		if(intval($row['took_office']) > $congress) {
		  			$congress = intval($row['took_office']);
		  		}
		  	}
		}
		return $congress;
	}

	//strips out unwanted votes where at least one of the senators inputted 
	function unwanted($values) {
		$output = $values;
		foreach ($output as $congress_key => $congress) {
			foreach ($congress as $session_key => $session) {
				foreach ($session as $vote_key => $vote) {
					if(count($vote) != 2) {
						unset($output[$congress_key][$session_key][$vote_key]);
					}
				}
			}
		}
		return $output;
	}

	//retrieves vote data and values for the two senators given in the input
	function values($sen_a, $sen_b) {
		$earliest = $this->earliest($sen_a, $sen_b);
		$query = "(SELECT `congress`, `session`, `vote_id`, `senator`, `value` FROM `senate_vote_values` WHERE `congress` >= ? AND `senator` = ?) UNION (SELECT `congress`, `session`, `vote_id`, `senator`, `value` FROM `senate_vote_values` WHERE `congress` >= ? AND `senator` = ?)";
		$stmt = $this->mysql->prepare($query);
		$values = array();
		if ($stmt->execute(array($earliest, $sen_a, $earliest, $sen_b))) {
			$votes = array();
		  	while($row = $stmt->fetch()) {
		  		$values[$row['congress']][$row['session']][$row['vote_id']][$row['senator']] = $row['value'];
		  	}
		  	$values = $this->unwanted($values);
		  	$this->oldest_vote = array(min(array_keys($values)), min(array_keys($values[min(array_keys($values))])), min(array_keys($values[min(array_keys($values))][min(array_keys($values[min(array_keys($values))]))])));
		  	$this->newest_vote = array(max(array_keys($values)), max(array_keys($values[max(array_keys($values))])), max(array_keys($values[max(array_keys($values))][max(array_keys($values[max(array_keys($values))]))])));
		  	$this->congresses_compared = array_reverse(array_keys($values));
			return $values;
		} 
	}

	//determines if vote values are valid (either a zero or a one)
	function are_votes($vals) {
		foreach ($vals as $val) {
			if($val != 0 AND $val != 1) {
				return FALSE;
			}
		}
		return TRUE;
	}

	//function that calculates final similarity percentage 
	function compare($sen_a, $sen_b) {
		$cmp_data = $this->values($sen_a, $sen_b);
		$total = 0;
		$same = 0;
		$diff = 0;
		foreach ($cmp_data as $congress_key => $congress) {
			foreach ($congress as $session_key => $session) {
				foreach ($session as $vote_key => $vote) {
					$vals = array_values($vote);
					if($this->are_votes($vals)) {
						if($vals[0] == $vals[1]) {
							$same = $same + 1;
						} else {
							$diff = $diff + 1;
						}
						$total = $total + 1;
					}
				}
			}
		}
		$percentage = $same / $total;
		$out = round((float)$percentage, 5) * 100;
		$this->total_compared = $total;
		$this->same_votes = $same;
		$this->diff_votes = $diff;
		$this->percentage = round((float)$percentage, 7);
		return $out;
	}

	//gets basic information on senators for display on the main page (index.php)
	function sen_info($sen_a, $sen_b) {
		include './states.php';
		$colors = array('D' => '#6193C7', 'R' => '#CF635D', 'I' => '#7EAD53');
		$query = "(SELECT `last_name`, `first_name`, `state`, `party`, `govtrack_id` FROM `senators` WHERE `voter_id` = ?) UNION (SELECT `last_name`, `first_name`, `state`, `party`, `govtrack_id` FROM `senators` WHERE `voter_id` = ?)";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute(array($sen_a, $sen_b))) {
			$out = array();
			while($row = $stmt->fetch()) {
		  		$name = $row['first_name']." ".$row['last_name'];
		  		$state = $states[$row['state']];
		  		$party = $this->parties[$row['party']];
		  		$img = "./images/senate/".$row['govtrack_id'].".jpeg";
		  		$color = $colors[$row['party']];
		  		$out[] = array($name, $state, $party, $img, $color);
		  	}
		}
		return $out;
	}

	//generates list of senators and outputs them in html <option> elements
	//for the <select> elements in index.php for user input
	function name_list($val_a = NULL, $val_b = NULL) {
		$query = "SELECT `voter_id`, `last_name`, `first_name`, `state`, `party` FROM `senators`";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute()) {
			$html = array("a" => "", "b" => "");
			while($row = $stmt->fetch()) {
				$name = $row['last_name'].", ".$row['first_name']." - ".$row['party']." - ".$row['state'];
				if($val_a == $row['voter_id']) {
					$html["a"] = $html["a"]."<option selected='selected' id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
					$html["b"] = $html["b"]."<option id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
				} elseif($val_b == $row['voter_id']) {
					$html["a"] = $html["a"]."<option id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
					$html["b"] = $html["b"]."<option selected='selected' id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
				} else {
					$html["a"] = $html["a"]."<option id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
					$html["b"] = $html["b"]."<option id='".$row['party']."' value='".$row['voter_id']."'>".$name."</option>\n\t\t\t";
				}
		  	}
		}
		return $html;
	}

	function vote_date($vote) {
		$query = "SELECT `date` FROM `senate_votes` WHERE `congress` = ? AND `session` = ? AND `vote_id` = ?";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute($vote)) {
			$vote_date = $stmt->fetchColumn();
		}
		return $vote_date;
	}

	//outputs details of comparison
	function get_details() {
		$oldest = substr($this->vote_date($this->oldest_vote), 0, 10);
		$oldest_link = 'https://www.senate.gov/legislative/LIS/roll_call_lists/roll_call_vote_cfm.cfm?congress='.$this->oldest_vote[0].'&session='.($this->oldest_vote[1] % 2 == 0 ? 2 : 1).'&vote='.sprintf('%05d', $this->oldest_vote[2]);
		$newest = substr($this->vote_date($this->newest_vote), 0, 10);
		$newest_link = 'https://www.senate.gov/legislative/LIS/roll_call_lists/roll_call_vote_cfm.cfm?congress='.$this->newest_vote[0].'&session='.($this->newest_vote[1] % 2 == 0 ? 2 : 1).'&vote='.sprintf('%05d', $this->newest_vote[2]);
		$out = array('comparison' => array('percentage' => $this->percentage, 'total' => $this->total_compared, 'same' => $this->same_votes, 'different' => $this->diff_votes), 'oldest_vote' => array('date' => $oldest, 'link' => $oldest_link, 'vote_id' => array('congress' => $this->oldest_vote[0], 'session' => $this->oldest_vote[1], 'vote_id' => $this->oldest_vote[2])), 'newest_vote' => array('date' => $newest, 'link' => $newest_link, 'vote_id' => array('congress' => $this->newest_vote[0], 'session' => $this->newest_vote[1], 'vote_id' => $this->newest_vote[2])), 'congresses_compared' => $this->congresses_compared);
		return $out;
	}

}



?>