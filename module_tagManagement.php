<?php

function prv_tagManagement() {

	// Build renderers

	$_ENV['templateRenderer1'] = displayTagsByUserID($_SESSION['user']['id']);
	$_ENV['templateRenderer2'] = displayNonUserTags();

	// Get details

	$_ENV['templateData'] = tagDetails(sanitize("id"));

	// Template

	$_ENV['contentTitle'] = "Tag Management";

	$_ENV['template'] = "template_tagManagement.php";

}

function prv_tagEvents() {

	$_ENV['contentTitle'] = "Tag Events";

	$_ENV['template'] = "template_tagEvents.php";

	$_ENV['templateData'] = getEventsToTag();

}

function getEventsToTag() {

	$num = sanitize("num");
	$sortColumn = sanitize("sortColumn");
	$sortDirection = sanitize("sortDirection");
	$untaggedOnly = sanitize("untaggedOnly");
	$sensorIDsToSearch = sanitize("sensorIDsToSearch");
	$tagID = sanitize("tagID");
	$tagEventCheckbox = getVar("tagEventCheckbox");
	$tagFilter = sanitize("tagFilter");
	$sensorArray = array();
	$tagArray = array();
	$tagMessage = "";

	$store = $_SESSION['user']['activeSearchStore'];

	if (!$sortColumn)
		$sortColumn = "timestamp";

	if (!$sortDirection)
		$sortDirection = "DESC";

	if (!$num)
		$num = 25;

	if ($num != "all")
		$num = " LIMIT " . $num;
	else
		$num = "";

	$objSensor = new mySensor();
	$sensorIDs = implode(",",$objSensor->getSensorIDs(0,$_SESSION['user']['id']));

	if (!$sensorIDs || $sensorIDs == "")
		$sensorIDs = -1;

	if (!$sensorIDsToSearch)
		$sensorIDsToSearch = $sensorIDs;

	$sensorArray[] = array("id" => $sensorIDs, "name" => "All");

	if ($tagID && $tagEventCheckbox) {

		$tagCountTotal = 0;
		$tagCountTagged = 0;

		$tagInfo = mysqli_fetch_assoc($_ENV['appDB']->query("SELECT name FROM idsTags WHERE id=" . $tagID));

		foreach ($tagEventCheckbox AS $thisEventToTag) {

			$tagCountTotal++;

			$q = $_ENV['appDB']->query("SELECT * FROM idsDataStore_" . $store . "_Tag_Index WHERE tagID = '$tagID' AND eventID = '$thisEventToTag'");

			if (!$q)
				$q = $_ENV['appDB']->query("SELECT * FROM idsdatastore_" . $store . "_tag_index WHERE tagID = '$tagID' AND eventID = '$thisEventToTag'");

			if (mysqli_num_rows($q) < 1) {

				$tagCountTagged++;

				$q = $_ENV['appDB']->query("INSERT INTO idsDataStore_" . $store . "_Tag_Index (tagID, eventID) VALUES ('$tagID', '$thisEventToTag')");

				if (!$q)
					$q = $_ENV['appDB']->query("INSERT INTO idsdatastore_" . $store . "_tag_index (tagID, eventID) VALUES ('$tagID', '$thisEventToTag')");

			}

		}

		$tagMessage = $tagCountTagged . " Selected Event";

		if ($tagCountTagged != 1)
			$tagMessage .= "s";

		$tagMessage .= " Tagged With '" . $tagInfo["name"] . "'";

		if ($tagCountTotal != $tagCountTagged) {

			$tagCountMissed = $tagCountTotal - $tagCountTagged;

			$tagMessage .= " (" . $tagCountMissed . " Selected Event";

			if ($tagCountMissed > 1)
				$tagMessage .= "s";

			$tagMessage .= " Previously Tagged)";

		}

	}
  
	$query = "SELECT idsDataStore_" . $store . "_Events.*, idsDataStore_" . $store . "_Master.sensorID  FROM idsDataStore_" . $store . "_Events INNER JOIN idsDataStore_" . $store . "_Master ON (idsDataStore_" . $store . "_Events.id = idsDataStore_" . $store . "_Master.id) WHERE idsDataStore_" . $store . "_Master.sensorID IN(" . $sensorIDsToSearch . ")";

	if ($untaggedOnly && $untaggedOnly == 1)
		$query .= " AND idsDataStore_" . $store . "_Events.id NOT IN(SELECT DISTINCT eventID FROM idsDataStore_" . $store . "_Tag_Index)";

	if ($tagFilter && $tagFilter >= 1)
		$query .= " AND idsDataStore_" . $store . "_Events.id IN(SELECT DISTINCT eventID FROM idsDataStore_" . $store . "_Tag_Index WHERE tagID = " . $tagFilter . ")";

	$query .= " ORDER BY " . $sortColumn . " " . $sortDirection . $num;

	$result = $_ENV['appDB']->query($query);

	if (!$result) {

		$query = "SELECT idsdatastore_" . $store . "_events.*, idsdatastore_" . $store . "_master.sensorID  FROM idsdatastore_" . $store . "_events INNER JOIN idsdatastore_" . $store . "_master ON (idsdatastore_" . $store . "_events.id = idsdatastore_" . $store . "_master.id) WHERE idsdatastore_" . $store . "_master.sensorID IN(" . $sensorIDsToSearch . ")";
		
		if ($untaggedOnly && $untaggedOnly == 1)
			$query .= " AND idsdatastore_" . $store . "_events.id NOT IN(SELECT DISTINCT eventID FROM idsdatastore_" . $store . "_tag_index)";
		
		if ($tagFilter && $tagFilter >= 1)
			$query .= " AND idsdatastore_" . $store . "_events.id IN(SELECT DISTINCT eventID FROM idsdatastore_" . $store . "_tag_index WHERE tagID = " . $tagFilter . ")";
		
		$query .= " ORDER BY " . $sortColumn . " " . $sortDirection . $num;
		
		$result = $_ENV['appDB']->query($query);

	}

	while ($row = mysqli_fetch_assoc($result)) {

		$eventAge = consoleTimeConversion(time()) - sensorTimeConversion($row["sensorID"], $row["timestamp"]);

		if ($eventAge < 0)
			$eventAge = 0;

		$row["eventAge"] = $eventAge;
		$row["checkboxValue"] = $row["id"];
		$row["checkboxName"] = "tagEventCheckbox[]";

		$eventRenders .= itemRenderer("eventConsole", $row);

	}

	foreach (explode(",",$sensorIDs) AS $thisSensorID) {

		$sensorArray[] = array("id" => $thisSensorID, "name" => $objSensor->getSensorName($thisSensorID));

	}

	$result = $_ENV['appDB']->query("SELECT * FROM idsTags ORDER BY name ASC");

	while ($row = mysqli_fetch_assoc($result)) {

		$tagArray[] = array("id" => $row["id"], "name" => $row["name"]);

	}

	return arrayToObject(array("events" => $eventRenders, "sensors" => $sensorArray, "tags" => $tagArray, "tagMessage" => $tagMessage));

}

