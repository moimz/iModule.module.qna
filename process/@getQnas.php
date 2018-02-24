<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모든 문의게시판목록을 불러온다.
 *
 * @file /modules/qna/process/@getQnas.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 2. 18.
 */
if (defined('__IM__') == false) exit;

$start = Request('start');
$limit = Request('limit');
$lists = $this->db()->select($this->table->qna);
$total = $lists->copy()->count();
$sort = Request('sort') ? Request('sort') : 'title';
$dir = Request('dir') ? Request('dir') : 'asc';
if ($limit > 0) $lists->limit($start,$limit);
$lists = $lists->orderBy($sort,$dir)->get();

for ($i=0, $loop=count($lists);$i<$loop;$i++) {
//	$files = $this->db()->select($this->table->attachment)->where('qid',$lists[$i]->qid)->get('idx');
	
//	$lists[$i]->file = count($files);
//	$lists[$i]->file_size = $this->IM->getModule('attachment')->getTotalFileSize($files);
}

$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>