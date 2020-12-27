<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: ftp.func.php 16688 2008-11-14 06:41:07Z cnteacher $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function dftp_connect($ftphost, $ftpuser, $ftppass, $ftppath, $ftpport = 21, $ftpssl = 0, $silent = 0) {
	global $ftp;
	@set_time_limit(0);

	$ftphost = wipespecial($ftphost);
	$ftpport = intval($ftpport);
	$ftpssl = intval($ftpssl);
	$ftp['timeout'] = intval($ftp['timeout']);

	$func = $ftpssl && function_exists('ftp_ssl_connect') ? 'ftp_ssl_connect' : 'ftp_connect';
	if($func == 'ftp_connect' && !function_exists('ftp_connect')) {
		if($silent) {
			return -4;
		} else {
			errorlog('FTP', "FTP not supported.", 0);
		}
	}
	if($ftp_conn_id = @$func($ftphost, $ftpport, 20)) {
		if($ftp['timeout'] && function_exists('ftp_set_option')) {
			@ftp_set_option($ftp_conn_id, FTP_TIMEOUT_SEC, $ftp['timeout']);
		}
		if(dftp_login($ftp_conn_id, $ftpuser, $ftppass)) {
			if($ftp['pasv']) {
				dftp_pasv($ftp_conn_id, TRUE);
			}
			if(dftp_chdir($ftp_conn_id, $ftppath)) {
				return $ftp_conn_id;
			} else {
				if($silent) {
					return -3;
				} else {
					errorlog('FTP', "Chdir '$ftppath' error.", 0);
				}
			}
		} else {
			if($silent) {
				return -2;
			} else {
				errorlog('FTP', '530 Not logged in.', 0);
			}
		}
	} else {
		if($silent) {
			return -1;
		} else {
			errorlog('FTP', "Couldn't connect to $ftphost:$ftpport.", 0);
		}
	}
	dftp_close($ftp_conn_id);
	return -1;
}

function dftp_mkdir($ftp_stream, $directory) {
	$directory = wipespecial($directory);
	return @ftp_mkdir($ftp_stream, $directory);
}

function dftp_rmdir($ftp_stream, $directory) {
	$directory = wipespecial($directory);
	return @ftp_rmdir($ftp_stream, $directory);
}

function dftp_put($ftp_stream, $remote_file, $local_file, $mode, $startpos = 0 ) {
	$remote_file = wipespecial($remote_file);
	$local_file = wipespecial($local_file);
	$mode = intval($mode);
	$startpos = intval($startpos);
	return @ftp_put($ftp_stream, $remote_file, $local_file, $mode, $startpos);
}

function dftp_size($ftp_stream, $remote_file) {
	$remote_file = wipespecial($remote_file);
	return @ftp_size($ftp_stream, $remote_file);
}

function dftp_close($ftp_stream) {
	return @ftp_close($ftp_stream);
}

function dftp_delete($ftp_stream, $path) {
	$path = wipespecial($path);
	return @ftp_delete($ftp_stream, $path);
}

function dftp_get($ftp_stream, $local_file, $remote_file, $mode, $resumepos = 0) {
	$remote_file = wipespecial($remote_file);
	$local_file = wipespecial($local_file);
	$mode = intval($mode);
	$resumepos = intval($resumepos);
	return @ftp_get($ftp_stream, $local_file, $remote_file, $mode, $resumepos);
}

function dftp_login($ftp_stream, $username, $password) {
	$username = wipespecial($username);
	$password = str_replace(array("\n", "\r"), array('', ''), $password);
	return @ftp_login($ftp_stream, $username, $password);
}

function dftp_pasv($ftp_stream, $pasv) {
	$pasv = intval($pasv);
	return @ftp_pasv($ftp_stream, $pasv);
}

function dftp_chdir($ftp_stream, $directory) {
	$directory = wipespecial($directory);
	return @ftp_chdir($ftp_stream, $directory);
}

function dftp_site($ftp_stream, $cmd) {
	$cmd = wipespecial($cmd);
	return @ftp_site($ftp_stream, $cmd);
}

function dftp_chmod($ftp_stream, $mode, $filename) {
	$mode = intval($mode);
	$filename = wipespecial($filename);
	if(function_exists('ftp_chmod')) {
		return @ftp_chmod($ftp_stream, $mode, $filename);
	} else {
		return dftp_site($ftp_stream, 'CHMOD '.$mode.' '.$filename);
	}
}

?>