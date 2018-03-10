<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿
 * 
 * @file /modules/qna/templets/default/ment.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;
?>
<div class="form" data-option="<?php echo $qna->allow_secret == true || $qna->allow_anonymity == true ? 'TRUE' : 'FALSE'; ?>">
	<div class="inputbox">
		<div data-role="input" data-auto-height="true">
			<textarea name="content" placeholder="<?php echo $me->getText('text/question_ment_help'); ?>"></textarea>
		</div>
		
		<button type="submit">등록하기</button>
		
		<?php if ($qna->allow_secret == true || $qna->allow_anonymity == true) { ?>
		<div class="option">
			<?php if ($qna->allow_secret == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_secret">비밀댓글</label>
			</div>
			<?php } ?>
			
			<?php if ($qna->allow_anonymity == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_anonymity">익명댓글</label>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
	
	
</div>