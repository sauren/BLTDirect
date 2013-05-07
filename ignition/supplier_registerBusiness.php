<?php
	require_once('lib/common/app_header.php');	
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	// Secure this section
	$session->Secure(2);
	$supplier = new Supplier($_REQUEST['cid']);
	
	$direct = "supplier_registered.php";
	if(isset($_REQUEST['direct'])) $direct = $_REQUEST['direct'];


	$form = new Form($_SERVER['PHP_SELF']);
	$form->Icons['valid'] = '';
	
	$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Direct', 'hidden', $supplier->ID, 'paragraph', 1, 255);
	$form->AddField('direct', 'Direct', 'hidden', $direct, 'paragraph', 1, 255);
	
	$form->AddField('department', 'Department', 'text', '', 'alpha_numeric', 1, 40, false);
	$form->AddField('position', 'Position', 'text', '', 'alpha_numeric', 1, 100);
	$form->AddField('name', 'Business Name', 'text', '', 'alpha_numeric', 1, 100);
	
	$form->AddField('type', 'Business Type', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('type', '', 'Select...');
	$type = new DataQuery("select * from organisation_type order by Org_Type asc");
	while($type->Row){
		$form->AddOption('type', $type->Row['Org_Type_ID'], $type->Row['Org_Type']);
		$type->Next();
	}
	$type->Disconnect();
	unset($type);
	
	$form->AddField('industry', 'Your Industry', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('industry', '', 'Select...');
	$industry = new DataQuery("select * from organisation_industry order by Industry_Name asc");
	while($industry->Row){
		$form->AddOption('industry', $industry->Row['Industry_ID'], $industry->Row['Industry_Name']);
		$industry->Next();
	}
	$industry->Disconnect();
	unset($industry);
	
	$form->AddField('reg', 'Company Registration', 'text', '', 'alpha_numeric', 1, 50, false);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$form->Validate();	
		if($form->Valid){
			$supplier->Contact->Get();
			$supplier->Contact->Person->Department = $form->GetValue('department');
			$supplier->Contact->Person->Position = $form->GetValue('position');
			
			$contact = new Contact;
			$contact->Type = 'O';
			$contact->IsSupplier = 'Y';
			$contact->Organisation->Name = $form->GetValue('name');
			$contact->Organisation->Type->ID = $form->GetValue('type');
			$contact->Organisation->Industry->ID = $form->GetValue('industry');
			$contact->Organisation->Address = $supplier->Contact->Person->Address;
			$contact->Organisation->Address->ID = NULL;
			$contact->Organisation->Phone1 = $supplier->Contact->Person->Phone1;
			$contact->Organisation->Email = $supplier->Contact->Person->Email;
			$contact->Organisation->CompanyNo = $form->GetValue('reg');
			$contact->Add();
			
			$supplier->Contact->Parent->ID = $contact->ID;	
			$supplier->Contact->Update();	
				
			redirect("Location: " . $direct);
		}
	}

	// Initiate the Pager
	$page = new Page('Create an Organisation account', 'Your account is currently classed as an individual and will emain a Home account till you have successfully completed the Create a Organisation Account Form. Please complete the form below.');
	$page->Display('header');
	
	$window = new StandardWindow('Add');

?>
			<p>Required fields are marked with an asterisk (*).</p>
			<?php 
				// Show Error Report if Form Object validation fails
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
				echo $form->Open(); 
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
				echo $form->GetHtml('direct');
				echo $form->GetHtml('cid');
			?>
			
<?php
	echo $window->Open();
	echo $window->AddHeader('Your Business Profile');
	echo $window->OpenContent();
	
?>

			<table class="form" cellspacing="0">
				<tr>
					<td><?php echo $form->GetLabel('department'); ?></td>
					<td><?php echo $form->GetHtml('department'); ?> <?php echo $form->GetIcon('department'); ?></td>
				</tr>
				<tr>
					<td><?php echo $form->GetLabel('position'); ?></td>
					<td><?php echo $form->GetHtml('position'); ?> <?php echo $form->GetIcon('position'); ?></td>
				</tr>
			</table>
			<br />
<?php
	echo $window->CloseContent();
	echo $window->AddHeader('Your Business Details');
	echo $window->OpenContent();
?>
			<table class="form" cellspacing="0">
				<tr>
					<td><?php echo $form->GetLabel('name'); ?></td>
					<td><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
				</tr>
				<tr>
					<td><?php echo $form->GetLabel('type'); ?></td>
					<td><?php echo $form->GetHtml('type'); ?> <?php echo $form->GetIcon('type'); ?></td>
				</tr>
				<tr>
					<td><?php echo $form->GetLabel('industry'); ?></td>
					<td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
				</tr>
				<tr>
					<td><?php echo $form->GetLabel('reg'); ?></td>
					<td><?php echo $form->GetHtml('reg'); ?> <?php echo $form->GetIcon('reg'); ?></td>
				</tr>
			</table>
			<p align="right">
			  <br />
			  <input name="Continue" type="submit" class="btn" id="Continue" value="Continue" />
			</p>
<?php
	echo $window->CloseContent();
	echo $window->Close();
?>
			<?php echo $form->Close(); ?></td>
<?php
	$page->Display('footer');
require_once('lib/common/app_footer.php');
?>