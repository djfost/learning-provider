<?php
require_once( "../lib/arc2/ARC2.php" );
require_once( "../lib/Graphite/Graphite.php" );

date_default_timezone_set( "Europe/London" );
try {
    $f3=require('lib/base.php');
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

#if ((float)strstr(PCRE_VERSION,' ',TRUE)<7.9)
#	trigger_error('Outdated PCRE library version');

if (function_exists('apache_get_modules') &&
	!in_array('mod_rewrite',apache_get_modules()))
	trigger_error('Apache rewrite_module is disabled');

$f3->set('DEBUG',2);
$f3->set('UI','ui/');

$f3->route('GET /',
	function() {
		$content = file_get_contents( "homepage-1.html.fragment" )
			 . file_get_contents( "data/learning-providers-lookup.html.fragment" )
			 . file_get_contents( "homepage-2.html.fragment" );
		print render_page( "UK Learning Providers", $content );
	}
);

$f3->route('GET /ukprn/@file',
	function() use($f3) {
		@list( $ukprn, $format ) = preg_split( "/\./", $f3->get('PARAMS.file'), 2 );
		$file = "ukprn/$ukprn.ttl";
		if( !file_exists( $file ) ) { $f3->error(404); exit; }

		if( $format == "" )
		{
			header( "HTTP/1.1 303 See Other" );
			header( "Location: http://learning-provider.data.ac.uk/ukprn/$ukprn.ttl" );
			exit;
		}

		if( $format != "html" )
		{
			$f3->error(404); 
			exit; 
		}

		$graph = new Graphite();
		$graph->ns( "ospost", "http://data.ordnancesurvey.co.uk/ontology/postcode/" );
		$graph->ns( "vcard", "http://www.w3.org/2006/vcard/ns#" );
		$graph->load( $file );
		$uri = "http://id.learning-provider.data.ac.uk/ukprn/$ukprn";
		$lp = $graph->resource( $uri );
		$content = "";
		
		$pc = $lp->get("ospost:postcode");
		if( $pc->has( "geo:lat" ) )
		{
			$content .= "</div>";
			$content .= "<div class='ten columns'>";
		}
		$content .= "<p><strong>URI:</strong> $uri</p>\n";

		$adr = $lp->get("vcard:adr" );
		$content .= "<p><addr>\n";
		$content .= "".$adr->getLiteral( "vcard:street-address" )."<br />\n";
		$content .= "".$adr->getLiteral( "vcard:locality" )."<br />\n";
		$content .= "".$adr->getLiteral( "vcard:country-name" )."<br />\n";
		$content .= "".$lp->get("ospost:postcode")->prettyLink();
		$content .= "</addr></p>\n";

		$content .= "<p><strong>UKPRN:</strong> ".$lp->getLiteral( "skos:notation" )."</p>\n";
		$content .= "<p><strong>Homepage:</strong> ".$lp->get( "foaf:homepage" )->link()."</p>\n";

		# lazy, assumes only one isPrimaryTopicOf
		$content .= "<p><strong>Wikipedia:</strong> ".$lp->get( "foaf:isPrimaryTopicOf" )->link()."</p>\n";
		if( $lp->has( "-foaf:member" ) )
		{
			$content.= "<h3>Member of</h3><ul class='bullets'>";
			foreach( $lp->all( "-foaf:member" )->sort( "rdfs:label" ) as $org )
			{
				# sloppy linking as URI does not auto-detect HTML preference
				$content.= "<li><a href='".$org.".html'>".$org->label()."</a></li>";
			}
			$content .= "</ul>";
		}

		$content .= "
<h3>Data</h3>
<table><tr>
 <td style='width:40px;'><a href='/ukprn/$ukprn.ttl'><img src='/resources/images/file.png' /></a></td>
 <td><strong><a href='/ukprn/$ukprn.ttl'>".$lp->label()."</a></strong> - RDF Description (.ttl)</td>
</tr></table>
";
		if( $pc->has( "geo:lat" ) )
		{
			$content .= "</div>";
			$content .= "<div class='six columns'>";
			$lat = $pc->get( "geo:lat" );
			$long = $pc->get( "geo:long" );
			#$content.=$lat." ".$long.'
			$content.='
    <script src="http://openlayers.org/api/OpenLayers.js"></script>
      <div style="border:solid 1px #ccc;width:300px; height:300px;float:right" id="map"></div>
<script>
var map = new OpenLayers.Map("map");
var wms = new OpenLayers.Layer.OSM();
map.addLayer(wms);

var lonLat = new OpenLayers.LonLat( '.$long.','.$lat.')
         .transform(
            new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
            map.getProjectionObject() // to Spherical Mercator Projection
          );
 
var zoom = 9;

var markers = new OpenLayers.Layer.Markers( "Markers" );
map.addLayer(markers);
markers.addMarker(new OpenLayers.Marker(lonLat));
map.setCenter( lonLat, zoom );
</script>
   ';
			$content .= "</div>";
			$content .= "<div class='sixteen columns'>";
}

		print render_page( $lp->label()." - Learning Provider", $content );

	}
);

$f3->route('GET /group/@file',
	function() use($f3) {
		@list( $group, $format ) = preg_split( "/\./", $f3->get('PARAMS.file'), 2 );
		$file = "group/$group.ttl";
		if( !file_exists( $file ) ) { $f3->error(404); exit; }

		if( $format == "" )
		{
			header( "HTTP/1.1 303 See Other" );
			header( "Location: http://learning-provider.data.ac.uk/group/$group.ttl" );
			exit;
		}

		if( $format != "html" )
		{
			$f3->error(404); 
			exit; 
		}

		$graph = new Graphite();
		$graph->ns( "ospost", "http://data.ordnancesurvey.co.uk/ontology/postcode/" );
		$graph->ns( "vcard", "http://www.w3.org/2006/vcard/ns#" );
		$graph->load( $file );
		$uri = "http://id.learning-provider.data.ac.uk/group/$group";
		$c_res = $graph->resource( $uri );
		$content = "";
		
		$content .= "</div><div class='ten columns'>";
		$content .= "<p><strong>URI:</strong> $uri</p>\n";

		$content .= "<p><strong>Homepage:</strong> ".$c_res->get( "foaf:homepage" )->link()."</p>\n";

		# lazy, assumes only one isPrimaryTopicOf
		$content .= "<p><strong>Wikipedia:</strong> ".$c_res->get( "foaf:isPrimaryTopicOf" )->link()."</p>\n";
		$content .= "</div><div class='six columns'>";
		$content .= "
<h3>Data</h3>
<table>
<tr>
 <td style='width:40px;'><a href='turtle/$group.ttl'><img src='/resources/images/file.png' /></a></td>
 <td><strong><a href='/group/$group.ttl'>".$c_res->label()."</a></strong> - RDF Description (.ttl)</td>
</tr>
<tr>
 <td style='width:40px;'><a href='turtle/$group.csv'><img src='/resources/images/file.png' /></a></td>
 <td><strong><a href='/group/$group.csv'>".$c_res->label()."</a></strong> - List of members (.csv)</td>
</tr>
<tr>
 <td style='width:40px;'><a href='turtle/$group.tsv'><img src='/resources/images/file.png' /></a></td>
 <td><strong><a href='/group/$group.tsv'>".$c_res->label()."</a></strong> - List of members (.tsv)</td>
</tr>
</table>
";
		$content .= "</div><div class='sixteen columns'>";
		$content.= "<h3>Members</h3><table class='scheme'>";
		foreach( $c_res->all( "foaf:member" )->sort( "rdfs:label" ) as $lp )
		{
			# sloppy linking as URI does not auto-detect HTML preference
			$content.= "<tr>";
			$content.= "<td>".$lp->get("foaf:homepage")->link()."</td>";
			$content.= "<td><a href='".$lp.".html'>".$lp->label()."</a></td>";
			$content.= "</tr>";
		}
		$content .= "</table>";

		print render_page( $c_res->label()." - University Group", $content );
	}
);


$f3->route('GET /all.html',
	function() use($f3) {
		$graph = new Graphite();
		$graph->ns( "ospost", "http://data.ordnancesurvey.co.uk/ontology/postcode/" );
		$graph->ns( "vcard", "http://www.w3.org/2006/vcard/ns#" );
		$graph->load( "data/learning-providers.ttl" );
		$content = "";
		$content.= "<table class='scheme'>";
		foreach( $graph->allOfType( "http://purl.org/vocab/aiiso/schema#Institution" )->sort( "rdfs:label" ) as $lp )
		{
			# sloppy linking as URI does not auto-detect HTML preference
			$content.= "<tr>";
			$content.= "<td>".$lp->get("foaf:homepage")->link()."</td>";
			$content.= "<td><a href='".$lp.".html'>".$lp->label()."</a></td>";
			$content.= "</tr>";
		}
		$content .= "</table>";
		print render_page( "All UK Universities", $content );
	}
);
		


$f3->run();
exit;

function render_page( $title, $content )
{
	$page = file_get_contents( "template.html" );
	$page = preg_replace( "/\\\$CONTENT/", $content, $page );
	$page = preg_replace( "/\\\$TITLE/", $title, $page );
	return $page;
}

