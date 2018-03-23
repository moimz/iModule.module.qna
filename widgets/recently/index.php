<?php
/**
 * 이 파일은 iModule 게시판모듈 일부입니다. (https://www.imodule.kr)
 *
 * 게시판의 최근게시물을 가져온다.
 * 
 * @file /modules/qna/widgets/recently/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160910
 */
if (defined('__IM__') == false) exit;

$title = $Widget->getValue('title');
$qid = $Widget->getValue('bid') ? $Widget->getValue('bid') : array();
$qid = is_array($qid) == true ? $qid : array($qid);
$category = $Widget->getValue('category') ? $Widget->getValue('category') : array();
$category = is_array($category) == true ? $category : array($category);

$type = in_array($Widget->getValue('type'),array('post','ment')) == true ? $Widget->getValue('type') : null;
$count = $Widget->getValue('count');
$cache = $Widget->getValue('cache');

if ($type == null) return $Templet->getError('INVALID_VALUE',$Widget->getValue('type'));

if ($Widget->checkCache() < time() - $cache) {
	$lists = $me->db()->select($me->getTable($type));
	if (count($qid) > 0) $lists->where('qid',$qid,'IN');
	if (count($category) > 0) $lists->where('category',$category,'IN');
	$lists = $lists->limit($count)->orderBy('idx','desc')->get();
	
	for ($i=0, $loop=count($lists);$i<$loop;$i++) {
		$lists[$i] = $me->getPost($lists[$i],true);
		$lists[$i]->category = $lists[$i]->category == 0 ? null : $me->getCategory($lists[$i]->category);
	}
	
	$Widget->storeCache(json_encode($lists,JSON_UNESCAPED_UNICODE));
} else {
	$lists = json_decode($Widget->getCache());
}

if (count($qid) == 1) {
	$options = count($category) == 1 ? array('category'=>$category[0]) : array();
	$page = $IM->getContextUrl('board',$qid[0],array(),$options,true);
	$more = $page == null ? null : $IM->getUrl($page->menu,$page->page,false);
} else {
	$more = null;
}

return $Templet->getContext('index',get_defined_vars());
?>