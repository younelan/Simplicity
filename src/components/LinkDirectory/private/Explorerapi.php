<?php

namespace App\Controllers;

class Explorerapi extends BaseController
{

	public function index($command='help',$param='')
	{
		switch($command)
		{
			case 'category':
				$query=$this->db
				->like('LOWER(catname)',strtolower($param))
				->get('node_tree');
				$category=0;
				$row=$query->result();
				//print_r($row);
				if($row)
				{
					$category=$row[0]->treeid;
				}
				//$this->load->model('ExplorerModel');
				$explorer_model = new \App\Models\ExplorerModel();
				$links=$ExplorerModel->getlinks($category,5,'random()');
				$names=null;

				foreach($links as $link) {
					$names[]=$link->title;
				}
				//print_r($links);
 				if ($names)
					$data['reply']=implode("\n",$names);
				else
					$data['reply']=array();
				$data['places']=$links;
				break;
			case 'zipcode':
			case 'zip':
				if(strlen($param)<2) {
					$data['reply']="Incorrect syntax. \n\nUsage:\nzip [zipcode]";
					break;
				}
				$where=array('nodes_details.field2'=>$param);
				$query=$this->db->select('*')->from('nodes')->join('nodes_details', 'nodes.id=nodes_details.nodeid')
					->order_by('title', 'random')
					->limit(5)->where($where)->get();
				$rows=$query->result();
				$places=array();
				$data['places']=$rows;
				foreach($rows as $row) {
                                      if(isset($row->name))
					$places[]=$row->title;
				}
				if($places)
					$data['reply']= implode("\n" , $places);
				else
					$data['reply'] = 'No places in this zipcode';
				break;
			case 'info':
			case 'help':
				$data['commands']=array("place (placename)", "zip (zipcode)","category (category name)");
				break;
			case 'place':
			case 'poi':
				if(strlen($param)<2) {
					$data['reply']="Incorrect syntax. \n\nUsage:\nplace [placename]";
					break;
				}
                $where=array('nodes_details.field2'=>$param);
				$query=$this->db->select('*')->from('nodes')->join('nodes_details', 'nodes.id=nodes_details.nodeid')
					->order_by('title', 'random')
					->like('LOWER(title)', strtolower($param))
				    ->limit(5)->get();

				$row=$query->result();

				if($row)
				{
					$data['content']=$row;

                    if($row[0]->field1) $address[]=$row[0]->field1;
                    if($row[0]->field2) $zipcode[]=$row[0]->field2;

                    $data['reply']= $row[0]->title;
                    if($row[0]->field4) $data['reply'] .="\nPhone: " . $row[0]->field4;
                    if($row[0]->field1) $data['reply'] .= "\nAddress: " . $row[0]->field1;
                    if($row[0]->field6) $data['reply'] .= "\nURL: " . $row[0]->field6;
                    if($row[0]->field5) $data['reply'] .= "\n" . $row[0]->field5;

				}
				else
				{
					$data['reply']='Place not found. Please check spelling/Try a partial name. For example, place hirshhorn returns HirshHorn museum';
				}
				//print($data['reply'] . '<br/>');
				//print_r($row);exit;
				//print_r($row);
				break;
			default:
				$data['reply']="command $command not found";
		}
		print json_encode($data);
		//$this->load->view('smsreply',$data);

	}
	function fruits() {
		/* first get the fruits */
		$sql='select * from fruits';
		$query=$this->db->query($sql);
		foreach($query->result() as $row)
		{
			$fruits[$row->id]=$row->name;
		}
		//print_r($fruits);

		/* then create the array */
		$sql='select * from people order by name';
		$query=$this->db->query($sql);
		$i=0;
		foreach($query->result() as $row)
		{
			$fruit_numbers=explode(',',$row->favorite_fruit_ids);
			$fruit_field='';
			foreach($fruit_numbers as $key) {
				$fruit_field[]=$fruits[$key];
			}
			$fruit_count=count($fruit_field	);
			$fruit_field= implode(",",$fruit_field) ;
			$people_fruits[$i]=array('name'=>$row->name,'fruits'=>$fruit_field,'n_fruits'=>$fruit_count);
			//could reimplement with function callback
			$sort_array[$i]=$fruit_count;
			$i++;
		}
		array_multisort($sort_array,SORT_DESC,$people_fruits);

		print("<pre>");
		print_r($people_fruits);
	//$row->id

	}
}
