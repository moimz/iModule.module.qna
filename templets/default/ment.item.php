<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 문의게시판 기본 템플릿 - 댓글항목
 * 
 * @file /modules/qna/templets/default/ment.item.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;

$IM->loadWebFont('Roboto');
?>
<div class="topbar">
	<?php echo $ment->photo; ?>
	<?php echo $ment->name; ?>
	
	<?php if ($permission->modify == true || $permission->delete == true) { ?>
	<button type="button" data-action="action" data-type="ment" data-idx="<?php echo $ment->idx; ?>"><i class="fa fa-caret-down"></i></button>
	<ul data-role="action" data-type="ment" data-idx="<?php echo $ment->idx; ?>">
		<?php if ($permission->modify == true) { ?><li><button type="button" data-action="modify" data-type="ment" data-idx="<?php echo $ment->idx; ?>"><i class="xi xi-pen"></i>수정</button></li><?php } ?>
		<?php if ($permission->delete == true) { ?><li><button type="button" data-action="delete" data-type="ment" data-idx="<?php echo $ment->idx; ?>"><i class="xi xi-trash"></i>삭제</button></li><?php } ?>
	</ul>
	<?php } ?>
</div>

<div class="content">
	<?php echo $ment->is_secret == true ? '<i class="mi mi-lock"></i>' : ''; ?>
	<?php echo $ment->content; ?>
</div>

<div class="footbar">
	<?php echo GetTime('Y-m-d H:i:s',$ment->reg_date); ?>
</div>