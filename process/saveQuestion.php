<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 질문을 저장한다.
 *
 * @file /modules/qna/process/saveQuestion.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;

if ($this->IM->getModule('member')->isLogged() == false) {
	$results->success = false;
	$results->error = $this->getErrorText('REQUIRED_LOGIN');
	return;
}

$qid = Request('qid');
$qna = $this->getQna($qid);

if ($this->checkPermission($qid,'question_write') == false) {
	$results->success = false;
	$results->error = $this->getErrorText('FORBIDDEN');
	return;
}

$errors = array();
$idx = Request('idx');
$title = Request('title') ? Request('title') : $errors['title'] = $this->getErrorText('REQUIRED');
$content = Request('content') ? Request('content') : $errors['content'] = $this->getErrorText('REQUIRED');
$labels = is_array(Request('labels')) == true ? Request('labels') : array();
$is_notice = $this->checkPermission($qid,'notice') == true && Request('is_notice') == 'TRUE' ? true : false;
$is_secret = $qna->allow_secret == true && Request('is_secret') == 'TRUE' ? 'TRUE' : 'FALSE';
$is_anonymity = $qna->allow_anonymity == true && Request('is_anonymity') == 'TRUE' ? 'TRUE' : 'FALSE';

if ($is_notice == true) {
	$labels = array();
} else {
	if ($qna->use_label == 'FORCE' && count($labels) == 0) {
		$errors['labels'] = $this->getErrorText('REQUIRED');
	}
	
	for ($i=0, $loop=count($labels);$i<$loop;$i++) {
		if ($this->db()->select($this->table->label)->where('idx',$labels[$i])->where('qid',$qid)->has() == false) {
			$errors['labels'] = $this->getErrorText('NOT_FOUND');
		}
	}
}

$attachments = is_array(Request('attachments')) == true ? Request('attachments') : array();
for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
	$attachments[$i] = Decoder($attachments[$i]);
}
$content = $this->IM->getModule('wysiwyg')->encodeContent($content,$attachments);

if ($idx) {
	$post = $this->getPost($idx);
	if ($post == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	if ($post->type == 'QUESTION') {
		if ($qna->use_protection == true && $this->checkPermission($qid,'question_modify') == false && $post->answer > 0) {
			$results->success = false;
			$reslts->error = $this->getErrorText('PROTECTED_QUESTION');
			return;
		}
		
		if ($post->is_closed == true && $this->checkPermission($qid,'question_modify') == false) {
			$results->success = false;
			$results->error = $this->getErrorText('CLOSED_QUESTION');
			return;
		}
	} elseif ($post->type == 'ANSWER') {
		if ($qna->use_protection == true && $this->checkPermission($qid,'answer_modify') == false && $post->is_adopted == true) {
			$results->success = false;
			$reslts->error = $this->getErrorText('PROTECTED_ANSWER');
			return;
		}
	}
	
	$answers = $this->db()->select($this->table->post)->where('parent',$idx)->get('content');
	$search = GetString($content."\n".implode("\n",$answers),'index');
} else {
	if ($is_notice == false && $qna->use_force_adopt === true && $this->db()->select($this->table->post)->where('midx',$this->IM->getModule('member')->getLogged())->where('type','QUESTION')->where('is_adopted','FALSE')->count() > 3) {
		$results->success = false;
		$results->error = $this->getErrorText('NOT_ADOPTED_PREVIOUS_QUESTION');
		return;
	}
	$search = GetString($content,'index');
}

if (count($errors) == 0) {
	$insert = array();
	$insert['qid'] = $qid;
	$insert['type'] = $is_notice == true ? 'NOTICE' : 'QUESTION';
	$insert['title'] = $title;
	$insert['content'] = $content;
	$insert['search'] = $search;
	$insert['is_secret'] = $is_secret;
	$insert['is_anonymity'] = $is_anonymity;
	
	if ($idx) {
		$this->db()->update($this->table->post,$insert)->where('idx',$idx)->execute();
		
		/**
		 * 글작성자와 수정한 사람이 다를 경우 알림메세지를 전송한다.
		 */
		if ($post->midx != $this->IM->getModule('member')->getLogged()) {
			$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'QUESTION',$idx,'MODIFY',array('from'=>$this->IM->getModule('member')->getLogged()));
		}
		
		/**
		 * 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),0,$this->getModule()->getName(),'QUESTION_MODIFY',array('idx'=>$idx));
	} else {
		$insert['midx'] = $this->IM->getModule('member')->getLogged();
		$insert['ip'] = $_SERVER['REMOTE_ADDR'];
		$insert['reg_date'] = time();
		
		$idx = $this->db()->insert($this->table->post,$insert)->execute();
		
		/**
		 * 포인트 및 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->sendPoint($this->IM->getModule('member')->getLogged(),$qna->question_point,$this->getModule()->getName(),'QUESTION',array('idx'=>$idx));
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),$qna->question_exp,$this->getModule()->getName(),'QUESTION',array('idx'=>$idx));
	}
	
	if ($qna->use_label != 'NONE') {
		$updateLabels = array();
		
		if (count($labels) > 0) {
			foreach ($labels as $label) {
				$this->db()->replace($this->table->post_label,array('idx'=>$idx,'label'=>$label))->execute();
				$updateLabels[] = $label;
			}
			
			$updateLabels = array_merge($updateLabels,$this->db()->select($this->table->post_label)->where('idx',$idx)->where('label',$labels,'NOT IN')->get('label'));
			$this->db()->delete($this->table->post_label)->where('idx',$idx)->where('label',$labels,'NOT IN')->execute();
		} else {
			$updateLabels = $this->db()->select($this->table->post_label)->where('idx',$idx)->get('label');
		}
		
		$updateLabels = array_unique($updateLabels);
		foreach ($updateLabels as $label) {
			$this->updateLabel($label);
		}
	}
	
	$mAttachment = $this->IM->getModule('attachment');
	for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
		$file = $mAttachment->getFileInfo($attachments[$i]);
		
		if ($file != null) {
			$this->db()->replace($this->table->attachment,array('idx'=>$file->idx,'qid'=>$qid,'type'=>'POST','parent'=>$idx))->execute();
		}
		$mAttachment->filePublish($attachments[$i]);
	}
	
	$this->updateQna($qid);
	
	$results->success = true;
	$results->idx = $idx;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>