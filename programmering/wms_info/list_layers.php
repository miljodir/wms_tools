<?php
//////////////////////////////////////////////////////////////////
// list_layers.php
//     This file produces an openlayer map based on layers within a 
//     given workspace on a geoserver
//
//     It uses the wms-parser.php library
//
//     rla@miljodir.no
//
///////////////////////////////////////////////////////////////////




//Get workspace request 

$select_workspace=(isset($_GET['ws']) ? $_GET['ws'] : null);


//Do the default workspace if the request returns empty
if (empty($select_workspace))
{
    $select_workspace="inon";
}

$select_workspace = $select_workspace.":";

//Set variables
$wms_server                 =   "http://wms.dirnat.no/geoserver/ows?";
$wms_server_getcapabilities =   $wms_server."service=wms&version=1.1.1&request=GetCapabilities";


//get that fairly ok wms parser
include ('include/wms-parser.php');


//Calculate the workspace length for use later
$select_workspace_length=strlen($select_workspace);

$gestor = fopen($wms_server_getcapabilities, "r");
$contenido = stream_get_contents($gestor);
fclose($gestor);

$caps = new CapabilitiesParser( );
$caps->parse($contenido);
$caps->free_parser( );


//Go through all relevant layers and find max lonlat extent

             
// Set default values to be adjusted      
$minx_geo=180.00;
$miny_geo=90.00;
$maxx_geo=-180.00;
$maxy_geo=-90.00;


foreach ($caps->layers as $d)
{
    if ($d['queryable'])
    {
        if (substr(($d['Name']), 0, $select_workspace_length) == $select_workspace)
        {
            if (isset($d['LatLonBoundingBox']['minx'])) 
            {
                $current_minx_geo = $d['LatLonBoundingBox']['minx'];  
                if (floatval($current_minx_geo) < $minx_geo)
                {
                    $minx_geo = floatval($current_minx_geo);
                }
            }

            if (isset($d['LatLonBoundingBox']['miny'])) 
            {
                $current_miny_geo = $d['LatLonBoundingBox']['miny'];  
                if (floatval($current_miny_geo) < $miny_geo)
                {
                    $miny_geo = floatval($current_miny_geo);
                }
            }

            if (isset($d['LatLonBoundingBox']['maxx'])) 
            {
                $current_maxx_geo = $d['LatLonBoundingBox']['maxx'];  
                if (floatval($current_maxx_geo) > $maxx_geo)
                {
                    $maxx_geo = floatval($current_maxx_geo);
                }
            }

            if (isset($d['LatLonBoundingBox']['maxy'])) 
            {
                $current_maxy_geo = $d['LatLonBoundingBox']['maxy'];  
                if (floatval($current_maxyx) > $maxy_geo)
                {
                    $maxy_geo = floatval($current_maxy_geo);
                }
            }    

        }
    }
}

//Make the average calculation to find the center of the presented map
$map_center_x_geo =(($minx_geo + $maxx_geo) / 2);
$map_center_y_geo =(($miny_geo + $maxy_geo) / 2);

$boundingbox_geo = $minx_geo.",".$miny_geo.",".$maxx_geo.",".$maxy_geo;



//Go through all relevant layers and find max native extent

// Set default values to be adjusted                      
$minx_native=10000000;
$miny_native=-10000000;
$maxx_native=-10000000;
$maxy_native=10000000;



foreach ($caps->layers as $d)
{
    if ($d['queryable'])
    {

        if (is_array($d['BoundingBox'])) {
        
        
            if (substr(($d['Name']), 0, $select_workspace_length) == $select_workspace)
            {
               
                    $current_minx_native = $d['BoundingBox'][$srs_native[0]]['minx'];  
                    
                    if (floatval($current_minx_native) < $minx_native)
                    {
                        $minx_native = floatval($current_minx_native);
                    }
                

              
                    $current_miny_native = $d['BoundingBox'][$srs_native[0]]['miny'];  
                    if (floatval($current_miny_native) < $miny_native)
                    {
                        $miny_native = floatval($current_miny_native);
                    }
                

                    $current_maxx_native = $d['BoundingBox'][$srs_native[0]]['maxx'];  
                    if (floatval($current_maxx_native) > $maxx_native)
                    {
                        $maxx_native = floatval($current_maxx_native);
                    }
                

             
                    $current_maxy_native = $d['BoundingBox'][$srs_native[0]]['maxy'];  
                    if (floatval($current_maxy_native) > $maxy_native)
                    {
                        $maxy_native = floatval($current_maxy_native);
                    }
            
               
            }
         }
    }
}

//Make the average calculation to find the center of the presented map
$map_center_x_native =(($minx_native + $maxx_native) / 2);
$map_center_y_native =(($miny_native + $maxy_native) / 2);


$boundingbox_native = $minx_native.",".$miny_native.",".$maxx_native.",".$maxy_native;

//Here the HTML code starts
?><!DOCTYPE html>
<html>
    <head>
        <title>Spatial data for the Geoserver workspace: <?php echo $select_workspace; ?></title>

        <script src = "http://www.openlayers.org/api/OpenLayers.js"></script>
        <script src='http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAl9RMqSzhPUXAfeBCXOussRSPP9rEdPLw3W8siaiuHC3ED5y09RTJKbutSNVCYFKU-GnzKsHwbJ3SUw'></script>
