<?php
/**
 * @filesource modules/car/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Car\Settings;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=car-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มตั้งค่า
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/car/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        // car_w
        $fieldset->add('text', array(
            'id' => 'car_w',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'item',
            'label' => '{LNG_Size of} {LNG_Image} ({LNG_Width})',
            'comment' => '{LNG_Image size is in pixels} ({LNG_resized automatically})',
            'value' => isset(self::$cfg->car_w) ? self::$cfg->car_w : 600
        ));
        // chauffeur_status
        $fieldset->add('select', array(
            'id' => 'chauffeur_status',
            'labelClass' => 'g-input icon-star0',
            'itemClass' => 'item',
            'label' => '{LNG_Chauffeur}',
            'comment' => '{LNG_Status of members who are drivers}',
            'options' => self::$cfg->member_status,
            'value' => isset(self::$cfg->chauffeur_status) ? self::$cfg->chauffeur_status : 2
        ));
        // car_status
        $fieldset->add('select', array(
            'id' => 'car_status',
            'labelClass' => 'g-input icon-valid',
            'itemClass' => 'item',
            'label' => '{LNG_Initial booking status}',
            'options' => Language::get('BOOKING_STATUS'),
            'value' => isset(self::$cfg->car_status) ? self::$cfg->car_status : 0
        ));
        // car_approving
        $fieldset->add('select', array(
            'id' => 'car_approving',
            'labelClass' => 'g-input icon-write',
            'itemClass' => 'item',
            'label' => '{LNG_Approving/editing reservations}',
            'options' => Language::get('APPROVING_RESERVATIONS'),
            'value' => isset(self::$cfg->car_approving) ? self::$cfg->car_approving : 0
        ));
        // car_cancellation
        $fieldset->add('select', array(
            'id' => 'car_cancellation',
            'labelClass' => 'g-input icon-warning',
            'itemClass' => 'item',
            'label' => '{LNG_Cancellation}',
            'options' => Language::get('CANCEL_RESERVATIONS'),
            'value' => isset(self::$cfg->car_cancellation) ? self::$cfg->car_cancellation : 0
        ));
        // car_delete
        $fieldset->add('select', array(
            'id' => 'car_delete',
            'labelClass' => 'g-input icon-delete',
            'itemClass' => 'item',
            'label' => '{LNG_Delete items that have been canceled by the booker}',
            'options' => Language::get('BOOLEANS'),
            'value' => isset(self::$cfg->car_delete) ? self::$cfg->car_delete : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-comments',
            'title' => '{LNG_Notification}'
        ));
        $booleans = Language::get('BOOLEANS');
        // car_email
        $fieldset->add('select', array(
            'id' => 'car_email',
            'labelClass' => 'g-input icon-email',
            'label' => '{LNG_Send notification messages When making a transaction}',
            'itemClass' => 'item',
            'options' => $booleans,
            'value' => isset(self::$cfg->car_email) ? self::$cfg->car_email : 1
        ));
        // car_notifications
        $fieldset->add('select', array(
            'id' => 'car_notifications',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Notify relevant parties when booking details are modified by customers}',
            'options' => $booleans,
            'value' => isset(self::$cfg->car_notifications) ? self::$cfg->car_notifications : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
