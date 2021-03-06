<?php  // $Id: lib.php,v 1.1.4.2 2008/01/15 23:53:28 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

define('FEEDBACK_RADIO_LINE_SEP', '|');
define('FEEDBACK_RADIO_ADJUST_SEP', '<<<<<');

class feedback_item_radio extends feedback_item_base {
    var $type = "radio";
    function init() {
    
    }
    
    function show_edit($item, $usehtmleditor = false) {

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        
        //check, whether the buttons are vertical or horizontal
        $presentation = $horizontal = '';
        @list($presentation, $horizontal) = explode(FEEDBACK_RADIO_ADJUST_SEP, $item->presentation);
        if(isset($horizontal) AND $horizontal == 1) {
            $horizontal = true;
        }else {
            $horizontal = false;
        }

    ?>
        <table>
            <tr>
                <th colspan="2"><?php print_string('radio', 'feedback');?>
                    &nbsp;(<input type="checkbox" name="required" value="1" <?php
                    $item->required=isset($item->required)?$item->required:0;
                    echo ($item->required == 1?'checked="checked"':'');
                    ?> />&nbsp;<?php print_string('required', 'feedback');?>)
                </th>
            </tr>
            <tr>
                <td colspan="2">
                    <?php print_string('adjustment', 'feedback');?>:
                    &nbsp;<?php print_string('vertical', 'feedback');?><input type="radio" name="horizontal" value="0" <?php echo $horizontal ? '' : 'checked="checked"';?> />
                    &nbsp;<?php print_string('horizontal', 'feedback');?><input type="radio" name="horizontal" value="1" <?php echo $horizontal ? 'checked="checked"' : '';?> />
                </td>
            </tr>
            <tr>
                <td><?php print_string('item_name', 'feedback');?></td>
                <td><input type="text" id="itemname" name="itemname" size="40" maxlength="255" value="<?php echo isset($item->name)?htmlspecialchars(stripslashes_safe($item->name)):'';?>" /></td>
            </tr>
            <tr>
                <td>
                    <?php print_string('radio_values', 'feedback');?>
                    <?php print_string('use_one_line_for_each_value', 'feedback');?>
                </td>
                <td>
    <?php
                    $itemvalues = str_replace(FEEDBACK_RADIO_LINE_SEP, "\n", stripslashes_safe($presentation));
    ?>
                    <textarea name="itemvalues" cols="30" rows="5"><?php echo $itemvalues;?></textarea>
                </td>
            </tr>
        </table>
    <?php
    }

    //liefert ein eindimensionales Array mit drei Werten(typ, name, XXX)
    //XXX ist ein eindimensionales Array (anzahl der Antworten bei Typ Radio) Jedes Element ist eine Struktur (answertext, answercount)
    function get_analysed($item, $groupid = false, $courseid = false, $facetofacesessionid = false) {
        //fuer den Anfang erstmal nur die Radiobuttons
        $analysedItem = array();
        $analysedItem[] = $item->typ;
        $analysedItem[] = $item->name;
        //die moeglichen Antworten extrahieren
        $answers = null;
        $presentation = '';
        @list($presentation) = explode(FEEDBACK_RADIO_ADJUST_SEP, $item->presentation); //remove the adjustment-info
        
        $answers = explode (FEEDBACK_RADIO_LINE_SEP, stripslashes_safe($presentation));
        if(!is_array($answers)) return null;

        //die Werte holen
        //$values = get_records('feedback_value', 'item', $item->id);
   $values = feedback_get_group_values($item, $groupid, $courseid, $facetofacesessionid);
   if(!$values && !$facetofacesessionid) return null;
        //schleife ueber den Werten und ueber die Antwortmoeglichkeiten
        
        $analysedAnswer = array();

        for($i = 1; $i <= sizeof($answers); $i++) {
            $ans = null;
            $ans->answertext = $answers[$i-1];
            $ans->answercount = 0;
      if($values) {
          foreach($values as $value) {
             //ist die Antwort gleich dem index der Antworten + 1?
             if ($value->value == $i) {
                $ans->answercount++;
             }
          }
            }
            $ans->quotient = $ans->answercount / sizeof($values);
            $analysedAnswer[] = $ans;
        }
        $analysedItem[] = $analysedAnswer;
        return $analysedItem;
    }

