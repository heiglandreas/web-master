<?php # $Id$

/* Show the list of people caught up in the CR system
 * for the current user. */

require_once 'login.inc';
require_once 'functions.inc';
require_once 'email-validation.inc';

head("challenge response anti-spam thingamy");

@mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
@mysql_select_db("phpmasterdb");

if (isset($_POST['confirm_them']) && is_array($_POST['confirm'])) {
	foreach ($_POST['confirm'] as $address) {
		$addr = mysql_escape_string($address);
		db_query("insert into accounts.confirmed (email, ts) values ('$addr', NOW())");
	}
}

$res = db_query("select distinct sender from phpmasterdb.users left join accounts.quarantine on users.email = rcpt where username='$user' and not isnull(id)");

$inmates = array();
while ($row = mysql_fetch_array($res)) {
	$inmates[] = $row[0];
}

function sort_by_domain($a, $b)
{
	list($al, $ad) = explode('@', $a, 2);
	list($bl, $bd) = explode('@', $b, 2);

	$x = strcmp($ad, $bd);
	if ($x)
		return $x;

	return strcmp($al, $bl);
}

usort($inmates, 'sort_by_domain');

?>

<h1>Addresses in quarantine for <?= $user ?>@php.net</h1>

<form method="post">

<table>
	<tr>
		<td>&nbsp;</td>
		<td>Sender</td>
		<td>Domain</td>
	</tr>

<?php
foreach ($inmates as $prisoner) {
	list($localpart, $domain) = explode('@', $prisoner, 2);
?>
<tr>
	<td><input type="checkbox" name="confirm[]" value="<?= htmlentities($prisoner) ?>"/></td>
	<td align="right"><?= htmlentities($localpart) ?></td>
	<td align="left">@<?= htmlentities($domain) ?></td>
</tr>
<?php
}
?>
</table>

<p>
If you see an address listed here that you are 100% sure is a legitimate sender,
you may tick the appropriate box and confirm them.
</p>

<input type="submit" name="confirm_them" value="Confirm Ticked Senders"/>

</form>

<?php
$res = db_query("select count(id) from phpmasterdb.users left join accounts.quarantine on users.email = rcpt where username='$user'");
$n = @mysql_result($res, 0);

echo "You have <b>$n</b> messages in quarantine<br>";

foot();
?>