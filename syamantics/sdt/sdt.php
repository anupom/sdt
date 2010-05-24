<?php
namespace syamantics\sdt;

include_once('revision_table.php');
include_once('tree_table_factory.php');
include_once('dependency_tree.php');
include_once('html_tree.php');

class SDT {
	
  private $html_tree = null;

  public function __construct($table_type, $svn_log_xml) {
	$this->html_tree = new HTMLTree( new DependencyTree(
		TreeTableFactory::getTreeTable($table_type,
			new RevisionTable($svn_log_xml))
	));
  }

  public function printAll() {
	echo $this->html_tree->getFull();
  }
}
?>
