# yii2-date-input-behavior
A general solution to having different date formats for user input vs. storage.

**Currently the only storage format supported is UNIX timestamps**.

## Usage
Let's say we have a model with an date attribute called `published_from`. It's stored as 
a timestamp in the database, and appears in a form with some kind of JavaScript date picker.


### Add behavior to model
Add the behavior to your ActiveRecord.
```php
 return [
    [
        'class' => 'NIOLAB\dateinput\DateAttributeBehavior',
        'inputFormat' => 'dd-MM-y',
       // 'dateInputSuffix' => '__date_input', 
        'dateAttributes' => [
            'published_from',
        ]
    ]
];
```


### Use it in your form
The behavior adds an attribute with a suffix `__date_input` to your model for each attribute defined in `dateAttributes` above. These can be used in forms. In this example, the attribute `published_from__date_input` is created.

Use it in your form with any kind of input, the example here uses a DatePicker widget.

```php
 <?= $form->field($model, 'published_from__date_input')->widget(DatePicker::class, [
        'language' => Yii::$app->language,
        'clientOptions' => [
            'autoclose' => true,
            'todayBtn' => true,
            'format' => 'dd-mm-yyyy',
        ]
    ]) ?
``` 


## How does it work?
The behavior performs magic (for each attribute in `dateAttributes`):
- It adds a virtual attribute `<original_attribute>__date_input` to your model. This attribute contains the human readable formatted date (set on `ActiveRecord::EVENT_AFTER_FIND`)
- It adds a 'safe' validation rule for this attribute so it can be used in massive assignment.
- It updates the original date attribute with the timestamp value of the `<original_attribute>__date_input` virtual attribute on `ActiveRecord::EVENT_BEFORE_VALIDATE`.

The virtual attributes are created by extending the magic functions `__get()` and `__set()` and by adding them to the behavior's `canSetProperty` and `canGetProperty` functions. Hacking Yii2!
