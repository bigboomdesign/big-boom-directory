<?php
class CPTD_tax{
	public $name = "";
	public $slug = "";
	public $sing = "";
	public $pl = "";
	public $labels = array();
	public $obj = '';
	
	function __construct($rewrite_slug, $singular_label, $plural_label, $post_type, $heirarchical = true){
		if(
			!is_string($singular_label)
			|| !is_string($plural_label)
			|| !is_string($rewrite_slug)
			|| !is_string($post_type)
			|| !is_bool($heirarchical)
		) return false;
		$this->name = CPTD::clean_str_for_field($singular_label);
		$this->slug = CPTD::clean_str_for_url($rewrite_slug);
		$this->sing = $singular_label;
		$this->pl = $plural_label;
		$this->post_type = $post_type;
		$this->heir = $heirarchical;
		$this->load_labels($this->sing, $this->pl);
	}
	# Register the taxonomy
	public function register_tax(){
		register_taxonomy(
				$this->name,
				$this->post_type,
				array(
					'labels' => $this->labels,
					'rewrite' => array( 'slug' => $this->slug ),
					"hierarchical" => $this->heir
				)
		);
		$this->obj = get_taxonomy($this->name);
	}

	# Label array for taxonomy
	private function load_labels($sing, $pl){
		$this->labels = array(
			'name'              => $this->pl,
			'singular_name'     => $this->sing,
			'search_items'      => "Search " . $this->pl,
			'all_items'         => "All " . $this->pl,
			'parent_item'       => "Parent " . $this->sing,
			'parent_item_colon' => "Parent " . $this->sing . ":",
			'edit_item'         => "Edit " . $this->sing,
			'update_item'       => "Update " . $this->sing,
			'add_new_item'      => "Add New " . $this->sing,
			'new_item_name'     => "New " . $this->sing . " Name",
			'menu_name'         => $this->pl,
		);
	}	
}

?>