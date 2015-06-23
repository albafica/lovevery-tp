<?php

/**
 * Description of EmployeeController
 * 求职者列表相关控制器
 * @author albafica.wang
 */

namespace Backend\Controller;

use Backend\Controller\BaseController;

class EmployeeController extends BaseController {

    public function __construct() {
        parent::__construct();
        //求职者管理权限和简历管理权限一致
//        $this->checkRight(self::HANDLECV);
    }

    /**
     * 求职者列表
     */
    public function index() {
        $employeeModel = D('Employee');
        //获取所有求职者信息列表
        $map = array(
            'enddate' => array('gt', date('Y-m-d')),
            'employee.status' => array('in', array('01', '02',))
        );
        $field = 'employee.id,employee.managerid,employee.status,employee.startdate,employee.enddate,manager.cname,manager.email,manager.mobilephone';
        $join = 'LEFT JOIN manager ON employee.managerid = manager.id';
        $condition = array('sort' => 'enddate', 'order' => 'DESC', 'rows' => 10,);
        $employeeList = $employeeModel->search($map, $condition, false, $field, $join);
        $this->employeeList = $employeeList;
        $this->display();
    }

    /**
     * 改变发布状态
     */
    public function changetatus() {
        $employeeId = I('employeeid', 0, 'intval');
        $managerId = I('managerid', 0, 'intval');
        $status = I('status', '', 'trim');
        $updWhere = array(
            'id' => $employeeId,
            'managerid' => $managerId,
        );
        $updData = array();
        switch (strtolower($status)) {
            case 'stop':
                //暂停发布
                $updWhere['status'] = '01';
                $updData['status'] = '02';
                break;
            case 'restart':
                //重新发布
                $updWhere['status'] = '02';
                $updData['status'] = '01';
                break;
            default:
                $this->error('参数错误', U('Backend/Employee/index'));
                exit();
        }
        $employeeModel = D('Employee');
        $result = $employeeModel->where($updWhere)->save($updData);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->success('操作失败');
        }
    }

    /**
     * 结束发布状态
     */
    public function endEmplyee() {
        $employeeId = I('employeeid', 0, 'intval');
        $managerId = I('managerid', 0, 'intval');
        $updWhere = array(
            'id' => $employeeId,
            'managerid' => $managerId,
            'status' => array('in', array('01', '02',))
        );
        $updData = array(
            'status' => '03',
        );
        $managerModel = D('Manager');
        $employeeModel = D('Employee');
        $employeeModel->startTrans();
        $result = $employeeModel->where($updWhere)->save($updData);
        //2、更新简历信息
        $managerUpdwhere = array(
            'id' => $managerId,
            'releasestatus' => '01',
        );
        $managerupdData = array(
            'releasestatus' => '00',
        );
        $result2 = $managerModel->where($managerUpdwhere)->save($managerupdData);
        if ($result && $result2) {
            $employeeModel->commit();
            $this->success('发布成功', U('Backend/Employee/index'));
        } else {
            $employeeModel->rollback();
            $this->error('发布失败，请稍后再试', U('Backend/Employee/index'));
        }
    }

}