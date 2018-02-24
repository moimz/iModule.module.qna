<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 댓글을 저장한다.
 *
 * @file /modules/qna/process/saveMent.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 2. 17.
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

if ($post == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$qna = $this->getQna($post->qid);
$qid = $qna->qid;

if ($this->checkPermission($qid,'question_write') == false) {
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
	$insert['qid'] = $qid;
	$insert['parent'] = $parent;
	$insert['content'] = $content;
	
	if ($idx) {
		
	} else {
		$insert['midx'] = $this->IM->getModule('member')->getLogged();
		$insert['reg_date'] = time();
		$insert['ip'] = $_SERVER['REMOTE_ADDR'];
		$insert['is_secret'] = $is_secret;
		$insert['is_anonymity'] = $is_anonymity;
		
		$idx = $this->db()->insert($this->table->ment,$insert)->execute();
		
		/**
		 * 게시물 작성자에게 알림메세지를 전송한다.
		 */
		$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'QUESTION',$parent,'NEW_MENT',array('idx'=>$idx));
		
		/**
		 * 포인트 및 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->sendPoint($this->IM->getModule('member')->getLogged(),$qna->ment_point,$this->getModule()->getName(),'MENT',array('idx'=>$idx));
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),$qna->ment_exp,$this->getModule()->getName(),'MENT',array('idx'=>$idx));
	}
	
	$this->updatePost($parent);
	$this->updateQna($qid);
	
	$results->success = true;
	$results->parent = $parent;
	$results->idx = $idx;
} else {
	
}
?>