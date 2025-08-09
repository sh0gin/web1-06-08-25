<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'dassdfgsdf',
            'baseURL' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser',
            ]
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode == 404) {
                    $response->data = [
                        'message' => 'not found',
                    ];
                }
                if ($response->statusCode == 403) {
                    $response->data = [
                        'message' => 'forbidden for you',
                    ];
                }
                if ($response->statusCode == 401) {
                    Yii::$app->response->statusCode = 403;
                    $response->data = [
                        'message' => 'login failed',
                        'code' => 403,
                    ];
                }
            },
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    // ...
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'file',
                    'pluralize' => true,
                    'extraPatterns' => [

                        
                        "GET disk" => "get-files",
                        "OPTIONS disk" => "options",
                        
                        "GET shared" => "get-files-so-author",
                        "OPTIONS shared" => "options",
                        
                        'PATCH <file_id>' => 'rename-file',
                        'OPTIONS <file_id>' => 'options',
                        
                        'DELETE <file_id>' => 'delete-file',
                        'OPTIONS <file_id>' => 'options',
                        
                        'GET <file_id>' => 'download-file',
                        'OPTIONS <file_id>' => 'options',
                        
                        "POST <file_id>/accesses" => "add-access",
                        "OPTIONS <file_id>/accesses" => "options",
                        
                        "DELETE <file_id>/accesses" => "delete-access",
                        "OPTIONS <file_id>/accesses" => "options",
                        
                    ]
                ],

                "POST authorization" => 'user/login',
                "OPTIONS authorization" => 'options',

                "POST registration" => 'user/register',
                "OPTIONS registration" => 'options',

                "GET logout" => 'user/logout',
                "OPTIONS logout" => 'options',

                "POST file" => 'file/upload',
                "OPTIONS file" => 'options',


                // "POST file" => 'file/upload',
                // "OPTIONS file" => 'options',

                // "POST files/<file_id>" => 'file/upload',
                // "OPTIONS files/<file_id>" => 'options',

                // "POST " => 'user/logout',
                // "OPTIONS " => 'options',

                // "POST " => 'user/logout',
                // "OPTIONS " => 'options',

                // "POST " => 'user/logout',
                // "OPTIONS " => 'options',

                // "POST " => 'user/logout',
                // "OPTIONS " => 'options',


            ],
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
