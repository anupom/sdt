<?php
namespace syamantics\sdt;

include_once('tree_table.php');

class TicketTable extends TreeTable {

  private $ticket_table = null;
  
  public function __construct(RevisionTable $revision_table_obj) {
    parent::__construct($revision_table_obj);
	$this->generate();	
  }

  private function generate() {
	$ticket_table = array();
	foreach($this->revision_table as $revision => $revision_data)  {
		$ticket_number = $revision_data['ticket_number'];
		if(!isset($ticket_table[$ticket_number])) {
		  $ticket_table[$ticket_number] = array();
		}
		$file_names = $revision_data['file_names'];
		foreach($file_names as $file_name) {
		  if(!isset($ticket_table[$ticket_number]['files'][$file_name])
			|| $ticket_table[$ticket_number]['files'][$file_name] < $revision) {
			$ticket_table[$ticket_number]['files'][$file_name] = $revision;
		  }
		}
    }

	$this->ticket_table = $ticket_table;
  }
  
  public function get() {
	  return $this->ticket_table;
  }
}
?>
