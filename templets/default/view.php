<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 문의게시판 기본 템플릿 - 질문보기
 * 
 * @file /modules/qna/templets/default/view.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 22.
 */
if (defined('__IM__') == false) exit;
?>
<div data-role="post">
	<h4><i><?php echo substr($post->type,0,1); ?></i><?php echo $post->title; ?></h4>
	
	<section>
		<?php if ($post->type != 'NOTICE') { ?>
		<aside>
			<button type="button" data-action="good" data-type="post" data-idx="<?php echo $post->idx; ?>"><i class="fa fa-caret-up"></i></button>
			<?php echo $post->vote; ?>
			<button type="button" data-action="bad" data-type="post" data-idx="<?php echo $post->idx; ?>"><i class="fa fa-caret-down"></i></button>
			
			<time datetime="<?php echo date('c',$post->reg_date); ?>" data-time="<?php echo $post->reg_date; ?>" data-moment="fromNow"></time>
		</aside>
		<?php } ?>
		<article>
			<?php echo $post->content; ?>
			
			<?php if (count($attachments) > 0) { ?>
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

			<?php if ($post->type != 'NOTICE' && count($post->labels) > 0) { ?>
			<div class="labels">
				<?php foreach ($post->labels as $label) { ?>
				<a href="<?php echo $me->getUrl('list',$label->idx.'/1'); ?>" class="label"><?php echo $label->title; ?></a>
				<?php } ?>
			</div>
			<?php } ?>
			
			<div data-role="button">
				<div class="author">
					<?php echo $post->photo; ?>
					<?php echo $post->name; ?>
					<div class="level">
						<div class="level">LV.<b><?php echo $post->member->level->level; ?></b></div>
						<div class="progress">
							<div class="on" style="width:<?php echo sprintf('%0.2f',$post->member->level->exp / $post->member->level->next * 100); ?>%;"></div>
						</div>
					</div>
				</div>
				
				<?php if ($permission->modify == true || $permission->delete == true || $permission->adopt == true) { ?>
				<button type="button" data-action="action" data-type="post" data-idx="<?php echo $post->idx; ?>"><i class="fa fa-caret-down"></i></button>
				<ul data-role="action" data-type="post" data-idx="<?php echo $post->idx; ?>">
					<?php if ($permission->modify == true) { ?>
					<li><button type="button" data-action="modify" data-type="post" data-idx="<?php echo $post->idx; ?>">수정하기</button></li>
					<?php } ?>
					
					<?php if ($permission->delete == true) { ?>
					<li><button type="button" data-action="delete" data-type="post" data-idx="<?php echo $post->idx; ?>">삭제하기</button></li>
					<?php } ?>
					
					<?php if ($permission->adopt == true) { ?>
					<li><button type="button" data-action="adopt" data-type="post" data-idx="<?php echo $post->idx; ?>">질문마감하기</button></li>
					<?php } ?>
				</ul>
				<?php } ?>
			</div>
		</article>
	</section>
</div>

<?php echo $ment; ?>

<?php echo $answer; ?>