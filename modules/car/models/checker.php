<?php
/**
 * @filesource modules/car/models/checker.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Checker;

use Kotchasan\Database\Sql;

/**
 * คลาสสำหรับตรวจสอบข้อมูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * ตรวจสอบรถว่าง
     * คืนค่า true ถ้ารถว่าง
     * ไม่ว่าง คืนค่า false
     *
     * @param array $save
     * @param int $id
     *
     * @return bool
     */
    public static function availability($save, $id = 0)
    {
        $where = array(
            array('vehicle_id', $save['vehicle_id']),
            array('status', 1)
        );
        if ($id > 0) {
            $where[] = array('id', '!=', $id);
        }
        $search = \Kotchasan\Model::createQuery()
            ->from('car_reservation')
            ->where($where)
            ->andWhere(array(
                Sql::create("('$save[end]' BETWEEN `begin` AND `end`)"),
                Sql::create("('$save[begin]' BETWEEN `begin` AND `end`)"),
                Sql::create("(`begin` BETWEEN '$save[begin]' AND '$save[end]' AND `end` BETWEEN '$save[begin]' AND '$save[end]')")
            ), 'OR')
            ->first('id');
        return $search === false ? true : false;
    }
}
