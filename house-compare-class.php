<?php



class house_compare
{
	
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
		$query = "SELECT COUNT(*) FROM `representatives` WHERE `bioguide_id` = ? AND `bioguide_prefix` = ?";
		$res_a = $this->mysql->prepare($query);
		$res_b = $this->mysql->prepare($query);
		if($res_a->execute($a) AND $res_b->execute($b)) {
			$out['a'] = $res_a->fetchColumn();
			$out['b'] = $res_b->fetchColumn();
		}
		if($out['a'] == 1 AND $out['b'] == 1) {
			//both $a and $b are valid
			return TRUE;
		} elseif($out['a'] == 1 AND $out['b'] == 0) {
			//only $a is valid
			return 'a';
		} elseif($out['a'] == 0 AND $out['b'] == 1) {
			//only $b is valid
			return 'b';
		} elseif($out['a'] == 0 AND $out['b'] == 0) {
			//neither $a nor $b is valid
			return FALSE;
		}
	}

	//determines the earliest congress that both reps were memebers of,
	//which prevents the program from looking for votes from past congresses
	//where it is impossible for there to be any votes participated in by both senators.
	//The two input arrays must be structured as follows:
	//    array('[bioguide_id]', '[bioguide_prefix]')
	//The bioguide_id should be 6-digits including leading zeros; the prefix is a
	//2-digit value of corresponding to the ASCII value (A-Z); both should be inputed
	//as strings, which preserves the leading zeros
	function earliest($rep_a, $rep_b) {
		$query = "SELECT `took_office` FROM `representatives` WHERE (`bioguide_id` = ? AND `bioguide_prefix` = ?) OR (`bioguide_id` = ? AND `bioguide_prefix` = ?)";
		$stmt = $this->mysql->prepare($query);
		$reps = array_merge($rep_a, $rep_b);
		if ($stmt->execute($reps)) {
			$congress = 111;
			while($row = $stmt->fetch()) {
		  		if(intval($row['took_office']) > $congress) {
		  			$congress = intval($row['took_office']);
		  		}
		  	}
		}
		return $congress;
	}

	//strips out unwanted votes where at least one of the 
	//reps inputted did not participate in the vote
	function unwanted($values) {
		$output = $values;
		foreach ($output as $congress_key => $congress) {
			foreach ($congress as $session_key => $session) {
				foreach ($session as $vote_key => $vote) {
					if(count($vote) != 2) {
						unset($output[$congress_key][$session_key][$vote_key]);
					}
				}
				if(count($output[$congress_key][$session_key]) == 0) { unset($output[$congress_key][$session_key]); }
			}
			if(count($output[$congress_key]) == 0) { unset($output[$congress_key]); }
		}
		return $output;
	}

	//retrieves vote data and values for the two senators given in the input
	function values($rep_a, $rep_b) {
		$earliest = $this->earliest($rep_a, $rep_b);
		$query = "(SELECT `congress`, `session`, `vote_id`, `bioguide_id`, `bioguide_prefix`, `value` FROM `house_vote_values` WHERE `congress` >= ? AND `bioguide_id` = ? AND `bioguide_prefix` = ?) UNION (SELECT `congress`, `session`, `vote_id`, `bioguide_id`, `bioguide_prefix`, `value` FROM `house_vote_values` WHERE `congress` >= ? AND `bioguide_id` = ? AND `bioguide_prefix` = ?)";
		$stmt = $this->mysql->prepare($query);
		$values = array();
		if ($stmt->execute(array($earliest, $rep_a[0], $rep_a[1], $earliest, $rep_b[0], $rep_b[1]))) {
			$votes = array();
		  	while($row = $stmt->fetch()) {
		  		$id = chr($row['bioguide_prefix']).$row['bioguide_id'];
		  		$values[$row['congress']][$row['session']][$row['vote_id']][$id] = (int)$row['value'];
		  	}
		  	$values = $this->unwanted($values);
		  	//echo $this->timing(TRUE);
		  	$this->oldest_vote = array(min(array_keys($values)), min(array_keys($values[min(array_keys($values))])), min(array_keys($values[min(array_keys($values))][min(array_keys($values[min(array_keys($values))]))])));
		  	$this->newest_vote = array(max(array_keys($values)), max(array_keys($values[max(array_keys($values))])), max(array_keys($values[max(array_keys($values))][max(array_keys($values[max(array_keys($values))]))])));
		  	$this->congresses_compared = array_keys($values);
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
	function compare($rep_a, $rep_b) {
		$cmp_data = $this->values($rep_a, $rep_b);
		$total = 0;
		$same = 0;
		$diff = 0;
		foreach ($cmp_data as $congress_key => $congress) {
			foreach ($congress as $session_key => $session) {
				foreach ($session as $vote_key => $vote) {
					$vals = array_values($vote);
					//var_dump($vals);
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
	function rep_info($rep_a, $rep_b) {
		include './states.php';
		$colors = array('D' => '#6193C7', 'R' => '#CF635D', 'I' => '#91B66E');
		$query = "(SELECT `bioguide_id`, `last_name`, `first_name`, `state`, `party`, `govtrack_id` FROM `representatives` WHERE `bioguide_id` = ? AND `bioguide_prefix` = ?) UNION (SELECT `bioguide_id`, `last_name`, `first_name`, `state`, `party`, `govtrack_id` FROM `representatives` WHERE `bioguide_id` = ? AND `bioguide_prefix` = ?)";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute(array($rep_a[0], $rep_a[1], $rep_b[0], $rep_b[1]))) {
			$out = array();
			while($row = $stmt->fetch()) {
		  		$name = $row['first_name']." ".$row['last_name'];
		  		$state = $states[$row['state']];
		  		$party = $this->parties[$row['party']];

		  		$img = "./images/house/".$row['govtrack_id'].".jpeg";
		  		$color = $colors[$row['party']];
		  		$out[] = array($name, $state, $party, $img, $color);
		  	}
		}
		return $out;
	}

	//generates list of senators and outputs them in html <option> elements
	//for the <select> elements in index.php for user input
	function name_list($val_a=NULL, $val_b=NULL) {
		$query = "SELECT `bioguide_id`, `bioguide_prefix`, `last_name`, `first_name`, `state`, `district`, `party` FROM `representatives` ORDER BY `last_name`";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute()) {
			$html = array("a" => NULL, "b" => NULL);
			while($row = $stmt->fetch()) {
				$name = $row['last_name'].", ".$row['first_name']." (".$row['state']."-".$row['district'].")";
				if($val_a == chr($row['bioguide_prefix']).$row['bioguide_id']) {
					$html["a"] = $html["a"].'<option selected="selected" id="'.$row['party'].'" data-state="'.$row['state'].'" value="'.chr($row['bioguide_prefix']).$row['bioguide_id'].'">'.$name.'</option>\n\t\t\t';
					$html["b"] = $html["b"].'<option id="'.$row['party'].'" data-state="'.$row['state'].'" value="'.chr($row['bioguide_prefix']).$row['bioguide_id'].'">'.$name.'</option>\n\t\t\t';
				} elseif($val_b == chr($row['bioguide_prefix']).$row['bioguide_id']) {
					$html["a"] = $html["a"].'<option id="'.$row['party'].'" data-state="'.$row['state'].'" value="'.chr($row['bioguide_prefix']).$row['bioguide_id'].'">'.$name.'</option>\n\t\t\t';
					$html["b"] = $html["b"].'<option selected="selected" data-state="'.$row['state'].'" id="'.$row['party'].'" value="'.chr($row['bioguide_prefix']).$row['bioguide_id'].'">'.$name.'</option>\n\t\t\t';
				} else {
					$html["a"] = $html["a"]."<option id='".$row['party']."' data-state='".$row['state']."' value='".chr($row['bioguide_prefix']).$row['bioguide_id']."'>".$name."</option>\n\t\t\t";
					$html["b"] = $html["b"]."<option id='".$row['party']."' data-state='".$row['state']."' value='".chr($row['bioguide_prefix']).$row['bioguide_id']."'>".$name."</option>\n\t\t\t";
				}
		  	}
		}
		return $html;
	}

	function vote_date($vote) {
		$query = "SELECT `date` FROM `house_votes` WHERE `congress` = ? AND `session` = ? AND `vote_id` = ?";
		$stmt = $this->mysql->prepare($query);
		if ($stmt->execute($vote)) {
			$vote_date = $stmt->fetchColumn();
		}
		return $vote_date;
	}

	//outputs details of comparison
	function get_details() {
		$oldest = substr($this->vote_date($this->oldest_vote), 0, 10);
		$oldest_link = 'http://clerk.house.gov/evs/'.$this->oldest_vote[1].'/roll'.sprintf('%03d', $this->oldest_vote[2]).'.xml';
		$newest = substr($this->vote_date($this->newest_vote), 0, 10);
		$newest_link = 'http://clerk.house.gov/evs/'.$this->newest_vote[1].'/roll'.sprintf('%03d', $this->newest_vote[2]).'.xml';
		$out = array('comparison' => array('percentage' => $this->percentage, 'total' => $this->total_compared, 'same' => $this->same_votes, 'different' => $this->diff_votes), 'oldest_vote' => array('date' => $oldest, 'link' => $oldest_link, 'vote_id' => array('congress' => $this->oldest_vote[0], 'session' => $this->oldest_vote[1], 'vote_id' => $this->oldest_vote[2])), 'newest_vote' => array('date' => $newest, 'link' => $newest_link, 'vote_id' => array('congress' => $this->newest_vote[0], 'session' => $this->newest_vote[1], 'vote_id' => $this->newest_vote[2])), 'congresses_compared' => $this->congresses_compared);
		return $out;
	}

}






?>