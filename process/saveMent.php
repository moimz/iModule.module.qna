<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 댓글을 저장한다.
 *
 * @file /modules/qna/process/saveMent.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 6.
 */
if (defined('__IM__') == false) exit;

if ($this->IM->getModule('member')->isLogged() == false) {
	$results->success = false;
	$results->error = $this->getErrorText('REQUIRED_LOGIN');
	return;
}

$idx = Request('idx');
$parent = Request('parent');
$post = $this->getPost($parent);

if ($post == null || $post->type == 'NOTICE') {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$qna = $this->getQna($post->qid);
$qid = $qna->qid;

if ($this->checkPermission($qid,'ment_write') == false) {
	$results->success = false;
	$results->error = $this->getErrorText('FORBIDDEN');
	return;
}

$errors = array();
$content = Request('content') ? Request('content') : $errors['content'] = $this->getErrorText('REQUIRED');
$is_secret = $qna->allow_secret == true && Request('is_secret') ? 'TRUE' : 'FALSE';
$is_anonymity = $qna->allow_anonymity == true && Request('is_anonymity') ? 'TRUE' : 'FALSE';

if (count($errors) == 0) {
	$insert = array();
	$insert['content'] = $content;
	$insert['is_secret'] = $is_secret;
	$insert['is_anonymity'] = $is_anonymity;
	
	if ($idx) {
		$ment = $this->getMent($idx);
		
		$this->db()->update($this->table->ment,$insert)->where('idx',$idx)->execute();
		
		/**
		 * 댓글작성자와 수정한 사람이 다를 경우 알림메세지를 전송한다.
		 */
		if ($ment->midx != $this->IM->getModule('member')->getLogged()) {
			//$this->IM->getModule('push')->sendPush($ment->midx,$this->getModule()->getName(),'ment',$idx,'ment_modify',array('idx'=>$idx,'from'=>$this->IM->getModule('member')->getLogged()));
		}
		
		/**
		 * 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),0,$this->getModule()->getName(),'ment_modify',array('idx'=>$idx));
	} else {
		$insert['qid'] = $qid;
		$insert['parent'] = $parent;
		$insert['midx'] = $this->IM->getModule('member')->getLogged();
		$insert['reg_date'] = time();
		$insert['ip'] = $_SERVER['REMOTE_ADDR'];
		
		$idx = $this->db()->insert($this->table->ment,$insert)->execute();
		
		if ($idx === false) {
			$results->success = false;
			$results->message = $this->getErrorText('DATABASE_INSERT_ERROR');
			return;
		}
		
		/**
		 * 게시물 작성자에게 알림메세지를 전송한다.
		 */
		if ($post->type == 'QUESTION') {
			if ($post->midx != $this->IM->getModule('member')->getLogged()) {
				$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),strtolower($post->type),$parent,'new_question_ment',array('idx'=>$idx,'title'=>$post->title));
			}
		} else {
			if ($post->midx != $this->IM->getModule('member')->getLogged()) {
				$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),strtolower($post->type),$parent,'new_answer_ment',array('idx'=>$idx,'title'=>$post->title));
			}
			
			$question = $this->getPost($post->parent);
			if ($question->midx != $this->IM->getModule('member')->getLogged()) {
				$this->IM->getModule('push')->sendPush($question->midx,$this->getModule()->getName(),strtolower($post->type),$parent,'new_answer_ment',array('idx'=>$idx,'title'=>$question->title));
			}
		}
		
		/**
		 * 포인트 및 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->sendPoint($this->IM->getModule('member')->getLogged(),$qna->ment_point,$this->getModule()->getName(),'ment',array('idx'=>$idx));
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),$qna->ment_exp,$this->getModule()->getName(),'ment',array('idx'=>$idx));
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