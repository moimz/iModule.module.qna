<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿 - 답변 작성
 * 
 * @file /modules/qna/templets/default/answer.write.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 18.
 */
if (defined('__IM__') == false) exit;
?>
<ul data-role="form" class="inner">
	<li>
		<div data-role="input">
			<?php $wysiwyg->doLayout(); ?>
			<?php $uploader->doLayout(); ?>
		</div>
	</li>
	<?php if ($qna->allow_secret == true || $qna->allow_anonymity == true) { ?>
	<li>
		<label><?php echo $me->getText('text/answer_option'); ?></label>
		<div>
			<?php if ($qna->allow_secret == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_secret" value="TRUE"<?php echo $post != null && $post->is_secret == true ? ' checked="checked"' : ''; ?>><?php echo $me->getText('answer_option/secret'); ?></label>
			</div>
			<?php } ?>
			
			<?php if ($qna->allow_anonymity == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_anonymity" value="TRUE"<?php echo $post != null && $post->is_anonymity == true ? ' checked="checked"' : ''; ?>><?php echo $me->getText('answer_option/anonymity'); ?></label>
			</div>
			<?php } ?>
		</div>
	</li>
	<?php } ?>
</ul>

<?php if ($qna->use_protection == true || $qna->use_force_adopt == true) { ?>
<div class="questionHelp">
	<?php if ($qna->use_protection == true) { ?><p><i></i><?php echo $me->getText('text/protection_answer_help'); ?></p><?php } ?>
</div>
<?php } ?>

<div data-role="button">
	<a href="<?php echo $post == null ? $me->getUrl('list',false) : $me->getUrl('view',$post->idx); ?>"><?php echo $me->getText('button/cancel'); ?></a>
	<button type="submit"><?php echo $post == null ? $me->getText('button/answer_write') : $me->getText('button/answer_modify'); ?></button>
</div>