<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿
 * 
 * @file /modules/qna/templets/default/answer.item.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 22.
 */
if (defined('__IM__') == false) exit;
?>
<div data-role="post">
	<section>
		<aside>
			<button type="button" data-action="good" data-type="post" data-idx="<?php echo $post->idx; ?>"><i class="fa fa-caret-up"></i></button>
			<?php echo $post->vote; ?>
			<button type="button" data-action="bad" data-type="post" data-idx="<?php echo $post->idx; ?>"><i class="fa fa-caret-down"></i></button>
		</aside>
		<article>
			<?php echo $post->content; ?>
			
			<?php if (count($attachments) > 0) { $IM->addHeadResource('style',$IM->getModule('attachment')->getModule()->getDir().'/styles/style.css'); ?>
			<div data-module="attachment">
				<h5><i class="xi xi-clip"></i>첨부파일</h5>
				
				<ul>
					<?php for ($i=0, $loop=count($attachments);$i<$loop;$i++) { ?>
					<li>
						<i class="icon" data-type="<?php echo $attachments[$i]->type; ?>"></i>
						<a href="<?php echo $attachments[$i]->download; ?>"><span class="size">(<?php echo GetFileSize($attachments[$i]->size); ?>)</span><?php echo $attachments[$i]->name; ?></a>
					</li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
			
			<div data-role="button">
				<div class="author">
					<?php echo $post->photo; ?>
					<?php echo $post->name; ?>
					<?php echo GetTime('Y-m-d H:i:s',$post->reg_date); ?>
				</div>
				
				<?php if ($permission->adopt == true) { ?>
				<button type="button" data-action="adopt" data-type="post" data-idx="<?php echo $post->idx; ?>" class="submit">답변채택하기</button>
				<?php } ?>
				
				<?php if ($permission->modify == true) { ?>
				<button type="button" data-action="modify" data-type="post" data-idx="<?php echo $post->idx; ?>">수정하기</button>
				<?php } ?>
				
				<?php if ($permission->delete == true) { ?>
				<button type="button" data-action="delete" data-type="post" data-idx="<?php echo $post->idx; ?>" class="danger">삭제하기</button>
				<?php } ?>
			</div>
		</article>
	</section>
</div>