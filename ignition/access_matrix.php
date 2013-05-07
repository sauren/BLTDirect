<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/RegistryPermissions.php');

$session->Secure(3);

$access = array();
$registry = array();
$permission = array();

$data = new DataQuery(sprintf("SELECT * FROM access_levels ORDER BY Access_Level ASC"));
while($data->Row) {
	$access[] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT * FROM registry ORDER BY Script_Name ASC"));
while($data->Row) {
	$registry[] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT r.Registry_ID, rp.Access_ID FROM registry AS r INNER JOIN registry_permissions AS rp ON rp.Registry_ID=r.Registry_ID"));
while($data->Row) {
	$permission[] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(isset($_REQUEST['confirm'])) {
	foreach($registry as $registryItem) {
		foreach($access as $accessItem) {
			$hasAccess = false;
					
			for($i=0; $i<count($permission); $i++) {
				if(($permission[$i]['Registry_ID'] == $registryItem['Registry_ID']) && ($permission[$i]['Access_ID'] == $accessItem['Access_ID'])) {
					$hasAccess = true;
				}
			}
			
			if(isset($_REQUEST[$accessItem['Access_ID'].'-'.$registryItem['Registry_ID']]) && !$hasAccess) {
				$data = new RegistryPermissions();
				$data->registry =  $registryItem['Registry_ID'];
				$data->access = $accessItem['Access_ID'];
				$data->permission = 3;
				$data->add();
			} elseif(!isset($_REQUEST[$accessItem['Access_ID'].'-'.$registryItem['Registry_ID']]) && $hasAccess) {
				$data = new RegistryPermissions();
				$data->registry = $registryItem['Registry_ID'];
				$data->access = $accessItem['Access_ID'];
				$data->deleteByRegistryAccess();
			}
		}
	}
	
	redirectTo('?');
}

$page = new Page('Access Matrix', 'Manage multiple permissions within one form.');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	<thead>
		<tr>
			<th></th>
			
			<?php
			foreach($access as $accessItem) {
				echo sprintf('<th>%s</th>', $accessItem['Access_Level']);
			}			
			?>
		</tr>
	</thead>
	<tbody>
	
		<?php
		foreach($registry as $registryItem) {
			?>
			
			<tr>
				<td><?php echo $registryItem['Script_Name']; ?></td>
				
				<?php
				foreach($access as $accessItem) {
					$hasAccess = false;
					
					for($i=0; $i<count($permission); $i++) {
						if(($permission[$i]['Registry_ID'] == $registryItem['Registry_ID']) && ($permission[$i]['Access_ID'] == $accessItem['Access_ID'])) {
							$hasAccess = true;
						}
					}
					
					echo sprintf('<td align="center"><input type="checkbox" name="%d-%d"%s /></td>', $accessItem['Access_ID'], $registryItem['Registry_ID'], $hasAccess ? ' checked="checked"' : '');
				}			
				?>
			</tr>
			
			<?php
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