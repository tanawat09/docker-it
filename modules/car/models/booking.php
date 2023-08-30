<?php
/**
 * @filesource modules/car/models/booking.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Booking;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=car-booking
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int   $id
     * @param int   $vehicle_id
     * @param array $login
     *
     * @return object|null
     */
    public static function get($id, $vehicle_id, $login)
    {
        if ($login) {
            if (empty($id)) {
                // ใหม่
                return (object) array(
                    'id' => 0,
                    'vehicle_id' => $vehicle_id,
                    'status' => 0,
                    'today' => 0,
                    'name' => $login['name'],
                    'member_id' => $login['id'],
                    'phone' => isset($login['phone']) ? $login['phone'] : ''
                );
            } else {
                // แก้ไข อ่านรายการที่เลือก
                $sql = Sql::create('(CASE WHEN NOW() BETWEEN V.`begin` AND V.`end` THEN 1 WHEN NOW() > V.`end` THEN 2 ELSE 0 END) AS `today`');
                $query = static::createQuery()
                    ->from('car_reservation V')
                    ->join('user U', 'LEFT', array('U.id', 'V.member_id'))
                    ->where(array('V.id', $id));
                $select = array('V.*', 'U.name', 'U.phone', $sql);
                $n = 1;
                foreach (Language::get('CAR_OPTIONS', array()) as $key => $label) {
                    $query->join('car_reservation_data M'.$n, 'LEFT', array(array('M'.$n.'.reservation_id', 'V.id'), array('M'.$n.'.name', $key)));
                    $select[] = 'M'.$n.'.value '.$key;
                    ++$n;
                }
                return $query->first($select);
            }
        }
        // ไม่ได้เข้าระบบ
        return null;
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (booking.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe()) {
            if ($login = Login::isMember()) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'vehicle_id' => $request->post('vehicle_id')->toInt(),
                        'travelers' => $request->post('travelers')->toInt(),
                        'detail' => $request->post('detail')->textarea(),
                        'comment' => $request->post('comment')->textarea(),
                        'chauffeur' => $request->post('chauffeur')->toInt()
                    );
                    $begin_date = $request->post('begin_date')->date();
                    $begin_time = $request->post('begin_time')->time();
                    $end_date = $request->post('end_date')->date();
                    $end_time = $request->post('end_time')->time();
                    $user = array(
                        'phone' => $request->post('phone')->topic()
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt(), 0, $login);
                    // เจ้าของ ยังไม่ได้อนุมัติ และ ไม่ใช่วันนี้
                    if ($index && ($login['id'] == $index->member_id && $index->status == 0 && $index->today == 0)) {
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
                            // ตรวจสอบ ว่าง
                            if (!\Car\Checker\Model::availability($save)) {
                                $ret['ret_begin_date'] = Language::get('Booking are not available at select time');
                            }
                        } else {
                            // วันที่ ไม่ถูกต้อง
                            $ret['ret_end_date'] = Language::get('End date must be greater than begin date');
                        }
                        $datas = array();
                        // ตัวแปรสำหรับตรวจสอบการแก้ไข
                        $options_check = array();
                        foreach (Language::get('CAR_OPTIONS', array()) as $key => $label) {
                            $options_check[] = $key;
                            $values = $request->post($key, array())->toInt();
                            if (!empty($values)) {
                                $datas[$key] = implode(',', $values);
                            }
                        }
                        if (empty($ret)) {
                            // Database
                            $db = $this->db();
                            if ($index->id == 0) {
                                // ใหม่
                                $save['status'] = self::$cfg->car_status;
                                if ($save['status'] == self::$cfg->car_approved_status) {
                                    // อนุมัติทันที
                                    $save['approver'] = $login['id'];
                                    $save['approved_date'] = date('Y-m-d H:i:s');
                                } else {
                                    // รอตรวจสอบ
                                    $save['approver'] = 0;
                                    $save['approved_date'] = null;
                                }
                                $save['member_id'] = $login['id'];
                                $save['create_date'] = date('Y-m-d H:i:s');
                                $index->id = $db->insert($this->getTableName('car_reservation'), $save);
                                // ใหม่ ส่งอีเมลเสมอ
                                $changed = true;
                            } else {
                                // แก้ไข
                                $db->update($this->getTableName('car_reservation'), $index->id, $save);
                                // ตรวจสอบการแก้ไข
                                $changed = false;
                                if (!empty(self::$cfg->car_notifications)) {
                                    foreach ($save as $key => $value) {
                                        if ($value != $index->{$key}) {
                                            $changed = true;
                                            break;
                                        }
                                    }
                                    if (!$changed) {
                                        foreach ($options_check as $key) {
                                            if (isset($datas[$key])) {
                                                if ($datas[$key] != $index->{$key}) {
                                                    $changed = true;
                                                    break;
                                                }
                                            } elseif ($index->{$key} != '') {
                                                $changed = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                $save['member_id'] = $index->member_id;
                                $save['status'] = $index->status;
                                $save['approver'] = $index->approver;
                                $save['approved_date'] = $index->approved_date;
                                $save['create_date'] = $index->create_date;
                            }
                            if ($index->phone != $user['phone']) {
                                if (!empty(self::$cfg->car_notifications)) {
                                    $changed = true;
                                }
                                // อัปเดตเบอร์โทรสมาชิก
                                $db->update($this->getTableName('user'), $login['id'], $user);
                            }
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
                            // log
                            \Index\Log\Model::add($index->id, 'car', 'Status', Language::find('BOOKING_STATUS', '', $save['status']), $login['id']);
                            if (empty($ret) && $changed) {
                                // ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                                $save['id'] = $index->id;
                                $ret['alert'] = \Car\Email\Model::send($save);
                            } else {
                                // ไม่ส่งอีเมล
                                $ret['alert'] = Language::get('Saved successfully');
                            }
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'car', 'status' => $save['status']));
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
