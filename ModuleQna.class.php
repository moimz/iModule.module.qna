<?php
/**
 * 이 파일은 iModule 문의게시판모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 문의게시판과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/qna/ModuleQna.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 17.
 */
class ModuleQna {
	/**
	 * iModule 및 Module 코어클래스
	 */
	private $IM;
	private $Module;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private object $DB DB접속객체
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @private $qnas 문의게시판설정정보
	 */
	private $qnas = array();
	private $categories = array();
	private $prefixes = array();
	private $posts = array();
	private $ments = array();
	
	/**
	 * 기본 URL (다른 모듈에서 호출되었을 경우에 사용된다.)
	 */
	private $baseUrl = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param Module $Module Module 코어클래스
	 * @see /classes/iModule.class.php
	 * @see /classes/Module.class.php
	 */
	function __construct($IM,$Module) {
		/**
		 * iModule 및 Module 코어 선언
		 */
		$this->IM = $IM;
		$this->Module = $Module;
		
		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see 모듈폴더의 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->attachment = 'qna_attachment_table';
		$this->table->qna = 'qna_table';
		$this->table->post = 'qna_post_table';
		$this->table->label = 'qna_label_table';
		$this->table->post_label = 'qna_post_label_table';
		$this->table->ment = 'qna_ment_table';
	}
	
