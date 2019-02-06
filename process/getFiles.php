<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 첨부파일을 가져온다.
 *
 * @file /modules/qna/process/getFiles.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 6.
 */
if (defined('__IM__') == false) exit;

$idx = Decoder(Request('idx'));
if ($idx == false) {
	$results->success = false;
} else {
	$idx = json_decode($idx);
	$module = Request('module');
	$target = Request('target');
	
	$files = array();
	$lists = $this->db()->select($this->table->attachment)->where('type',$idx->type)->where('parent',$idx->idx)->get();
	for ($i=0, $loop=count($lists);$i<$loop;$i++) {
		$file = $this->IM->getModule('attachment')->getFileInfo($lists[$i]->idx);
		if ($file != null && ($module == null || $module == $file->module) && ($target == null || $target == $file->target)) {
			$files[] = $file;
		}
	}
	
	$results->success = true;
	$results->files = $files;
}
?>