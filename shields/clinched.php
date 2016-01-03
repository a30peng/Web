<?php
$user = "null";

if (array_key_exists("u", $_GET)) {
    $user = $_GET['u'];
    setcookie("lastuser", $user, time() + (86400 * 30), "/");
} else if (isset($_COOKIE['lastuser'])) {
    header("Location: /user?u=" . $_COOKIE['lastuser']); /* Redirect browser */
    exit();
}

$dbname = "TravelMapping";
if (isset($_COOKIE['currentdb'])) {
    $dbname = $_COOKIE['currentdb'];
}

if (array_key_exists("db", $_GET)) {
    $dbname = $_GET['db'];
    setcookie("currentdb", $dbname, time() + (86400 * 30), "/");
}

$db = new mysqli("localhost", "travmap", "clinch", $dbname) or die("Failed to connect to database");
$sql = "SELECT * FROM connectedRoutes WHERE "
?>
<html>
<head>
    <title>Clinched Routes for <?php echo $user ?></title>
    <link rel="stylesheet" type="text/css" href="/css/travelMapping.css">
</head>
<body>
    <a href="/">Home</a> -
    <a href="/hbtest">Highway Browser</a> -
    <?php echo "<a href='/user?u={$user}'>{$user}</a>" ?>

    <form id="userselect">
        <label>User: </label>
        <input type="text" name="u" form="userselect" value="<?php echo $user ?>">
        <input type="submit">
    </form>
    <h1>Clinched Routes for <?php echo $user ?>: </h1>
    <table>
    <?php
    $sql = <<<SQL
      SELECT sys.fullName,
        group_concat(ccr.route ORDER BY SUBSTRING(ccr.route, LOCATE('.', ccr.route))) AS clinchedRoutes,
        count(cr.route) as total,
        sum(ccr.clinched) as clinched
      FROM connectedRoutes as cr
      LEFT JOIN systems AS sys ON sys.systemName = cr.systemName
      LEFT JOIN clinchedConnectedRoutes AS ccr ON cr.firstRoot = ccr.route AND ccr.traveler = '{$user}' AND ccr.clinched = 1
      GROUP BY sys.systemName
      HAVING (sum(ccr.clinched) >= 1)
      ORDER BY sys.tier, sys.systemName;
SQL;
    //echo $sql;
    $db->query("SET SESSION  group_concat_max_len = 5555555;");
    $res = $db->query($sql);
    while($row = $res->fetch_assoc()) {
        echo "<h4>{$row['fullName']} ({$row['clinched']} / {$row['total']})</h4>";
        $rootList = explode(",", $row['clinchedRoutes']);
        $col = 0;
        foreach($rootList as $root) {
            echo "<a href='/devel/hb.php?r={$root}'><img src='/shields?r={$root}' height='36' width='45'/></a>";
            $col++;
            if ($col > 8) {
                echo "<br/>";
                $col = 0;
            }
            echo "<!--{$root}-->\n";
        }
    }
    $res->free();
    ?>
    </table>
</body>
</html>