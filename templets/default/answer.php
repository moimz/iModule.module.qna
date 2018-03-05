<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿
 * 
 * @file /modules/qna/templets/default/answer.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;
?>
<?php if ($question->answer > 0 && $answer) { ?>
<h4><i>A</i> 답변</h4>
<?php echo $answer; ?>
<?php } ?>

<?php if ($form) { ?>
<h4><i class="xi xi-pen-point"></i> 답변 <?php echo $post == null ? '작성하기' : '수정하기'; ?></h4>

<div class="answerHelp">
	<p><i></i> 질문에 대한 해결방법을 알고 있는 누구나 답변을 작성하실 수 있습니다.</p>
	<p><i></i> 답변이 아닌 내용은 질문의 댓글을 이용하여 주시기 바랍니다.</p>
</div>

<?php echo $form; ?>
<?php } ?>