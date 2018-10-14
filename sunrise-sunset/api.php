<?PHP
if (strcasecmp(filter_input(INPUT_SERVER, 'REQUEST_METHOD'), 'POST') != 0)
{
    throw new Exception('Request method must be POST');
}

$contentType = filter_input(INPUT_SERVER, 'CONTENT_TYPE') !== null ? trim(filter_input(INPUT_SERVER, 'CONTENT_TYPE')) : '';
if (strcasecmp($contentType, 'application/json') != 0)
{
    throw new Exception('Content type must be: application/json');
}

$jsonBody = json_decode(file_get_contents('php://input'), true);

if (!is_array($jsonBody))
{
    throw new Exception('Received content contained invalid JSON');
}

if ($jsonBody['authCode'] != getenv('HTTPS_AUTHENTICATION_SECRET')) 
{
    throw new Exception('Auth Code is invalid');
}

$lat = 41.699493;
$lng = -93.594779;

$timezoneOffset = date('Z');
$currentDate = date('Y-m-d');

$requestUrl = "https://api.sunrise-sunset.org/json?lat=$lat&lng=$lng&date=$currentDate";

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $requestUrl
));
$json_response = curl_exec($ch);

$sunriseTime = '';
$sunsetTime = '';
$dayOrNight = '';

if ($json_response)
{
    $data = json_decode($json_response, true)['results'];

    if (trim($data['sunrise']) != '' && trim($data['sunset']) != '') 
    {
        $sunriseTime = date('H:i:s', strtotime($data['sunrise'] . " $timezoneOffset seconds"));
        $sunsetTime = date('H:i:s', strtotime($data['sunset'] . " $timezoneOffset seconds"));
        $dayOrNight = time() > strtotime("$currentDate $sunriseTime") && time() < strtotime("$currentDate $sunsetTime") ? 'day' : 'night';
        echo json_encode(array(
            "localDate" => $currentDate,
            "localSunriseTime" => $sunriseTime,
            "localSunsetTime" => $sunsetTime,
            "dayOrNight" => $dayOrNight
        ));
    }
}
?>