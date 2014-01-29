<?php
	// DISPLAY FORM ELEMENTS
	function renderFormElement($field_type, $field_id, $default_text, $field_elements, $field_value, $field_style='', $row = array()) {
		global $modx;
		global $base_url;
		global $rb_base_url;
		global $manager_theme;
		global $_lang;
		global $content;
		
		$field_html ='';
		$field_value = ($field_value!="" ? $field_value : $default_text);

		switch (strtolower($field_type)) {

			case "text": // handler for regular text boxes
			case "rawtext"; // non-htmlentity converted text boxes
			case "email": // handles email input fields
			case "number": // handles the input of numbers
				if($field_type=='text') $field_type = '';
				elseif($field_type=='number') $field_type .= ' imeoff';
				$field_html .=  '<input type="text" class="text ' . $field_type . '" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' tvtype="'.$field_type.'" />';
				break;
			case "textareamini": // handler for textarea mini boxes
				$field_type .= " phptextarea";
				$field_html .=  '<textarea class="' . $field_type . '" id="tv'.$field_id.'" name="tv'.$field_id.'" cols="40" rows="5">' . htmlspecialchars($field_value) .'</textarea>';
				break;
			case "textarea": // handler for textarea boxes
			case "rawtextarea": // non-htmlentity convertex textarea boxes
			case "htmlarea": // handler for textarea boxes (deprecated)
			case "richtext": // handler for textarea boxes
				$field_type .= " phptextarea";
				$field_html .=  '<textarea class="' . $field_type . '" id="tv'.$field_id.'" name="tv'.$field_id.'" cols="40" rows="15">' . htmlspecialchars($field_value) .'</textarea>';
				break;
			case "date":
				$field_id = str_replace(array('-', '.'),'_', urldecode($field_id));	
                if($field_value=='') $field_value=0;
				$field_html .=  '<input id="tv'.$field_id.'" name="tv'.$field_id.'" class="DatePicker" type="text" value="' . ($field_value==0 || !isset($field_value) ? "" : $field_value) . '" onblur="documentDirty=true;" />';
				$field_html .=  ' <a onclick="document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].value=\'\';document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].onblur(); return true;" style="cursor:pointer; cursor:hand"><img src="media/style/' . $manager_theme . '/images/icons/cal_nodate.gif" border="0" alt="No date"></a>';

				$field_html .=  '<script type="text/javascript">';
				$field_html .=  '	window.addEvent(\'domready\', function() {';
				$field_html .=  '   	new DatePicker($(\'tv'.$field_id.'\'), {\'yearOffset\' : '.$modx->config['datepicker_offset']. ", 'format' : " . "'" . $modx->config['datetime_format']  . ' hh:mm:00\'' . '});';
				$field_html .=  '});';
				$field_html .=  '</script>';

				break;
			case "dateonly":
				$field_id = str_replace(array('-', '.'),'_', urldecode($field_id));	
                if($field_value=='') $field_value=0;
				$field_html .=  '<input id="tv'.$field_id.'" name="tv'.$field_id.'" class="DatePicker" type="text" value="' . ($field_value==0 || !isset($field_value) ? "" : $field_value) . '" onblur="documentDirty=true;" />';
				$field_html .=  ' <a onclick="document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].value=\'\';document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].onblur(); return true;" style="cursor:pointer; cursor:hand"><img src="media/style/'.$manager_theme.'/images/icons/cal_nodate.gif" border="0" alt="No date"></a>';

				$field_html .=  '<script type="text/javascript">';
				$field_html .=  '	window.addEvent(\'domready\', function() {';
				$field_html .=  '   	new DatePicker($(\'tv'.$field_id.'\'), {\'yearOffset\' : '.$modx->config['datepicker_offset']. ", 'format' : " . "'" . $modx->config['datetime_format'] . "'" . '});';
				$field_html .=  '});';
				$field_html .=  '</script>';

				break;
			case "dropdown": // handler for select boxes
				$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'" size="1">';
				$rs = ProcessTVCommand($field_elements, $field_id,'','tvform');
				$index_list = ParseIntputOptions($rs);
				while (list($label, $item) = each ($index_list))
				{
					list($label,$value) = splitOption($item);
					$selected = ($value==$field_value) ?' selected="selected"':'';
					$field_html .=  '<option value="'.htmlspecialchars($value).'"'.$selected.'>'.htmlspecialchars($label).'</option>';
				}
				$field_html .=  "</select>";
				break;
			case "listbox": // handler for select boxes
				$rs = ProcessTVCommand($field_elements, $field_id,'','tvform');
				$index_list = ParseIntputOptions($rs);
				$count = (count($index_list)<8) ? count($index_list) : 8;
				$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'" size="' . $count . '">';	
				while (list($label, $item) = each ($index_list))
				{
					list($label,$value) = splitOption($item);
					$selected = (isSelected($label,$value,$item,$field_value)) ?' selected="selected"':'';
					$field_html .=  '<option value="'.htmlspecialchars($value).'"' . $selected . '>'.htmlspecialchars($label).'</option>';
				}
				$field_html .=  "</select>";
				break;
			case "listbox-multiple": // handler for select boxes where you can choose multiple items
				$rs = ProcessTVCommand($field_elements, $field_id,'','tvform');
				$index_list = ParseIntputOptions($rs);
				$count = (count($index_list)<8) ? count($index_list) : 8;
				$field_value = explode("||",$field_value);
				$field_html .=  '<select id="tv'.$field_id.'[]" name="tv'.$field_id.'[]" multiple="multiple" size="' . $count . '">';
				while (list($label, $item) = each ($index_list))
				{
					list($label,$value) = splitOption($item);
					$selected = (isSelected($label,$value,$item,$field_value)) ?' selected="selected"':'';
					$field_html .=  '<option value="'.htmlspecialchars($value).'"' . $selected .'>'.htmlspecialchars($label).'</option>';
				}
				$field_html .=  "</select>";
				break;
			case "url": // handles url input fields
				$urls= array(''=>'--', 'http://'=>'http://', 'https://'=>'https://', 'ftp://'=>'ftp://', 'mailto:'=>'mailto:');
				$field_html ='<table border="0" cellspacing="0" cellpadding="0"><tr><td><select id="tv'.$field_id.'_prefix" name="tv'.$field_id.'_prefix">';
				foreach($urls as $k => $v)
				{
					if(strpos($field_value,$v)===false) $field_html.='<option value="'.$v.'">'.$k.'</option>';
					else
					{
						$field_value = str_replace($v,'',$field_value);
						$field_html.='<option value="'.$v.'" selected="selected">'.$k.'</option>';
					}
				}
				$field_html .='</select></td><td>';
				$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" width="100" '.$field_style.' /></td></tr></table>';
				break;
			case "checkbox": // handles check boxes
				if(!is_array($field_value)) $field_value = explode('||',$field_value);
				$rs = ProcessTVCommand($field_elements, $field_id,'','tvform');
				$index_list = ParseIntputOptions($rs);
				static $i=0;
				foreach ($index_list as $item)
				{
					list($label,$value) = splitOption($item);
					$checked = (isSelected($label,$value,$item,$field_value)) ? ' checked="checked"':'';
					$value = htmlspecialchars($value);
					$field_html .=  '<label for="tv_'.$i.'"><input type="checkbox" value="'.$value.'" id="tv_'.$i.'" name="tv'.$field_id.'[]" '. $checked.' />'.$label.'</label>';
					$i++;
				}
				break;
			case "option": // handles radio buttons
				$rs = ProcessTVCommand($field_elements, $field_id,'','tvform');
				$index_list = ParseIntputOptions($rs);
				static $i=0;
				while (list($label, $item) = each ($index_list))
				{
					list($label,$value) = splitOption($item);
					$checked = (isSelected($label,$value,$item,$field_value)) ?'checked="checked"':'';
					$value = htmlspecialchars($value);
					$field_html .=  '<label for="tv_'.$i.'"><input type="radio" value="'.$value.'" id="tv_'.$i.'" name="tv'.$field_id.'" '. $checked .' />'.$label.'</label>';
					$i++;
				}
				break;
			case "image":	// handles image fields using htmlarea image manager
				global $_lang;
				global $content,$use_editor,$which_editor;
				$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' />&nbsp;<input type="button" value="'.$_lang['insert'].'" onclick="BrowseServer(\'tv'.$field_id.'\')" />';
				break;
			case "file": // handles the input of file uploads
			/* Modified by Timon for use with resource browser */
                global $_lang;
				global $content,$use_editor,$which_editor;
				$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' />&nbsp;<input type="button" value="'.$_lang['insert'].'" onclick="BrowseFileServer(\'tv'.$field_id.'\')" />';
                
				break;
			case "hidden":
				$field_type = 'hidden';
				$field_html .=  '<input type="hidden" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value). '" tvtype="' . $field_type.'" />';
				break;

            case 'custom_tv':
                $custom_output = '';
                /* If we are loading a file */
                if(substr($field_elements, 0, 5) == "@FILE") {
                    $file_name = MODX_BASE_PATH . trim(substr($field_elements, 6));
                    if( !file_exists($file_name) ) {
                        $custom_output = $file_name . ' does not exist';
                    } else {
                        $custom_output = file_get_contents($file_name);
                    }
                } elseif(substr($field_elements, 0, 8) == '@INCLUDE') {
                    $file_name = MODX_BASE_PATH . trim(substr($field_elements, 9));
                    if( !file_exists($file_name) ) {
                        $custom_output = $file_name . ' does not exist';
                    } else {
                        ob_start();
                        include $file_name;
                        $custom_output = ob_get_contents();
                        ob_end_clean();
                    }
                } elseif(substr($field_elements, 0, 6) == "@CHUNK") {
                    $chunk_name = trim(substr($field_elements, 7));
                    $chunk_body = $modx->getChunk($chunk_name);
                    if($chunk_body == false) {
                        $custom_output = $_lang['chunk_no_exist']
                            . '(' . $_lang['htmlsnippet_name']
                            . ':' . $chunk_name . ')';
                } else {
                        $custom_output = $chunk_body;
                    }
                } elseif(substr($field_elements, 0, 5) == "@EVAL") {
                    $eval_str = trim(substr($field_elements, 6));
                    $custom_output = eval($eval_str);
                } else {
                    $custom_output = $field_elements;
                }
                    $replacements = array(
                        '[+field_type+]'   => $field_type,
                        '[+field_id+]'     => $field_id,
                        '[+default_text+]' => $default_text,
                        '[+field_value+]'  => htmlspecialchars($field_value),
                        '[+field_style+]'  => $field_style,
                        );
                $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
                $modx->documentObject = $content;
                $custom_output = $modx->parseDocumentSource($custom_output);
                $field_html .= $custom_output;
                break;
            
			default: // the default handler -- for errors, mostly
				$sname = strtolower($field_type);
				$result = $modx->db->select('snippet','[+prefix+]site_snippets',"name='input:{$field_type}'");
				if($modx->db->getRecordCount($result)==1)
				{
					$field_html .= eval($modx->db->getValue($result));
				}
				else
					$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' />';
		} // end switch statement
		return $field_html;
	} // end renderFormElement function

	function ParseIntputOptions($v)
	{
		global $modx;
		$a = array();
		if(is_array($v)) $a = $v;
		elseif(is_resource($v))
		{
			while ($cols = $modx->db->getRow($v,'num'))
			{
				$a[] = $cols;
			}
		}
		else
		{
			$a = explode('||', $v);
		}
		return $a;
	}
	
	function splitOption($value)
	{
		if(is_array($value))
		{
			$label=$value[0];
			$value=$value[1];
		}
		else
		{
			if(strpos($value,'==')===false)
				$label = $value;
			else
				list($label,$value) = explode('==',$value,2);
		}
		return array($label,$value);
	}
	
	function isSelected($label,$value,$item,$field_value)
	{
		if(is_array($item)) $item = $item['0'];
		if(strpos($item,'==')!==false && strlen($value)==0)
		{
			if(is_array($field_value))
			{
				$rs = in_array($label,$field_value);
			}
			else $rs = ($label===$field_value);
		}
		else
		{
			if(is_array($field_value))
			{
				$rs = in_array($value,$field_value);
			}
			else $rs = ($value===$field_value);
		}
		
		return $rs;
	}
