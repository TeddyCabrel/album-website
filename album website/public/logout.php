<?php
session_start();
// Destroy session data
session_destroy();
// Redirect to collections page
header('Location: collections.php');
?>