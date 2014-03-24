<?php
/*
 * MODx Manager API Class
 * Written by Raymond Irving 2005
 *
 */

global $_PAGE; // page view state object. Usage $_PAGE['vs']['propertyname'] = $value;

// Content manager wrapper class
class ManagerAPI {
	
	var $action; // action directive

	function ManagerAPI(){
		global $action;
		$this->action = $action; // set action directive
	}
	
	function initPageViewState($id=0)
	{
		global $_PAGE;
		$vsid = isset($_SESSION['mgrPageViewSID']) ? $_SESSION['mgrPageViewSID'] : '';
		if($vsid!=$this->action)
		{
			$_SESSION['mgrPageViewSDATA'] = array(); // new view state
			$_SESSION['mgrPageViewSID']   = ($id > 0) ? $id : $this->action; // set id
		}
		$_PAGE['vs'] = &$_SESSION['mgrPageViewSDATA']; // restore viewstate
	}

	// save page view state - not really necessary,
	function savePageViewState($id=0)
	{
		$_SESSION['mgrPageViewSDATA'] = $_PAGE['vs'];
		$_SESSION['mgrPageViewSID']   = ($id > 0) ? $id : $this->action;
	}
	
	// check for saved form
	function hasFormValues() {
		if(isset($_SESSION['mgrFormValueId']) && isset($_SESSION['mgrFormValues']) && !empty($_SESSION['mgrFormValues']))
		{
			if($this->action==$_SESSION['mgrFormValueId'] && is_array($_SESSION['mgrFormValues']))
			{
				return true;
			}
			else
			{
				$this->clearSavedFormValues();
			}
		}
		return false;
	}
	// saved form post from $_POST
	function saveFormValues($id=0)
	{
		$_SESSION['mgrFormValues']  = $_POST;
		$_SESSION['mgrFormValueId'] = ($id > 0) ? $id : $this->action;
	}
	// load saved form values into $_POST
	function loadFormValues()
	{
		if($this->hasFormValues())
		{
			$p = $_SESSION['mgrFormValues'];
			foreach($p as $k=>$v)
			{
				$_POST[$k]=$v;
			}
			$this->clearSavedFormValues();
			return true;
		}
		else return false;
	}
	// clear form post
	function clearSavedFormValues()
	{
		unset($_SESSION['mgrFormValues']);
		unset($_SESSION['mgrFormValueId']);
	}
	
	function get_alias_from_title($id,$pagetitle)
	{
	    global $modx;
	    if($id==='') $id = 0;
	    
		$alias = strtolower($modx->stripAlias(trim($pagetitle)));
		
		if(!$modx->config['allow_duplicate_alias'])
		{
		    $rs = $modx->db->select('id','[+prefix+]site_content',"id<>'{$id}' AND alias='{$alias}'");
			if(0 < $modx->db->getRecordCount($rs))
			{
				$c = 2;
				$_ = $alias;
				while(0 < $modx->db->getRecordCount($modx->db->select('id','[+prefix+]site_content',"id!='{$id}' AND alias='{$_}'")))
				{
					$_  = $alias;
					$_ .= "_{$c}";
					$c++;
				}
				$alias = $_;
			}
		}
		else $alias = '';
		
		return $alias;
	}
	
	function get_alias_num_in_folder($id,$parent)
	{
		global $modx;
	    if($id==='') $id = 0;
	    
		$tbl_site_content = $modx->getFullTableName('site_content');
		
		$rs = $modx->db->select('MAX(cast(alias as SIGNED))',$tbl_site_content,"id<>'{$id}' AND parent={$parent} AND alias REGEXP '^[0-9]+$'");
		$_ = $modx->db->getValue($rs);
		if(empty($_)) $_ = 0;
		$_++;
		while(!isset($noduplex))
		{
			$rs = $modx->db->select('id',$tbl_site_content,"id='{$_}' AND parent={$parent} AND alias=''");
			if($modx->db->getRecordCount($rs)==0) $noduplex = true;
			else $_++;
		}
		return $_;
	}
	
