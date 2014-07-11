<?php
# Requires CPTDirectory
class CPTD_pt{
	public $name = "";
	public $slug = "";
	public $sing = "";
	public $pl = "";
	public $labels = array();

	function __construct($rewrite_slug, $singular_label, $plural_label){
		if(
			!is_string($singular_label)
			|| !is_string($plural_label)
			|| !is_string($rewrite_slug)
		) return false;
		$this->name = CPTDirectory::str_to_field_name($singular_label);
		$this->slug = CPTDirectory::clean_str_for_url($rewrite_slug);
		$this->sing = $singular_label;
		$this->pl = $plural_label;
		
		$this->load_labels();

	}
	public function register_pt(){
		register_post_type(
			$this->name,
			array(
				"labels" => $this->labels,
				"public" => true,
				"has_archive" => true,
				"rewrite" => array('slug' => $this->slug),
				"supports" => array("title", "editor", "thumbnail", "custom-fields"),
			)
		);
	}
	private function load_labels(){
		$this->labels = array(
			'name'               => $this->pl,
			'singular_name'      => $this->sing,
			'menu_name'          => $this->pl,
			'name_admin_bar'     => $this->sing,
			'add_new'            => "Add New",
			'add_new_item'       => "Add New " . $this->sing,
			'new_item'           => "New " . $this->sing,
			'edit_item'          => "Edit " . $this->sing,
			'view_item'          => "View " . $this->sing,
			'all_items'          => "All " . $this->pl,
			'search_items'       => "Search " . $this->pl,
			'parent_item_colon'  => "Parent " . $this->pl . ":",
			'not_found'          => "No ". strtolower($this->pl) . " found.",
			'not_found_in_trash' => "No ". strtolower($this->pl) . " found in Trash.",
		);
	}	
}
?>