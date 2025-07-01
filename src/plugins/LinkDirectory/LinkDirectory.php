<?php

namespace Opensitez\Simplicity\Plugins;
use \Opensitez\Simplicity\Plugin;
use \Opensitez\Simplicity\MSG;
$root=dirname(dirname(__DIR__));
//require_once("$root/core.php");
require_once(__DIR__ . "/ExplorerModel.php");



class LinkDirectory extends \Opensitez\Simplicity\Plugin
{
	private $explorer_model;
	private $node_types;
	private $site;
	public $name = "Link Directory";
	public $description = "A simple link directory for points of interest";
	private $style = "
	<style>
	#node-details .label {color: #069;font-weight: bold;}
	#map { border:1px solid #ccc;height:300px;width: 50%; }
	.item-count {color: red!important; font-size: 0.9em; margin-top: 5px;}
	.ui-li-icon { float: left; top:0.3em; margin-right:10px;}
	</style>
	";
	public function on_event($event)
	{
		if ($event['type'] === MSG::PluginLoad) {
			$this->framework->register_type('routetype', 'linkdirectory');
			$this->framework->register_type('blocktype', 'linkdirectory');
		}
		parent::on_event($event);
	}
	public function index($categoryid = 0)
	{
		$this->categories($categoryid);
	}
	function get_menus($app = [])
	{
		$menus = [
			"content" => [
				"text" => "Content",
				"image" => "genimgguestlist.png",
				"children" => [
					"linkdir" => ["plugin" => "linkdirectory", "page" => "default", "text" => "Link Directory", "category" => "all"],
				]
			],

		];
		return $menus;
	}
	public function init()
	{
		$this->explorer_model = new \Opensitez\Simplicity\Plugins\ExplorerModel($this->config_object);
		$this->explorer_model->connect();
		$this->node_types = $this->explorer_model->get('node_types');
		return $this->node_types;
	}
	public function categories($dest = null, $feature_id = null)
	{
		$explorer_model = $this->explorer_model;
		$data['categories'] = $explorer_model->getcategories($feature_id, $dest);
		$data['links'] = $explorer_model->getlinks($feature_id, $dest);
		$data['node_types'] = $this->node_types;

		$debug = $this->get_component('debug');

		return $data;
	}
	public function get_item($itemid = 0)
	{
		$app = $this->app ?? [];
		$action = strtolower($params[0] ?? "category");
		$dest = strtolower($params[1] ?? "");
		$dest = intval($dest);
		$feature = intval($app['id'] ?? 0);

		$itemid = (int)$itemid;
		$map_key = $app["map"]['api-key'] ?? "";
		if ($itemid == 0) {
			return $this->categories();
		} else {
			$explorer_model = new ExplorerModel($this->config_object);
			$data = $explorer_model->retrieve_item($feature, $itemid);
			if ($data) {
				foreach ($data as $val) {
					$item = $val;
				}
				$retval = ["item" => $item, "node_types" => $this->node_types];
				return $retval;
			} else {
				return false;
			}
		}
	}
	function render_item($data)
	{
		$block_plugin = $this->framework->get_component('block');
		$dbitem = $data['item'] ?? [];
		$node_types = $data['node_types'];
		$output = "";
		$app = $this->app;
		$itemhide = $app['item']['hide'] ?? [];
		if (!is_array($itemhide))
			$itemhide = [$itemhide];


		$output .= "<h1>" . $dbitem['title'] . "</h1>";
		$itemtitle = $app['item']['title'] ?? "Description";
		$showmap = $app['map']['show'] ?? false;
		$addressfields = $app['map']['address-fields'] ?? [];
		$content_type = $app['item']['content-type'] ?? "html";
		$map_key = $app["map"]['api-key'] ?? "";
		$map_fields = $app["map"]['address-fields'] ?? "";
		$dir_fields = $app["fields"] ?? ["phone", "cost", "zipcode", "address"];
		$options = [
			'content-type' => $content_type
		];
		$item = [];
		foreach ($dbitem as $key => $value) {
			$item[$key] = $value;
		}
	
		$body = $block_plugin->render_insert_text($item['body'] ?? "", $options, $app);
		$output .= $this->style;
		$output .= "
		<div id=node-details>
		<div class=field>
		<div class='label'>$itemtitle</div><div class='label'></div><div class=description>" . ($body) . "
		</div>
		";

		foreach ($node_types[$dbitem['node_type']]['fields'] ?? [] as $field => $dbfield) {
			$item[$dbfield] = $dbitem["field$field"];
			if (in_array($dbfield, $itemhide)) {

				continue;
			}
			if (isset($dbitem["field" . $field])) {
				if (strtolower($dbfield) == "url") {
					if ($dbitem["field" . $field]) {
						$output .= "<div class=field>
						<div class='label'>URL</div><div class=url><a href='" . $dbitem["field" . $field] . "'>" . $dbitem["field" . $field] . "</a>
						</div>";
					}
				} else {
					if ($dbitem["field" . $field]) {
						$output .= "<div><b>" . ucfirst($dbfield) . ":</b> " . $dbitem["field" . $field] . "</div>";
					}
				}
			}
		}
		if (!is_array($addressfields)) {
			$addressfields = [$addressfields];
		}
		$addressvalues = [];
		foreach ($addressfields as $current) {
			$addressvalues[] = str_replace("\n", ", ", $item[$current] ?? "");
		}
		$current_address = addslashes(implode(", ", $addressvalues));
		// if($item['url']??"") {
		// 	$output .="<div class=field>
		// 	<div class='label'>URL</div><div class=url><a href='" . ($item['url']??'') . "'>" . ($item['url']??'') . "</a>
		// 	</div>";	

		// }
		if ($showmap) {
			$output .= "
			<script src='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js'></script>
			<link href='https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css' rel='stylesheet' />";
			$output .= "
			<div id='map'></div>
			<script src='https://unpkg.com/es6-promise@4.2.4/dist/es6-promise.auto.min.js'></script>
			<script src='https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js'></script>
			<script>
				mapboxgl.accessToken = '$map_key';
			var mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });
			mapboxClient.geocoding
			.forwardGeocode({
			query: '";
			if ($current_address ?? '') {
				$output .= $current_address;;
			} else {
				$output .= $item['title'] ?? '' . "," . $item['country'] ?? "USA" . "'";
			}
			$output .= "',
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
			</div>";
		}

		return $output;
	}
	function render_category($data)
	{
		$output = "";
		$app = $this->app;
		$imgpath = $app['images'] ?? "";
		if (!$imgpath) $imgpath .= "/";
		$paths = $this->config_object->get('paths');
		$route = $app['route'] ?? "";
		$cathide = $app['category']['hide'] ?? [];
		if (!is_array($cathide))
			$cathide = [$cathide];
		$defaulticon = $app['category']['default-icon'] ?? "empty.gif";
		if ($data['categories']) {
			$output .= "
			<ul class='list-group'>
			<li class='list-group-item list-group-item-primary'>Categories</li>			
			";

			foreach ($data['categories'] as $url => $row) {
				$catcount = $row['catcount'] ?? 0;
				$poicount = $row['poicount'] ?? 0;
				if ((!$catcount) && (!$poicount))
					continue;
				if (in_array(strval($row['treeID']), $cathide) || in_array($row['slug'], $cathide)) {
					continue;
				}
				if ($route) {
					$url = "$route/" . ltrim($url, "/"); // Ensure relative links

				}
				if (isset($row['icon']) && $row['icon'])
					$icon = $row['icon'];
				else {
					if ($defaulticon) {
						$icon = $defaulticon;
					} else {
						$icon = 'empty.gif';
					}
				}

				$output .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . "\n";
				$iconurl = '';
				$itemCount = array();
				$poiword = $app['replacements']['poi'] ?? "poi";
				$catword = $app['replacements']['poi'] ?? "cat";
				if ($row['catcount']) $itemCount[] = $row['catcount'] . ' ' . $catword;
				if ($row['poicount']) $itemCount[] = $row['poicount'] . ' ' . $poiword;
				if ($itemCount) {
					$itemCount = implode('/', $itemCount);
				} else {
					$itemCount = '&nbsp;';
				}
				if ($icon <> '') {
					$iconurl = '<img class="ui-li-icon" src="' 
								. $this->absolute_link(  $imgpath . $icon) . "\">\n";
				}
				$output .= "<span class='link-item-text'>"
					. $this->anchor($url, $row['catname'] . $iconurl, 'rel=external')
					. "</span>\n";
				$output .= $this->style;
				$output .= "<div class='item-count'>$itemCount</div>\n";
				$output .= "</li>\n";
			}
			$output .= "</ul>";
		}
		return $output;
	}
	function render_links($data)
	{
		$output = "";
		$app = $this->app;
		$route = $app['route'] ?? "";
		$links = $data['links'] ?? [];
		$node_types = $this->node_types ?? [];
		$cathide = $app['category']['hide'] ?? [];
		if (!is_array($cathide))
			$cathide = [$cathide];

		$categorytitle = $app['category']['title'] ?? "Points of interest";
		if (is_array($links) && count($links) > 0) {

			$output .= '
			<ul class="list-group">
			<li class="list-group-item list-group-item-primary">' . $categorytitle . '</li>';
		}
		foreach ($links as $linkurl => $row) {
			$address = [];
			if (in_array($linkurl, $cathide)) {
				continue;
			}

			$output .= '<li class="list-group-item list-group-item-action flex-column align-items-start">';
			$output .= $this->anchor(rtrim($route, "/") . "/item/" . $row['id'], ucfirst($row['title'])); // Ensure relative links
			foreach ($node_types[$row['node_type']]['fields'] ?? [] as $field => $dbfield) {
				//print $field;
				if (isset($row["field" . $field]) && $row["field" . $field] && (!in_array(strtolower($dbfield), $cathide))) {
					$output .= "<div><b>" . ucfirst($dbfield) . ":</b> " . ucfirst($row["field" . $field]) . "</div>";
				}
			}
			$output .= "</li>";
		}
		return $output;
	}
	public function on_render_page($app)
	{

		$this->init();
		if (!$app) {
			$app = $this->options;
		}
		$this->app = $app;
		$retval = "";

		$action = strtolower($app['segments'][0] ?? "category");
		if (!$action) {
			$action = "category";
		}

		$dest = strtolower($app['segments'][1] ?? "");
		if (!$dest) {
			$dest =0;
		}
		$feature_id = intval($app['id'] ?? 0) ?? null;
		if ( is_int($dest) || ctype_alnum($dest)) {
			$feature_id = $app['id'] ?? "";
		} else {
			$feature_id = null;
		}

		$debug = $this->get_component('debug');

		if ($action == "category" || $action == "categories") {
			$data =	$this->categories($dest, $feature_id);

			$retval = $this->render_category($data);
			$retval .=  $this->render_links($data);
		} elseif ($action == 'item') {
			$data =	$this->get_item($dest);
			$retval = $this->render_item($data);
		}
		return $retval;
	}
	function render($app=false) {
		if(!$app) {
			$app = $this->options;
		}
		return $this->on_render_page($app);
	}
}
