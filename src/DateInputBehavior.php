<?php

namespace NIOLAB\dateinput;

use yii\base\InvalidConfigException;
use yii\helpers\StringHelper;
use Yii;
use yii\base\Behavior;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\validators\SafeValidator;
use yii\validators\Validator;

class DateInputBehavior extends Behavior
{

    public $dateInputSuffix = '__date_input';
    /** @var array Array of model scenarios under which to enable this behavior */
    public $only = [];
    /** @var array Array of model scenarios under which to disable this behavior */
    public $except = [];
    protected $inputs = [];

    /**
     * @var \yii\validators\Validator[]
     */
    protected $validators = []; // track references of appended validators


    /**
     * @var array list of model attributes that are dates
     *
     * ```php
     * [
     *     'date'
     * ]
     * ```
     */
    public $dateAttributes = [];

    /** @var string A format for yii\i18n\Formatter:asDate() */
    public $inputFormat = 'd MMMM yyyy';

    public $translateDates = true;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_VALIDATE => "beforeValidate"
        ];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $suffix = $this->dateInputSuffix;
        $inputAttributes = array_map(function($a) use ($suffix) {
            return $a . $suffix;
        },$this->dateAttributes);
        $rule = new SafeValidator([
            'attributes' => $inputAttributes,
        ]);

        $validators = $owner->validators;
        $validators->append($rule);
        $this->validators[] = $rule;
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        $ownerValidators = $this->owner->validators;
        $cleanValidators = [];
        foreach ($ownerValidators as $validator) {
            if (!in_array($validator, $this->validators)) {
                $cleanValidators[] = $validator;
            }
        }
        $ownerValidators->exchangeArray($cleanValidators);
    }

    public function __get($name)
    {
        if (StringHelper::endsWith($name,$this->dateInputSuffix)) {
            $originalName = substr($name,0,-strlen($this->dateInputSuffix));
            if (in_array($originalName,$this->dateAttributes)) {
                return isset($this->inputs[$originalName]) ? $this->inputs[$originalName] : null;
            } else {
                return parent::__get($name);
            }
        } else {
            return parent::__get($name);
        }

    }

    public function __set($name, $value)
    {
        if (StringHelper::endsWith($name,$this->dateInputSuffix)) {
            $originalName = substr($name,0,-strlen($this->dateInputSuffix));
            if (in_array($originalName,$this->dateAttributes)) {
                $this->inputs[$originalName] = $value;
            } else {
                parent::__set($name, $value);
            }
        } else {
            parent::__set($name, $value);
        }

    }


    public function canGetProperty($name, $checkVars = true)
    {
        if (StringHelper::endsWith($name,$this->dateInputSuffix)) {
            $originalName = substr($name, 0, -strlen($this->dateInputSuffix));
            if (in_array($originalName, $this->dateAttributes)) {
                return true;
            }
        }
        return parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true)
    {

        if (StringHelper::endsWith($name,$this->dateInputSuffix)) {
            $originalName = substr($name, 0, -strlen($this->dateInputSuffix));
            if (in_array($originalName, $this->dateAttributes)) {
                return true;
            }
        }
        return parent::canSetProperty($name, $checkVars);
    }

    public function afterFind($event)
    {
        if (in_array($this->owner->scenario,$this->except, true)) {
            return;
        }
        if (count($this->only) > 0 && !in_array($this->owner->scenario, $this->only, true)) {
            return;
        }
        foreach ($this->dateAttributes as $dateAttribute) {
            $inputAttribute = $dateAttribute . $this->dateInputSuffix;
            if ($this->owner->$dateAttribute !== null) {
                $this->owner->$inputAttribute = $this->toFormattedDate($this->owner->$dateAttribute);
            } elseif (!$this->owner->isNewRecord) {
                $this->owner->$inputAttribute = null;
            }
        }
    }

    public function beforeValidate($event)
    {
        if (in_array($this->owner->scenario,$this->except)) {
            return;
        }
        if (count($this->only) > 0 && !in_array($this->owner->scenario, $this->only, true)) {
            return;
        }
        foreach ($this->dateAttributes as $dateAttribute) {
            $inputAttribute = $dateAttribute . $this->dateInputSuffix;
            if ($this->owner->$inputAttribute !== null) {
                if (empty($this->owner->$inputAttribute)) {
                    $this->owner->$dateAttribute = null;
                } else {
                    $this->owner->$dateAttribute = $this->toTimestamp($this->owner->$inputAttribute);
                }
            }
        }
    }

    public function toTimestamp($formattedDate)
    {
        if (is_int($formattedDate)) return $formattedDate;
        $date = $this->translateDates ? $this->translateDate($formattedDate) : $formattedDate;
        return strtotime($date);
    }

    public function toFormattedDate($timestamp)
    {
        return Yii::$app->formatter->asDate($timestamp,$this->inputFormat);
    }


    protected function translateDate($date)
    {
        return str_ireplace([
            'maandag',
            'dinsdag',
            'woensdag',
            'donderdag',
            'vrijdag',
            'zaterdag',
            'zondag',
            'januari',
            'februari',
            'maart',
            'april',
            'mei',
            'juni',
            'juli',
            'augustus',
            'september',
            'oktober',
            'november',
            'december'
        ],
            [
                'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday',
                'January','February','March','April','May','June','July','August','September','October','November','December'
            ],
            $date);
    }

}