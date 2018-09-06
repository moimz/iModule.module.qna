<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모달창을 가져온다.
 *
 * @file /modules/qna/process/getModal.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 6.
 */
if (defined('__IM__') == false) exit;

$modal = Request('modal');

if ($modal == 'modify') {
	$type = Request('type');
	$idx = Request('idx');
	
	if ($type == 'ment') {
		$ment = $this->getMent($idx);
		if ($ment == null) {
			$results->success = false;
			$results->message = $this->getErrorText('NOT_FOUND');
			return;
		}
		
		if ($this->checkPermission($ment->qid,'ment_modify') == true || $ment->midx == $this->IM->getModule('member')->getLogged()) {
			$results->success = true;
			$results->modalHtml = $this->getMentModifyModal($idx);
		} else {
			$results->success = false;
			$results->message = $this->getErrorText('FORBIDDEN');
		}
	}
}

if ($modal == 'delete') {
	$type = Request('type');
	$idx = Request('idx');
	
	if ($type == 'post') {
		$post = $this->getPost($idx);
		if ($post == null) {
			$results->success = false;
			$results->message = $this->getErrorText('NOT_FOUND');
			return;
		}
		
		$qna = $this->getQna($post->qid);
		
		if ($qna->use_protection == true && $post->type == 'ANSWER' && $post->is_adopted == true && $this->checkPermission($post->qid,'answer_delete') == false) {
			$results->success = false;
			$results->error = $this->getErrorText('PROTECTED_ANSWER');
			return;
		}
		
		if ($qna->use_protection == true && $post->type == 'QUESTION' && $post->answer > 0 && $this->checkPermission($post->qid,'question_delete') == false) {
			$results->success = false;
			$results->error = $this->getErrorText('PROTECTED_QUESTION');
			return;
		}
		
		if ($post->type == 'QUESTION' && $post->is_closed == true) {
			$results->success = false;
			$results->error = $this->getErrorText('PROTECTED_QUESTION');
			return;
		}
		
		if ($post->midx == $this->IM->getModule('member')->getLogged() || ($post->type == 'ANSWER' && $this->checkPermission($post->qid,'answer_delete') == true) || ($post->type != 'ANSWER' || $this->checkPermission($post->qid,'question_delete') == true)) {
			$results->success = true;
			$results->modalHtml = $this->getPostDeleteModal($idx);
		} else {
			$results->success = false;
			$results->message = $this->getErrorText('FORBIDDEN');
		}
	}
	
	if ($type == 'ment') {
		$ment = $this->getMent($idx);
		if ($ment == null) {
			$results->success = false;
			$results->message = $this->getErrorText('NOT_FOUND');
			return;
		}
		
		if ($this->checkPermission($ment->qid,'ment_delete') == true || $ment->midx == $this->IM->getModule('member')->getLogged()) {
			$results->success = true;
			$results->modalHtml = $this->getMentDeleteModal($idx);
		} else {
			$results->success = false;
			$results->message = $this->getErrorText('FORBIDDEN');
		}
	}
}

if ($modal == 'adopt') {
	$idx = Request('idx');
	$post = $this->getPost($idx);
	
	if ($post == null || $post->type == 'NOTICE') {
		$results->success = false;
		$results->error = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	if ($post->type == 'QUESTION') {
		$question = $post;
	} else {
		$question = $this->getPost($post->parent);
	}
	
	if ($question->is_closed == true) {
		$results->success = false;
		$results->error = $this->getErrorText('ALREADY_CLOSED');
		return;
	}
	
	if ($this->checkPermission($post->qid,'answer_adopt') == true || $question->midx == $this->IM->getModule('member')->getLogged()) {
		$results->success = true;
		$results->modalHtml = $this->getPostAdoptModal($idx);
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('FORBIDDEN');
	}
}
?>