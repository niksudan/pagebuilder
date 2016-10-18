<?php

// This page is used for loading PageBuilder on the admin page

if ( isset($_GET['enabled']) && $_GET['enabled'] == 'true' ) :

	require('pagebuilder.class.php');

	$page = new PageBuilder();

	if (isset($_GET['data'])) {
		$data = $_GET['data'];
		$page->loadData(stripslashes($data));
	}

	?>

	<style>
		#pbDisabled, #postdivrich {
			display: none !important;
		}
		#pb_meta_preview {
			display: block !important;
		}
	</style>

	<?= $page->renderAdmin() ?>

<?php else : ?>

	<style>
		#pbDisabled, #postdivrich {
			display: block !important;
		}
		#pb_meta_preview {
			display: none !important;
		}
	</style>

<?php endif; ?>