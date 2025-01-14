<?php

// Fonction pour obtenir la géolocalisation de l'IUT Charlemagne
function getGeolocationIUT() {
    $api = "https://api-adresse.data.gouv.fr/search/?q=Institut%20Universitaire%20de%20Technologie%20Nancy-Charlemagne";
    $response = file_get_contents($api);
    return $response ? json_decode($response, true) : false;
}

// Fonction pour obtenir les informations de géolocalisation en XML
function getGeolocation($ip) {
    $url = "https://ipapi.co/$ip/xml/";
    $response = file_get_contents($url);
    return $response ? simplexml_load_string($response) : false;
}

// Obtenir l'adresse IP du client
$clientIp = file_get_contents('https://api.ipify.org');

// Géolocaliser l'adresse IP du client
$geoData = getGeolocation($clientIp);

$latitude = null;
$longitude = null;

// Vérifier si la géolocalisation a réussi sur Nancy
if ($geoData && isset($geoData->city) && strtolower((string)$geoData->city) === 'nancy') {
    $latitude = (string)$geoData->latitude;
    $longitude = (string)$geoData->longitude;
} else {
    // Sinon, essayer de géolocaliser l'IUT Charlemagne
    $geoData = getGeolocationIUT();
    if ($geoData && isset($geoData['features'][0]['geometry']['coordinates'])) {
        $latitude = (string)$geoData['features'][0]['geometry']['coordinates'][1];
        $longitude = (string)$geoData['features'][0]['geometry']['coordinates'][0];
    } else {
        die("Impossible de déterminer la géolocalisation.");
    }
}

// Récupérer les données météo en fonction de la réponse de géolocalisation
$weather_url = "http://www.infoclimat.fr/public-api/gfs/xml?_ll=$latitude,$longitude&_auth=BR9fSAR6UXMFKFNkUyVQeVE5VWBcKgIlA39WNQhtVClSOQJjDm5VM14wVitSfQI0UXwAY1phUmIAa1YuCnhUNQVvXzMEb1E2BWpTNlN8UHtRf1U0XHwCJQNhVjgIZlQpUjQCZg5zVTZeMlYxUnwCNFFiAGNaelJ1AGJWNgpkVDEFYV8zBGBRNAVjUzNTfFB7UWRVMlxrAj8DZFY2CGBUN1JiAjAOO1U0XmRWPFJ8AjVRYABgWmZSbABlVjgKZVQoBXlfQgQUUS4FKlNzUzZQIlF%2FVWBcPQJu&_c=8c5cc1a54781fbecdfafccc538dd6da0";
$weather = simplexml_load_file($weather_url);

if ($weather === false) {
    die("Échec du chargement des données météo.");
}

// Générer le fragment HTML à l'aide de la feuille de style XSL
$xsl = new DOMDocument();
$xsl->load('HtmlMeteo.xsl');
$xml = new DOMDocument();
$xml->loadXML($weather->asXML());
$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);
$weather_fragment = $proc->transformToXML($xml);

// Fonction pour obtenir les données de circulation
function getTrafficData() {
    $url = "https://carto.g-ny.org/data/cifs/cifs_waze_v2.json";
    $response = file_get_contents($url);
    return $response ? json_decode($response, true) : false;
}

$trafficData = getTrafficData();

// Fonction pour obtenir les données de qualité de l'air
function getAirQuality($latitude, $longitude, $apiKey) {
    $url = "http://api.openweathermap.org/data/2.5/air_pollution?lat=$latitude&lon=$longitude&appid=$apiKey";
    $response = file_get_contents($url);
    return $response ? json_decode($response, true) : false;
}

$apiKey = 'dc87b148530b91e0071c3c9b2a55c041';
$airQualityData = getAirQuality($latitude, $longitude, $apiKey);

// Générer le contenu HTML pour la qualité de l'air
$airQualityHtml = "<h1>Qualité de l'air</h1>";
if ($airQualityData) {
    $aqi = $airQualityData['list'][0]['main']['aqi'];
    $airQualityHtml .= match ($aqi) {
        1 => "<p>Qualité de l'air : Bonne</p>",
        2 => "<p>Qualité de l'air : Moyenne</p>",
        3 => "<p>Qualité de l'air : Acceptable</p>",
        4 => "<p>Qualité de l'air : Mauvaise</p>",
        5 => "<p>Qualité de l'air : Très mauvaise</p>",
        default => "<p>Qualité de l'air : Inconnue</p>",
    };
} else {
    $airQualityHtml .= "<p>Impossible de récupérer les données de qualité de l'air.</p>";
}

$iutNancyData = getGeolocationIUT();
$iutNancyLat = $iutNancyData['features'][0]['geometry']['coordinates'][1];
$iutNancyLon = $iutNancyData['features'][0]['geometry']['coordinates'][0];

?>
<!DOCTYPE html>
<html lang="fr">
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

        .resources {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-left: 5px solid #3498db;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .resources a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
    <link rel='stylesheet' href='https://unpkg.com/leaflet@1.7.1/dist/leaflet.css'/>
</head>
<body>
<div class='content'>
    <?php echo $weather_fragment; ?>
</div>
<h1>Carte des difficultés de circulation</h1>
<div id='map'></div>
<script src='https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'></script>
<script>
    let map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let trafficData = <?php echo json_encode($trafficData); ?>;

    trafficData.incidents.forEach(function (traffic) {
        let coords = traffic.location.polyline.split(' ');
        let lat = parseFloat(coords[0]);
        let lon = parseFloat(coords[1]);
        let marker = L.marker([lat, lon]).addTo(map);
        marker.bindPopup('<b>' + traffic.short_description + '</b><br>' + traffic.description + '<br>From ' + traffic.starttime + ' to ' + traffic.endtime);
    });

    let iutNancy = L.marker([<?php echo $iutNancyLat; ?>, <?php echo $iutNancyLon; ?>]).addTo(map);
    iutNancy.bindPopup('<b>IUT Nancy-Charlemagne</b><br>2 ter boulevard Charlemagne, 54000 Nancy');
</script>
<div class='air-quality'>
    <?php echo $airQualityHtml; ?>
</div>
<div class='resources'>
    <h2>Ressources utilisées</h2>
    <ul>
        <li>Données météorologiques : <a href='http://www.infoclimat.fr/'>Infoclimat</a></li>
        <li>Données de circulation : <a href='https://carto.g-ny.org/'>Carto</a></li>
        <li>Données de qualité de l'air : <a href='https://openweathermap.org/'>OpenWeatherMap</a></li>
        <li>Données de géolocalisation : <a href='https://ipapi.co/'>IPAPI</a></li>
        <li>Données de géolocalisation de l'IUT Nancy-Charlemagne : <a href='https://api-adresse.data.gouv.fr/'>API Adresse</a></li>
    </ul>
</div>
</body>
</html>