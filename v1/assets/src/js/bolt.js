
// define our bolt instance 
// we'll init YUI below
// new yui
YUI.add('bolt',function(Y){

	// shortcuts
	var $ = Y.one, $j = Y.JSON;

	// check for browser 			
	if ( /opera/.test(navigator.userAgent.toLowerCase()) ) { $(document.body).addClass('opera'); }
	if ( /firefox/.test(navigator.userAgent.toLowerCase()) ) { $(document.body).addClass('firefox'); }	
	if ( /chrome/.test(navigator.userAgent.toLowerCase()) ){ $(document.body).addClass('chrome'); }
	if ( /safari/.test(navigator.userAgent.toLowerCase()) ) { $(document.body).addClass('safari'); } 		
	if ( /msie/.test(navigator.userAgent.toLowerCase()) ) { $(document.body).addClass('ie'); }

	// load images
	var img = new Y.ImgLoadGroup({ timeLimit: 2, foldDistance: 30 }); img.set('className', 'defer');

	// base 
	BLT.Base = function() {
		this.init();
	}

	// base prototype
	BLT.Base.prototype = {
		
		// args
		store : { 'actionCache': {} },
		cl : {},
		
		// preventIterceptAction
		preventIterceptAction : false,
		
		// shortcuts
		$ : Y.Node.one,
		$$ : Y.Node.all,		
		Y: Y,
		
		// fbpersm
		fbPerms : "read_stream,publish_stream,offline_access,user_about_me,email,user_location,friends_location,user_events,friends_events",
		
		// init 
		init : function() {					
		
			// attach some stuff
			$('#doc').on('click',this.click,this);
			$('#doc').on('mouseover',this.mouse,this);			
			$('#doc').on('mouseout',this.mouse,this);
			$('#doc').on('keyup',this.keyup,this);
			
			// beed to check form tags to see if 
			// they should open in a panel
			$('#doc').all('form.open-panel').each(function(el){
				
				// get attr
				var action = el.getAttribute('action');
				
				// reset it 
				el.setAttribute('x-action', self.getUrl(action,{'.context':'xhr'}));
				el.setAttribute('action','#');
				el.setAttribute('method','get');													
											
				// attach to submit
				el.on('submit',function(e){	
				
					// halt what the browser wants to do
					e.halt(); 
							
					// get target
					var tar = e.target;
					
					// has class
					if ( tar.hasClass('loading') ) {
						return;
					}
					
					// loading
					tar.addClass('loading');
					
					// get the action
					var url = tar.getAttribute('x-action');
					
					// get the form
					BLT.Obj.panel.load(url,{'form':tar,'openAfter':true});
		
					// remove
					tar.removeClass('loading');
					
					
				},this);				
			
			});
						
			// self
			var self = this;		
			
			// events
			this.publish('blt-base:docclick');			
			this.publish('blt-base:mouse');
			this.publish('blt-base:tblcheckboxclick');
			this.publish('blt-base:resize');			
			this.publish('blt-base:actionloaded');
			this.publish('blt-base:actionpreload');			
			
			// fb events			
			this.publish('blt-base:fb-init');
			this.publish('blt-base:fb-not-con');
			this.publish('blt-base:fb-con');
			
			// som css for title bubble
			var css = '#title-bubble { background: #333; position: absolute; top: -999em; left: -999em; max-width: 150px; text-align:center; ' +
					  '  color: #fff; padding: 5px; font-size: 11px; opacity: .8; -moz-border-radius: 5px; -webkit-border-radius: 5px; } '; 		
					  	
			// add some css to head
			$('head').append("<style type='text/css'>"+css+"</style>");

		},
		
		fbInit : function () {
	
			// need fbroot-
			if ( !$('#fb-root') ) {
				$(document.body).append("<div id='fb-root'></div>");
			}
	
			// load facebook
			Y.Get.script(document.location.protocol + '//connect.facebook.net/en_US/all.js',{
				'insertBefore': 'fb-root',
				'scope': BLT.Obj,
				'onSuccess': function(){ 
	
					// yes to the fb
					BLT.Env.fb = true;			
					
					// self
					var self = this;
					
					// init
					FB.init({appId: BLT.Env.fbApiKey, status: false, cookie: true, xfbml: true});
													
				}
				
			});	
		
		
		},		
		
		fbLogin : function(func) {
				
			var _func = func;	
				
				// login	
				FB.login(function(response) {
				  if (response.session) {
				    if (response.perms) {
						_func();
				    } 
				    else {
						alert("You must grant proper permissions")
				    }
				  } else {
				    alert("Error with facebook login");
				  }
				}, {perms:this.fbPerms});			
		
		},
		
		// load css
		loadCss : function(url) {
			Y.Get.css(url);
		},
		
		loadJs : function(url,args) {		
			Y.Get.script(url,args);
		},
	
		// click
		click : function(e) {
		
			this.fire('blt-base:docclick',e);
		
			// target
			var tar = otar = e.target;
			
			 // ! open a panel
			if ( otar.hasAttribute('rel') && otar.getAttribute('rel') == 'more' ) {
				e.halt(); this.showMore(otar);
			}			 			
			else if (  tar.hasClass('fb-login') ) {

				// stop
				e.halt();
				
				// fb login
				this.fbLogin(function(){
					window.location.href = otar.getAttribute('href');
				});			

			}
			else if ( (tar = this.getParent(otar, {'tag':'a'}, 5)) ) {
				
				// rel
				var rel = tar.getAttribute('rel');
				
				// xhr or ajax
				if ( rel in {'panel':1, 'xhr':1, 'ajax':1}  ) {
					this.interceptAction(tar, rel, e);
				}
				
			}
			else if ( otar.get('tagName') == 'BUTTON' ) {
			
				// get the form
				var form = this.getParent(otar, {'tag':'form'});
			
				// rel
				if ( form.getAttribute('rel') == 'xhr' ) {
										
					// stop			
					e.halt();
					
					// fire
					this.interceptAction(form, 'xhr', e);
					
				}	
					
			}
			
			// no tar
			if ( !tar ) { return; }			
		
		},
		
		scrollTo : function(node) {
			
			// if it's a string make it a selector
			if ( typeof node == 'string' ) {
				node = $(node);
			}
					
			var anim = new Y.Anim({
			    node: $(window),
			    duration: 1,
			    easing: Y.Easing.easeOut,
			    to: {
			    	scroll: function() {
						return [0, node.getY()]
			    	}
			    }
			});		
		
			// run it
			anim.run();
		
		},
		
		showMore : function(tar) {
		
			// get the lust
			var list = $(tar.getAttribute('data-list'));
		
			// per click
			var per = tar.getAttribute('data-per') || 99999;
		
			// i
			var i = 1;
			
			// list
			var items = list.all('li.hide');
		
			// list
			items.each(function(el){
				if ( i++ > per ) { return false; }
				el.removeClass('hide');
			});
		
			// if size of list is <= per
			// remove the tar
			if ( items.size() <= per ) {
				tar.remove();
			}
		
		},
		
		interceptAction : function(tar, rel, event) {
			
			// cfg
			var cfg = {};

				// see if there's stuff to pass
				if ( tar.hasAttribute('data-cfg') ) {
					var parts = tar.getAttribute('data-cfg').split('&');
					for ( var p in parts ) {
						cfg[parts[p].split('=')[0]] = parts[p].split('=')[1];
					}
				}
		
		
			// fire
			this.fire('blt-base:preload', tar, cfg);									
		
			// need a panel
			if ( !this.store.panel ) {			
				this.store.panel = new BLT.Class.Panel({modal:true});	
			}
		
			// sometimes we dont' want this
			if ( this.preventIterceptAction === true ) { return; }
		
			// no hash in href we should stop the event
			if ( tar.getAttribute('href').indexOf('#') == -1 ) {
				event.halt();
			}			
			
			// href
			var href = ( tar.get('tagName') == 'FORM' ? tar.getAttribute('action') : tar.getAttribute('href') );								
			
			// args
			var args = {};
			
				// see if there's stuff to pass
				if ( tar.hasAttribute('data-args') ) {
					var parts = tar.getAttribute('data-args').split('&');
					for ( var p in parts ) {
						args[parts[p].split('=')[0]] = parts[p].split('=')[1];
					}
				}
			
			// make our url 
			var url =  this.getUrl(href, args);
		
			// load
			if ( rel == 'xhr' ) {
						
				if ( tar.hasAttribute('data-xhr') ) {
					url = this.getXhrUrl( tar.getAttribute('data-xhr').replace(/\+href/, href.replace(new RegExp("http://"+location.hostname+"/"),'')) );									
				}						
						
				// content
				var content = $(tar.getAttribute('data-target'));
				var replace = false;
			
					// replace ?
					if ( tar.getAttribute('data-target').indexOf('.replace') != -1 ) {
						content = $(tar.getAttribute('data-target').replace(/\.replace/,''));
						replace = true;
					}		
	
				// content caching?
				if ( tar.hasAttribute('data-cache') && tar.getAttribute('data-cache') == 'yes' && (url in this.store.actionCache) ) {
				
					// take wh				
					this.store.actionCache[content.getAttribute('data-cid')] = content.get('innerHTML');
				
					// load in the html
					content.set('innerHTML', this.store.actionCache[url]);	
			
					// what url is at the current content
					content.setAttribute('data-cid', url);
					
					// fire
					this.fire('blt-base:actionloaded', a[0]);												
					
				}
				
				// loading
				content.addClass('loading');				
										
				// o
				var o = {
					'method': 'get',
					'arguments': [tar, url, cfg],
					'context': this,
					'on': {
						'complete': function(id, o, a) {
						
							// tar
							var tar = a[0];
							
							// parse
							var j = $j.parse(o.responseText);
							
							// none 
							if ( !j || j.stat != 1 ) { window.location.href = tar.getAttribute('href'); }
							
							// if we want to cache
							if ( tar.hasAttribute('data-cache') && tar.getAttribute('data-cache') == 'yes' ) {
								this.store.actionCache[content.getAttribute('data-cid')] = content.get('innerHTML');
							}								
							
							// get container
							if ( replace ) {
								content.replace( Y.Node.create(j.html) );
							}
							else {
								content.set('innerHTML', j.html).removeClass('loading');
							}
							
							// what url is at the current content
							content.setAttribute('data-cid', a[1]);
							
							// boot me
							this.bootstrap(j);
							
							// load
							BLT.execute('l');						
							
							// fire
							this.fire('blt-base:actionloaded', a[0], a[2]);							
							
						}			
					}			
				};
				
				// form
				if ( tar.get('tagName')=='FORM' ) {
					
					// method
					o.method = tar.getAttribute('method');
				
					// form
					o.form = { 'id': tar };
				
				}
				
				// all our unload
				B.execute('u');
				
				// io
				Y.io(url,o);
				
			}
			
			// ajxx
			else if ( rel == 'ajax' ) {
					
				if ( tar.hasAttribute('data-xhr')  ) {
					url = this.getAjaxUrl( tar.getAttribute('data-xhr').replace(/\+href/, href.replace(new RegExp("http://"+location.hostname+"/"),'')) );					
				}		
				
				alert('x');
						
				// o
				var o = {
					'method': 'get',
					'arguments': [tar, url, cfg],
					'context': this,
					'on': {
						'complete': function(id, o, a) {
						
							// tar
							var tar = a[0];
							
							// parse
							var j = $j.parse(o.responseText);
							
							// none 
							if ( !j || j.stat != 1 ) { window.location.href = tar.getAttribute('href'); }
							
							// fire
							this.fire('blt-base:actionloaded', a[0], a[2]);														
												
							// check for special actions
							if ( j['do'] ) {
							
								// redirect
								if ( j['do'] == 'redi' ) {
					//				window.location.href = j.url; return;
								}
								else if ( j['do'] == 'refresh' ) {
					//				window.location.href = window.location.href;
								}
							}												
														
							// boot me
							this.bootstrap(j);
							
							// load
							BLT.execute('l');						
							
						}			
					}			
				};
				
				// form
				if ( tar.get('tagName')=='FORM' ) {
					
					// method
					o.method = tar.getAttribute('method');
				
					// form
					o.form = { 'id': tar };
				
				}
				
				// all our unload
				B.execute('u');
				
				// io
				Y.io(url,o);			
			
			} 
			
			// panel
			else if ( rel == 'panel' ) {
				
				// clear
				this.store.panel.obj.set('bodyContent','');
				
				// what to do
				if ( tar.getAttribute("data-src") ) {
					
					// src
					var src = $("#"+tar.getAttribute("data-src"));
				
					// attach
					var ohandle = this.store.panel.on("panel:open",function(){
											
						// append
						if ( tar.getAttribute('data-target').indexOf('.replace') != -1 ) {
							this.obj.set('bodyContent', '');							
						}

						// append
						src.get('children').each(function(el){
							this.obj.get('srcNode').one(".yui3-widget-bd").append(el);
						}, this);
					
						// center
						this.obj.centered();					
						
						// detach
						ohandle.detach();
					
					});
					
					// close
					var chandle = this.store.panel.on("panel:close",function(){

						// append
						this.obj.get('srcNode').one(".yui3-widget-bd").get('children').each(function(el){
							src.append(el);
						}, this);
						
						// detach
						chandle.detach();
					
					});					
				
					// open
					this.store.panel.open();					
				
				}
				else {
				
					if ( tar.hasAttribute('data-xhr')  ) {
						url = this.getXhrUrl( tar.getAttribute('data-xhr').replace(/\+href/, href.replace(new RegExp("http://"+location.hostname+"/"),'')) );					
					}					
						
					// load
					this.store.panel.load(url, {'openAfter':true});
					
				}
			
			}					
		
		},
		
		titleBubble: function (tar, event, type) {
		
		    if ( !$('#title-bubble') ) {				
		        $(document.body).append("<div id='title-bubble'></div>");				        
		    }
		
		    var bubble = $('#title-bubble');		
		
		    if (type == 'mouseout') {
		
		        bubble.setXY([-99, -99]);
		
		        tar.setAttribute('title', tar.getAttribute('xtitle'));
		
		        return;
		
		    }
		
		    var title = tar.getAttribute('title');
		
		    if (!title || title == "" || title == 'null') return;
		
		    bubble.set('innerHTML', title + "<span></span>");
		
		    tar.setAttribute('title', '');
		    tar.setAttribute('xtitle', title);
		
		    // figure the bubble's width 
		    var bReg = bubble.get('region');
		    var tReg = tar.get('region');
		
		    var bw = (bReg.right - bReg.left);
		    var bh = (bReg.bottom - bReg.top);
		
		    var tw = (tReg.right - tReg.left);
		
		    var txy = tar.getXY();
		
		    var x = (txy[0] + tw / 2) - (bw / 2);
		    var y = (txy[1] - (bh + 5));
		    
		    // left
		    if ( tar.hasClass('bubble-left') ) {
		    	x = tar.getX();
		    }
		
		    // set the bubbl's xy 
		    bubble.setXY([x, y]);
		
		},		
		
		// keydown
		keyup : function(e) {
		
			// target
			var tar = oTar = e.target;
			
			 // ! open a panel
			if ( tar.hasClass('edit-slug') ) {
			
				var slug = tar.get('value').replace(/ /g,'-').replace(/'/g,'');
					
				Y.one("#slug-container").set('innerHTML',slug);
				
				// validate slug
				this.validateSlug(slug);	
			
			}
			
			// no tar
			if ( !tar ) { return; }			
		
		},
		
		// mouse
		mouse : function(e,type) {
			
			// target
			var tar = oTar = e.target;
			
			// custom
			this.fire('BLT-base:mouse',e);
		
		},
		
		getUrl : function(url,params) {
        
        	// no http
        	if ( url.indexOf('http') == -1 ) {
        		url = BLT.Env.Urls.base + url;
        	}
        	
			// qp
			var qp = [];
			
				// add 
				for ( var p in params ) {
					qp.push(p+"="+ encodeURIComponent(params[p]) );
				}
        
        	// do it 
        	return url + (url.indexOf('?')==-1?'?':'&') + qp.join('&');
        
        },
		

		getAjaxUrl : function(act,params) {
		
			// reurn
			return this.getUrl( BLT.Env.Urls.base+'ajax/'+act, params);
			
		},
		
		getXhrUrl : function(act,params) {
		
			// reurn
			return this.getUrl( BLT.Env.Urls.base+'xhr/'+act, params);
			
		},
		
		getParent : function(tar,g,max) {
       	       	
			// no tar
			if ( !tar )	{ return false; }
       	       	
       		// max
       		if ( !max ) { max = 10; }
        
            // local
            var gt = g;
           	var i = 0;            
           	var m = max;
            
            if ( typeof g == 'object' ) {
            
            	// current
            	if ( tar.get('tagName') == gt.tag.toUpperCase() ) { return tar; }
            
            	// reutrn
                return tar.ancestor(function(el){
                	if ( i++ > max ) { return false; }
					return (el.get('tagName') == gt.tag.toUpperCase()); }
				);
				
            }
            else {
            
            	// current
            	if ( tar.hasClass(g) ) { return tar; }            
            
            	// moreve
                return tar.ancestor(function(el){ 
                	if ( i++ > max ) { return false; }                
                	return el.hasClass(gt); 
                });
                
            }
        },
        
        
        validateSlug : function(slug) { 
        	
        	// url
			var url = BLT.Obj.getUrl(BLT.Env.Urls.base+'trucks/validate-slug',{'.context':'xhr'});
        	
        	// params
			var params = {
				'method': 'GET',
				'context': this,
				'data': 'slug='+slug,
				'timeout': 10000,
				'on': {
					'failure': function() {
					//	window.location.href = reg_url;
					},
				 	'complete': function(id,o,a) {
						
						// get data
						var json = false;
						
						// try to parse
						//try {
							
							json = $j.parse(o.responseText);
								
							var slugContainer = Y.one("span#slug-result");	
																					
							if (json.validslug == 'good') {
								
								slugContainer.removeClass('bad');
								slugContainer.addClass('good');
								slugContainer.set('innerHTML','Available');
								
							
							} else { 
								
								slugContainer.removeClass('good');
								slugContainer.addClass('bad');
								slugContainer.set('innerHTML','Taken');
								
							}
							
						//}
						//catch (e) {}
						
						// need a good stat
						if ( !json || json.stat != 1 ) {
							return false;						
						}
						
						
					}
				}
			}        	
        	
        	// fire
			Y.io(url,params);
        
        },
        
		displayMap : function(div,address) {
			
			// make our map		
		    this.store.map = new google.maps.Map2(document.getElementById(div));

	    	// get geo code
			var geocoder = new GClientGeocoder();
	    	
	    	// geocode address
	    	geocoder.getLatLng(
	    		address,
	    		function(pt) {	    
					BLT.Obj.store.map.setCenter(pt, 13);
		 			var marker = new GMarker(pt);
  				    BLT.Obj.store.map.addOverlay(marker);
	    		}
	    	);
		
		},
		
		bootstrap : function(json) {
	
			// if bootstrap
			if ( json.bootstrap ) {

				// boot me
				if ( json.bootstrap.js ) {
					for ( var el in json.bootstrap.js ) {
						eval(json.bootstrap.js[el]);
					}
				}
				
			}
		
		}    
        
			
	}

	// we fire some custom events
	Y.augment(BLT.Base, Y.EventTarget);
	
});