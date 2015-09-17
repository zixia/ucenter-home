<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: image.php 20757 2009-10-19 01:50:23Z monkey $
*/


error_reporting(0);

if(empty($_GET['aid']) || empty($_GET['size']) || empty($_GET['key'])) {
	exit;
}

$nocache = !empty($_GET['nocache']) ? 1 : 0;
$aid = intval($_GET['aid']);
list($w, $h) = explode('x', $_GET['size']);
$w = intval($w);
$h = intval($h);
$thumbfile = '';
if(!$nocache) {
	$thumbfile = 'forumdata/imagecaches/'.$aid.'_'.$w.'_'.$h.'.jpg';
	if(file_exists($thumbfile)) {
		$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
		$boardurl = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].preg_replace("/\/+(api|archiver|wap)?\/*$/i", '', substr($PHP_SELF, 0, strrpos($PHP_SELF, '/'))).'/');
		header('location: '.$boardurl.$thumbfile);
		exit;
	}
}

define('CURSCRIPT', 'image');
define('NOROBOT', TRUE);

require_once './include/common.inc.php';

list($daid, $dw, $dh) = explode("\t", authcode($_GET['key'], 'DECODE', $_DCACHE['settings']['authkey']));

if($daid != $aid || $dw != $w || $dh != $h) {
	dheader('location: '.$boardurl.'images/common/none.gif');
}

if($attach = $db->fetch_array($db->query("SELECT remote, attachment FROM {$tablepre}attachments WHERE aid='$aid' AND isimage IN ('1', '-1')"))) {
	if($attach['remote']) {
		$filename = $ftp['attachurl'].'/'.$attach['attachment'];
	} else {
		$filename = $attachdir.'/'.$attach['attachment'];
	}
	$img = new Image_Lite($filename, !$nocache ? $thumbfile : '');
	if($img->attachinfo === FALSE || $img->Thumb($w, $h) && !$nocache) {
		dheader('location: '.($thumbfile ? $boardurl.$thumbfile : $filename));
	}
	@readfile($filename);
}

class Image_Lite {
	var $attachinfo = '';
	var $srcfile = '';
	var $targetfile = '';
	var $imagecreatefromfunc = '';
	var $imagefunc = '';
	var $attach = array();
	var $animatedgif = 0;
	var $smallimg = 0;
	var $img_w = 0;
	var $img_h = 0;
	var $thumbwidth = 0;
	var $thumbheight = 0;

	function Image_Lite($srcfile, $targetfile) {
		$this->srcfile = $srcfile;
		$this->targetfile = $targetfile ? $targetfile : '';
		$this->attachinfo = @getimagesize($srcfile);
		list($this->img_w, $this->img_h) = $this->attachinfo;
		if($targetfile) {
			@mkdir(DISCUZ_ROOT.'./forumdata/imagecaches/', 0777);
		}
	}

	function Thumb($thumbwidth, $thumbheight) {
		global $imagelib, $imageimpath;
		$this->thumbwidth = $thumbwidth;
		$this->thumbheight = $thumbheight;
		$this->smallimg = $this->img_w < $this->thumbwidth || $this->img_h < $this->thumbheight;
		return $imagelib && $imageimpath && $this->targetfile && !$this->smallimg ? $this->Thumb_IM() : $this->Thumb_GD();
	}

	function Size() {
		$imgratio = $this->img_w / $this->img_h;
		$thumbratio = $this->thumbwidth / $this->thumbheight;
		if($imgratio >= 1 && $imgratio >= $thumbratio || $imgratio < 1 && $imgratio > $thumbratio) {
			$cuth = $this->img_h;
			$cutw = $cuth * $thumbratio;
			$startx = ($this->img_w - $thumbratio * $this->img_h) / 2;
			$starty = 0;
		} elseif($imgratio >= 1 && $imgratio <= $thumbratio || $imgratio < 1 && $imgratio < $thumbratio) {
			$cutw = $this->img_w;
			$cuth = $cutw / $thumbratio;
			$startx = 0;
			$starty = 0;
		}
		return array($cutw, $cuth, $startx, $starty);
	}

