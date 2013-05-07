<form name="searchForm" id="searchForm" method="get" action="search.php" >
<table class="searchcontainer"><tr><td>
<input name="search" id="searchinput1" placeholder="Search BLT Direct" type="text" class="search" value="<?php echo htmlentities(param('search', '')); ?>" />&nbsp;<input type="submit" name="submit" value="Search" class="searchbtn" /></td></tr>
</table></form>
