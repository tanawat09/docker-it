<?php
/**
 * @filesource modules/car/views/order.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Order;

use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=car-order
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มแก้ไข การจอง (admin)
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/car/model/order/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Booking}'
        ));
        $groups = $fieldset->add('groups');
        // vehicle_id
        $groups->add('text', array(
            'id' => 'vehicle_id',
            'labelClass' => 'g-input icon-shipping',
            'itemClass' => 'width50',
            'label' => '{LNG_Vehicle}',
            'placeholder' => Language::replace('Search :name and select from the list', array(':name' => 'Vehicle')),
            'datalist' => \Car\Vehicles\Model::toSelect(),
            'value' => $index->vehicle_id
        ));
        // travelers
        $groups->add('number', array(
            'id' => 'travelers',
            'labelClass' => 'g-input icon-group',
            'itemClass' => 'width50',
            'label' => '{LNG_Number of travelers}',
            'unit' => '{LNG_persons}',
            'value' => $index->travelers
        ));
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Usage details}',
            'rows' => 3,
            'value' => $index->detail
        ));
        $groups = $fieldset->add('groups');
        // name
        $groups->add('text', array(
            'id' => 'name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'width50',
            'label' => '{LNG_Contact name}',
            'disabled' => true,
            'value' => $index->name
        ));
        // phone
        $groups->add('text', array(
            'id' => 'phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'disabled' => true,
            'value' => $index->phone
        ));
        $groups = $fieldset->add('groups');
        // begin_date
        $begin = empty($index->begin) ? time() : strtotime($index->begin);
        $groups->add('date', array(
            'id' => 'begin_date',
            'label' => '{LNG_Begin date}',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'value' => date('Y-m-d', $begin)
        ));
        // begin_time
        $groups->add('time', array(
            'id' => 'begin_time',
            'label' => '{LNG_Begin time}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'value' => date('H:i', $begin)
        ));
        $groups = $fieldset->add('groups');
        // end_date
        $end = empty($index->end) ? time() : strtotime($index->end);
        $groups->add('date', array(
            'id' => 'end_date',
            'label' => '{LNG_End date}',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'value' => date('Y-m-d', $end)
        ));
        // end_time
        $groups->add('time', array(
            'id' => 'end_time',
            'label' => '{LNG_End time}',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'value' => date('H:i', $end)
        ));
        // ตัวเลือก checkbox
        $category = \Car\Category\Model::init();
        foreach (Language::get('CAR_OPTIONS', array()) as $key => $label) {
            if (!$category->isEmpty($key)) {
                $fieldset->add('checkboxgroups', array(
                    'id' => $key,
                    'labelClass' => 'g-input icon-list',
                    'itemClass' => 'item',
                    'label' => $label,
                    'options' => $category->toSelect($key),
                    'value' => isset($index->{$key}) ? explode(',', $index->{$key}) : array()
                ));
            }
        }
        // comment
        $fieldset->add('textarea', array(
            'id' => 'comment',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Other}',
            'rows' => 3,
            'value' => $index->comment
        ));
        // chauffeur
        $fieldset->add('select', array(
            'id' => 'chauffeur',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'item',
            'label' => '{LNG_Chauffeur}',
            'options' => array(-1 => '{LNG_Do not want}', 0 => '{LNG_Not specified (anyone)}')+\Car\Chauffeur\Model::init($index->chauffeur)->toSelect(),
            'value' => $index->chauffeur
        ));
        // status
        $fieldset->add('select', array(
            'id' => 'status',
            'labelClass' => 'g-input icon-star0',
            'itemClass' => 'item',
            'label' => '{LNG_Status}',
            'options' => Language::get('BOOKING_STATUS'),
            'value' => $index->status
        ));
        if ($index->status > 0) {
            $groups = $fieldset->add('groups');
            // approver
            $groups->add('text', array(
                'id' => 'approver',
                'label' => '{LNG_Approver}',
                'labelClass' => 'g-input icon-customer',
                'itemClass' => 'width50',
                'disabled' => true,
                'value' => $index->approver_name
            ));
            // approved_date
            $groups->add('text', array(
                'id' => 'approved_date',
                'label' => '{LNG_Approval date}',
                'labelClass' => 'g-input icon-calendar',
                'itemClass' => 'width50',
                'disabled' => true,
                'value' => Date::format($index->approved_date)
            ));
        }
        // reason
        $fieldset->add('text', array(
            'id' => 'reason',
            'labelClass' => 'g-input icon-question',
            'itemClass' => 'item',
            'label' => '{LNG_Reason}',
            'maxlength' => 128,
            'value' => $index->reason
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        $can_manage_car = true;
        if ($login['id'] != 1) {
            if (empty(self::$cfg->car_approving) && $index->today == 2) {
                $can_manage_car = false;
            } elseif (self::$cfg->car_approving == 1 && $index->remain < 0) {
                $can_manage_car = false;
            }
        }
        if ($can_manage_car) {
            // submit
            $fieldset->add('submit', array(
                'class' => 'button ok large icon-save',
                'value' => '{LNG_Save}'
            ));
            // send_mail
            $fieldset->add('checkbox', array(
                'id' => 'send_mail',
                'labelClass' => 'inline-block middle',
                'label' => '&nbsp;{LNG_Send a notification message to the person concerned}',
                'value' => 1
            ));
        }
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