    function get_printval($item, $value) {
        $printval = '';
        
        if(!isset($value->value)) return $printval;
                
        @list($presentation) = explode(FEEDBACK_RADIO_ADJUST_SEP, $item->presentation); //remove the adjustment-info
        
        $presentation = explode (FEEDBACK_RADIO_LINE_SEP, stripslashes_safe($presentation));
        $index = 1;
        foreach($presentation as $pres){
            if($value->value == $index){
                $printval = $pres;
                break;
            }
            $index++;
        }
        return $printval;
    }

    function print_analysed($item, $itemnr = 0, $groupid = false, $courseid = false) {
        $sep_dec = get_string('separator_decimal', 'feedback');
        if(substr($sep_dec, 0, 2) == '[['){
            $sep_dec = FEEDBACK_DECIMAL;
        }
        
        $sep_thous = get_string('separator_thousand', 'feedback');
        if(substr($sep_thous, 0, 2) == '[['){
            $sep_thous = FEEDBACK_THOUSAND;
        }
            
        $analysedItem = $this->get_analysed($item, $groupid, $courseid);
        if($analysedItem) {
            //echo '<table>';
            $itemnr++;
            echo '<tr><th colspan="2" align="left">'. $itemnr . '.)&nbsp;' . $analysedItem[1] .'</th></tr>';
            $analysedVals = $analysedItem[2];
            $pixnr = 0;
            foreach($analysedVals as $val) {
                if( function_exists("bcmod")) {
                    $pix = 'pics/' . bcmod($pixnr, 10) . '.gif';
                }else {
                    $pix = 'pics/0.gif';
                }
                $pixnr++;
                $pixwidth = intval($val->quotient * FEEDBACK_MAX_PIX_LENGTH);
                $quotient = number_format(($val->quotient * 100), 2, $sep_dec, $sep_thous);
                echo '<tr><td align="left" valign="top">-&nbsp;&nbsp;' . trim($val->answertext) . ':</td><td align="left" width="'.FEEDBACK_MAX_PIX_LENGTH.'"><img style=" vertical-align: baseline;" src="'.$pix.'" height="5" width="'.$pixwidth.'" />&nbsp;' . $val->answercount . (($val->quotient > 0)?'&nbsp;('. $quotient . '&nbsp;%)':'').'</td></tr>';
            }
            //echo '</table>';
        }
        return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false, $colOffset = 0, $facetofacesessionid = 0) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid, $facetofacesessionid);


        $data = $analysed_item[2];

        $worksheet->setFormat("<l><f><ro2><vo><c:green>");
        //frage schreiben
        $worksheet->write_string($rowOffset, $colOffset, $analysed_item[1]);
        if(is_array($data)) {
            for($i = 0; $i < sizeof($data); $i++) {
                $aData = $data[$i];
                
                $worksheet->setFormat("<l><f><ro2><vo><c:blue>");
                $worksheet->write_string($rowOffset, $colOffset + $i + 1, trim($aData->answertext));
                
                $worksheet->setFormat("<l><vo>");
                $worksheet->write_number($rowOffset + 1, $colOffset + $i + 1, $aData->answercount);
                $worksheet->setFormat("<l><f><vo><pr>");
                $worksheet->write_number($rowOffset + 2, $colOffset + $i + 1, $aData->quotient);
            }
        }
        $rowOffset +=3 ;
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        
        //extract the adjustment-info
        $presentation = $horizontal = '';
        @list($presentation, $horizontal) = explode(FEEDBACK_RADIO_ADJUST_SEP, $item->presentation);
        if(isset($horizontal) AND $horizontal == 1) {
            $horizontal = true;
        }else {
            $horizontal = false;
        }
        
        $presentation = explode (FEEDBACK_RADIO_LINE_SEP, stripslashes_safe($presentation));
        if($highlightrequire AND $item->required AND intval($value) <= 0) {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
        $requiredmark =  ($item->required == 1)?'<font color="red">*</font>':'';
    ?>
        <td <?php echo $highlight;?> valign="top" align="<?php echo $align;?>"><?php echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);?></td>
        <td valign="top" align="<?php echo $align;?>">
    <?php
        $index = 1;
        $checked = '';
        if($readonly){
            foreach($presentation as $radio){
                if($value == $index){
                    // print_simple_box_start($align);
                    print_box_start('generalbox boxalign'.$align);
                    echo text_to_html($radio, true, false, false);
                    // print_simple_box_end();
                    print_box_end();
                    break;
                }
                $index++;
            }
        } else {
    ?>
            <table><tr>
            <td valign="top" align="<?php echo $align;?>"><input type="radio"
                    name="<?php echo $item->typ . '_' . $item->id ;?>"
                    id="<?php echo $item->typ . '_' . $item->id.'_xxx';?>"
                    value="" <?php echo $value ? '' : 'checked="checked"';?> />
            </td>
            <td align="<?php echo $align;?>">
                <label for="<?php echo $item->typ . '_' . $item->id.'_xxx';?>"><?php print_string('not_selected', 'feedback');?>&nbsp;</label>
            </td>
            </tr></table>
    <?php
            if($horizontal) {
                echo '<table><tr>';
            }
            foreach($presentation as $radio){
                if($value == $index){
                    $checked = 'checked="checked"';
                }else{
                    $checked = '';
                }
                $inputname = $item->typ . '_' . $item->id;
                $inputid = $inputname.'_'.$index;
                if($horizontal) {
    ?>
                    <td valign="top" align="<?php echo $align;?>"><input type="radio"
                        name="<?php echo $inputname;?>"
                        id="<?php echo $inputid;?>"
                        value="<?php echo $index;?>" <?php echo $checked;?> />
                    </td>
                    <td align="<?php echo $align;?>">
                        <label for="<?php echo $inputid;?>"><?php echo text_to_html($radio, true, false, false);?>&nbsp;</label>
                    </td>
    <?php
                }else {
    ?>
                    <table><tr>
                    <td valign="top" align="<?php echo $align;?>"><input type="radio"
                            name="<?php echo $inputname;?>"
                            id="<?php echo $inputid;?>"
                            value="<?php echo $index;?>" <?php echo $checked;?> />
                    </td><td align="<?php echo $align;?>"><label for="<?php echo $inputid;?>"><?php echo text_to_html($radio, true, false, false);?>&nbsp;</label>
                    </td></tr></table>
    <?php
                }
                $index++;
            }
            if($horizontal) {
                echo '</tr></table>';
            }
            /*
            if($item->required == 1) {
                echo '<input type="hidden" name="'.$item->typ . '_' . $item->id.'" value="1" />';
            }
            */
        }
    ?>
        </td>
    <?php
    }

    function check_value($value, $item) {
        //if the item is not required, so the check is true if no value is given
        if((!isset($value) OR $value == '' OR $value == 0) AND $item->required != 1) return true;
        if(intval($value) > 0)return true;
        return false;
    }

    function create_value($data) {
        $data = clean_param($data, PARAM_INT);
        return $data;
    }

    function get_presentation($data) {
        $present = str_replace("\n", FEEDBACK_RADIO_LINE_SEP, trim($data->itemvalues));
        if($data->horizontal == 1) {
            $present .= FEEDBACK_RADIO_ADJUST_SEP.'1';
        }
        return $present;
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