	function modx_move_uploaded_file($tmp_path,$target_path)
	{
		global $modx,$image_limit_width;
		
		$target_path = str_replace('\\','/', $target_path);
		$new_file_permissions = octdec($modx->config['new_file_permissions']);
		
		if(strpos($target_path, $modx->config['filemanager_path'])!==0)
		{
			$msg = "Can't upload to '{$target_path}'.";
			$modx->logEvent(1,3,$msg,'move_uploaded_file');
		}
		
		if(isset($modx->config['image_limit_width']))
			$image_limit_width = $modx->config['image_limit_width'];
		else $image_limit_width = '';
		
		$img = getimagesize($tmp_path);
		switch($img[2])
		{
			case IMAGETYPE_JPEG: $ext = '.jpg'; break;
			case IMAGETYPE_PNG:  $ext = '.png'; break;
			case IMAGETYPE_GIF:  $ext = '.gif'; break;
			case IMAGETYPE_BMP:  $ext = '.bmp'; break;
		}
		if(isset($ext)) $target_path = substr($target_path,0,strrpos($target_path,'.')) . $ext;
		
		if(!isset($ext) || $image_limit_width==='' || $img[0] <= $image_limit_width)
		{
			$rs = move_uploaded_file($tmp_path, $target_path);
			if(!$rs)
			{
				$target_is_writable = (is_writable(dirname($target_path))) ? 'true' : 'false';
				
				$msg  = '$tmp_path = ' . "{$tmp_path}\n";
				$msg .= '$target_path = ' . "{$target_path}\n";
				$msg .= '$image_limit_width = ' . "{$image_limit_width}\n";
				$msg .= '$target_is_writable = ' . "{$target_is_writable}\n";
				if(isset($ext))
				{
					$msg .= 'getimagesize = ' . print_r($img,true);
				}
				
				$msg = str_replace("\n","<br />\n",$msg);
				$modx->logEvent(1,3,$msg,'move_uploaded_file');
			}
			else @chmod($target_path, $new_file_permissions);
			return $rs;
		}
		
		$new_width = $image_limit_width;
		$new_height = (int)( ($img[1]/$img[0]) * $new_width);
		
		switch($img[2])
		{
			case IMAGETYPE_JPEG:
				$tmp_image = imagecreatefromjpeg($tmp_path);
				$new_image = imagecreatetruecolor($new_width, $new_height);
				$rs = imagecopyresampled($new_image,$tmp_image,0,0,0,0,$new_width,$new_height,$img[0],$img[1]);
				if($rs) $rs = imagejpeg($new_image, $target_path, 85);
				break;
			case IMAGETYPE_PNG:
				$tmp_image = imagecreatefrompng($tmp_path);
				$new_image = imagecreatetruecolor($new_width, $new_height);
//				imagealphablending($new_image,false);
//				imagesavealpha($new_image,true);
				$rs = imagecopyresampled($new_image,$tmp_image,0,0,0,0,$new_width,$new_height,$img[0],$img[1]);
				if($rs) $rs = imagepng($new_image, $target_path);
				break;
			case IMAGETYPE_GIF: 
			case IMAGETYPE_BMP:
				if($img[2]==IMAGETYPE_GIF)
					$tmp_image = imagecreatefromgif($tmp_path);
				if($img[2]==IMAGETYPE_BMP)
					$tmp_image = imagecreatefromwbmp($tmp_path);
				$new_image = imagecreatetruecolor($new_width, $new_height);
				$rs = imagecopyresampled($new_image,$tmp_image,0,0,0,0,$new_width,$new_height,$img[0],$img[1]);
				if($rs) $rs = imagepng($new_image, $target_path);
				break;
			default:
		}
		if($new_image)
		{
			imagedestroy($tmp_image);
			imagedestroy($new_image);
		}
		if($rs) @chmod($target_path, $new_file_permissions);
		return $rs;
	}
	
