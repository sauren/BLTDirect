<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineQuote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

if(!isset($_REQUEST['customerid'])) {
	redirect(sprintf("Location: enquiry_create.php"));
}

$sessionKey = sprintf('new-%s', $session->ID);

$customer = new Customer($_REQUEST['customerid']);
$customer->Contact->Get();

if($customer->Contact->Parent->ID > 0) {
	$customer->Contact->Parent->Get();
}

$customerName = trim(sprintf('%s %s %s', $customer->Contact->Person->Title, $customer->Contact->Person->Name, $customer->Contact->Person->LastName));
$orgName = $customer->Contact->Person->GetFullName();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('customerid', 'Customer ID', 'hidden', '', 'numeric_unsigned', 1, 11, true);
$form->AddField('prefix', 'Type', 'select', 'T', 'alpha', 1, 1);
$form->AddOption('prefix', 'T', 'Telephone');
$form->AddOption('prefix', 'W', 'Web');
$form->AddOption('prefix', 'E', 'Email');
$form->AddOption('prefix', 'F', 'Fax');
$form->AddOption('prefix', 'L', 'Letter');
$form->AddField('type', 'Category', 'select', '0', 'numeric_unsigned', 1, 11, true, 'onchange="populateTemplate(this);"');
$form->AddOption('type', '33', '33');
$form->AddField('subject', 'Subject', 'text', '', 'anything', 1, 255, true, 'style="width: 100%;"');
$form->AddField('message', 'Message', 'textarea', '', 'anything', 1, 16384, false, 'style="width: 100%; font-family: arial, sans-serif;" rows="15"');
$form->AddField('template', 'Standard Template', 'select', '0', 'anything', 1, 255, false, 'onchange="populateResponse(this);"');
$form->AddOption('template', '0', '');
$form->AddField('product', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="8"');
$form->AddField('public', 'Public', 'checkbox', (isset($_REQUEST['public'])) ? $_REQUEST['public'] : (isset($_REQUEST['confirm']) ? 'N' : 'Y'), 'boolean', 1, 1, false);
$form->AddField('customermessage', 'Customer Message', 'checkbox', (isset($_REQUEST['customermessage'])) ? $_REQUEST['customermessage'] : 'N', 'boolean', 1, 1, false);
$form->AddField('pending', 'Pending Action', 'checkbox', (isset($_REQUEST['pending'])) ? $_REQUEST['pending'] : (isset($_REQUEST['confirm']) ? 'N' : 'Y'), 'boolean', 1, 1, false);
$form->AddField('owner', 'Owned By', 'select', $session->UserID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('owner', '0', '');
$form->AddField('bigenquiry', 'Big Enquiry', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('tradeenquiry', 'Trade Enquiry', 'checkbox', 'N', 'boolean', 1, 1, false);

$title = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$additional = array();

$data = new DataQuery(sprintf("SELECT * FROM enquiry_type ORDER BY Name ASC"));
while($data->Row) {
	if(strtolower($data->Row['Name']) == 'other') {
		$additional[$data->Row['Name']] = $data->Row['Enquiry_Type_ID'];
	} else {
		$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);
	}

	$data->Next();
}
$data->Disconnect();

foreach($additional as $type=>$id) {
	$form->AddOption('type', $id, $type);
}

$data = new DataQuery(sprintf("SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC"));
while($data->Row) {
	$form->AddOption('owner', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

	$data->Next();
}
$data->Disconnect();

$templateCount = 0;

$data = new DataQuery(sprintf("SELECT Enquiry_Template_ID, Title, Template FROM enquiry_template WHERE Enquiry_Type_ID=0 ORDER BY Title ASC"));
while($data->Row) {
	$templateCount++;

	$form->AddOption('template', $data->Row['Enquiry_Template_ID'], $data->Row['Title']);
	$data->Next();
}
$data->Disconnect();

if($action == 'removesessionquote') {
	unset($_SESSION['Enquiries'][$sessionKey]['Quotes'][$_REQUEST['quoteid']]);

	redirect(sprintf("Location: %s?customerid=%d", $_SERVER['PHP_SELF'], $customer->ID));

} elseif($action == 'removesessiondocument') {
	$file = new IFile();
	$file->Directory = $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'];
	$file->FileName = $_REQUEST['document'];

	if($file->Delete()) {
		unset($_SESSION['Enquiries'][$sessionKey]['Documents'][$_REQUEST['document']]);
	}

	redirect(sprintf("Location: %s?customerid=%d", $_SERVER['PHP_SELF'], $customer->ID));
}

if(isset($_REQUEST['post'])) {
	if(isset($_REQUEST['post'])) {
		$form->InputFields['message']->Required = true;
	}

	if($form->Validate()) {
		$enquiry = new Enquiry();
		$enquiry->Customer->ID = $form->GetValue('customerid');
		$enquiry->Prefix = $form->GetValue('prefix');
		$enquiry->Subject = $form->GetValue('subject');
		$enquiry->Status = 'Unread';
		$enquiry->Type->ID = $form->GetValue('type');
		$enquiry->IsPendingAction = (isset($_REQUEST['pending']) && ($_REQUEST['pending'] == 'Y')) ? 'Y' : 'N';
		$enquiry->OwnedBy = ($form->GetValue('owner') > 0) ? $form->GetValue('owner') : $session->UserID;
		$enquiry->IsBigEnquiry = $form->GetValue('bigenquiry');
		$enquiry->IsTradeEnquiry = $form->GetValue('tradeenquiry');
		$enquiry->Add();

		$user = new User($session->UserID);
		$ownerFound = false;

		if($enquiry->OwnedBy > 0) {
			$owner = new User();
			$owner->ID = $enquiry->OwnedBy;

			if($owner->Get()) {
				$ownerFound = true;
			}
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s%s %s %s", ($enquiry->Customer->Contact->Parent->ID > 0) ? sprintf('%s<br />', $enquiry->Customer->Contact->Parent->Organisation->Name) : '', $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName));
		$findReplace->Add('/\[TITLE\]/', $enquiry->Customer->Contact->Person->Title);
		$findReplace->Add('/\[FIRSTNAME\]/', $enquiry->Customer->Contact->Person->Name);
		$findReplace->Add('/\[LASTNAME\]/', $enquiry->Customer->Contact->Person->LastName);
		$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName)))));
		$findReplace->Add('/\[FAX\]/', $enquiry->Customer->Contact->Person->Fax);
		$findReplace->Add('/\[PHONE\]/', $enquiry->Customer->Contact->Person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $enquiry->Customer->Contact->Person->Address->GetLongString());
		$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[USEREMAIL\]/', $user->Username);
		$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
		$findReplace->Add('/\[EMAIL\]/', $enquiry->Customer->GetEmail());
		//Do not send out Password via Email - Plus, its impossible to get unencrypted version of password.
		//$findReplace->Add('/\[PASSWORD\]/', $enquiry->Customer->GetPassword());

		if($ownerFound) {
			$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
		} else {
			$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
		}

		$message = $findReplace->Execute($form->GetValue('message'));

		$enquiryLine = new EnquiryLine();
		$enquiryLine->Enquiry->ID = $enquiry->ID;
		$enquiryLine->IsCustomerMessage = (isset($_REQUEST['customermessage']) && ($_REQUEST['customermessage'] == 'Y')) ? $_REQUEST['customermessage'] : 'N';
		$enquiryLine->IsPublic = (isset($_REQUEST['public']) && ($_REQUEST['public'] == 'Y')) ? $_REQUEST['public'] : 'N';
		$enquiryLine->Message = $message;
		$enquiryLine->Add();

		if(isset($_SESSION['Enquiries']) && isset($_SESSION['Enquiries'][$sessionKey])) {
			if(isset($_SESSION['Enquiries'][$sessionKey]['Quotes'])) {
				foreach($_SESSION['Enquiries'][$sessionKey]['Quotes'] as $quoteItem) {
					$enquiryQuote = new EnquiryLineQuote();
					$enquiryQuote->Quote->ID = $quoteItem;
					$enquiryQuote->EnquiryLineID = $enquiryLine->ID;
					$enquiryQuote->Add();

					$enquiryLine->Quotes[] = $enquiryQuote;
				}
			}

			if(isset($_SESSION['Enquiries'][$sessionKey]['Documents'])) {
				foreach($_SESSION['Enquiries'][$sessionKey]['Documents'] as $documentItem) {
					$enquiryDocument = new EnquiryLineDocument();
					$enquiryDocument->IsPublic = $documentItem['Public'];
					$enquiryDocument->File->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
					$enquiryDocument->File->FileName = $documentItem['FileName'];
					$enquiryDocument->EnquiryLineID = $enquiryLine->ID;

					if(!empty($enquiryDocument->File->FileName) && file_exists($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$enquiryDocument->File->FileName)) {
						$destination = new IFile();
						$destination->OnConflict = "makeunique";
						$destination->Extensions = "";
						$destination->SetDirectory($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS']);
						$destination->FileName = $enquiryDocument->File->FileName;
						$destination->SetName($destination->FileName);

						if($destination->Exists()) {
							$destination->CreateUniqueName($enquiryDocument->File->FileName);
						}

						if($enquiryDocument->File->Copy($destination->Directory, $destination->FileName)) {
							$enquiryDocument->File->FileName = $destination->FileName;
							$enquiryDocument->Add();

							$enquiryLine->Documents[] = $enquiryDocument;

							$source = new IFile();
							$source->Directory = $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'];
							$source->FileName = $documentItem['FileName'];
							$source->Delete();
						}
					}
				}
			}

			unset($_SESSION['Enquiries'][$sessionKey]);
		}

		redirect(sprintf("Location: enquiry_details.php?enquiryid=%d", $enquiry->ID));
	}
}

