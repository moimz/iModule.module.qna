<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판 기본 템플릿
 * 
 * @file /modules/qna/templets/default/list.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
if (defined('__IM__') == false) exit;

$IM->loadWebFont('Roboto');
?>
<div data-role="toolbar">
	<?php if (count($labels) > 0) { ?>
	<div data-role="input">
		<select name="label">
			<option value="0"><?php echo $me->getText('text/label_all'); ?></option>
			<?php for ($i=0, $loop=count($labels);$i<$loop;$i++) { ?>
			<option value="<?php echo $labels[$i]->idx; ?>"<?php echo $label == $labels[$i]->idx ? ' selected="selected"' : ''; ?>><?php echo $labels[$i]->title; ?></option>
			<?php } ?>
		</select>
	</div>
	<?php } ?>
	
	<div data-role="search">
		<div data-role="input">
			<input type="search" name="keyword" value="<?php echo GetString($keyword,'input'); ?>">
		</div>
		<button type="submit"><i class="mi mi-search"></i></button>
	</div>
	
	<a href="<?php echo $link->write; ?>" class="submit"><?php echo $me->getText('button/question_write'); ?></a>
</div>

<ul data-role="table" class="black">
	<li class="thead">
		<span class="count vote">추천</span>
		<span class="count answer">답변</span>
		<span class="count hit">조회</span>
		<span class="title">질문</span>
	</li>
	<?php foreach ($notices as $item) { ?>
	<li class="tbody notice">
		<span class="notice"><b>공지</b></span>
		<span class="title"><a href="<?php echo $item->link; ?>"><?php echo $item->is_secret == true ? '<i class="mi mi-lock"></i>' : ''; ?><?php echo $item->title; ?></a></span>
		<span class="name"><?php echo $item->photo; ?><?php echo $item->name; ?></span>
		<span class="reg_date"><?php echo GetTime('Y-m-d',$item->reg_date); ?></span>
	</li>
	<?php } ?>
	<?php foreach ($lists as $item) { ?>
	<li class="tbody">
		<span class="count vote">
			<a href="<?php echo $item->link; ?>"><b><?php echo $item->good - $item->bad; ?></b><small>votes</small></a>
		</span>
		<span class="count answer<?php echo $item->answer > 0 ? ' hasAnswer' : ''; ?><?php echo $item->is_adopted == true > 0 ? ' adopted' : ''; ?><?php echo $item->is_closed == true > 0 ? ' closed' : ''; ?>">
			<a href="<?php echo $item->link; ?>"><b><?php echo $item->answer; ?></b><small>answers</small></a>
		</span>
		<span class="count hit">
			<a href="<?php echo $item->link; ?>"><b><?php echo $item->hit; ?></b><small>hits</small></a>
		</span>
		<span class="title">
			<a href="<?php echo $item->link; ?>"><?php echo $item->is_secret == true ? '<i class="mi mi-lock"></i>' : ''; ?><?php echo $item->title; ?></a>
			
			<div class="details">
				<?php for ($i=0, $loop=count($item->labels);$i<$loop;$i++) { ?>
				<a href="<?php echo $me->getUrl(null,$item->labels[$i]->idx.'/1'); ?>" class="label"><?php echo $item->labels[$i]->title; ?></a>
				<?php } ?>
				
				<div class="author">
					<?php echo $item->photo; ?>
					<?php echo $item->name; ?>
				</div>
			</div>
		</span>
	</li>
	<?php } ?>
	
	<?php if (count($lists) == 0) { ?>
	<li class="empty">게시물이 없습니다.</li>
	<?php } ?>
</ul>

<div data-role="searchbar">
	<a href="<?php echo $link->write; ?>"><i class="xi xi-marquee-add"></i><span><?php echo $me->getText('button/question_write'); ?></span></a>
	
	<div class="search">
		<div data-role="input">
			<input type="search" name="keyword" value="<?php echo GetString($keyword,'input'); ?>">
		</div>
		<button type="submit"><i class="mi mi-search"></i></button>
	</div>
</div>

<div class="pagination">
	<?php echo $pagination; ?>
</div>