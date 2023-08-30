<?php
/**
 * @filesource modules/car/views/tools.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Tools;

use Kotchasan\Date;

/**
 * View Base Class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * คืนค่าเวลาจอง
     *
     * @param array $item
     *
     * @return string
     */
    public static function dateRange($item)
    {
        if (
            preg_match('/([0-9]{4,4}\-[0-9]{2,2}\-[0-9]{2,2})\s[0-9\:]+$/', $item['begin'], $begin) &&
            preg_match('/([0-9]{4,4}\-[0-9]{2,2}\-[0-9]{2,2})\s[0-9\:]+$/', $item['end'], $end)
        ) {
            if ($begin[1] == $end[1]) {
                return Date::format($item['begin'], 'd M Y').' {LNG_Time} '.Date::format($item['begin'], 'TIME_FORMAT').' {LNG_to} '.Date::format($item['end'], 'TIME_FORMAT');
            } else {
                return Date::format($item['begin']).' {LNG_to} '.Date::format($item['end']);
            }
        }
    }
}