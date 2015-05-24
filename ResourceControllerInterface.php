<?php namespace glasteel\SlimResourceRouter;

interface ResourceControllerInterface
{
	public function index();

	public function index_tabledata();

	public function show_resource();

	public function get_edit_form();

	public function get_create_form();

	public function update_resource();

	public function new_resource();
	
}//interface ResourceControllerInterface