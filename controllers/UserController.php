<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\auth\HttpBearerAuth;

class UserController extends \yii\rest\ActiveController
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
            'only' => ['logout']
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
    
    public function actionLogin()
    {
        $model = new User();
        $model->load(Yii::$app->request->post(), '');
        if ($model->validate()) {
            $model = $model->findOne(['email' => Yii::$app->request->post()['email']]);
            if ($model) {
                if ($model->validatePassword(Yii::$app->request->post()['password'])) {
                    $model->token = Yii::$app->security->generateRandomString();
                    $model->save();
                    return $this->asJson([
                        'seccess' => true,
                        'code' => 200,
                        'message' => 'Success',
                        'token' => $model->token,
                    ]);
                } else {
                    Yii::$app->response->statusCode = 401;

                    return $this->asJson([
                        'seccess' => false,
                        'code' => 401,
                        'message' => 'Authorization failed',
                    ]);
                };
            } else {
                Yii::$app->response->statusCode = 401;

                return $this->asJson([
                    'seccess' => false,
                    'code' => 401,
                    'message' => 'Authorization failed',
                ]);
            }
        } else {
            Yii::$app->response->statusCode = 422;
            return $this->asJson([
                'seccess' => false,
                'code' => 422,
                'message' => [
                    $model->getErrors(),
                ]
            ]);
        }
    }

    public function actionRegister()
    {
        $model = new User(['scenario' => 'register']);
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate()) {
            $model->password = Yii::$app->security->generatePasswordHash($model->password);
            $model->token = Yii::$app->security->generateRandomString();
            $model->save(false);
            Yii::$app->response->statusCode = 201;
            return $this->asJson([
                'success' => true,
                'code' => 201,
                'message' => 'Success',
                'token' => $model->token,
            ]);
        } else {
            Yii::$app->response->statusCode = 422;
            return $this->asJson([
                'seccess' => false,
                'code' => 422,
                'message' => [
                    $model->getErrors(),
                ]
            ]);
        }
    }

    public function actionLogout()
    {
        $model = User::findOne(Yii::$app->user->identity->id);
        $model->token = Null;
        $model->save(false);
        Yii::$app->response->statusCode = 204;
    }
}
