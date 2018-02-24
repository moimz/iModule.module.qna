/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판모듈 UI를 제어한다.
 * 
 * @file /modules/qna/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
var Qna = {
	getUrl:function(view,idx) {
		var url = $("div[data-module=qna]").attr("data-base-url") ? $("div[data-module=qna]").attr("data-base-url") : ENV.getUrl(null,null,false);
		if (!view || view == false) return url;
		url+= "/"+view;
		if (!idx || idx == false) return url;
		return url+"/"+idx;
	},
	/**
	 * 목록보기
	 */
	list:{
		init:function() {
			var $form = $("#ModuleQnaListForm");
			
			$("select[name=label]",$form).on("change",function() {
				if ($(this).val() == 0) location.href = Qna.getUrl("list",1);
				else location.href = Qna.getUrl("list",$(this).val()+"/1");
			});
			
			$("input[name=keyword]",$form).on("change",function() {
				$("input[name=keyword]",$form).val($(this).val());
			});
			
			$form.on("submit",function() {
				var label = $("select[name=label]",$form).length > 0 ? $("select[name=label]",$form).val() : 0;
				if (label == 0) $form.attr("action",Qna.getUrl("list",1));
				else $form.attr("action",Qna.getUrl("list",label+"/"+1));
				
				$("input[name=keyword]",$form).disable();
				$("input[name=keyword]",$form).first().enable();
				
				$("select[name=label]",$form).disable();
			});
			
			$("a",$form).on("click",function(e) {
				var link = document.createElement("a");
				link.href = $(this).attr("href");
				
				if (link.hash.indexOf("#secret-") == 0) {
					Qna.view.secret(link.hash.replace("#secret-",""),link.search);
					e.preventDefault();
				}
			});
		}
	},
	/**
	 * 질문보기
	 */
	view:{
		init:function(id) {
			var $container = $("#"+id);
			
			$("button[data-action][data-type=post]",$container).on("click",function() {
				alert("아직 지원되지 않습니다.");
			});
		},
		secret:function(idx,query) {
			$.send(ENV.getProcessUrl("qna","checkPermission"),{type:"question_secret",idx:idx},function(result) {
				if (result.success == true) {
					location.href = Qna.getUrl("view",idx)+(query ? "?"+query : "");
				}
			});
		}
	},
	/**
	 * 댓글
	 */
	ment:{
		init:function(id) {
			if (typeof id == "string") {
				var $form = $("#"+id);
				
				if (id.search(/ModuleQnaMentForm-/) == 0) {
					$form.inits(Qna.ment.submit);
				}
			} else if (typeof id == "number") {
				Qna.ment.init($("div[data-module=qna] div[data-role=ment][data-parent="+id+"] div[data-role=list]"));
			} else {
				var $container = id;
				
				$("button[data-action][data-type=ment]",$container).on("click",function() {
					alert("아직 지원되지 않습니다.");
				});
			}
		},
		load:function(parent,direction,callback) {
			var configs = JSON.parse($("div[data-module=qna]").attr("data-configs"));
			
			var position = 0;
			var $lists = $("div[data-module=qna] div[data-role=ment][data-parent="+parent+"] div[data-role=list]");
			if (direction == "next") {
				var $last = $("div[data-role=item]",$lists).last();
				if ($last.length > 0) position = $last.attr("data-idx");
			} else {
				var $first = $("div[data-role=item]",$lists).first();
				if ($first.length > 0) position = $first.attr("data-idx");
			}
			
			$.send(ENV.getProcessUrl("qna","getMents"),{parent:parent,position:position,direction:direction,configs:JSON.stringify(configs)},function(result) {
				if (result.success == true) {
					for (var i=0, loop=result.lists.length;i<loop;i++) {
						var $item = $(result.lists[i]);
						
						if (direction == "next") $lists.append($item);
						else $lists.prepend($item);
						
						Qna.ment.init($item);
						
						if (typeof callback == "function") callback(result);
					}
				}
			});
		},
		reset:function(parent) {
			var $form = $("#ModuleQnaMentForm-"+parent);
			$("textarea",$form).val("");
			$("input[type=checkbox]",$form).checked(false);
			$form.status("default");
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("qna","saveMent"),function(result) {
				if (result.success == true) {
					Qna.ment.reset(result.parent);
					Qna.ment.load(result.parent,"next",function(result) {
						var $container = $("div[data-role=ment][data-parent="+result.parent+"]",$("div[data-module=qna]"));
						$("div[data-role=item][data-idx="+result.idx+"]",$container).scroll();
					});
				}
			});
		}
	},
	/**
	 * 질문작성
	 */
	question:{
		init:function(id) {
			var $form = $("#"+id);
			
			$form.inits(Qna.question.submit);
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("qna","saveQuestion"),function(result) {
				if (result.success == true) {
					location.href = Qna.getUrl("view",result.idx);
				}
			});
		}
	},
	/**
	 * 답변작성
	 */
	answer:{
		init:function(id) {
			var $form = $("#"+id);
			
			$form.inits(Qna.answer.submit);
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("qna","saveAnswer"),function(result) {
				if (result.success == true) {
					location.href = Qna.getUrl("view",result.parent);
				}
			});
		}
	}
};