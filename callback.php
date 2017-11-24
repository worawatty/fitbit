<?php
/**
 * Created by PhpStorm.
 * User: Worawat
 * Date: 11/16/2017
 * Time: 3:16 PM
 */

//phpinfo();

require_once __DIR__ . '/vendor/autoload.php';
use djchen\OAuth2\Client\Provider\Fitbit;


echo stats_stat_factorial(15);

$provider = new Fitbit([
    'clientId'          => '22CLGD',
    'clientSecret'      => '08816ee1bcbdd9b0d826a8a4faa6163c',
    'redirectUri'       => 'http://localhost:8080/fitbit/callback.php'
]);

$yesterday = date('Y-m-d',strtotime('-2 days'));
$today = date('Y-m-d');
$starttime = '01:00';
$endtime = '23:00';

var_dump($yesterday);
var_dump($today);

//echo $_GET['code'];
$trackerObjects = array();

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
    //$activityAPI = '/1/user/-/activities/date/'.date("Y-m-d").'.json';

    $foodLogAPI = '/1/user/-/foods/log/date/'.date('Y-m-d').'.json';


    //var_export($resourceOwner->toArray());


    //Define API URL
    $activityIntraDayAPI = '/1/user/-/activities/calories/date/'.$today.'/1d/1min/time/10:00/11:00.json';
    $heartRateAPI = '/1/user/-/activities/heart/date/'.$today.'/1d/1min/time/10:00/11:00.json';
    $sleepLogAPI = '/1.2/user/-/sleep/date/'.$today.'.json';
    $stepAPI = '/1/user/-/activities/steps/date/'.$today.'/1d/1min/time/10:00/11:00.json';
    $restingHeartRateAPI = '/1/user/-/activities/heart/date/'.$today.'/1d.json';
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
    //$requestActivity=apiRequest($activityAPI,$provider,$accessToken);
    $requestFoodLog=apiRequest($foodLogAPI,$provider,$accessToken);
    $requestHeartRate=apiRequest($heartRateAPI,$provider,$accessToken);
    $requestSleepLog=apiRequest($sleepLogAPI,$provider,$accessToken);
    $requestActivityIntraDay=apiRequest($activityIntraDayAPI,$provider,$accessToken);
    $requestSteps=apiRequest($stepAPI,$provider,$accessToken);
    $requestRestingHR=apiRequest($restingHeartRateAPI,$provider,$accessToken);
    //Request for the parsed Response
    //$responseActivity = $provider->getParsedResponse($requestActivity);
    $responseFoodLog = $provider->getParsedResponse($requestFoodLog);
    $responseHeartRate = $provider->getParsedResponse($requestHeartRate);
    $responseSleepLog = $provider->getParsedResponse($requestSleepLog);
    $responseActivityIntraday = $provider->getParsedResponse($requestActivityIntraDay);
    $responseSteps=$provider->getParsedResponse($requestSteps);
    $responseRestingHR=$provider->getParsedResponse($requestRestingHR);


    $sumCalories = 0;
    foreach ($responseActivityIntraday['activities-calories-intraday']['dataset'] as $data){
        $sumCalories = $sumCalories+$data['value'];
    }

    $caloriesAverages =  averageCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],60,'value');
    $caloriesMax = maxCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],60,'value');
    $caloriesMin = minCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],60,'value');
    $caloriesSd = stdCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],60,'value');
    $caloriesSum = sumCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],60,'value');

    $heartRateAverages = averageCalculator($responseHeartRate['activities-heart-intraday']['dataset'],60,'value');
    $heartRateMax = maxCalculator($responseHeartRate['activities-heart-intraday']['dataset'],60,'value');
    $heartRateMin = minCalculator($responseHeartRate['activities-heart-intraday']['dataset'],60,'value');
    $heartRateSd = stdCalculator($responseHeartRate['activities-heart-intraday']['dataset'],60,'value');

    $stepsAverages = averageCalculator($responseSteps['activities-steps-intraday']['dataset'],60,'value');
    $stepsMax = maxCalculator($responseSteps['activities-steps-intraday']['dataset'],60,'value');
    $stepsMin = minCalculator($responseSteps['activities-steps-intraday']['dataset'],60,'value');
    $stepsSd = stdCalculator($responseSteps['activities-steps-intraday']['dataset'],60,'value');
    $stepsSum = sumCalculator($responseSteps['activities-steps-intraday']['dataset'],60,'value');


    /*foreach ($caloriesAverages as $value){
        echo $value."\n";
    }*/

    if (count($caloriesAverages)==count($caloriesMax)&& count($caloriesMax)==count($caloriesMin) &&count($caloriesMin) == count($caloriesSd)
        && count($caloriesSd) == count($heartRateAverages) && count($heartRateAverages)== count($heartRateMax)&& count($heartRateMax)==count($heartRateMin)
        && count($heartRateMin) == count($heartRateSd)){

        for($i=0; $i<count($caloriesSd); $i++){
            array_push($trackerObjects,array(
                'averageCalories' => $caloriesAverages[$i],
                'minCalories' => $caloriesMin[$i],
                'maxCalories' => $caloriesMax[$i],
                'sdCalories' => $caloriesSd[$i],
                'sumCalories' => $caloriesSum[$i],
                'averageSteps' => $stepsAverages[$i],
                'minSteps' => $stepsMin[$i],
                'maxSteps' => $stepsMax[$i],
                'sdSteps' => $stepsSd[$i],
                'sumSteps' => $stepsSum[$i],
                'averageHeartRate' => $heartRateAverages[$i],
                'minHeartRate' => $heartRateMin[$i],
                'maxHeartRate' => $heartRateMax[$i],
                'sdHeartRate' => $heartRateMax[$i],
                'restingHR' => $responseRestingHR['activities-heart'][0]['value']['restingHeartRate'],
                'deepSleep' => $responseSleepLog['sleep'][0]['levels']['summary']['deep']['minutes'],
                'lightSleep' => $responseSleepLog['sleep'][0]['levels']['summary']['light']['minutes'],
                'remSleep' => $responseSleepLog['sleep'][0]['levels']['summary']['rem']['minutes'],
                'wakeSleep' => $responseSleepLog['sleep'][0]['levels']['summary']['wake']['minutes'],
                'minutesAsleep' => $responseSleepLog['sleep'][0]['minutesAsleep'],
                'minutesAwake' => $responseSleepLog['sleep'][0]['minutesAwake'],
            ));
        }


    }



    foreach ($trackerObjects as $trackerObject){
        $columns = 'avg_calories,min_calories,max_calories,std_calories,sum_calories,avg_steps,min_steps,max_steps,sd_steps,sum_steps,
                    avg_heartrate,min_heartrate,max_heartrate,std_heartrate,resting_heartrate,
                    deep_sleep,light_sleep,rem_sleep,wake_sleep,total_sleep,total_awake';
        $values = implode(',',$trackerObject);
        //echo $values;
        //insertIntoDB($values,$columns);
    }


    echo '<html>';
    echo '<pre>';
    /*echo count($caloriesAverages);
    echo count($caloriesMax);
    echo count($caloriesMax);
    echo count($caloriesMin);
    echo count($caloriesMin);
    echo count($caloriesSd);
    echo count($caloriesSd);
    echo count($heartRateAverages);
    echo count($heartRateAverages);
    echo count($heartRateMax);
    echo count($heartRateMax);
    echo count($heartRateMin);
    echo count($heartRateMin);
    echo count($heartRateSd);*/
    //print_r($caloriesMin);
    //print_r($caloriesMax);
    //print_r($caloriesAverages);
    //print_r(stdCalculator($responseActivityIntraday['activities-calories-intraday']['dataset'],4,'value'));
    print_r($trackerObjects);
    //print_r($responseRestingHR);
    print_r($responseActivityIntraday);
    //print_r($responseSteps);
    //print_r($heartrateAverages);

   //print_r($responseSleepLog);
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
            $sum+=$data[$key];
            $average=$sum/$interval;
            $sum=0;
            array_push($allAverage,$average);
            $average=0;
            $count++;
            $intervalComplete=true;
        }
    }
    /*if(!$intervalComplete){
        $average=$sum/$count;
        array_push($allAverage,$average);
    }*/
    //var_dump($allAverage);
    return $allAverage;
}

