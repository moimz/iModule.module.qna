<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 답변을 채택하거나, 질문을 마감한다.
 *
 * @file /modules/qna/process/adopt.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 2. 24.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$post = $this->getPost($idx);

if ($post == null || $post->type == 'N') {
	$results->success = false;
	$results->error = $this->getErrorText('NOT_FOUND');
	return;
}

$qna = $this->getQna($post->qid);

if ($post->type == 'Q') {
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
	if ($post->idx == $question->idx) {
		$this->db()->update($this->table->post,array('is_adopted'=>'CLOSED'))->where('idx',$post->idx)->execute();
	} else {
		$this->db()->update($this->table->post,array('is_adopted'=>'TRUE'))->where('idx',$post->idx)->execute();
		$this->db()->update($this->table->post,array('is_adopted'=>'TRUE'))->where('idx',$question->idx)->execute();
		
		/**
		 * 답변자에게 알림메세지를 전송한다.
		 */
		$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'ANSWER',$post->idx,'ADOPTED',array('parent'=>$question->idx));
		
		/**
		 * 답변자의 포인트 및 활동내역을 기록한다.
		 */
		$this->IM->getModule('member')->sendPoint($post->midx,$qna->adopted_point,$this->getModule()->getName(),'ADOPTED',array('idx'=>$post->idx));
		$this->IM->getModule('member')->addActivity($post->midx,$qna->adopted_exp,$this->getModule()->getName(),'ADOPTED',array('idx'=>$post->idx));
	}
	
	/**
	 * 질문자와 채택자가 다를 경우 알림메세지를 전송한다.
	 */
	if ($question->midx != $this->IM->getModule('member')->getLogged()) {
		$this->IM->getModule('push')->sendPush($question->midx,$this->getModule()->getName(),'QUESTION',$question->idx,'ADOPTED',array('from'=>$this->IM->getModule('member')->getLogged()));
	}
	
	/**
	 * 질문자의 포인트 및 활동내역을 기록한다.
	 */
	if ($question->midx == $this->IM->getModule('member')->getLogged()) {
		$this->IM->getModule('member')->sendPoint($question->midx,$qna->adopted_point,$this->getModule()->getName(),'ADOPT',array('idx'=>$question->idx));
		$this->IM->getModule('member')->addActivity($question->midx,$qna->adopted_exp,$this->getModule()->getName(),'ADOPT',array('idx'=>$question->idx));
	} else {
		$this->IM->getModule('member')->addActivity($this->IM->getModule('member')->getLogged(),0,$this->getModule()->getName(),'ADOPT',array('idx'=>$question->idx));
	}
	
	$results->success = true;
} else {
	$results->success = false;
	$results->message = $this->getErrorText('FORBIDDEN');
}