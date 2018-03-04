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

$this->db()->delete($this->table->ment)->where('idx',$ment->idx)->execute();

/**
 * 댓글작성자와 삭제자가 다른 경우 댓글작성자에게 알림메세지를 전송한다.
 */
if ($ment->midx != $this->IM->getModule('member')->getLogged()) {
	$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'MENT',$idx,'DELETE_MENT',array('from'=>$this->IM->getModule('member')->getLogged(),'parent'=>$ment->parent));
}

/**
 * 새 댓글 작성 알림메세지를 삭제한다.
 */
$this->IM->getModule('push')->cancelPush($post->midx,$this->getModule()->getName(),$post->type == 'A' ? 'ANSWER' : 'QUESTION',$post->idx,'NEW_MENT',array('idx'=>$idx));

/**
 * 포인트 및 활동내역을 기록한다.
 */
$this->IM->getModule('member')->sendPoint($this->IM->getModule('member')->getLogged(),$qna->ment_point * -1,$this->getModule()->getName(),'DELETE_MENT',array('parent'=>$ment->parent));
$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),$qna->ment_exp * -1,$this->getModule()->getName(),'DELETE_MENT',array('parent'=>$ment->parent));

$this->updatePost($ment->parent);
$this->updateQna($ment->qid);

$results->success = true;
$results->idx = $ment->idx;
$results->parent = $ment->parent;
?>