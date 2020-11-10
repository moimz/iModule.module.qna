<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 문의게시판모듈 관리자패널을 구성한다.
 * 
 * @file /modules/qna/admin/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2020. 3. 3.
 */
if (defined('__IM__') == false) exit;

$basePoints = array();
$baseExps = array();
foreach ($this->getModule()->getConfig() as $key=>$value) {
	if (preg_match('/_point$/',$key) == true) {
		$basePoints[$key] = $value;
	} elseif (preg_match('/_exp$/',$key) == true) {
		$baseExps[$key] = $value;
	}
}
?>
<script>
Ext.onReady(function () { Ext.getCmp("iModuleAdminPanel").add(
	new Ext.TabPanel({
		id:"ModuleQna",
		border:false,
		tabPosition:"bottom",
		basePoints:<?php echo json_encode($basePoints); ?>,
		baseExps:<?php echo json_encode($baseExps); ?>,
		items:[
			new Ext.grid.Panel({
				id:"ModuleQnaList",
				iconCls:"fa fa-file-text-o",
				title:Qna.getText("admin/list/title"),
				border:false,
				tbar:[
					new Ext.Button({
						text:Qna.getText("admin/list/add_qna"),
						iconCls:"mi mi-plus",
						handler:function() {
							Qna.list.add();
						}
					}),
					new Ext.Button({
						text:Qna.getText("admin/list/delete_board"),
						iconCls:"mi mi-trash",
						handler:function() {
							Qna.list.delete();
						}
					})
				],
				store:new Ext.data.JsonStore({
					proxy:{
						type:"ajax",
						simpleSortMode:true,
						url:ENV.getProcessUrl("qna","@getQnas"),
						reader:{type:"json"}
					},
					remoteSort:true,
					sorters:[{property:"qid",direction:"ASC"}],
					autoLoad:true,
					pageSize:50,
					fields:["qid","title","nickname","exp","point","reg_date","last_login","display_url","count","image"],
					listeners:{
						load:function(store,records,success,e) {
							if (success == false) {
								if (e.getError()) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
							}
						}
					}
				}),
				columns:[{
					text:Qna.getText("admin/list/columns/qid"),
					width:120,
					sortable:true,
					dataIndex:"qid"
				},{
					text:Qna.getText("admin/list/columns/title"),
					minWidth:200,
					flex:1,
					sortable:true,
					dataIndex:"title"
				},{
					text:Qna.getText("admin/list/columns/label"),
					width:80,
					align:"right",
					dataIndex:"label",
					renderer:function(value,p) {
						if (value == 0) {
							p.style = "text-align:center;";
							return "-";
						}
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Qna.getText("admin/list/columns/question"),
					width:80,
					align:"right",
					dataIndex:"question",
					sortable:true,
					renderer:function(value,p) {
						if (value == 0) {
							p.style = "text-align:center;";
							return "-";
						}
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Qna.getText("admin/list/columns/latest_question"),
					width:130,
					align:"center",
					dataIndex:"latest_question",
					sortable:true,
					renderer:function(value) {
						return value > 0 ? moment(value * 1000).format("YYYY-MM-DD HH:mm") : "-";
					}
				},{
					text:Qna.getText("admin/list/columns/answer"),
					width:80,
					align:"right",
					dataIndex:"answer",
					sortable:true,
					renderer:function(value,p) {
						if (value == 0) {
							p.style = "text-align:center;";
							return "-";
						}
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Qna.getText("admin/list/columns/latest_answer"),
					width:130,
					align:"center",
					dataIndex:"latest_answer",
					sortable:true,
					renderer:function(value) {
						return value > 0 ? moment(value * 1000).format("YYYY-MM-DD HH:mm") : "-";
					}
				},{
					text:Qna.getText("admin/list/columns/ment"),
					width:80,
					align:"right",
					dataIndex:"ment",
					sortable:true,
					renderer:function(value,p) {
						if (value == 0) {
							p.style = "text-align:center;";
							return "-";
						}
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Qna.getText("admin/list/columns/latest_ment"),
					width:130,
					align:"center",
					dataIndex:"latest_ment",
					sortable:true,
					renderer:function(value) {
						return value > 0 ? moment(value * 1000).format("YYYY-MM-DD HH:mm") : "-";
					}
				},{
					text:Qna.getText("admin/list/columns/file"),
					width:80,
					align:"right",
					dataIndex:"file",
					renderer:function(value,p) {
						if (value == 0) {
							p.style = "text-align:center;";
							return "-";
						}
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Qna.getText("admin/list/columns/file_size"),
					width:100,
					align:"right",
					dataIndex:"file_size",
					renderer:function(value) {
						return iModule.getFileSize(value);
					}
				}],
				selModel:new Ext.selection.CheckboxModel(),
				bbar:new Ext.PagingToolbar({
					store:null,
					displayInfo:false,
					items:[
						"->",
						{xtype:"tbtext",text:"항목 더블클릭 : 게시판보기 / 항목 우클릭 : 상세메뉴"}
					],
					listeners:{
						beforerender:function(tool) {
							tool.bindStore(Ext.getCmp("ModuleQnaList").getStore());
						}
					}
				}),
				listeners:{
					itemdblclick:function(grid,record) {
						Qna.list.view(record.data.qid,record.data.title);
					},
					itemcontextmenu:function(grid,record,item,index,e) {
						var menu = new Ext.menu.Menu();
						
						menu.add('<div class="x-menu-title">'+record.data.title+'</div>');
						
						menu.add({
							iconCls:"xi xi-form",
							text:"문의게시판 수정",
							handler:function() {
								Qna.list.add(record.data.qid);
							}
						});
						
						menu.add({
							iconCls:"mi mi-trash",
							text:"문의게시판 삭제",
							handler:function() {
								Qna.list.delete();
							}
						});
						
						e.stopEvent();
						menu.showAt(e.getXY());
					}
				}
			})
		]
	})
); });
</script>