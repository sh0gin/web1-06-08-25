<?php

namespace app\controllers;

use app\models\File;
use app\models\Role;
use app\models\UserAccess;
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
            'only' => ['upload', 'rename-file', 'delete-file', 'download-file']
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
        $files = new File(['scenario' => 'upload']);
        $files->file = UploadedFile::getInstancesByName('files');
        if ($files->validate()) {
            $result_succes = [];
            $name_file_erorred = [];
            foreach ($files->file as $value) {

                $file = new File(['scenario' => 'upload-two']);
                $file->file = $value;
                if ($file->validate()) {
                    $file->scenario = 'basic';
                    $file->file_id = Yii::$app->security->generateRandomString(10);


                    $files_access = UserAccess::findAll(['user_id' => Yii::$app->user->identity->id]);
                    $files_have = [];
                    foreach ($files_access as $value1) {
                        $files_have[] = $value1->file_id;
                    }
                    $files_have = File::findAll($files_have);

                    if ($files_have) {
                        foreach ($files_have as $item) { // задача изменить $value->name || здесь мы сравниваем все файлы пользователя с передынным файлом
                            if (strtolower($item->name) == strtolower($value->name)) {


                                $file_name = str_split($value->name, strripos($value->name, '.'))[0];
                                $file_ext = str_split($value->name, strripos($value->name, '.'))[1];

                                $name_for_regex = preg_quote($file_name);
                                $query = (new Query())->select('name')->from('File')->where(['REGEXP', 'name', "$name_for_regex( \(\d+\))*($file_ext)$"])->orderBy(['id' => SORT_DESC])->limit(1)->one()['name'];
                                $name_last_from_bd = str_split($query, strripos($query, '.'))[0];
                                if ($value->name != $query) {
                                    $position = (int) trim(str_split($name_last_from_bd, strripos($name_last_from_bd, '('))[1], '()') + 1;
                                    $file->name = $file_name . " ($position).$value->extension";
                                    break;
                                } else {
                                    $file->name = "$file_name (2).$value->extension";
                                    break;
                                }
                            } else {
                                $file->name = $value->name;
                            }
                        }
                    } else {
                        $file->name = $value->name;
                    }

                    $value->saveAs(__DIR__ . "/../files/$file->name");
                    if ($file->save(false)) {
                        $access = new UserAccess();
                        $access->user_id = Yii::$app->user->identity->id;
                        $access->file_id = $file->id;
                        $access->user_role = Role::getRole('author');
                        $access->save(false);
                        $result_succes[] = [
                            'seccess' => true,
                            'code' => 200,
                            'message' => 'Success',
                            'name' => $file->name,
                            'url' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/files/$file->name",
                            'file_id' => $file->file_id,
                        ];
                    } else {
                        $name_file_erorred = $file->name;
                    };
                } else {
                    $name_file_erorred[] = [
                        'success' => false,
                        'message' => $file->getErrors(),
                        'name' => $file->file->name,
                    ];
                }
            }
            return $this->asJson([
                $result_succes,
                $name_file_erorred,
            ]);
        } else {
            return $this->asJson([
                'seccess' => false,
                'code' => 422,
                'message' => [
                    $files->getErrors(),
                ]
            ]);
        }
    }

    public function actionRenameFile($file_id)
    {
        $model = File::findOne(['file_id' => $file_id]);
        if ($model) {
            if ($model->user_id == Yii::$app->user->identity->id) {
                $model->name = Yii::$app->request->post()['name'];
                $model->scenario = 'basic';
                $new_name = Yii::$app->request->post()['name'];
                if ($model->validate()) {
                    foreach (File::findAll(['user_id' => Yii::$app->user->identity->id]) as $value) {
                        if (strtolower($value->name) == strtolower($new_name) && $value->id != $model->id) {
                            return $this->asJson([
                                'seccess' => false,
                                'code' => 422,
                                'message' => [
                                    'name' => 'Value must be unique',
                                ],
                            ]);
                        }
                    }
                    $model->save();
                    return $this->asJson([
                        'success' => true,
                        'code' => 200,
                        'message' => 'Renamed',
                    ]);
                } else {
                    return $this->asJson([
                        'seccess' => false,
                        'code' => 422,
                        'message' => [
                            $model->getErrors(),
                        ]
                    ]);
                }
            } else {
                Yii::$app->response->statusCode = 403;
            }
        } else {
            Yii::$app->response->statusCode = 404;
        }
    }

    public function actionDeleteFile($file_id)
    {
        $model = File::findOne(['file_id' => $file_id]);
        if ($model) {
            if ($model->user_id == Yii::$app->user->identity->id) {
                $model->delete();
                return $this->asJson([
                    'success' => true,
                    'code' => 200,
                    'message' => 'File deleted',
                ]);
            } else {
                Yii::$app->response->statusCode = 403;
            }
        } else {
            Yii::$app->response->statusCode = 404;
        }
        return $file_id;
    }

    public function actionDownloadFile($file_id)
    {
        $model = File::findOne(['file_id' => $file_id]);
        if ($model) {
            if (UserAccess::findOne(['file_id' => $model->id, 'user_id' => Yii::$app->user->identity->id])) {
                return Yii::$app->response->sendFile(__DIR__ . "/../files/{$model->name}");
            } else {
                Yii::$app->response->statusCode = 403;
            };
        } else {
            Yii::$app->response->statusCode = 404;
        }
    }
}