function displayAllTags() {
	
	// Tag list

	$q = $_ENV['appDB']->query("SELECT * FROM idsTags ORDER BY name ASC");
	while ($row = mysqli_fetch_assoc($q)) {
	
		if (!getVar("id")) setVar("id", $row['id']);
	
		$out .= itemRenderer('tagManagement', $row);	
		
	}
	
	return $out;

}

function displayTagsByUserID($id) {
	
	// Tag list

	$q = $_ENV['appDB']->query("SELECT * FROM idsTags WHERE userid = '" . $id . "' ORDER BY name ASC");
	while ($row = mysqli_fetch_assoc($q)) {
	
		if (!getVar("id")) setVar("id", $row['id']);
	
		$out .= itemRenderer('tagManagement', $row);	
		
	}
	
	return $out;

}

function displayNonUserTags() {
	
	// Tag list

	$q = $_ENV['appDB']->query("SELECT * FROM idsTags WHERE userid != '" . $_SESSION['user']['id'] . "' ORDER BY name ASC");
	while ($row = mysqli_fetch_assoc($q)) {
	
		if (!getVar("id")) setVar("id", $row['id']);
	
		$out .= itemRenderer('tagManagement', $row);	
		
	}
	
	return $out;

}

function tagDetails($id) {

	$datastore = new dataStore();
	$currentDatastore = $datastore->getActiveDataStore();

	$q = arrayToObject(mysqli_fetch_assoc($_ENV['appDB']->query("SELECT * FROM idsTags WHERE id = '$id'")));
	
	$currentDatastoreTagCount = arrayToObject(mysqli_fetch_assoc($_ENV['appDB']->query("SELECT COUNT(tagID) AS tagCount FROM idsDataStore_" . $currentDatastore . "_Tag_Index where tagID = " . $id)))->tagCount;

	$stores = $_ENV['appDB']->query("SELECT DISTINCT store FROM idsDataStore WHERE store  != " . $currentDatastore);

	$storeQueryArray = array();

	while ($row = mysqli_fetch_assoc($stores)) {

		$storeQueryArray[] = "SELECT COUNT(tagID) AS tagCount FROM idsDataStore_" . $row['store'] . "_Tag_Index where tagid = " . $id;		 

	}

	$query = "SELECT SUM(tagCount) AS tagCount FROM (" . implode(" UNION ALL ", $storeQueryArray) . ") tCount";

	$q3 = $_ENV['appDB']->query($query);

	$q->currentDatastoreTagCount = $currentDatastoreTagCount;
	$q->otherDatastoreTagCount = mysqli_fetch_object($q3)->tagCount;

	return $q;
	
}

