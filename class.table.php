<?php
defined('ABSPATH') or die('Hack your own stuff!');
if (! class_exists('WP_List_Table')) {
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class Survey_List_Table extends WP_List_Table {
	private $per_page;
	private $data;
	function __construct($data, $per_page = 10) {
		$this->per_page = $per_page;
		$this->data = $data;
		global $status, $page;
		parent::__construct(array(
				'singular' => __('survey', 'getopenion'),
				'plural' => __('surveys', 'getopenion'),
				'ajax' => false 
		));
	}
	function no_items() {
		echo __("You don't have any surveys yet.", 'getopenion') . ' <a href="' . GETOPENION_DOMAIN . '/Dashboard">' . __('Add one', 'getopenion') . '</a>';
	}
	function column_default($item, $column_name) {
		switch ($column_name) {
			case 'title' :
				return $item['Name'];
			case 'shortcode' :
				return '[getopenion id="' . $item['ID'] . '" /]';
			default :
				return $item[$column_name];
		}
	}
	function get_sortable_columns() {
		$sortable_columns = array(
				'title' => array(
						'Name',
						false 
				),
				'participants' => array(
						'participants',
						false 
				),
				'questions' => array(
						'questions',
						false 
				),
				'creationDate' => array(
						'creationDate',
						false 
				) 
		);
		return $sortable_columns;
	}
	function get_columns() {
		$columns = array(
				'title' => __('Workingtitle', 'getopenion'),
				'shortcode' => __('Shortcode', 'getopenion'),
				'participants' => __('Responses', 'getopenion'),
				'questions' => __('Questions', 'getopenion'),
				'Date' => __('Created', 'getopenion') 
		);
		return $columns;
	}
	function usort_reorder($a, $b) {
		// If no sort, default to title
		$orderby = (! empty($_GET['orderby']))? $_GET['orderby']:'Name';
		// If no order, default to asc
		$order = (! empty($_GET['order']))? $_GET['order']:'asc';
		// Determine sort order
		if ($orderby != 'creationDate') {
			$result = strcmp($a[$orderby], $b[$orderby]);
		} else {
			$result = strtotime($a[$orderby]) - strtotime($b[$orderby]);
		}
		// Send final sort direction to usort
		return ($order === 'asc')? $result:- $result;
	}
	function column_title($item) {
		$actions = array(
				'edit' => sprintf('<a href="' . GETOPENION_DOMAIN . '/Dashboard/%s?back=%s">%s</a>', $item['ID'], go_backUrl(), __('Edit', 'getopenion')) 
		);
		return sprintf('<strong>%1$s</strong> %2$s', $item['Name'], $this->row_actions($actions));
	}
	function column_cb($item) {
		return sprintf('<input type="checkbox" name="book[]" value="%s" />', $item['ID']);
	}
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array(
				$columns,
				$hidden,
				$sortable 
		);
		if (!empty($this->data)) {
			usort($this->data, array(
					&$this,
					'usort_reorder' 
			));
		}
		
		// Apply filter
		if (isset($_GET['filter_by'])) {
			$data = [];
			foreach($this->data as $d) {
				if ($d['Published']) {
					$data[] = $d;
				}
			}
		} else {
			$data = $this->data;
		}
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$i = 0;
		for($j = ($current_page - 1)*$this->per_page; $j < $total_items; $j ++) {
			if ($i == $this->per_page)
				break;
			$found_data[] = $data[$j];
			$i ++;
		}
		
		$this->set_pagination_args(array(
				'total_items' => $total_items,
				'per_page' => $this->per_page 
		));
		$option = 'per_page';
		$args = array(
				'label' => __('Surveys'),
				'default' => 10 
		);
		add_screen_option($option, $args);
		$this->items = $found_data;
	}
} //class

// Source: http://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/ and modified by Pius Ladenburger
