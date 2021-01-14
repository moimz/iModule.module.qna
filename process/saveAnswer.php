<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 질문을 저장한다.
 *
 * @file /modules/qna/process/saveAnswer.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 5.
 */
if (defined('__IM__') == false) exit;

if ($this->IM->getModule('member')->isLogged() == false) {
	$results->success = false;
	$results->error = $this->getErrorText('REQUIRED_LOGIN');
	return;
}

$errors = array();
$idx = Request('idx');
$parent = Request('parent');

$question = $this->getPost($parent);
if ($question == null || $question->type != 'QUESTION') {
	$results->success = false;
	$results->error = $this->getErrorText('NOT_FOUND');
	return;
}

$qid = $question->qid;
$qna = $this->getQna($qid);

if ($this->checkPermission($qid,'answer_write') == false) {
	$results->success = false;
	$results->error = $this->getErrorText('FORBIDDEN');
	return;
}

if ($question->midx == $this->IM->getModule('member')->getLogged()) {
	$results->success = false;
	$results->error = $this->getErrorText('CANNOT_ANSWER_TO_MY_QUESTION');
	return;
}

$content = Request('content') ? Request('content') : $errors['content'] = $this->getErrorText('REQUIRED');
$is_secret = $qna->allow_secret == true && Request('is_secret') == 'TRUE' ? 'TRUE' : 'FALSE';
$is_anonymity = $qna->allow_anonymity == true && Request('is_anonymity') == 'TRUE' ? 'TRUE' : 'FALSE';

$field1 = Request('field1');
$field2 = Request('field2');
$field3 = Request('field3');
$field4 = Request('field4') == null || is_numeric(Request('field4')) == false ? null : Request('field4');
$field5 = Request('field5') == null || is_numeric(Request('field5')) == false ? null : Request('field5');
$field6 = Request('field6') == null || is_numeric(Request('field6')) == false ? null : Request('field6');
$extra = Request('extra') ? Request('extra') : null;

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
	
	if ($this->getModule()->getConfig('use_protection') == true && $this->checkPermission('answer_modify') == false && $post->is_adopted == true) {
		$results->success = false;
		$reslts->error = $this->getErrorText('PROTECTED_ANSWER');
		return;
	}
}

if (count($errors) == 0) {
	$insert = array();
	$insert['qid'] = $qid;
	$insert['type'] = 'ANSWER';
	$insert['parent'] = $parent;
	$insert['title'] = $question->title;
	$insert['content'] = $content;
	$insert['search'] = '';
	$insert['is_secret'] = $is_secret;
	$insert['is_anonymity'] = $is_anonymity;
	
	if ($field1 !== null) $insert['field1'] = $field1;
	if ($field2 !== null) $insert['field2'] = $field2;
	if ($field3 !== null) $insert['field3'] = $field3;
	if ($field4 !== null) $insert['field4'] = $field4;
	if ($field5 !== null) $insert['field5'] = $field5;
	if ($field6 !== null) $insert['field6'] = $field6;
	if ($extra) $insert['extra'] = $extra;
	
	if ($idx) {
		$this->db()->update($this->table->post,$insert)->where('idx',$idx)->execute();
		
		/**
		 * 글작성자와 수정한 사람이 다를 경우 알림메세지를 전송한다.
		 */
		if ($post->midx != $this->IM->getModule('member')->getLogged()) {
			$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'answer',$idx,'modify',array('from'=>$this->IM->getModule('member')->getLogged(),'title'=>$question->title));
		}
		
		/**
		 * 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),0,$this->getModule()->getName(),'answer_modify',array('idx'=>$idx));
	} else {
		$insert['midx'] = $this->IM->getModule('member')->getLogged();
		$insert['ip'] = $_SERVER['REMOTE_ADDR'];
		$insert['reg_date'] = time();
		
		$idx = $this->db()->insert($this->table->post,$insert)->execute();
		
		if ($idx === false) {
			$results->success = false;
			$results->message = $this->getErrorText('DATABASE_INSERT_ERROR');
			return;
		}
		
		/**
		 * 질문자에게 알림메세지를 전송한다.
		 */
		$this->IM->getModule('push')->sendPush($question->midx,$this->getModule()->getName(),'question',$parent,'new_answer',array('idx'=>$idx,'title'=>$question->title));
		
		/**
		 * 포인트 및 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->sendPoint($this->IM->getModule('member')->getLogged(),$qna->answer_point,$this->getModule()->getName(),'answer',array('idx'=>$idx));
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),$qna->answer_exp,$this->getModule()->getName(),'answer',array('idx'=>$idx));
	}
	
	$mAttachment = $this->IM->getModule('attachment');
	for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
		$file = $mAttachment->getFileInfo($attachments[$i]);
		
		if ($file != null) {
			$this->db()->replace($this->table->attachment,array('idx'=>$file->idx,'qid'=>$qid,'type'=>'POST','parent'=>$idx))->execute();
		}
		$mAttachment->filePublish($attachments[$i]);
	}
	
	$deleteds = $this->db()->select($this->table->attachment)->where('qid',$qid)->where('type','POST')->where('parent',$idx);
	if (count($attachments) > 0) $deleteds->where('idx',$attachments,'NOT IN');
	$deleteds = $deleteds->get('idx');
	foreach ($deleteds as $deleted) {
		$mAttachment->fileDelete($deleted);
	}
	
	$this->updatePost($parent);
	$this->updateQna($qid);
	
	$results->success = true;
	$results->parent = $parent;
	$results->idx = $idx;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>