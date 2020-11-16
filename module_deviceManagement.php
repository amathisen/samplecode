<?php

function prv_deviceManagement() {

	// Build renderers

	$_ENV['templateRenderer'] = displayAlldevices();

	// Get details

	$_ENV['templateData'] = deviceDetails(sanitize("id"));

	// Template

	$_ENV['contentTitle'] = "Device Management";

	$_ENV['template'] = "template_deviceManagement.php";

}

function displayAlldevices() {

	if (sanitize('filterDeviceType')) {

		$filterQuery = "WHERE idsDevices.type = '" . sanitize('filterDeviceType') . "'";
		
	}
	
	// List

	$q = $_ENV['appDB']->query("SELECT idsDevices.*, idsDeviceTypes.name AS typeName FROM idsDevices JOIN idsDeviceTypes ON (idsDevices.type = idsDeviceTypes.id) $filterQuery ORDER BY name");
	while ($row = mysqli_fetch_assoc($q)) {

		if (!getVar("id")) setVar("id", $row['id']);

		$q2 = $_ENV['appDB']->query("SELECT * FROM idsDeviceMeta WHERE deviceID = '" . $row['id'] . "' AND name = 'State'");
		while ($row2 = mysqli_fetch_assoc($q2)) {

			$row['state'] = $row2['value'];

		}
	
		$out .= itemRenderer('deviceManagement', $row);	
		
	}
	
	return $out;

}

function deviceDetails($id) {
	
	$q = $_ENV['appDB']->query("SELECT * FROM idsDevices WHERE id = '$id'");
	$result = mysqli_fetch_object($q);

	$result->addresses = array();

	$q = $_ENV['appDB']->query("SELECT * FROM idsDeviceAddresses WHERE deviceID = '$id'");
	while($row = mysqli_fetch_object($q)) {
	
		$result->addresses[] = $row;
		
	}

	$q = $_ENV['appDB']->query("SELECT * FROM idsDeviceServices WHERE deviceID = '$id'");
	while($row = mysqli_fetch_object($q)) {
	
		$result->services[] = $row;
		
	}

	$q = $_ENV['appDB']->query("SELECT * FROM idsDeviceMeta WHERE deviceID = '$id' ORDER BY name ASC");
	while($row = mysqli_fetch_object($q)) {
	
		$result->meta[] = $row;
		
	}
	
	return $result;
	
}

function prv_deviceManagementUpdate() {

	$id = sanitize("id");
	$name = sanitize("name");
	$description = sanitize("description");
	$type = sanitize("deviceType");

	// Update device details

	$_ENV['appDB']->query("UPDATE idsDevices SET name = '$name', description = '$description', type = '$type' WHERE id = '$id'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Updating Device";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceManagementCreate() {

	// Create default device

	$q = $_ENV['appDB']->query("INSERT INTO idsDevices (name, description, type) VALUES ('New Device', '', '1')");
	
	$id = mysqli_insert_id($_ENV['appDB']->link);
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Creating New Device";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceManagementDelete() {

	$id = sanitize("id");

	// Delete device

	$_ENV['appDB']->query("DELETE FROM idsDevices WHERE id = '$id'");
	$_ENV['appDB']->query("DELETE FROM idsDeviceAddresses WHERE deviceID = '$id'");
	$_ENV['appDB']->query("DELETE FROM idsDeviceServices WHERE deviceID = '$id'");
	$_ENV['appDB']->query("DELETE FROM idsDeviceMeta WHERE deviceID = '$id'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement", DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Deleting Device";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceAddressCreate() {

	$id = sanitize("id");

	// Create default device address

	$q = $_ENV['appDB']->query("INSERT INTO idsDeviceAddresses (deviceID, name, ip) VALUES ('$id', 'eth0', '0.0.0.0')");
	
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Creating New Device Address";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceAddressUpdate() {

	$id = sanitize("id");
	$aid = sanitize("aid");

	$name = sanitize("name");
	$ip = sanitize("ip");

	// Update device address

	$_ENV['appDB']->query("UPDATE idsDeviceAddresses SET name = '$name', ip = '" . ipToLong($ip) . "' WHERE id = '$aid'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Updating Device Address";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceAddressDelete() {

	$id = sanitize("id");
	$aid = sanitize("aid");

	// Delete device address

	$_ENV['appDB']->query("DELETE FROM idsDeviceAddresses WHERE id = '$aid'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Deleting Device Address";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceServiceCreate() {

	$id = sanitize("id");

	// Create default device service

	$q = $_ENV['appDB']->query("INSERT INTO idsDeviceServices (deviceID, name, port, protocol) VALUES ('$id', '', '0', '6')");
	
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Creating New Device Service";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceServiceUpdate() {

	$id = sanitize("id");
	$sid = sanitize("sid");

	$name = sanitize("name");
	$port = sanitize("port");
	$protocol = sanitize("protocol");

	// Update device service

	$_ENV['appDB']->query("UPDATE idsDeviceServices SET name = '$name', port = '$port', protocol = '$protocol' WHERE id = '$sid'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Updating Device Service";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

function prv_deviceServiceDelete() {

	$id = sanitize("id");
	$sid = sanitize("sid");

	// Delete device service

	$_ENV['appDB']->query("DELETE FROM idsDeviceServices WHERE id = '$sid'");
		
    // Redirect

    $_ENV['redirect'] = array("prv_deviceManagement&id=$id&filterDeviceType=" . sanitize("filterDeviceType"), DEFAULT_REDIRECT_TIMEOUT);

    $_ENV['redirectMessage'] = "Deleting Device Service";

    $_ENV['template'] = "template_redirectGeneral.php";
		
}

?>