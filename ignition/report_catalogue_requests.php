<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

if($action == 'clear') {
	$session->Secure(3);
	clear();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function clear() {
	$data = new DataQuery(sprintf("SELECT c.Contact_ID, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE c.Is_Catalogue_Requested='Y' ORDER BY o.Org_Name ASC, Contact_Name ASC"));
	while($data->Row) {
		$contact = new Contact($data->Row['Contact_ID']);
		$contact->IsCatalogueRequested = 'N';
		$contact->Update();

		$data->Next();	
	}
	$data->Disconnect();

	redirectTo('?action=view');
}

function view() {
	$page = new Page('Catalogue Requests Report', '');
	$page->Display('header');
	?>

	<h3>Catalogue Requests</h3>
	<p>Listing all catalogue requests.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Organisation</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Contact</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT c.Contact_ID, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE c.Is_Catalogue_Requested='Y' ORDER BY o.Org_Name ASC, Contact_Name ASC"));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr>
					<td><?php echo $data->Row['Org_Name']; ?></td>
					<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo $data->Row['Contact_Name']; ?></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>
			
			<tr>
				<td colspan="2" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php
		}
		$data->Disconnect();
		?>

	</table>
	<br />

	<input type="button" class="btn" name="send" value="send" onclick="popUrl('report_catalogue_requests_print.php', 800, 600);" />
	<input type="button" class="btn" name="clear" value="clear" onclick="window.self.location.href = '?action=clear';" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}