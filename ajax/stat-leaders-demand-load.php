<?php
// Make sure to include path.php first to define BASE_URL
include_once '../path.php';
include_once '../includes/functions.php';

$type = $_POST['type'];
$category = $_POST['category'];
$season = $_POST['season'];
$loadOnDemand = isset($_POST['loadOnDemand']) ? filter_var($_POST['loadOnDemand'], FILTER_VALIDATE_BOOLEAN) : false;

$output = renderStatHolder($type, $category, $season, $loadOnDemand);
echo $output;
?>
