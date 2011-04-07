YUI.add("bolt-class-panel",function(Y) {

	// shortcuts
	var $ = Y.one, $j = Y.JSON;

	// base 
	BLT.Class.Panel = function(args) {
		this.init(args);
	}

	// base prototype
	BLT.Class.Panel.prototype = {
		
		// args
		args : {},
		
		// init 
		init : function(args) {
			
			// args
			this.args = args;
		
			// object
			this.obj = new Y.Overlay({
				"centered": true,
				"bodyContent": "",
				"zIndex": 100
			});
			
			// add our master class			
			if ( args && args.type != 'simple' ) {
			
				this.obj.get('boundingBox').append("<div class='back'></div><a class='close-panel'>close</a>");
				this.obj.get('boundingBox').addClass("panel");	
				
				// content
				this.obj.get('contentBox').append("<div class='loading_mask'></div><div class='loading_ind'></div>");
				
			}
			
			// add class
			if ( args && args['class'] ) {
				for ( var c in args['class'] ) {
					this.obj.get('boundingBox').addClass(args['class'][c]);
				}
			}
			
			// render
			this.obj.render("#doc");
			
			// hide
			this.obj.hide();
			
			// click
			this.obj.get('boundingBox').on('click',this.click,this);
			
			// events to publish
			this.publish('panel:click');
			this.publish('panel:open');
			this.publish('panel:close');			
			this.publish('panel:submit');			
			this.publish('panel:beforeload');			
			this.publish('panel:afterload');				
			
			// watch the xy change 
			this.obj.after('xyChange',function(e){
			
				// get the new xy
				var xy = e.newVal;
			
				// if the x is - reset to 10
				if ( xy[1] < 10 ) {		
					this.obj.move([xy[0],20]);
				}
			
			},this);		
		
			// scroll me
			Y.on('scroll',function(){
			
				// center
				this.obj.centered();
			
			},document,this);
		
			// pannel css
			if ( !$('head style#panel-css') ) {
				
				// the css
				var css = " .panel { z-index: 100; padding: 10px;} " +
						  " .panel .back { position: absolute; top: 0; left: 0; height: 100%; width: 100%; background: #333; border-radius:10px; -moz-border-radius: 10px; z-index: 101; opacity: .6; } " +
						  " .panel .yui3-overlay-content { max-width: 900px; min-width: 400px; max-height: 600px; position: relative; z-index: " +
						  "   102; background: #fff; min-height: 200px; background: #fff; overflow: auto;} " +
						  " .panel a.close-panel {  z-index: 105; position:absolute; top: 0px; right: 0px; color: #fff; text-align:right; cursor:pointer; background: #000; display:block; padding: 5px; border-radius: 5px; } " +
						  " .panel:after, .yui3-overlay-content:after, {content:'.';visibility:hidden;clear:left;height:0;display:block}" +
						  " #panel-modal { position: absolute; top: 0; left: 0; background: #888; opacity: .6; width:100%; } ";
			
				// append
				$('head').append("<style type='text/css' id='panel-css'>" + css + "</style>");
			
			}
		
		},
		
		// click
		click : function(e) { 
		
			// tar
			var tar = e.target;
			
			// fire
			this.fire('panel:click',{'target':tar,'event':e});
			
			// close
			if ( tar.hasClass('close-panel') ) {
				this.close();
			}
			
		},
		
		// open
		open : function() {
			
			// if modal
			if ( this.args.modal ) {
				
				if ( !Y.one('#panel-modal') ) {
					Y.one("body").insert("<div id='panel-modal' style='display:none'></div>");
				}			
	
				// open
				if ( !$('#panel-modal').hasClass('open') ) {
		
					// set it 
					$("#panel-modal").setStyles({'opacity':0,'display':'block','height':Y.one(window).get('docHeight')+'px'});
				
					// fade them out
					var a = new Y.Anim({
				        node: $('#panel-modal'),
				        from: {
				        	'opacity': 0
				        },		        
				        to: {
							'opacity': .6
		        		},
						easing: Y.Easing.easeIn,	        		
		        		"duration": (this.args.modalDuration?this.args.modalDuration:.5)
					});				
					
					a.run();
					
					$('#panel-modal').addClass('open');
					
				}
				
			}
			
			// fire
			this.fire('panel:open');
		
			// center
			this.obj.centered();		
		
			// open
			this.obj.show();
			
		},
		
		close : function(args) {
		
			// fire
			this.fire('panel:close',args);	
			
			// if modal
			if ( this.args.modal && $('#panel-modal') ) {		
			
				if ( !$('#panel-modal').hasClass('open') ) { return; }
			
				// fade them out
				var a = new Y.Anim({
			        node: $('#panel-modal'),
			        to: {
						'opacity': 0
	        		},
					easing: Y.Easing.easeOut,	        		
	        		"duration": .2
				});				
			
				a.on("end",function(){
					// set it 
					$("#panel-modal").setStyles({'opacity':0,'display':'none'});								
				});
				
				$('#panel-modal').removeClass('open');
				
				a.run();
			
				
			}				

			// load
			BLT.execute('u');
		
			// hide
			this.obj.hide();
			
		},
	
		// submit
		submit : function(e) { 
			
			// get target
			var tar = e.target;
			
			// has loading
			if ( tar.hasClass('loading') ) {
				return;
			}
			
			// loading
			tar.addClass('loading');
			
			// get the action
			var url = tar.getAttribute('x-action');
						
			// fire
			this.fire('panel:submit');			

			// get the form
			this.load(url,{'form':tar}); 
			
		},
	
		// load
		load : function(url,args) {
		
			// loading
			this.obj.get('boundingBox').addClass('loading');
		
			// fire
			this.fire('panel:beforeload');		
			
			// url
			var url = BLT.Obj.getUrl(url);	
				
			console.log(url);				
				
			// reg urk
			var reg_url = url;
					
			// params
			var params = {
				'method': 'GET',
				'context': this,
				'arguments': args,
				'timeout': 10000,
				'on': {
					'failure': function() {
					//	window.location.href = reg_url;
					},
				 	'complete': function(id,o,a) {
						
						// get fata
						var json = false;
						
						// try to parse
						try {
							json = $j.parse(o.responseText);
						}
						catch (e) {}
						
						// need a good stat
						if ( !json || json.stat != 1 ) {
						//	window.location.href = reg_url; return;						
						}
						
						// not loading
						this.obj.get('boundingBox').removeClass('loading');
						
						// check for special actions
						if ( json['do'] ) {
							if ( json['do'] == 'redi' ) {
								this.close();
								window.location.href = json.url; return;
							}
							else if ( json['do'] == 'error' ) {
								
								//remove loading class to allow resubmit
								Y.one(args.form).removeClass('loading');
								
								return;
							
							}
							else if ( json['do'] == 'login' ) {
								BLT.Obj.login(json.args); return;
							}
							else if ( json['do'] == 'load' ) {
													
								// load a page
								this.load(json.url+'&.context=xhr',{'openAfter':true}); return;
								
							}
							else if ( json['do'] == 'close' ) {
								this.close(json.args); return;
							}
							else if ( json['do'] == 'refresh' ) {
							
								// window
								window.location.href = window.location.href;
								
							}
						}
						
						// set it 
						this.obj.set('bodyContent',json.html);	
							
							// check for panel-bd and classes
							if ( this.obj.get('contentBox').one(".panel-bd") ) {
							
								// bd
								var bd = this.obj.get('contentBox').one(".panel-bd");	
								
								// get all classes
								var classes = bd.getAttribute('class').split(' ');
								
								// append to 
								for ( var c in classes ) {
									if ( classes[c] != 'panel-bd' ) {
										bd.removeClass(classes[c]);
										this.obj.get('contentBox').one('.yui3-widget-bd').addClass(classes[c]);
									}
								}
							
							}
 						
						
							// size it
					//		this.obj.get('contentBox').setStyle('width',this.obj.get('bodyContent').getStyle('width')[0] );				
					
						// if bootstrap
						if ( json.bootstrap ) {
						
							// header content
							this.obj.get('bodyContent').addClass(json.bootstrap.c);
							
							// boot me
							if ( json.bootstrap.js ) {
								for ( var el in json.bootstrap.js ) {
									eval(json.bootstrap.js[el]);
								}
							}
							
						}
						
						// hd
						if ( json.hd ) {
							this.obj.set('headerContent', json.hd);
						}
						
						// look for forms in the head content
						this.obj.get('boundingBox').all('form').each(function(el){						
							
							//don't do it if it's a direct post
							if (!el.hasClass('direct')) {
								
								// get attr
								var action = el.getAttribute('action');
								
								// reset it 
								el.setAttribute('x-action', action);
								el.setAttribute('action','#');
								el.setAttribute('method','get');										
															
									// attach to submit
									el.on('submit',function(e){	
										e.halt(); this.submit(e);								
									},this);
								
							}
							
						},this);
						
						this.obj.centered();
						
						// fire
						this.fire('panel:afterload');						
						
						// load
						BLT.execute('l');
						
						// open
						if ( a && a.openAfter ) {
							this.open();
						}			 		
						
					}
				}			
			};
			
			// form
			if (args && args.form) {
				params['form'] = { 'id': args.form };
				params['method'] = 'POST';
			}
		
			// fire
			Y.io(url,params);
		
		}
	
	} 
	
	// we fire some custom events
	Y.augment(BLT.Class.Panel, Y.EventTarget);

});
