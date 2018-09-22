<?php
/**
 * 
 * Spoof MSISDN Web Interface
 * @duartevolvox
 * 
 */

require_once("lib/lib_proj.php");
set_timezone();
require_once("ansql/set_debug.php");
require_once("ansql/lib.php");
require_once("lib/lib_proj.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title><?php print $proj_title; ?></title>
<link type="text/css" rel="stylesheet" href="css/main.css" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<script type="text/javascript" src="ansql/javascript.js"></script>
<script type="text/javascript" src="javascript.js"></script>
</head>
<body class="mainbody">
<?php  
if (getparam("method")=="send_command_to_yate")
	send_command_to_yate();
else
	change_msisdn();
?>
</body>
</html>

<?php
function change_msisdn($error = null, $note = null)
{
	if (!shell_exec('pidof yate')) 
                errormess("Please start YateBTS before performing this action.", "no");	
	
	if (strlen($error))
		errormess($error,"no");
	
	if ($note)
		nib_note($note);
	
	$fields = array(
		"IMSI"=> array("comment"=>"The IMSI change will change the MSISDN.","column_name"=>"IMSI"),
		"MSISDN" => array("column_name"=>"MSISDN", "comment"=>"MSISDN to be change to "),
	);
	
	start_form("change_msisdn.php", "get");
	addHidden(null,array("method"=>"send_command_to_yate"));
	editObject(null,$fields,"Spoof MSISDN Attack","Start Spoofing!",null,true);
	end_form();
}

function send_command_to_yate()
{
	global $default_ip, $default_port;

	$IMSI = trim(getparam("IMSI"));
	$MSISDN = trim(getparam("MSISDN"));


	if (!$IMSI || !$MSISDN){
		return change_msisdn("Invalid operation (missing values)");
	}

	if ($IMSI == "") 
		return change_msisdn("The IMSI cannot be empty. Please insert IMSI.");

	if ($MSISDN == "") 
		return change_msisdn("The MSISDN cannot be empty. Please insert MSISDN.");

	

	$socket = new SocketConn($default_ip, $default_port);
	if (strlen($socket->error))
		return change_msisdn($socket->error);
	
	$response = $socket->command("debug off", "quit");
	$response = $socket->command("sniffer off", "quit");
	
	//test if called is online 
	$command = "nib registered ". $IMSI;
	$response = $socket->command($command, "quit");
	
	if (!preg_match("/".$IMSI ." is registered./i", $response)) {
		$socket->close();
		return change_msisdn(null, "The subscriber ". $params["IMSI"]." is not online, try later to send the SMS.");
	}

	$command = "javascript load change_msisdn";
	$response = $socket->command($command, 'quit');

	if (preg_match("/Failed to load script from file 'change_msisdn.js'/i", $response))
		return change_msisdn("Failed to load script from file 'change_msisdn.js'. Please check logs."); 

	// Command
	// control change_msisdn imsi=$ msisdn=$ 
	$command = "control change_msisdn imsi=".$IMSI;
	$command .= " msisdn=".$MSISDN;


	$response = $socket->command($command, 'quit');
	$socket->close();
	
	if (preg_match("/Could not control /i", $response)) 
		return change_msisdn("Internal error, script change_msisdn.js didn't handle chan.control.");

	$note = "Problems encounted while sending custom SMS.";
	if (preg_match("/Control 'change_msisdn' ([[:print:]]+)\r/i", $response, $match)) 
		$note = $match[1];
	switch ($note) {
		case "FAILED":
			$note = "MSISDN was succesfully changed.";
			break;
		case "OK":
			$note =  "MSISDN was successfully changed.";
			break;
	}
	
	change_msisdn(null, $note);
}
?>