<!-- Load the OpenLayers API library and stylesheet -->
        <link rel="stylesheet" href="OpenLayers-2.7/theme/default/style.css" type="text/css" />

        <script type = "text/javascript">
            var lon = <?php echo $map_center_x_geo ?>;
            var lat = <?php echo $map_center_y_geo ?>;

            var zoom = 8;

            var format = 'image/png';

            var map;

            function init(){

                map_controls = [ new OpenLayers.Control.OverviewMap(),       
                    new OpenLayers.Control.LayerSwitcher({'ascending':true}),
                    new OpenLayers.Control.PanZoomBar(),
                    new OpenLayers.Control.KeyboardDefaults()];

                map = new OpenLayers.Map( 'map', {controls: map_controls} );

                var ls = map.getControlsByClass('OpenLayers.Control.LayerSwitcher')[0];

                ls.maximizeControl();


                    var gmap = new OpenLayers.Layer.Google(
                        "Google Streets" // the default
                    );
                    var gsat = new OpenLayers.Layer.Google(
                        "Google Satellite",
                        {type: G_SATELLITE_MAP}
                    );
                    map.addLayers([gmap, gsat]);<?php 

                // Add layers according to available layers in Geoserver
                // Uses lonlat 
                $i=0;
                
                foreach ($caps->layers as $l) {
                    if ($l['queryable']) {          
                        //Filter out layers which are similar to the one 
                        if  (substr(($l['Name']),0,$select_workspace_length)==$select_workspace) {
                            
                            
                            $srs_latlon = (array_keys($d['BoundingBox']));
                            //echo $srs_latlon[0];?>    
                            
                            wms_layer_<?php echo $i; ?> = new OpenLayers.Layer.WMS("<?php echo $l['Name'] ?>",
                                "http://wms.dirnat.no/geoserver/ows?service=wms",
                                {
                                    "layers":"<?php echo $l['Name'] ?>",
                                    "format":"image/png", 
                                    "transparent": "true"
                                }
                                //,
                                //{ "reproject":"true" }
                            );

                            map.addLayer(wms_layer_<?php echo $i; ?>);

                            wms_layer_<?php echo $i; ?>.setVisibility(false);  

                            <?php $i++;
                        }  
                    } 

                }?>                                                                                                                 
                map.setCenter(new OpenLayers.LonLat(lon, lat), zoom);     
            }
        </script>
    </head>

    <body onload = "init()">
        <h1 id = "title">Data set presentation</h1>
        <div id="map" class="bigmap"></div>        
        <h3><strong>Layers count:</strong><?php echo ($i - 1) ?></h3>

        <ol>
            <?php
            $i=1;

            //Make a list of the layers
            foreach ($caps->layers as $l)
            {
                if ($l['queryable'])
                {
                    if (substr(($l['Name']), 0, $select_workspace_length) == $select_workspace)
                    
                    {
                        
                        $srs_native = (array_keys($l['BoundingBox']));
                                            
                        if (isset($l['BoundingBox'][$srs_native[0]]['minx'])) 
                        {
                            $current_minx_t = $l['BoundingBox'][$srs_native[0]]['minx'];  
                            
                            $current_minx = floatval($current_minx_t);
                        }

                        if (isset($l['BoundingBox'][$srs_native[0]]['miny'])) 
                        {
                            $current_miny_t = $l['BoundingBox'][$srs_native[0]]['miny']; 
                            
                            $current_miny = floatval($current_miny_t);
                        }

                        if (isset($l['BoundingBox'][$srs_native[0]]['maxx'])) 
                        {
                            $current_maxx_t = $l['BoundingBox'][$srs_native[0]]['maxx'];
                            $current_maxx = floatval($current_maxx_t);  
                        }

                        if (isset($l['BoundingBox'][$srs_native[0]]['maxy'])) 
                        {
                            $current_maxy_t = $l['BoundingBox'][$srs_native[0]]['maxy']; 
                            $current_maxy = floatval($current_maxy_t);
                        }    

                        $boundingbox_native = $current_minx.",".$current_miny.",".$current_maxx.",".$current_maxy;
                        
                        ?>

                        <li><strong><?php echo $l['Title'] ?></strong><br>
                        (<a href=<?php echo $wms_server ?>service=wms&version=1.1.1&request=GetMap&layers=<?php echo $l['Name'] ?>&styles=&bbox=<?php echo $boundingbox_native;?>&width=512&height=469&srs=<?php echo $srs_native[0] ?>&format=application/openlayers><?php
                            echo $l['Name'] ?></a>)<br/>

                                <br>
                                <table>
                                    <tr><td colspan=2><a href="<?php echo $wms_server ?>service=WFS&version=1.0.0&request=GetFeature&typeName=<?php echo $l['Name'] ?>&outputFormat=SHAPE-ZIP">Download shapefile</a></td></tr>
                                    <tr><td><b>Legend&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></td><td><b>Abstract</b></td></tr>
                                    <tr><td><img src = "<?php echo $wms_server ?>service=wms&REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=<?php echo $l['Name'] ?>"></td>

                                        <td>
                                            <?php
                                            echo $l['Abstract']?>

                                            <br>
                                            Bounding box: (<i><?php echo $boundingbox_native; ?>)</i>    

                                        </td>

                                    </tr>

                                </table>

                            <br/></li>

                        <?php
                        $i++;
                    }
                }
            }
            ?>
        </ol>
    </body>
</html>