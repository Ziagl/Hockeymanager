<?php
include 'content/session.php';
include 'content/header.php';
?>
<h2>Home Page</h2>
<p>Welcome back, <?=$_SESSION['name']?>!</p>
<?php
include 'content/footer.php';
?>