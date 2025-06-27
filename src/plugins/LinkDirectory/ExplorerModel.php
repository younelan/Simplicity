<?php
namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\DBLayer;

class ExplorerModel extends \Opensitez\Simplicity\DBLayer {
	private $site;
	function is_valid_slug($slug) {
		return preg_match('/^[a-zA-Z0-9-_]+$/', $slug);
	}
	function get($var) 
	{
		switch ($var) {
			case 'categories':
				return $this->getcategories($this->site, 0);
			case 'node_types':
				return $this->get_node_types();
			case 'links':
				return $this->getlinks($this->site, 0);
			default:
				return [];
		}
	}
	function getcategories($site,$categoryid) {

		if(!$this->is_valid_slug($categoryid)) { 
			$categoryid=0;
		} 
		if(!is_numeric($categoryid)) {

			$tree_table = $this->osz_fields['tbl_tree'];
			$lookup_query = "SELECT treeID FROM $tree_table WHERE slug = '$categoryid' LIMIT 1";
			$lookup_result = $this->fetch_query($lookup_query);
			if($lookup_result && count($lookup_result) > 0) {
				$categoryid = intval($lookup_result[0]['treeID']);
			} else {
				$categoryid = 0;
			}
		}
		$site = intval($site);

		
		if($categoryid)
			$sort=' order by catname';
		else
			$sort='';
		$tree_table = $this->osz_fields['tbl_tree'];
		$node_table = $this->osz_fields['tbl_nodes'];
		$feature = $this->osz_fields['node_group'];

		$catcount = 		"		( SELECT COUNT(*)
		FROM $tree_table c2
		WHERE c2.parent = c.treeid ) AS catcount
		
		GROUP BY c.treeID,c.catname,c.icon,c.parent,c.slug  $sort ;
		";
$myquery="SELECT tree1.treeID,node.category1,tree1.slug, COUNT(node.id) AS poicount, 
( SELECT COUNT(*)
		FROM $tree_table tree2
		WHERE tree2.parent = tree2.treeID ) AS catcount
, tree1.catname, tree1.icon, tree1.parent, tree1.treeid , tree1.*
FROM $tree_table tree1
LEFT OUTER JOIN $node_table node ON tree1.treeid=node.category1
LEFT JOIN $tree_table tree2 ON tree1.parent=tree2.treeid
WHERE (tree2.treeID = \"$categoryid\" or tree2.slug = \"$categoryid\")
AND tree1.$feature='$site'
GROUP BY tree1.treeID,tree1.catname,tree1.icon,tree1.parent,tree1.slug  $sort ;";


		$results=$this->fetch_query($myquery);

		// exit;
		$data=[];
		if($results) {
			foreach ($results as $row)
			{

				if($row['slug']) {
					$slug=$row['slug'];
				} else {
					$slug=$row['treeID'];
				}
				$url='categories/' . $slug ;
				$data[$url]=$row;
			}
	
		} else {

		}

		return $data;	
	}
	function getlinks($feature,$categoryid,$limit=0,$order='title asc') {	
		if(!$this->is_valid_slug($categoryid)) { 
			$categoryid=0;
		} 
		if(!is_numeric($categoryid)) {

			$tree_table = $this->osz_fields['tbl_tree'];
			$lookup_query = "SELECT treeID FROM $tree_table WHERE slug = '$categoryid' LIMIT 1";
			$lookup_result = $this->fetch_query($lookup_query);
			if($lookup_result && count($lookup_result) > 0) {
				$categoryid = intval($lookup_result[0]['treeID']);
			} else {
				$categoryid = 0;
			}
		}
		$node_table = $this->osz_fields['tbl_nodes'];
        $app['fields']= ["*"];
		//$app['debug']=true;
		$where = [
			["type"=>"OR","field"=>"category1","value"=>$categoryid],
			["type"=>"OR","field"=>"category2","value"=>$categoryid],
		];
		$app['where']=$where;
        $results=$this->query_nodes($app);

		return $results;	
	}
	function retrieve_item($site,$item) {
		$item = intval($item);
		$node_table = $this->osz_fields['tbl_nodes'];
        $app['fields']= ["*"];
		$where = [
			["type"=>"OR","field"=>"id","value"=>$item],
		];
		$app['where']=$where;
        $results=$this->query_nodes($app);

		return $results;

	}
}
