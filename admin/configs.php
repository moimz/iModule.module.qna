<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 모듈 환경설정 패널을 가져온다.
 * 
 * @file /modules/qna/admin/configs.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2020. 10. 20.
 */
if (defined('__IM__') == false) exit;
?>
<script>
new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:"10 10 5 10",
	width:500,
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
	items:[
		new Ext.form.FieldSet({
			title:Qna.getText("admin/configs/form/point_setting"),
			collapsible:true,
			collapsed:false,
			items:[
				new Ext.form.FieldContainer({
					margin:"0 0 0 105",
					layout:"hbox",
					items:[
						new Ext.form.DisplayField({
							value:Qna.getText("admin/configs/form/point"),
							flex:1
						}),
						new Ext.form.DisplayField({
							value:Qna.getText("admin/configs/form/exp"),
							margin:"0 0 0 5",
							flex:1
						})
					]
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Qna.getText("admin/configs/form/question_write"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"question_point",
							flex:1
						}),
						new Ext.form.NumberField({
							name:"question_exp",
							margin:"0 0 0 5",
							flex:1
						})
					]
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Qna.getText("admin/configs/form/answer_write"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"answer_point",
							flex:1
						}),
						new Ext.form.NumberField({
							name:"answer_exp",
							margin:"0 0 0 5",
							flex:1
						})
					]
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Qna.getText("admin/configs/form/ment_write"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"ment_point",
							flex:1
						}),
						new Ext.form.NumberField({
							name:"ment_exp",
							margin:"0 0 0 5",
							flex:1
						})
					]
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Qna.getText("admin/configs/form/vote"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"vote_point",
							flex:1
						}),
						new Ext.form.NumberField({
							name:"vote_exp",
							margin:"0 0 0 5",
							flex:1
						})
					]
				})
			]
		})
	]
});
</script>