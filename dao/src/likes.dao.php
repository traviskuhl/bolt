<?php

namespace Dao;
class likes extends Db implements \Iterator {

        // get a list
        public function get($cfg=array()) {
                                
                // page
                $page = p('page',1,(array)$cfg);
                $per = p('per',20,(array)$cfg);
                $start = ($page-1)*$per;
                        
                // where limits
                $where = array(true);
                $p = array();
        
                if ( isset($cfg['id']) ) {
                        $where[] = " l.asset_id = ? ";
                        $p[] = $cfg['id'];
                }
                
                if ( isset($cfg['type']) ) {
                        $where[] = " l.asset_type = ? ";
                        $p[] = $cfg['type'];            
                }

                if ( isset($cfg['user']) ) {
                        $where[] = " l.user = ? ";
                        $p[] = $cfg['user'];            
                }


                if ( isset($cfg['entity']) ) {
                        $where[] = " l.entity = ? ";
                        $p[] = $cfg['entity'];            
                }
        
                // sql
                $sql = "
                        SELECT * 
                        FROM likes as l
                        WHERE ".implode(' AND ',$where)." 
                        ORDER BY ts ".(p('order','DESC',$cfg))." 
                        LIMIT $start,$per
                ";      
        
                // total                
                $total = true;
                                
                // cid g
                $cg = 'like:'.p('user',false,$cfg);
                                                        
                // cache
                $cid = 'likes:'.md5( $sql . serialize($p) );
                
                // check the cache
                if ( ($sth = $this->cache->get($cid,$cg)) == false ) {
                                                                                     
                        // sth
                        $sth = $this->query($sql,$p,$total);
                        
                        // save
                        $sth = array($sth,$total);
                        
                        // save it 
                        $this->cache->set($cid,$sth,(60),$cg);
                        
                }
                
                
                // set it 
                $total = $sth[1];
                $sth = $sth[0];
                
                // loop
                foreach ( $sth as $row ) {              
                        $this->_items[] = new like('set',$row);
                }
                
                // pager
                $this->setPager($total,$page,$per);
                        
        }
        
        // find 
        public function find($type,$id,$entity) {
                
                foreach ( $this->_items as $item ) {
                        if ( $item->asset_type == $type AND $item->asset_id == $id AND $item->entity == $entity ) {
                                return $item;
                        }
                }
        
                // nope
                return false;
        
        }

}






?>