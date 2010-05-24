<?php
namespace syamantics\sdt;

include_once('tree_table.php');

class ChangesetTable extends TreeTable {

  private $changeset_table = null;
  
  public function __construct(RevisionTable $revision_table_obj) {
    parent::__construct($revision_table_obj);
	$this->generate();	
  }

  private function generate() {
	$changeset_table = array();

	foreach($this->revision_table as $revision => $revision_data)  {
		$changeset_table[$revision] = array();

		$changeset_table[$revision]['ticket_number'] = $revision_data['ticket_number'];

		$file_names = $revision_data['file_names'];
		foreach($file_names as $file_name) {
		  $changeset_table[$revision]['files'][$file_name] = $revision;
		}
    }
	
	$this->changeset_table = $changeset_table;
  }
  
  public function get() {
	  return $this->changeset_table;
  }
}
?>
