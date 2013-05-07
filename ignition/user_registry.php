<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserRegistry.php');

if($action == 'matrix') {
	$session->Secure(3);
	matrix();
	exit;
} else {
	$session->Secure(3);
	start();
	exit;
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('user', 'Users', 'selectmultiple', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY User ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['User']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$users = array();

			foreach($form->GetValue('user') as $user) {
				$users[] = sprintf('user[]=%d', $user);
			}

			redirect(sprintf('Location: ?action=matrix%s', !empty($users) ? sprintf('&%s', implode('&', $users)) : ''));
		}
	}

	$page = new Page('User Registry', 'Select users for managing registry permissions.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Select users.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function matrix() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'matrix', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('user', 'Users', 'hidden-array', '', 'numeric_unsigned', 1, 11);

	$user = array();
	$registry = array();
	$permission = array();
	$used = array();

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.User_ID IN (%s) ORDER BY User ASC", implode(', ', $form->GetValue('user'))));
	while($data->Row) {
		$user[] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT * FROM registry ORDER BY Script_Name ASC"));
	while($data->Row) {
		$registry[] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT ur.registryId, ur.userId FROM registry AS r INNER JOIN user_registry AS ur ON ur.registryId=r.Registry_ID"));
	while($data->Row) {
		$permission[] = $data->Row;
		
		$data->Next();	
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		foreach($registry as $registryItem) {
			foreach($user as $userItem) {
				$hasAccess = false;
				
				for($i=0; $i<count($permission); $i++) {
					if(($permission[$i]['registryId'] == $registryItem['Registry_ID']) && ($permission[$i]['userId'] == $userItem['User_ID'])) {
						$hasAccess = true;
					}
				}
				
				if(isset($_REQUEST[$userItem['User_ID'].'-'.$registryItem['Registry_ID']]) && !$hasAccess) {
					$data = new UserRegistry();
					$data->userId = $userItem['User_ID'];
					$data->registryId = $registryItem['Registry_ID'];
					$data->add();
					
				} elseif(!isset($_REQUEST[$userItem['User_ID'].'-'.$registryItem['Registry_ID']]) && $hasAccess) {
					new DataQuery(sprintf("DELETE FROM user_registry WHERE userId=%d AND registryId=%d", mysql_real_escape_string($userItem['User_ID']), mysql_real_escape_string($registryItem['Registry_ID'])));
				}
			}
		}
		
		$users = array();

		foreach($form->GetValue('user') as $user) {
			$users[] = sprintf('user[]=%d', $user);
		}

		redirect(sprintf('Location: ?action=matrix%s', !empty($users) ? sprintf('&%s', implode('&', $users)) : ''));
	}

	$page = new Page('User Registry (Matrix)', 'Manage registry for these users.');
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('user');
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th></th>
				
				<?php
				foreach($user as $userItem) {
					echo sprintf('<th>%s</th>', $userItem['User']);
				}			
				?>
			</tr>
		</thead>
		<tbody>
		
			<?php
			$used = outputNavigation(0, $user, $registry, $permission, $used);
			$level = 0;

			if(count($registry) > count($used)) {
				?>

				<tr>
					<td colspan="<?php echo count($user)+1; ?>" style="background-color: #ddd;"><span style="padding-left: <?php echo $level*20; ?>px;">Other</span></td>
				</tr>

				<?php
			}

			foreach($registry as $registryItem) {
				if(!isset($used[$registryItem['Registry_ID']])) {
					?>
					
					<tr>
						<td><span style="padding-left: <?php echo $level*20; ?>px;"><?php echo $registryItem['Script_Name']; ?></span></td>
						
						<?php
						foreach($user as $userItem) {
							$hasAccess = false;
							
							for($i=0; $i<count($permission); $i++) {
								if(($permission[$i]['registryId'] == $registryItem['Registry_ID']) && ($permission[$i]['userId'] == $userItem['User_ID'])) {
									$hasAccess = true;
								}
							}
							
							echo sprintf('<td align="center"><input type="checkbox" name="%d-%d"%s /></td>', $userItem['User_ID'], $registryItem['Registry_ID'], $hasAccess ? ' checked="checked"' : '');
						}
						?>
					</tr>
					
					<?php
				}
			}
			?>

		</tbody>
	</table>
	<br />

	<input type="submit" name="update" value="update" class="btn" />

	<?php
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function outputNavigation($nodeId, $user, $registry, $permission, $used, $level = 0) {
	$data = new DataQuery(sprintf("SELECT * FROM treemenu WHERE Parent_ID=%d ORDER BY Caption ASC", mysql_real_escape_string($nodeId)));
	while($data->Row) {
		if(!empty($data->Row['Url'])) {
			foreach($registry as $registryItem) {
				if($data->Row['Url'] == $registryItem['Script_File']) {
					$used[$registryItem['Registry_ID']] = true;
					?>
					
					<tr>
						<td><span style="padding-left: <?php echo $level*20; ?>px;"><?php echo $registryItem['Script_Name']; ?></span></td>
						
						<?php
						foreach($user as $userItem) {
							$hasAccess = false;
							
							for($i=0; $i<count($permission); $i++) {
								if(($permission[$i]['registryId'] == $registryItem['Registry_ID']) && ($permission[$i]['userId'] == $userItem['User_ID'])) {
									$hasAccess = true;
								}
							}
							
							echo sprintf('<td align="center"><input type="checkbox" name="%d-%d"%s /></td>', $userItem['User_ID'], $registryItem['Registry_ID'], $hasAccess ? ' checked="checked"' : '');
						}
						?>
					</tr>
					
					<?php
				}
			}
		} elseif(empty($data->Row['Url'])) {
			?>
			
			<tr>
				<td colspan="<?php echo count($user)+1; ?>" style="background-color: #ddd;"><span style="padding-left: <?php echo $level*20; ?>px;"><?php echo $data->Row['Caption']; ?></span></td>
			</tr>
			
			<?php
			$used = outputNavigation($data->Row['Node_ID'], $user, $registry, $permission, $used, $level+1);
		}

		$data->Next();
	}
	$data->Disconnect();

	return $used;
}