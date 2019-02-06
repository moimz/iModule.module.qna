<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 권한을 확인한다.
 *
 * @file /modules/qna/process/checkPermission.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 6.
 */
if (defined('__IM__') == false) exit;

$type = Request('type');

if ($type == 'question_secret') {
	$idx = Request('idx');
	$post = $this->getPost($idx);
	
	if ($post == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	if ($this->checkPermission($post->qid,$type) == true || $post->midx == $this->IM->getModule('member')->getLogged()) {
		$results->success = true;
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('FORBIDDEN');
	}
}

if (strpos($type,'post_') === 0) {
	$idx = Request('idx');
	$post = $this->getPost($idx);
	
	if ($post == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	$qna = $this->getQna($post->qid);
	$type = $post->type == 'ANSWER' ? str_replace('post_','answer_',$type) : str_replace('post_','question_',$type);
	
	if ($this->checkPermission($post->qid,$type) == true || $post->midx == $this->IM->getModule('member')->getLogged()) {
		if ($qna->use_protection == true) {
			if ($post->type == 'QUESTION' && $this->checkPermission($post->qid,$type) == false && $post->answer > 0) {
				$results->success = false;
				$results->error = $this->getErrorText('PROTECTED_QUESTION');
				return;
			}
			
			if ($post->type == 'QUESTION' && $post->is_closed == true && $this->checkPermission($qid,$type) == false) {
				$results->success = false;
				$results->error = $this->getErrorText('CLOSED_QUESTION');
				return;
			}
			
			if ($post->type == 'ANSWER' && $this->checkPermission($post->qid,$type) == false && $post->is_adopted == true) {
				$results->success = false;
				$results->error = $this->getErrorText('PROTECTED_ANSWER');
				return;
			}
			
			if ($post->type == 'NOTICE' && $this->checkPermission($post->qid,'notice') == false) {
				$results->success = false;
				$results->error = $this->getErrorText('FORBIDDEN');
				return;
			}
			
			$results->success = true;
			$results->type = $post->type;
			$results->idx = $post->idx;
			$results->parent = $post->parent;
		} else {
			$results->success = true;
			$results->type = $post->type;
			$results->idx = $post->idx;
			$results->parent = $post->parent;
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('FORBIDDEN');
	}
}
?>