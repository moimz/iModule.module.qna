<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 댓글을 가져온다.
 *
 * @file /modules/qna/process/getMent.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 24.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$ment = $this->getMent($idx);
if ($ment == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$post = $this->getPost($ment->parent);
if ($post == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

/**
 * 게시물 보기 권한이 없을 경우
 */
if ($this->checkPermission($post->qid,'view') == false && $post->midx != $this->IM->getModule('member')->getLogged()) {
	$results->success = false;
	$results->message = $this->getErrorText('FORBIDDEN');
	return;
}

/**
 * 게시물이 비밀글인 경우
 */
if ($post->is_secret == true && $this->checkPermission($post->qid,'question_secret') == false && $post->midx != $this->IM->getModule('member')->getLogged()) {
	$results->success = false;
	$results->message = $this->getErrorText('FORBIDDEN');
	return;
}

$configs = json_decode(Request('configs'));

$results->success = true;
$results->idx = $ment->idx;
$results->parent = $ment->parent;
$results->ment = $ment;
$results->mentHtml = $this->getMentItemComponent($ment,$configs);
?>