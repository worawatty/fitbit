<?php
/**
 * Created by PhpStorm.
 * User: Worawat
 * Date: 11/16/2017
 * Time: 3:16 PM
 */

require_once __DIR__ . '/vendor/autoload.php';
use djchen\OAuth2\Client\Provider\Fitbit;

$provider = new Fitbit([
    'clientId'          => '22CLGD',
    'clientSecret'      => '08816ee1bcbdd9b0d826a8a4faa6163c',
    'redirectUri'       => 'http://localhost:8080/fitbit/callback.php'
]);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fitbit";

echo $_GET['code'];
try {

    // Try to get an access token using the authorization code grant.
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // We have an access token, which we may use in authenticated
    // requests against the service provider's API.
    echo $accessToken->getToken() . "\n";
    echo $accessToken->getRefreshToken() . "\n";
    echo $accessToken->getExpires() . "\n";
    echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "\n";

    // Using the access token, we may look up details about the
    // resource owner.
    $resourceOwner = $provider->getResourceOwner($accessToken);
    $activityAPI = '/1/user/-/activities/date/'.date("Y-m-d").'.json';
    $activityIntraDayAPI = '/1/user/-/activities/calories/date/today/1d/15min/time/06:00/23:00.json';
    $foodLogAPI = '/1/user/-/foods/log/date/'.date('Y-m-d').'.json';
    $heartRateAPI = '/1/user/-/activities/heart/date/today/1d.json';
    $sleepLogAPI = '/1.2/user/-/sleep/date/'.date("Y-m-d").'.json';
    //var_export($resourceOwner->toArray());

    // The provider provides a way to get an authenticated API request for
    // the service, using the access token; it returns an object conforming
    // to Psr\Http\Message\RequestInterface.
    /*$request = $provider->getAuthenticatedRequest(
        Fitbit::METHOD_GET,
        Fitbit::BASE_FITBIT_API_URL . '/1/user/-/profile.json',

        $accessToken,
        ['headers' => [Fitbit::HEADER_ACCEPT_LANG => 'en_US'], [Fitbit::HEADER_ACCEPT_LOCALE => 'en_US']]
    // Fitbit uses the Accept-Language for setting the unit system used
    // and setting Accept-Locale will return a translated response if available.
    // https://dev.fitbit.com/docs/basics/#localization
    );*/
    // Make the authenticated API request and get the parsed response.
    $requestActivity=apiRequest($activityAPI,$provider,$accessToken);
    $requestFoodLog=apiRequest($foodLogAPI,$provider,$accessToken);
    $requestHeartRate=apiRequest($heartRateAPI,$provider,$accessToken);
    $requestSleepLog=apiRequest($sleepLogAPI,$provider,$accessToken);
    $requestActivityIntraDay=apiRequest($activityIntraDayAPI,$provider,$accessToken);

    //Request for the parsed Response
    $responseActivity = $provider->getParsedResponse($requestActivity);
    $responseFoodLog = $provider->getParsedResponse($requestFoodLog);
    $responseHeartRate = $provider->getParsedResponse($requestHeartRate);
    $responseSleepLog = $provider->getParsedResponse($requestSleepLog);
    $responseActivityIntraday = $provider->getParsedResponse($requestActivityIntraDay);


    //echo gettype($response);
    //var_export($response);
    //echo $response['user']['age'];
    $sumCalories = 0;
    foreach ($responseActivityIntraday['activities-calories-intraday']['dataset'] as $data){
        $sumCalories = $sumCalories+$data['value'];
    }
    $caloriesAverages =  averageCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],4,'value');
    foreach ($caloriesAverages as $value){
        echo $value."\n";
    }
    echo '<html>';
    echo '<pre>';
    print_r($responseActivity);
    print_r($responseFoodLog);
    print_r($responseHeartRate);
    print_r($responseSleepLog);
    print_r($responseActivityIntraday);
    echo '</pre>';
    echo '</html>';
    // If you would like to get the response headers in addition to the response body, use:
    //$response = $provider->getResponse($request);
    //echo $response;
    //$headers = $response->getHeaders();
    //$parsedResponse = $provider->parseResponse($response);

} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token or user details.
    exit($e->getMessage());

}


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "INSERT INTO trackerdata (avg_heartrate)
VALUES ('72')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}
$conn->close();



function apiRequest($apiURL, $provider, $accessToken){
    $request = $provider->getAuthenticatedRequest(
        Fitbit::METHOD_GET,
        Fitbit::BASE_FITBIT_API_URL .$apiURL,

        $accessToken,
        ['headers' => [Fitbit::HEADER_ACCEPT_LANG => 'en_US'], [Fitbit::HEADER_ACCEPT_LOCALE => 'en_US']]
    // Fitbit uses the Accept-Language for setting the unit system used
    // and setting Accept-Locale will return a translated response if available.
    // https://dev.fitbit.com/docs/basics/#localization

    );
    return $request;
}

function averageCalculator($dataset,$interval,$key){
    $count =1;
    $sum = 0;
    $average = 0;
    $allAverage = array();
    $intervalComplete = true;
    foreach ($dataset as $data){
        if(($count%$interval)!=0){
            $sum+=$data[$key];
            //echo $sum.'\n';

            $count++;
            $intervalComplete=false;
        }else{
            $average=$sum/$interval;
            $sum=0;
            array_push($allAverage,$average);
            $average=0;
            $count++;
            $intervalComplete=true;
        }
    }
    if(!$intervalComplete){
        $average=$sum/$count;
        array_push($allAverage,$average);
    }
    //var_dump($allAverage);
    return $allAverage;
}

