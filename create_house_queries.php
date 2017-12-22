<?php

$congresses = array(1947 => 80, 1948 => 80, 1949 => 81, 1950 => 81, 1951 => 82, 1952 => 82, 1953 => 83, 1954 => 83, 1955 => 84, 1956 => 84, 1957 => 85, 1958 => 85, 1959 => 86, 1960 => 86, 1961 => 87, 1962 => 87, 1963 => 88, 1964 => 88, 1965 => 89, 1966 => 89, 1967 => 90, 1968 => 90, 1969 => 91, 1970 => 91, 1971 => 92, 1972 => 92, 1973 => 93, 1974 => 93, 1975 => 94, 1976 => 94, 1977 => 95, 1978 => 95, 1979 => 96, 1980 => 96, 1981 => 97, 1982 => 97, 1983 => 98, 1984 => 98, 1985 => 99, 1986 => 99, 1987 => 100, 1988 => 100, 1989 => 101, 1990 => 101, 1991 => 102, 1992 => 102, 1993 => 103, 1994 => 103, 1995 => 104, 1996 => 104, 1997 => 105, 1998 => 105, 1999 => 106, 2000 => 106, 2001 => 107, 2002 => 107, 2003 => 108, 2004 => 108, 2005 => 109, 2006 => 109, 2007 => 110, 2008 => 110, 2009 => 111, 2010 => 111, 2011 => 112, 2012 => 112, 2013 => 113, 2014 => 113, 2015 => 114, 2016 => 114, 2017 => 115);

function bioguide($id) {
	if(strlen($id) == 7) {
		$alpha = substr($id, 0, 1);
		$numeric = substr($id, 1);
	}
	return array($alpha, $numeric);
}


$json = file_get_contents("./legislators-current.json");


$data = json_decode($json, TRUE);

//echo "<b>bioguide | last_name | first_name | took_office | birthday | gender | state | district | party | govtrack_id</b><br>";





echo "INSERT INTO `representatives` (`bioguide_id`, `bioguide_prefix`, `last_name`, `first_name`, `took_office`, `birthday`, `gender`, `state`, `district`, `party`, `govtrack_id`) VALUES ";






$c = 0;
for ($i=0; $i < count($data); $i++) { 

 	$current = end($data[$i]['terms']);

 	if($current['type'] == "rep") {
		
		$bioguide = bioguide($data[$i]['id']['bioguide']);
		$last = $data[$i]['name']['last'];
		$first = $data[$i]['name']['first'];
		$took_office = intval(substr($data[$i]['terms'][0]['start'], 0, 4));
		$birthday = $data[$i]['bio']['birthday'];
		$gender = $data[$i]['bio']['gender'];
		$state = $current['state'];
		$district = $current['district'];
		$party = substr($current['party'], 0, 1);
		$govtrack_id = $data[$i]['id']['govtrack'];

		$cong = $congresses[$took_office];

		echo "('".$bioguide[1]."', '".$bioguide[0]."', '".$last."', '".$first."', '".$cong."', '".$birthday."', '".$gender."', '".$state."', '".$district."', '".$party."', '".$govtrack_id."'), <br>";

		$c = $c + 1;
	}
 }

echo "<br><b>".$c."</b>";






?>