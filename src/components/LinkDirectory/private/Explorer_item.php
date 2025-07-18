<?php //if ( ! defined('BASEPATH')) exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en-US">

<head>
  <script src="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css" rel="stylesheet" />
  <link href="/res/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="/res/css/main.css" rel="stylesheet">
  <!-- site CSS -->
  <!--
    <link rel='stylesheet' type="text/css" href='<?= base_url(); ?>res/css/explorer.css' />
    -->
  <link rel='stylesheet' type="text/css" href='<?= base_url() ?>/res/explorer.css' />
  <link rel='stylesheet' type="text/css" href='<?= base_url() ?>/screen.css' />


  <style>
    body {
      margin: 0;
      padding: 0;
    }

    #map {
      border: 1px solid #ccc;
      height: 300px;
      width: 50%;
    }

    #content {
      width: 80%;
    }

    .topimg {
      height: 40px;
      width: auto;
    }

    .yellow {
      color: #ff5;
    }

    .label {
      font-weight: bold;
      float: left;
      background-color: #def;
      min-width: 90px;
  </style>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

  <script language=javascript src="<?php echo base_url(); ?>res/jquery-1.7.2.min.js"></script>

  <title> <?php echo $item["title"] ?? "" ?></title>
</head>


<div class=topnav>
  <a href="/"><img class='topimg' src="/res/dcexplorer.jpg"></a>

  <span class=toptitle><?= $item['title'] ?? "" ?></span>

</div>
<div id=sidebar>
  &nbsp;
</div>

<div id=content>
  <div class=field>
    <div class='label'>Description</div>
    <div class='label'></div>
    <div class=description><?= $item['body'] ?>
    </div>
    <div class=field>
      <div class='label'>Phone</div>
      <div class=phone><?= $item['phone'] ?>
      </div>
      <div class=field>
        <div class='label'>Cost</div>
        <div class=cost>&nbsp;<?= $item['cost'] ?>
        </div>
        <div class=field>
          <div class='label'>URL</div>
          <div class=url><a href="<?= $item['url'] ?>"><?= $item['url'] ?></a>
          </div>
          <div class=field>
            <div class='label'>Zip</div>
            <div class=zipcode><?= $item['zipcode'] ?></div>
          </div>
          <div class=field>
            <div class='label'>Address</div>
            <div class=address><?= $item['address'] ?></div>
          </div>

          <div id="map"></div>
          <script src="https://unpkg.com/es6-promise@4.2.4/dist/es6-promise.auto.min.js"></script>
          <script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>
          <script>
            mapboxgl.accessToken = 'pk.eyJ1IjoieW91bmVsYW4iLCJhIjoiY2s2ODVhYjZ1MDFmbDNsbGp2cWtiOGkyeiJ9.ZZS8eBNjazK35VLZfs7X0A';
            var mapboxClient = mapboxSdk({
              accessToken: mapboxgl.accessToken
            });
            mapboxClient.geocoding
              .forwardGeocode({
                query: '<?php
                        if ($item['address']) {
                          echo $item['address'] . "," . $item['zipcode'] . ", DC, USA";
                        } else {
                          echo $item['title'] . ", USA";
                        }
                        ?>',
                autocomplete: false,
                limit: 1
              })
              .send()
              .then(function(response) {
                if (
                  response &&
                  response.body &&
                  response.body.features &&
                  response.body.features.length
                ) {
                  var feature = response.body.features[0];

                  var map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/streets-v11',
                    center: feature.center,
                    zoom: 14
                  });
                  new mapboxgl.Marker().setLngLat(feature.center).addTo(map);
                }
              });
          </script>
        </div>
        </body>

</html>