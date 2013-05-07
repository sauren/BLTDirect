<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportExtract.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'download') {
	$session->Secure(2);
	download();
	exit;
} elseif($action == 'execute') {
	$session->Secure(2);
	execute();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$extract = new ReportExtract();
		$extract->delete($_REQUEST['id']);
	}

	redirectTo('?action=view');
}

function execute() {
	$extract = new ReportExtract();

	if(!isset($_REQUEST['id']) || !$extract->get($_REQUEST['id'])) {
		redirectTo('?action=view');
	}

	$data = new DataQuery($extract->query);

	$page = new Page('<a href="?action=view">Report Extracts</a> &gt; ' . $extract->name, 'Live extract data.');
	$page->Display('header');
	?>

	<input type="button" class="btn" name="download" value="download" onclick="window.self.location.href = '?action=download&id=<?php echo $extract->id; ?>';" />
	<br /><br />

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eee;">

			<?php
			foreach($data->Row as $key=>$value) {
				echo sprintf('<td style="border-bottom: 1px solid #ddd;"><strong>%s</strong></td>', str_replace('_', ' ', $key));
			}
			?>

		</tr>

		<?php
		while($data->Row) {
			?>

			<tr>

				<?php
				foreach($data->Row as $key=>$value) {
					echo '<td style="border-top: 1px solid #ddd;">';

					if(preg_match('/^product(_*)id$/i', $key)) {
						echo sprintf('<a href="product_profile.php?pid=%d">%s</a>', $value, $value);
					} elseif(preg_match('/^purchase(_*)id$/i', $key)) {
						echo sprintf('<a href="purchase_edit.php?pid=%d">%s</a>', $value, $value);
					} else {
						echo $value;	
					}

					echo '</td>';
				}
				?>

			</tr>

			<?php
			$data->Next();
		}
		?>

	</table>

	<?php
	$data->Disconnect();

	$page->Display('footer');
}

function download() {
	$extract = new ReportExtract();

	if(!isset($_REQUEST['id']) || !$extract->get($_REQUEST['id'])) {
		redirectTo('?action=view');
	}

	$fileDate = date('Y-m-d');
	$fileName = sprintf('%s_%s.csv', str_replace(' ', '_', strtolower($extract->name)), $fileDate);

	$data = new DataQuery($extract->query);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	$line = array();
	
	foreach($data->Row as $key=>$value) {
		$line[] = $key;
	}

	echo getCsv($line);

	while($data->Row) {
		$line = array();

		foreach($data->Row as $key=>$value) {
			$line[] = $value;
		}

		echo getCsv($line);

		$data->Next();
	}
	$data->Disconnect();
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function view() {
	$page = new Page('Report Extracts', 'Extract reports.');
	$page->Display('header');

	$table = new DataTable('extracts');
	$table->SetSQL("SELECT id, name FROM report_extract");
	$table->AddField('ID', 'id', 'left');
	$table->AddField('Name', 'name', 'left');
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->AddLink('?action=execute&id=%s', '<img src="images/folderopen.gif" alt="Execute" border="0" />', 'id');
	$table->AddLink('?action=download&id=%s', '<img src="images/icon_info_1.gif" alt="Download" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&id=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'id');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}