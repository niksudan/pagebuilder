<?php

// This page is used for rendering HTML from PageBuilder data

if (isset($_GET['data'])) {
	require('pagebuilder.class.php');
	$page = new PageBuilder();
	$page->loadData(json_encode($_GET['data']));
	echo $page->render();
}