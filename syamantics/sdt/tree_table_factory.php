<?php
namespace syamantics\sdt;

include_once('revision_table.php');

include_once('changeset_table.php');
include_once('ticket_table.php');

class TreeTableFactory {

  private static $types = array(
		'changeset' => 'ChangesetTable',
		'ticket' => 'TicketTable'
	);

  public static function getTreeTable($type, RevisionTable $revision_table) {
	if(isset(self::$types[$type])) {
		$class_name = __NAMESPACE__.'\\'.self::$types[$type];
		return new $class_name($revision_table);
	}

	throw new Exception('Invalid TreeTable type');
  }
}
?>

