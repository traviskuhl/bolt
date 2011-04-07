#!/usr/bin/php
<?php
	
	date_default_timezone_set('America/Los_Angeles');
	
	// sync
	define("ROOT", "/home/bolt/libexec/bolt/warhol/" );
	
	// require cssmin
	require(ROOT.'cssmin.php');
	
	// require jsmin
	require(ROOT.'jsmin.php');
	
	// require s3
	require(ROOT.'S3.php');
	
	// require global
	require(ROOT."config.inc");
	
	// require warhol config, returns $cfg
	if (!empty($argv[1])) { 
		require($argv[1]);
	} else if (file_exists('warhol.config')) { 
		require('warhol.config');
	} else { 
		echo 'No warhol config file found!'; die;
	}
	
	if (!empty($argv[2])) { 
		$mf = $argv[2];
	} else if (file_exists('warhol.manifest')) { 
		$mf = 'warhol.manifest';
	} else { 
		echo 'No manifest file found!'; die;
	}
	
	$manifest = json_decode(file_get_contents($mf),true);
	
	// s3
	$aws = new S3($cfg['aws-key'],$cfg['aws-secret']);
	
	// get the bucket
	$awsbucket = $cfg['aws-bucket'];

	// set the host for files
	if (isset($cfg['aws-cloudfront'])) { 
		$host = $cfg['aws-cloudfront'];
	} else { 
		$host = $awsbucket.'.s3.amazonaws.com';
	}
	
	$awsheaders = array(
    		'Expires' => date("D, d M Y H:i:s e",(strtotime('now')+(60*60*24*365*10))),
    		'Cache-Control'=>'max-age=315360000', 
    );
	
	// setup storage
	$filelist = array();
	
	//compile list of assets based on args and assign contents to array
	foreach ($cfg['dirs'] as $dir) { 
		
		//get all dirs
		$subdirs = get_dirs($dir);
		$subdirs = explode("\n",$subdirs);
		
		foreach ($subdirs as $d) {
			
			if ($handle = opendir($d)) { 
		
				/* loop over the directory. */
			    while (false !== ($file = readdir($handle))) { 
			    			        
			       if ($file != '..' && $file != '.' && $file != ".svn" && $file != "tmp" && $file != "props" && $file != "text-base" && $file != "prop-base") { 
			       		
			       		// make sure it is a file
			       		if ( stripos($file,'.') > 0 ) { 	
			       		
					       	   // get the file extension	
						       $ext = getextention($file); 
						        			        
						       if ($ext == 'css') {
						        		echo 'Inspecting \''.$file."' for changes...\n";
						        		$filelist[] = array('type'=>'css','file'=>$d.'/'.$file,'dir'=>$d);
							   } else if ($ext == 'js') { 
						        		echo 'Inspecting \''.$file."' for changes...\n";
						        		$filelist[] = array('type'=>'js','file'=>$d.'/'.$file,'dir'=>$d);
						        		
						       } 
				       
				       }
			       
			       }
			    		        
			        
			    }
		    
		    }	
		
		}
	
	} 
	
	$images = array();
	
	$css = array();
	$js = array();
	
	$count = 0;

				
	//go through each css file and find images, check manifest file, if newer, push to S3 and replace in css file
	foreach ($filelist as $f) {
		
		// find images to replace
		if ($f['type'] == 'css') { 
		
			echo "Processing ".$f['file']."\n";
			
			$css[$f['file']] = file_get_contents($f['file']);
			
			$matches = array();
			
			preg_match_all('/url\(\'?(.*)\'?\)/',$css[$f['file']],$matches); 
			
			// go through all matches and create an array of them
			foreach ($matches[1] as $m) {
				
				$m = str_replace("'","",$m); 
				
				// create a nice filename for pushing to amazon
				$cleanFile = str_replace('../','',$m);
				$cleanFile = str_replace('/','_',$cleanFile);
				
				// make sure the image is relative
				if (stripos($m,'http') === false) { 
															
					// get the last modified time of the image
					$images[$m] = @filemtime($f['dir'].'/'.$m);
					
					if (!$images[$m]) { echo "\n".'FATAL ERROR with ' . $f['dir'].'/'.$m; continue;   }
						
						// is the file newer, if so, push and update manifest
						if ((isset($manifest[$m]) AND $manifest[$m]['mtime'] < $images[$m]) OR !isset($manifest[$m])) { 
						
							// update manifest array
							$manifest[$m]['mtime'] = $images[$m];
							
							// push new version to S3
							echo 'Pushing \''.$m.'\' to Amazon S3 ... ';
							
							// get the image content for pushing
							$imgcontent = file_get_contents($f['dir'].'/'.$m); 
							
							// set a filename for pushing
							$filename = 'static/img/'.$cfg['sitename'].'_'.$images[$m].'_'.$cleanFile;
							
							// push to Amazon
							$r = $aws->putObject($imgcontent,$awsbucket,$filename,S3::ACL_PUBLIC_READ,$awsheaders,$awsheaders);
							
							if ($r) {
								
								echo "\033[32mSUCCESS\033[37m\r\n";
								
								// http static url
								$cdnImage = 'http://'.$host.'/'.$filename;	
								
								// relative for replacing
								$relImage = '../img/'.$cfg['sitename'].'_'.$images[$m].'_'.$cleanFile; 
								
								// update the manifest						
								$manifest[$m] = array('mtime'=>$images[$m],'static'=>$cdnImage,'rel'=>$relImage);
								
								// replace the image in the CSS
								$css[$f['file']] = str_replace($m,$relImage,$css[$f['file']]); 	
								
								
							} else { 
								
								echo "FAILURE\r\n";
								
							}							
						
						} else if (isset($manifest[$m]['rel'])) {	
							
							// nothing has changed
							$cdnImage = $manifest[$m]['rel'];
							
							echo 'No new changes to push to S3 for '.$m."\n";
							
							// replace the image in the CSS
							$css[$f['file']] = str_replace($m,$cdnImage,$css[$f['file']]); 	
							
						}
																							
												
					
				}
				
			}			
						
			
		
		} else if ($f['type'] == 'js') { 
			
			$parts = explode('/',$f['file']);
			$jsFilename = $parts[count($parts)-1];
			$js[$jsFilename] = file_get_contents($f['file']);
		
		}
		
		// increment count for css
		$count++;
			
	}
	
	
	// push each individual CSS file to amazon
	foreach ($css as $k=>$c) {
		
		$cssfparts = explode('/',$k);
		$file = $cssfparts[count($cssfparts)-1]; 
		
		$mtime = filemtime($k);
		
		// is the file newer, if so, push and update manifest
		if ((isset($manifest[$file]) AND $manifest[$file]['mtime'] < $mtime) OR !isset($manifest[$file])) { 
		
			// update manifest array
			$manifest[$file]['mtime'] = $mtime;
			
			// push new version to S3
			echo 'Pushing \''.$file.'\' to Amazon S3 ... ';
			
			// get the image content for pushing
			$cssContent = file_get_contents($k); 
			
			// set a filename for pushing
			$filename = 'static/css/'.$cfg['sitename'].'_'.$mtime.'_'.$file;
			
			$awsheaders['Content-Type'] = 'text/css';
			
			// push to Amazon
			$r = $aws->putObject($cssContent,$awsbucket,$filename,S3::ACL_PUBLIC_READ,$awsheaders,$awsheaders);
			
			if ($r) {
				
				echo "\033[32mSUCCESS\033[37m\r\n";
				$cdnImage = 'http://'.$host.'/'.$filename;
				$manifest[$file] = array('mtime'=>$mtime,'static'=>$cdnImage,'rel'=>$filename); 			
				
			} else { 
				
				echo "FAILURE\r\n";
				
			}							
		
		}
		
	}
		
		
			
	
	// sort css if config for rollup order
	if (isset($cfg['css-sort'])) { 
	
		$cssOrder = explode(',',$cfg['css-sort']);
		$newCss = array();
		
		foreach ($cssOrder as $co) { 
		
			foreach ($css as $k=>$c) { 
				
				$cssfparts = explode('/',$k);
				
				$file = $cssfparts[count($cssfparts)-1]; 
				
				if ($file == $co) { 
					$newCss[] = $c;
				}
				
			}
			
		}
		
		$css = $newCss;
		
	} 
		
	$cssRollup = array();
	
	foreach ($css as $c) {
		// minify each css file
		$cssRollup[] = cssmin::minify($c);	
	}	
	
	// concat css files
	$finalCss = implode("\n\n",$cssRollup);
	
	// check version manifest to make sure something changed
	if (!empty($finalCss) AND (!isset($manifest['css']['hash']) OR $manifest['css']['hash'] != md5($finalCss))) { 
	
		// push new version to S3
		echo 'Pushing new CSS rollup to Amazon S3 ... ';
		
		// add content type for css
		$awsheaders['Content-Type'] = 'text/css';
		
		// create a filename
		$filename = 'static/css/'.$cfg['sitename'].'_'.strtotime('now').'.css';
		
		// push to Amazon
		$r = $aws->putObject($finalCss,$awsbucket,$filename,S3::ACL_PUBLIC_READ,$awsheaders,$awsheaders);
		
		if ($r) { 
			
			$manifest['css']['hash'] = md5($finalCss);
			$manifest['css']['static'] = 'http://'.$host.'/'.$filename;
			$manifest['css']['rel'] = $filename;
			
			echo "\033[32mSUCCESS\033[37m\r\nPushed new CSS rollup to ".$manifest['css']['static']."\n";

		
		} else { 
			
			echo "FAILURE \n";
		}
	
	} else { 
		
		echo "No new changes to push to S3 for CSS rollup \n";
				
	}
	
	
	
	
	if (!isset($cfg['js-rollup']) || $cfg['js-rollup'] == true) { 
	
		// javascript rollup
		$jsRollup = array();
		
		foreach ($js as $j) {
			// minify each js file
			$jsRollup[] = jsmin::minify($j);	
		}
		
		// concat js files
		$finalJs = implode("\n\n",$jsRollup);
		
		// check version manifest to make sure something changed
		if (!isset($manifest['js']) OR $manifest['js']['hash'] != md5($finalJs)) { 
		
			// push new version to S3
			echo 'Pushing new JS rollup to Amazon S3 ...';
			
			// add content type for js
			$awsheaders['Content-Type'] = 'text/javascript';
			
			// create a filename
			$filename = 'static/js/'.$cfg['sitename'].'_'.strtotime('now').'.js';
			
			// push to Amazon
			$r = $aws->putObject($finalJs,$awsbucket,$filename,S3::ACL_PUBLIC_READ,$awsheaders,$awsheaders);
			
			if ($r) { 
				
				$manifest['js']['hash'] = md5($finalJs);
				$manifest['js']['static'] = 'http://'.$host.'/'.$filename;
				$manifest['js']['rel'] = $filename;
				
				echo "\033[32mSUCCESS\033[37m\r\nPushed new JS rollup to ".$manifest['js']['static']."\n";
	
			
			} else { 
				
				echo "FAILURE \n";
			}
		
		}
	
	} else { // push each Javascript file individually and then update manifest
						
		foreach ($js as $k=>$j) {
			// minify each js file
			$minJs = jsmin::minify($j);	
			
			// check version manifest to make sure something changed
			if (!isset($manifest[$k]) OR $manifest[$k]['hash'] != md5($minJs)) { 
			
				// push new version to S3
				echo 'Pushing '.$k.' to Amazon S3 ... ';
				
				// add content type for js
				$awsheaders['Content-Type'] = 'text/javascript';
				
				// create a filename
				$filename = 'static/js/'.$cfg['sitename'].'_'.strtotime('now').'_'.$k;
				
				// push to Amazon
				$r = $aws->putObject($minJs,$awsbucket,$filename,S3::ACL_PUBLIC_READ,$awsheaders,$awsheaders);
				
				if ($r) { 
					
					$manifest[$k]['hash'] = md5($minJs);
					$manifest[$k]['static'] = 'http://'.$host.'/'.$filename;
					$manifest[$k]['rel'] = $filename;
					
					echo "\033[32mSUCCESS\033[37m\r\nPushed ".$k." to ".$manifest[$k]['static']."\n";
		
				
				} else { 
					
					echo "FAILURE \n";
				}
			
			} else { 
			
				echo 'No new changes to push to S3 for '.$k."\n";
			
			}

			
		}
	
	
	
	
	
	}
	
	
	
	// write the manifest back to the file and we're done
	$fp = fopen($mf, 'w');
	fwrite($fp, json_encode($manifest));
	fclose($fp);	
	
	// make sure we check in the manifest
	shell_exec('svn ci '.$mf.' -m "('.date("F j, Y, g:i a").') updated warhol version manifest"');
	
		
	/******* UTILITIES ********/
	
	function get_dirs($dir){ 
	    global $dirs; 
	    if (!isset($dirs)){$dirs = '';} 
	    if(substr($dir,-1) !== '/'){$dir .= '/';} 
	    if ($handle = opendir($dir)){ 
	        while (false !== ($file = readdir($handle))){ 
	            if (filetype($dir.$file) === 'dir' && $file != "." && $file != ".."){ 
	                clearstatcache(); 
	                $dirs .= $dir.$file . "\n"; 
	                get_dirs($dir . $file); 
	            } 
	        } 
	        closedir($handle); 
	    } 
	    return $dirs; 
	} 
	
	
	function getextention ($filename) 
	 { 
	 $filename = strtolower($filename) ; 
	 $exts = split("[/\\.]", $filename) ; 
	 $n = count($exts)-1; 
	 $exts = $exts[$n]; 
	 return $exts; 
	 } 
	
?>
	
	