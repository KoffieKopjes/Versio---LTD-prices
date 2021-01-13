<?php
/*
 * Created by Gino Otten
 * Run cron jon every 15 min or more
 */

// Set username and password
$url = 'https://www.versio.nl/testapi/v1/tld/info';
$username = '';
$password = '';

// MySql Details
$db['host'] = 'localhost';
$db['port'] = '3306';
$db['db'] = 'hosting';
$db['user'] = 'root';
$db['pass'] = '';

// basic domain marge
$basicmarge = 1.50;

// Reset Mode
$firstrun = true; // Disable after first time

// Niets meer aanpassen
try {
    $dbh = new PDO('mysql:host='.$db['host'].':'.$db['port'].';dbname='.$db['db'].'', $db['user'], $db['pass']);
}
catch (PDOException $e) {
    echo ("<div style='background-repeat: no-repeat;
		background-position: 10px 50%;
		padding: 10px 10px 10px 10px;
		-moz-border-radius: 5px;
		border-radius: 5px;
		-moz-box-shadow: 0 1px 1px #fff inset;
		box-shadow: 0 1px 1px #fff inset;
		border: 1px solid maroon !important;
		color: #000;
		background: pink;
		display: table;
		margin: 0 auto;
		font-size: 15px;
		font-family: Tahoma;'>Je moet je database goedzetten mate.</div>");
    die();
}
if($firstrun) {
    $sql = "CREATE TABLE `domain_resell_price` (
      `tld` varchar(20) NOT NULL,
      `versio_price_registration` decimal(10,2) DEFAULT NULL,
      `versio_price_transfer` decimal(10,2) DEFAULT NULL,
      `versio_price_renewal` decimal(10,2) DEFAULT NULL,
      `minimum_years_required` varchar(50) DEFAULT NULL,
      `validation_required` varchar(50) DEFAULT NULL,
      `domain_lock` varchar(50) DEFAULT NULL,
      `sell_price_registration` decimal(10,2) DEFAULT NULL,
      `sell_price_transfer` decimal(10,2) DEFAULT NULL,
      `sell_price_renewal` decimal(10,2) DEFAULT NULL,
      `marge` decimal(10,2) DEFAULT NULL,
      `last_update` int(60) DEFAULT NULL,
      `active` enum('1','0') DEFAULT NULL,
      PRIMARY KEY (`tld`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $dbh->prepare($sql)->execute();
}
// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
// This time we only want to get the categories
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_URL => $url,
    CURLOPT_HTTPAUTH => CURLAUTH_ANY,
    CURLOPT_USERPWD => "$username:$password"
));
// Send the request & save response to $resp
$resp = curl_exec($curl);
// Get status code of the response
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// Close request to clear up some resources
curl_close($curl);
// Do something with the response (if you echo it, you will see it's in JSON format) and the status code
?>
<table border="1">
<tr>
    <th>tld</th>
    <th>versio_price_registration</th>
    <th>versio_price_transfer</th>
    <th>versio_price_renewal</th>
    <th>minimum_years_required</th>
    <th>validation_required</th>
    <th>domain_lock</th>
    <th>sell_price_registration</th>
    <th>sell_price_registration</th>
    <th>sell_price_renewal</th>
    <th>marge</th>
</tr>
<?php
    $decode = json_decode($resp);
    foreach ($decode->tldInfo as $domain) {
        $tld = $domain->tld;
        $price_registration  = $domain->price_registration;
        $price_transfer = $domain->price_transfer;
        $price_renewal = $domain->price_renewal;
        $minimum_years_required = $domain->minimum_years_required;
        $validation_required = $domain->validation_required;
        $domain_lock = $domain->domain_lock;
    // Check if tld already exists
        $stmt = $dbh->prepare("SELECT * FROM domain_resell_price WHERE tld=:tld");
        $stmt->bindParam(":tld", $tld);
        $stmt->execute();
        $domaindetails = $stmt->fetch();
        if($stmt->rowCount() > 0){
            // Update TLD
            $sell_price_registration = $price_registration * $domaindetails['marge'];
            $sell_price_transfer = $price_transfer * $domaindetails['marge'];
            $sell_price_renewal = $price_renewal * $domaindetails['marge'];
            $marge = $domaindetails['marge'];
            $date = strtotime('now');
            $sql = "UPDATE domain_resell_price SET versio_price_registration=?, versio_price_transfer=?, versio_price_renewal=?, minimum_years_required=?, validation_required=?, domain_lock=?, sell_price_registration=?, sell_price_transfer=?, sell_price_renewal=?, marge=?, last_update=?, active=? WHERE tld=?";
            $dbh->prepare($sql)->execute([$price_registration, $price_transfer, $price_renewal, $minimum_years_required, $validation_required, $domain_lock, $sell_price_registration, $sell_price_transfer, $sell_price_renewal, $marge, $date, 1, $tld]);
        } else {
            // Calculate sell marge (basic);
            $sell_price_registration = $price_registration * $basicmarge;
            $sell_price_transfer = $price_transfer * $basicmarge;
            $sell_price_renewal = $price_renewal * $basicmarge;
            $date = strtotime('now');
            // Insert TLD
            $sql = "INSERT INTO domain_resell_price (tld, versio_price_registration, versio_price_transfer, versio_price_renewal, minimum_years_required, validation_required, domain_lock, sell_price_registration, sell_price_transfer, sell_price_renewal, marge, last_update, active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $dbh->prepare($sql)->execute([$tld, $price_registration, $price_transfer, $price_renewal, $minimum_years_required, $validation_required, $domain_lock, $sell_price_registration, $sell_price_transfer, $sell_price_renewal, $basicmarge, $date, 1]);
        }
    }

    // Check if there are domains are removed from Versio
    $stmt = $dbh->prepare("SELECT * FROM domain_resell_price");
    $stmt->bindParam(":tld", $tld);
    $stmt->execute();
    foreach($stmt as $d){
        if($d['last_update'] < strtotime("- 10 min")){
            $sql = "UPDATE domain_resell_price SET versio_price_registration=? WHERE tld=?";
            $dbh->prepare($sql)->execute([$d['tld'], 0]);
            echo $d['tld'] . ' is disabled aangezien Versio deze niet meer verkoopt. <br>';
        }
    }

?>
</table>