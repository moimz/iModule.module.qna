<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 게시판 정보를 불러온다.
 *
 * @file /modules/qna/process/@getQna.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 3. 3.
 */
if (defined('__IM__') == false) exit;

$qid = Request('qid');
$data = $this->db()->select($this->table->qna)->where('qid',$qid)->getOne();

if ($data == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
} else {
	$permission = json_decode($data->permission);
	unset($data->permission);
	
	if ($permission != null) {
		foreach ($permission as $key=>$value) {
			$data->{'permission_'.$key} = $value;
		}
	}
	
	unset($data->templet_configs);
	
	$attachment = json_decode($data->attachment);
	unset($data->attachment);
	$data->use_attachment = $attachment->attachment;
	$data->attachment = $data->use_attachment == true ? $attachment->templet : '#';
	
	$data->allow_secret = $data->allow_secret == 'TRUE';
	$data->allow_anonymity = $data->allow_anonymity == 'TRUE';
	
	$data->use_label = $data->use_label != 'NONE';
	if ($data->use_label == true) {
		$label = $this->db()->select($this->table->label,'idx,title,question')->where('qid',$qid)->orderBy('title','asc')->get();
		$data->label = json_encode($label,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
	}
	
	$results->success = true;
	$results->data = $data;
}
?>