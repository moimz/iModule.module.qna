<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 문의게시판정보를 저장한다.
 *
 * @file /modules/qna/process/@saveQna.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 3. 3.
 */
if (defined('__IM__') == false) exit;

$mode = Request('mode');
$errors = array();
$insert = array();
$insert['title'] = Request('title') ? Request('title') : $errors['title'] = $this->getErrorText('REQUIRED');
$insert['templet'] = Request('templet') ? Request('templet') : $errors['templet'] = $this->getErrorText('REQUIRED');
$insert['post_limit'] = Request('post_limit') && is_numeric(Request('post_limit')) == true ? Request('post_limit') : $errors['post_limit'] = $this->getErrorText('REQUIRED');
$insert['ment_limit'] = Request('ment_limit') && is_numeric(Request('ment_limit')) == true ? Request('ment_limit') : $errors['ment_limit'] = $this->getErrorText('REQUIRED');
$insert['page_limit'] = Request('page_limit') && is_numeric(Request('page_limit')) == true ? Request('page_limit') : $errors['page_limit'] = $this->getErrorText('REQUIRED');
$insert['page_type'] = Request('page_type') && in_array(Request('page_type'),array('FIXED','CENTER')) == true ? Request('page_type') : $errors['page_type'] = $this->getErrorText('REQUIRED');

$insert['allow_secret'] = Request('allow_secret') ? 'TRUE' : 'FALSE';
$insert['allow_anonymity'] = Request('allow_anonymity') ? 'TRUE' : 'FALSE';

$insert['view_notice_page'] = Request('view_notice_page') && in_array(Request('view_notice_page'),array('FIRST','ALL')) == true ? Request('view_notice_page') : $errors['view_notice_page'] = $this->getErrorText('REQUIRED');
$insert['view_notice_count'] = Request('view_notice_count') && in_array(Request('view_notice_count'),array('INCLUDE','EXCLUDE')) == true ? Request('view_notice_count') : $errors['view_notice_count'] = $this->getErrorText('REQUIRED');

$use_label = Request('use_label') == 'on';
if ($use_label == true) {
	$insert['use_label'] = 'USED';
	$label = json_decode(Request('label'));
} else {
	$insert['use_label'] = 'NONE';
	$label = array();
}

$insert['question_point'] = Request('question_point') && is_numeric(Request('question_point')) == true ? Request('question_point') : 0;
$insert['question_exp'] = Request('question_exp') && is_numeric(Request('question_exp')) == true ? Request('question_exp') : 0;
$insert['answer_point'] = Request('answer_point') && is_numeric(Request('answer_point')) == true ? Request('answer_point') : 0;
$insert['answer_exp'] = Request('answer_exp') && is_numeric(Request('answer_exp')) == true ? Request('answer_exp') : 0;
$insert['ment_point'] = Request('ment_point') && is_numeric(Request('ment_point')) == true ? Request('ment_point') : 0;
$insert['ment_exp'] = Request('ment_exp') && is_numeric(Request('ment_exp')) == true ? Request('ment_exp') : 0;
$insert['vote_point'] = Request('vote_point') && is_numeric(Request('vote_point')) == true ? Request('vote_point') : 0;
$insert['vote_exp'] = Request('vote_exp') && is_numeric(Request('vote_exp')) == true ? Request('vote_exp') : 0;

$attachment = new stdClass();
$attachment->attachment = Request('use_attachment') ? true : false;
if ($attachment->attachment == true) {
	$attachment->templet = Request('attachment') ? Request('attachment') : $errors['attachment'] = $this->getErrorText('REQUIRED');
	$attachment->templet_configs = new stdClass();
}

$templetConfigs = new stdClass();
$permission = new stdClass();
foreach ($_POST as $key=>$value) {
	if (preg_match('/^permission_/',$key) == true && preg_match('/_selector$/',$key) == false) {
		if ($this->IM->checkPermissionString($value) !== true) {
			$errors[$key] = $this->IM->checkPermissionString($value);
		} else {
			$permission->{str_replace('permission_','',$key)} = $value;
		}
	}
	
	if (preg_match('/^templet_configs_/',$key) == true) {
		$templetConfigs->{str_replace('templet_configs_','',$key)} = $value;
	}
	
	if (preg_match('/^attachment_configs_/',$key) == true) {
		$attachment->templet_configs->{str_replace('attachment_configs_','',$key)} = $value;
	}
}

$insert['templet_configs'] = json_encode($templetConfigs,JSON_UNESCAPED_UNICODE);
$insert['permission'] = json_encode($permission,JSON_UNESCAPED_UNICODE);
$insert['attachment'] = json_encode($attachment,JSON_UNESCAPED_UNICODE);

if ($mode == 'add') {
	$qid = Request('qid');
	if ($this->db()->select($this->table->qna)->where('qid',$qid)->has() == true) $errors['qid'] = $this->getErrorText('DUPLICATED');
	else $insert['qid'] = $qid;
}

if (count($errors) == 0) {
	if ($mode == 'add') {
		$this->db()->insert($this->table->qna,$insert)->execute();
	} else {
		$qid = Request('qid');
		$this->db()->update($this->table->qna,$insert)->where('qid',$qid)->execute();
	}
	
	if ($use_label == true) {
		$labels = array();
		for ($i=0, $loop=count($label);$i<$loop;$i++) {
			if ($label[$i]->idx == 0) {
				$labels[] = $this->db()->insert($this->table->label,array('qid'=>$qid,'title'=>$label[$i]->title))->execute();
			} else {
				$labels[] = $label[$i]->idx;
				$this->db()->update($this->table->label,array('title'=>$label[$i]->title))->where('idx',$label[$i]->idx)->execute();
			}
		}
		
		$this->db()->delete($this->table->label)->where('qid',$qid)->where('idx',$labels,'NOT IN')->execute();
		$this->db()->delete($this->table->post_label)->where('label',$labels,'NOT IN')->execute();
	} else {
		$labels = $this->db()->select($this->table->label)->where('qid',$qid)->get('idx');
		if (count($labels) > 0) {
			$this->db()->delete($this->table->label)->where('qid',$qid)->execute();
			$this->db()->delete($this->table->post_label)->where('label',$labels,'IN')->execute();
		}
	}
	
	$results->success = true;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>