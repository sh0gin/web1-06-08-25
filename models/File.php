<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "File".
 *
 * @property int $id
 * @property int $name
 * @property int $extension
 * @property int $file_id
 */
class File extends \yii\db\ActiveRecord
{
    public $file;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'File';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'file_id', 'extension'], 'required', 'on' => 'basic'],
            ['file', 'file','maxFiles' => 2, 'on' => 'upload'],
            ['file', 'required', 'on' => 'upload'],
            ['file', 'file', 'extensions' => ['doc', 'pdf', 'docx', 'zip', 'jpeg', 'jpg', 'png'], 'maxSize' => 1024*1024*2, 'on' => 'upload-two'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'file_id' => 'File ID',
        ];
    }
}
