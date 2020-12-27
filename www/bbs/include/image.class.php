<?

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: image.class.php 19557 2009-09-04 08:44:50Z monkey $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Image {

	var $attachinfo = '';
	var $targetfile = '';
	var $imagecreatefromfunc = '';
	var $imagefunc = '';
	var $attach = array();
	var $animatedgif = 0;
	var $error = 0;

	function Image($targetfile, $attach = array()) {
		global $imagelib, $watermarktext, $imageimpath;
		$this->targetfile = $targetfile;
		$this->attach = $attach;
		$this->attachinfo = @getimagesize($targetfile);
		if(!$imagelib || !$imageimpath) {
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
		} else {
			$this->imagecreatefromfunc = $this->imagefunc = TRUE;
		}

		$this->attach['size'] = empty($this->attach['size']) ? @filesize($targetfile) : $this->attach['size'];
		if($this->attachinfo['mime'] == 'image/gif') {
			if($this->imagecreatefromfunc && !@imagecreatefromgif($targetfile)) {
				$this->error = 1;
				$this->imagecreatefromfunc = $this->imagefunc = '';
				return FALSE;
			}
			$fp = fopen($targetfile, 'rb');
			$targetfilecontent = fread($fp, $this->attach['size']);
			fclose($fp);
			$this->animatedgif = strpos($targetfilecontent, 'NETSCAPE2.0') === FALSE ? 0 : 1;
		}
	}

	function Thumb($thumbwidth, $thumbheight, $preview = 0) {
		global $imagelib, $imageimpath, $thumbstatus, $watermarkstatus;
		$imagelib && $imageimpath ? $this->Thumb_IM($thumbwidth, $thumbheight, $preview) : $this->Thumb_GD($thumbwidth, $thumbheight, $preview);
		if($thumbstatus == 2 && $watermarkstatus) {
			$this->Image($this->targetfile, $this->attach);
		}
		$this->attach['size'] = filesize($this->targetfile);
	}

	function Watermark($preview = 0) {
		global $imagelib, $imageimpath, $watermarktype, $watermarktext, $watermarkminwidth, $watermarkminheight;
		if(($watermarkminwidth && $this->attachinfo[0] <= $watermarkminwidth && $watermarkminheight && $this->attachinfo[1] <= $watermarkminheight) || ($watermarktype == 2 && (!file_exists($watermarktext['fontpath']) || !is_file($watermarktext['fontpath'])))) {
			return;
		}
		$imagelib && $imageimpath ? $this->Watermark_IM($preview) : $this->Watermark_GD($preview);
	}

	function Thumb_GD($thumbwidth, $thumbheight, $preview = 0) {
		global $thumbstatus, $thumbquality;

		if($thumbstatus && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled') && function_exists('imagejpeg')) {
			$imagecreatefromfunc = $this->imagecreatefromfunc;
			$imagefunc = $thumbstatus == 1 ? 'imagejpeg' : $this->imagefunc;
			list($img_w, $img_h) = $this->attachinfo;

			if(!$this->animatedgif && ($img_w >= $thumbwidth || $img_h >= $thumbheight)) {

				if($thumbstatus != 3) {
					$attach_photo = $imagecreatefromfunc($this->targetfile);

					$x_ratio = $thumbwidth / $img_w;
					$y_ratio = $thumbheight / $img_h;

					if(($x_ratio * $img_h) < $thumbheight) {
						$thumb['height'] = ceil($x_ratio * $img_h);
						$thumb['width'] = $thumbwidth;
					} else {
						$thumb['width'] = ceil($y_ratio * $img_w);
						$thumb['height'] = $thumbheight;
					}

					$targetfile = !$preview ? ($thumbstatus == 1 ? $this->targetfile.'.thumb.jpg' : $this->targetfile) : DISCUZ_ROOT.'./forumdata/watermark_temp.jpg';
					$cx = $img_w;
					$cy = $img_h;
				} else {
					$attach_photo = $imagecreatefromfunc($this->targetfile);

					$imgratio = $img_w / $img_h;
					$thumbratio = $thumbwidth / $thumbheight;

					if($imgratio >= 1 && $imgratio >= $thumbratio || $imgratio < 1 && $imgratio > $thumbratio) {
						$cuty = $img_h;
						$cutx = $cuty * $thumbratio;
					} elseif($imgratio >= 1 && $imgratio <= $thumbratio || $imgratio < 1 && $imgratio < $thumbratio) {
						$cutx = $img_w;
						$cuty = $cutx / $thumbratio;
					}

					$dst_photo = imagecreatetruecolor($cutx, $cuty);
					@imageCopyMerge($dst_photo, $attach_photo, 0, 0, 0, 0, $cutx, $cuty, 100);

					$thumb['width'] = $thumbwidth;
					$thumb['height'] = $thumbheight;

					$targetfile = !$preview ? $this->targetfile.'.thumb.jpg' : DISCUZ_ROOT.'./forumdata/watermark_temp.jpg';
					$cx = $cutx;
					$cy = $cuty;
				}

				$thumb_photo = imagecreatetruecolor($thumb['width'], $thumb['height']);
				@imageCopyreSampled($thumb_photo, $attach_photo ,0, 0, 0, 0, $thumb['width'], $thumb['height'], $cx, $cy);
				clearstatcache();
				if($this->attachinfo['mime'] == 'image/jpeg') {
					$imagefunc($thumb_photo, $targetfile, $thumbquality);
				} else {
					$imagefunc($thumb_photo, $targetfile);
				}
				$this->attach['thumb'] = $thumbstatus == 1 || $thumbstatus == 3 ? 1 : 0;
			}
		}
	}

	function Watermark_GD($preview = 0) {
		global $watermarkstatus, $watermarktype, $watermarktrans, $watermarkquality, $watermarktext;
		$watermarkstatus = $GLOBALS['forum']['disablewatermark'] ? 0 : $watermarkstatus;

		if($watermarkstatus && function_exists('imagecopy') && function_exists('imagealphablending') && function_exists('imagecopymerge')) {
			$imagecreatefromfunc = $this->imagecreatefromfunc;
			$imagefunc = $this->imagefunc;
			list($img_w, $img_h) = $this->attachinfo;
			if($watermarktype < 2) {
				$watermark_file = $watermarktype == 1 ? './images/common/watermark.png' : './images/common/watermark.gif';
				$watermarkinfo	= @getimagesize($watermark_file);
				$watermark_logo	= $watermarktype == 1 ? @imageCreateFromPNG($watermark_file) : @imageCreateFromGIF($watermark_file);
				if(!$watermark_logo) {
					return;
				}
				list($logo_w, $logo_h) = $watermarkinfo;
			} else {
				$watermarktextcvt = pack("H*", $watermarktext['text']);
				$box = imagettfbbox($watermarktext['size'], $watermarktext['angle'], $watermarktext['fontpath'], $watermarktextcvt);
				$logo_h = max($box[1], $box[3]) - min($box[5], $box[7]);
				$logo_w = max($box[2], $box[4]) - min($box[0], $box[6]);
				$ax = min($box[0], $box[6]) * -1;
   				$ay = min($box[5], $box[7]) * -1;
			}
			$wmwidth = $img_w - $logo_w;
			$wmheight = $img_h - $logo_h;

			if(($watermarktype < 2 && is_readable($watermark_file) || $watermarktype == 2) && $wmwidth > 10 && $wmheight > 10 && !$this->animatedgif) {
				switch($watermarkstatus) {
					case 1:
						$x = +5;
						$y = +5;
						break;
					case 2:
						$x = ($img_w - $logo_w) / 2;
						$y = +5;
						break;
					case 3:
						$x = $img_w - $logo_w - 5;
						$y = +5;
						break;
					case 4:
						$x = +5;
						$y = ($img_h - $logo_h) / 2;
						break;
					case 5:
						$x = ($img_w - $logo_w) / 2;
						$y = ($img_h - $logo_h) / 2;
						break;
					case 6:
						$x = $img_w - $logo_w;
						$y = ($img_h - $logo_h) / 2;
						break;
					case 7:
						$x = +5;
						$y = $img_h - $logo_h - 5;
						break;
					case 8:
						$x = ($img_w - $logo_w) / 2;
						$y = $img_h - $logo_h - 5;
						break;
					case 9:
						$x = $img_w - $logo_w - 5;
						$y = $img_h - $logo_h - 5;
						break;
				}

				$dst_photo = imagecreatetruecolor($img_w, $img_h);
				$target_photo = @$imagecreatefromfunc($this->targetfile);
				@imageCopy($dst_photo, $target_photo, 0, 0, 0, 0, $img_w, $img_h);

				if($watermarktype == 1) {
					@imageCopy($dst_photo, $watermark_logo, $x, $y, 0, 0, $logo_w, $logo_h);
				} elseif($watermarktype == 2) {
					if(($watermarktext['shadowx'] || $watermarktext['shadowy']) && $watermarktext['shadowcolor']) {
						$shadowcolorrgb = explode(',', $watermarktext['shadowcolor']);
						$shadowcolor = imagecolorallocate($dst_photo, $shadowcolorrgb[0], $shadowcolorrgb[1], $shadowcolorrgb[2]);
						imagettftext($dst_photo, $watermarktext['size'], $watermarktext['angle'], $x + $ax + $watermarktext['shadowx'], $y + $ay + $watermarktext['shadowy'], $shadowcolor, $watermarktext['fontpath'], $watermarktextcvt);
					}
					$colorrgb = explode(',', $watermarktext['color']);
					$color = imagecolorallocate($dst_photo, $colorrgb[0], $colorrgb[1], $colorrgb[2]);
					imagettftext($dst_photo, $watermarktext['size'], $watermarktext['angle'], $x + $ax, $y + $ay, $color, $watermarktext['fontpath'], $watermarktextcvt);
				} else {
					imageAlphaBlending($watermark_logo, true);
					@imageCopyMerge($dst_photo, $watermark_logo, $x, $y, 0, 0, $logo_w, $logo_h, $watermarktrans);
				}

				$targetfile = !$preview ? $this->targetfile : DISCUZ_ROOT.'./forumdata/watermark_temp.jpg';
				clearstatcache();
				if($this->attachinfo['mime'] == 'image/jpeg') {
					$imagefunc($dst_photo, $targetfile, $watermarkquality);
				} else {
					$imagefunc($dst_photo, $targetfile);
				}

				$this->attach['size'] = filesize($targetfile);
			}
		}
	}

	function Thumb_IM($thumbwidth, $thumbheight, $preview = 0) {
		global $thumbstatus, $imageimpath, $thumbquality;
		if($thumbstatus) {
			list($img_w, $img_h) = $this->attachinfo;
			$targetfile = !$preview ? ($thumbstatus == 1 || $thumbstatus == 3 ? $this->targetfile.'.thumb.jpg' : $this->targetfile) : DISCUZ_ROOT.'./forumdata/watermark_temp.jpg';
			if(!$this->animatedgif && ($img_w >= $thumbwidth || $img_h >= $thumbheight)) {
				if($thumbstatus != 3) {
					$exec_str = $imageimpath.'/convert -quality '.intval($thumbquality).' -geometry '.$thumbwidth.'x'.$thumbheight.' '.$this->targetfile.' '.$targetfile;
					@exec($exec_str, $output, $return);
					if(empty($return) && empty($output)) {
						$this->attach['thumb'] = $thumbstatus == 1 ? 1 : 0;
					}
				} else {
					$imgratio = $img_w / $img_h;
					$thumbratio = $thumbwidth / $thumbheight;

					if($imgratio >= 1 && $imgratio >= $thumbratio || $imgratio < 1 && $imgratio > $thumbratio) {
						$cuty = $img_h;
						$cutx = $cuty * $thumbratio;
					} elseif($imgratio >= 1 && $imgratio <= $thumbratio || $imgratio < 1 && $imgratio < $thumbratio) {
						$cutx = $img_w;
						$cuty = $cutx / $thumbratio;
					}
					$exec_str = $imageimpath.'/convert -crop '.$cutx.'x'.$cuty.'+0+0  '.$this->targetfile.' '.$targetfile;
					@exec($exec_str, $output, $return);
					$exec_str = $imageimpath.'/convert -quality '.intval($thumbquality).' -geometry '.$thumbwidth.'x'.$thumbheight.' '.$targetfile.' '.$targetfile;
					@exec($exec_str, $output, $return);
					if(empty($return) && empty($output)) {
						$this->attach['thumb'] = $thumbstatus == 1 || $thumbstatus == 3 ? 1 : 0;
					}
				}
	           	}
		}
	}

	function Watermark_IM($preview = 0) {
		global $watermarkstatus, $watermarktype, $watermarktrans, $watermarkquality, $watermarktext, $imageimpath;
		$watermarkstatus = $GLOBALS['forum']['disablewatermark'] ? 0 : $watermarkstatus;

		switch($watermarkstatus) {
			case 1:
				$gravity = 'NorthWest';
				break;
			case 2:
				$gravity = 'North';
				break;
			case 3:
				$gravity = 'NorthEast';
				break;
			case 4:
				$gravity = 'West';
				break;
			case 5:
				$gravity = 'Center';
				break;
			case 6:
				$gravity = 'East';
				break;
			case 7:
				$gravity = 'SouthWest';
				break;
			case 8:
				$gravity = 'South';
				break;
			case 9:
				$gravity = 'SouthEast';
				break;
		}

		$targetfile = !$preview ? $this->targetfile : DISCUZ_ROOT.'./forumdata/watermark_temp.jpg';
		if($watermarktype < 2) {
			$watermark_file = $watermarktype == 1 ? DISCUZ_ROOT.'./images/common/watermark.png' : DISCUZ_ROOT.'./images/common/watermark.gif';
			$exec_str = $imageimpath.'/composite'.
				($watermarktype != 1 && $watermarktrans != '100' ? ' -watermark '.$watermarktrans.'%' : '').
				' -quality '.$watermarkquality.
				' -gravity '.$gravity.
				' '.$watermark_file.' '.$this->targetfile.' '.$targetfile;
		} else {
			$watermarktextcvt = str_replace(array("\n", "\r", "'"), array('', '', '\''), pack("H*", $watermarktext['text']));
			$watermarktext['angle'] = -$watermarktext['angle'];
			$translate = $watermarktext['translatex'] || $watermarktext['translatey'] ? ' translate '.$watermarktext['translatex'].','.$watermarktext['translatey'] : '';
			$skewX = $watermarktext['skewx'] ? ' skewX '.$watermarktext['skewx'] : '';
			$skewY = $watermarktext['skewy'] ? ' skewY '.$watermarktext['skewy'] : '';
			$exec_str = $imageimpath.'/convert'.
				' -quality '.$watermarkquality.
				' -font "'.$watermarktext['fontpath'].'"'.
				' -pointsize '.$watermarktext['size'].
				(($watermarktext['shadowx'] || $watermarktext['shadowy']) && $watermarktext['shadowcolor'] ?
					' -fill "rgb('.$watermarktext['shadowcolor'].')"'.
					' -draw "'.
						' gravity '.$gravity.$translate.$skewX.$skewY.
						' rotate '.$watermarktext['angle'].
						' text '.$watermarktext['shadowx'].','.$watermarktext['shadowy'].' \''.$watermarktextcvt.'\'"' : '').
				' -fill "rgb('.$watermarktext['color'].')"'.
				' -draw "'.
					' gravity '.$gravity.$translate.$skewX.$skewY.
					' rotate '.$watermarktext['angle'].
					' text 0,0 \''.$watermarktextcvt.'\'"'.
				' '.$this->targetfile.' '.$targetfile;
		}
		@exec($exec_str, $output, $return);
		if(empty($return) && empty($output)) {
			$this->attach['size'] = filesize($this->targetfile);
		}
	}

}