function sumCalculator($dataset,$interval,$key){
    $count =1;
    $sum = 0;
    $average = 0;
    $allSums = array();
    $intervalComplete = true;
    foreach ($dataset as $data){
        if(($count%$interval)!=0){
            $sum+=$data[$key];
            //echo $sum.'\n';

            $count++;
            $intervalComplete=false;
        }else{
            $sum+=$data[$key];
            $average=$sum/$interval;

            array_push($allSums,$sum);
            $sum=0;
            $average=0;
            $count++;
            $intervalComplete=true;
        }
    }
    /*if(!$intervalComplete){
        $average=$sum/$count;
        array_push($allAverage,$average);
    }*/
    //var_dump($allAverage);
    return $allSums;
}

function stdCalculator($dataset,$interval,$key){
    $allSTD = array();
    $count = 1;
    $intervalComplete = true;
    $std = 0;
    $minuteData = array();
    foreach ($dataset as $data){
        if(($count%$interval)!=0){
            //$sum+=$data[$key];
            //echo $sum.'\n';

            array_push($minuteData,$data[$key]);
            $count++;
            $intervalComplete=false;
        }else{

            //calculate standardDeviation for one hour and push to array
            array_push($minuteData,$data[$key]);
            //var_dump($minuteData);
            array_push($allSTD,sd($minuteData));
            $minuteData=array();
            $count++;
            $intervalComplete=true;
        }
    }

    /*if(!$intervalComplete){

        array_push($allSTD,sd($minuteData));
    }*/
    return $allSTD;
}

