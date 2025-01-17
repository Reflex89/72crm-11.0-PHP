<?php
// +----------------------------------------------------------------------
// | Description: 审批意见
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use app\admin\model\Common;
use think\Request;
use think\Validate;

class ExamineRecord extends Common
{
	/**
     * 为了数据库的整洁，同时又不影响Model和Controller的名称
     * 我们约定每个模块的数据表都加上相同的前缀，比如CRM模块用crm作为数据表前缀
     */
	protected $name = 'admin_examine_record';

	/**
     * 审批意见(创建)
     * @param types 关联对象
     * @param types_id 联对象ID
     * @param flow_id 审批流程ID
     * @param step_id 审批步骤ID
     * @param user_id 审批人ID
     * @param status 1通过0驳回
     * @return 
     */
    public function createData($param)
    {
		if ($this->data($param)->allowField(true)->save()) {
			$data = [];
			$data['record_id'] = $this->record_id;
			return $data;
		} else {
			$this->error = '添加失败';
			return false;
		}    	
    }

	/**
     * 审批意见(列表)
     * @param types 关联对象
     * @param types_id 联对象ID
     * @return 
     */
    public function getDataList($param)
    {
		$userModel = new \app\admin\model\User();
        if (empty($param['types']) || empty($param['types_id'])) {
            return [];
        }

        $result = [];

        # 获取创建者信息（办公审批）
        if ($param['types'] == 'oa_examine' && !empty($param['is_record'])) {
            $info     = db('oa_examine')->field(['create_time', 'create_user_id'])->where('examine_id', $param['types_id'])->find();
            $userInfo = $userModel->getUserById($info['create_user_id']);

            $result[] = [
                'check_date'         => date('Y-m-d H:i:s', $info['create_time']),
                'check_time'         => $info['create_time'],
                'check_user_id'      => $info['create_user_id'],
                'check_user_id_info' => $userInfo,
                'content'            => '',
                'flow_id'            => 0,
                'is_end'             => 0,
                'order_id'           => 1,
                'record_id'          => 0,
                'status'             => 3,
                'types'              => $param['types'],
                'types_id'           => $param['types_id']
            ];
        }

        # 获取创建者信息（业务审批）
        if (in_array($param['types'], ['crm_contract', 'crm_receivables', 'crm_invoice']) && !empty($param['is_record'])) {
            $model      = db($param['types']);
            $primaryKey = null;
            if ($param['types'] == 'crm_contract')    $primaryKey = 'contract_id';
            if ($param['types'] == 'crm_receivables') $primaryKey = 'receivables_id';
            if ($param['types'] == 'crm_invoice')     $primaryKey = 'invoice_id';

            $info     = $model->field(['create_time', 'owner_user_id'])->where($primaryKey, $param['types_id'])->find();
            $userInfo = $userModel->getUserById($info['owner_user_id']);

            $result[] = [
                'check_date'         => date('Y-m-d H:i:s', $info['create_time']),
                'check_time'         => $info['create_time'],
                'check_user_id'      => $info['owner_user_id'],
                'check_user_id_info' => $userInfo,
                'content'            => '',
                'flow_id'            => 0,
                'is_end'             => 0,
                'order_id'           => 1,
                'record_id'          => 0,
                'status'             => 3,
                'types'              => $param['types'],
                'types_id'           => $param['types_id']
            ];
        }
        unset($param['is_record']);

        $list = db('admin_examine_record')->where($param)->order('check_time asc')->select();
        foreach ($list as $k=>$v) {
            $list[$k]['check_user_id_info'] = $userModel->getUserById($v['check_user_id']);
            $list[$k]['check_date'] = date('Y-m-d H:i:s', $v['check_time']);
            $list[$k]['order_id'] = $k + 2;

            $result[] = $list[$k];
        }

        return !empty($result) ? $result : [];
    } 

    /**
     * 审批意见(标记无效,撤销审批时使用)
     * @param types 关联对象
     * @param types_id 关联对象ID
     * @return 
     */
    public function setEnd($param)
    {
        if (empty($param['types']) || empty($param['types_id'])) {
            $this->error = '参数错误';
            return false;
        }        
        $res = $this->where(['types' => $param['types'],'types_id' => $param['types_id']])->update(['is_end' => 1]);
        return true;
    }      
} 