/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 문의게시판 관리자 UI를 처리한다.
 * 
 * @file /modules/qna/admin/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 3. 3.
 */
var Qna = {
	/**
	 * 문의게시판 목록관리
	 */
	list:{
		/**
		 * 문의게시판 추가/삭제
		 *
		 * @param string qid 게시판아이디 (없을 경우 추가)
		 */
		add:function(qid) {
			new Ext.Window({
				id:"ModuleQnaAddQnaWindow",
				title:(qid ? Qna.getText("admin/list/window/modify") : Qna.getText("admin/list/window/add")),
				modal:true,
				width:750,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleQnaAddQnaForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
						items:[
							new Ext.form.Hidden({
								name:"mode",
								value:(qid ? "modify" : "add")
							}),
							new Ext.form.FieldSet({
								collapsible:true,
								collapsed:false,
								title:Qna.getText("admin/list/form/default_setting"),
								items:[
									new Ext.form.TextField({
										fieldLabel:Qna.getText("admin/list/form/qid"),
										name:"qid",
										maxLength:20,
										readOnly:qid ? true : false
									}),
									new Ext.form.TextField({
										fieldLabel:Qna.getText("admin/list/form/title"),
										name:"title",
										maxLength:50
									})
								]
							}),
							new Ext.form.FieldSet({
								collapsible:true,
								collapsed:false,
								title:Qna.getText("admin/list/form/design_setting"),
								items:[
									Admin.templetField(Qna.getText("admin/list/form/templet"),"templet","module","qna",false,ENV.getProcessUrl("qna","@getTempletConfigs"),["qid"]),
									new Ext.form.FieldContainer({
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												fieldLabel:Qna.getText("admin/list/form/post_limit"),
												name:"post_limit",
												value:20,
												flex:1
											}),
											new Ext.form.NumberField({
												fieldLabel:Qna.getText("admin/list/form/ment_limit"),
												name:"ment_limit",
												value:50,
												flex:1
											})
										]
									}),
									new Ext.form.FieldContainer({
										layout:"hbox",
										items:[
											new Ext.form.ComboBox({
												fieldLabel:Qna.getText("admin/list/form/page_type"),
												name:"page_type",
												store:new Ext.data.ArrayStore({
													fields:["display","value"],
													data:[[Qna.getText("admin/list/page_type/FIXED"),"FIXED"],[Qna.getText("admin/list/page_type/CENTER"),"CENTER"]]
												}),
												editable:false,
												displayField:"display",
												valueField:"value",
												value:"FIXED",
												flex:1,
												listeners:{
													change:function(form,value) {
														var form = Ext.getCmp("ModuleQnaAddQnaForm").getForm();
														if (value == "CENTER" && form.findField("page_limit").getValue() % 2 == 0) {
															form.findField("page_limit").setValue(form.findField("page_limit").getValue() + 1);
															
															Ext.Msg.show({title:Admin.getText("alert/info"),msg:Qna.getText("admin/list/page_type/help"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO});
														}
													}
												}
											}),
											new Ext.form.NumberField({
												fieldLabel:Qna.getText("admin/list/form/page_limit"),
												name:"page_limit",
												value:10,
												flex:1
											})
										]
									})
								]
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/attachment_setting"),
								checkboxName:"use_attachment",
								checkboxToggle:true,
								collapsed:false,
								items:[
									Admin.templetField(Qna.getText("admin/list/form/attachment_templet"),"attachment","module","attachment",Qna.getText("admin/list/form/attachment_templet_default"),ENV.getProcessUrl("qna","@getTempletConfigs"),["qid"]),
								]
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/option"),
								items:[
									new Ext.form.Checkbox({
										name:"allow_secret",
										boxLabel:Qna.getText("admin/list/form/allow_secret"),
										checked:true
									}),
									new Ext.form.Checkbox({
										name:"allow_anonymity",
										boxLabel:Qna.getText("admin/list/form/allow_anonymity"),
										checked:true
									})
								]
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/notice_setting"),
								collapsible:true,
								collapsed:true,
								items:[
									new Ext.form.ComboBox({
										name:"view_notice_page",
										store:new Ext.data.ArrayStore({
											fields:["display","value"],
											data:[[Qna.getText("admin/list/notice_type/FIRST"),"FIRST"],[Qna.getText("admin/list/notice_type/ALL"),"ALL"]]
										}),
										editable:false,
										displayField:"display",
										valueField:"value",
										value:"FIRST",
										flex:1,
										listeners:{
											change:function(form,value) {
												var form = Ext.getCmp("ModuleQnaAddQnaForm").getForm();
												if (form.findField("view_notice_page").getValue() == "ALL" && form.findField("view_notice_count").getValue() == "INCLUDE") {
													Ext.Msg.show({title:Admin.getText("alert/info"),msg:Qna.getText("admin/list/notice_type/help"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO});
												}
											}
										}
									}),
									new Ext.form.ComboBox({
										name:"view_notice_count",
										store:new Ext.data.ArrayStore({
											fields:["display","value"],
											data:[[Qna.getText("admin/list/notice_type/INCLUDE"),"INCLUDE"],[Qna.getText("admin/list/notice_type/EXCLUDE"),"EXCLUDE"]]
										}),
										editable:false,
										displayField:"display",
										valueField:"value",
										value:"INCLUDE",
										flex:1,
										listeners:{
											change:function(form,value) {
												var form = Ext.getCmp("ModuleQnaAddQnaForm").getForm();
												if (form.findField("view_notice_page").getValue() == "ALL" && form.findField("view_notice_count").getValue() == "INCLUDE") {
													Ext.Msg.show({title:Admin.getText("alert/info"),msg:Qna.getText("admin/list/notice_type/help"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO});
												}
											}
										}
									})
								]
							}),
							new Ext.form.Hidden({
								name:"label"
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/label_setting"),
								checkboxName:"use_label",
								checkboxToggle:true,
								collapsed:true,
								items:[
									new Ext.grid.Panel({
										id:"ModuleQnaLabelList",
										border:true,
										tbar:[
											new Ext.Button({
												iconCls:"mi mi-plus",
												text:"라벨추가",
												handler:function() {
													Qna.label.add();
												}
											}),
											new Ext.Button({
												iconCls:"mi mi-trash",
												text:"선택 라벨 삭제",
												handler:function() {
													Qna.label.delete();
												}
											})
										],
										store:new Ext.data.ArrayStore({
											fields:["idx","title","question"],
											sorters:[{property:"title",direction:"ASC"}],
											data:[]
										}),
										flex:1,
										height:300,
										columns:[{
											text:"라벨명",
											dataIndex:"title",
											flex:1
										},{
											text:"게시물수",
											dataIndex:"question",
											width:100,
											align:"right",
											renderer:function(value) {
												return Ext.util.Format.number(value,"0,000")+"개";
											}
										}],
										bbar:[
											new Ext.Button({
												iconCls:"fa fa-caret-up",
												handler:function() {
													Admin.gridSort(Ext.getCmp("ModuleQnaLabelList"),"sort","up");
												}
											}),
											new Ext.Button({
												iconCls:"fa fa-caret-down",
												handler:function() {
													Admin.gridSort(Ext.getCmp("ModuleQnaLabelList"),"sort","down");
												}
											}),
											"->",
											{xtype:"tbtext",text:"더블클릭 : 라벨수정 / 마우스우클릭 : 상세메뉴"}
										],
										selModel:new Ext.selection.CheckboxModel(),
										listeners:{
											itemdblclick:function(grid,record,td,index) {
												Qna.label.add(index);
											},
											itemcontextmenu:function(grid,record,item,index,e) {
												var menu = new Ext.menu.Menu();
												
												menu.addTitle(record.data.title);
												
												menu.add({
													iconCls:"xi xi-form",
													text:"라벨 수정",
													handler:function() {
														Qna.label.add(index);
													}
												});
												
												menu.add({
													iconCls:"mi mi-trash",
													text:"라벨 삭제",
													handler:function() {
														Qna.label.delete();
													}
												});
												
												e.stopEvent();
												menu.showAt(e.getXY());
											}
										}
									})
								]
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/permission_setting"),
								collapsible:true,
								collapsed:true,
								items:[
									Admin.permissionField(Qna.getText("admin/list/form/permission_list"),"permission_list","true"),
									Admin.permissionField(Qna.getText("admin/list/form/permission_view"),"permission_view","true"),
									Admin.permissionField(Qna.getText("admin/list/form/permission_question_write"),"permission_question_write","{$member.type} != 'GUEST'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_answer_write"),"permission_answer_write","{$member.type} != 'GUEST'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_ment_write"),"permission_ment_write","{$member.type} != 'GUEST'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_question_modify"),"permission_question_modify","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_answer_modify"),"permission_answer_modify","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_ment_modify"),"permission_ment_modify","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_question_delete"),"permission_question_delete","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_question_secret"),"permission_question_secret","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_answer_delete"),"permission_answer_delete","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_answer_secret"),"permission_answer_secret","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_answer_adopt"),"permission_answer_adopt","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_ment_delete"),"permission_ment_delete","{$member.type} == 'ADMINISTRATOR'",false),
									Admin.permissionField(Qna.getText("admin/list/form/permission_notice"),"permission_notice","{$member.type} == 'ADMINISTRATOR'",false),
									new Ext.Panel({
										border:false,
										html:'<div class="helpBlock">'+Qna.getText("admin/list/form/permission_help")+'</div>'
									})
								]
							}),
							new Ext.form.FieldSet({
								title:Qna.getText("admin/list/form/point_setting"),
								collapsible:true,
								collapsed:false,
								items:[
									new Ext.form.FieldContainer({
										margin:"0 0 0 105",
										layout:"hbox",
										items:[
											new Ext.form.DisplayField({
												value:Qna.getText("admin/list/form/point"),
												flex:1
											}),
											new Ext.form.DisplayField({
												value:Qna.getText("admin/list/form/exp"),
												margin:"0 0 0 5",
												flex:1
											})
										]
									}),
									new Ext.form.FieldContainer({
										fieldLabel:Qna.getText("admin/list/form/question_write"),
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"question_point",
												value:Ext.getCmp("ModuleQna").basePoints.question_point,
												flex:1
											}),
											new Ext.form.NumberField({
												name:"question_exp",
												margin:"0 0 0 5",
												value:Ext.getCmp("ModuleQna").baseExps.question_exp,
												flex:1
											})
										]
									}),
									new Ext.form.FieldContainer({
										fieldLabel:Qna.getText("admin/list/form/answer_write"),
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"answer_point",
												value:Ext.getCmp("ModuleQna").basePoints.answer_point,
												flex:1
											}),
											new Ext.form.NumberField({
												name:"answer_exp",
												margin:"0 0 0 5",
												value:Ext.getCmp("ModuleQna").baseExps.answer_exp,
												flex:1
											})
										]
									}),
									new Ext.form.FieldContainer({
										fieldLabel:Qna.getText("admin/list/form/ment_write"),
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"ment_point",
												value:Ext.getCmp("ModuleQna").basePoints.ment_point,
												flex:1
											}),
											new Ext.form.NumberField({
												name:"ment_exp",
												margin:"0 0 0 5",
												value:Ext.getCmp("ModuleQna").baseExps.ment_exp,
												flex:1
											})
										]
									}),
									new Ext.form.FieldContainer({
										fieldLabel:Qna.getText("admin/list/form/vote"),
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"vote_point",
												value:Ext.getCmp("ModuleQna").basePoints.vote_point,
												flex:1
											}),
											new Ext.form.NumberField({
												name:"vote_exp",
												margin:"0 0 0 5",
												value:Ext.getCmp("ModuleQna").baseExps.vote_exp,
												flex:1
											})
										]
									})
								]
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Qna.getText("button/confirm"),
						handler:function() {
							Ext.getCmp("ModuleQnaAddQnaForm").getForm().submit({
								url:ENV.getProcessUrl("qna","@saveQna"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleQnaAddQnaWindow").close();
										Ext.getCmp("ModuleQnaList").getStore().reload();
									}});
								},
								failure:function(form,action) {
									if (action.result) {
										if (action.result.message) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_SAVE_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("INVALID_FORM_DATA"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
								}
							});
						}
					}),
					new Ext.Button({
						text:Qna.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleQnaAddQnaWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (qid !== undefined) {
							Ext.getCmp("ModuleQnaAddQnaForm").getForm().load({
								url:ENV.getProcessUrl("qna","@getQna"),
								params:{qid:qid},
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/loading"),
								success:function(form,action) {
									if (form.findField("use_label").checked == true) {
										var label = JSON.parse(form.findField("label").getValue());
										for (var i=0, loop=label.length;i<loop;i++) {
											Ext.getCmp("ModuleQnaLabelList").getStore().add(label[i]);
										}
									}
								},
								failure:function(form,action) {
									if (action.result && action.result.message) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
									Ext.getCmp("ModuleQnaAddQnaWindow").close();
								}
							});
						}
					}
				}
			}).show();
		}
	},
	/**
	 * 라벨 관리
	 *
	 * @param object data 라벨 데이터
	 */
	label:{
		add:function(index) {
			var data = index !== undefined ? Ext.getCmp("ModuleQnaLabelList").getStore().getAt(index).data : null;
			
			new Ext.Window({
				id:"ModuleQnaAddLabelWindow",
				title:(data == null ? "라벨추가" : "라벨수정"),
				modal:true,
				width:400,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleQnaAddLabelForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
						items:[
							new Ext.form.TextField({
								name:"title",
								emptyText:"라벨명",
								allowBlank:false,
								value:(data ? data.title : null),
								validator:function(value) {
									var check = Ext.getCmp("ModuleQnaLabelList").getStore().findExact("title",value);
									if (check == -1 || check == index) return true;
									else return "라벨명이 중복됩니다.";
								}
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:"확인",
						handler:function() {
							var form = Ext.getCmp("ModuleQnaAddLabelForm").getForm();
							var list = Ext.getCmp("ModuleQnaLabelList");
							
							if (form.isValid() == true) {
								var title = form.findField("title").getValue();
								
								if (index === undefined) {
									var idx = 0;
									var question = 0;
									list.getStore().add({idx:0,title:title,question:question});
								} else {
									list.getStore().getAt(index).set({title:title});
								}
								
								Ext.getCmp("ModuleQnaAddLabelWindow").close();
							}
						}
					})
				]
			}).show();
		},
		delete:function() {
			var selected = Ext.getCmp("ModuleQnaLabelList").getSelectionModel().getSelection();
			if (selected.length == 0) {
				Ext.Msg.show({title:Admin.getText("alert/error"),msg:"삭제할 라벨를 선택하여 주십시오.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
				return;
			}
			
			Ext.Msg.show({title:Admin.getText("alert/info"),msg:"선택하신 라벨를 삭제하시겠습니까?<br>삭제되는 라벨의 게시물이 기본 라벨로 이동됩니다.",buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
				if (button == "ok") {
					var store = Ext.getCmp("ModuleQnaLabelList").getStore();
					store.remove(selected);
					for (var i=0, loop=store.getCount();i<loop;i++) {
						store.getAt(i).set({sort:i});
					}
				}
			}});
		}
	}
};