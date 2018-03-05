<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 댓글을 삭제한다.
 *
 * @file /modules/qna/process/deleteMent.php
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

$qna = $this->getQna($ment->qid);
if ($this->checkPermission($ment->qid,'ment_delete') == false && $ment->midx != $this->IM->getModule('member')->getLogged()) {
	$results->success = false;
	$results->message = $this->getErrorText('FORBIDDEN');
	return;
}

$this->deleteMent($ment->idx);

$results->success = true;
$results->idx = $ment->idx;
$results->parent = $ment->parent;
?>