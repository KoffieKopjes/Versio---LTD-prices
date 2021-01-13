<?php
$url = 'https://www.versio.nl/testapi/v1/tld/info';


// Set username and password
$username = '-';
$password = '-';
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
    <th>price_registration</th>
    <th>price_transfer</th>
    <th>price_renewal</th>
    <th>minimum_years_required</th>
    <th>validation_required</th>
    <th>domain_lock</th>
</tr>
<?php
$test = json_decode($resp);
foreach ($test->tldInfo as $domain){
    echo '
    <tr>
        <td>' . $domain->tld . '</td>
        <td>' . $domain->price_registration . '</td>
        <td>' . $domain->price_transfer . '</td>
        <td>' . $domain->price_renewal . '</td>
        <td>' . $domain->minimum_years_required . '</td>
        <td>' . $domain->validation_required . '</td>
        <td>' . $domain->domain_lock . '</td>
    </tr>
';
}
?>
</table>