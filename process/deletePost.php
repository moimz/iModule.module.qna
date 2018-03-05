<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 게시물을 삭제한다.
 *
 * @file /modules/qna/process/deletePost.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 24.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$post = $this->getPost($idx);
if ($post == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$qna = $this->getQna($post->qid);

if ($qna->use_protection == true && $post->type == 'ANSWER' && $post->is_adopted == true && $this->checkPermission($post->qid,'answer_delete') == false) {
	$results->success = false;
	$results->message = $this->getErrorText('PROTECTED_ANSWER');
	return;
}

if ($qna->use_protection == true && $post->type == 'QUESTION' && $post->answer > 0 && $this->checkPermission($post->qid,'question_delete') == false) {
	$results->success = false;
	$results->message = $this->getErrorText('PROTECTED_QUESTION');
	return;
}

if ($post->type == 'QUESTION' && $post->is_closed == true) {
	$results->success = false;
	$results->message = $this->getErrorText('PROTECTED_QUESTION');
	return;
}

if ($post->midx == $this->IM->getModule('member')->getLogged() || ($post->type == 'ANSWER' && $this->checkPermission($post->qid,'answer_delete') == true) || ($post->type != 'ANSWER' || $this->checkPermission($post->qid,'question_delete') == true)) {
	$this->deletePost($idx);
	
	$results->success = true;
	$results->type = $post->type;
} else {
	$results->success = false;
	$results->message = $this->getErrorText('FORBIDDEN');
}
?>