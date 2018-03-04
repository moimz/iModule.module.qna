<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
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
?>
<div class="topbar">
	<?php echo $ment->photo; ?>
	<?php echo $ment->name; ?>
</div>

<div class="content">
	<?php echo $ment->is_secret == true ? '<i class="mi mi-lock"></i>' : ''; ?>
	<?php echo $ment->content; ?>
</div>

<div class="footbar">
	<?php echo GetTime('Y-m-d H:i:s',$ment->reg_date); ?>
	
	<?php if ($permission->modify == true || $permission->delete == true) { ?>
	<i></i>
	<?php if ($permission->modify == true) { ?><button type="button" data-action="modify" data-type="ment" data-idx="<?php echo $ment->idx; ?>">수정</button><?php } ?>
	<?php if ($permission->modify == true && $permission->delete == true) { ?><i></i><?php } ?>
	<?php if ($permission->delete == true) { ?><button type="button" data-action="delete" data-type="ment" data-idx="<?php echo $ment->idx; ?>">삭제</button><?php } ?>
	<?php } ?>
</div>