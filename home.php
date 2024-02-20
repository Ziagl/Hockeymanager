<?php
include_once 'config/config.php';
include 'content/session.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// if user has no team yet -> team selection
$stmt = $con->prepare('SELECT * FROM User WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_array();
$stmt->close();
if($user['dream_team_id'] == 0) {
	header('Location: teamselect.php');
	exit;
}
include 'content/header.php';
?>
<h2>Home Page</h2>
<p>Welcome back, <?=$_SESSION['name']?>!</p>
<?php
include 'content/footer.php';
?>