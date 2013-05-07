<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

class EntityController {
	public static function start() {
		if(isset($_REQUEST['entity'])) {
			$entity = trim(urldecode($_REQUEST['entity']));

			$cypher = new Cipher(base64_decode($entity));
			$cypher->Decrypt();

			$entity = unserialize($cypher->Value);

			if(isset($entity['Type'])) {
				switch($entity['Type']) {
					case 'Campaign':
						new DataQuery(sprintf("UPDATE campaign_contact_event SET Is_Email_Followed='Y' WHERE Campaign_Event_ID=%d AND Campaign_Contact_ID=%d", mysql_real_escape_string($entity['CampaignEvent']), mysql_real_escape_string($entity['CampaignContact'])));

						$data = new DataQuery(sprintf("SELECT Subject, Created_On FROM campaign_event WHERE Campaign_Event_ID=%d", mysql_real_escape_string($entity['CampaignEvent'])));
						if($data->TotalRows > 0) {
							$_GET['trace'] = sprintf('eShot: %s (%s)', $data->Row['Subject'], cDatetime($data->Row['Created_On'], 'shortdate'));
						}
						$data->Disconnect();

						unset($_GET['entity']);

						$queryString = array();

						foreach($_GET as $key => $value) {
							$queryString[] = sprintf('%s=%s', $key, $value);
						}

						redirect(sprintf("Location: %s%s", $_SERVER['PHP_SELF'], (count($queryString) > 0) ? sprintf('?%s', implode('&', $queryString)) : ''));
						break;
				}
			}
		}
	}
}