<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿
 * 
 * @file /modules/qna/templets/default/header.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;

$IM->loadWebFont('XEIcon');
$IM->loadWebFont('FontAwesome');
$IM->loadWebFont('Roboto');
?>
<div data-role="tabbar">
	<div>
		<ul data-role="tab" data-name="intro">
			<li<?php echo $view == 'list' ? ' class="selected"' : ''; ?>><a href="<?php echo $me->getUrl('list',false); ?>">최근 등록된 질문</a></li>
			<li<?php echo $view == 'noreply' ? ' class="selected"' : ''; ?>><a href="<?php echo $me->getUrl('noreply',false); ?>">답변이 없는 질문</a></li>
			<li<?php echo $view == 'write' ? ' class="selected"' : ''; ?>><a href="<?php echo $me->getUrl('write',false); ?>">질문하기</a></li>
			<li<?php echo $view == 'mylist' ? ' class="selected"' : ''; ?>><a href="<?php echo $me->getUrl('mylist',false); ?>">나의 질문</a></li>
		</ul>
	</div>
</div>