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
        unset($behaviors['authenticator']);

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

    // POST /api/register
    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        $user = new User();
        $user->username = $data['username'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->setPassword($data['password'] ?? '');
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->created_at = time();
        $user->updated_at = time();

        if ($user->save()) {
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Registration failed',
            'errors' => $user->errors
        ];
    }

    // POST /api/login
    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        $model = new LoginForm();
        $model->username = $data['username'] ?? '';
        $model->password = $data['password'] ?? '';
        $model->rememberMe = true;

        if ($model->login()) {
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
            'message' => 'Invalid username or password',
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
                'email' => Yii::$app->user->identity->email,
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