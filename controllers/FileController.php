<?php

namespace app\controllers;

use app\models\File;
use Yii;
use yii\db\Query;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

class FileController extends \yii\rest\ActiveController
{


    public $modelClass = '';
    public $enableCsrfValidation = '';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => [isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_OROGIN'] : 'http://' . $_SERVER['REMOTE_ADDR']],
                // 'Origin' => ["*"],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
            'actions' => [
                'logout' => [
                    'Access-Control-Allow-Credentials' => true,
                ]
            ]
        ];
        $auth = [
            'class' => HttpBearerAuth::class,
            'only' => ['upload']
        ];
        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['delete'], $actions['create']);

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionUpload()
    {
        return 123;
        // $files = new File(['scenario' => 'upload']);
        // $files->file = UploadedFile::getInstancesByName('files');
        // if ($files->validate()) {
        //     $result_succes = [];
        //     foreach ($files->file as $value) {
        //         $file = new File(['scenario' => 'basic']);
        //         $file->file_id = Yii::$app->security->generateRandomString(10);
        //         $file->user_id = 2;

        //         foreach (File::findAll(['user_id' => 2]) as $item) { // задача изменить $value->name || здесь мы сравниваем все файлы пользователя с передынным файлом
        //             if ($item->name == $value->name) {

        //                 $file_name = str_split($value->name, strripos($value->name, '.'))[0];
        //                 $file_ext = str_split($value->name, strripos($value->name, '.'))[1];

        //                 $query = (new Query())->select('name')->from('File')->where(['REGEXP', 'name', "^$file_name( \(\d+\))*($file_ext)$"])->orderBy(['id' => SORT_DESC])->limit(1)->one()['name'];
                        
        //                 $name_last_from_bd = str_split($query, strripos($query, '.'))[0];
        //                 if (strripos($name_last_from_bd, '(')) {
        //                     $name_last_from_bd[-2] = (int) $name_last_from_bd[-2] + 1;
        //                     $name_last_from_bd .= ".$value->extension";
        //                     $file->name = $name_last_from_bd;
        //                 } else {
        //                     $file->name = "$file_name (2).$value->extension";
        //                 }
        //             } else {
        //                 $file->name = $value->name;
        //             };
        //         }
        //         // $file->saveAs(__DIR__ . "/../files/$file->name");
        //         // if ($file->save(false)) {
        //         //     $result_succes = [
        //         //         'seccess' => true,
        //         //         'code' => 200,
        //         //         'message' => 'Success',
        //         //         'name' => $file->name,
        //         //     ];
        //         // };
        //     }
        // } else {
        //     return $files->getErrors();
        // }
    }
}
