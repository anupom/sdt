<?php
namespace syamantics\sdt;

include_once('tree_table.php');

class DependencyTree {

  private $ticket_table = null;

  private $dependency_tree = null;
  private $trace_ticket_numbers = array();

  public function __construct(TreeTable $ticket_table_object) {
	  $this->ticket_table = $ticket_table_object->get();
  }

  public function get($ticket_number) {
    $this->trace_ticket_numbers = array();
    $this->dependency_tree = array();
    $this->getRecursive($ticket_number, $this->dependency_tree);
	return $this->dependency_tree;
  }
  
  private function getRecursive($ticket_number, &$subtree) {
    //if this ticket is already analyzed then skip it to avoid infinite looping
	if(isset($this->trace_ticket_numbers[$ticket_number])) {
      return false;
    }

	$this->trace_ticket_numbers[$ticket_number] = true;
    
    $files = $this->ticket_table[$ticket_number]['files'];
    
    //find depended tickets
    $dependent_tickets = array();
    foreach($files as $file => $revision) {
      foreach($this->ticket_table as $ticket => $ticket_data) {
	if($ticket == $ticket_number) {
	  continue;
	}
	if(!isset($ticket_data['files'][$file])) {
	  continue;
	}
	if($ticket_data['files'][$file] > $revision) {
	  continue;
	}
	if(!in_array($ticket, $dependent_tickets)) {
	  $dependent_tickets[] = $ticket;
	}
      }
    }
    
    $subtree[$ticket_number]['files'] = $this->ticket_table[$ticket_number]['files'];
    $subtree[$ticket_number]['dependents'] = array();
    
    //now dig into each individual dependent ticket
    foreach($dependent_tickets as $ticket) {
      $this->getRecursive($ticket, $subtree[$ticket_number]['dependents']);
    }
  }

  public function listTickets() {
	return array_keys($this->ticket_table);
  }
}
?>
