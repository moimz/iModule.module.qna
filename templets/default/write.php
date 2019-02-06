<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 문의게시판 기본 템플릿 - 게시물 작성
 * 
 * @file /modules/qna/templets/default/write.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 18.
 */
if (defined('__IM__') == false) exit;
?>
<ul data-role="form" class="black inner">
	<li>
		<label><?php echo $me->getText('text/title'); ?></label>
		<div>
			<div data-role="input">
				<input type="text" name="title" value="<?php echo $post == null ? '' : GetString($post->title,'input'); ?>">
			</div>
		</div>
	</li>
	<li>
		<div data-role="input">
			<?php echo $wysiwyg->doLayout(); ?>
			<?php echo $uploader->doLayout(); ?>
		</div>
	</li>
	<?php if (count($labels) > 0) { ?>
	<li>
		<label><?php echo $me->getText('text/label'); ?></label>
		<div>
			<div data-role="inputset" class="inline">
				<?php foreach ($labels as $label) { ?>
				<div data-role="input"><label><input type="checkbox" name="labels[]" value="<?php echo $label->idx; ?>"<?php echo $post != null && in_array($label->idx,$post->labels) == true ? ' checked="checked"' : ''; ?>><?php echo $label->title; ?></label></div>
				<?php } ?>
			</div>
		</div>
	</li>
	<?php } ?>
	<?php if ($me->checkPermission($qna->qid,'notice') == true || $qna->allow_secret == true || $qna->allow_anonymity == true) { ?>
	<li>
		<label><?php echo $me->getText('text/question_option'); ?></label>
		<div>
			<?php if ($me->checkPermission($qna->qid,'notice') == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_notice" value="TRUE"<?php echo $post != null && $post->is_notice == true ? ' checked="checked"' : ''; ?>><?php echo $me->getText('question_option/notice'); ?></label>
			</div>
			<?php } ?>
			
			<?php if ($qna->allow_secret == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_secret" value="TRUE"<?php echo $post != null && $post->is_secret == true ? ' checked="checked"' : ''; ?>><?php echo $me->getText('question_option/secret'); ?></label>
			</div>
			<?php } ?>
			
			<?php if ($qna->allow_anonymity == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_anonymity" value="TRUE"<?php echo $post != null && $post->is_anonymity == true ? ' checked="checked"' : ''; ?>><?php echo $me->getText('question_option/anonymity'); ?></label>
			</div>
			<?php } ?>
		</div>
	</li>
	<?php } ?>
</ul>

<?php if ($qna->use_protection == true || $qna->use_force_adopt == true) { ?>
<div class="questionHelp">
	<?php if ($qna->use_protection == true) { ?><p><i></i><?php echo $me->getText('text/protection_help'); ?></p><?php } ?>
	<?php if ($qna->use_force_adopt == true) { ?><p><i></i><?php echo $me->getText('text/force_adopt_help'); ?></p><?php } ?>
</div>
<?php } ?>

<div data-role="button">
	<a href="<?php echo $post == null ? $me->getUrl('list',false) : $me->getUrl('view',$post->idx); ?>"><?php echo $me->getText('button/cancel'); ?></a>
	<button type="submit"><?php echo $post == null ? $me->getText('button/question_write') : $me->getText('button/question_modify'); ?></button>
</div>