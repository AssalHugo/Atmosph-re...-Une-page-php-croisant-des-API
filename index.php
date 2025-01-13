<?php

//Fonction pour obtenir la localisation de l'iut charlemagne
function getGeolocationIUT(){
    $api = "https://api-adresse.data.gouv.fr/search/?q=Institut%20Universitaire%20de%20Technologie%20Nancy-Charlemagne";

    $ch = curl_init($api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $reponse = curl_exec($ch);
    curl_close($ch);

    if ($reponse) {
        return json_decode($reponse, true);
    } else {
        return false;
    }
}

// Fonction pour obtenir les informations de géolocalisation en XML
function getGeolocation($ip)
{

    $url = "https://ipapi.co/" . $ip . "/xml/";
    $ch = curl_init($url);


    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $reponse = curl_exec($ch);
    curl_close($ch);

    if ($reponse) {
        return simplexml_load_string($reponse);
    } else {
        return false;
    }
}

// Obtenir l'adresse IP du client
$clientIp = $_SERVER['REMOTE_ADDR'];

// Géolocaliser l'adresse IP du client
$geoData = getGeolocation($clientIp);

$latitude = null;
$longitude = null;

// Vérifier si la géolocalisation a abouti sur Nancy
if ($geoData && isset($geoData->city) && strtolower((string)$geoData->city) === 'nancy') {
    $latitude = (string)$geoData->latitude;
    $longitude = (string)$geoData->longitude;
} else {
    // Sinon, essayer de géolocaliser l'IUT Charlemagne
    $geoData = getGeolocationIUT();

    $latitude = (string)$geoData['features'][0]['geometry']['coordinates'][1];
    $longitude = (string)$geoData['features'][0]['geometry']['coordinates'][0];
}

// Récupérer les données météo, à partir de la réponse à la géolocalisation
$weather_url = "http://www.infoclimat.fr/public-api/gfs/xml?_ll=$latitude,$longitude&_auth=BR9fSAR6UXMFKFNkUyVQeVE5VWBcKgIlA39WNQhtVClSOQJjDm5VM14wVitSfQI0UXwAY1phUmIAa1YuCnhUNQVvXzMEb1E2BWpTNlN8UHtRf1U0XHwCJQNhVjgIZlQpUjQCZg5zVTZeMlYxUnwCNFFiAGNaelJ1AGJWNgpkVDEFYV8zBGBRNAVjUzNTfFB7UWRVMlxrAj8DZFY2CGBUN1JiAjAOO1U0XmRWPFJ8AjVRYABgWmZSbABlVjgKZVQoBXlfQgQUUS4FKlNzUzZQIlF%2FVWBcPQJu&_c=8c5cc1a54781fbecdfafccc538dd6da0";
$weather = simplexml_load_file($weather_url);

// Générer le fragment HTML à l'aide de la feuille XSL demandée dans le préalable
$xsl = new DOMDocument();
$xsl->load('HtmlMeteo.xsl');
$xml = new DOMDocument();
$xml->loadXML($weather->asXML());
$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);
$weather_fragment = $proc->transformToXML($xml);

// On envoie le résultat dans index.html
file_put_contents('index.html', $weather_fragment);

// Fonction pour obtenir les données de circulation
function getTrafficData()
{
    $url = "https://carto.g-ny.org/data/cifs/cifs_waze_v2.json";
    $ch = curl_init($url);

    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $reponse = curl_exec($ch);
    curl_close($ch);

    if ($reponse) {
        return json_decode($reponse, true);
    } else {
        return false;
    }
}

$trafficData = getTrafficData();

// Fonction pour obtenir les données de qualité de l'air
function getAirQuality($latitude, $longitude, $apiKey)
{
    $url = "http://api.openweathermap.org/data/2.5/air_pollution?lat=$latitude&lon=$longitude&appid=$apiKey";
    $ch = curl_init($url);

    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        return json_decode($response, true);
    } else {
        return false;
    }
}

$apiKey = 'dc87b148530b91e0071c3c9b2a55c041';
$airQualityData = getAirQuality($latitude, $longitude, $apiKey);

// Générer le contenu HTML pour la qualité de l'air
$airQualityHtml = "<h1>Qualité de l'air</h1>";
if ($airQualityData) {
    $aqi = $airQualityData['list'][0]['main']['aqi'];
    switch ($aqi) {
        case 1:
            $airQualityHtml .= "<p>Qualité de l'air : Bonne</p>";
            break;
        case 2:
            $airQualityHtml .= "<p>Qualité de l'air : Moyenne</p>";
            break;
        case 3:
            $airQualityHtml .= "<p>Qualité de l'air : Acceptable</p>";
            break;
        case 4:
            $airQualityHtml .= "<p>Qualité de l'air : Mauvaise</p>";
            break;
        case 5:
            $airQualityHtml .= "<p>Qualité de l'air : Très mauvaise</p>";
            break;
    }
} else {
    $airQualityHtml .= "<p>Impossible de récupérer les données de qualité de l'air.</p>";
}

$iutNancyData = getGeolocationIUT();
$iutNancyLat = $iutNancyData['features'][0]['geometry']['coordinates'][1];
$iutNancyLon = $iutNancyData['features'][0]['geometry']['coordinates'][0];


// Générer le contenu HTML pour la carte Leaflet
$mapHtml = "
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Météo du jour</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f4f9; 
            color: #333; 
        }
        h1 { 
            color: #444; 
            text-align: center; 
            margin-top: 20px; 
            font-size: 2em; 
        }
        .content { 
            padding: 20px; 
            background-color: #fff; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
            margin: 20px auto; 
            max-width: 800px; 
            border-radius: 8px; 
        }
        .air-quality { 
            margin-top: 20px; 
            padding: 20px; 
            background-color: #e9f7ef; 
            border-left: 5px solid #2ecc71; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
            border-radius: 8px; 
        }
        .air-quality p { 
            font-size: 18px; 
            margin: 0; 
        }
        #map { 
            height: 400px; 
            width: 100%; 
            margin-top: 20px; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
        }
    </style>
    <link rel='stylesheet' href='https://unpkg.com/leaflet@1.7.1/dist/leaflet.css' />
</head>
<body>
    <div class='content'>
        $weather_fragment
    </div>
    <h1>Carte des difficultés de circulation</h1>
    <div id='map'></div>
    <script src='https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'></script>
    <script>
        let map = L.map('map').setView([$latitude, $longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let trafficData = " . json_encode($trafficData) . ";

        trafficData.incidents.forEach(function (traffic) {
            let coords = traffic.location.polyline.split(' ');
            let lat = parseFloat(coords[0]);
            let lon = parseFloat(coords[1]);
            let marker = L.marker([lat, lon]).addTo(map);
            marker.bindPopup('<b>' + traffic.short_description + '</b><br>' + traffic.description + '<br>From ' + traffic.starttime + ' to ' + traffic.endtime);
        });

        let iutNancy = L.marker([$iutNancyLat, $iutNancyLon]).addTo(map);
        iutNancy.bindPopup('<b>IUT Nancy-Charlemagne</b><br>2 ter boulevard Charlemagne, 54000 Nancy');
    </script>
    <div class='air-quality'>
        $airQualityHtml
    </div>
</body>
</html>";

// Écrire le contenu complet dans index.html
file_put_contents('index.html', $mapHtml);

echo "Le fichier index.html a été mis à jour avec les prévisions météorologiques, les données de circulation et la qualité de l'air.";