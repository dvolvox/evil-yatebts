/*
* MSISDN Spoofing function
*/

function onControl(msg)
{
	// check if parameters exist
	if (!msg.imsi || !msg.msisdn) {
		msg.retValue("Missing IMSI or MSISDN. The MSISDN will not changed.");
		msg["operation-status"] = false;
		return true;
	}

	// Get current subscriber information
	var tmp_subscriber = registered_subscribers[msg.imsi];
	
	// Update the subscriber with new MSISDN
	tmp_subscriber["msisdn"] = msg.msisdn;
	
	// Update in DB the subscriber information
	registered_subscribers[imsi] = tmp_subscriber;
	
	return true;	
	
}

Engine.debugName("change_msisdn");
Message.install(onControl,"chan.control",80,"component","change_msisdn");


