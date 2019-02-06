<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 댓글목록을 가져온다.
 *
 * @file /modules/qna/process/getMents.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 24.
 */
if (defined('__IM__') == false) exit;

$parent = Request('parent');
$post = $this->getPost($parent);
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

$qna = $this->getQna($post->qid);
$position = Request('position');
$direction = Request('direction') == 'next' ? '>' : '<';
$lists = $this->db()->select($this->table->ment)->where('parent',$parent)->where('idx',$position,$direction);
if ($direction == '<') $lists->orderBy('idx','desc')->limit($qna->ment_limit + 1);
$lists = $lists->get();

$previous = false;
if ($direction == '<') {
	$previous = count($lists) > $qna->ment_limit;
	if ($previous == true) array_pop($lists);
}

$configs = json_decode(Request('configs'));
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i] = $this->getMentItemComponent($lists[$i],$configs);
}

$results->success = true;
$results->lists = $lists;
$results->total = $post->ment;
$results->previous = $previous;
?>