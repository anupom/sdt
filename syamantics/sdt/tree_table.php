<?php
namespace syamantics\sdt;

include_once('table.php');

abstract class TreeTable implements Table {

  protected $revision_table = null;
  
  public function __construct(RevisionTable $revision_table_obj) {
    $this->revision_table = $revision_table_obj->get();
  }
}
?>
