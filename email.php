<?php
require_once('ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

if(param('ref')) {
	$reference = trim(urldecode(param('ref')));

	$cypher = new Cipher(base64_decode($reference));

	if(strlen(base64_decode($reference)) >= $cypher->IvSize) {
		$cypher->Data = base64_decode($reference);
		$cypher->Decrypt();

		if(is_numeric($cypher->Value)) {
			$data = new DataQuery(sprintf("SELECT e.Body FROM email_queue AS e WHERE e.Email_Queue_ID=%d", $cypher->Value));
			if($data->TotalRows > 0) {
				$entity = param('entity', '');
				if(!empty($entity)) { $entity = trim(urldecode($entity)); }
				if(!empty($entity)) {
					$cypher = new Cipher(base64_decode($entity));
					$cypher->Decrypt();

					$entity = unserialize($cypher->Value);

					if(isset($entity['Type'])) {
						switch($entity['Type']) {
							case 'Campaign':
								if(is_numeric($entity['CampaignEvent']) && is_numeric($entity['CampaignContact'])){
									new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Viewed='Y' WHERE Campaign_Event_ID=%d AND Campaign_Contact_ID=%d", $entity['CampaignEvent'], $entity['CampaignContact']));
								}
								break;
						}
					}
				}

				echo $data->Row['Body'];

				$data->Disconnect();
				exit;
			}
			$data->Disconnect();
		}
	}
}

redirect(sprintf("Location: index.php"));