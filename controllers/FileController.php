<?php

namespace app\controllers;

use app\models\File;
use app\models\Role;
use app\models\User;
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
            'only' => ['upload', 'rename-file', 'delete-file', 'download-file', 'add-access', 'delete-access', 'get-files', 'get-files-so-author'],
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
                            if (strtolower("$item->name.$item->extension") == strtolower($value->name)) {


                                $file_name = str_split($value->name, strripos($value->name, '.'))[0];
                                $file_ext = trim(str_split($value->name, strripos($value->name, '.'))[1], '.');
                                $name_for_regex = preg_quote($file_name);
                                $query = (new Query())->select('name')->from('File')->where(['REGEXP', 'name', "$name_for_regex( \(\d+\))*"])->andWhere(['extension' => $file_ext])->orderBy(['id' => SORT_DESC])->limit(1)->one()['name'];

                                if ($file_name != $query) {
                                    $position = (int) trim(str_split($query, strripos($query, '('))[1], '()') + 1;
                                    $file->name = $file_name . " ($position)";
                                    break;
                                } else {
                                    $file->name = "$file_name (2)";
                                    break;
                                }
                            } else {
                                $file->name = str_split($value->name, strripos($value->name, '.'))[0];
                            }
                        }
                    } else {
                        $file->name = str_split($value->name, strripos($value->name, '.'))[0];
                    }

                    $file->extension = $value->extension;
                    $value->saveAs(__DIR__ . "/../files/$file->name.$file->extension");
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
                            'url' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/files/$file->name.$file->extension",
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
        $access = UserAccess::findOne(['file_id' => $model->id]);

        if ($model) {
            if ($access->user_id == Yii::$app->user->identity->id) {
                $model->name = Yii::$app->request->post()['name'];
                $model->scenario = 'basic';
                $new_name = Yii::$app->request->post()['name'];
                if ($model->validate()) {
                    $files_access = UserAccess::findAll(['user_id' => Yii::$app->user->identity->id]);
                    $files_have = [];
                    foreach ($files_access as $value1) {
                        $files_have[] = $value1->file_id;
                    }
                    foreach (File::findAll(['id' => $files_have]) as $value) {
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
        $access = UserAccess::findOne(['file_id' => $model->id]);
        if ($model) {
            if ($access->user_id == Yii::$app->user->identity->id) {
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
                return Yii::$app->response->sendFile(__DIR__ . "/../files/{$model->name}.{$model->extension}");
            } else {
                Yii::$app->response->statusCode = 403;
            };
        } else {
            Yii::$app->response->statusCode = 404;
        }
    }

    public function actionAddAccess($file_id)
    {
        $file = File::findOne(["file_id" => $file_id]);
        if ($file) {
            $leader = User::findOne(Yii::$app->user->identity->id);
            $access = UserAccess::findOne(['file_id' => $file->id, 'user_id' => $leader->id, 'user_role' => 2]);
            if ($access) {
                $new_user = User::findOne(['email' => Yii::$app->request->post()]);
                if ($new_user) {
                    // здесь можно добавить проверку на то чтобы пользователь не мог добавить сам себя, надо ли?:) в задании такого нет условия, странно добавлять себя же ещё раз
                    if ($new_user != $leader && !UserAccess::findOne(['user_id' => $new_user->id, 'file_id' => $file->id])) {
                        $new_access = new UserAccess;
                        $new_access->file_id = $file->id;
                        $new_access->user_id = $new_user->id;
                        $new_access->user_role = Role::getRole('co-author');
                        $new_access->save(false);

                        $user_all_access = [];

                        foreach (UserAccess::findAll(['file_id' => $file]) as $value) {
                            $file_ = File::findOne([$value->file_id]);
                            $user_ = User::findOne([$value->user_id]);

                            $user_all_access[] = [
                                'fullname' => $file_->name,
                                'email' => $user_->email,
                                'type' => Role::getRoleName($value->user_role),
                                'code' => 200,
                            ];
                        }

                        return $this->asJson(
                            $user_all_access,
                        );
                    } else {
                        Yii::$app->response->statusCode = 403;
                    }
                } else {
                    Yii::$app->response->statusCode = 404;
                }
            } else {
                Yii::$app->response->statusCode = 403;
            }
        } else {
            Yii::$app->response->statusCode = 404;
        }
    }

    public function actionDeleteAccess($file_id)
    {
        $email_new_user = Yii::$app->request->post()['email'];
        $model_user_new = User::findOne(['email' => $email_new_user]);
        $model_file = File::findOne(['file_id' => $file_id]);
        if ($model_user_new && $model_file) {
            $user_access = UserAccess::findOne(['file_id' => $model_file->id, 'user_id' => $model_user_new->id]);
            if ($user_access) {
                $leader = User::findOne(Yii::$app->user->identity->id);
                $leader_access = UserAccess::findOne(['file_id' => $model_file->id, 'user_id' => $leader->id, 'user_role' => 2]);
                if ($leader->email != $email_new_user && $leader_access) {
                    $user_access->delete();

                    $user_all_access = [];

                    foreach (UserAccess::findAll(['file_id' => $model_file->id]) as $value) {
                        $file_ = File::findOne([$value->file_id]);
                        $user_ = User::findOne([$value->user_id]);

                        $user_all_access[] = [
                            'fullname' => $file_->name,
                            'email' => $user_->email,
                            'type' => Role::getRoleName($value->user_role),
                            'code' => 200,
                        ];
                    }
                    return $this->asJson(
                        $user_all_access,
                    );
                } else {
                    Yii::$app->response->statusCode = 403;
                }
            } else {
                Yii::$app->response->statusCode = 404;
            }
        } else {
            Yii::$app->response->statusCode = 404;
        }
    }

    public function actionGetFiles()
    {
        return 123;
        $all_files = [];
        foreach (UserAccess::findAll(['user_id' => Yii::$app->user->identity->id, 'user_role' => 2]) as $value) {
            $file = File::findOne($value->file_id);
            $users = [];
            foreach (UserAccess::findAll(['file_id' => $file->id]) as $item) {
                $model_user = User::findOne($item->user_id);
                $users[] = [
                    'fullname' => "$model_user->first_name $model_user->last_name",
                    'email' => "$model_user->email",
                    'type' => Role::getRoleName($item->user_role),
                ];
            }
            $all_files[] = [
                'file_id' => $file->id,
                'name' => $file->name,
                'code' => 200,
                'url' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/files/$file->name.$file->extension",
                'accesses' => [
                    $users,
                ]
            ];
        }

        return $this->asJson($all_files);
    }

    public function actionGetFilesSoAuthor()
    {
        $all_files = [];
        foreach (UserAccess::findAll(['user_id' => Yii::$app->user->identity->id, 'user_role' => 1]) as $value) {
            $file = File::findOne($value->file_id);
            $all_files[] = [
                'file_id' => $file->id,
                'code' => 200,
                'name' => $file->name,
                'url' => "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/files/$file->name.$file->extension",
            ];
        }
        return $this->asJson($all_files);
    }
}
