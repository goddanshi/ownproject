<?php
namespace backend\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\LoginForm;
use common\models\User;

class ApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Убираем authenticator чтобы не требовать токен
        unset($behaviors['authenticator']);

        // CORS для доступа с фронтенда
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['http://81.19.136.133', 'http://localhost:5173'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        return $behaviors;
    }

    // POST /api/login
    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new LoginForm();
        $data = Yii::$app->request->post();

        if ($model->load($data, '') && $model->login()) {
            $user = Yii::$app->user->identity;
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid credentials',
            'errors' => $model->errors
        ];
    }

    // GET /api/check
    public function actionCheck()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => Yii::$app->user->id,
                'username' => Yii::$app->user->identity->username,
            ]
        ];
    }

    // POST /api/logout
    public function actionLogout()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->user->logout();

        return ['success' => true, 'message' => 'Logged out'];
    }
}