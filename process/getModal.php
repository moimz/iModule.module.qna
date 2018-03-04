<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모달창을 가져온다.
 *
 * @file /modules/qna/process/getModal.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 2. 27.
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
?>