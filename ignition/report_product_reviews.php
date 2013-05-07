<?php
require_once('lib/common/app_header.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'approve') {
	$session->Secure(3);
	approve();
	exit;
} else {
	$session->Secure(2);
	report();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReview.php');

	if(isset($_REQUEST['id'])) {
		$review = new ProductReview();
		$review->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function approve() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReview.php');

	if(isset($_REQUEST['id'])) {
		$review = new ProductReview($_REQUEST['id']);
		$review->Approve();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReview.php');

	$review = array();

	$data = new DataQuery(sprintf("SELECT pr.*, p.Product_Title, CONCAT_WS(' ', pe.Name_First, pe.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Customer_Name, AVG(pr2.Rating) AS Average_Rating FROM product_review AS pr INNER JOIN product AS p ON pr.Product_ID=p.Product_ID INNER JOIN customer AS cu ON cu.Customer_ID=pr.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID INNER JOIN person AS pe ON pe.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN product_review AS pr2 ON pr2.Product_ID=p.Product_ID AND pr.Is_Approved='Y' WHERE pr.Is_Approved='N' GROUP BY pr.Product_Review_ID ORDER BY pr.Created_On ASC"));
	while($data->Row) {
		$review[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	foreach($review as $reviewItem) {
		$form->AddField('review_' . $reviewItem['Product_Review_ID'], 'Review for \'' . $reviewItem['Product_Title'] . '\'', 'textarea', $reviewItem['Review'], 'anything', 1, 8192, true, 'style="width: 100%; font-family: arials, sans-serif; font-size: 8pt;" rows="5"');
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($review as $reviewItem) {
				new DataQuery(sprintf("UPDATE product_review SET Review='%s' WHERE Product_Review_ID=%d", mysql_real_escape_string($form->GetValue('review_' . $reviewItem['Product_Review_ID'])), mysql_real_escape_string($reviewItem['Product_Review_ID'])));
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('Product Review Report', '');
	$page->Display('header');
	?>

	<br />
	<h3>Unapproved Product Reviews</h3>
	<p>List of reviews awaiting approval or rejection.</p>

	<?php
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	?>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Created On</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Rating</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Review</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Rating</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left" width="1%">&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa" align="left" width="1%">&nbsp;</td>
		</tr>

		<?php
		if(count($review) > 0) {
			foreach($review as $reviewItem) {
				$stars = number_format($reviewItem['Rating'] * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');
				$rating = '';

				for($i=0; $i<floor($stars); $i++) {
					$rating .= sprintf('<img src="/images/rating_star_2.gif" align="absmiddle" alt="%s out of %s stars" />', $stars, $GLOBALS['PRODUCT_REVIEW_RATINGS']);
				}

				if(($stars) <> floor($stars)) {
					$rating .= sprintf('<img src="/images/rating_star_3.gif" align="absmiddle" alt="%s out of %s stars" />', $stars, $GLOBALS['PRODUCT_REVIEW_RATINGS']);
				}

				for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS'] - ceil($stars); $i++) {
					$rating .= sprintf('<img src="/images/rating_star_1.gif" align="absmiddle" alt="%s out of %s stars" />', $stars, $GLOBALS['PRODUCT_REVIEW_RATINGS']);
				}
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo cDatetime($reviewItem['Created_On'], 'shortdatetime'); ?></td>
					<td><?php echo $reviewItem['Product_Title']; ?></td>
					<td align="right"><?php echo !empty($reviewItem['Average_Rating']) ? number_format($reviewItem['Average_Rating']*$GLOBALS['PRODUCT_REVIEW_RATINGS'], 2, '.', ',') : ''; ?></td>
					<td><?php echo $reviewItem['Customer_Name']; ?></td>
					<td><?php echo $form->GetHTML('review_' . $reviewItem['Product_Review_ID']); ?></td>
					<td width="1%" nowrap="nowrap"><?php echo $rating; ?></td>
					<td><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&id=<?php echo $reviewItem['Product_Review_ID']; ?>', 'Are you sure you wish to remove this review?');"><img src="images/aztector_6.gif" width="16" height="16" alt="Remove Review" border="0" /></a></td>
					<td><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=approve&id=<?php echo $reviewItem['Product_Review_ID']; ?>', 'Are you sure you wish to approve this review?');"><img src="images/aztector_5.gif" width="16" height="16" alt="Approve Review" border="0" /></a></td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr>
				<td align="center" colspan="7">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<input type="submit" class="btn" name="update" value="update" />

	<?php
	echo $form->Close();

	$page->Display('footer');
}
?>