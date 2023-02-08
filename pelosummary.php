<?php
header('Content-Type: application/json; charset=utf-8');

$opts = array(
  'http'=>array(
    'method'=>"POST",
	'header'  => 'Content-type: application/json',
    'content'=> "{\"username_or_email\":\"REMOVED\", \"password\":\"REMOVED\"}"
  )
);

$context = stream_context_create($opts);
$result = file_get_contents('https://api.onepeloton.com/auth/login', false, $context);
$jsonified = json_decode($result, true);
$userId = $jsonified['user_id'];
$cookies = array();
foreach ($http_response_header as $hdr) {
    if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
        parse_str($matches[1], $tmp);
        $cookies += $tmp;
    }
}
$peloCookie = $cookies['peloton_session_id'];

// cURL seems to work more reliably for the second request.
// (specifically, we can manipulate the $data response without PHP freaking out)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.onepeloton.com/api/user/' . $userId . '/workout_history_csv');
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie:peloton_session_id=" . $peloCookie));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
curl_close($ch);
$rows = array_map('str_getcsv', explode(PHP_EOL, rtrim($data)));
$header = array_shift($rows);
$workoutData = array();
$workoutData["currentRide"] = array();
$workoutData["latestRide"] = array();
$workoutData["workouts"] = array();
$workoutData["byInstructor"] = array();
$workoutData["byInstructorAndDiscipline"] = array();
$workoutData["byTimeAndDiscipline"] = array();
$workoutData["PBs"] = array();
$workoutData["distanceCycled"] = array();
$records = array();
$progressions = array();

foreach($rows as $row) {
	$year = substr($row[0],0,4);
	$thisWorkout = createWorkoutRow($row);
	$discipline = $row[4];
	$instructor = $row[2];
	$workoutData["workouts"]["Total"]["Total"] = ($workoutData["workouts"]["Total"]["Total"] ?? 0) + 1;
	$workoutData["workouts"]["Total"][$discipline] = ($workoutData["workouts"]["Total"][$discipline] ?? 0) + 1;
	$workoutData["workouts"][$year]["Total"] = ($workoutData["workouts"][$year]["Total"] ?? 0) + 1;
	$workoutData["workouts"][$year][$discipline] = ($workoutData["workouts"][$year][$discipline] ?? 0) + 1;
	if ($instructor != "") {
		$workoutData["byInstructor"]["Total"][$instructor] = ($workoutData["byInstructor"]["Total"][$instructor] ?? 0) + 1;
		$workoutData["byInstructor"][$year][$instructor] = ($workoutData["byInstructor"][$year][$instructor] ?? 0) + 1;
		$workoutData["byInstructorAndDiscipline"]["Total"][$discipline][$instructor] = ($workoutData["byInstructorAndDiscipline"]["Total"][$discipline][$instructor] ?? 0) + 1;
		$workoutData["byInstructorAndDiscipline"][$year][$discipline][$instructor] = ($workoutData["byInstructorAndDiscipline"][$year][$discipline][$instructor] ?? 0) + 1;
		$workoutData["byTimeAndDiscipline"]["Total"]["Total"] = ($workoutData["byTimeAndDiscipline"]["Total"]["Total"] ?? 0) + (int)$row[3];
		$workoutData["byTimeAndDiscipline"][$year]["Total"] = ($workoutData["byTimeAndDiscipline"][$year]["Total"] ?? 0) + (int)$row[3];
		$workoutData["byTimeAndDiscipline"]["Total"][$discipline] = ($workoutData["byTimeAndDiscipline"]["Total"][$discipline] ?? 0) + (int)$row[3];
		$workoutData["byTimeAndDiscipline"][$year][$discipline] = ($workoutData["byTimeAndDiscipline"][$year][$discipline] ?? 0) + (int)$row[3];
	}

	if($discipline == "Cycling") {
		if(isRealRide($row)) {
			if(!isRideInProgress($row)) {
				$workoutData["latestRide"] = $thisWorkout; // This will be correct during the final loop.	
			} else {
				$workoutData["currentRide"] = $thisWorkout;
			}
		}
		$workoutData["distanceCycled"]["Total"] = ($workoutData["distanceCycled"]["Total"] ?? 0) + $row[13];
		$workoutData["distanceCycled"][$year] = ($workoutData["distanceCycled"][$year] ?? 0) + $row[13];
	}

	// PB check
	if(array_key_exists($row[3],$records) && $records[$row[3]]!= null) {
		if(intval($row[8]) > intval($records[$row[3]]["Total Output"])) {
			$records[$row[3]] = $thisWorkout;
			array_push($progressions[$row[3]], $thisWorkout);
		}
	} else {
		$records[$row[3]] = $thisWorkout;
		$progressions[$row[3]] = [$thisWorkout];
	}
}

foreach($workoutData["workouts"] as $year => $data) {
	array_multisort(array_values($workoutData["workouts"][$year]), SORT_DESC, array_keys($workoutData["workouts"][$year]), SORT_ASC, $workoutData["workouts"][$year]);
}

foreach($workoutData["byInstructor"] as $year => $data) {
	array_multisort(array_values($workoutData["byInstructor"][$year]), SORT_DESC, array_keys($workoutData["byInstructor"][$year]), SORT_ASC, $workoutData["byInstructor"][$year]);
}

foreach($workoutData["byInstructorAndDiscipline"] as $year => $data) {
	foreach($workoutData["byInstructorAndDiscipline"][$year] as $discipline => $disciplineCounts) {
		array_multisort(array_values($workoutData["byInstructorAndDiscipline"][$year][$discipline]), SORT_DESC, array_keys($workoutData["byInstructorAndDiscipline"][$year][$discipline]), SORT_ASC, $workoutData["byInstructorAndDiscipline"][$year][$discipline]);
	}
}

foreach($workoutData["byTimeAndDiscipline"] as $year => $data) {
	array_multisort(array_values($workoutData["byTimeAndDiscipline"][$year]), SORT_DESC, array_keys($workoutData["byTimeAndDiscipline"][$year]), SORT_ASC, $workoutData["byTimeAndDiscipline"][$year]);
}

foreach($workoutData["distanceCycled"] as $year => $distance) {
	$workoutData["distanceCycled"][$year] = number_format((float)$distance, 2, '.', '');
}

foreach($progressions as $distance => $data) {
	$records[$distance]["progressions"] = $data;
}

$workoutData["PBs"] = ["5" => $records["5"], "10" => $records["10"], "15" => $records["15"], "20" => $records["20"], "30" => $records["30"], "45" => $records["45"], "60" => $records["60"], "75" => $records["75"], "90" => $records["90"]];
															
echo json_encode($workoutData, JSON_PRETTY_PRINT);	

function createWorkoutRow($row) {
	$timestamp = $row[0];
	$wasLive = ($row[1] == "Live");
	$instructor = $row[2] ? $row[2] : "N/A";
	$rideName = $row[6];
	$rideLength = $row[3];
	$totalOutput = $row[8];
	return [ "Timestamp" => $timestamp, "Instructor" => $instructor, "Ride Name" => $rideName, "Total Output" => $totalOutput, "Length" => $rideLength, "Live Ride" => $wasLive];
}

function isRealRide($row) {
	return !strpos(strtolower($row[6]),"cool down") && !strpos(strtolower($row[6]),"warm");
}

function isRideInProgress($row) {
	return $row[8] == "";
}
?>