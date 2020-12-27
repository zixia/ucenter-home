<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: diff.class.php 16698 2008-11-14 07:58:56Z cnteacher $
*/

class Diff {
	var $table = array();
	var $left = array();
	var $right = array();
	var $left_len = 0;
	var $right_len = 0;

	function Diff($left, $right) {
		$this->left = preg_split('/(\r\n|\n|\r)/', $left);
		$this->right = preg_split('/(\r\n|\n|\r)/', $right);
		$this->left_len = count($this->left);
		$this->right_len = count($this->right);
	}

	function getrow($row) {
		$return = array();
		$i = -1;
		foreach(explode('|', $row) AS $value) {
			$return[$i] = $value;
			$i++;
		}
		return $return;
	}

	function &fetch_diff() {
		$prev_row = array();
		for($i = -1; $i < $this->right_len; $i++) {
			$prev_row[$i] = 0;
		}
		for($i = 0; $i < $this->left_len; $i++) {
			$this_row = array('-1' => 0);
			$data_left_value = $this->left[$i];
			for($j = 0; $j < $this->right_len; $j++) {
				if($data_left_value == $this->right[$j]) {
					$this_row[$j] = $prev_row[$j - 1] + 1;
				} elseif($this_row[$j - 1] > $prev_row[$j]) {
					$this_row[$j] = $this_row[$j - 1];
				} else {
					$this_row[$j] = $prev_row[$j];
				}
			}
			$this->table[$i - 1] = implode('|', $prev_row);
			$prev_row = $this_row;
		}
		unset($prev_row);
		$this->table[$this->left_len - 1] = implode('|', $this_row);
		$table = &$this->table;
		$output = $match = $nonmatch1 = $nonmatch2 = array();
		$data_left_key = $this->left_len - 1;
		$data_right_key = $this->right_len - 1;
		$this_row = $this->getrow($table[$data_left_key]);
		$above_row = $this->getrow($table[$data_left_key - 1]);
		while($data_left_key >= 0 AND $data_right_key >= 0) {
			if($this_row[$data_right_key] != $above_row[$data_right_key - 1] AND $this->left[$data_left_key] == $this->right[$data_right_key]) {
				$this->nonmatches($output, $nonmatch1, $nonmatch2);
				array_unshift($match, $this->left[$data_left_key]);
				$data_left_key--;
				$data_right_key--;
				$this_row = $above_row;
				$above_row = $this->getrow($table[$data_left_key - 1]);
			} elseif($above_row[$data_right_key] > $this_row[$data_right_key - 1]) {
				$this->matches($output, $match);
				array_unshift($nonmatch1, $this->left[$data_left_key]);
				$data_left_key--;
				$this_row = $above_row;
				$above_row = $this->getrow($table[$data_left_key - 1]);
			} else {
				$this->matches($output, $match);
				array_unshift($nonmatch2, $this->right[$data_right_key]);
				$data_right_key--;
			}
		}

		$this->matches($output, $match);
		if($data_left_key > -1 OR $data_right_key > -1) {
			for(; $data_left_key > -1; $data_left_key--) {
				array_unshift($nonmatch1, $this->left[$data_left_key]);
			}
			for(; $data_right_key > -1; $data_right_key--) {
				array_unshift($nonmatch2, $this->right[$data_right_key]);
			}
			$this->nonmatches($output, $nonmatch1, $nonmatch2);
		}

		return $output;
	}

	function matches(&$output, &$match) {
		if(count($match) > 0) {
			$data = implode("\n", $match);
			array_unshift($output, new Diff_Entry($data, $data));
		}
		$match = array();
	}

	function nonmatches(&$output, &$text_left, &$text_right) {
		$s1 = count($text_left);
		$s2 = count($text_right);
		if($s1 > 0 AND $s2 == 0) {
			array_unshift($output, new Diff_Entry(implode("\n", $text_left), ''));
		} elseif($s2 > 0 AND $s1 == 0) {
			array_unshift($output, new Diff_Entry('', implode("\n", $text_right)));
		} elseif($s1 > 0 AND $s2 > 0) {
			array_unshift($output, new Diff_Entry(implode("\n", $text_left), implode("\n", $text_right)));
		}
		$text_left = $text_right = array();
	}
}

class Diff_Entry {
	var $left = '';
	var $right = '';

	function Diff_Entry($data_left, $data_right) {
		$this->left = $data_left;
		$this->right = $data_right;
	}

	function left_class() {
		if($this->left == $this->right)	{
			return 'unchanged';
		} elseif($this->left AND empty($this->right)) {
			return 'deleted';
		} elseif(trim($this->left) === '') {
			return 'notext';
		} else {
			return 'changed';
		}
	}

	function right_class() {
		if($this->left == $this->right) {
			return 'unchanged';
		} elseif($this->right AND empty($this->left)) {
			return 'added';
		} elseif(trim($this->right) === '') {
			return 'notext';
		} else {
			return 'changed';
		}
	}

	function diff_text($string, $wrap = true) {
		if(trim($string) === '') {
			return '&nbsp;';
		} else {
			return $wrap ? '<code>'.str_replace(array('  ', "\t"), array('&nbsp;&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;'), nl2br(htmlspecialchars($string))).'</code>' : '<pre style="display:inline">'.htmlspecialchars($string).'</pre>';
		}
	}
}

?>