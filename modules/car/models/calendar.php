<?php
/**
 * @filesource modules/car/models/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Calendar;

use Kotchasan\Http\Request;

/**
 * คืนค่าข้อมูลปฏิทิน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่าข้อมูลปฏิทินเป็น JSON
     *
     * @param Request $request
     *
     * @return \static
     */
    public function toJSON(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && $request->isAjax()) {
            // ค่าที่ส่งมา
            $first = strtotime($request->post('year')->toInt().'-'.$request->post('month')->toInt().'-01');
            $d = date('w', $first);
            // วันที่เริ่มต้นและสิ้นสุดตามที่ปฏิทินแสดงผล
            $from = date('Y-m-d', strtotime('-'.$d.' days', $first));
            $to = date('Y-m-d', strtotime($from.' + 41 days'));
            $events = array();
            // โหลดโมดูลที่ติดตั้งแล้ว
            $modules = \Gcms\Modules::create();
            foreach ($modules->getControllers('Calendar') as $className) {
                if (method_exists($className, 'get')) {
                    // โหลดค่าติดตั้งโมดูล
                    $className::get($from, $to, $events);
                }
            }
            // คืนค่า JSON
            echo json_encode($events);
        }
    }
}