$script = '<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>';
$script .= sprintf('<script language="javascript" type="text/javascript">
var parseResponseHandler = function(response) {
	tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
	tinyMCE.activeEditor.setContent(response);
}

var parseRequest = new HttpRequest();
parseRequest.setCaching(false);
parseRequest.setHandlerResponse(parseResponseHandler);

var parseDocument = function(id) {
	parseRequest.abort();
	parseRequest.get(\'lib/util/parseEnquiryDocument.php?id=\' + id + \'&customerid=%d&userid=%d\');
}

var parseTemplate = function(id) {
	parseRequest.abort();
	parseRequest.get(\'lib/util/parseEnquiryTemplate.php?id=\' + id + \'&customerid=%d&&userid=%d\');
}
</script>', $customer->ID, $session->UserID, $customer->ID, $session->UserID);

$script .= sprintf('<script language="javascript" type="text/javascript">
var templateResponseHandler = function(details) {
	var items = details.split("{br}\n");

	parseTemplate(items[0]);
}

var templateRequest = new HttpRequest();
templateRequest.setCaching(false);
templateRequest.setHandlerResponse(templateResponseHandler);

var populateResponse = function(obj) {
	if(obj.value == 0) {
		tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
		tinyMCE.activeEditor.setContent(\'\');
	} else {
		templateRequest.abort();
		templateRequest.get(\'lib/util/getEnquiryTemplate.php?id=\' + obj.value);
	}
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var documentResponseHandler = function(details) {
	var items = details.split("{br}\n");

	parseDocument(items[0]);
}

var documentRequest = new HttpRequest();
documentRequest.setCaching(false);
documentRequest.setHandlerResponse(documentResponseHandler);

var foundDocument = function(id, title) {
	documentRequest.abort();
	documentRequest.get(\'lib/util/getDocument.php?id=\' + id);
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var typeResponseHandler = function(details) {
	var items = details.split("{br}{br}\n");
	var subItems = null;
	var e = document.getElementById(\'template\');
	var templateContainer = null;

	if(e) {
		e.options.length = 1;

		for(var i=0; i < items.length; i++) {
			subItems = items[i].split("{br}\n");

			if(subItems[0] && subItems[1]) {
				e.options[i+1] = new Option(subItems[1], subItems[0]);
			}
		}

		e.selectedIndex = 0;

		templateContainer = document.getElementById(\'templateContainer\');
		if(templateContainer) {
			if(e.options.length > 1) {
				templateContainer.style.display = \'block\';
			} else {
				templateContainer.style.display = \'none\';
			}
		}

		if(templateId > 0) {
			for(var i=0; i<e.options.length; i++) {
				if(e.options[i].value == templateId) {
					e.selectedIndex = i;
					break;
				}
			}
		}
	}
}

var typeRequest = new HttpRequest();
typeRequest.setCaching(false);
typeRequest.setHandlerResponse(typeResponseHandler);

var populateTemplate = function(obj) {
	typeRequest.get(\'lib/util/getEnquiryTemplatesByType.php?id=\' + obj.value);
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var productResponseHandler = function(details) {
	var items = details.split("{br}\n");
	var text = \'<a href="http://www.bltdirect.com/product.php?pid=\' + items[0] + \'">\' + items[1] + \'</a>\';
		
	tinyMCE.execInstanceCommand(\'mceFocus\', false, \'message\');
	tinyMCE.activeEditor.execCommand("mceInsertRawHTML", false, text);

	var e = document.getElementById(\'product\');
	if(e) {
		e.value = \'\';
	}
}

var productResponseError = function() {
	alert(\'The Product ID could not be found.\');
}

var productRequest = new HttpRequest();
productRequest.setCaching(false);
productRequest.setHandlerResponse(productResponseHandler);
productRequest.setHandlerError(productResponseError);

var insertAtCursor = function() {
	var e = document.getElementById(\'product\');

	if(e) {
		if(e.value.length == 0) {
			alert(\'Product ID requies a value.\');
		} else {
			productRequest.get(\'lib/util/getProduct.php?id=\' + e.value);
		}
	}
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var foundProduct = function(pid) {
	var e = document.getElementById(\'product\');
	if(e) {
		e.value = pid;
	}
}
</script>');

$script .= sprintf('<script language="javascript" type="text/javascript">
var templateId = \'%s\';

window.onload = function() {
	var e = document.getElementById(\'type\');

	if(e) {
		if(e.value > 0) {
			typeRequest.get(\'lib/util/getEnquiryTemplatesByType.php?id=\' + e.value);
		}
	}
}
</script>', isset($_REQUEST['template']) ? $_REQUEST['template'] : 0);

$page = new Page('Create New Enquiry', sprintf('Create a new enquiry for %s.', $customerName));
$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_enquiries.css" />');
$page->AddToHead($script);
$page->SetEditor(true);
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('customerid');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top">

			<table width="100%">
				<tr>
					<td class="enquiryBlock">
						<p><span class="pageSubTitle">Enquiry Message</span><br /><span class="pageDescription">Enter the specific enquiry details here.</span></p>

						<?php
						echo sprintf('<strong>%s</strong><br />%s<br />', $form->GetLabel('subject'), $form->GetHTML('subject'));
						echo sprintf('<div id="templateContainer" style="%s"><strong>%s</strong><br />%s<br /><br /></div>', ($templateCount == 0) ? 'display: none;' : '',$form->GetLabel('template'), $form->GetHTML('template'));
						echo sprintf('<strong>%s</strong> <a href="javascript:popUrl(\'popFindDocument.php?callback=foundDocument\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a><br /><fieldset style="border: none; padding: 0;">%s</fieldset><br />', $form->GetLabel('message'), $form->GetHTML('message'));
						?>

						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td valign="top">
									<strong><?php echo $form->GetLabel('product'); ?></strong> <a href="javascript:popUrl('popFindProduct.php?callback=foundProduct', 650, 500);"><img src="images/icon_search_1.gif" alt="Add product" width="16" height="16" align="absmiddle" border="0" /></a><br />
									<?php echo $form->GetHTML('product'); ?> <a href="javascript:insertAtCursor();"><img src="images/icon_edit_1.gif" alt="Add product" width="16" height="16" align="absmiddle" border="0" /></a>
								</td>
							</tr>
						</table><br />

						<?php echo $form->GetHTML('public'); ?> <strong><?php echo $form->GetLabel('public'); ?></strong> (Check this box if the customer is allowed to view this message)<br /><br />
						<?php echo $form->GetHTML('customermessage'); ?> <strong><?php echo $form->GetLabel('customermessage'); ?></strong> (Check this box to post this message as if authored by the customer)<br /><br />
						<?php echo $form->GetHTML('pending'); ?> <strong><?php echo $form->GetLabel('pending'); ?></strong> (Check this box if this enquiry requires further action)<br /><br />

						<input type="submit" name="post" value="post message" class="btn" />

						<br /><br />

						<?php
						if(isset($_SESSION['Enquiries']) && isset($_SESSION['Enquiries'][$sessionKey])) {
							if(isset($_SESSION['Enquiries'][$sessionKey]['Quotes']) && (count($_SESSION['Enquiries'][$sessionKey]['Quotes']) > 0)) {
								echo '<p><strong><em>Attached Quotes:</em></strong></p>';

								$quote = new Quote();

								foreach($_SESSION['Enquiries'][$sessionKey]['Quotes'] as $quoteItem) {
									$quote->Get($quoteItem);
									echo sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_summary.php?action=removesessionquote&customerid=%d&quoteid=%d\', \'Are you sure you wish to remove this quote?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Quote" align="absmiddle" /></a> <a href="quote_details.php?quoteid=%d">%s%s</a> (%s)</div>', $customer->ID, $quote->ID, $quote->ID, $quote->Prefix, $quote->ID, cDatetime($quote->CreatedOn, 'shortdatetime'));
								}

								echo '<br />';
							}

							if(isset($_SESSION['Enquiries'][$sessionKey]['Documents']) && (count($_SESSION['Enquiries'][$sessionKey]['Documents']) > 0)) {
								$lines = array();

								foreach($_SESSION['Enquiries'][$sessionKey]['Documents'] as $key=>$documentItem) {
									if(!empty($documentItem['FileName']) && file_exists($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$documentItem['FileName'])) {
										$lines[] = sprintf('<div style="padding: 0 0 0 20px;"><a href="javascript:confirmRequest(\'enquiry_summary.php?action=removesessiondocument&customerid=%d&document=%s\', \'Are you sure you wish to remove this document?\');"><img border="0" src="images/icon_cross_3.gif" alt="Remove Document" align="absmiddle" /></a> <a %s href="%s" target="_blank">%s</a> (%s bytes)</div>', $customer->ID, $documentItem['FileName'], ($documentItem['Public'] == 'N') ? 'class="enquiryHiddenDocument"' : '', $GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_WS'].$documentItem['FileName'], $documentItem['FileName'], number_format(filesize($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS'].$documentItem['FileName']), 0, '.', ','));
									} else {
										unset($_SESSION['Enquiries'][$sessionKey]['Documents'][$key]);
									}
								}

								if(count($lines) > 0) {
									echo '<p><strong><em>Attached Documents:</em></strong></p>';

									foreach($lines as $line) {
										echo $line;
									}

									echo '<br />';
								}
							}
						}
					 	?>

						<hr style="background-color: #eee; color: #eee; height: 1px;" />
					 	<div style="text-align: right;">
							<p style="padding: 0; margin: 0;"><a href="enquiry_add_product_downloads.php?customerid=<?php echo $customer->ID; ?>">Attach Product Downloads</a> | <a href="enquiry_add_quote.php?customerid=<?php echo $customer->ID; ?>">Attach Quote</a> | <a href="enquiry_add_document.php?customerid=<?php echo $customer->ID; ?>">Attach Document</a></p>
						</div>

					</td>
				</tr>
			</table>

		</td>
		<td width="15"></td>
		<td valign="top" width="300">

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Enquiry Info</span><br /><span class="pageDescription">Change the type of this enquiry here.</span></p>

				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<tr>
						<td><p><strong>Type:</strong></p></td>
						<td><p><?php print $form->GetHTML('prefix'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Category:</strong></p></td>
						<td><p><?php print $form->GetHTML('type'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Owned By:</strong></p></td>
						<td><p><?php print $form->GetHTML('owner'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Big Enquiry:</strong></p></td>
						<td><p><?php print $form->GetHTML('bigenquiry'); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Trade Enquiry:</strong></p></td>
						<td><p><?php print $form->GetHTML('tradeenquiry'); ?></p></td>
					</tr>
				</table>

			</div>
			<br />

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID)));
			$enquiryCount = $data->Row['Count'];
			$data->Disconnect();
			?>

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Customer Info</span><br /><span class="pageDescription">Contact details for this customer.</span></p>

				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<tr>
						<td><p><strong>Customer:</strong></p></td>
						<td><p><a href="contact_profile.php?cid=<?php print $customer->Contact->ID; ?>"><?php print $customerName; ?></a></p></td>
					</tr>
					<tr>
						<td><p><strong>Phone:</strong></p></td>
						<td><p><?php print $customer->Contact->Person->Phone1; ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Email:</strong></p></td>
						<td><p><?php print $customer->GetEmail(); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Enquiries:</strong></p></td>
						<td><p><?php print $enquiryCount; ?></p></td>
					</tr>
				</table>

			</div>
			<br />

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Quick Links</span><br /><span class="pageDescription">Use these quick links to swiftly navigate to areas of this customers profile.</span></p>

				<p>
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('enquiry_create.php?action=find&cuid=%d', $customer->ID); ?>">Create New Enquiry</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('order_create.php?action=find&cuid=%d', $customer->ID); ?>">Create New Order/Quote</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('return_add.php?cid=%d', $customer->Contact->ID); ?>">Create New Return</a><br />

					<?php
					$data = new DataQuery(sprintf("select count(*) as Counter from orders where Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$orderCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from quote where Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$quoteCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from invoice where Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$invoiceCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from `return` where Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$returnCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from credit_note as c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID where o.Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$creditCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
					$campaignCount = $data->Row['Counter'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("select count(*) as Counter from enquiry where Customer_ID=%d", mysql_real_escape_string($customer->ID)));
					$enquiryCount = $data->Row['Counter'];
					$data->Disconnect();
					?>

					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_orders.php?customer=%d', $customer->ID); ?>">View Order History (<?php echo $orderCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_quotes.php?customer=%d', $customer->ID); ?>">View Quote History (<?php echo $quoteCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_invoices.php?customer=%d', $customer->ID); ?>">View Invoice History (<?php echo $invoiceCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_returns.php?customer=%d', $customer->ID); ?>">View Return History (<?php echo $returnCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_credits.php?customer=%d', $customer->ID); ?>">View Credit History (<?php echo $creditCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('contact_campaigns.php?cid=%d', $customer->Contact->ID); ?>">View Campaigns (<?php echo $campaignCount; ?>)</a><br />
					<img src="images/enquiry_link.gif" alt="" width="16" height="16" align="absmiddle" /> <a href="<?php echo sprintf('customer_enquiries.php?customer=%d', $customer->ID); ?>">View Enquiries (<?php echo $enquiryCount; ?>)</a><br />
				</p>

			</div>
			<br />

		</td>
	</tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>