// Function to calculate square of value - mean
function sd_square($x, $mean) { return pow($x - $mean,2); }

// Function to calculate standard deviation (uses sd_square)
function sd($array) {

// square root of sum of squares devided by N-1
    return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)) );
}


function maxCalculator($dataset,$interval,$key){
    $allMax = array();
    $count = 1;
    $intervalComplete = true;
    $hourData=array();
    foreach ($dataset as $data){
        if(($count%$interval)!=0){
            array_push($hourData,$data[$key]);
            $count++;
            $intervalComplete=false;

        }else{
            array_push($hourData,$data[$key]);
            array_push($allMax,max($hourData));
            $count++;
            $intervalComplete=true;

            $hourData=array();
        }
    }

    /*if(!$intervalComplete){
        array_push($hourData,$data[$key]);
        array_push($allMax,max($hourData));
        $intervalComplete=true;
    }*/
    return $allMax;
}

function minCalculator($dataset,$interval,$key){
    $allMin = array();
    $count = 1;
    $intervalComplete = true;
    $hourData=array();
    foreach ($dataset as $data){
        if(($count%$interval)!=0){
            array_push($hourData,$data[$key]);
            $count++;
            $intervalComplete=false;

        }else{
            array_push($hourData,$data[$key]);
            array_push($allMin,min($hourData));
            $count++;
            $intervalComplete=true;

            $hourData=array();
        }
    }

    /*if(!$intervalComplete){
        array_push($hourData,$data[$key]);
        array_push($allMin,min($hourData));
        $intervalComplete=true;
    }*/
    return $allMin;

}

function insertIntoDB($values,$columns){

//MySQL Information
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "fitbit";
    $tablename = "trackerdata";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    $sql = "INSERT INTO ".$tablename." (".$columns.") VALUES (".$values.")";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $conn->close();
}

