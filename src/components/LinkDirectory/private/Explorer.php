<?php

namespace App\Controllers;
use App\Models\ExplorerModel as ExplorerModel;

class Explorer extends BaseController {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index($categoryid=0)
	{
		$this->categories($categoryid);
	}

	public function categories($categoryid)
	{
		//$this->load->model('Explorer_model');
		$explorer_model = new \App\Models\ExplorerModel();
//print($categoryid);exit;
		$data['categoryname']=$explorer_model->getTitle($categoryid);
		$data['categories']=$explorer_model->getcategories($categoryid);
		$data['links']=$explorer_model->getlinks($categoryid);
		//$this->load->view('Explorer_view',$data);
        return view('Explorer_view',$data);
	}
	public function item($itemid=0)
	{	
		$itemid=(int)$itemid;
		if($itemid==0) {
			$this->categories();			
		} else {
			//$this->load->model('Explorer_model');
			$explorer_model = new \App\Models\ExplorerModel();
			$data['item']=$explorer_model->getItem($itemid);
			//var_dump($data);
			//exit();
			return view('Explorer_item',$data);

                }
	}
}
