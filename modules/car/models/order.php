<?php
/**
 * @filesource modules/car/models/order.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Order;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=car-order
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $query = static::createQuery()
            ->from('car_reservation V')
            ->join('user U', 'LEFT', array('U.id', 'V.member_id'))
            ->join('user A', 'LEFT', array('A.id', 'V.approver'))
            ->where(array('V.id', $id));
        $select = array('V.*', 'U.name', 'U.phone', 'U.username', 'A.name approver_name');
        $n = 1;
        foreach (Language::get('CAR_OPTIONS', array()) as $key => $label) {
            $query->join('car_reservation_data M'.$n, 'LEFT', array(array('M'.$n.'.reservation_id', 'V.id'), array('M'.$n.'.name', $key)));
            $select[] = 'M'.$n.'.value '.$key;
            ++$n;
        }
        return $query->first($select);
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (order.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, สามารถอนุมัติได้
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_approve_car')) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'vehicle_id' => $request->post('vehicle_id')->toInt(),
                        'travelers' => $request->post('travelers')->toInt(),
                        'detail' => $request->post('detail')->textarea(),
                        'comment' => $request->post('comment')->textarea(),
                        'status' => $request->post('status')->toInt(),
                        'reason' => $request->post('reason')->topic(),
                        'chauffeur' => $request->post('chauffeur')->toInt()
                    );
                    $begin_date = $request->post('begin_date')->date();
                    $begin_time = $request->post('begin_time')->time();
                    $end_date = $request->post('end_date')->date();
                    $end_time = $request->post('end_time')->time();
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    if ($index) {
                        if ($save['vehicle_id'] == 0) {
                            // ไม่ได้เลือก vehicle_id
                            $ret['ret_vehicle_id'] = Language::replace('Search :name and select from the list', array(':name' => 'Vehicle'));
                        }
                        if ($save['travelers'] == 0) {
                            // ไม่ได้กรอก travelers
                            $ret['ret_travelers'] = 'Please fill in';
                        }
                        if ($save['detail'] == '') {
                            // ไม่ได้กรอก detail
                            $ret['ret_detail'] = 'Please fill in';
                        }
                        if (empty($begin_date)) {
                            // ไม่ได้กรอก begin_date
                            $ret['ret_begin_date'] = 'Please fill in';
                        }
                        if (empty($begin_time)) {
                            // ไม่ได้กรอก begin_time
                            $ret['ret_begin_time'] = 'Please fill in';
                        }
                        if (empty($end_date)) {
                            // ไม่ได้กรอก end
                            $ret['ret_end_date'] = 'Please fill in';
                        }
                        if (empty($end_time)) {
                            // ไม่ได้กรอก end_time
                            $ret['ret_end_time'] = 'Please fill in';
                        }
                        if ($end_date.$end_time > $begin_date.$begin_time) {
                            $save['begin'] = $begin_date.' '.$begin_time.':01';
                            $save['end'] = $end_date.' '.$end_time.':00';
                            $save['id'] = $index->id;
                            $save['member_id'] = $index->member_id;
                            $save['vehicle_id'] = $index->vehicle_id;
                            // ตรวจสอบ ว่าง
                            if (!\Car\Checker\Model::availability($save)) {
                                $ret['ret_begin_date'] = Language::get('Booking are not available at select time');
                            }
                        } else {
                            // วันที่ ไม่ถูกต้อง
                            $ret['ret_end_date'] = Language::get('End date must be greater than begin date');
                        }
                        $datas = array();
                        foreach (Language::get('CAR_OPTIONS', array()) as $key => $label) {
                            $values = $request->post($key, array())->toInt();
                            if (!empty($values)) {
                                $datas[$key] = implode(',', $values);
                            }
                        }
                        if ($save['status'] == 1 && $save['chauffeur'] == 0) {
                            // ไม่ได้เลือก chauffeur
                            $ret['ret_chauffeur'] = 'Please select';
                        } elseif ($save['status'] == 2 && $save['reason'] == '') {
                            // ไม่ได้กรอก reason
                            $ret['ret_reason'] = 'Please fill in';
                        }
                        if (empty($ret)) {
                            // Database
                            $db = $this->db();
                            $save['approver'] = $index->approver;
                            $save['approved_date'] = $index->approved_date;
                            if ($save['status'] != $index->status) {
                                if ($save['status'] == self::$cfg->car_approved_status) {
                                    // อนุมัติทันที
                                    $save['approver'] = $login['id'];
                                    $save['approved_date'] = date('Y-m-d H:i:s');
                                } else {
                                    // รอตรวจสอบ
                                    $save['approver'] = 0;
                                    $save['approved_date'] = null;
                                }
                            }
                            // save
                            $db->update($this->getTableName('car_reservation'), $index->id, $save);
                            // รายละเอียดการจอง
                            $table = $this->getTableName('car_reservation_data');
                            $db->delete($table, array('reservation_id', $index->id), 0);
                            foreach ($datas as $key => $value) {
                                if ($value != '') {
                                    $db->insert($table, array(
                                        'reservation_id' => $index->id,
                                        'name' => $key,
                                        'value' => $value
                                    ));
                                }
                                $save[$key] = $value;
                            }
                            if ($request->post('send_mail')->toBoolean()) {
                                // ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                                $ret['alert'] = \Car\Email\Model::send($save);
                            } else {
                                // ไม่ส่งอีเมล
                                $ret['alert'] = Language::get('Saved successfully');
                            }
                            // log
                            \Index\Log\Model::add($index->id, 'car', 'Status', Language::find('BOOKING_STATUS', '', $save['status']), $login['id']);
                            // location
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'car-report', 'status' => $save['status']));
                            // เคลียร์
                            $request->removeToken();
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
