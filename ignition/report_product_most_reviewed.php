<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReview.php');

$review = array();

$data = new DataQuery(sprintf("SELECT COUNT(pr.Product_Review_ID) AS Review_Count, AVG(pr.Rating) AS Review_Average, p.Product_ID, p.Product_Title FROM product_review AS pr INNER JOIN product AS p ON pr.Product_ID=p.Product_ID WHERE pr.Is_Approved='Y' GROUP BY p.Product_ID ORDER BY Review_Count DESC"));
while($data->Row) {
	$review[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_review AS pr INNER JOIN product AS p ON pr.Product_ID=p.Product_ID WHERE pr.Is_Approved='Y'"));
$count = ($data->TotalRows > 0) ? $data->Row['Count'] : 0;
$data->Disconnect();

$page = new Page('Product Most Reviewed Report', '');
$page->Display('header');
?>

<br />
<h3>Most Reviewed Products</h3>
<p>List of reviews awaiting approval or rejection. Total reviews approved: <strong><?php echo $count; ?></strong>.</p>

<table width="100%" border="0" >
	<tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product ID</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Name</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Ratings</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Average</strong></td>
	</tr>

	<?php
	if(count($review) > 0) {
		foreach($review as $reviewItem) {
			$stars = round($reviewItem['Review_Average'] * $GLOBALS['PRODUCT_REVIEW_RATINGS']);
			$rating = '';

			for($i=0; $i<floor($stars); $i++) {
				$rating .= sprintf('<img src="/images/rating_star_2.gif" align="absmiddle" alt="%s out of %s stars" />', $stars, $GLOBALS['PRODUCT_REVIEW_RATINGS']);
			}

			for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS'] - ceil($stars); $i++) {
				$rating .= sprintf('<img src="/images/rating_star_1.gif" align="absmiddle" alt="%s out of %s stars" />', $stars, $GLOBALS['PRODUCT_REVIEW_RATINGS']);
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $reviewItem['Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $reviewItem['Product_ID']; ?>"><?php echo $reviewItem['Product_Title']; ?></a></td>
				<td><?php echo $reviewItem['Review_Count']; ?></td>
				<td><?php echo $rating; ?></td>
			</tr>

			<?php
		}
	} else {
		?>

		<tr>
			<td align="center" colspan="4">There are no items available for viewing.</td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');