	function Thumb_GD() {
		switch($this->attachinfo['mime']) {
			case 'image/jpeg':
				$this->imagecreatefromfunc = function_exists('imagecreatefromjpeg') ? 'imagecreatefromjpeg' : '';
				$this->imagefunc = function_exists('imagejpeg') ? 'imagejpeg' : '';
				break;
			case 'image/gif':
				$this->imagecreatefromfunc = function_exists('imagecreatefromgif') ? 'imagecreatefromgif' : '';
				$this->imagefunc = function_exists('imagegif') ? 'imagegif' : '';
				break;
			case 'image/png':
				$this->imagecreatefromfunc = function_exists('imagecreatefrompng') ? 'imagecreatefrompng' : '';
				$this->imagefunc = function_exists('imagepng') ? 'imagepng' : '';
				break;
		}

		if($this->attachinfo['mime'] == 'image/gif') {
			$fp = fopen($this->srcfile, 'rb');
			$targetfilecontent = fread($fp, @filesize($this->srcfile));
			fclose($fp);
			$this->animatedgif = strpos($targetfilecontent, 'NETSCAPE2.0') === FALSE ? 0 : 1;
		}
		if(function_exists('imagecreatetruecolor') && function_exists('imagecopymerge') && function_exists('imagecopyresampled') && function_exists($this->imagecreatefromfunc) && function_exists($this->imagefunc)) {
			$imagecreatefromfunc = $this->imagecreatefromfunc;
			$imagefunc = $this->imagefunc;
			if(!$this->animatedgif) {
				$attach_photo = @$imagecreatefromfunc($this->srcfile);
				if($attach_photo) {
					if(!$this->smallimg) {
						list($cutw, $cuth, $startx, $starty) = $this->size();
						$dst_photo = imagecreatetruecolor($cutw, $cuth);
						imagecopymerge($dst_photo, $attach_photo, 0, 0, $startx, $starty, $cutw, $cuth, 100);
						$thumb_photo = imagecreatetruecolor($this->thumbwidth, $this->thumbheight);
						imagecopyresampled($thumb_photo, $dst_photo ,0, 0, 0, 0, $this->thumbwidth, $this->thumbheight, $cutw, $cuth);
					} else {
						$bgcolor = imagecolorat($attach_photo, 0, 0);
						$bgcolor = imagecolorsforindex($attach_photo, $bgcolor);
						$thumb_photo = imagecreatetruecolor($this->thumbwidth, $this->thumbheight);
						$bgcolor = imagecolorallocate($thumb_photo, $bgcolor['red'], $bgcolor['green'], $bgcolor['blue']);
						imagefill($thumb_photo, 0, 0, $bgcolor);
						imagecopymerge($thumb_photo, $attach_photo, ($this->thumbwidth - $this->img_w) / 2, ($this->thumbheight - $this->img_h) / 2, 0, 0, $this->img_w, $this->img_h, 100);
					}
					clearstatcache();

					$targetfile = $this->targetfile ? DISCUZ_ROOT.$this->targetfile : '';
					if($this->attachinfo['mime'] == 'image/jpeg') {
						$imagefunc($thumb_photo, $targetfile, 100);
					} elseif($targetfile) {
						$imagefunc($thumb_photo, $targetfile);
					} else {
						$imagefunc($thumb_photo);
					}
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	function Thumb_IM() {
		list($cutw, $cuth, $startx, $starty) = $this->size();
		global $imageimpath;
		$exec_str = $imageimpath.'/convert -crop '.$cutw.'x'.$cuth.'+'.$startx.'+'.$starty.'  '.$this->srcfile.' '.$this->targetfile;
		@exec($exec_str);
		$exec_str = $imageimpath.'/convert -quality 100 -geometry '.$this->thumbwidth.'x'.$this->thumbheight.' '.$this->targetfile.' '.$this->targetfile;
		@exec($exec_str);
		if(file_exists(DISCUZ_ROOT.$this->targetfile)) {
			dheader('location: '.$GLOBALS['boardurl'].$this->targetfile);
		}
	}
}

?>