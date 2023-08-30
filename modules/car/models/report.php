<?php
/**
 * @filesource modules/car/models/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Report;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=car-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array();
        if ($params['status'] > -1) {
            $where[] = array('V.status', $params['status']);
        }
        if ($params['vehicle_id'] > 0) {
            $where[] = array('V.vehicle_id', $params['vehicle_id']);
        }
        if ($params['chauffeur'] > -2) {
            $where[] = array('V.chauffeur', $params['chauffeur']);
        }
        if ($params['from'] != '') {
            $where[] = array(Sql::DATE('V.begin'), '>=', $params['from']);
        }
        if ($params['to'] != '') {
            $where[] = array(Sql::DATE('V.begin'), '<=', $params['to']);
        }
        $today = date('Y-m-d H:i:s');
        return static::createQuery()
            ->select(
                'V.detail',
                'V.id',
                'V.vehicle_id',
                'R.color',
                'U.name contact',
                'U.phone',
                'V.begin',
                'V.end',
                'V.chauffeur',
                'V.create_date',
                'V.status',
                'V.reason',
                Sql::create('(CASE WHEN "'.$today.'" BETWEEN V.`begin` AND V.`end` THEN 1 WHEN "'.$today.'" > V.`end` THEN 2 ELSE 0 END) AS `today`'),
                Sql::create('TIMESTAMPDIFF(MINUTE,"'.$today.'",V.`begin`) AS `remain`')
            )
            ->from('car_reservation V')
            ->join('vehicles R', 'LEFT', array('R.id', 'V.vehicle_id'))
            ->join('user U', 'LEFT', array('U.id', 'V.member_id'))
            ->where($where);
    }

    /**
     * รับค่าจาก action (report.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, สามารถอนุมัติได้
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_approve_car')) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete') {
                        $where = array(
                            array('id', $match[1])
                        );
                        if ($login['status'] != 1) {
                            // แอดมินลบได้ทั้งหมด, สถานะอื่นๆไม่สามารถลบรายการที่อนุมัติแล้วได้
                            $where[] = Sql::create('(NOW()<`begin` OR `status`!='.self::$cfg->car_approved_status.')');
                        }
                        $query = static::createQuery()
                            ->select('id')
                            ->from('car_reservation')
                            ->where($where);
                        $ids = array();
                        foreach ($query->execute() as $item) {
                            $ids[] = $item->id;
                        }
                        if (!empty($ids)) {
                            // ลบ
                            $this->db()->delete($this->getTableName('car_reservation'), array('id', $ids), 0);
                            $this->db()->delete($this->getTableName('car_reservation_data'), array('reservation_id', $ids), 0);
                            // log
                            \Index\Log\Model::add(0, 'car', 'Delete', '{LNG_Delete} {LNG_Vehicle booking report} ID : '.implode(', ', $ids), $login['id']);
                        }
                        // reload
                        $ret['location'] = 'reload';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
