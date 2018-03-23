<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판의 최근게시물을 가져온다.
 * 
 * @file /modules/qna/widgets/recently/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 3. 23.
 */
if (defined('__IM__') == false) exit;

$title = $Widget->getValue('title');
$qid = $Widget->getValue('qid') ? $Widget->getValue('qid') : array();
$qid = is_array($qid) == true ? $qid : array($qid);
$label = $Widget->getValue('label') ? $Widget->getValue('label') : array();
$label = is_array($label) == true ? $label : array($label);

$type = in_array($Widget->getValue('type'),array('question','answer')) == true ? $Widget->getValue('type') : null;
$count = $Widget->getValue('count');
$cache = $Widget->getValue('cache');

if ($type == null) return $Templet->getError('INVALID_VALUE',$Widget->getValue('type'));

if ($Widget->checkCache() < time() - $cache) {
	if ($type == 'question') {
		$lists = $me->db()->select($me->getTable('post').' p','p.idx')->join($me->getTable('post_label').' l','l.idx=p.idx','LEFT')->where('p.parent',0);
	} elseif ($type == 'answer') {
		$lists = $me->db()->select($me->getTable('post').' p','p.idx')->join($me->getTable('post_label').' l','l.idx=p.parent','LEFT')->where('p.parent',0,'>');
	}
	if (count($qid) > 0) $lists->where('p.qid',$qid,'IN');
	if (count($label) > 0) $lists->where('l.idx',$label,'IN');
	
	$lists = $lists->limit($count)->orderBy('p.reg_date','desc')->groupBy('p.idx')->get('idx');
	
	for ($i=0, $loop=count($lists);$i<$loop;$i++) {
		$lists[$i] = $me->getPost($lists[$i],true);
	}
	
	$Widget->storeCache(json_encode($lists,JSON_UNESCAPED_UNICODE));
} else {
	$lists = json_decode($Widget->getCache());
}

if (count($qid) == 1) {
	$options = count($label) == 1 ? array('label'=>$label[0]) : array();
	$page = $IM->getContextUrl('qna',$qid[0],array(),$options,true);
	$more = $page == null ? null : $IM->getUrl($page->menu,$page->page,false);
} else {
	$more = null;
}

return $Templet->getContext('index',get_defined_vars());
?>