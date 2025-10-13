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

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    // POST /api/register
    public function actionRegister()
    {
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
        $data = Yii::$app->request->post();

        $model = new LoginForm();
        $model->username = $data['username'] ?? '';
        $model->password = $data['password'] ?? '';

        if ($model->login()) {
            $user = Yii::$app->user->identity;

            // Генерация JWT
            $jwt = Yii::$app->jwt;
            $signer = $jwt->getSigner('HS256');
            $key = $jwt->getKey();
            $time = time();

            $token = $jwt->getBuilder()
                ->issuedBy('http://81.19.136.133') // кто выдал
                ->permittedFor('http://81.19.136.133') // кому
                ->issuedAt($time)
                ->expiresAt($time + 3600 * 24 * 7) // 7 дней
                ->withClaim('uid', $user->id)
                ->getToken($signer, $key);

            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => (string) $token,
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
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Missing token'];
        }

        $token = $matches[1];
        $jwt = Yii::$app->jwt;

        try {
            $parsed = $jwt->getParser()->parse((string)$token);
            $uid = $parsed->claims()->get('uid');

            $user = User::findOne($uid);
            if ($user) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ]
                ];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        return ['success' => false];
    }

    // POST /api/logout
    public function actionLogout()
    {
        return ['success' => true, 'message' => 'Logout (client side only)'];
    }
}
