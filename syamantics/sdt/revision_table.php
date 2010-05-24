<?php
namespace syamantics\sdt;

class RevisionTable {

	private $xml = null;
	private $revision_table = null;

	function __construct($xml_file) {
		$this->xml = simplexml_load_file($xml_file);
		$this->generate();
	}

	private function generate() {
		$revision_table = array();

		foreach ($this->xml->logentry as $logentry) {
		  $attributes = $logentry->attributes();
		  $revision = (string) $attributes['revision'];

		  //find ticket number from here
		  $msg = $logentry->msg;
		  $ticket_number = $this->grabTicketNumberFromMsg($msg);

		  $file_names = array();
		  foreach ($logentry->paths as $paths) {
			foreach ($paths->path as $path) {
			  $file_names[] = (string) $path;
			}
		  }

		  $revision_table[$revision] = array (
			'ticket_number' => $ticket_number,
			'file_names' => $file_names
		  );
		}

		$this->revision_table = $revision_table;
	}

	private function grabTicketNumberFromMsg($msg) {
		$msg = (string) $msg;
		
		preg_match_all('/#(\d+)/', $msg, $matches);
		if(!empty($matches[0])) {
			//more than one related ticket
			if(count($matches[1]) > 1) {
				return implode(', ', $matches[1]);
			}
			else {
				return $matches[1][0];
			}
		}
		else if(!empty($msg)) {
			$msg = preg_replace('/[^A-Za-z0-9-]/', '_', $msg);
			$msg = preg_replace('/-+/', "_", $msg);
			return $msg;
		}
		else {
			return uniqid('no_comment_');
		}
	}

	public function get() {
		return $this->revision_table;
	}
}
?>