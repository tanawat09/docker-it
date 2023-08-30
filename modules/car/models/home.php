<?php
/**
 * @filesource modules/car/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Home;

use Kotchasan\Database\Sql;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายการจองวันนี้
     *
     * @return int
     */
    public static function getNew()
    {
        $search = static::createQuery()
            ->selectCount()
            ->from('car_reservation')
            ->where(array(
                array('status', self::$cfg->car_approved_status),
                Sql::BETWEEN(date('Y-m-d'), Sql::DATE('begin'), Sql::DATE('end'))
            ))
            ->execute();
        if (!empty($search)) {
            return $search[0]->count;
        }
        return 0;
    }

    /**
     * จำนวนรถยนต์ทั้งหมดที่สามารถจองได้
     *
     * @return int
     */
    public static function cars()
    {
        $search = static::createQuery()
            ->selectCount()
            ->from('vehicles')
            ->where(array('published', 1))
            ->execute();
        if (!empty($search)) {
            return $search[0]->count;
        }
        return 0;
    }

    /**
     * คืนค่าปีที่มีการจองสูงสุดและต่ำสุด
     * สำหรับแสดงในปฏิทิน
     * ถ้าไม่มีข้อมูลคืนค่าปีปัจจุบัน
     *
     * @return object
     */
    public static function getYearRange()
    {
        $result = static::createQuery()
            ->from('car_reservation R')
            ->first(Sql::YEAR(Sql::MAX('R.end'), 'max'), Sql::YEAR(Sql::MIN('R.begin'), 'min'));
        if (empty($result->min)) {
            $result->min = date('Y');
        }
        if (empty($result->max)) {
            $result->max = date('Y');
        }
        return $result;
    }
}