	function validate_referer($flag)
	{
		global $modx;
		
		if($flag!=1) return;
        if(isset($_GET['frame']) && $_GET['frame']==='main')
        {
        	switch($modx->manager->action)
        	{
            	case '3' :
            	case '120' :
            	case '4' :
            	case '27' :
            	case '85' :
            	case '72' :
            	case '8' :
            	case '87' :
            	case '88' :
            	case '11' :
            	case '12' :
            	case '119' :
            	case '28' :
            	case '35' :
            	case '38' :
            	case '16' :
            	case '19' :
            	case '22' :
            	case '23' :
            	case '77' :
            	case '78' :
            	case '18' :
            	case '106' :
            	case '107' :
            	case '108' :
            	case '100' :
            	case '101' :
            	case '102' :
            	case '200' :
            	case '31' :
            	case '40' :
            	case '91' :
            	case '41' :
            	case '92' :
            	case '17' :
            	case '53' :
            	case '13' :
            	case '10' :
            	case '70' :
            	case '71' :
            	case '59' :
            	case '75' :
            	case '99' :
            	case '86' :
            	case '76' :
            	case '83' :
            	case '93' :
            	case '95' :
            	case '9' :
            	case '301' :
            	case '302' :
            	case '115' :
                	unset($_GET['frame']);
                	$_SESSION['mainframe'] = $_GET;
                	header('Location:' . MODX_MANAGER_URL);
                	exit;
                	break;
            	default :
        	}
        }

		$referer = isset($_SERVER['HTTP_REFERER']) ? strip_tags($_SERVER['HTTP_REFERER']) : '';
		
		if(empty($referer))
		{
			echo "A possible CSRF attempt was detected. No referer was provided by the server.";
			exit();
		}
		elseif(stripos($referer,MODX_SITE_URL)===false)
		{
			echo "A possible CSRF attempt was detected from referer: {$referer}.";
			exit();
		}
	}
	
	function checkToken()
	{
		if(isset($_POST['token']) && !empty($_POST['token']))    $token = $_POST['token'];
		elseif(isset($_GET['token']) && !empty($_GET['token']))  $token = $_GET['token'];
		else                                                     $token = false;
		
		if(isset($_SESSION['token']) && !empty($_SESSION['token']) && $_SESSION['token']===$token)
		{
			$rs =true;
		}
		else $rs = false;
		$_SESSION['token'] = '';
		return $rs;
	}
	
	function makeToken()
	{
		$newToken = uniqid('');
		$_SESSION['token'] = $newToken;
		return $newToken;
	}
	
	function remove_locks($action='all',$limit_time=120)
	{
		global $modx;
		
		$limit_time = time() - $limit_time;
		if($action === 'all')
		{
			$action = '';
		}
		else
		{
			$action = intval($action);
			$action = "action={$action} and";
		}
		$modx->db->delete('[+prefix+]active_users',"{$action} lasthit < {$limit_time}");
	}
	
	function genHash($password, $seed='1')
	{ // $seed is user_id basically
		global $modx;
		
		if(isset($modx->config['pwd_hash_algo']) && !empty($modx->config['pwd_hash_algo']))
			$algorithm = $modx->config['pwd_hash_algo'];
		else $algorithm = 'UNCRYPT';
		
		$salt = md5($password . $seed);
		
		switch($algorithm)
		{
			case 'BLOWFISH_Y':
				$salt = '$2y$07$' . substr($salt,0,22);
				break;
			case 'BLOWFISH_A':
				$salt = '$2a$07$' . substr($salt,0,22);
				break;
			case 'SHA512':
				$salt = '$6$' . substr($salt,0,16);
				break;
			case 'SHA256':
				$salt = '$5$' . substr($salt,0,16);
				break;
			case 'MD5':
				$salt = '$1$' . substr($salt,0,8);
				break;
			case 'UNCRYPT':
				break;
		}
		
		if($algorithm!=='UNCRYPT')
		{
			$password = sha1($password) . crypt($password,$salt);
		}
		else $password = sha1($salt.$password);
		
		$result = strtolower($algorithm) . '>' . md5($salt.$password) . substr(md5($salt),0,8);
		
		return $result;
	}
	
