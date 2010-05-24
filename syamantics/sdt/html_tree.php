<?php
namespace syamantics\sdt;

include_once('dependency_tree.php');

class HTMLTree {

  private $dependency_tree_obj = null;
  
  private $trace_file_names = array();
  private $current_root_ticket = 0;

  private $ul_tag = "<ul>%s</ul>";
  private $ul_root_tag = "<ul class=\"root\">%s</ul>";
  private $li_ticket_tag = "<li class=\"ticket\">%s</li>";
  private $li_file_tag = "<li id=\"%s\" class=\"file\">%s [r%s]</li>";
  private $style_tag = "<style>%s</style>";
  private $span_tag = "<span>%s</span>";
  private $p_tag = "<p>%s</p>";
  private $pre_command_tag = "<pre class=\"command\">%s</pre>";
  private $h4_title_tag = "<h4>Dependency Tree For #%s</h4>";

  private $command_format =  "sudo svn up -r %d %s";
  private $default_style = ".file {text-decoration: line-through;}\n";
  private $highlighted_style = "{ text-decoration: none; }";
  private $ticket_summary_text = "Ticket %s is started at revision #%s and completed at revision #%s";

  private $tickets = array();
  
  public function __construct(DependencyTree $dependency_tree_obj) {
    $this->dependency_tree_obj = $dependency_tree_obj;
  }

  public function get($ticket_number) {
    $dependency_tree = $this->dependency_tree_obj->get($ticket_number);
	
	$this->current_root_ticket = $ticket_number;
	$this->tickets[$ticket_number] =  array();
    $this->trace_file_names = array();

	$html_str = $this->getRecursive($dependency_tree);
	
	$style_str = $this->default_style;
	$command_str = '';
	
	$highlighted_revisions = array();
	$subcommands = array();
	$high = 0;
	$low = 0;
	foreach($this->trace_file_names as $filename => $revision) {
		$highlighted_revisions[] = '#'.$this->getHTMLIdAttr($filename, $revision);
		$subcommands[] = sprintf($this->command_format, $revision, ltrim($filename, '\/'));
		
		if($revision > $high) {
			$high = $revision;
		}
		if(empty($low) || $revision < $low) {
			$low = $revision;
		}
	}

	$this->tickets[$ticket_number]['high'] = $high;
	$this->tickets[$ticket_number]['low'] = $low;
	
	$style_str .= implode($highlighted_revisions, ",\n") . $this->highlighted_style;
	$style_str = sprintf($this->style_tag, $style_str);

	$command_str .= sprintf($this->pre_command_tag, implode("\n", $subcommands));

	$ticket_summary_str =
		sprintf($this->p_tag, sprintf($this->ticket_summary_text,
			$ticket_number, $low, $high));

	//$html_str = $style_str . $html_str . $command_str .$ticket_summary_str;
	$html_str = $style_str . $html_str;

	return $html_str;
  }
  
  private function getRecursive($dependency_tree, $root = true) {
    if(empty($dependency_tree)) {
      return false;
    }
	//determine if it's the first step in recursion
	$top_ul_tag = $this->ul_tag;
	if($root) {
		$top_ul_tag = $this->ul_root_tag;
	}

	$str = '';
    foreach($dependency_tree as $revision_number => $data) {
      $files = '';
      foreach($data['files'] as $file => $revision) {
		if(!isset($this->trace_file_names[$file])) {
			$this->trace_file_names[$file] = 0;
		}
		if($this->trace_file_names[$file] < $revision) {
			$this->trace_file_names[$file] = $revision;
		}
		$files .= sprintf($this->li_file_tag, $this->getHTMLIdAttr($file, $revision),
						$file, $revision);
	  }
      
      $dependents = $this->getRecursive($data['dependents'], false);
	  $str .= sprintf($this->li_ticket_tag, 
					sprintf($this->span_tag, $revision_number)
					.sprintf($this->ul_tag, $files.$dependents));
    }
	return sprintf($top_ul_tag, $str);
  }

  public function getFull() {
	$html_str = '';
	$tickets  = $this->dependency_tree_obj->listTickets();
	foreach($tickets as $ticket) {
		$html_str .= sprintf($this->h4_title_tag, $ticket);
		$html_str .= $this->get($ticket);
	}
	//$this->getStablePoints();
	return $html_str;
  }

  private function getHTMLIdAttr($file, $revision) {
	return 'f_'.
	preg_replace('/[^A-Za-z0-9-]/', '_', $this->current_root_ticket).'_'.md5($file).'_'.$revision;
  }

  /*public function getStablePoints() {
	  $stable_points = array();
	  foreach($this->tickets as $a_ticket_number => $a_ticket_data) {
		//print_r($this->tickets); echo '<hr/>';
		//this ticket is already analyzed
		if(!empty($a_ticket_data['crossed'])) {
			continue;
		}

		$this->tickets[$a_ticket_number]['crossed'] = true;

		$stable_point = array(
			'low' => $a_ticket_data['low'],
			'high' => $a_ticket_data['high'],
			'tickets' => array($a_ticket_number)
		);
		foreach($this->tickets as $b_ticket_number => $b_ticket_data) {
			if($a_ticket_number == $b_ticket_number) {
				continue;
			}
			if($b_ticket_data['low'] <= $a_ticket_data['low']
			&& $b_ticket_data['high'] >= $a_ticket_data['low']) {
				$b_ticket_data['crossed'] = true;
				$stable_point['tickets'][] = $b_ticket_number;
				$this->tickets[$b_ticket_number]['crossed'] = true;
				$stable_point['low'] = $b_ticket_data['low'];
			}
			else if($b_ticket_data['high'] >= $a_ticket_data['high']
			&& $b_ticket_data['low'] <= $a_ticket_data['high']) {
				$stable_point['tickets'][] = $b_ticket_number;
				$this->tickets[$b_ticket_number]['crossed'] = true;
				$stable_point['high'] = $b_ticket_data['high'];
			}
			else if($b_ticket_data['low'] < $a_ticket_data['low']
			&& $b_ticket_data['high'] > $a_ticket_data['high']) {
				$stable_point['tickets'][] = $b_ticket_number;
				$this->tickets[$b_ticket_number]['crossed'] = true;
				$stable_point['high'] = $b_ticket_data['high'];
				$stable_point['low'] = $b_ticket_data['low'];
			}
			else if($b_ticket_data['low'] > $a_ticket_data['low']
			&& $b_ticket_data['high'] < $a_ticket_data['high']) {
				$stable_point['tickets'][] = $b_ticket_number;
				$this->tickets[$b_ticket_number]['crossed'] = true;
			}
		}
		$stable_points[] = $stable_point;
	  }
  }*/
}
?>
