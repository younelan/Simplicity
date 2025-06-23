<html lang="en">
<?php //$this->load->helper('url');
?>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.js" crossorigin="anonymous"></script>
	<script>
		/*window.jQuery || document.write('<script src="{themepath}/js/vendor/jquery-slim.min.js"><\/script>')*/
	</script>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="icon" href="{favicon}">

	<title><?= $categoryname ?></title>

	<!-- Bootstrap core CSS -->
	<link href="/res/bootstrap/bootstrap.min.css" rel="stylesheet">
	<link href="/res/css/main.css" rel="stylesheet">
	<!-- site CSS -->
	<!--
	<link rel='stylesheet' type="text/css" href='<?= base_url(); ?>res/css/explorer.css' />
	-->
	<link rel='stylesheet' type="text/css" href='<?= base_url() ?>/res/explorer.css' />
	<link rel='stylesheet' type="text/css" href='<?= base_url() ?>/screen.css' />

</head>

<div class=topnav>
	<a href="/"><img class='topimg' src="/res/dcexplorer.jpg"></a>
	<span class=toptitle>
		<?= $categoryname ?>
	</span>
</div>
<div id=sidebar>
	&nbsp;
</div>
<div id=content>
	<style>
		.listview {
			list-style-type: none;
		}

		.listview li {
			list-style-type: none;
			/*height:50px;*/
			line-height: 20px;
		}

		.listview li a {
			display: block;
			height: 100%;
		}

		.list-group li:hover {
			background-color: #ffc;
		}
	</style>
	<?php
	print '<div style="margin:0px auto;border-right: 0px transparent outset;min-width:300px; max-width:600px; width:100%">';
	if ($categories) {
	?>

		<ul class="list-group">
			<li class="list-group-item list-group-item-primary">Categories</li>

			<?php

			foreach ($categories as $url => $row) {
				if (isset($row['icon']) && $row['icon'])
					$icon = $row['icon'];
				else
					$icon = 'empty.gif';
			?>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<?php
					$iconurl = '';
					$itemCount = array();
					if ($row['catcount']) $itemCount[] = $row['catcount'] . ' cat ';
					if ($row['poicount']) $itemCount[] = $row['poicount'] . ' poi ';
					if ($itemCount)
						$itemCount = implode('/', $itemCount);
					else
						$itemCount = '&nbsp;';
					if ($icon <> '') {
						$iconurl = '<img class="ui-li-icon" align=left style="top:0.3em;border:1px solid black;" src=' . base_url() . '/res/icons/' . $icon . ">\n";
					}
					echo "<span class=''>"
						. anchor($url, $row['catname'] . $iconurl, 'rel=external')
						. "</span>";

					?>
					<span class="badge badge-primary badge-pill">
						<?= $itemCount ?>
					</span>
				</li>
		<?php
				//print('<pre>');
				//print_r($row);
			}
		}
		?>

		</ul>

		<?php
		$count = count($links);
		//print $count;


		if (is_array($links) && $count > 0) {

		?>

			<br />
			<ul class="list-group">
				<li class="list-group-item list-group-item-primary">Points of Interest</li>

			<?php
		}
		foreach ($links as $linkurl => $row) {
			$address = null;

			if ($row['address']) $address[] = $row['address'];
			if ($row['zipcode']) $address[] = $row['zipcode'];
			if ($address) {
				$address = ' <strong>Address: </strong> ' . implode(',', $address) . ' ';
			} else
				$address = '';
			if ($row['phone'])
				$phone = ' <strong>Phone: </strong>' . $row['phone']  . ' ';
			else
				$phone = '';
			?>
				<li class="list-group-item list-group-item-action flex-column align-items-start">
					<?= anchor("/explorer/item/" . $row['id'], $row['title']) ?>
					<div><?= $row['body'] ?>
					</div>

					<div>
						<?= $phone ?><?= $address ?>
					</div>
					<div>
						<?= $row['publicaccess'] ?>
					</div>
				</li>
			<?php
		}
			?>
			<div class=bottomfooter>&nbsp;&nbsp;&copy; DCExplorer.com 2012- <?php echo @date('Y'); ?>. DCExplorer is also available via SMS: Text <span class='yellow'>2026013575</span> with the keyword <span class='yellow'>start</span> </div>
</div>



</div>
</ul>
</div>
</body>

</html>
<?php

exit;

?>