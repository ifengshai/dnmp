<?php

namespace app\admin\model;

use think\Model;

class AuthGroupAccess extends Model
{
    //
    /**
     * 判断当前用户所拥有的站点权限
     *
     * @Description
     * @author lsw
     * @since 2020/06/01 17:57:30 
     * @return void
     */
    // public function getUserPrivilege($user_id)
    // {
    //     $result = $this->alias('a')->where(['a.uid'=>$user_id])->join('fa_auth_group g','a.group_id=g.id')->field('g.id,g.rules')->select();
    //     if($result){
    //         $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 0;
    //         $result = collection($result)->toArray();
    //         foreach($result as $v){
    //             if($v['rules'] == '*'){
    //                 $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 1; 
    //             }elseif(!empty($v['rules']) && ($v['rules']!='*')){
    //                 $rulesArr = explode(',',$v['rules']);
    //                  //zeelool权限
    //                 if(in_array(846,$rulesArr)){
    //                     $zeelool_privilege = 1;    
    //                 }
    //                 //voogueme权限
    //                 if(in_array(847,$rulesArr)){
    //                     $voogueme_privilege = 1;
    //                 }
    //                 //nihao权限
    //                 if(in_array(848,$rulesArr)){
    //                     $nihao_privilege = 1;
    //                 }
    //                 //meeloog权限
    //                 if(in_array(849,$rulesArr)){
    //                     $meeloog_privilege = 1;
    //                 }
    //             }
    //         }
    //             //都没有权限
    //             $privilege = 0;
    //         if($zeelool_privilege ==1 && $voogueme_privilege==0 && $nihao_privilege == 0 && $meeloog_privilege == 0){
    //             //只有zeelool的权限
    //             $privilege = 1;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==1 && $nihao_privilege == 0 && $meeloog_privilege == 0){
    //             //只有voogueme权限
    //             $privilege = 2;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==0 && $nihao_privilege == 1 && $meeloog_privilege == 0){
    //             //只有nihao权限
    //             $privilege = 3;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==0 && $nihao_privilege == 0 && $meeloog_privilege == 1){
    //             //只有meeloog权限
    //             $privilege = 4;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==1 && $nihao_privilege == 0 && $meeloog_privilege == 0){
    //             //只有zeelool和voogueme权限
    //             $privilege = 5;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==0 && $nihao_privilege == 1 && $meeloog_privilege == 0){
    //             //只有zeelool和nihao权限
    //             $privilege = 6;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==0 && $nihao_privilege == 0 && $meeloog_privilege == 1){
    //             //只有zeelool和meeloog权限
    //             $privilege = 7;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==1 && $nihao_privilege == 1 && $meeloog_privilege == 0){
    //             //只有voogueme和nihao权限
    //             $privilege = 8;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==1 && $nihao_privilege == 0 && $meeloog_privilege == 1){
    //             //只有voogueme和meeloog权限
    //             $privilege = 9;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==0 && $nihao_privilege == 1 && $meeloog_privilege == 1){
    //             //只有nihao和meeloog权限
    //             $privilege = 10;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==1 && $nihao_privilege == 1 && $meeloog_privilege == 0){
    //             //只有zeelool、voogueme、nihao的权限
    //             $privilege = 11;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==1 && $nihao_privilege == 0 && $meeloog_privilege == 1){
    //             //只有zeelool、voogueme、meeloog权限
    //             $privilege = 12;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==0 && $nihao_privilege == 1 && $meeloog_privilege == 1){
    //             //只有zeelool、nihao、meeloog权限
    //             $privilege = 13;
    //         }elseif($zeelool_privilege ==0 && $voogueme_privilege==1 && $nihao_privilege == 1 && $meeloog_privilege == 1){
    //             //只有voogueme、nihao、meeloog权限
    //             $privilege = 14;
    //         }elseif($zeelool_privilege ==1 && $voogueme_privilege==1 && $nihao_privilege == 1 && $meeloog_privilege == 1){
    //             //拥有所有的权限
    //             $privilege = 15;
    //         }else{
    //             //都没有权限
    //             $privilege = 0; 
    //         }
    //     }
    //     return $privilege ?? 0;
    // }
     public function getUserPrivilege($user_id)
    {
        $result = $this->alias('a')->where(['a.uid'=>$user_id])->join('fa_auth_group g','a.group_id=g.id')->field('g.id,g.rules')->select();
        if($result){
            $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 
            $zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege = $all_privilege = 0;
            $result = collection($result)->toArray();
            foreach($result as $v){
                if($v['rules'] == '*'){
                    $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 
                    $zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege = $all_privilege = 1; 
                }elseif(!empty($v['rules']) && ($v['rules']!='*')){
                    $rulesArr = explode(',',$v['rules']);
                     //zeelool权限
                    if(in_array(846,$rulesArr)){
                        $zeelool_privilege = 1;    
                    }
                    //voogueme权限
                    if(in_array(847,$rulesArr)){
                        $voogueme_privilege = 1;
                    }
                    //nihao权限
                    if(in_array(848,$rulesArr)){
                        $nihao_privilege = 1;
                    }
                    //meeloog权限
                    if(in_array(849,$rulesArr)){
                        $meeloog_privilege = 1;
                    }
                    //zeelool_es权限
                    if(in_array(979,$rulesArr)){
                        $zeelool_es_privilege = 1;
                    }
                    //zeelool_de权限
                    if(in_array(980,$rulesArr)){
                        $zeelool_de_privilege = 1;
                    }
                }
            }
            //都没有权限
            $arr = [];
            if(1 == $zeelool_privilege){
                //有zeelool的权限
                $arr[] = 1;
            }
            if(1 == $voogueme_privilege){
                //有voogueme权限
                $arr[] = 2;
            }
            if(1 == $nihao_privilege){
                //有nihao权限
                $arr[] = 3;
            }
            if(1 == $meeloog_privilege){
                //有meeloog权限
                $arr[] = 4;
            }
            if(1 == $zeelool_es_privilege){
                $arr[] = 9;
            }
            if(1 == $zeelool_de_privilege){
                $arr[] = 10;
            }
            if(1 == $zeelool_jp_privilege){
                $arr[] = 11;
            }
            if(1 == $all_privilege){
                $arr[] = 100;
            }
        }
        return $arr ?? 0;
    }   
    /**
     * 经营报告权限分组
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 15:58:54 
     * @param [type] $user_id
     * @return void
     */
    public function getOperationalreportPrivilege($user_id)
    {
        $result = $this->alias('a')->where(['a.uid'=>$user_id])->join('fa_auth_group g','a.group_id=g.id')->field('g.id,g.rules')->select();
        if($result){
            $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = $wesee_privilege =
            $zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege = 0;
            $result = collection($result)->toArray();
            foreach($result as $v){
                if($v['rules'] == '*'){
                    $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 
                    $zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege = 1; 
                }elseif(!empty($v['rules']) && ($v['rules']!='*')){
                    $rulesArr = explode(',',$v['rules']);
                     //zeelool权限
                    if(in_array(850,$rulesArr)){
                        $zeelool_privilege = 1;    
                    }
                    //voogueme权限
                    if(in_array(851,$rulesArr)){
                        $voogueme_privilege = 1;
                    }
                    //nihao权限
                    if(in_array(852,$rulesArr)){
                        $nihao_privilege = 1;
                    }
                    //meeloog权限
                    if(in_array(853,$rulesArr)){
                        $meeloog_privilege = 1;
                    }
                    
                    //zeelool_es权限
                    if(in_array(979,$rulesArr)){
                        $zeelool_es_privilege = 1;
                    }
                    //zeelool_de权限
                    if(in_array(980,$rulesArr)){
                        $zeelool_de_privilege = 1;
                    }
                }
            }
                //都没有权限
                $arr = [];
            if(1 == $zeelool_privilege){
                //有zeelool的权限
                $arr[] = 1;
            }
            if(1 == $voogueme_privilege){
                //有voogueme权限
                $arr[] = 2;
            }
            if(1 == $nihao_privilege){
                //有nihao权限
                $arr[] = 3;
            }
            if(1 == $meeloog_privilege){
                //有meeloog权限
                $arr[] = 4;
            }
            if(1 == $zeelool_es_privilege){
                $arr[] = 9;
            }
            if(1 == $zeelool_de_privilege){
                $arr[] = 10;
            }
            if(1 == $zeelool_jp_privilege){
                $arr[] = 11;
            }
        }
        return $arr ?? [];
    }
    /**
     * 转化率权限分组
     *
     * @Description
     * @author lsw
     * @since 2020/06/03 15:58:54 
     * @param [type] $user_id
     * @return void
     */
    public function getConversionratePrivilege($user_id)
    {
        $result = $this->alias('a')->where(['a.uid'=>$user_id])->join('fa_auth_group g','a.group_id=g.id')->field('g.id,g.rules')->select();
        if($result){
            $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege 
            =$zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege = 0;
            $result = collection($result)->toArray();
            foreach($result as $v){
                if($v['rules'] == '*'){
                    $zeelool_privilege = $voogueme_privilege = $nihao_privilege = $meeloog_privilege = 
                    $zeelool_es_privilege = $zeelool_de_privilege = $zeelool_jp_privilege =1; 
                }elseif(!empty($v['rules']) && ($v['rules']!='*')){
                    $rulesArr = explode(',',$v['rules']);
                     //zeelool权限
                    if(in_array(854,$rulesArr)){
                        $zeelool_privilege = 1;    
                    }
                    //voogueme权限
                    if(in_array(855,$rulesArr)){
                        $voogueme_privilege = 1;
                    }
                    //nihao权限
                    if(in_array(856,$rulesArr)){
                        $nihao_privilege = 1;
                    }
                    //meeloog权限
                    if(in_array(857,$rulesArr)){
                        $meeloog_privilege = 1;
                    }
                    //zeelool_es权限
                    if(in_array(979,$rulesArr)){
                        $zeelool_es_privilege = 1;
                    }
                    //zeelool_de权限
                    if(in_array(980,$rulesArr)){
                        $zeelool_de_privilege = 1;
                    }
                }
            }
                //都没有权限
                $arr = [];
            if(1 == $zeelool_privilege){
                //有zeelool的权限
                $arr[] = 1;
            }
            if(1 == $voogueme_privilege){
                //有voogueme权限
                $arr[] = 2;
            }
            if(1 == $nihao_privilege){
                //有nihao权限
                $arr[] = 3;
            }
            if(1 == $meeloog_privilege){
                //有meeloog权限
                $arr[] = 4;
            }
            if(1 == $zeelool_es_privilege){
                $arr[] = 9;
            }
            if(1 == $zeelool_de_privilege){
                $arr[] = 10;
            }
            if(1 == $zeelool_jp_privilege){
                $arr[] = 11;
            }
        }
        return $arr ?? [];
    }      
}
