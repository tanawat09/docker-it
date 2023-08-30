<?php
/**
 * @filesource modules/car/controllers/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Calendar;

use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * คืนค่าข้อมูลปฏิทิน (booking)
     * สำหรับแสดงในปฏิทินหน้าแรก
     *
     * @param string $from
     * @param string $to
     * @param array $events
     *
     * @return mixed
     */
    public static function get($from, $to, &$events)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('V.id', 'V.detail', 'V.begin', 'V.end', 'R.color')
            ->from('car_reservation V')
            ->join('vehicles R', 'INNER', array('R.id', 'V.vehicle_id'))
            ->where(array('V.status', self::$cfg->car_calendar_status))
            ->andWhere(array(
                Sql::create("(DATE(V.`begin`)>='$from' AND DATE(V.`begin`)<='$to')"),
                Sql::create("(DATE(V.`end`)>='$from' AND DATE(V.`end`)<='$to')")
            ), 'OR')
            ->order('V.begin')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $events[] = array(
                'id' => $item->id.'_car',
                'title' => self::title($item),
                'start' => $item->begin,
                'end' => $item->end,
                'color' => $item->color,
                'class' => 'icon-shipping'
            );
        }
    }

    /**
     * คืนค่าเวลาจอง
     *
     * @param object $item
     *
     * @return string
     */
    private static function title($item)
    {
        if (
            preg_match('/([0-9]{4,4}\-[0-9]{2,2}\-[0-9]{2,2})\s[0-9\:]+$/', $item->begin, $begin) &&
            preg_match('/([0-9]{4,4}\-[0-9]{2,2}\-[0-9]{2,2})\s[0-9\:]+$/', $item->end, $end)
        ) {
            if ($begin[1] == $end[1]) {
                $return = '{LNG_Time} '.Date::format($item->begin, 'TIME_FORMAT').' {LNG_to} '.Date::format($item->end, 'TIME_FORMAT');
            } else {
                $return = Date::format($item->begin).' {LNG_to} '.Date::format($item->end);
            }
            return Language::trans($return)."\n".Text::unhtmlspecialchars($item->detail);
        }
    }
}
