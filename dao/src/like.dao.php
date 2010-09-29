<?php

namespace Dao;

class like extends Db {

        protected $data = array(        
            'asset_type' => false,
            'asset_id' => false,
            'entity' => false,
            'user' => false,
            'ts' => false
		);
        
        public function save() { 
                
                // normalize
                $data = $this->normalize();
                
                // try to get
                if ( $this->row("SELECT * FROM likes as l WHERE l.entity = ? AND l.asset_type = ? AND l.asset_id = ? AND l.user = ? ",array($data['entity'],$data['asset_type'],$data['asset_id'],$data['user'])) ) {
                        return false;
                }
                
                // sql
                $sql = "
                        INSERT INTO 
                                `likes`
                        SET 
                                asset_id = ?,
                                asset_type = ?,
                                entity = ?,
                                user = ?,
                                ts = ?
                ";
                
                // save
                $this->query($sql,array(
                        $data['asset_id'],
                        $data['asset_type'],
                        $data['entity'],                        
                        $data['user'],
                        \b::utctime()
                ));                    
                
                // return
                return true;
                        
        }
        
       }

?>