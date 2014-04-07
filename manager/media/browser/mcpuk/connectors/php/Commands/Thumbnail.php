<?php
/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * File Name: Thumbnail.php
 * 	Implements the Thumbnail command, to return
 * 	a thumbnail to the browser for the sent file,
 * 	if the file is an image an attempt is made to
 * 	generate a thumbnail, otherwise an appropriate
 * 	icon is returned.
 * 	Output is image data
 * 
 * File Authors:
 * 		Grant French (grant@mcpuk.net)
 */

if(!defined('MODX_BASE_PATH') || strpos(str_replace('\\','/',__FILE__), MODX_BASE_PATH)!==0) exit;
include_once(MODX_BASE_PATH.'manager/media/browser/mcpuk/connectors/php/Commands/helpers/iconlookup.php');

class Thumbnail {
	var $fckphp_config;
	var $type;
	var $cwd;
	var $actual_cwd;
	var $filename;
	
	function Thumbnail($fckphp_config,$type,$cwd) {
		$this->fckphp_config=$fckphp_config;
		$this->type=$type;
		$this->actual_cwd=str_replace('//','/',("/$type/".$cwd));
		$this->real_cwd=str_replace('//','/',($this->fckphp_config['basedir'].'/'.$this->actual_cwd));
		$this->real_cwd = rtrim($this->real_cwd,'/');
		$this->filename=str_replace(array('..','/'),'',$_GET['FileName']);
	}
	
	function run()
	{
		//$mimeIcon=getMimeIcon($mime);
		$fullfile=$this->real_cwd.'/'.$this->filename;
		$thumbfile=$this->real_cwd.'/.thumb/'.$this->filename;
		$icon=false;
		
		if (is_file($thumbfile)) {
			$icon=$thumbfile;
		} else {
			$thumbdir = dirname($thumbfile);
			
			$mime = $this->getMimeType($fullfile);
			$ext=strtolower($this->getExtension($this->filename));
			
			if ($this->isImage($mime,$ext))
			{
				if(!is_dir($thumbdir)) $rs = mkdir($thumbdir,$this->fckphp_config['modx']['folder_permissions'],true);
				if($rs) chmod($thumbdir,$this->fckphp_config['modx']['folder_permissions']);
				//Try and find a thumbnail, else try to generate one
				//	else send generic picture icon.
				
				if($this->isJPEG($mime,$ext))    $result=$this->resizeFromJPEG($fullfile);
				elseif($this->isGIF($mime,$ext)) $result=$this->resizeFromGIF($fullfile);
				elseif($this->isPNG($mime,$ext)) $result=$this->resizeFromPNG($fullfile);
				
				if ($result!==false)
				{
					if (function_exists("imagejpeg")) {
						imagejpeg($result,$thumbfile,80);
						@chmod($thumbfile,$this->fckphp_config['modx']['file_permissions']);
						$icon=$thumbfile;
					} elseif (function_exists("imagepng")) {
						imagepng($result,$thumbfile);
						@chmod($thumbfile,$this->fckphp_config['modx']['file_permissions']);
						$icon=$thumbfile;
					} elseif (function_exists("imagegif")) {
						imagegif($result,$thumbfile);
						@chmod($thumbfile,$this->fckphp_config['modx']['file_permissions']);
						$icon=$thumbfile;
					} else {
						$icon=iconLookup($mime,$ext);
					}
					
				} else {
					$icon=iconLookup($mime,$ext);
				}
			} else {
				$icon=iconLookup($mime,$ext);
			}
		}
		
		$iconMime = $this->getMimeType($icon);
		if ($iconMime==false) $iconMime='image/jpeg';
		
		header("Content-type: $iconMime",true);
		readfile($icon);
		
	}
	
	function isImage($mime,$ext) {
		if (
			($mime=="image/gif")||
			($mime=="image/jpeg")||
			($mime=="image/jpg")||
			($mime=="image/pjpeg")||
			($mime=="image/png")||
			($ext=="jpg")||
			($ext=="jpeg")||
			($ext=="png")||
			($ext=="gif") ) {
		
			return true;
		} else {
			return false;
		}
	}
	
	function isJPEG($mime,$ext) {
		if (($mime=="image/jpeg")||($mime=="image/jpg")||($mime=="image/pjpeg")||($ext=="jpg")||($ext=="jpeg")) {
			return true;
		} else {
			return false;
		}
	}

	function isGIF($mime,$ext) {
		if (($mime=="image/gif")||($ext=="gif")) {
			return true;
		} else {
			return false;
		}
	}
	
	function isPNG($mime,$ext) {
		if (($mime=="image/png")||($ext=="png")) {
			return true;
		} else {
			return false;
		}
	}
	
	function getExtension($filename) {
		//Get Extension
		$ext='';
		$lastpos=strrpos($this->filename,'.');
		if ($lastpos!==false) $ext=substr($this->filename,($lastpos+1));
		return strtolower($ext);
	}
	
	function getMimeType($file_path) {
		$fp = fopen($file_path, 'rb');
		$head= fread($fp, 2); fclose($fp);
		$head = mb_convert_encoding($head, '8BIT');
		if($head==='BM')                    $mime_type = 'image/bmp';
		elseif($head==='GI')                $mime_type = 'image/gif';
		elseif($head===chr(0xFF).chr(0xd8)) $mime_type = 'image/jpeg';
		elseif($head===chr(0x89).'P')       $mime_type = 'image/png';
		else $mime_type = false;
		return $mime_type;
	}
	
	function resizeFromJPEG($file) {
		$img = imagecreatefromjpeg($file);
		return (($img)?$this->resizeImage($img):false);
	}
	
	function resizeFromGIF($file) {
		$img=imagecreatefromgif($file);
		return (($img)?$this->resizeImage($img):false);
	}
	
	function resizeFromPNG($file) {
		$img=imagecreatefrompng($file);
		return (($img)?$this->resizeImage($img):false);
	}
	
	function resizeImage($img) {
		//Get size for thumbnail
		$width=imagesx($img); $height=imagesy($img);
		if ($width>$height) { $n_height=$height*(64/$width); $n_width=64; } else { $n_width=$width*(64/$height); $n_height=64; }
		
		$x=0;$y=0;
		if ($n_width<64) $x=round((64-$n_width)/2);
		if ($n_height<64) $y=round((64-$n_height)/2);
		
		$thumb=imagecreatetruecolor(64,64);
		
		#Background colour fix by:
		#Ben Lancaster (benlanc@ster.me.uk)
		$bgcolor = imagecolorallocate($thumb,255,255,255);
		imagefill($thumb, 0, 0, $bgcolor);
		
		if (function_exists("imagecopyresampled")) {
			if (!($result=@imagecopyresampled($thumb,$img,$x,$y,0,0,$n_width,$n_height,$width,$height))) {
				$result=imagecopyresized($thumb,$img,$x,$y,0,0,$n_width,$n_height,$width,$height);
			}
		} else {
			$result=imagecopyresized($thumb,$img,$x,$y,0,0,$n_width,$n_height,$width,$height);
		}

		return ($result)?$thumb:false;
	}
}