function prv_addTagToEvent() {

	$id = sanitize("id");
	$tag = sanitize("tag");
	$store = sanitize("store");

	// Check if tag exists
	
	$q = $_ENV['appDB']->query("SELECT * FROM idsDataStore_" . $store . "_Tag_Index WHERE tagID = '$tag' AND eventID = '$id'");
	if (mysqli_num_rows($q) > 0) {

	    $_ENV['redirectMessage'] = "Tag already exists";
			
	} else {
	
		$q = $_ENV['appDB']->query("INSERT INTO idsDataStore_" . $store . "_Tag_Index (tagID, eventID) VALUES ('$tag', '$id')");
	
	    $_ENV['redirectMessage'] = "Adding event tag";
	
	}

    // Redirect

    $_ENV['redirect'] = array("prv_eventDetails&id=$id", DEFAULT_REDIRECT_TIMEOUT);


    $_ENV['template'] = "template_redirectGeneral.php";

}

function prv_deleteTagFromEvent() {

	$id = sanitize("id");
	$eid = sanitize("eid");
	$store = sanitize("store");

	$q = $_ENV['appDB']->query("DELETE FROM idsDataStore_" . $store . "_Tag_Index WHERE tagID = '$id' AND eventID = '$eid'");

    // Redirect

    $_ENV['redirect'] = array("prv_eventDetails&id=$eid", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Removing event tag";

    $_ENV['template'] = "template_redirectGeneral.php";

}

function prv_tagManagementUpdate() {

	$id = sanitize("id");
	$name = sanitize("name");
	$description = sanitize("description");

	// Update tag details

	$_ENV['appDB']->query("UPDATE idsTags SET name = '$name' WHERE id = '$id'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_tagManagement&id=$id", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Updating Tag";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_tagManagementCreate() {

	// Create default tag

	$q = $_ENV['appDB']->query("INSERT INTO idsTags (userid, name) VALUES ('" . $_SESSION['user']['id'] . "', 'New Tag')");
	
	$id = mysqli_insert_id($_ENV['appDB']->link);
		
    // Redirect

    $_ENV['redirect'] = array("prv_tagManagement&id=$id", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Creating Tag";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_tagManagementDelete() {

	$id = sanitize("id");

	// Delete tag

	$_ENV['appDB']->query("DELETE FROM idsTags WHERE id = '$id'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_tagManagement", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Deleting Tag";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

?>