	function getUserHashAlgorithm($uid)
	{
		global $modx;
		
		$user = $modx->db->getObject('manager_users',"id='{$uid}'");
		
		if(strpos($user->password,'>')===false) $algo = 'NOSALT';
		else
		{
			$algo = substr($user->password,0,strpos($user->password,'>'));
		}
		return strtoupper($algo);
	}
	
	function checkHashAlgorithm($algorithm='')
	{
		if(empty($algorithm)) return;
		
		switch($algorithm)
		{
			case 'BLOWFISH_Y':
				if(defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1)
				{
					if(version_compare('5.3.7', PHP_VERSION) <= 0) $result = true;
				}
				break;
			case 'BLOWFISH_A':
				if(defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH == 1) $result = true;
				break;
			case 'SHA512':
				if(defined('CRYPT_SHA512') && CRYPT_SHA512 == 1) $result = true;
				break;
			case 'SHA256':
				if(defined('CRYPT_SHA256') && CRYPT_SHA256 == 1) $result = true;
				break;
			case 'MD5':
				if(defined('CRYPT_MD5') && CRYPT_MD5 == 1 && PHP_VERSION != '5.3.7')
					$result = true;
				break;
			case 'UNCRYPT':
				$result = true;
				break;
		}
		
		if(!isset($result)) $result = false;
		
		return $result;
	}

	function getSystemChecksum($check_files) {
		global $modx;
		
		$check_files = trim($check_files);
		$check_files = explode("\n", $check_files);
		foreach($check_files as $file) {
			$file = trim($file);
			$file = MODX_BASE_PATH . $file;
			if(!is_file($file)) continue;
			$_[$file]= md5_file($file);
		}
		return serialize($_);
	}
	
	function setSystemChecksum($checksum) {
		global $modx;
		$tbl_system_settings = $modx->getFullTableName('system_settings');
		$sql = "REPLACE INTO {$tbl_system_settings} (setting_name, setting_value) VALUES ('sys_files_checksum','{$checksum}')";
        $modx->db->query($sql);
	}
	
	function checkSystemChecksum() {
		global $modx;

		if(!isset($modx->config['check_files_onlogin']) || empty($modx->config['check_files_onlogin'])) return '0';
		
		$current = $this->getSystemChecksum($modx->config['check_files_onlogin']);
		if(empty($current)) return;
		
		if(!isset($modx->config['sys_files_checksum']) || empty($modx->config['sys_files_checksum']))
		{
			$this->setSystemChecksum($current);
			return;
		}
		if($current===$modx->config['sys_files_checksum']) $result = '0';
		else                                               $result = 'modified';

		return $result;
	}
	
	function setView($action)
	{
		$actions = explode(',', '10,100,101,102,106,107,108,11,112,113,114,115,117,119,12,120,13,16,17,18,19,2,200,22,23,26,27,28,29,3,300,301,31,35,38,4,40,51,53,59,70,71,72,75,76,77,78,81,83,84,85,86,87,88,9,91,93,95,99,998,999');
		if(in_array($action,$actions))
		{
			if(isset($_SESSION['current_request_uri'])) $_SESSION['prev_request_uri'] = $_SESSION['current_request_uri'];
			$_SESSION['current_request_uri'] = $_SERVER['REQUEST_URI'];
		}
	}
    
    function ab($ph)
    {
    	global $modx;
    	
    	$tpl = '<li><a href="#" onclick="[+onclick+]"><img src="[+icon+]" alt="[+alt+]" /> [+label+]</a></li>';
    	$ph['alt']     = isset($ph['alt']) ? $ph['alt'] : $ph['label'];
    	return $modx->parseText($tpl,$ph);
    }
}
