<?php namespace glasteel\SlimResourceRouter;

class ResourceRouter
{
	private $interface = 'namespace glasteel\SlimResourceRouter\ResourceControllerInterface';
	private $slim = false;

	public function register($controller_class,$url_slug,$middleware=[]){
		$this->validateControllerClassOr500($controller_class);
		$args = array_merge(
			[$url_slug . '(/:id(/:action))'],
			$middleware,
			['resource_router_catcher_helper'],
			[function($id=null,$action=null){}]
		);
		call_user_func_array([$this->getSlim(),'map'],$args)
			->via('GET', 'POST', 'PUT', 'DELETE')
			->name($controller_class);
	}//register

	public function switcher($route){
		$controller_class = $route->getName();
		$this->validateControllerClassOr500($controller_class);
		
		$controller_class_instance = new $controller_class;
		$app = $this->getSlim();
		$request = $app->request;
		$method = $request->getMethod();
		
		$params = $route->getParams();

		$id_is_int = ( isset($params['id']) && filter_var($params['id'], FILTER_VALIDATE_INT) ) ? true : false;

		switch($method){
			case 'GET':
				if( count($params) == 0 ){
					$controller_class_instance->index();
				}elseif( $id_is_int ){
					if( isset($params['action']) ){
						switch( $params['action'] ){
							case 'edit':
								$controller_class_instance->get_edit_form();
							break;
							case 'delete':
								pre_r('show confirm delete form');
							break;
							default:
								pre_r('404: ' . __LINE__);
						}
					}else{
						$controller_class_instance->show_resource();
					}
				}elseif( $params['id'] == 'create' ){
					$controller_class_instance->get_create_form();
				}else{
					pre_r('404: ' . __LINE__);
				}
			break;
			case 'POST':
				if( count($params) == 0 && $request->isAjax() ){
					$controller_class_instance->index_tabledata();
				}elseif( count($params) == 1 && $params['id'] == 'create' ){
					$controller_class_instance->new_resource();
				}else{
					pre_r('bad POST endpoint, redirect to list resources');
				}
			break;
			case 'PUT':
				if ( count($params) == 1 && $id_is_int ){
					$controller_class_instance->update_resource();
				}else{
					pre_r('bad PUT endpoint, redirect to list resources');
				}
			break;
			case 'DELETE':
				if ( count($params) == 1 && $id_is_int ){
					pre_r('delete item #' . $params['id']);
				}else{
					pre_r('bad DELETE endpoint, redirect to list resources');
				}
			break;
			default:
				pre_r('Error, unrecognized method - redirect to resources list');
		}//switch $method
	}//switcher

	protected function validateControllerClassOr500($controller_class){
		//TODO refactor
		if ( !class_exists($controller_class) ){
			//TODO redirect to error page
			$this->getSlim()
				->halt(500, 'Controller class ' . $controller_class . ' does not exist.');
		}
		
		$interfaces = class_implements($controller_class);
		if ( !array_key_exists($this->interface, $interfaces) ){
			//TODO redirect to error page
			$this->getSlim()
				->halt(500, 'Controller class ' . $controller_class . ' must implement ' . $this->interface . '.');
		}
	}//validateControllerClassOr500()

	protected function getSlim(){
		if ( $this->slim === false ){
			$this->slim = \Slim\Slim::getInstance();
		}
		return $this->slim;
	}//getSlim()

}//class ResourceRouter

function resource_router_catcher_helper($route){
	$rr = new ResourceRouter;
	return $rr->switcher($route);
}//resource_router_catcher_helper()