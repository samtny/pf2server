<?PHP

include('pf-config.php');

// mostly from here; http://www.devarticles.com/c/a/MySQL/PHP-MySQL-and-Authentication-101/3/

$db = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Couldn't connect to the database.");
mysql_select_db(DB_NAME) or die("Couldn't select the database");

// Add slashes to the username, and make a md5 checksum of the password.
$_POST['user'] = addslashes($_POST['user']);
$_POST['pass'] = md5($_POST['pass']);

$result = mysql_query("SELECT count(userid) FROM user WHERE password='$_POST[pass]' AND username='$_POST[user]'") or die("Couldn't query the user-database.");
$num = mysql_result($result, 0);

if (!$num) {
	
	// When the query didn't return anything,
	// display the login form.
	
	echo "<h3>User Login</h3>
	<form action='$_SERVER[PHP_SELF]' method='post'>
	Username: <input type='text' name='user'><br>
	Password: <input type='password' name='pass'><br><br>
	<input type='submit' value='Login'>
	</form>";
	
} else {
	
	// Start the login session
	session_start();
	
	// We've already added slashes and MD5'd the password
	$_SESSION['user'] = $_POST['user'];
	$_SESSION['pass'] = $_POST['pass'];
	
	echo "<a href='pf-mgmt.php'>Managment</a>";
	
}

?>
