<?php
/**
* Permission authentication class
 * Features:
 * 1, is to authenticate the rules, not the nodes. Users can use the node as a rule name to authenticate the node.
 * $auth=new Auth(); $auth->check('rule name','user id')
 * 2, can authenticate multiple rules at the same time, and set the relationship of multiple rules (or or and)
 * $auth=new Auth(); $auth->check('Rule 1,Rule 2','User id','and')
 * When the third parameter is and, it means that the user needs to have the permissions of both rule 1 and rule 2. When the third parameter is or, it means that the user value needs to meet one of the conditions. Default is or
 * 3. A user can belong to multiple user groups (the think_auth_group_access table defines the user group to which the user belongs). We need to set which rules each user group has (think_auth_group defines user group permissions)
 *
* 4, support regular expressions.
 * When defining a rule in the think_auth_rule table, if the type is 1, the condition field can define the rule expression. For example, the definition of {score}>5 and {score}<100 means that this rule will only pass when the user's score is between 5-100.
 */
namespace com;
use think\facade\Db;
class Auth{

    //default allocation
    protected $_config = array(
        'auth_on' => true, // authentication switch
        'auth_type' => 1, // Authentication method, 1 is real-time authentication; 2 is login authentication.
        'auth_group' => 'think_auth_group', // User group data table name
        'auth_group_access' => 'think_auth_group_access', // user-user group relationship table
        'auth_rule' => 'think_auth_rule', // permission rule table
        'auth_user' => 'think_admin' // user information table
    );

    public function __construct() {
        if (config('app.auth_config')) {
            //The configuration item auth_config can be set, this configuration item is an array.
            $this->_config = array_merge($this->_config, config('app.auth_config'));
        }
    }

    /**
      * Check permissions
      * @param name string|array The list of rules to be verified, supports comma-separated permission rules or index array
      * @param uid int id of the authenticated user
      * @param string mode mode to execute check
      * @param relation string If it is 'or', it means that it will pass the verification if any rule is satisfied; if it is 'and', it means that all the rules must be satisfied to pass the verification
      * @return boolean returns true on validation; false on failure
     */
    public function check($name, $uid, $type=1, $mode='url', $relation='or') {
        if (!$this->_config['auth_on'])
            return true;
       $authList = $this->getAuthList($uid,$type); //Get a list of all valid rules that the user needs to authenticate
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //Save the rule name that passed the validation
        if ($mode=='url') {
            $REQUEST = unserialize( strtolower(serialize($_REQUEST)) );
        }
        foreach ( $authList as $auth ) {
            $query = preg_replace('/^.+\?/U','',$auth);
            if ($mode=='url' && $query!=$auth ) {
                parse_str($query,$param); //Parse the param in the rule
                $intersect = array_intersect_assoc($REQUEST,$param);
                $auth = preg_replace('/\?.*$/U','',$auth);
                if ( in_array($auth,$name) && $intersect==$param ) { //If the node matches and the url parameter is satisfied
                    $list[] = $auth ;
                }
            }else if (in_array($auth , $name)){
                $list[] = $auth ;
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }

        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * Get the user group according to the user id, the return value is an array
     * @param uid int user id
     * @return array User group array(
     * array('uid'=>'user id','group_id'=>'user group id','title'=>'user group name','rules'=>'rule id owned by user group, multiple , separated by '),
     *...) 
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = Db::table('think_auth_group_access')
            ->alias('a')
            ->join("think_auth_group g", "g.id=a.group_id")
            ->where("a.uid='$uid' and g.status='1'")
            ->field('uid,group_id,title,rules')->select();
        $groups[$uid] = $user_groups ? $user_groups : array();
        return $groups[$uid];
    }

    /**
     * Get permission list
     * @param integer $uid user id
     * @param integer $type
     */
    protected function getAuthList($uid,$type) {
        static $_authList = array(); //Save the list of permissions that the user has authenticated
        $t = implode(',',(array)$type);
        if (isset($_authList[$uid.$t])) {
            return $_authList[$uid.$t];
        }
        if( $this->_config['auth_type']==2 && session('_auth_list_'.$uid.$t)){
            return session('_auth_list_'.$uid.$t);
        }
        //Read the user group to which the user belongs
        $groups = $this->getGroups($uid);
        $ids = array();//Save all permission rule ids set by the user group to which the user belongs
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid.$t] = array();
            return array();
        }
        $map=[
            ['id','in',$ids],
            ['type','=',$type],
            ['status','=',1],
        ];
        //Read all permission rules of the user group
        $rules =Db::table('think_auth_rule')->where($map)->field('condition,name')->select();

        //The loop rule, judge the result.
        $authList = array(); //

        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) { //Verify according to condition
                $user = $this->getUserInfo($uid);//Get user information, one-dimensional array
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                // record as long as it exists
                $authList[] = strtolower($rule['name']);
            }
        }
        $_authList[$uid.$t] = $authList;
        if($this->_config['auth_type']==2){
            //The result of the rule list is saved to the session
            session('_auth_list_'.$uid.$t, $authList);
        }
        return array_unique($authList);
    }

    /**
     * Obtain user information and read the database according to your own situation
     */
    protected function getUserInfo($uid) {
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
             $userinfo[$uid]=Db::table('think_admin')->where('id',$uid)->find();
        }
        return $userinfo[$uid];
    }

}