	/**
	 * 모듈 코어 클래스를 반환한다.
	 * 현재 모듈의 각종 설정값이나 모듈의 package.json 설정값을 모듈 코어 클래스를 통해 확인할 수 있다.
	 *
	 * @return Module $Module
	 */
	function getModule() {
		return $this->Module;
	}
	
	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getModule()->getInstalled()->database);
		return $this->DB;
	}
	
	/**
	 * 모듈에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * URL 을 가져온다.
	 *
	 * @param string $view
	 * @param string $idx
	 * @return string $url
	 */
	function getUrl($view=null,$idx=null) {
		$url = $this->baseUrl ? $this->baseUrl : $this->IM->getUrl(null,null,false);
		
		$view = $view === null ? $this->getView($this->baseUrl) : $view;
		if ($view == null || $view == false) return $url;
		$url.= '/'.$view;
		
		$idx = $idx === null ? $this->getIdx($this->baseUrl) : $idx;
		if ($idx == null || $idx == false) return $url;
		
		return $url.'/'.$idx;
	}
	
	/**
	 * view 값을 가져온다.
	 *
	 * @return string $view
	 */
	function getView() {
		return $this->IM->getView($this->baseUrl);
	}
	
	/**
	 * idx 값을 가져온다.
	 *
	 * @return string $idx
	 */
	function getIdx() {
		return $this->IM->getIdx($this->baseUrl);
	}
	
	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $protocol API 호출 프로토콜 (get, post, put, delete)
	 * @param string $api API명
	 * @param any $idx API 호출대상 고유값
	 * @param object $params API 호출시 전달된 파라메터
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($protocol,$api,$idx=null,$params=null) {
		$data = new stdClass();
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeGetApi',$this->getModule()->getName(),$api,$values);
		
		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterGetApi',$this->getModule()->getName(),$api,$values,$data);
		
		return $data;
	}
	
	/**
	 * [사이트관리자] 모듈 관리자패널 구성한다.
	 *
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this;
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/index.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * [사이트관리자] 모듈의 전체 컨텍스트 목록을 반환한다.
	 *
	 * @return object $lists 전체 컨텍스트 목록
	 */
	function getContexts() {
		$lists = $this->db()->select($this->table->qna,'qid,title')->get();
		
		for ($i=0,$loop=count($lists);$i<$loop;$i++) {
			$lists[$i] = array('context'=>$lists[$i]->qid,'title'=>$lists[$i]->title);
		}
		
		return $lists;
	}
	
	/**
	 * 특정 컨텍스트에 대한 제목을 반환한다.
	 *
	 * @param string $context 컨텍스트명
	 * @return string $title 컨텍스트 제목
	 */
	function getContextTitle($context) {
		$qna = $this->getQna($context);
		if ($qna == null) return '삭제된 문의게시판';
		return $qna->title.'('.$qna->qid.')';
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 환경설정을 구성한다.
	 *
	 * @param object $site 설정대상 사이트
	 * @param object $values 설정값
	 * @param string $context 설정대상 컨텍스트명
	 * @return object[] $configs 환경설정
	 */
	function getContextConfigs($site,$values,$context) {
		$configs = array();
		
		$templet = new stdClass();
		$templet->title = $this->IM->getText('text/templet');
		$templet->name = 'templet';
		$templet->type = 'templet';
		$templet->target = 'qna';
		$templet->use_default = true;
		$templet->value = $values != null && isset($values->templet) == true ? $values->templet : '#';
		$configs[] = $templet;
		
		$templet = new stdClass();
		$templet->title = '첨부파일 템플릿';
		$templet->name = 'attachment';
		$templet->type = 'templet';
		$templet->target = 'attachment';
		$templet->use_default = true;
		$templet->value = $values != null && isset($values->attachment) == true ? $values->attachment : '#';
		$configs[] = $templet;
		
		return $configs;
	}
	
	/**
	 * 사이트맵에 나타날 뱃지데이터를 생성한다.
	 *
	 * @param string $context 컨텍스트종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return object $badge 뱃지데이터 ($badge->count : 뱃지숫자, $badge->latest : 뱃지업데이트 시각(UNIXTIME), $badge->text : 뱃지텍스트)
	 * @todo check count information
	 */
	function getContextBadge($context,$config) {
		/**
		 * null 일 경우 뱃지를 표시하지 않는다.
		 */
		return null;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (is_file($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language && is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		$this->IM->fireEvent('afterGetText',$this->getModule()->getName(),$code,$returnString);
		
		/**
		 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		$error->type = 'BACK';
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 템플릿 정보를 가져온다.
	 *
	 * @param string $this->getTemplet($configs) 템플릿명
	 * @return string $package 템플릿 정보
	 */
	function getTemplet($templet=null) {
		$templet = $templet == null ? '#' : $templet;
		
		/**
		 * 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정일 경우
		 */
		if (is_object($templet) == true) {
			$templet_configs = $templet !== null && isset($templet->templet_configs) == true ? $templet->templet_configs : null;
			$templet = $templet !== null && isset($templet->templet) == true ? $templet->templet : '#';
		} else {
			$templet_configs = null;
		}
		
		/**
		 * 템플릿명이 # 이면 모듈 기본설정에 설정된 템플릿을 사용한다.
		 */
		if ($templet == '#') {
			$templet = $this->getModule()->getConfig('templet');
			$templet_configs = $this->getModule()->getConfig('templet_configs');
		}
		
		return $this->getModule()->getTemplet($templet,$templet_configs);
	}
	
	/**
	 * 모듈 외부컨테이너를 가져온다.
	 *
	 * @param string $container 컨테이너명
	 * @return string $html 컨텍스트 HTML
	 */
	function getContainer($container) {
		$html = $this->getContext($container);
		
		$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/container.css');
		
		$this->IM->removeTemplet();
		$footer = $this->IM->getFooter();
		$header = $this->IM->getHeader();
		
		return $header.$html.$footer;
	}
	
	/**
	 * 페이지 컨텍스트를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext($qid,$configs=null) {
		/**
		 * 모듈 기본 스타일 및 자바스크립트
		 */
		$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/style.css');
		$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/script.js');
		
		$values = new stdClass();
		
		if ($configs != null && isset($configs->baseUrl) == true) $this->baseUrl = $configs->baseUrl;
		
		$view = $this->getView() == null ? 'list' : $this->getView();
		
		$qna = $this->getQna($qid);
		if ($qna == null) return $this->getError('NOT_FOUND_PAGE');
		
		if ($configs == null) $configs = new stdClass();
		if (isset($configs->templet) == false) $configs->templet = '#';
		if ($configs->templet == '#') {
			$configs->templet = $qna->templet;
			$configs->templet_configs = $qna->templet_configs;
		} else {
			$configs->templet_configs = isset($configs->templet_configs) == true ? $configs->templet_configs : null;
		}

		$html = PHP_EOL.'<!-- QNA MODULE -->'.PHP_EOL.'<div data-role="context" data-type="module" data-module="qna" data-base-url="'.($configs == null || isset($configs->baseUrl) == false ? '' : $configs->baseUrl).'" data-qid="'.$qid.'" data-view="'.$view.'" data-configs="'.GetString(json_encode($configs),'input').'">'.PHP_EOL;
		$html.= $this->getHeader($qid,$configs);
		
		switch ($view) {
			case 'list' :
				$html.= $this->getListContext($qid,$configs);
				break;
			
			case 'view' :
				$html.= $this->getViewContext($qid,$configs);
				break;
			
			case 'write' :
				$html.= $this->getWriteContext($qid,$configs);
				break;
		}
		
		$html.= $this->getFooter($qid,$configs);
		
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html.= PHP_EOL.'</div>'.PHP_EOL.'<!--// QNA MODULE -->'.PHP_EOL;
		
		return $html;
	}
	
	/**
	 * 컨텍스트 헤더를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getHeader($qid,$configs=null) {
		$view = $this->getView() ? $this->getView() : 'list';
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getHeader(get_defined_vars());
	}
	
	/**
	 * 컨텍스트 푸터를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getFooter($qid,$configs=null) {
		$view = $this->getView() ? $this->getView() : 'list';
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getFooter(get_defined_vars());
	}
	
	/**
	 * 에러메세지를 반환한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @return $html 에러메세지 HTML
	 */
	function getError($code,$value=null) {
		/**
		 * iModule 코어를 통해 에러메세지를 구성한다.
		 */
		$error = $this->getErrorText($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 게시물 목록 컨텍스트를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getListContext($qid,$configs=null) {
		if ($this->checkPermission($qid,'list') == false) return $this->getError('FORBIDDEN');
		$this->IM->addHeadResource('meta',array('name'=>'robots','content'=>'noidex,follow'));
		
		$qna = $this->getQna($qid);
		if ($qna == null) return $this->getError('NOT_FOUND_PAGE');
		
		$listType = $this->getView() ? $this->getView() : 'list';
		$idxes = $this->getIdx() ? explode('/',$this->getIdx()) : array(1);
		$label = null;
		if (count($idxes) == 2) list($label,$p) = $idxes;
		elseif (count($idxes) == 1) list($p) = $idxes;
		
		$limit = $qna->post_limit;
		$start = ($p - 1) * $limit;
		
		$sort = Request('sort') ? Request('sort') : 'idx';
		$dir = Request('dir') ? Request('dir') : (in_array($sort,array('idx')) == true ? 'desc' : 'asc');
		
		if ($qna->use_label == 'NONE') {
			$labels = array();
		} else {
			$labels = $this->db()->select($this->table->label)->where('qid',$qid)->orderBy('question','desc')->get();
		}
		
		$notice = $this->db()->select($this->table->post)->where('qid',$qid)->where('type','NOTICE')->count();
		
		if ($qna->view_notice_count == 'INCLUDE') {
			if ($qna->view_notice_page == 'FIRST') {
				if (ceil($notice / $limit) >= $p) {
					$notices = $this->db()->select($this->table->post)->where('qid',$qid)->where('type','NOTICE')->orderBy('reg_date','desc')->limit($start,$limit)->get();
					$start = 0;
					$limit = $limit - count($notices);
				} else {
					$notices = array();
					$start = $start - $notice;
				}
				
				$lists = $this->db()->select($this->table->post.' p','p.*')->where('p.qid',$qid)->where('p.type','QUESTION');
			} elseif ($qna->view_notice_page == 'ALL') {
				$notices = $this->db()->select($this->table->post)->where('qid',$qid)->where('type','NOTICE')->orderBy('reg_date','desc')->limit(0,$limit)->get();
				
				$start = ($p - 1) * ($limit - count($notices));
				$limit = $limit - count($notices);
				
				$lists = $this->db()->select($this->table->post.' p','p.*')->where('p.qid',$qid)->where('p.type','QUESTION');
			}
		} else {
			if ($p == 1 || $qna->view_notice_page == 'ALL') {
				$notices = $this->db()->select($this->table->post)->where('qid',$qid)->where('type','NOTICE')->orderBy('reg_date','desc')->limit($start,$limit)->get();
			} else {
				$notices = array();
			}
			$lists = $this->db()->select($this->table->post.' p','p.*')->where('p.qid',$qid)->where('p.type','QUESTION');
		}
		
		if ($label != null && $label != 0) {
			$lists->join($this->table->post_label.' l','l.idx=p.idx','LEFT')->where('l.label',$label);
		}
		
		$keyword = Request('keyword');
		if ($keyword) {
			$lists = $this->IM->getModule('keyword')->getWhere($lists,array('p.title','p.search'),$keyword);
			$this->IM->getModule('keyword')->mark($keyword,'div[data-module=qna] span[data-role=title]');
		}
		$total = $lists->copy()->count();
		
		$idx = 0;
		if ($configs != null && isset($configs->idx) == true) {
			$idx = $configs->idx;
		}
		
		$lists = $lists->orderBy('p.'.$sort,$dir)->limit($start,$limit)->get();
		
		for ($i=0, $loop=count($notices);$i<$loop;$i++) {
			$notices[$i] = $this->getPost($notices[$i]);
			$notices[$i]->link = $this->getUrl('view',$notices[$i]->idx).$this->IM->getQueryString().($notices[$i]->is_secret == true ? '#secret-'.$notices[$i]->idx : '');
		}
		
		$loopnum = $total - $start;
		for ($i=0, $loop=count($lists);$i<$loop;$i++) {
			$lists[$i] = $this->getPost($lists[$i]);
			$lists[$i]->link = $this->getUrl('view',$lists[$i]->idx).$this->IM->getQueryString().($lists[$i]->is_secret == true ? '#secret-'.$lists[$i]->idx : '');
			
			if ($keyword) {
				$lists[$i]->title = '<span data-role="title">'.$lists[$i]->title.'</span>';
			}
		}
		
		$pagination = $this->getTemplet($configs)->getPagination($p,ceil(($total + $notice)/$qna->post_limit),$qna->page_limit,$this->getUrl('list',($label == null ? '' : $label.'/').'{PAGE}'),$qna->page_type);
		
		$link = new stdClass();
		$link->list = $this->getUrl('list',($label == null ? '' : $label.'/').$p);
		$link->write = $this->getUrl('write',false);
		
		$permission = new stdClass();
		$permission->write = $this->checkPermission($qna->qid,'question_write');
		
		$header = PHP_EOL.'<form id="ModuleQnaListForm">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Qna.list.init("ModuleQnaListForm");</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('list',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 게시물 보기 컨텍스트를 가져온다.
	 *
	 * @param string $qid 게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getViewContext($qid,$configs=null) {
		if ($this->checkPermission($qid,'view') == false) return $this->getError('FORBIDDEN');
		$this->IM->addHeadResource('meta',array('name'=>'robots','content'=>'idx,nofollow'));
		
		$qna = $this->getQna($qid);
		$idxes = $this->getIdx() ? explode('/',$this->getIdx()) : array();
		$idx = $idxes[0];
		$post = $this->getPost($idx);
		
		if ($post == null) return $this->getError('NOT_FOUND_PAGE');
		if ($this->checkPermission($qid,'view') == false && $post->midx != $this->IM->getModule('member')->getLogged()) return $this->getError('FORBIDDEN');
		if ($post->is_secret == true && $this->checkPermission($qid,'question_secret') == false && $post->midx != $this->IM->getModule('member')->getLogged()) {
			return $this->getError('FORBIDDEN');
		}
		
		if (count($idxes) == 3 && $idxes[1] == 'answer') {
			$answer = $this->db()->select($this->table->post)->where('idx',$idxes[2])->getOne();
			if ($answer == null || $answer->parent != $idx) return $this->getError('NOT_FOUND');
			
			if ($qna->use_protection == true && $answer->is_adopted == 'TRUE' && $this->checkPermission($answer->qid,'answer_modify') == false) return $this->getError('PROTECTED_ANSWER');
			if ($answer->midx != $this->IM->getModule('member')->getLogged() && $this->checkPermission($answer->qid,'answer_modify') == false) return $this->getError('FORBIDDEN');
		} else {
			$answer = null;
		}
		
		/**
		 * 조회수 증가
		 */
		$readed = is_array(Request('IM_QNA_READED','session')) == true ? Request('IM_QNA_READED','session') : array();
		if (in_array($idx,$readed) == false) {
			$readed[] = $idx;
			$this->db()->update($this->table->post,array('hit'=>$this->db()->inc()))->where('idx',$idx)->execute();
			$post->hit = $post->hit + 1;
			
			$_SESSION['IM_QNA_READED'] = $readed;
		}
		
		/**
		 * 첨부파일
		 */
		$attachments = $this->db()->select($this->table->attachment)->where('type','POST')->where('parent',$idx)->get();
		for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
			$attachments[$i] = $this->IM->getModule('attachment')->getFileInfo($attachments[$i]->idx);
		}
		
		/**
		 * 댓글 컴포넌트를 불러온다.
		 */
		$ment = $answer == null ? $this->getMentComponent($idx,$configs) : '';
		
		/**
		 * 답변 컴포넌트를 불러온다.
		 */
		$answer = $answer == null ? $this->getAnswerComponent($idx,$configs) : $this->getAnswerComponent($answer,$configs);
		
		/**
		 * 현재 게시물이 속한 페이지를 구한다.
		 */
		if ($post->is_notice == true && $qna->view_notice_page == 'FIRST') {
			$p = 1;
		} else {
			$sort = Request('sort') ? Request('sort') : 'idx';
			$dir = Request('dir') ? Request('dir') : 'asc';
			$previous = $this->db()->select($this->table->post.' p','p.*')->where('p.qid',$post->qid)->where('p.type','QUESTION')->where('p.'.$sort,$post->{$sort},$dir == 'desc' ? '<=' : '>=');
			if (Request('keyword')) $this->IM->getModule('keyword')->getWhere($previous,array('title','search'),Request('keyword'));
			$previous = $previous->count();
			
			$notice = $this->db()->select($this->table->post)->where('qid',$post->qid)->where('type','NOTICE')->count();
			
			if ($qna->view_notice_count == 'INCLUDE') {
				if ($qna->view_notice_page == 'FIRST') {
					$p = ceil(($previous + $notice)/$qna->post_limit);
				} elseif ($qna->view_notice_page == 'ALL') {
					$p = ceil($previous/($qna->post_limit - $notice));
				}
			} else {
				$p = ceil($previous/$qna->post_limit);
			}
		}
		
		$link = new stdClass();
		$link->list = $this->getUrl('list',$p);
		if (Request('keyword')) $link->list.= '?keyword='.urlencode(Request('keyword'));
		$link->write = $this->getUrl('write',false);
		
		$permission = new stdClass();
		$permission->modify = $post->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'question_modify') == true;
		$permission->delete = $post->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'question_delete') == true;
		$permission->adopt = $post->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'answer_adopt') == true;
		
		if ($post->is_closed == true) {
			$permission->modify = $this->checkPermission($post->qid,'question_modify');
			$permission->delete = $this->checkPermission($post->qid,'question_delete');
			$permission->adopt = false;
		}
		
		if ($qna->use_protection == true && $post->answer > 0) {
			$permission->modify = $this->checkPermission($post->qid,'question_modify');
			$permission->delete = $this->checkPermission($post->qid,'question_delete');
		}
		
		$keyword = Request('keyword');
		if ($keyword) {
			$post->title = '<span data-role="title">'.$post->title.'</span>';
			$this->IM->getModule('keyword')->mark($keyword,'div[data-module=qna] span[data-role=title], div[data-module=qna] div[data-role=wysiwyg-content]');
		}
		
		$header = PHP_EOL.'<div id="ModuleQnaView" data-idx="'.$idx.'">'.PHP_EOL;
		$footer = PHP_EOL.'</div>'.PHP_EOL.'<script>Qna.view.init("ModuleQnaView");</script>';
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('view',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 게시물 작성 컨텍스트를 가져온다.
	 *
	 * @param string $qid 게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getWriteContext($qid,$configs=null) {
		if ($this->IM->getModule('member')->isLogged() == false) return $this->getError('REQUIRED_LOGIN');
		if ($this->checkPermission($qid,'question_write') == false) return $this->getError('FORBIDDEN');
		
		$this->IM->addHeadResource('meta',array('name'=>'robots','content'=>'noidex,nofollow'));
		
		$qna = $this->getQna($qid);
		$idx = $this->getIdx();
		
		if ($qna->use_label == 'NONE') {
			$labels = array();
		} else {
			$labels = $this->db()->select($this->table->label)->where('qid',$qid)->orderBy('question','desc')->get();
		}
		
		/**
		 * 게시물 수정
		 */
		if ($idx !== null) {
			$post = $this->db()->select($this->table->post)->where('idx',$idx)->getOne();
			
			if ($post == null || $post->type == 'ANSWER') {
				return $this->getError('NOT_FOUND_PAGE');
			}
			
			if ($qna->use_protection == true && $this->checkPermission($post->qid,'question_modify') == false && $this->db()->select($this->table->post)->where('parent',$post->idx)->count() > 0) {
				return $this->getError('PROTECTED_QUESTION');
			}
			
			if ($post->type == 'QUESTION' && $post->is_adopted != 'FALSE' && $this->checkPermission($post->qid,'question_modify') == false) {
				$results->success = false;
				return $this->getError('CLOSED_QUESTION');
			}
			
			if ($this->checkPermission($qid,'question_modify') == false && $post->midx != $this->IM->getModule('member')->getLogged()) {
				return $this->getError('FORBIDDEN');
			}
			
			$post->content = $this->IM->getModule('wysiwyg')->decodeContent($post->content,false);
			$post->labels = $this->db()->select($this->table->post_label)->where('idx',$idx)->get('label');
			
			$post->is_notice = $post->type == 'NOTICE';
			$post->is_secret = $post->is_secret == 'TRUE';
			$post->is_anonymity = $post->is_anonymity == 'TRUE';
		} else {
			if ($qna->use_force_adopt === true && $this->checkPermission($qid,'notice') == false && $this->db()->select($this->table->post)->where('midx',$this->IM->getModule('member')->getLogged())->where('type','QUESTION')->where('is_adopted','FALSE')->count() > 3) {
				return $this->getError('NOT_ADOPTED_PREVIOUS_QUESTION');
			}
			
			$post = null;
		}
		
		$header = PHP_EOL.'<form id="ModuleQnaQuestionForm" data-autosave="true" data-autosave-value="'.$qid.'">'.PHP_EOL;
		$header.= '<input type="hidden" name="qid" value="'.$qid.'">'.PHP_EOL;
		if ($post !== null) $header.= '<input type="hidden" name="idx" value="'.$post->idx.'">'.PHP_EOL;
		
		if ($configs != null && isset($configs->category) == true && $configs->category != 0) {
			$categories = array();
			$header.= '<input type="hidden" name="category" value="'.$configs->category.'">'.PHP_EOL;
		}
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Qna.question.init("ModuleQnaQuestionForm");</script>'.PHP_EOL;
		
		$wysiwyg = $this->IM->getModule('wysiwyg')->setModule('qna')->setName('content')->setRequired(true)->setContent($post == null ? '' : $post->content);
		$uploader = $this->IM->getModule('attachment');
		if ($qna->use_attachment == true) {
			if ($configs == null || isset($configs->attachment) == null || $configs->attachment == '#') {
				$attachment_templet_name = $qna->attachment->templet;
				$attachment_templet_configs = $qna->attachment->templet_configs;
			} else {
				$attachment_templet_name = $configs->attachment;
				$attachment_templet_configs = isset($configs->attachment_configs) == true ? $configs->attachment_configs : null;
			}
			
			if ($attachment_templet_name != '#') {
				$attachment_templet = new stdClass();
				$attachment_templet->templet = $attachment_templet_name;
				$attachment_templet->templet_configs = $attachment_templet_configs;
			} else {
				$attachment_templet = '#';
			}
			
			$uploader = $uploader->setTemplet($attachment_templet)->setModule('qna')->setWysiwyg('content');
			if ($post != null) {
				$uploader->setLoader($this->IM->getProcessUrl('qna','getFiles',array('idx'=>Encoder(json_encode(array('type'=>'POST','idx'=>$post->idx))))));
			}
		} else {
			$uploader = $uploader->disable();
		}
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('write',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 게시물 댓글 컴포넌트
	 *
	 * @param int $parent 댓글을 달린 게시물 번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getMentComponent($parent,$configs) {
		$post = $this->getPost($parent);
		if ($post->type == 'NOTICE') return '';
		
		$qna = $this->getQna($post->qid);
		
		if ($this->checkPermission($qna->qid,'ment_write') == true) {
			$form = $this->getMentWriteComponent($parent,$configs);
		} else {
			if ($post->ment == 0) return '';
			
			$form = '';
		}
		
		$ment = $this->getMentListComponent($parent,$configs);
		
		$total = '<span data-role="count">'.$post->ment.'</span>';
		
		$header = PHP_EOL.'<div data-role="ment" data-parent="'.$parent.'">'.PHP_EOL;
		$footer = PHP_EOL.'</div>'.PHP_EOL;
		$footer.= PHP_EOL.'<script>Qna.ment.init('.$parent.');</script>'.PHP_EOL;
		
		return $this->getTemplet($configs)->getContext('ment',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 댓글 목록 컴포넌트
	 *
	 * @param int $parent 댓글을 달린 게시물 번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getMentListComponent($parent,$configs) {
		$post = $this->getPost($parent);
		$qna = $this->getQna($post->qid);
		
		$total = $this->db()->select($this->table->ment)->where('parent',$parent)->count();
		$previous = $total > $qna->ment_limit;
		$lists = $this->db()->select($this->table->ment)->where('parent',$parent)->orderBy('idx','desc')->limit($qna->ment_limit)->get();
		$lists = array_reverse($lists);
		
		$context = PHP_EOL.'<div data-role="list" data-previous="'.($previous == true ? 'TRUE' : 'FALSE').'">';
		for ($i=0, $loop=count($lists);$i<$loop;$i++) {
			$context.= $this->getMentItemComponent($lists[$i],$configs);
		}
		
		$context.= '</div>'.PHP_EOL;
		
		return $context;
	}
	
	/**
	 * 댓글 보기 컴포넌트
	 *
	 * @param object $ment 댓글정보
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getMentItemComponent($ment,$configs) {
		$ment = $this->getMent($ment);
		$qna = $this->getQna($ment->qid);
		
		if ($ment->is_secret == true) {
			$post = $this->getPost($ment->parent);
			if ($this->checkPermission($ment->qid,'ment_secret') == false && $ment->midx != $this->IM->getModule('member')->getLogged() && $post->midx != $this->IM->getModule('member')->getLogged()) {
				$ment->content = '<div data-secret="TRUE">'.$this->getErrorText('FORBIDDEN_SECRET').'</div>';
			}
		}
		
		$permission = new stdClass();
		$permission->modify = $ment->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($ment->qid,'ment_modify') == true;
		$permission->delete = $ment->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($ment->qid,'ment_delete') == true;
		
		$header = PHP_EOL.'<div data-role="item" data-idx="'.$ment->idx.'" data-parent="'.$ment->parent.'">'.PHP_EOL;
		$footer = PHP_EOL.'</div>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('ment.item',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 댓글 작성 컴포넌트
	 *
	 * @param int $parent 댓글을 작성할 게시물 번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getMentWriteComponent($parent,$configs) {
		$post = $this->getPost($parent);
		$qna = $this->getQna($post->qid);
		
		$header = PHP_EOL.'<form id="ModuleQnaMentForm-'.$parent.'">'.PHP_EOL;
		$header.= '<input type="hidden" name="parent" value="'.$parent.'">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL;
		$footer.= '<script>Qna.ment.init("ModuleQnaMentForm-'.$parent.'");</script>';
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('ment.write',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 답변 컴포넌트
	 *
	 * @param int $parent 질문고유번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getAnswerComponent($parent,$configs) {
		if (is_object($parent) == true) {
			$question = $this->getPost($parent->parent);
			$post = $parent;
		} else {
			$question = $this->getPost($parent);
			$post = null;
		}
		
		if ($question->type == 'NOTICE') return '';
		
		$qna = $this->getQna($question->qid);
		
		if (is_object($parent) == true) {
			$form = $this->getAnswerWriteComponent($post,$configs);
			$answer = '';
		} else {
			if ($this->checkPermission($qna->qid,'answer_write') == true && ($post != null && $post->midx != $this->IM->getModule('member')->getLogged())) {
				$form = $this->getAnswerWriteComponent($parent,$configs);
			} else {
				$form = '';
			}
		
			$answer = $this->getAnswerListComponent($parent,$configs);
		}
		
		$total = '<span data-role="count">'.$question->ment.'</span>';
		
		$header = PHP_EOL.'<div data-role="answer" data-parent="'.$question->idx.'">'.PHP_EOL;
		$footer = PHP_EOL.'</div>'.PHP_EOL;
		$footer.= PHP_EOL.'<script>Qna.answer.init('.$question->idx.');</script>'.PHP_EOL;
		
		return $this->getTemplet($configs)->getContext('answer',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 답변 목록 컴포넌트
	 *
	 * @param int $parent 답변이 달린 질문 번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getAnswerListComponent($parent,$configs) {
		$post = $this->getPost($parent);
		$qna = $this->getQna($post->qid);
		
		$lists = $this->db()->select($this->table->post)->where('parent',$parent)->orderBy('is_adopted','desc')->orderBy('good - bad','desc')->get();
		
		$context = PHP_EOL.'<div data-role="list">';
		for ($i=0, $loop=count($lists);$i<$loop;$i++) {
			$context.= $this->getAnswerItemComponent($lists[$i],$configs);
		}
		
		$context.= '</div>'.PHP_EOL;
		
		return $context;
	}
	
	/**
	 * 답변 보기 컴포넌트
	 *
	 * @param object $answer 답변정보
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getAnswerItemComponent($answer,$configs) {
		$post = $this->getPost($answer);
		$question = $this->getPost($post->parent);
		$qna = $this->getQna($post->qid);
		
		if ($post->is_secret == true) {
			if ($this->checkPermission($post->qid,'answer_secret') == false && $post->midx != $this->IM->getModule('member')->getLogged() && $question->midx != $this->IM->getModule('member')->getLogged()) {
				$post->content = '<div data-secret="TRUE">'.$this->getErrorText('FORBIDDEN_SECRET').'</div>';
			}
		}
		
		/**
		 * 댓글 컴포넌트를 불러온다.
		 */
		$ment = $this->getMentComponent($answer->idx,$configs);
		
		/**
		 * 첨부파일
		 */
		$attachments = $this->db()->select($this->table->attachment)->where('type','POST')->where('parent',$post->idx)->get();
		for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
			$attachments[$i] = $this->IM->getModule('attachment')->getFileInfo($attachments[$i]->idx);
		}
		
		$permission = new stdClass();
		$permission->modify = $post->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'answer_modify') == true;
		$permission->delete = $post->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'answer_delete') == true;
		$permission->adopt = $question->midx == $this->IM->getModule('member')->getLogged() || $this->checkPermission($post->qid,'answer_adopt') == true;
		
		if ($question->is_closed == true) {
			$permission->modify = $this->checkPermission($post->qid,'answer_modify');
			$permission->delete = $this->checkPermission($post->qid,'answer_delete');
			$permission->adopt = false;
		}
		
		if ($qna->use_protection == true && $post->is_adopted == true) {
			$permission->modify = $this->checkPermission($post->qid,'answer_modify');
			$permission->delete = $this->checkPermission($post->qid,'answer_delete');
		}
		
		$header = PHP_EOL.'<div data-role="item" data-idx="'.$post->idx.'">'.PHP_EOL;
		$footer = PHP_EOL.'</div>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('answer.item',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 답변 작성 컴포넌트
	 *
	 * @param int $parent 답변을 작성할 게시물 번호
	 * @param object $configs 설정값
	 * @return string $html
	 */
	function getAnswerWriteComponent($parent,$configs) {
		if (is_object($parent) == true) {
			$post = $parent;
			$parent = $post->parent;
			
			$post->is_secret = $post->is_secret == 'TRUE';
			$post->is_anonymity = $post->is_anonymity == 'TRUE';
		} else {
			$post = null;
		}
		
		$question = $this->getPost($parent);
		
		$qna = $this->getQna($question->qid);
		if ($post == null) {
			if ($question->is_closed == true) return '';
		}
		
		$wysiwyg = $this->IM->getModule('wysiwyg')->setModule('qna')->setName('content')->setRequired(true)->setContent($post == null ? '' : $post->content);
		$uploader = $this->IM->getModule('attachment');
		if ($qna->use_attachment == true) {
			if ($configs == null || isset($configs->attachment) == null || $configs->attachment == '#') {
				$attachment_templet_name = $qna->attachment->templet;
				$attachment_templet_configs = $qna->attachment->templet_configs;
			} else {
				$attachment_templet_name = $configs->attachment;
				$attachment_templet_configs = isset($configs->attachment_configs) == true ? $configs->attachment_configs : null;
			}
			
			if ($attachment_templet_name != '#') {
				$attachment_templet = new stdClass();
				$attachment_templet->templet = $attachment_templet_name;
				$attachment_templet->templet_configs = $attachment_templet_configs;
			} else {
				$attachment_templet = '#';
			}
			
			$uploader = $uploader->setTemplet($attachment_templet)->setModule('qna')->setWysiwyg('content');
			if ($post != null) {
				$uploader->setLoader($this->IM->getProcessUrl('qna','getFiles',array('idx'=>Encoder(json_encode(array('type'=>'POST','idx'=>$post->idx))))));
			}
		} else {
			$uploader = $uploader->disable();
		}
		
		$header = PHP_EOL.'<form id="ModuleQnaAnswerForm">'.PHP_EOL;
		$header.= '<input type="hidden" name="parent" value="'.$parent.'">'.PHP_EOL;
		if ($post) $header.= '<input type="hidden" name="idx" value="'.$post->idx.'">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL;
		$footer.= '<script>Qna.answer.init("ModuleQnaAnswerForm");</script>';
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('answer.write',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 답변채택 모달을 가져온다.
	 *
	 * @param int $idx 게시물고유번호
	 * @return string $html 모달 HTML
	 */
	function getPostAdoptModal($idx) {
		$post = $this->getPost($idx);
		if ($post == null || $post->type == 'NOTICE') return;
		
		$content = '<input type="hidden" name="idx" value="'.$idx.'"><div data-role="message">';
		
		if ($post->type == 'QUESTION') {
			$title = '질문마감';
			$content.= '답변을 채택하지 않고 질문을 마감하시겠습니까?<br>질문마감시 해당 질문에 대하여 더이상 답변을 받을 수 없습니다.';
		} else {
			$title = '답변채택';
			$content.= '선택하신 답변을 채택하시겠습니까?<br>답변 채택시 해당 질문에 대하여 더이상 답변을 받을 수 없습니다.';
		}
		
		$content.= '</div>';
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '확인';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}
	
	/**
	 * 댓글수정 모달을 가져온다.
	 *
	 * @param int $idx 댓글고유번호
	 * @return string $html 모달 HTML
	 */
	function getMentModifyModal($idx) {
		$ment = $this->db()->select($this->table->ment)->where('idx',$idx)->getOne();
		$qna = $this->getQna($ment->qid);
		
		$title = '댓글수정';
		$content = '<input type="hidden" name="idx" value="'.$idx.'">';
		$content.= '<input type="hidden" name="parent" value="'.$ment->parent.'">';
		$content.= '<div data-role="input"><textarea name="content">'.$ment->content.'</textarea></div>';
		
		if ($qna->allow_secret == true || $qna->allow_anonymity == true) {
			$content.= '<div data-role="inputset" class="inline">';
			
			if ($qna->allow_secret == true) $content.= '<div data-role="input"><label><input type="checkbox" name="is_secret"'.($ment->is_secret == 'TRUE' ? ' checked="checked"' : '').'>비밀댓글</label></div>';
			if ($qna->allow_anonymity == true) $content.= '<div data-role="input"><label><input type="checkbox" name="is_anonymity"'.($ment->is_anonymity == 'TRUE' ? ' checked="checked"' : '').'>익명댓글</label></div>';
			$content.= '</div>';
		}
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '확인';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}
	
	/**
	 * 게시물삭제 모달을 가져온다.
	 *
	 * @param int $idx 게시물고유번호
	 * @return string $html 모달 HTML
	 */
	function getPostDeleteModal($idx) {
		$post = $this->db()->select($this->table->post)->where('idx',$idx)->getOne();
		$qna = $this->getQna($post->qid);
		
		$content = '<input type="hidden" name="idx" value="'.$idx.'"><div data-role="message">';
		if ($post->type == 'NOTICE') {
			$title = '공지사항 삭제';
			$content.= '공지사항을 삭제하시겠습니까?';
		}
		
		if ($post->type == 'QUESTION') {
			$title = '질문 삭제';
			$content.= '질문을 삭제하시겠습니까?<br>질문에 등록된 답변도 모두 함께 삭제됩니다.';
		}
		
		if ($post->type == 'ANSWER') {
			$title = '답변 삭제';
			$content.= '답변을 삭제하시겠습니까?';
		}
		$content.= '</div>';
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '삭제하기';
		$button->class = 'danger';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}
	
	/**
	 * 댓글삭제 모달을 가져온다.
	 *
	 * @param int $idx 댓글고유번호
	 * @return string $html 모달 HTML
	 */
	function getMentDeleteModal($idx) {
		$ment = $this->db()->select($this->table->ment)->where('idx',$idx)->getOne();
		$qna = $this->getQna($ment->qid);
		
		$title = '댓글 삭제';
		
		$content = '<input type="hidden" name="idx" value="'.$idx.'">'.PHP_EOL;
		$content.= '<div data-role="message">댓글을 삭제하시겠습니까?</div>';
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '삭제하기';
		$button->class = 'danger';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}
	
	/**
	 * 삭제모달을 가져온다.
	 *
	 * @param string $type post or ment
	 * @param int $idx 게시물/댓글 고유번호
	 * @return string $html 모달 HTML
	 */
	function getDeleteModal($type,$idx) {
		$title = $type == 'post' ? '게시물 삭제' : '댓글 삭제';
		
		$content = '<input type="hidden" name="type" value="'.$type.'">'.PHP_EOL;
		$content.= '<input type="hidden" name="idx" value="'.$idx.'">'.PHP_EOL;
		
		if ($type == 'post') {
			$post = $this->getPost($idx);
			
			if ($this->checkPermission($post->qid,'post_delete') == false && $post->midx != 0 && $post->midx != $this->IM->getModule('member')->getLogged()) return;
			
			$content.= '<div data-role="message">게시물을 삭제하시겠습니까?</div>';
			
			if ($this->checkPermission($post->qid,'post_delete') == false && $post->midx == 0) {
				$content.= '<div data-role="input" data-default="게시물 등록시 입력한 패스워드를 입력하여 주십시오."><input type="password" name="password"></div>';
			}
		} elseif ($type == 'ment') {
			$ment = $this->getMent($idx);
			
			if ($this->checkPermission($ment->qid,'ment_delete') == false && $ment->midx != 0 && $ment->midx != $this->IM->getModule('member')->getLogged()) return;
			
			$content.= '<div data-role="message">댓글을 삭제하시겠습니까?</div>';
			if ($this->checkPermission($ment->qid,'ment_delete') == false && $ment->midx == 0) {
				$content.= '<div data-role="input" data-default="댓글 등록시 입력한 패스워드를 입력하여 주십시오."><input type="password" name="password"></div>';
			}
		}
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '삭제하기';
		$button->class = 'danger';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array(),$buttons);
	}
	
	/**
	 * 문의게시판정보를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @return object $qna
	 */
	function getQna($qid) {
		if (isset($this->qnas[$qid]) == true) return $this->qnas[$qid];
		$qna = $this->db()->select($this->table->qna)->where('qid',$qid)->getOne();
		if ($qna == null) {
			$this->qnas[$qid] = null;
		} else {
			$qna->templet_configs = json_decode($qna->templet_configs);
			
			$attachment = json_decode($qna->attachment);
			unset($qna->attachment);
			$qna->use_attachment = $attachment->attachment;
			if ($qna->use_attachment == true) {
				$qna->attachment = new stdClass();
				$qna->attachment->templet = $attachment->templet;
				$qna->attachment->templet_configs = $attachment->templet_configs;
			}
			
			$qna->allow_secret = $qna->allow_secret == 'TRUE';
			$qna->allow_anonymity = $qna->allow_anonymity == 'TRUE';
			$qna->use_protection = $qna->use_protection == 'TRUE';
			$qna->use_gift = $qna->use_gift == 'TRUE';
			$qna->use_force_adopt = $qna->use_force_adopt == 'TRUE';
			
			$this->qnas[$qid] = $qna;
		}
		
		return $this->qnas[$qid];
	}
	
	/**
	 * 게시물정보를 가져온다.
	 *
	 * @param int $idx 게시물고유번호
	 * @param int $is_link 게시물 링크를 구할지 여부 (기본값 : false)
	 * @return object $post
	 */
	function getPost($idx,$is_link=false) {
		if (empty($idx) == true || (is_numeric($idx) == false && is_object($idx) == false)) return null;
		
		if (is_numeric($idx) == true) {
			if (isset($this->posts[$idx]) == true) return $this->posts[$idx];
			else return $this->getPost($this->db()->select($this->table->post)->where('idx',$idx)->getOne(),$is_link);
		} else {
			$post = $idx;
			if (isset($post->is_rendered) === true && $post->is_rendered === true) return $post;
			
			$post->member = $this->IM->getModule('member')->getMember($post->midx);
			$post->name = $this->IM->getModule('member')->getMemberNickname($post->midx,'Unknown',true);
			$post->photo = $this->IM->getModule('member')->getMemberPhoto($post->midx);
			
			if ($is_link == true) {
				$page = $this->IM->getContextUrl('qna',$post->qid,array(),array(),true);
				$post->link = $page == null ? '#' : $this->IM->getUrl($page->menu,$page->page,'view',$post->idx);
			}
			
			$post->content = $this->IM->getModule('wysiwyg')->decodeContent($post->content);
			
			$post->is_secret = $post->is_secret == 'TRUE';
			$post->is_anonymity = $post->is_anonymity == 'TRUE';
			$post->is_notice = $post->type == 'NOTICE';
			
			if ($post->is_anonymity == true) {
				$post->name = '<span data-module="member" data-role="name">익명-'.strtoupper(substr(base_convert(ip2long($post->ip),10,32),0,6)).'</span>';
				$post->photo = '<i data-module="member" data-role="photo" style="background-image:url('.$this->getModule()->getDir().'/images/icon_'.(ip2long($post->ip) % 2 == 0 ? 'man' : 'woman').'.png);"></i>';
			}
			
			$qna = $this->getQna($post->qid);
			if ($qna->use_label == 'NONE') {
				$post->labels = array();
			} else {
				if ($post->type == 'ANSWER') {
					$post->labels = $this->db()->select($this->table->post_label.' p','l.idx, l.title')->join($this->table->label.' l','l.idx=p.label','LEFT')->where('p.idx',$post->parent)->get();
				} else {
					$post->labels = $this->db()->select($this->table->post_label.' p','l.idx, l.title')->join($this->table->label.' l','l.idx=p.label','LEFT')->where('p.idx',$post->idx)->get();
				}
			}
			
			$post->vote = '<span data-role="vote" data-idx="'.$post->idx.'">'.($post->good - $post->bad).'</span>';
			$post->is_closed = $post->is_adopted != 'FALSE';
			$post->is_adopted = $post->is_adopted == 'TRUE';
			
			$post->is_rendered = true;
			
			$this->posts[$post->idx] = $post;
			return $this->posts[$post->idx];
		}
	}
	
	/**
	 * 댓글정보를 가져온다.
	 *
	 * @param int $idx 댓글 고유번호
	 * @param int $is_link 게시물 링크를 구할지 여부 (기본값 : false)
	 * @return object $ment
	 */
	function getMent($idx,$is_link=false) {
		if (empty($idx) == true || (is_numeric($idx) == false && is_object($idx) == false)) return null;
		
		if (is_numeric($idx) == true) {
			if (isset($this->ments[$idx]) == true) return $this->ments[$idx];
			else return $this->getMent($this->db()->select($this->table->ment)->where('idx',$idx)->getOne());
		} else {
			$ment = $idx;
			if (isset($ment->is_rendered) === true && $ment->is_rendered === true) return $ment;
			
			$ment->member = $this->IM->getModule('member')->getMember($ment->midx);
			$ment->name = $this->IM->getModule('member')->getMemberNickname($ment->midx,'Unknown',true);
			$ment->photo = $this->IM->getModule('member')->getMemberPhoto($ment->midx);
			
			if ($is_link == true) {
//				$page = $this->IM->getContextUrl('qna',$ment->qid,array(),array('category'=>$post->category),true);
//				$post->link = $page == null ? '#' : $this->IM->getUrl($page->menu,$page->page,'view',$post->idx);
			}
			
//			$post->image = $post->image > 0 ? $this->IM->getModule('attachment')->getFileInfo($post->image) : null;
			
			$ment->content = '<div data-role="wysiwyg-content">'.nl2br(GetString($ment->content,'replace')).'</div>';
			
			$ment->is_secret = $ment->is_secret == 'TRUE';
			$ment->is_anonymity = $ment->is_anonymity == 'TRUE';
			$ment->is_rendered = true;
			
			if ($ment->is_anonymity == true) {
				$ment->name = '<span data-module="member" data-role="name">익명-'.strtoupper(substr(base_convert(ip2long($ment->ip),10,32),0,6)).'</span>';
				$ment->photo = '<i data-module="member" data-role="photo" style="background-image:url('.$this->getModule()->getDir().'/images/icon_'.(ip2long($ment->ip) % 2 == 0 ? 'man' : 'woman').'.png);"></i>';
			}
			
			$this->ments[$ment->idx] = $ment;
			return $this->ments[$ment->idx];
		}
	}
	
	/**
	 * 권한을 확인한다.
	 *
	 * @param string $qid 게시판 ID
	 * @param string $type 확인할 권한코드
	 * @return boolean $hasPermssion
	 */
	function checkPermission($qid,$type) {
		if ($this->IM->getModule('member')->isAdmin() == true) return true;
		if (in_array($type,array('question_write')) == true && $this->IM->getModule('member')->isLogged() == false) return false;
		
		
		$qna = $this->getQna($qid);
		$permission = json_decode($qna->permission);
		
		if (isset($permission->{$type}) == false) return false;
		return $this->IM->parsePermissionString($permission->{$type});
	}
	
	/**
	 * 게시판 정보를 업데이트한다.
	 *
	 * @param string $qid 게시판 ID
	 */
	function updateQna($qid) {
		$status = $this->db()->select($this->table->post,'SUM(ment) as ment, MAX(latest_ment) as latest_ment')->where('qid',$qid)->getOne();
		$question = $this->db()->select($this->table->post,'COUNT(*) as total, MAX(reg_date) as latest')->where('qid',$qid)->where('type','QUESTION')->getOne();
		$answer = $this->db()->select($this->table->post,'COUNT(*) as total, MAX(reg_date) as latest')->where('qid',$qid)->where('type','ANSWER')->getOne();
		
		$this->db()->update($this->table->qna,array('question'=>$question->total,'latest_question'=>($question->latest ? $question->latest : 0),'answer'=>$answer->total,'latest_answer'=>($answer->latest ? $answer->latest : 0),'ment'=>$status->ment,'latest_ment'=>($status->latest_ment ? $status->latest_ment : 0)))->where('qid',$qid)->execute();
	}
	
	/**
	 * 게시물 정보를 업데이트한다.
	 *
	 * @param int $idx 게시물고유번호
	 */
	function updatePost($idx) {
		$post = $this->getPost($idx);
		$status = $this->db()->select($this->table->ment,'COUNT(*) as total, MAX(reg_date) as latest')->where('parent',$idx)->getOne();
		$this->db()->update($this->table->post,array('ment'=>$status->total,'latest_ment'=>($status->latest ? $status->latest : 0)))->where('idx',$idx)->execute();
		
		if ($post->type == 'QUESTION') {
			$status = $this->db()->select($this->table->post,'COUNT(*) as total, MAX(reg_date) as latest')->where('parent',$idx)->getOne();
			$this->db()->update($this->table->post,array('answer'=>$status->total,'latest_answer'=>($status->latest ? $status->latest : 0)))->where('idx',$idx)->execute();
			
			if ($post->answer != $status->total) {
				$contents = $post->content;
				
				$answers = $this->db()->select($this->table->post)->where('parent',$idx)->get('content');
				$search = GetString($post->content."\n".implode("\n",$answers),'index');
				
				$this->db()->update($this->table->post,array('search'=>$search))->where('idx',$idx)->execute();
			}
		}
	}
	
	/**
	 * 분류정보를 업데이트한다.
	 *
	 * @param int $label 분류고유번호
	 */
	function updateLabel($label) {
		if ($label == 0) return;
		
		$status = $this->db()->select($this->table->post_label.' l','COUNT(*) as total, MAX(p.reg_date) as latest')->join($this->table->post.' p','p.idx=l.idx','LEFT')->where('l.label',$label)->getOne();
		$this->db()->update($this->table->label,array('question'=>$status->total,'latest_question'=>($status->latest ? $status->latest : 0)))->where('idx',$label)->execute();
	}
	
	/**
	 * 게시물을 삭제한다.
	 *
	 * @param int $idx 게시물고유번호
	 */
	function deletePost($idx,$is_self=true) {
		$post = $this->getPost($idx);
		if ($post == null) return false;
		
		$qna = $this->getQna($post->qid);
		
		/**
		 * 질문글의 경우 게시물 라벨과 질문의 답변을 삭제한다.
		 */
		if ($post->type == 'QUESTION') {
			/**
			 * 라벨을 삭제한다.
			 */
			$labels = $this->db()->select($this->table->post_label)->where('idx',$post->idx)->get();
			foreach ($labels as $label) {
				$this->db()->delete($this->table->post_label)->where('idx',$post->idx)->where('label',$label->label)->execute();
				$this->updateLabel($label->label);
			}
			
			/**
			 * 답변을 삭제한다.
			 */
			$answers = $this->db()->select($this->table->post)->where('parent',$post->idx)->get();
			foreach ($answers as $answer) {
				$this->db()->deletePost($answer->idx,false);
			}
		}
		
		/**
		 * 게시물의 댓글을 삭제한다.
		 */
		$ments = $this->db()->select($this->table->ment)->where('parent',$post->idx)->get();
		foreach ($ments as $ment) {
			$this->deleteMent($ment->idx,false);
		}
		
		/**
		 * 알림메세지를 삭제한다.
		 */
		$this->IM->getModule('push')->deletePush($this->getModule()->getName(),$post->type,$post->idx);
		
		/**
		 * 게시물에 첨부된 첨부파일을 삭제한다.
		 */
		$attachments = $this->db()->select($this->table->attachment)->where('type','POST')->where('parent',$idx)->get();
		for ($i=0, $loop=count($attachments);$i<$loop;$i++) {
			$this->IM->getModule('attachment')->fileDelete($attachments[$i]->idx);
		}
		
		/**
		 * 게시물을 삭제한다.
		 */
		$this->db()->delete($this->table->post)->where('idx',$idx)->execute();
		
		if ($is_self == true) {
			/**
			 * 게시물작성자와 삭제자가 다른 경우 댓글작성자에게 알림메세지를 전송한다.
			 */
			if ($post->midx != $this->IM->getModule('member')->getLogged()) {
				$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),$post->type,$idx,'DELETE',array('from'=>$this->IM->getModule('member')->getLogged(),'title'=>$post->title));
			}
			
			/**
			 * 포인트 및 활동내역을 기록한다.
			 */
			if ($post->type == 'ANSWER') {
				$this->IM->getModule('member')->sendPoint($post->midx,$qna->answer_point * -1,$this->getModule()->getName(),'DELETE_ANSWER',array('title'=>$post->title));
				$this->IM->getModule('member')->addActivity($post->midx,$qna->answer_exp * -1,$this->getModule()->getName(),'DELETE_ANSWER',array('title'=>$post->title));
			} else {
				$this->IM->getModule('member')->sendPoint($post->midx,$qna->question_point * -1,$this->getModule()->getName(),'DELETE_'.$post->type,array('title'=>$post->title));
				$this->IM->getModule('member')->addActivity($post->midx,$qna->question_exp * -1,$this->getModule()->getName(),'DELETE_'.$post->type,array('title'=>$post->title));
			}
			
			if ($post->type == 'ANSWER') $this->updatePost($post->parent);
			$this->updateQna($post->qid);
		}
		
		return true;
	}
	
	/**
	 * 댓글을 삭제한다.
	 *
	 * @param int $idx 댓글고유번호
	 * @return boolean $success
	 */
	function deleteMent($idx,$is_self=true) {
		$ment = $this->getMent($idx);
		if ($ment == null) return false;
		
		$post = $this->getPost($ment->parent);
		if ($post == null) return false;
		
		$qna = $this->getQna($ment->qid);
		
		$this->db()->delete($this->table->ment)->where('idx',$ment->idx)->execute();
		
		/**
		 * 새 댓글 작성 알림메세지를 취소한다.
		 */
		$this->IM->getModule('push')->cancelPush($post->midx,$this->getModule()->getName(),$post->type,$post->idx,'NEW_MENT',array('idx'=>$idx));
		
		if ($is_self == true) {
			/**
			 * 댓글작성자와 삭제자가 다른 경우 댓글작성자에게 알림메세지를 전송한다.
			 */
			if ($ment->midx != $this->IM->getModule('member')->getLogged()) {
				$this->IM->getModule('push')->sendPush($post->midx,$this->getModule()->getName(),'MENT',$idx,'DELETE_MENT',array('from'=>$this->IM->getModule('member')->getLogged(),'parent'=>$ment->parent));
			}
			
			/**
			 * 포인트 및 활동내역을 기록한다.
			 */
			$this->IM->getModule('member')->sendPoint($ment->midx,$qna->ment_point * -1,$this->getModule()->getName(),'DELETE_MENT',array('parent'=>$ment->parent));
			$this->IM->getModule('member')->addActivity($ment->midx,$qna->ment_exp * -1,$this->getModule()->getName(),'DELETE_MENT',array('parent'=>$ment->parent));
		
			$this->updatePost($ment->parent);
			$this->updateQna($ment->qid);
		}
		
		return true;
	}
	
	/**
	 * 현재 모듈에서 처리해야하는 요청이 들어왔을 경우 처리하여 결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 * 작업코드가 '@' 로 시작할 경우 사이트관리자를 위한 작업으로 최고관리자 권한이 필요하다.
	 *
	 * @param string $action 작업코드
	 * @return object $results 수행결과
	 * @see /process/index.php
	 */
	function doProcess($action) {
		$results = new stdClass();
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeDoProcess',$this->getModule()->getName(),$action,$values);
		
		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/process/'.$action.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterDoProcess',$this->getModule()->getName(),$action,$values,$results);
		
		return $results;
	}
	
	/**
	 * 첨부파일을 동기화한다.
	 *
	 * @param string $action 동기화작업
	 * @param int $idx 파일 고유번호
	 */
	function syncAttachment($action,$idx) {
		/**
		 * 첨부파일 삭제
		 */
		if ($action == 'delete') {
			$this->db()->delete($this->table->attachment)->where('idx',$idx)->execute();
		}
	}
	
	/**
	 * 회원모듈과 동기화한다.
	 *
	 * @param string $action 동기화작업
	 * @param any[] $data 정보
	 */
	function syncMember($action,$data) {
		if ($action == 'point_history') {
			switch ($data->code) {
				case 'post' :
					$idx = $data->content->idx;
					$post = $this->getPost($idx,true);
					
					if ($post == null) {
						return '[삭제된 게시물] 게시물 작성';
					} else {
						return '<a href="'.$post->link.'" target="_blank">['.$post->title.']</a> 게시물 작성';
					}
					break;
			}
			
			return json_encode($data);
		}
	}
}
?>