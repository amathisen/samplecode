<?php

function getLatLonCoordinates($longIP) {

	if (strstr($longIP, ".")) $longIP = ipToLong($longIP);

	$lat = 0;
	$lon = 0;

	// check cache prior to running full geo query

	$q = $_ENV['appDB']->query("SELECT idsGeoCache.* FROM idsGeoCache WHERE ip = '" . $longIP . "'");
	while ($row = mysqli_fetch_assoc($q)) {
	
		$cacheLocationID = $row['locationID'];
	
	}
	
	if ($cacheLocationID) {

		$q = $_ENV['appDB']->query("SELECT idsGeoLocation.* FROM idsGeoLocation WHERE idsGeoLocation.locationID = '" . $cacheLocationID . "'");
		while ($row = mysqli_fetch_assoc($q)) {
		
			$lat = $row["latitude"];
			$lon = $row["longitude"];
			$city = $row["city"];
			$region = $row["region"];
			$country = $row["country"];
			
		}

	} else {
	
		$ipArray = explode(".", longToIP($longIP));
		$mod = 16777216 * $ipArray[0] + 65536 * $ipArray[1] + 256 * $ipArray[2] + $ipArray[3];
		
		$q = $_ENV['appDB']->query("SELECT idsGeoLocation.* FROM idsGeoData JOIN idsGeoLocation ON (idsGeoData.locationID = idsGeoLocation.locationID) WHERE $mod BETWEEN idsGeoData.ipStart AND idsGeoData.ipEnd");
		while ($row = mysqli_fetch_assoc($q)) {
		
			$lat = $row["latitude"];
			$lon = $row["longitude"];
			$city = $row["city"];
			$region = $row["region"];
			$country = $row["country"];

			// Insert into cache

			$_ENV['appDB']->query("INSERT INTO idsGeoCache (ip, locationID, timestamp) VALUES ('" . $longIP . "', '" . $row['locationID'] . "', " . time() . ")");
		
		}
		
	}
	
	return array($lat, $lon, $city, $region, $country);

}

function getGeoData($longIP) {

	if (strstr($longIP, ".")) $longIP = ipToLong($longIP);

	$lat = 0;
	$lon = 0;

	// check cache prior to running full geo query

	$q = $_ENV['appDB']->query("SELECT idsGeoCache.* FROM idsGeoCache WHERE ip = '" . $longIP . "'");
	while ($row = mysqli_fetch_assoc($q)) {
	
		$cacheLocationID = $row['locationID'];
	
	}
	
	if ($cacheLocationID) {

		$q = $_ENV['appDB']->query("SELECT idsGeoLocation.* FROM idsGeoLocation WHERE idsGeoLocation.locationID = '" . $cacheLocationID . "'");
		$result = mysqli_fetch_object($q);

	} else {
	
		$ipArray = explode(".", longToIP($longIP));
		$mod = 16777216 * $ipArray[0] + 65536 * $ipArray[1] + 256 * $ipArray[2] + $ipArray[3];
		
		$q = $_ENV['appDB']->query("SELECT idsGeoLocation.* FROM idsGeoData JOIN idsGeoLocation ON (idsGeoData.locationID = idsGeoLocation.locationID) WHERE $mod BETWEEN idsGeoData.ipStart AND idsGeoData.ipEnd");
		$result = mysqli_fetch_object($q);

		if (mysqli_num_rows($q)) {
		
			// Insert into cache
	
			$_ENV['appDB']->query("INSERT INTO idsGeoCache (ip, locationID, timestamp) VALUES ('" . $longIP . "', '" . $result->locationID . "', " . time() . ")");

		}

	}
	
	return $result;

}

// Pass in an array of IP addresses (long or short form). Returns formatted javascript to plot the points on a map
function buildJavascriptPlotsByIP($ips,$zoomLevel=5,$scale="0.5",$color="1C1C1C") {

	$ips = array_unique($ips);
	$dotsToPlot = array();

	if (!$ips)
		return;

	foreach($ips AS $thisIP) {

		$ipInfo = getLatLonCoordinates($thisIP);

		$dotTitle = array();

		if (!strpos($thisIP,"."))
			$dotTitle[] = "IP: " . long2ip($thisIP);
		else
			$dotTitle[] = "IP: " . $thisIP;

		if ($ipInfo[0] && strlen($ipInfo[0]) > 0 && $ipInfo[1] && strlen($ipInfo[1]) > 0) {

			if ($ipInfo[2] && strlen($ipInfo[2]) > 0)
				$dotTitle[] = $ipInfo[2];

			if ($ipInfo[3] && strlen($ipInfo[3]) > 0)
				$dotTitle[] = $ipInfo[3];

			if ($ipInfo[4] && strlen($ipInfo[4]) > 0)
				$dotTitle[] = $ipInfo[4];

			if (count($dotTitle))
				$dotTitle = implode(", ", $dotTitle);
			else
				$dotTitle = "Unknown";

			$dotsToPlot[] = array("title" => $dotTitle, "longitude" => $ipInfo[1], "latitude" => $ipInfo[0]);
				
		}
	}

	return buildJavascriptPlots($dotsToPlot,$zoomLevel,$scale,$color);
}

// Pass in an array 'dots' where each element is an array in the format ({ title,longitude,latitude }) to return appropriately formatted javascript to plot points with ammaps
function buildJavascriptPlots($dots,$zoomLevel=5,$scale="0.5",$color="1C1C1C") {

	if (!is_array($dots))
		return;

	$dots = array_unique($dots);

	$out = array();

	foreach($dots AS $thisDot) {

		$out[] = "{svgPath:targetSVG, color: \"#" . $color . "\", zoomLevel:" . $zoomLevel . ", scale:" . $scale . ", title:\"" . $thisDot["title"] . "\", latitude:" . $thisDot["latitude"] . ", longitude:" . $thisDot["longitude"] . "}";

	}

	return implode(",",$out);
}

?>